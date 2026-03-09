<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
require_once $docroot . '/plugins/unraid.template.manager/include/PluginPaths.php';
require_once $docroot . '/plugins/unraid.template.manager/include/BackupService.php';

\UnraidTemplateManager\PluginPaths::ensureConfigDirectory();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$backupId = (string) ($_POST['backup_id'] ?? '');
$overwrite = ((string) ($_POST['overwrite'] ?? '0')) === '1';
$selectedRaw = trim((string) ($_POST['files'] ?? ''));
$selectedFiles = null;
if ($selectedRaw !== '') {
    $selectedFiles = array_values(array_filter(array_map('trim', explode(',', $selectedRaw))));
}

try {
    $backupService = new \UnraidTemplateManager\BackupService(
        \UnraidTemplateManager\PluginPaths::TEMPLATES_DIR,
        \UnraidTemplateManager\PluginPaths::BACKUP_DIR
    );
    $result = $backupService->restoreBackup($backupId, $selectedFiles, $overwrite);
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

