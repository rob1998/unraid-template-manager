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
        $dockerBinaryAvailable = $this->containerService->isDockerAvailable();
        $containerMeta = $this->containerService->getLastRunMeta();
        $dockerInventoryAvailable = (string) ($containerMeta['source'] ?? 'none') !== 'none';
        $dockerAvailableForMapping = $dockerBinaryAvailable && $dockerInventoryAvailable;
        $dockerUnavailableReason = $this->dockerUnavailableReason($dockerBinaryAvailable, $containerMeta);

        $mapping = $this->matcher->mapTemplatesToContainers(
            $templateRows,
            $containerRows,
            $dockerAvailableForMapping,
            $dockerUnavailableReason
        );
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

        $pathClass = $pathExists ? 'ok' : 'warn';
        $pathLabel = $pathExists ? 'available' : 'missing';

        $dockerClass = ($dockerBinaryAvailable && $dockerInventoryAvailable) ? 'ok' : 'warn';
        $dockerLabel = $dockerBinaryAvailable ? 'available' : 'unavailable';
        $dockerRuntime = $this->containerRuntimeSummary($containerMeta);

        $storageMode = strtoupper((string) ($storage['mode'] ?? 'unknown'));
        $storagePath = $this->escape((string) ($storage['path'] ?? ''));
        $storageDetails = $this->escape((string) ($storage['details'] ?? ''));
        $storageGuidance = $this->escape((string) ($storage['guidance'] ?? ''));
        $storageClass = ((string) ($storage['mode'] ?? 'unknown')) === 'unknown' ? 'warn' : 'ok';

        $html = [];
        $html[] = "<link rel='stylesheet' href='{$this->escape($cssPath)}'>";
        $html[] = "<div class='dtm-wrap'>";
        $html[] = "<div class='dtm-header'>";
        $html[] = '<h2>Unraid Template Manager</h2>';
        $html[] = "<p class='dtm-subtitle'>Template lifecycle inventory, diagnostics, backup-safe cleanup, and import/export operations.</p>";
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

        $html[] = "<div id='dtm-feedback' class='dtm-feedback' aria-live='polite'></div>";

        $html[] = "<div class='dtm-tabs' role='tablist' aria-label='Template Manager Sections'>";
        $html[] = "<button type='button' class='dtm-tab-button is-active' data-tab='templates' role='tab' aria-selected='true'>Templates</button>";
        $html[] = "<button type='button' class='dtm-tab-button' data-tab='settings' role='tab' aria-selected='false'>Settings</button>";
        $html[] = "<button type='button' class='dtm-tab-button' data-tab='tools' role='tab' aria-selected='false'>Tools</button>";
        $html[] = '</div>';

        $html[] = "<div class='dtm-tab-panels'>";

        $html[] = "<section class='dtm-tab-panel is-active' data-tab-panel='templates' role='tabpanel'>";
        $html[] = "<div class='dtm-toolbar'>";
        $html[] = "<div class='dtm-filter-row'>";
        $html[] = "<input id='dtm-search' type='search' placeholder='Filter by name, image, container, issue'>";
        $html[] = "<select id='dtm-filter-template-state'><option value='all'>All Template States</option><option value='valid'>Valid</option><option value='invalid'>Invalid</option></select>";
        $html[] = "<select id='dtm-filter-mapping-state'><option value='all'>All Mapping States</option><option value='matched'>Matched</option><option value='orphaned'>Orphaned</option><option value='unknown'>Unknown</option><option value='invalid'>Invalid</option></select>";
        $html[] = "<select id='dtm-filter-severity'><option value='all'>All Severities</option><option value='info'>Info</option><option value='warning'>Warning</option><option value='error'>Error</option></select>";
        $html[] = "<button id='dtm-clear-filters' type='button' class='dtm-button'>Clear</button>";
        $html[] = '</div>';

        $html[] = "<div class='dtm-action-row'>";
        $html[] = "<label class='dtm-checkbox-inline'><input id='dtm-select-all' type='checkbox'> Select all visible</label>";
        $html[] = "<span id='dtm-selected-count' class='dtm-selected-count'>0 selected</span>";
        $html[] = "<button id='dtm-bulk-delete' type='button' class='dtm-button dtm-button-danger'>Delete Selected</button>";
        $html[] = '</div>';
        $html[] = '</div>';

        $html[] = "<table id='dtm-table' class='dtm-table tablesorter'>";
        $html[] = '<thead><tr><th><input id="dtm-select-page" type="checkbox" aria-label="Select all rows"></th><th>Template</th><th>Status</th><th>Matched Container</th><th>Image</th><th>Network</th><th>Modified</th><th>Size</th><th>Issue</th><th>Actions</th></tr></thead>';
        $html[] = '<tbody>';

        if ($totalTemplates === 0) {
            $html[] = "<tr><td colspan='10' class='empty'>No templates found.</td></tr>";
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
                $filenameRaw = (string) $row['filename'];
                $filename = $this->escape($filenameRaw);
                $matchedContainer = $this->escape((string) ($row['matched_container'] ?? ''));
                $image = $this->escape((string) $row['image']);
                $network = $this->escape((string) $row['network']);
                $issue = $this->escape((string) ($row['issue_summary'] ?? ''));
                $reason = $this->escape((string) ($row['match_reason'] ?? ''));
                $modified = $this->escape($this->formatTimestamp((int) $row['modified']));
                $size = $this->escape($this->formatBytes((int) $row['size']));

                $searchBlob = strtolower(trim(implode(' ', [
                    (string) ($row['name'] ?? ''),
                    $filenameRaw,
                    (string) ($row['image'] ?? ''),
                    (string) ($row['network'] ?? ''),
                    (string) ($row['matched_container'] ?? ''),
                    (string) ($row['issue_summary'] ?? ''),
                    (string) ($row['match_reason'] ?? ''),
                ])));

                $actions = "<button type='button' class='dtm-clone-template dtm-button dtm-button-primary' data-filename='{$filename}'>Clone</button> ";
                $actions .= "<button type='button' class='dtm-export-template dtm-button' data-filename='{$filename}'>Export</button> ";
                $actions .= "<button type='button' class='dtm-delete-template dtm-button dtm-button-danger' data-filename='{$filename}'>Delete</button>";

                $html[] = "<tr class='dtm-template-row' data-template-state='{$this->escape($templateState)}' data-mapping-state='{$this->escape($mappingState)}' data-severity='{$this->escape($severity)}' data-filter-text='{$this->escape($searchBlob)}' data-filename='{$filename}'>";
                $html[] = "<td><input type='checkbox' class='dtm-row-select' value='{$filename}' aria-label='Select {$name}'></td>";
                $html[] = "<td><div class='dtm-template-name'>{$name}</div><code>{$filename}</code></td>";
                $html[] = "<td><div class='status-stack'><span class='status {$templateClass}'>{$templateText}</span><span class='status {$mappingClass}'>{$mappingText}</span><span class='status {$severityClass}'>{$severityText}</span></div></td>";
                $html[] = "<td>{$matchedContainer}</td>";
                $html[] = "<td>{$image}</td>";
                $html[] = "<td>{$network}</td>";
                $html[] = "<td>{$modified}</td>";
                $html[] = "<td>{$size}</td>";
                $html[] = "<td><div>{$issue}</div><div class='dtm-reason'>{$reason}</div></td>";
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
        $html[] = '</section>';

        $html[] = "<section class='dtm-tab-panel' data-tab-panel='settings' role='tabpanel' hidden>";
        $html[] = "<div class='dtm-panel-card'>";
        $html[] = "<div class='dtm-path {$pathClass}'>templates-user path: <code>{$this->escape($templatesDir)}</code> ({$pathLabel})</div>";
        $html[] = "<div class='dtm-path {$dockerClass}'>docker CLI: <code>docker</code> ({$dockerLabel}) {$dockerRuntime}</div>";
        $html[] = "<div class='dtm-path {$storageClass}'>Docker storage mode: <strong>{$storageMode}</strong> <code>{$storagePath}</code><br>{$storageDetails}<br>{$storageGuidance}</div>";
        $html[] = "</div>";

        $html[] = "<form id='dtm-storage-form' class='dtm-storage-form'>";
        $html[] = '<label>Target mode</label>';
        $html[] = "<select id='dtm-storage-target-mode' name='mode'>" . $this->renderStorageModeOptions((string) ($storage['mode'] ?? 'unknown')) . '</select>';
        $html[] = '<label>Target path</label>';
        $html[] = "<input id='dtm-storage-target-path' name='path' type='text' value='{$storagePath}' placeholder='/mnt/cache/system/docker or /mnt/user/system/docker/docker.img'>";
        $html[] = "<label class='dtm-checkbox-inline'><input id='dtm-storage-restart' name='restart' type='checkbox'> Restart Docker now</label>";
        $html[] = "<div class='dtm-storage-warning'>Warning: switching Docker storage mode from this plugin has not been fully tested in production scenarios. Keep backups and proceed carefully.</div>";
        $html[] = "<button id='dtm-storage-switch' type='submit' class='dtm-button dtm-button-warning'>Switch Storage Mode</button>";
        $html[] = '</form>';
        $html[] = '</section>';

        $html[] = "<section class='dtm-tab-panel' data-tab-panel='tools' role='tabpanel' hidden>";
        $html[] = "<div class='dtm-tools-grid'>";

        $html[] = "<div class='dtm-panel-card'>";
        $html[] = "<h3>Backup and Export</h3>";
        $html[] = "<div class='dtm-action-row'>";
        $html[] = "<button id='dtm-backup-all' type='button' class='dtm-button'>Backup All</button>";
        $html[] = "<button id='dtm-backup-selected' type='button' class='dtm-button'>Backup Selected</button>";
        $html[] = "</div>";
        $html[] = "<div class='dtm-action-row'>";
        $html[] = "<button id='dtm-export-all' type='button' class='dtm-button'>Export All</button>";
        $html[] = "<button id='dtm-export-selected' type='button' class='dtm-button'>Export Selected</button>";
        $html[] = "</div>";
        $html[] = "</div>";

        $html[] = "<div class='dtm-panel-card'>";
        $html[] = "<h3>Import Templates</h3>";
        $html[] = "<form id='dtm-import-form' class='dtm-import-form' enctype='multipart/form-data'>";
        $html[] = "<input id='dtm-import-file' name='import_file' type='file' accept='.xml,.tgz,.tar,.tar.gz' required>";
        $html[] = "<label class='dtm-checkbox-inline'><input id='dtm-import-overwrite' name='overwrite' type='checkbox'> Overwrite existing</label>";
        $html[] = "<button id='dtm-import-submit' type='submit' class='dtm-button'>Import</button>";
        $html[] = "</form>";
        $html[] = "</div>";

        $html[] = "<div class='dtm-panel-card'>";
        $html[] = "<h3>Restore Backup</h3>";
        $html[] = "<div class='dtm-action-row'>";
        $html[] = "<select id='dtm-restore-backup-id'><option value=''>Select Backup</option></select>";
        $html[] = "<button id='dtm-refresh-backups' type='button' class='dtm-button'>Refresh Backups</button>";
        $html[] = "<button id='dtm-download-backup' type='button' class='dtm-button'>Download Backup</button>";
        $html[] = "</div>";
        $html[] = "<div class='dtm-action-row'>";
        $html[] = "<button id='dtm-preview-restore' type='button' class='dtm-button'>Preview Restore</button>";
        $html[] = "<label class='dtm-checkbox-inline'><input id='dtm-restore-overwrite' type='checkbox'> Overwrite on restore</label>";
        $html[] = "<button id='dtm-restore-backup' type='button' class='dtm-button dtm-button-warning'>Restore Backup</button>";
        $html[] = "</div>";
        $html[] = "</div>";

        $html[] = "</div>";
        $html[] = '</section>';

        $html[] = '</div>';

        $html[] = "<div id='dtm-confirm-modal' class='dtm-modal hidden' aria-hidden='true'>";
        $html[] = "<div class='dtm-modal-backdrop' data-modal-close='1'></div>";
        $html[] = "<div class='dtm-modal-dialog' role='dialog' aria-modal='true' aria-labelledby='dtm-modal-title'>";
        $html[] = "<h3 id='dtm-modal-title'>Confirm Action</h3>";
        $html[] = "<p id='dtm-modal-message' class='dtm-modal-message'></p>";
        $html[] = "<ul id='dtm-modal-items' class='dtm-modal-items'></ul>";
        $html[] = "<p id='dtm-modal-note' class='dtm-modal-note'></p>";
        $html[] = "<div class='dtm-modal-actions'>";
        $html[] = "<button id='dtm-modal-cancel' type='button' class='dtm-button'>Cancel</button>";
        $html[] = "<button id='dtm-modal-confirm' type='button' class='dtm-button dtm-button-danger'>Confirm Delete</button>";
        $html[] = "</div>";
        $html[] = "</div>";
        $html[] = "</div>";

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

    /**
     * @param array<string, mixed> $meta
     */
    private function containerRuntimeSummary(array $meta): string
    {
        $source = (string) ($meta['source'] ?? 'none');
        $timedOut = (bool) ($meta['timed_out'] ?? false);
        $duration = (int) ($meta['duration_ms'] ?? 0);
        $cacheAge = $meta['cache_age_seconds'] ?? null;

        $parts = [];
        if ($source === 'live') {
            $parts[] = 'live';
        } elseif ($source === 'live_fallback') {
            $parts[] = 'live-fallback';
        } elseif ($source === 'cache' || $source === 'cache_stale') {
            $parts[] = 'cache';
            if (is_int($cacheAge)) {
                $parts[] = 'age ' . $cacheAge . 's';
            }
        }
        if ($duration > 0) {
            $parts[] = $duration . 'ms';
        }
        if ($timedOut) {
            $parts[] = 'timeout';
        }

        if (count($parts) === 0) {
            return '';
        }

        return '<span class="dtm-runtime-meta">[' . $this->escape(implode(', ', $parts)) . ']</span>';
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function dockerUnavailableReason(bool $dockerBinaryAvailable, array $meta): string
    {
        if (!$dockerBinaryAvailable) {
            return 'Docker CLI unavailable';
        }

        $source = (string) ($meta['source'] ?? 'none');
        if ($source !== 'none') {
            return '';
        }

        if ((bool) ($meta['timed_out'] ?? false)) {
            return 'Docker inventory query timed out';
        }

        $error = trim((string) ($meta['error'] ?? ''));
        if ($error !== '') {
            return 'Docker inventory unavailable: ' . $error;
        }

        return 'Docker inventory unavailable';
    }

    private function renderStorageModeOptions(string $detectedMode): string
    {
        $detectedMode = strtolower($detectedMode);
        $vdiskSelected = $detectedMode === 'vdisk' ? ' selected' : '';
        $directorySelected = $detectedMode === 'directory' ? ' selected' : '';

        return '<option value="vdisk"' . $vdiskSelected . '>vDisk (.img)</option>'
            . '<option value="directory"' . $directorySelected . '>Directory</option>';
    }
}
