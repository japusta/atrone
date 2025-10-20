<?php

namespace App\Core;

final class Autoloader
{
    /**
     * @var array<string, string>
     */
    private static array $prefixes = [];

    public static function register(): void
    {
        if (self::$prefixes) {
            return;
        }

        self::$prefixes = [
            'App\\' => dirname(__DIR__),
            'Modules\\' => dirname(__DIR__, 1) . '/../modules',
        ];

        spl_autoload_register([self::class, 'autoload'], true, true);
    }

    private static function autoload(string $class): void
    {
        foreach (self::$prefixes as $prefix => $baseDir) {
            if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
                continue;
            }

            $relativeClass = substr($class, strlen($prefix));
            $file = rtrim($baseDir, '/') . '/' . str_replace('\\', '/', $relativeClass) . '.php';

            if (is_file($file)) {
                require $file;
            }

            return;
        }
    }
}