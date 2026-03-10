<?php
declare(strict_types=1);

namespace UnraidTemplateManager;

use RuntimeException;

final class TemplateTransferService
{
    private string $templatesDir;
    private string $exportDir;
    private BackupService $backupService;

    public function __construct(string $templatesDir, string $exportDir, BackupService $backupService)
    {
        $this->templatesDir = $templatesDir;
        $this->exportDir = $exportDir;
        $this->backupService = $backupService;
    }

    /**
     * @param array<int, string>|null $filenames
     * @return array<string, mixed>
     */
    public function createExportArchive(?array $filenames = null): array
    {
        $selected = $this->resolveExportFiles($filenames);
        if (count($selected) === 0) {
            throw new RuntimeException('No template files selected for export.');
        }

        if (!is_dir($this->exportDir) && !@mkdir($this->exportDir, 0775, true) && !is_dir($this->exportDir)) {
            throw new RuntimeException('Unable to create export directory.');
        }

        $exportId = 'template-export-' . date('Ymd-His');
        $stagingDir = $this->exportDir . '/.staging-' . $exportId . '-' . $this->randomSuffix();
        if (!@mkdir($stagingDir, 0775, true) && !is_dir($stagingDir)) {
            throw new RuntimeException('Unable to create export staging directory.');
        }

        try {
            foreach ($selected as $file) {
                $source = $this->templatesDir . '/' . $file;
                $dest = $stagingDir . '/' . $file;
                if (!@copy($source, $dest)) {
                    throw new RuntimeException('Failed to stage template: ' . $file);
                }
            }

            $manifest = [
                'export_id' => $exportId,
                'created_at' => date('c'),
                'source' => $this->templatesDir,
                'file_count' => count($selected),
                'files' => array_values($selected),
            ];
            @file_put_contents(
                $stagingDir . '/manifest.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            $archivePath = $this->exportDir . '/' . $exportId . '.tgz';
            $command = 'tar -czf ' . escapeshellarg($archivePath) . ' -C ' . escapeshellarg($stagingDir) . ' .';
            $this->runCommand($command, 'Failed to create export archive.');

            return [
                'export_id' => $exportId,
                'archive_path' => $archivePath,
                'download_name' => $exportId . '.tgz',
                'file_count' => count($selected),
                'files' => array_values($selected),
            ];
        } finally {
            $this->removeDirectoryRecursive($stagingDir);
        }
    }

    /**
     * @param array<string, mixed> $upload
     * @return array<string, mixed>
     */
    public function importFromUpload(array $upload, bool $overwrite = false): array
    {
        $errorCode = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed with error code ' . $errorCode . '.');
        }

        $tmpPath = (string) ($upload['tmp_name'] ?? '');
        $originalName = trim((string) ($upload['name'] ?? ''));
        if ($tmpPath === '' || !is_file($tmpPath)) {
            throw new RuntimeException('Uploaded file is not available.');
        }

        $importDir = sys_get_temp_dir() . '/utm-import-' . date('Ymd-His') . '-' . $this->randomSuffix();
        if (!@mkdir($importDir, 0775, true) && !is_dir($importDir)) {
            throw new RuntimeException('Unable to create import staging directory.');
        }

        try {
            $xmlFiles = $this->extractImportFiles($tmpPath, $originalName, $importDir);
            if (count($xmlFiles) === 0) {
                throw new RuntimeException('No XML templates found in upload.');
            }

            $existingTargets = [];
            foreach ($xmlFiles as $file) {
                if (is_file($this->templatesDir . '/' . $file['filename'])) {
                    $existingTargets[] = $file['filename'];
                }
            }

            $backup = null;
            if ($overwrite && count($existingTargets) > 0) {
                $backup = $this->backupService->createBackup($existingTargets);
            }

            $imported = [];
            $skippedExisting = [];
            $invalid = [];
            foreach ($xmlFiles as $file) {
                $filename = $file['filename'];
                $sourcePath = $file['path'];

                if (!$this->validateXmlFile($sourcePath)) {
                    $invalid[] = $filename;
                    continue;
                }

                $destPath = $this->safeJoin($this->templatesDir, $filename);
                if ($destPath === null) {
                    $invalid[] = $filename;
                    continue;
                }

                if (is_file($destPath) && !$overwrite) {
                    $skippedExisting[] = $filename;
                    continue;
                }

                if (!@copy($sourcePath, $destPath)) {
                    throw new RuntimeException('Failed to import template: ' . $filename);
                }
                $imported[] = $filename;
            }

            return [
                'imported' => $imported,
                'skipped_existing' => $skippedExisting,
                'invalid' => $invalid,
                'overwrite' => $overwrite,
                'backup' => $backup,
            ];
        } finally {
            $this->removeDirectoryRecursive($importDir);
        }
    }

