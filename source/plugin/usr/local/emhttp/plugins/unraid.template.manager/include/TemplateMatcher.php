<?php
declare(strict_types=1);

namespace UnraidTemplateManager;

final class TemplateMatcher
{
    /**
     * @param array<int, array<string, mixed>> $templates
     * @param array<int, array<string, string>> $containers
     * @return array{templates: array<int, array<string, mixed>>, unmatched_containers: array<int, array<string, string>>}
     */
    public function mapTemplatesToContainers(array $templates, array $containers, bool $dockerAvailable): array
    {
        $assignedContainerNames = [];
        $mappedTemplates = [];

        foreach ($templates as $template) {
            $mapped = $this->matchTemplate($template, $containers, $dockerAvailable);
            if (($mapped['matched'] ?? false) === true) {
                $containerName = (string) ($mapped['matched_container'] ?? '');
                if ($containerName !== '') {
                    $assignedContainerNames[strtolower($containerName)] = true;
                }
            }
            $mappedTemplates[] = $mapped;
        }

        $unmatchedContainers = [];
        foreach ($containers as $container) {
            $key = strtolower((string) ($container['name'] ?? ''));
            if ($key === '') {
                continue;
            }
            if (!isset($assignedContainerNames[$key])) {
                $unmatchedContainers[] = $container;
            }
        }

        return [
            'templates' => $mappedTemplates,
            'unmatched_containers' => $unmatchedContainers,
        ];
    }

    /**
     * @param array<string, mixed> $template
     * @param array<int, array<string, string>> $containers
     * @return array<string, mixed>
     */
    private function matchTemplate(array $template, array $containers, bool $dockerAvailable): array
    {
        $templateName = (string) ($template['name'] ?? '');
        $templateImage = strtolower((string) ($template['image'] ?? ''));
        $filename = (string) ($template['filename'] ?? '');

        $result = $template;
        $result['matched'] = false;
        $result['matched_container'] = '';
        $result['match_reason'] = '';
        $result['mapping_status'] = 'unknown';

        if ((string) ($template['status'] ?? '') === 'invalid') {
            $result['mapping_status'] = 'invalid';
            $result['match_reason'] = 'XML parse failure';
            return $result;
        }

        if (!$dockerAvailable) {
            $result['mapping_status'] = 'unknown';
            $result['match_reason'] = 'Docker CLI unavailable';
            return $result;
        }

        $normalizedTemplateName = strtolower($templateName);
        $strippedTemplateName = strtolower(preg_replace('/^my-/i', '', $templateName) ?? $templateName);
        $filenameName = strtolower(preg_replace('/\.xml$/i', '', $filename) ?? $filename);
        $strippedFilenameName = strtolower(preg_replace('/^my-/i', '', $filenameName) ?? $filenameName);

        foreach ($containers as $container) {
            $containerName = strtolower((string) ($container['name'] ?? ''));
            if ($containerName === '' || $normalizedTemplateName === '') {
                continue;
            }

            if ($containerName === $normalizedTemplateName) {
                return $this->markMatched($result, (string) $container['name'], 'Exact container name match');
            }
        }

        foreach ($containers as $container) {
            $containerName = strtolower((string) ($container['name'] ?? ''));
            if ($containerName === '' || $strippedTemplateName === '') {
                continue;
            }

            if ($containerName === $strippedTemplateName) {
                return $this->markMatched($result, (string) $container['name'], 'Name match after stripping "my-" prefix');
            }
        }

        foreach ($containers as $container) {
            $containerName = strtolower((string) ($container['name'] ?? ''));
            if ($containerName === '' || $filenameName === '') {
                continue;
            }

            if ($containerName === $filenameName || $containerName === $strippedFilenameName) {
                return $this->markMatched($result, (string) $container['name'], 'Filename-derived match');
            }
        }

        if ($templateImage !== '') {
            foreach ($containers as $container) {
                $containerImage = strtolower((string) ($container['image'] ?? ''));
                if ($containerImage !== '' && $containerImage === $templateImage) {
                    return $this->markMatched($result, (string) $container['name'], 'Image match (heuristic)');
                }
            }
        }

        $result['mapping_status'] = 'orphaned';
        $result['match_reason'] = 'No deterministic container match';
        return $result;
    }

    /**
     * @param array<string, mixed> $template
     * @return array<string, mixed>
     */
    private function markMatched(array $template, string $containerName, string $reason): array
    {
        $template['matched'] = true;
        $template['matched_container'] = $containerName;
        $template['mapping_status'] = 'matched';
        $template['match_reason'] = $reason;
        return $template;
    }
}

