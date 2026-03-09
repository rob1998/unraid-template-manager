<?php
declare(strict_types=1);

namespace UnraidTemplateManager;

final class TemplateDiagnosticsService
{
    /**
     * @param array<int, array<string, mixed>> $templates
     * @param array<int, array<string, string>> $unmatchedContainers
     * @return array{templates: array<int, array<string, mixed>>, issues: array<int, array<string, string>>}
     */
    public function analyze(array $templates, array $unmatchedContainers): array
    {
        $issues = [];
        $nameGroups = [];
        $imageGroups = [];

        foreach ($templates as $index => $template) {
            if ((string) ($template['status'] ?? '') === 'invalid') {
                continue;
            }

            $name = strtolower(trim((string) ($template['name'] ?? '')));
            if ($name !== '') {
                $nameGroups[$name][] = $index;
            }

            $image = strtolower(trim((string) ($template['image'] ?? '')));
            if ($image !== '') {
                $imageGroups[$image][] = $index;
            }
        }

        foreach ($nameGroups as $name => $indexes) {
            if (count($indexes) < 2) {
                continue;
            }
            foreach ($indexes as $index) {
                $issues[] = $this->newIssue(
                    'warning',
                    (string) ($templates[$index]['filename'] ?? ''),
                    'duplicate_name',
                    "Duplicate template name detected: {$name}"
                );
            }
        }

        foreach ($imageGroups as $image => $indexes) {
            if (count($indexes) < 2) {
                continue;
            }
            foreach ($indexes as $index) {
                $issues[] = $this->newIssue(
                    'info',
                    (string) ($templates[$index]['filename'] ?? ''),
                    'shared_image',
                    "Multiple templates use image: {$image}"
                );
            }
        }

        foreach ($templates as $template) {
            $filename = (string) ($template['filename'] ?? '');
            $state = (string) ($template['status'] ?? '');
            $mapping = (string) ($template['mapping_status'] ?? '');

            if ($state === 'invalid') {
                $issues[] = $this->newIssue('error', $filename, 'invalid_xml', 'Template XML is invalid.');
                continue;
            }

            if ($mapping === 'orphaned') {
                $issues[] = $this->newIssue('warning', $filename, 'orphaned', 'No matching container found.');
            } elseif ($mapping === 'unknown') {
                $issues[] = $this->newIssue('info', $filename, 'mapping_unknown', 'Mapping not available (docker unavailable).');
            }
        }

        foreach ($unmatchedContainers as $container) {
            $containerName = (string) ($container['name'] ?? '');
            $issues[] = [
                'severity' => 'warning',
                'target' => $containerName,
                'code' => 'container_missing_template',
                'message' => "Container has no matched template: {$containerName}",
            ];
        }

        $templatesByFile = [];
        foreach ($issues as $issue) {
            $target = (string) ($issue['target'] ?? '');
            if ($target === '' || !str_ends_with(strtolower($target), '.xml')) {
                continue;
            }
            $templatesByFile[$target][] = $issue;
        }

        $annotatedTemplates = [];
        foreach ($templates as $template) {
            $filename = (string) ($template['filename'] ?? '');
            $templateIssues = $templatesByFile[$filename] ?? [];
            $template['severity'] = $this->highestSeverity($templateIssues);
            $template['issue_summary'] = $this->summarizeIssues($templateIssues);
            $annotatedTemplates[] = $template;
        }

        return [
            'templates' => $annotatedTemplates,
            'issues' => $issues,
        ];
    }

    /**
     * @param array<int, array<string, string>> $issues
     */
    private function highestSeverity(array $issues): string
    {
        $rank = ['info' => 1, 'warning' => 2, 'error' => 3];
        $current = 'info';
        $currentRank = 1;

        foreach ($issues as $issue) {
            $severity = (string) ($issue['severity'] ?? 'info');
            $severityRank = $rank[$severity] ?? 1;
            if ($severityRank > $currentRank) {
                $current = $severity;
                $currentRank = $severityRank;
            }
        }

        return $current;
    }

    /**
     * @param array<int, array<string, string>> $issues
     */
    private function summarizeIssues(array $issues): string
    {
        if (count($issues) === 0) {
            return 'No issues';
        }

        $messages = array_map(
            static fn(array $issue): string => (string) ($issue['message'] ?? ''),
            $issues
        );

        return implode(' | ', array_filter($messages, static fn(string $message): bool => $message !== ''));
    }

    /**
     * @return array<string, string>
     */
    private function newIssue(string $severity, string $target, string $code, string $message): array
    {
        return [
            'severity' => $severity,
            'target' => $target,
            'code' => $code,
            'message' => $message,
        ];
    }
}

