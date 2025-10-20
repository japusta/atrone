<?php

namespace App\Core\Support;

final class Clock
{
    public function now(): int
    {
        return time();
    }
}