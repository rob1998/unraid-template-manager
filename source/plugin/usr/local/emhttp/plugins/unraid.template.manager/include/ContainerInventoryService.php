<?php
declare(strict_types=1);

namespace UnraidTemplateManager;

final class ContainerInventoryService
{
    private string $cacheFile;
    private float $commandTimeoutSeconds;
    private int $cacheTtlSeconds;
    /** @var array<string, mixed> */
    private array $lastRunMeta = [
        'source' => 'none',
        'timed_out' => false,
        'duration_ms' => 0,
        'cache_age_seconds' => null,
        'error' => '',
    ];

    public function __construct(
        string $cacheFile = '/boot/config/plugins/unraid.template.manager/cache/containers.json',
        float $commandTimeoutSeconds = 6.0,
        int $cacheTtlSeconds = 120
    )
    {
        $this->cacheFile = $cacheFile;
        $this->commandTimeoutSeconds = $commandTimeoutSeconds;
        $this->cacheTtlSeconds = $cacheTtlSeconds;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function listContainers(): array
    {
        $freshCache = $this->readCache(true);
        if ($freshCache !== null) {
            $this->lastRunMeta = [
                'source' => 'cache',
                'timed_out' => false,
                'duration_ms' => 0,
                'cache_age_seconds' => $freshCache['age_seconds'],
                'error' => '',
            ];
            return $freshCache['containers'];
        }

        $dockerBinary = $this->locateDockerBinary();
        if ($dockerBinary === null) {
            $staleCache = $this->readCache(false);
            if ($staleCache !== null) {
                $this->lastRunMeta = [
                    'source' => 'cache_stale',
                    'timed_out' => false,
                    'duration_ms' => 0,
                    'cache_age_seconds' => $staleCache['age_seconds'],
                    'error' => 'Docker binary not found.',
                ];
                return $staleCache['containers'];
            }

            $this->lastRunMeta = [
                'source' => 'none',
                'timed_out' => false,
                'duration_ms' => 0,
                'cache_age_seconds' => null,
                'error' => 'Docker binary not found.',
            ];
            return [];
        }

        $command = $this->buildDockerPsCommand($dockerBinary);
        $result = $this->runCommandWithTimeout($command, $this->commandTimeoutSeconds);
        if (($result['success'] ?? false) === true) {
            $containers = $this->parseContainerLines((string) ($result['stdout'] ?? ''));
            $this->writeCache($containers);
            $this->lastRunMeta = [
                'source' => 'live',
                'timed_out' => false,
                'duration_ms' => (int) ($result['duration_ms'] ?? 0),
                'cache_age_seconds' => 0,
                'error' => '',
            ];
            return $containers;
        }

        if ((bool) ($result['timed_out'] ?? false)) {
            $fallbackCommand = $this->buildDockerNamesOnlyCommand($dockerBinary);
            $fallbackResult = $this->runCommandWithTimeout($fallbackCommand, max(2.0, $this->commandTimeoutSeconds / 2.0));
            if (($fallbackResult['success'] ?? false) === true) {
                $containers = $this->parseContainerNames((string) ($fallbackResult['stdout'] ?? ''));
                $this->writeCache($containers);
                $this->lastRunMeta = [
                    'source' => 'live_fallback',
                    'timed_out' => true,
                    'duration_ms' => (int) (($result['duration_ms'] ?? 0) + ($fallbackResult['duration_ms'] ?? 0)),
                    'cache_age_seconds' => 0,
                    'error' => 'Primary docker inventory timed out; using names-only fallback.',
                ];
                return $containers;
            }
        }

        $staleCache = $this->readCache(false);
        if ($staleCache !== null) {
            $this->lastRunMeta = [
                'source' => 'cache_stale',
                'timed_out' => (bool) ($result['timed_out'] ?? false),
                'duration_ms' => (int) ($result['duration_ms'] ?? 0),
                'cache_age_seconds' => $staleCache['age_seconds'],
                'error' => (string) ($result['error'] ?? 'Docker command failed.'),
            ];
            return $staleCache['containers'];
        }

        $this->lastRunMeta = [
            'source' => 'none',
            'timed_out' => (bool) ($result['timed_out'] ?? false),
            'duration_ms' => (int) ($result['duration_ms'] ?? 0),
            'cache_age_seconds' => null,
            'error' => (string) ($result['error'] ?? 'Docker command failed.'),
        ];
        return [];
    }

    public function isDockerAvailable(): bool
    {
        return $this->locateDockerBinary() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getLastRunMeta(): array
    {
        return $this->lastRunMeta;
    }

    /**
     * @param string $stdout
     * @return array<int, array<string, string>>
     */
    private function parseContainerLines(string $stdout): array
    {
        $lines = preg_split('/\R/', $stdout) ?: [];
        $containers = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            $parts = explode("\t", $line);
            if (count($parts) >= 5) {
                $containers[] = [
                    'id' => (string) ($parts[0] ?? ''),
                    'name' => (string) ($parts[1] ?? ''),
                    'image' => (string) ($parts[2] ?? ''),
                    'state' => (string) ($parts[3] ?? ''),
                    'status' => (string) ($parts[4] ?? ''),
                ];
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

    /**
     * @return array<int, array<string, string>>
     */
    private function parseContainerNames(string $stdout): array
    {
        $lines = preg_split('/\R/', $stdout) ?: [];
        $containers = [];
        foreach ($lines as $line) {
            $name = trim((string) $line);
            if ($name === '') {
                continue;
            }

            $containers[] = [
                'id' => '',
                'name' => $name,
                'image' => '',
                'state' => '',
                'status' => '',
            ];
        }

        usort(
            $containers,
            static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name'])
        );

        return $containers;
    }

    /**
     * @return array{containers: array<int, array<string, string>>, age_seconds: int}|null
     */
    private function readCache(bool $freshOnly): ?array
    {
        if (!is_file($this->cacheFile)) {
            return null;
        }

        $mtime = (int) (@filemtime($this->cacheFile) ?: 0);
        if ($mtime <= 0) {
            return null;
        }

        $age = max(0, time() - $mtime);
        if ($freshOnly && $age > $this->cacheTtlSeconds) {
            return null;
        }

        $raw = @file_get_contents($this->cacheFile);
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['containers']) || !is_array($decoded['containers'])) {
            return null;
        }

        /** @var array<int, array<string, string>> $containers */
        $containers = $decoded['containers'];

        return [
            'containers' => $containers,
            'age_seconds' => $age,
        ];
    }

    /**
     * @param array<int, array<string, string>> $containers
     */
    private function writeCache(array $containers): void
    {
        $directory = dirname($this->cacheFile);
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        @file_put_contents(
            $this->cacheFile,
            json_encode(
                ['created_at' => date('c'), 'containers' => $containers],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );
    }

    /**
     * @return array{success: bool, stdout: string, error: string, timed_out: bool, duration_ms: int}
     */
    private function runCommandWithTimeout(string $command, float $timeoutSeconds): array
    {
        $timeoutBinary = $this->locateTimeoutBinary();
        if ($timeoutBinary !== null) {
            return $this->runCommandWithTimeoutBinary($command, $timeoutSeconds, $timeoutBinary);
        }

        if (!$this->isProcOpenAvailable()) {
            return $this->runCommandWithExecFallback($command);
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $start = microtime(true);
        $process = @proc_open($command, $descriptors, $pipes);
        if (!is_resource($process)) {
            return [
                'success' => false,
                'stdout' => '',
                'error' => 'Failed to start docker command.',
                'timed_out' => false,
                'duration_ms' => 0,
            ];
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        @fclose($pipes[0]);

        $stdout = '';
        $stderr = '';
        $timedOut = false;

        while (true) {
            $status = proc_get_status($process);
            $stdout .= (string) @stream_get_contents($pipes[1]);
            $stderr .= (string) @stream_get_contents($pipes[2]);

            if (($status['running'] ?? false) !== true) {
                break;
            }

            if ((microtime(true) - $start) >= $timeoutSeconds) {
                $timedOut = true;
                @proc_terminate($process, 9);
                break;
            }

            usleep(20000);
        }

        $stdout .= (string) @stream_get_contents($pipes[1]);
        $stderr .= (string) @stream_get_contents($pipes[2]);
        @fclose($pipes[1]);
        @fclose($pipes[2]);
        $exitCode = @proc_close($process);
        $durationMs = (int) round((microtime(true) - $start) * 1000);

        if ($timedOut) {
            return [
                'success' => false,
                'stdout' => '',
                'error' => 'Docker command timed out.',
                'timed_out' => true,
                'duration_ms' => $durationMs,
            ];
        }

        if ($exitCode !== 0) {
            return [
                'success' => false,
                'stdout' => '',
                'error' => trim($stderr) !== '' ? trim($stderr) : 'Docker command failed.',
                'timed_out' => false,
                'duration_ms' => $durationMs,
            ];
        }

        return [
            'success' => true,
            'stdout' => $stdout,
            'error' => '',
            'timed_out' => false,
            'duration_ms' => $durationMs,
        ];
    }

    /**
     * @return array{success: bool, stdout: string, error: string, timed_out: bool, duration_ms: int}
     */
    private function runCommandWithTimeoutBinary(string $command, float $timeoutSeconds, string $timeoutBinary): array
    {
        $start = microtime(true);
        $output = [];
        $exitCode = 0;
        $seconds = max(1, (int) ceil($timeoutSeconds));
        $wrapped = escapeshellarg($timeoutBinary) . ' ' . $seconds . ' sh -c ' . escapeshellarg($command) . ' 2>&1';
        @exec($wrapped, $output, $exitCode);
        $durationMs = (int) round((microtime(true) - $start) * 1000);

        if (in_array($exitCode, [124, 137, 143], true)) {
            return [
                'success' => false,
                'stdout' => '',
                'error' => 'Docker command timed out.',
                'timed_out' => true,
                'duration_ms' => $durationMs,
            ];
        }

        if ($exitCode !== 0) {
            return [
                'success' => false,
                'stdout' => '',
                'error' => trim(implode("\n", $output)) ?: 'Docker command failed.',
                'timed_out' => false,
                'duration_ms' => $durationMs,
            ];
        }

        return [
            'success' => true,
            'stdout' => implode("\n", $output),
            'error' => '',
            'timed_out' => false,
            'duration_ms' => $durationMs,
        ];
    }

    /**
     * @return array{success: bool, stdout: string, error: string, timed_out: bool, duration_ms: int}
     */
    private function runCommandWithExecFallback(string $command): array
    {
        $start = microtime(true);
        $output = [];
        $exitCode = 0;
        @exec($command . ' 2>&1', $output, $exitCode);
        $durationMs = (int) round((microtime(true) - $start) * 1000);
        if ($exitCode !== 0) {
            return [
                'success' => false,
                'stdout' => '',
                'error' => trim(implode("\n", $output)) ?: 'Docker command failed.',
                'timed_out' => false,
                'duration_ms' => $durationMs,
            ];
        }

        return [
            'success' => true,
            'stdout' => implode("\n", $output),
            'error' => '',
            'timed_out' => false,
            'duration_ms' => $durationMs,
        ];
    }

    private function buildDockerPsCommand(string $dockerBinary): string
    {
        return escapeshellarg($dockerBinary) . " ps -a --format '{{.ID}}\t{{.Names}}\t{{.Image}}\t{{.State}}\t{{.Status}}' 2>/dev/null";
    }

    private function buildDockerNamesOnlyCommand(string $dockerBinary): string
    {
        return escapeshellarg($dockerBinary) . " ps -a --format '{{.Names}}' 2>/dev/null";
    }

    private function locateDockerBinary(): ?string
    {
        $known = ['/usr/bin/docker', '/bin/docker', '/usr/local/bin/docker', '/usr/sbin/docker'];
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

    private function isProcOpenAvailable(): bool
    {
        if (!function_exists('proc_open')) {
            return false;
        }

        $disabled = trim((string) ini_get('disable_functions'));
        if ($disabled === '') {
            return true;
        }

        $functions = array_map('trim', explode(',', $disabled));
        return !in_array('proc_open', $functions, true);
    }

    /**
     * @return string|null
     */
    private function locateTimeoutBinary(): ?string
    {
        $known = ['/usr/bin/timeout', '/bin/timeout', '/usr/local/bin/timeout'];
        foreach ($known as $binary) {
            if (is_executable($binary)) {
                return $binary;
            }
        }

        return null;
    }
}
