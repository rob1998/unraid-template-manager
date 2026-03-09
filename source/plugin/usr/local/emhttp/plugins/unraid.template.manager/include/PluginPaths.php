<?php
declare(strict_types=1);

namespace UnraidTemplateManager;

final class PluginPaths
{
    public const PLUGIN_NAME = 'unraid.template.manager';
    public const CONFIG_DIR = '/boot/config/plugins/' . self::PLUGIN_NAME;
    public const BACKUP_DIR = self::CONFIG_DIR . '/backups';
    public const TEMPLATES_DIR = '/boot/config/plugins/dockerMan/templates-user';

    public static function ensureConfigDirectory(): void
    {
        if (!is_dir(self::CONFIG_DIR)) {
            @mkdir(self::CONFIG_DIR, 0775, true);
        }

        if (!is_dir(self::BACKUP_DIR)) {
            @mkdir(self::BACKUP_DIR, 0775, true);
        }
    }
}

