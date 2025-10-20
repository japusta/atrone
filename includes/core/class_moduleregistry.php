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
                    $callable = self::normalizeHandler($handler);
                    if ($callable) {
                        self::$pages[$path] = $callable;
                    }
                }
            }

            if (isset($config['api']) && is_array($config['api'])) {
                foreach ($config['api'] as $path => $handler) {
                    $callable = self::normalizeHandler($handler);
                    if ($callable) {
                        self::$api[$path] = $callable;
                    }
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

    private static function normalizeHandler($handler)
    {
        if (is_callable($handler)) {
            return $handler;
        }

        if (is_string($handler) && class_exists($handler)) {
            $instance = new $handler();
            if (is_callable($instance)) {
                return $instance;
            }

            if (method_exists($instance, 'handle')) {
                return [$instance, 'handle'];
            }

            if (method_exists($instance, '__invoke')) {
                return $instance;
            }
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            if (is_string($class) && class_exists($class)) {
                $instance = new $class();
                if (method_exists($instance, $method)) {
                    return [$instance, $method];
                }
            } elseif (is_object($class) && method_exists($class, $method)) {
                return [$class, $method];
            }
        }

        return null;
    }
}