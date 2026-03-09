<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
require_once $docroot . '/plugins/unraid.template.manager/include/PluginPaths.php';
require_once $docroot . '/plugins/unraid.template.manager/include/BackupService.php';

\UnraidTemplateManager\PluginPaths::ensureConfigDirectory();

$backupId = (string) ($_GET['backup_id'] ?? '');

try {
    $backupService = new \UnraidTemplateManager\BackupService(
        \UnraidTemplateManager\PluginPaths::TEMPLATES_DIR,
        \UnraidTemplateManager\PluginPaths::BACKUP_DIR
    );
    echo json_encode([
        'success' => true,
        'result' => $backupService->previewRestore($backupId),
    ]);
} catch (\Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}

