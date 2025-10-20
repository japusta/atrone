<?php

namespace App\Core\Template;

use InvalidArgumentException;

final class TemplateRenderer
{
    private string $baseDir;

    /**
     * @var array<string, mixed>
     */
    private array $globals = [];

    public function __construct(string $baseDir)
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function assign(string $key, $value): void
    {
        $this->globals[$key] = $value;
    }

    public function render(string $template, array $data = []): string
    {
        $path = $this->resolvePath($template);
        $variables = array_merge($this->globals, $data);

        ob_start();
        extract($variables, EXTR_OVERWRITE);
        include $path;

        return (string) ob_get_clean();
    }

    public function display(string $template, array $data = []): void
    {
        echo $this->render($template, $data);
    }

    private function resolvePath(string $template): string
    {
        $normalized = str_replace(['\\', '..'], ['/', ''], $template);
        if (substr($normalized, -4) !== '.php') {
            $normalized .= '.php';
        }

        $path = $this->baseDir.'/'.$normalized;
        if (!is_file($path)) {
            throw new InvalidArgumentException(sprintf('Template "%s" not found in %s', $template, $this->baseDir));
        }

        return $path;
    }
}
