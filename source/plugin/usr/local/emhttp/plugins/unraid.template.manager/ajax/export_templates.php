<?php
declare(strict_types=1);

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
require_once $docroot . '/plugins/unraid.template.manager/include/PluginPaths.php';
require_once $docroot . '/plugins/unraid.template.manager/include/BackupService.php';
require_once $docroot . '/plugins/unraid.template.manager/include/TemplateTransferService.php';

\UnraidTemplateManager\PluginPaths::ensureConfigDirectory();

$rawFiles = trim((string) ($_GET['files'] ?? ''));
$filenames = null;
if ($rawFiles !== '') {
    $filenames = array_values(array_filter(array_map('trim', explode(',', $rawFiles))));
}

try {
    $backupService = new \UnraidTemplateManager\BackupService(
        \UnraidTemplateManager\PluginPaths::TEMPLATES_DIR,
        \UnraidTemplateManager\PluginPaths::BACKUP_DIR
    );
    $transferService = new \UnraidTemplateManager\TemplateTransferService(
        \UnraidTemplateManager\PluginPaths::TEMPLATES_DIR,
        \UnraidTemplateManager\PluginPaths::EXPORT_DIR,
        $backupService
    );

    $export = $transferService->createExportArchive($filenames);
    $archivePath = (string) ($export['archive_path'] ?? '');
    $downloadName = (string) ($export['download_name'] ?? 'templates-export.tgz');

    if ($archivePath === '' || !is_file($archivePath)) {
        throw new RuntimeException('Export archive not found after creation.');
    }

    header('Content-Type: application/gzip');
    header('Content-Length: ' . (string) filesize($archivePath));
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    register_shutdown_function(static function () use ($archivePath): void {
        @unlink($archivePath);
    });

    readfile($archivePath);
} catch (\Throwable $exception) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}

