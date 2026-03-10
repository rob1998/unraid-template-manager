<?php
declare(strict_types=1);

namespace UnraidTemplateManager;

use RuntimeException;

final class StorageModeService
{
    private string $dockerConfigFile;
    private string $configBackupDir;

    public function __construct(
        string $dockerConfigFile = '/boot/config/docker.cfg',
        string $configBackupDir = '/boot/config/plugins/unraid.template.manager/backups'
    )
    {
        $this->dockerConfigFile = $dockerConfigFile;
        $this->configBackupDir = $configBackupDir;
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
            'switch_targets' => $this->buildSwitchTargets($mode, $path),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function switchMode(string $targetMode, string $targetPath, bool $restartDocker = false): array
    {
        $targetMode = strtolower(trim($targetMode));
        if (!in_array($targetMode, ['vdisk', 'directory'], true)) {
            throw new RuntimeException('Invalid target mode.');
        }

        $targetPath = trim($targetPath);
        if (!$this->isValidTargetPath($targetPath)) {
            throw new RuntimeException('Invalid target path.');
        }

        if ($targetMode === 'vdisk' && substr(strtolower($targetPath), -4) !== '.img') {
            throw new RuntimeException('vDisk mode requires a .img path.');
        }
        if ($targetMode === 'directory' && substr(strtolower($targetPath), -4) === '.img') {
            throw new RuntimeException('Directory mode requires a directory path.');
        }

        if (!is_file($this->dockerConfigFile)) {
            throw new RuntimeException('docker.cfg not found.');
        }

        $currentRaw = @file_get_contents($this->dockerConfigFile);
        if (!is_string($currentRaw) || $currentRaw === '') {
            throw new RuntimeException('Unable to read docker.cfg.');
        }

        if (!is_dir($this->configBackupDir)) {
            @mkdir($this->configBackupDir, 0775, true);
        }
        $backupFile = rtrim($this->configBackupDir, '/') . '/docker.cfg.' . date('Ymd-His') . '.bak';
        if (!@copy($this->dockerConfigFile, $backupFile)) {
            throw new RuntimeException('Failed to create docker.cfg backup.');
        }

        $updatedRaw = $this->setConfigValue($currentRaw, 'DOCKER_IMAGE_FILE', $targetPath);
        $dockerOpts = $this->getConfigValueFromRaw($updatedRaw, 'DOCKER_OPTS');
        $updatedOpts = $this->normalizeDockerOptsForMode($dockerOpts, $targetMode, $targetPath);
        $updatedRaw = $this->setConfigValue($updatedRaw, 'DOCKER_OPTS', $updatedOpts);

        $tempFile = $this->dockerConfigFile . '.tmp';
        if (@file_put_contents($tempFile, $updatedRaw) === false) {
            throw new RuntimeException('Failed to write temporary docker.cfg.');
        }
        if (!@rename($tempFile, $this->dockerConfigFile)) {
            @unlink($tempFile);
            throw new RuntimeException('Failed to replace docker.cfg.');
        }

        $restartResult = [
            'attempted' => $restartDocker,
            'exit_code' => null,
            'output' => '',
        ];

        if ($restartDocker) {
            $restartResult = $this->restartDocker();
        }

        return [
            'mode' => $targetMode,
            'path' => $targetPath,
            'backup_file' => $backupFile,
            'restart' => $restartResult,
            'detected' => $this->detect(),
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
        if (substr(strtolower($path), -4) === '.img') {
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

    /**
     * @return array<int, array<string, string>>
     */
    private function buildSwitchTargets(string $mode, string $path): array
    {
        $vdiskPath = '/mnt/user/system/docker/docker.img';
        $directoryPath = '/mnt/cache/system/docker';

        if ($mode === 'vdisk' && $path !== '' && substr(strtolower($path), -4) === '.img') {
            $vdiskPath = $path;
        }
        if ($mode === 'directory' && $path !== '' && substr(strtolower($path), -4) !== '.img') {
            $directoryPath = $path;
        }

        return [
            [
                'mode' => 'vdisk',
                'path' => $vdiskPath,
                'label' => 'Switch to vDisk',
            ],
            [
                'mode' => 'directory',
                'path' => $directoryPath,
                'label' => 'Switch to Directory',
            ],
        ];
    }

    private function isValidTargetPath(string $path): bool
    {
        if ($path === '') {
            return false;
        }
        if (strpos($path, "\0") !== false || strpos($path, '..') !== false) {
            return false;
        }
        return strpos($path, '/') === 0;
    }

    private function getConfigValueFromRaw(string $rawConfig, string $key): string
    {
        $pattern = '/^' . preg_quote($key, '/') . '=(.*)$/m';
        if (!preg_match($pattern, $rawConfig, $matches)) {
            return '';
        }
        $value = trim((string) ($matches[1] ?? ''));
        return trim($value, "\"'");
    }

    private function setConfigValue(string $rawConfig, string $key, string $value): string
    {
        $value = str_replace('"', '\"', $value);
        $line = $key . '="' . $value . '"';
        $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
        if (preg_match($pattern, $rawConfig)) {
            return (string) preg_replace($pattern, $line, $rawConfig);
        }

        $suffix = $rawConfig !== '' && substr($rawConfig, -1) !== "\n" ? "\n" : '';
        return $rawConfig . $suffix . $line . "\n";
    }

    private function normalizeDockerOptsForMode(string $dockerOpts, string $targetMode, string $targetPath): string
    {
        $dockerOpts = trim($dockerOpts);
        if ($dockerOpts !== '') {
            $dockerOpts = (string) preg_replace('/\s*--data-root=[^ ]+/', '', $dockerOpts);
            $dockerOpts = trim((string) preg_replace('/\s+/', ' ', $dockerOpts));
        }

        if ($targetMode === 'directory') {
            $dockerOpts = trim($dockerOpts . ' --data-root=' . $targetPath);
        }

        return $dockerOpts;
    }

    /**
     * @return array<string, mixed>
     */
    private function restartDocker(): array
    {
        $command = '/etc/rc.d/rc.docker restart 2>&1';
        $output = [];
        $exitCode = 0;
        @exec($command, $output, $exitCode);

        return [
            'attempted' => true,
            'exit_code' => $exitCode,
            'output' => implode("\n", $output),
        ];
    }
}
