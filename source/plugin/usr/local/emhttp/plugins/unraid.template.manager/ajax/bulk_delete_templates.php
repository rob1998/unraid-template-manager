<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

/**
 * @param array<string, mixed> $payload
 */
function dtm_bulk_delete_emit_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        $json = '{"success":false,"error":"Failed to encode JSON response."}';
    }
    echo $json;
}

try {
    $docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
    require_once $docroot . '/plugins/unraid.template.manager/include/PluginPaths.php';
    require_once $docroot . '/plugins/unraid.template.manager/include/BackupService.php';
    require_once $docroot . '/plugins/unraid.template.manager/include/TemplateActionService.php';

    \UnraidTemplateManager\PluginPaths::ensureConfigDirectory();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        dtm_bulk_delete_emit_json([
            'success' => false,
            'error' => 'Method not allowed',
        ], 405);
        exit;
    }

    $rawFiles = trim((string) ($_POST['files'] ?? ''));
    $filenames = array_values(array_filter(array_map('trim', explode(',', $rawFiles))));
    $backupService = new \UnraidTemplateManager\BackupService(
        \UnraidTemplateManager\PluginPaths::TEMPLATES_DIR,
        \UnraidTemplateManager\PluginPaths::BACKUP_DIR
    );
    $actionService = new \UnraidTemplateManager\TemplateActionService(
        \UnraidTemplateManager\PluginPaths::TEMPLATES_DIR,
        $backupService
    );

    $result = $actionService->deleteTemplates($filenames);
    dtm_bulk_delete_emit_json([
        'success' => true,
        'result' => $result,
    ]);
} catch (\Throwable $exception) {
    error_log('unraid.template.manager bulk_delete_templates.php: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
    dtm_bulk_delete_emit_json([
        'success' => false,
        'error' => $exception->getMessage(),
    ], 500);
}