    /**
     * @param array<int, string>|null $requested
     * @return array<int, string>
     */
    private function resolveExportFiles(?array $requested): array
    {
        $allFiles = array_map(
            static fn(string $path): string => basename($path),
            glob($this->templatesDir . '/*.xml') ?: []
        );
        sort($allFiles, SORT_NATURAL | SORT_FLAG_CASE);

        if ($requested === null) {
            return $allFiles;
        }

        $lookup = [];
        foreach ($requested as $filename) {
            $clean = trim((string) $filename);
            if ($this->isValidTemplateFilename($clean)) {
                $lookup[$clean] = true;
            }
        }

        return array_values(array_filter(
            $allFiles,
            static fn(string $file): bool => isset($lookup[$file])
        ));
    }

    /**
     * @return array<int, array{filename: string, path: string}>
     */
    private function extractImportFiles(string $tmpPath, string $originalName, string $importDir): array
    {
        $lowerName = strtolower($originalName);

        if (substr($lowerName, -4) === '.xml') {
            $filename = basename($originalName);
            if (!$this->isValidTemplateFilename($filename)) {
                throw new RuntimeException('Invalid XML filename.');
            }
            $dest = $importDir . '/' . $filename;
            if (!@copy($tmpPath, $dest)) {
                throw new RuntimeException('Unable to stage uploaded XML file.');
            }
            return [['filename' => $filename, 'path' => $dest]];
        }

        $isTgz = (substr($lowerName, -4) === '.tgz') || (substr($lowerName, -7) === '.tar.gz');
        $isTar = substr($lowerName, -4) === '.tar';
        if (!$isTgz && !$isTar) {
            throw new RuntimeException('Unsupported import file format. Use .xml, .tgz, .tar.gz, or .tar.');
        }

        $listFlag = $isTgz ? '-tzf' : '-tf';
        $extractFlag = $isTgz ? '-xzf' : '-xf';
        $listCommand = 'tar ' . $listFlag . ' ' . escapeshellarg($tmpPath);
        $listResult = $this->runCommand($listCommand, 'Failed to read archive contents.');
        $entries = preg_split('/\R/', $listResult['stdout']) ?: [];

        $xmlEntries = [];
        foreach ($entries as $entry) {
            $entry = trim((string) $entry);
            if ($entry === '' || substr($entry, -1) === '/') {
                continue;
            }

            $filename = basename($entry);
            if (!$this->isValidTemplateFilename($filename)) {
                continue;
            }

            if ($entry !== $filename) {
                continue;
            }
            if (strpos($entry, '..') !== false || strpos($entry, '/') !== false || strpos($entry, '\\') !== false) {
                continue;
            }

            $xmlEntries[$entry] = $filename;
        }

        if (count($xmlEntries) === 0) {
            return [];
        }

        $entryArgs = implode(
            ' ',
            array_map(
                static fn(string $entry): string => escapeshellarg($entry),
                array_keys($xmlEntries)
            )
        );
        $extractCommand = 'tar ' . $extractFlag . ' ' . escapeshellarg($tmpPath) . ' -C ' . escapeshellarg($importDir) . ' ' . $entryArgs;
        $this->runCommand($extractCommand, 'Failed to extract archive.');

        $files = [];
        foreach ($xmlEntries as $entry => $filename) {
            $path = $importDir . '/' . $entry;
            if (!is_file($path)) {
                continue;
            }

            $files[] = [
                'filename' => $filename,
                'path' => $path,
            ];
        }

        return $files;
    }

    private function validateXmlFile(string $path): bool
    {
        $xmlText = @file_get_contents($path);
        if (!is_string($xmlText) || $xmlText === '') {
            return false;
        }

        $previousSetting = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlText);
        libxml_clear_errors();
        libxml_use_internal_errors($previousSetting);

        return $xml !== false;
    }

    /**
     * @return array{stdout: string}
     */
    private function runCommand(string $command, string $errorMessage): array
    {
        $output = [];
        $exitCode = 0;
        @exec($command . ' 2>&1', $output, $exitCode);
        if ($exitCode !== 0) {
            throw new RuntimeException($errorMessage . ' ' . implode("\n", $output));
        }

        return ['stdout' => implode("\n", $output)];
    }

    private function isValidTemplateFilename(string $filename): bool
    {
        if ($filename === '') {
            return false;
        }
        if (!preg_match('/^[A-Za-z0-9._-]+\.xml$/', $filename)) {
            return false;
        }
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
            return false;
        }
        return true;
    }

    private function safeJoin(string $baseDir, string $filename): ?string
    {
        $base = realpath($baseDir);
        if ($base === false) {
            return null;
        }

        $joined = $base . '/' . $filename;
        $realParent = realpath(dirname($joined));
        if ($realParent === false || $realParent !== $base) {
            return null;
        }

        return $joined;
    }

    private function removeDirectoryRecursive(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . '/' . $item;
            if (is_dir($fullPath)) {
                $this->removeDirectoryRecursive($fullPath);
                continue;
            }
            @unlink($fullPath);
        }

        @rmdir($path);
    }

    private function randomSuffix(): string
    {
        try {
            return bin2hex(random_bytes(3));
        } catch (\Throwable $exception) {
            return substr(str_replace('.', '', uniqid('', true)), -6);
        }
    }
}
