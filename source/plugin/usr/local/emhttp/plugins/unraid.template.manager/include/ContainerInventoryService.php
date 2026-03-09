<?php
declare(strict_types=1);

namespace UnraidTemplateManager;

final class ContainerInventoryService
{
    /**
     * @return array<int, array<string, string>>
     */
    public function listContainers(): array
    {
        $dockerBinary = $this->locateDockerBinary();
        if ($dockerBinary === null) {
            return [];
        }

        $command = escapeshellarg($dockerBinary) . " ps -a --format '{{json .}}' 2>/dev/null";
        $lines = [];
        $exitCode = 0;
        @exec($command, $lines, $exitCode);

        if ($exitCode !== 0) {
            return [];
        }

        $containers = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $row = json_decode($line, true);
            if (!is_array($row)) {
                continue;
            }

            $containers[] = [
                'id' => (string) ($row['ID'] ?? ''),
                'name' => (string) ($row['Names'] ?? ''),
                'image' => (string) ($row['Image'] ?? ''),
                'state' => (string) ($row['State'] ?? ''),
                'status' => (string) ($row['Status'] ?? ''),
            ];
        }

        usort(
            $containers,
            static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name'])
        );

        return $containers;
    }

    public function isDockerAvailable(): bool
    {
        return $this->locateDockerBinary() !== null;
    }

    private function locateDockerBinary(): ?string
    {
        $known = ['/usr/bin/docker', '/bin/docker'];
        foreach ($known as $binary) {
            if (is_executable($binary)) {
                return $binary;
            }
        }

        $path = trim((string) @shell_exec('command -v docker 2>/dev/null'));
        if ($path !== '' && is_executable($path)) {
            return $path;
        }

        return null;
    }
}

