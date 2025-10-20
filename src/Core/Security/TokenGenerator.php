<?php

namespace App\Core\Security;

final class TokenGenerator
{
    public function generate(int $length = 40): string
    {
        return bin2hex(random_bytes((int) max(1, ceil($length / 2))));
    }
}