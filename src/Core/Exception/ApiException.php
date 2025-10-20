<?php

namespace App\Core\Exception;

use RuntimeException;

final class ApiException extends RuntimeException
{
    private int $errorCode;
    private array $errorData;

    public function __construct(int $errorCode, string $message, array $errorData = [])
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
        $this->errorData = $errorData;
    }

    public function toArray(): array
    {
        $response = [
            'error_code' => $this->errorCode,
            'error_msg' => $this->getMessage(),
        ];

        if ($this->errorData) {
            $response['error_data'] = $this->errorData;
        }

        return $response;
    }
}