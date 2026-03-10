<?php
declare(strict_types=1);

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
require_once $docroot . '/plugins/unraid.template.manager/include/PluginPaths.php';
require_once $docroot . '/plugins/unraid.template.manager/include/BackupService.php';

\UnraidTemplateManager\PluginPaths::ensureConfigDirectory();

$backupId = trim((string) ($_GET['backup_id'] ?? ''));

try {
    $backupService = new \UnraidTemplateManager\BackupService(
        \UnraidTemplateManager\PluginPaths::TEMPLATES_DIR,
        \UnraidTemplateManager\PluginPaths::BACKUP_DIR
    );
    $preview = $backupService->previewRestore($backupId);
    $backupPath = (string) ($preview['backup_path'] ?? '');
    if ($backupPath === '' || !is_dir($backupPath)) {
        throw new RuntimeException('Backup not found.');
    }

    $archive = sys_get_temp_dir() . '/utm-backup-' . $backupId . '-' . uniqid('', true) . '.tgz';
    $command = 'tar -czf ' . escapeshellarg($archive) . ' -C ' . escapeshellarg($backupPath) . ' . 2>&1';
    $output = [];
    $exitCode = 0;
    @exec($command, $output, $exitCode);
    if ($exitCode !== 0 || !is_file($archive)) {
        throw new RuntimeException('Failed to package backup for download.');
    }

    header('Content-Type: application/gzip');
    header('Content-Length: ' . (string) filesize($archive));
    header('Content-Disposition: attachment; filename="' . $backupId . '.tgz"');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    register_shutdown_function(static function () use ($archive): void {
        @unlink($archive);
    });

    readfile($archive);
} catch (\Throwable $exception) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}

