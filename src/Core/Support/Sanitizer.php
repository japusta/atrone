<?php

namespace App\Core\Support;

final class Sanitizer
{
    public function sanitize(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $trimmed = trim($value);
        $stripped = strip_tags($trimmed);

        return filter_var($stripped, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH) ?? '';
    }

    public function sanitizeDigits(?string $value): string
    {
        return preg_replace('~\D+~', '', $value ?? '') ?? '';
    }
}