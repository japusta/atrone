<?php

namespace App\Application\Auth;

use App\Core\Config\Config;
use App\Core\Database\DatabaseConnection;
use App\Core\Exception\ApiException;
use App\Core\Session\SessionManager;
use App\Core\Support\Clock;
use App\Core\Support\Sanitizer;
use Modules\Users\Application\UserService;

final class AuthService
{
    private UserService $userService;
    private SessionManager $sessionManager;
    private DatabaseConnection $database;
    private Sanitizer $sanitizer;
    private Config $config;
    private Clock $clock;

    public function __construct(
        UserService $userService,
        SessionManager $sessionManager,
        DatabaseConnection $database,
        Sanitizer $sanitizer,
        Config $config,
        Clock $clock
    ) {
        $this->userService = $userService;
        $this->sessionManager = $sessionManager;
        $this->database = $database;
        $this->sanitizer = $sanitizer;
        $this->config = $config;
        $this->clock = $clock;
    }

    public function requestCode(string $phone): array
    {
        $cleanPhone = $this->sanitizer->sanitizeDigits($phone);
        if ($cleanPhone === '') {
            throw new ApiException(1003, 'One of the parameters was missing or was passed in the wrong format.', ['phone' => 'empty field']);
        }

        $authInfo = $this->userService->getAuthInfo(['phone' => $cleanPhone]);
        if (($authInfo['access'] ?? 0) !== 1) {
            throw new ApiException(1004, 'User with this phone is not found.', ['phone' => 'incorrect phone']);
        }

        $this->database->execute(
            'UPDATE users SET phone_attempts_sms = phone_attempts_sms + 1, phone_attempts_code = 0 WHERE user_id = :user_id LIMIT 1',
            ['user_id' => $authInfo['id']]
        );

        return ['phone' => $cleanPhone];
    }

    public function confirmCode(string $phone, string $code): array
    {
        $cleanPhone = $this->sanitizer->sanitizeDigits($phone);
        $codeDigits = $this->sanitizer->sanitizeDigits($code);

        if ($cleanPhone === '' && $codeDigits === '') {
            throw new ApiException(1003, 'One of the parameters was missing or was passed in the wrong format.', [
                'phone' => 'empty field',
                'code' => 'empty field',
            ]);
        }

        if ($cleanPhone === '') {
            throw new ApiException(1003, 'One of the parameters was missing or was passed in the wrong format.', ['phone' => 'empty field']);
        }

        if ($codeDigits === '') {
            throw new ApiException(1003, 'One of the parameters was missing or was passed in the wrong format.', ['code' => 'empty field']);
        }

        $user = $this->userService->findUserByPhone($cleanPhone);
        if ($user === null) {
            throw new ApiException(1004, 'User with this phone is not found', ['phone' => 'user is not registered']);
        }

        $userId = (int) $user['user_id'];
        $lastLogin = isset($user['last_login']) ? (int) $user['last_login'] : 0;
        $attempts = isset($user['phone_attempts_code']) ? (int) $user['phone_attempts_code'] : 0;

        if ($lastLogin > 0 && ($this->clock->now() - $lastLogin) > 3600) {
            $attempts = 0;
            $this->database->execute(
                'UPDATE users SET phone_attempts_code = 0 WHERE user_id = :user_id LIMIT 1',
                ['user_id' => $userId]
            );
        }

        $remaining = $this->config->getLoginAttempts() - $attempts;
        if ($remaining <= 0) {
            throw new ApiException(1005, 'Number of invalid code attempts has been exceeded for this user, please try again later.', ['code' => 'exceeded error limit, please try later']);
        }

        if ((string) $user['phone_code'] !== $codeDigits) {
            $this->database->execute(
                'UPDATE users SET phone_attempts_code = phone_attempts_code + 1, last_login = :last_login WHERE user_id = :user_id LIMIT 1',
                [
                    'last_login' => $this->clock->now(),
                    'user_id' => $userId,
                ]
            );

            throw new ApiException(1005, 'Invalid phone code, number of remaining attempts is '.$remaining.'.', ['code' => 'invalid phone code']);
        }

        return $this->sessionManager->createSession($userId, (int) $user['access']);
    }
}
