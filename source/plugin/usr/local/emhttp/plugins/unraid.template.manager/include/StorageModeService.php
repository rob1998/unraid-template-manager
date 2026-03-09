<?php
declare(strict_types=1);

namespace UnraidTemplateManager;

final class StorageModeService
{
    private string $dockerConfigFile;

    public function __construct(string $dockerConfigFile = '/boot/config/docker.cfg')
    {
        $this->dockerConfigFile = $dockerConfigFile;
    }

    /**
     * @return array<string, mixed>
     */
    public function detect(): array
    {
        if (!is_file($this->dockerConfigFile)) {
            return [
                'mode' => 'unknown',
                'path' => '',
                'exists' => false,
                'details' => 'docker.cfg not found.',
                'guidance' => 'Enable Docker in Unraid to populate docker configuration.',
            ];
        }

        $config = @parse_ini_file($this->dockerConfigFile);
        if (!is_array($config)) {
            return [
                'mode' => 'unknown',
                'path' => '',
                'exists' => false,
                'details' => 'docker.cfg is unreadable.',
                'guidance' => 'Validate file permissions and Docker settings in Unraid.',
            ];
        }

        $path = $this->extractDataRootPath($config);
        $mode = $this->resolveMode($path);
        $exists = ($path !== '') && (is_file($path) || is_dir($path));

        return [
            'mode' => $mode,
            'path' => $path,
            'exists' => $exists,
            'details' => $this->detailsFor($mode, $path, $exists),
            'guidance' => $this->guidanceFor($mode),
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    private function extractDataRootPath(array $config): string
    {
        $candidates = ['DOCKER_IMAGE_FILE', 'DOCKER_IMAGE_PATH', 'DOCKER_IMAGE'];
        foreach ($candidates as $key) {
            $value = trim((string) ($config[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $dockerOpts = (string) ($config['DOCKER_OPTS'] ?? '');
        if ($dockerOpts !== '' && preg_match('/--data-root=([^ ]+)/', $dockerOpts, $matches)) {
            return trim((string) ($matches[1] ?? ''));
        }

        return '';
    }

    private function resolveMode(string $path): string
    {
        if ($path === '') {
            return 'unknown';
        }
        if (str_ends_with(strtolower($path), '.img')) {
            return 'vdisk';
        }
        return 'directory';
    }

    private function detailsFor(string $mode, string $path, bool $exists): string
    {
        if ($mode === 'unknown') {
            return 'Unable to infer Docker storage mode from config.';
        }

        $type = $mode === 'vdisk' ? 'vDisk image' : 'directory';
        $existLabel = $exists ? 'present' : 'missing';
        return "Detected {$type} path: {$path} ({$existLabel}).";
    }

    private function guidanceFor(string $mode): string
    {
        if ($mode === 'vdisk') {
            return 'vDisk mode is common but can be harder to inspect/recover. Keep template backups current.';
        }
        if ($mode === 'directory') {
            return 'Directory mode is usually easier for recovery and troubleshooting.';
        }
        return 'Open Unraid Docker settings to confirm current Docker data-root path.';
    }
}

