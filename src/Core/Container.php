<?php

namespace App\Core;

use InvalidArgumentException;

final class Container
{
    /**
     * @var array<string, callable(self):mixed>
     */
    private array $factories = [];

    /**
     * @var array<string, mixed>
     */
    private array $instances = [];

    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->factories[$id]);
    }

    /**
     * @template T
     * @param class-string<T>|string $id
     * @return T|mixed
     */
    public function get(string $id)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->factories[$id])) {
            throw new InvalidArgumentException(sprintf('Service "%s" is not registered in the container.', $id));
        }

        $this->instances[$id] = ($this->factories[$id])($this);

        return $this->instances[$id];
    }
}