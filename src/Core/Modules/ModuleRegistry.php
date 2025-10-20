<?php

namespace App\Core\Modules;

use App\Core\Container;
use App\Core\Http\Request;
use InvalidArgumentException;

final class ModuleRegistry
{
    private Container $container;
    /** @var array<string, callable> */
    private array $pages = [];
    /** @var array<string, callable> */
    private array $apis = [];

    public function __construct(Container $container, ?string $baseDir = null)
    {
        $this->container = $container;
        $modulesDir = $baseDir ?? dirname(__DIR__, 3).'/modules';
        $this->loadModules($modulesDir);
    }

    public function hasPage(string $path): bool
    {
        return isset($this->pages[$path]);
    }

    public function handlePage(string $path, Request $request)
    {
        if (!isset($this->pages[$path])) {
            return null;
        }

        return ($this->pages[$path])($request);
    }

    public function hasApi(string $path): bool
    {
        return isset($this->apis[$path]);
    }

    public function handleApi(string $path, ?string $action, array $payload)
    {
        if (!isset($this->apis[$path])) {
            return null;
        }

        return ($this->apis[$path])($action, $payload);
    }

    private function loadModules(string $baseDir): void
    {
        if (!is_dir($baseDir)) {
            return;
        }

        $items = scandir($baseDir);
        if ($items === false) {
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
            if (!is_file($configPath)) {
                continue;
            }

            $config = require $configPath;
            if (!is_array($config)) {
                continue;
            }

            if (isset($config['pages']) && is_array($config['pages'])) {
                foreach ($config['pages'] as $path => $handler) {
                    $this->pages[$path] = $this->normalizeHandler($handler);
                }
            }

            if (isset($config['api']) && is_array($config['api'])) {
                foreach ($config['api'] as $path => $handler) {
                    $this->apis[$path] = $this->normalizeHandler($handler);
                }
            }
        }
    }

    private function normalizeHandler($handler): callable
    {
        if (is_callable($handler) && !is_string($handler)) {
            return $handler;
        }

        if (is_string($handler) && class_exists($handler)) {
            return function (...$arguments) use ($handler) {
                $instance = $this->container->get($handler);
                if (is_callable($instance)) {
                    return $instance(...$arguments);
                }

                if (method_exists($instance, '__invoke')) {
                    return $instance(...$arguments);
                }

                throw new InvalidArgumentException(sprintf('Handler %s is not invokable.', $handler));
            };
        }

        if (is_array($handler) && count($handler) === 2) {
            [$classOrInstance, $method] = $handler;

            return function (...$arguments) use ($classOrInstance, $method) {
                $instance = is_string($classOrInstance)
                    ? $this->container->get($classOrInstance)
                    : $classOrInstance;

                if (!method_exists($instance, $method)) {
                    throw new InvalidArgumentException(sprintf('Method %s::%s does not exist.', is_object($instance) ? get_class($instance) : $classOrInstance, $method));
                }

                return $instance->$method(...$arguments);
            };
        }

        throw new InvalidArgumentException('Unsupported handler definition.');
    }
}
