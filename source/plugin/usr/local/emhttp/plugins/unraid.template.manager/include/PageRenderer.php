<?php
declare(strict_types=1);

namespace UnraidTemplateManager;

final class PageRenderer
{
    private TemplateInventoryService $inventoryService;
    private ContainerInventoryService $containerService;
    private TemplateMatcher $matcher;
    private TemplateDiagnosticsService $diagnosticsService;
    private StorageModeService $storageModeService;

    public function __construct(
        TemplateInventoryService $inventoryService,
        ContainerInventoryService $containerService,
        TemplateMatcher $matcher,
        TemplateDiagnosticsService $diagnosticsService,
        StorageModeService $storageModeService
    )
    {
        $this->inventoryService = $inventoryService;
        $this->containerService = $containerService;
        $this->matcher = $matcher;
        $this->diagnosticsService = $diagnosticsService;
        $this->storageModeService = $storageModeService;
    }

    public function render(): string
    {
        $templateRows = $this->inventoryService->listTemplates();
        $containerRows = $this->containerService->listContainers();
        $dockerAvailable = $this->containerService->isDockerAvailable();
        $mapping = $this->matcher->mapTemplatesToContainers($templateRows, $containerRows, $dockerAvailable);
        $diagnostics = $this->diagnosticsService->analyze($mapping['templates'], $mapping['unmatched_containers']);
        $templates = $diagnostics['templates'];
        $unmatchedContainers = $mapping['unmatched_containers'];
        $issues = $diagnostics['issues'];

        $totalTemplates = count($templates);
        $totalContainers = count($containerRows);
        $invalid = count(array_filter(
            $templates,
            static fn(array $row): bool => (string) ($row['status'] ?? '') === 'invalid'
        ));
        $matched = count(array_filter(
            $templates,
            static fn(array $row): bool => (string) ($row['mapping_status'] ?? '') === 'matched'
        ));
        $orphaned = count(array_filter(
            $templates,
            static fn(array $row): bool => (string) ($row['mapping_status'] ?? '') === 'orphaned'
        ));
        $warnings = count(array_filter(
            $issues,
            static fn(array $issue): bool => (string) ($issue['severity'] ?? '') === 'warning'
        ));
        $errors = count(array_filter(
            $issues,
            static fn(array $issue): bool => (string) ($issue['severity'] ?? '') === 'error'
        ));
        $valid = $totalTemplates - $invalid;

        $cssPath = $this->assetPath('/plugins/unraid.template.manager/css/app.css');
        $jsPath = $this->assetPath('/plugins/unraid.template.manager/javascript/app.js');
        $templatesDir = $this->inventoryService->getTemplatesDir();
        $pathExists = $this->inventoryService->templatesPathExists();
        $storage = $this->storageModeService->detect();

        $html = [];
        $html[] = "<link rel='stylesheet' href='{$this->escape($cssPath)}'>";
        $html[] = "<div class='dtm-wrap'>";
        $html[] = "<div class='dtm-header'>";
        $html[] = "<h2>Unraid Template Manager</h2>";
        $html[] = "<p class='dtm-subtitle'>Read-only template inventory and deterministic container matching diagnostics.</p>";
        $html[] = '</div>';

        $html[] = "<div class='dtm-cards'>";
        $html[] = "<div class='dtm-card'><span class='label'>Templates</span><strong>{$totalTemplates}</strong></div>";
        $html[] = "<div class='dtm-card'><span class='label'>Containers</span><strong>{$totalContainers}</strong></div>";
        $html[] = "<div class='dtm-card'><span class='label'>Valid</span><strong>{$valid}</strong></div>";
        $html[] = "<div class='dtm-card'><span class='label'>Invalid</span><strong>{$invalid}</strong></div>";
        $html[] = "<div class='dtm-card'><span class='label'>Matched</span><strong>{$matched}</strong></div>";
        $html[] = "<div class='dtm-card'><span class='label'>Orphaned</span><strong>{$orphaned}</strong></div>";
        $html[] = "<div class='dtm-card'><span class='label'>Warnings</span><strong>{$warnings}</strong></div>";
        $html[] = "<div class='dtm-card'><span class='label'>Errors</span><strong>{$errors}</strong></div>";
        $html[] = '</div>';

        $pathClass = $pathExists ? 'ok' : 'warn';
        $pathLabel = $pathExists ? 'available' : 'missing';
        $html[] = "<div class='dtm-path {$pathClass}'>templates-user path: <code>{$this->escape($templatesDir)}</code> ({$pathLabel})</div>";
        $dockerClass = $dockerAvailable ? 'ok' : 'warn';
        $dockerLabel = $dockerAvailable ? 'available' : 'unavailable';
        $html[] = "<div class='dtm-path {$dockerClass}'>docker CLI: <code>docker</code> ({$dockerLabel})</div>";
        $storageMode = strtoupper((string) ($storage['mode'] ?? 'unknown'));
        $storagePath = $this->escape((string) ($storage['path'] ?? ''));
        $storageDetails = $this->escape((string) ($storage['details'] ?? ''));
        $storageGuidance = $this->escape((string) ($storage['guidance'] ?? ''));
        $storageClass = ((string) ($storage['mode'] ?? 'unknown')) === 'unknown' ? 'warn' : 'ok';
        $html[] = "<div class='dtm-path {$storageClass}'>Docker storage mode: <strong>{$storageMode}</strong> <code>{$storagePath}</code><br>{$storageDetails}<br>{$storageGuidance}</div>";

        $html[] = "<div class='dtm-toolbar'>";
        $html[] = "<input id='dtm-search' type='search' placeholder='Filter by name, image, match reason, status'>";
        $html[] = '</div>';

        $html[] = "<table id='dtm-table' class='dtm-table tablesorter'>";
        $html[] = '<thead><tr><th>Template State</th><th>Mapping</th><th>Severity</th><th>Name</th><th>Filename</th><th>Matched Container</th><th>Image</th><th>Network</th><th>Modified</th><th>Size</th><th>Issue</th><th>Actions</th></tr></thead>';
        $html[] = '<tbody>';

        if ($totalTemplates === 0) {
            $html[] = "<tr><td colspan='12' class='empty'>No templates found.</td></tr>";
        } else {
            foreach ($templates as $row) {
                $templateState = (string) $row['status'];
                $templateClass = $templateState === 'invalid' ? 'status-invalid' : 'status-valid';
                $templateText = strtoupper($templateState);

                $mappingState = (string) ($row['mapping_status'] ?? 'unknown');
                $mappingClass = $this->mappingClass($mappingState);
                $mappingText = strtoupper($mappingState);
                $severity = (string) ($row['severity'] ?? 'info');
                $severityClass = $this->severityClass($severity);
                $severityText = strtoupper($severity);
                $name = $this->escape((string) $row['name']);
                $filename = $this->escape((string) $row['filename']);
                $matchedContainer = $this->escape((string) ($row['matched_container'] ?? ''));
                $image = $this->escape((string) $row['image']);
                $network = $this->escape((string) $row['network']);
                $issue = $this->escape((string) ($row['issue_summary'] ?? ''));
                $modified = $this->escape($this->formatTimestamp((int) $row['modified']));
                $size = $this->escape($this->formatBytes((int) $row['size']));
                $actions = "<button type='button' class='dtm-clone-template' data-filename='{$filename}'>Clone</button> ";
                $actions .= "<button type='button' class='dtm-delete-template' data-filename='{$filename}'>Backup + Delete</button>";

                $html[] = '<tr>';
                $html[] = "<td><span class='status {$templateClass}'>{$templateText}</span></td>";
                $html[] = "<td><span class='status {$mappingClass}'>{$mappingText}</span></td>";
                $html[] = "<td><span class='status {$severityClass}'>{$severityText}</span></td>";
                $html[] = "<td>{$name}</td>";
                $html[] = "<td><code>{$filename}</code></td>";
                $html[] = "<td>{$matchedContainer}</td>";
                $html[] = "<td>{$image}</td>";
                $html[] = "<td>{$network}</td>";
                $html[] = "<td>{$modified}</td>";
                $html[] = "<td>{$size}</td>";
                $html[] = "<td>{$issue}</td>";
                $html[] = "<td>{$actions}</td>";
                $html[] = '</tr>';
            }
        }

        $html[] = '</tbody></table>';

        if (count($unmatchedContainers) > 0) {
            $html[] = "<div class='dtm-unmatched-title'>Containers without matched templates</div>";
            $html[] = "<table class='dtm-table'><thead><tr><th>Name</th><th>Image</th><th>State</th><th>Status</th></tr></thead><tbody>";
            foreach ($unmatchedContainers as $container) {
                $name = $this->escape((string) ($container['name'] ?? ''));
                $image = $this->escape((string) ($container['image'] ?? ''));
                $state = $this->escape((string) ($container['state'] ?? ''));
                $status = $this->escape((string) ($container['status'] ?? ''));
                $html[] = "<tr><td>{$name}</td><td>{$image}</td><td>{$state}</td><td>{$status}</td></tr>";
            }
            $html[] = '</tbody></table>';
        }

        $html[] = '</div>';
        $html[] = "<script src='{$this->escape($jsPath)}'></script>";

        return implode("\n", $html);
    }

    private function assetPath(string $path): string
    {
        if (function_exists('autov')) {
            return autov($path);
        }

        return $path;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function formatTimestamp(int $timestamp): string
    {
        if ($timestamp <= 0) {
            return '';
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $size = $bytes / 1024;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return number_format($size, 1) . ' ' . $units[$unitIndex];
    }

    private function mappingClass(string $state): string
    {
        if ($state === 'matched') {
            return 'status-matched';
        }
        if ($state === 'orphaned') {
            return 'status-orphaned';
        }
        if ($state === 'invalid') {
            return 'status-invalid';
        }
        return 'status-unknown';
    }

    private function severityClass(string $severity): string
    {
        if ($severity === 'error') {
            return 'status-invalid';
        }
        if ($severity === 'warning') {
            return 'status-orphaned';
        }
        return 'status-unknown';
    }
}
