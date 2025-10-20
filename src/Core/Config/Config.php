<?php

namespace App\Core\Config;

final class Config
{
    public function getDsn(): string
    {
        return sprintf('mysql:dbname=%s;host=%s;charset=utf8mb4;', DB_NAME, DB_HOST);
    }

    public function getDbUser(): string
    {
        return DB_USER;
    }

    public function getDbPassword(): string
    {
        return DB_PASS;
    }

    public function isSecureCookies(): bool
    {
        return SITE_SCHEME === 'https';
    }

    public function getDefaultTimezone(): int
    {
        return (int) DEFAULT_TIMEZONE;
    }

    public function getLoginAttempts(): int
    {
        return (int) LOGIN_ATTEMPTS;
    }
}
