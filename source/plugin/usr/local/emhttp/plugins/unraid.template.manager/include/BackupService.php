<?php
declare(strict_types=1);

namespace UnraidTemplateManager;

use RuntimeException;

final class BackupService
{
    private string $templatesDir;
    private string $backupRoot;

    public function __construct(string $templatesDir, string $backupRoot)
    {
        $this->templatesDir = $templatesDir;
        $this->backupRoot = $backupRoot;
    }

    /**
     * @param array<int, string>|null $filenames
     * @return array<string, mixed>
     */
    public function createBackup(?array $filenames = null): array
    {
        if (!is_dir($this->templatesDir)) {
            throw new RuntimeException('Templates directory is not available.');
        }

        if (!is_dir($this->backupRoot) && !@mkdir($this->backupRoot, 0775, true) && !is_dir($this->backupRoot)) {
            throw new RuntimeException('Unable to create backup root directory.');
        }

        $backupId = 'backup-' . date('Ymd-His');
        $backupPath = $this->backupRoot . '/' . $backupId;
        if (!@mkdir($backupPath, 0775, true) && !is_dir($backupPath)) {
            throw new RuntimeException('Unable to create backup directory.');
        }

        $files = $this->resolveFiles($filenames);
        $copied = [];
        foreach ($files as $file) {
            $source = $this->templatesDir . '/' . $file;
            $dest = $backupPath . '/' . $file;
            if (@copy($source, $dest)) {
                $copied[] = $file;
            }
        }

        $metadata = [
            'id' => $backupId,
            'created_at' => date('c'),
            'source' => $this->templatesDir,
            'file_count' => count($copied),
            'files' => $copied,
        ];

        @file_put_contents($backupPath . '/metadata.json', json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $metadata;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listBackups(): array
    {
        if (!is_dir($this->backupRoot)) {
            return [];
        }

        $dirs = glob($this->backupRoot . '/backup-*', GLOB_ONLYDIR) ?: [];
        rsort($dirs, SORT_STRING);

        $backups = [];
        foreach ($dirs as $dir) {
            $id = basename($dir);
            $metadataFile = $dir . '/metadata.json';
            $metadata = [
                'id' => $id,
                'created_at' => date('c', (int) (@filemtime($dir) ?: time())),
                'source' => $this->templatesDir,
                'file_count' => count(glob($dir . '/*.xml') ?: []),
                'files' => [],
            ];

            if (is_file($metadataFile)) {
                $raw = @file_get_contents($metadataFile);
                if (is_string($raw)) {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        $metadata = array_merge($metadata, $decoded);
                    }
                }
            }

            $backups[] = $metadata;
        }

        return $backups;
    }

    /**
     * @return array<string, mixed>
     */
    public function previewRestore(string $backupId): array
    {
        $backupPath = $this->resolveBackupPath($backupId);
        if ($backupPath === null || !is_dir($backupPath)) {
            throw new RuntimeException('Backup not found.');
        }

        $backupFiles = array_map(
            static fn(string $path): string => basename($path),
            glob($backupPath . '/*.xml') ?: []
        );
        sort($backupFiles, SORT_NATURAL | SORT_FLAG_CASE);

        $conflicts = [];
        foreach ($backupFiles as $file) {
            if (is_file($this->templatesDir . '/' . $file)) {
                $conflicts[] = $file;
            }
        }

        return [
            'backup_id' => $backupId,
            'backup_path' => $backupPath,
            'files' => $backupFiles,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * @param array<int, string>|null $selectedFiles
     * @return array<string, mixed>
     */
    public function restoreBackup(string $backupId, ?array $selectedFiles = null, bool $overwrite = false): array
    {
        $preview = $this->previewRestore($backupId);
        $backupPath = (string) $preview['backup_path'];
        $files = (array) $preview['files'];

        if ($selectedFiles !== null) {
            $selectedLookup = [];
            foreach ($selectedFiles as $selected) {
                $selected = trim((string) $selected);
                if ($this->isValidTemplateFilename($selected)) {
                    $selectedLookup[$selected] = true;
                }
            }
            $files = array_values(array_filter(
                $files,
                static fn(string $file): bool => isset($selectedLookup[$file])
            ));
        }

        $restored = [];
        $skipped = [];
        foreach ($files as $file) {
            $source = $backupPath . '/' . $file;
            $dest = $this->templatesDir . '/' . $file;
            if (is_file($dest) && !$overwrite) {
                $skipped[] = $file;
                continue;
            }
            if (@copy($source, $dest)) {
                $restored[] = $file;
            }
        }

        return [
            'backup_id' => $backupId,
            'restored' => $restored,
            'skipped' => $skipped,
            'overwrite' => $overwrite,
        ];
    }

    /**
     * @param array<int, string>|null $requestedFiles
     * @return array<int, string>
     */
    private function resolveFiles(?array $requestedFiles): array
    {
        $allFiles = array_map(
            static fn(string $path): string => basename($path),
            glob($this->templatesDir . '/*.xml') ?: []
        );
        sort($allFiles, SORT_NATURAL | SORT_FLAG_CASE);

        if ($requestedFiles === null) {
            return $allFiles;
        }

        $requestedLookup = [];
        foreach ($requestedFiles as $file) {
            $clean = trim($file);
            if ($this->isValidTemplateFilename($clean)) {
                $requestedLookup[$clean] = true;
            }
        }

        return array_values(array_filter(
            $allFiles,
            static fn(string $file): bool => isset($requestedLookup[$file])
        ));
    }

    private function isValidTemplateFilename(string $filename): bool
    {
        if ($filename === '') {
            return false;
        }
        if (!preg_match('/^[A-Za-z0-9._-]+\.xml$/', $filename)) {
            return false;
        }
        if (str_contains($filename, '..') || str_contains($filename, '/')) {
            return false;
        }
        return true;
    }

    private function isValidBackupId(string $backupId): bool
    {
        return (bool) preg_match('/^backup-\d{8}-\d{6}$/', $backupId);
    }

    private function resolveBackupPath(string $backupId): ?string
    {
        $backupId = trim($backupId);
        if (!$this->isValidBackupId($backupId)) {
            return null;
        }

        return $this->backupRoot . '/' . $backupId;
    }
}
