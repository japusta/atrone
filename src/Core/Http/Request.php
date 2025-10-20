<?php

namespace App\Core\Http;

final class Request
{
    private string $path;
    private array $queryParams;
    private array $postParams;
    private array $cookies;
    private string $method;

    public function __construct(string $path, array $queryParams, array $postParams, array $cookies, string $method)
    {
        $this->path = ltrim($path, '/');
        $this->queryParams = $queryParams;
        $this->postParams = $postParams;
        $this->cookies = $cookies;
        $this->method = strtoupper($method);
    }

    public static function fromGlobals(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $parts = explode('?', $uri, 2);
        $path = $parts[0] ?? '';
        $queryParams = [];

        if (isset($parts[1])) {
            parse_str($parts[1], $queryParams);
        }

        return new self($path, $queryParams, $_POST, $_COOKIE, $method);
    }

    public function getPath(): string
    {
        return $this->path !== '' ? $this->path : 'plots';
    }

    public function getQueryParam(string $key, $default = null)
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function getAllQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getPostParam(string $key, $default = null)
    {
        return $this->postParams[$key] ?? $default;
    }

    public function getAllPostParams(): array
    {
        return $this->postParams;
    }

    public function getCookie(string $name, $default = null)
    {
        return $this->cookies[$name] ?? $default;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}
