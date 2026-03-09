<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
require_once $docroot . '/plugins/unraid.template.manager/include/PluginPaths.php';
require_once $docroot . '/plugins/unraid.template.manager/include/BackupService.php';
require_once $docroot . '/plugins/unraid.template.manager/include/TemplateActionService.php';

\UnraidTemplateManager\PluginPaths::ensureConfigDirectory();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$sourceFilename = (string) ($_POST['source_filename'] ?? '');
$targetFilename = (string) ($_POST['target_filename'] ?? '');
if ($targetFilename !== '' && substr(strtolower($targetFilename), -4) !== '.xml') {
    $targetFilename .= '.xml';
}

try {
    $backupService = new \UnraidTemplateManager\BackupService(
        \UnraidTemplateManager\PluginPaths::TEMPLATES_DIR,
        \UnraidTemplateManager\PluginPaths::BACKUP_DIR
    );
    $actionService = new \UnraidTemplateManager\TemplateActionService(
        \UnraidTemplateManager\PluginPaths::TEMPLATES_DIR,
        $backupService
    );
    $result = $actionService->cloneTemplate($sourceFilename, $targetFilename);

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
