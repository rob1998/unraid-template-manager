<?php
declare(strict_types=1);

namespace UnraidTemplateManager;

use RuntimeException;

final class TemplateActionService
{
    private string $templatesDir;
    private BackupService $backupService;

    public function __construct(string $templatesDir, BackupService $backupService)
    {
        $this->templatesDir = $templatesDir;
        $this->backupService = $backupService;
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteTemplate(string $filename): array
    {
        $result = $this->deleteTemplates([$filename]);

        return [
            'deleted' => (string) (($result['deleted'][0] ?? '')),
            'backup' => $result['backup'],
        ];
    }

    /**
     * @param array<int, string> $filenames
     * @return array<string, mixed>
     */
    public function deleteTemplates(array $filenames): array
    {
        $validated = $this->sanitizeFilenameList($filenames);
        if (count($validated) === 0) {
            throw new RuntimeException('No valid template filenames provided.');
        }

        $pathsByFile = [];
        $missing = [];
        foreach ($validated as $filename) {
            $path = $this->safeJoin($this->templatesDir, $filename);
            if ($path === null || !is_file($path)) {
                $missing[] = $filename;
                continue;
            }
            $pathsByFile[$filename] = $path;
        }

        if (count($pathsByFile) === 0) {
            throw new RuntimeException('Template files not found.');
        }

        $toDelete = array_keys($pathsByFile);
        $backup = $this->backupService->createBackup($toDelete);

        $deleted = [];
        $failed = [];
        foreach ($toDelete as $filename) {
            $path = $pathsByFile[$filename];
            if (!@unlink($path)) {
                $failed[] = $filename;
                continue;
            }
            $deleted[] = $filename;
        }

        return [
            'deleted' => $deleted,
            'failed' => $failed,
            'missing' => $missing,
            'backup' => $backup,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cloneTemplate(string $sourceFilename, string $targetFilename): array
    {
        $sourceFilename = trim($sourceFilename);
        $targetFilename = trim($targetFilename);

        if (!$this->isValidTemplateFilename($sourceFilename) || !$this->isValidTemplateFilename($targetFilename)) {
            throw new RuntimeException('Invalid template filename.');
        }

        $sourcePath = $this->safeJoin($this->templatesDir, $sourceFilename);
        $targetPath = $this->safeJoin($this->templatesDir, $targetFilename);
        if ($sourcePath === null || !is_file($sourcePath)) {
            throw new RuntimeException('Source template file not found.');
        }
        if ($targetPath === null) {
            throw new RuntimeException('Invalid target template path.');
        }
        if (is_file($targetPath)) {
            throw new RuntimeException('Target template already exists.');
        }

        $backup = $this->backupService->createBackup([$sourceFilename]);
        if (!@copy($sourcePath, $targetPath)) {
            throw new RuntimeException('Failed to clone template file.');
        }

        return [
            'source' => $sourceFilename,
            'target' => $targetFilename,
            'backup' => $backup,
        ];
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

    /**
     * @param array<int, string> $filenames
     * @return array<int, string>
     */
    private function sanitizeFilenameList(array $filenames): array
    {
        $dedupe = [];
        foreach ($filenames as $filename) {
            $clean = trim((string) $filename);
            if ($this->isValidTemplateFilename($clean)) {
                $dedupe[$clean] = true;
            }
        }

        return array_keys($dedupe);
    }
}
