<?php

namespace App\Core\Session;

use App\Core\Config\Config;
use App\Core\Database\DatabaseConnection;
use App\Core\Security\TokenGenerator;
use App\Core\Support\Clock;
use App\Core\Support\Sanitizer;

final class SessionManager
{
    private DatabaseConnection $database;
    private Config $config;
    private TokenGenerator $tokenGenerator;
    private Sanitizer $sanitizer;
    private Clock $clock;

    private string $token = '';
    private int $userId = 0;
    private int $access = 0;
    private int $timezone;

    public function __construct(
        DatabaseConnection $database,
        Config $config,
        TokenGenerator $tokenGenerator,
        Sanitizer $sanitizer,
        Clock $clock
    ) {
        $this->database = $database;
        $this->config = $config;
        $this->tokenGenerator = $tokenGenerator;
        $this->sanitizer = $sanitizer;
        $this->clock = $clock;
        $this->timezone = $config->getDefaultTimezone();
    }

    public function bootstrapFromCookies(array $cookies): void
    {
        $token = $this->sanitizer->sanitize($cookies['token'] ?? '');
        $timezone = $cookies['timezone'] ?? null;
        if ($timezone !== null && is_numeric($timezone)) {
            $tz = (int) round((float) $timezone);
            if ($tz >= -720 && $tz <= 720) {
                $this->timezone = $tz;
            }
        }

        if ($token === '') {
            return;
        }

        $session = $this->database->fetchOne(
            'SELECT user_id, access FROM sessions WHERE token = :token LIMIT 1',
            ['token' => $token]
        );

        if ($session === null) {
            $this->clearTokenCookie();
            return;
        }

        $this->token = $token;
        $this->userId = (int) $session['user_id'];
        $this->access = (int) $session['access'];
        $this->refresh();
    }

    public function isAuthenticated(): bool
    {
        return $this->access === 1;
    }

    public function getTimezone(): int
    {
        return $this->timezone;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getAccess(): int
    {
        return $this->access;
    }

    public function logout(): void
    {
        if ($this->token !== '') {
            $this->database->execute('DELETE FROM sessions WHERE token = :token LIMIT 1', ['token' => $this->token]);
        }

        $this->clearTokenCookie();
        $this->token = '';
        $this->userId = 0;
        $this->access = 0;
    }

    public function createSession(int $userId, int $access): array
    {
        $this->token = $this->tokenGenerator->generate();
        $this->userId = $userId;
        $this->access = $access;

        $this->clearTokenCookie();
        $this->setCookie('token', $this->token, strtotime('+1 year'));

        $timestamp = $this->clock->now();

        $this->database->execute(
            'UPDATE users SET phone_attempts_code = 0, last_login = :last_login WHERE user_id = :user_id LIMIT 1',
            [
                'last_login' => $timestamp,
                'user_id' => $userId,
            ]
        );

        $this->database->execute(
            'INSERT INTO sessions (user_id, access, token, tz, created, logged) VALUES (:user_id, :access, :token, :tz, :created, :logged)',
            [
                'user_id' => $userId,
                'access' => $access,
                'token' => $this->token,
                'tz' => $this->timezone,
                'created' => $timestamp,
                'logged' => $timestamp,
            ]
        );

        return ['token' => $this->token];
    }

    public function refresh(): void
    {
        if ($this->token === '') {
            return;
        }

        $this->setCookie('token', $this->token, strtotime('+1 year'));

        $this->database->execute(
            'UPDATE sessions SET tz = :tz, updated = :updated WHERE token = :token LIMIT 1',
            [
                'tz' => $this->timezone,
                'updated' => $this->clock->now(),
                'token' => $this->token,
            ]
        );
    }

    private function clearTokenCookie(): void
    {
        $this->setCookie('token', '', 1);
    }

    private function setCookie(string $name, string $value, int $expires): void
    {
        setcookie($name, $value, $expires, '/', '', $this->config->isSecureCookies(), true);
        if ($expires < time()) {
            unset($_COOKIE[$name]);
        } else {
            $_COOKIE[$name] = $value;
        }
    }
}