<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
try {
    require_once $docroot . '/plugins/unraid.template.manager/include/PluginPaths.php';
    require_once $docroot . '/plugins/unraid.template.manager/include/TemplateInventoryService.php';
    require_once $docroot . '/plugins/unraid.template.manager/include/ContainerInventoryService.php';
    require_once $docroot . '/plugins/unraid.template.manager/include/TemplateMatcher.php';
    require_once $docroot . '/plugins/unraid.template.manager/include/TemplateDiagnosticsService.php';
    require_once $docroot . '/plugins/unraid.template.manager/include/StorageModeService.php';
    require_once $docroot . '/plugins/unraid.template.manager/include/PageRenderer.php';

    \UnraidTemplateManager\PluginPaths::ensureConfigDirectory();
    $service = new \UnraidTemplateManager\TemplateInventoryService(\UnraidTemplateManager\PluginPaths::TEMPLATES_DIR);
    $containerService = new \UnraidTemplateManager\ContainerInventoryService();
    $matcher = new \UnraidTemplateManager\TemplateMatcher();
    $diagnostics = new \UnraidTemplateManager\TemplateDiagnosticsService();
    $storageModeService = new \UnraidTemplateManager\StorageModeService();
    $renderer = new \UnraidTemplateManager\PageRenderer($service, $containerService, $matcher, $diagnostics, $storageModeService);
    echo $renderer->render();
} catch (\Throwable $exception) {
    $message = htmlspecialchars($exception->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $file = htmlspecialchars((string) $exception->getFile(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $line = (int) $exception->getLine();
    $php = htmlspecialchars((string) PHP_VERSION, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    error_log('unraid.template.manager: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
    echo "<div style='padding:16px;border:1px solid #b44;border-radius:8px;background:#3a1f1f;color:#f4dcdc'>";
    echo "<h3 style='margin:0 0 8px 0'>Unraid Template Manager failed to render</h3>";
    echo "<div><strong>PHP:</strong> {$php}</div>";
    echo "<div><strong>Error:</strong> {$message}</div>";
    echo "<div><strong>File:</strong> {$file}:{$line}</div>";
    echo "</div>";
}

