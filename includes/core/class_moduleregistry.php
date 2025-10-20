<?php

class ModuleRegistry
{
    private static $pages = [];
    private static $api = [];
    private static $loaded = false;

    public static function load(string $baseDir = './modules')
    {
        if (self::$loaded) {
            return;
        }

        if (!is_dir($baseDir)) {
            self::$loaded = true;
            return;
        }

        $items = scandir($baseDir);
        if ($items === false) {
            self::$loaded = true;
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $moduleDir = rtrim($baseDir, '/').'/'.$item;
            if (!is_dir($moduleDir)) {
                continue;
            }

            $configPath = $moduleDir.'/module.php';
            if (!file_exists($configPath)) {
                continue;
            }

            $config = require $configPath;
            if (isset($config['pages']) && is_array($config['pages'])) {
                foreach ($config['pages'] as $path => $handler) {
                    self::$pages[$path] = $handler;
                }
            }

            if (isset($config['api']) && is_array($config['api'])) {
                foreach ($config['api'] as $path => $handler) {
                    self::$api[$path] = $handler;
                }
            }
        }

        self::$loaded = true;
    }

    public static function hasPage(string $path): bool
    {
        return isset(self::$pages[$path]);
    }

    public static function handlePage(string $path, array $query = []): bool
    {
        if (!isset(self::$pages[$path])) {
            return false;
        }

        call_user_func(self::$pages[$path], $query);
        return true;
    }

    public static function hasApi(string $path): bool
    {
        return isset(self::$api[$path]);
    }

    public static function handleApi(string $path, ?string $action, $payload)
    {
        if (!isset(self::$api[$path])) {
            return null;
        }

        return call_user_func(self::$api[$path], $action, $payload);
    }
}