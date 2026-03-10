<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
require_once $docroot . '/plugins/unraid.template.manager/include/PluginPaths.php';
require_once $docroot . '/plugins/unraid.template.manager/include/BackupService.php';
require_once $docroot . '/plugins/unraid.template.manager/include/TemplateTransferService.php';

\UnraidTemplateManager\PluginPaths::ensureConfigDirectory();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
    ]);
    exit;
}

if (!isset($_FILES['import_file']) || !is_array($_FILES['import_file'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'No import file uploaded.',
    ]);
    exit;
}

$overwrite = ((string) ($_POST['overwrite'] ?? '0')) === '1';

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

    $result = $transferService->importFromUpload($_FILES['import_file'], $overwrite);

    echo json_encode([
        'success' => true,
        'result' => $result,
    ]);
} catch (\Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
}

