<?php

namespace App\Core\Http;

final class JsonResponse extends Response
{
    public function __construct($data, int $statusCode = 200)
    {
        parent::__construct(json_encode($data, JSON_UNESCAPED_UNICODE), $statusCode, ['Content-Type' => 'application/json']);
    }
}