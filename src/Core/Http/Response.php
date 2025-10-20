<?php

namespace App\Core\Http;

final class Response
{
    private string $content;
    private int $statusCode;
    /**
     * @var array<string, string>
     */
    private array $headers;

    public function __construct(string $content, int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header($name.': '.$value, true, $this->statusCode);
        }

        echo $this->content;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
