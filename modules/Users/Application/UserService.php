<?php

namespace Modules\Users\Application;

use App\Core\Support\Paginator;
use App\Core\Support\PhoneFormatter;
use App\Core\Support\Sanitizer;
use Modules\Users\Domain\UserRepository;

final class UserService
{
    private const DEFAULT_ACCESS = 1;
    private const DEFAULT_PHONE_CODE = '1111';
    private const PAGE_SIZE = 20;

    private UserRepository $repository;
    private Sanitizer $sanitizer;
    private PhoneFormatter $phoneFormatter;
    private Paginator $paginator;

    public function __construct(UserRepository $repository, Sanitizer $sanitizer, PhoneFormatter $phoneFormatter, Paginator $paginator)
    {
        $this->repository = $repository;
        $this->sanitizer = $sanitizer;
        $this->phoneFormatter = $phoneFormatter;
        $this->paginator = $paginator;
    }

    public function getAuthInfo(array $criteria): array
    {
        $userId = isset($criteria['user_id']) && is_numeric($criteria['user_id']) ? (int) $criteria['user_id'] : null;
        $phone = isset($criteria['phone']) ? $this->sanitizer->sanitizeDigits((string) $criteria['phone']) : null;

        return $this->repository->findAuthInfo($userId, $phone);
    }

    public function getOwnersByPlot(string $number): array
    {
        $owners = $this->repository->findOwnersByPlot($number);

        return array_map(function (array $owner) {
            $owner['phone_str'] = $this->phoneFormatter->format($owner['phone']);
            return $owner;
        }, $owners);
    }

    public function getPaginatedList(string $search, int $offset): array
    {
        $normalizedOffset = max(0, $offset);
        [$items, $total] = $this->repository->search($search, $normalizedOffset, self::PAGE_SIZE);

        $items = array_map(function (array $item) {
            $item['phone_str'] = $this->phoneFormatter->format($item['phone']);
            $item['last_login'] = $item['last_login'] ? date('Y/m/d', (int) $item['last_login']) : '';
            return $item;
        }, $items);

        $searchQuery = trim($search);
        $path = 'users?';
        if ($searchQuery !== '') {
            $path .= 'search='.urlencode($searchQuery).'&';
        }

        $paginator = $this->paginator->render($total, $normalizedOffset, self::PAGE_SIZE, $path);

        return [
            'items' => $items,
            'paginator' => $paginator,
            'offset' => $normalizedOffset,
            'search' => $searchQuery,
        ];
    }

    public function getEditData(int $userId): array
    {
        $info = $userId ? $this->repository->findForEdit($userId) : null;

        if ($info) {
            return $info;
        }

        return [
            'id' => 0,
            'plot_id' => '',
            'first_name' => '',
            'last_name' => '',
            'phone' => '',
            'email' => '',
        ];
    }

    public function saveUser(array $payload): array
    {
        $userId = isset($payload['user_id']) && is_numeric($payload['user_id']) ? (int) $payload['user_id'] : 0;
        $firstName = $this->sanitizer->sanitize($payload['first_name'] ?? '');
        $lastName = $this->sanitizer->sanitize($payload['last_name'] ?? '');
        $phone = $this->sanitizer->sanitizeDigits($payload['phone'] ?? '');
        $email = strtolower($this->sanitizer->sanitize($payload['email'] ?? ''));
        $plots = $this->normalizePlots($payload['plots'] ?? '');

        if ($firstName === '' || $lastName === '' || $phone === '' || $email === '') {
            return ['error_msg' => 'Please fill in all required fields.'];
        }

        $data = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'email' => $email,
            'plot_id' => $plots,
            'access' => self::DEFAULT_ACCESS,
            'phone_code' => self::DEFAULT_PHONE_CODE,
        ];

        if ($userId > 0) {
            $this->repository->update($userId, $data);
        } else {
            $this->repository->insert($data);
        }

        $offset = isset($payload['offset']) && is_numeric($payload['offset']) ? (int) $payload['offset'] : 0;
        $search = isset($payload['search']) ? (string) $payload['search'] : '';

        return $this->getPaginatedList($search, $offset);
    }

    public function deleteUser(array $payload): array
    {
        $userId = isset($payload['user_id']) && is_numeric($payload['user_id']) ? (int) $payload['user_id'] : 0;
        if ($userId > 0) {
            $this->repository->delete($userId);
        }

        $offset = isset($payload['offset']) && is_numeric($payload['offset']) ? (int) $payload['offset'] : 0;
        $search = isset($payload['search']) ? (string) $payload['search'] : '';

        return $this->getPaginatedList($search, $offset);
    }

    public function findUserByPhone(string $phone): ?array
    {
        $normalized = $this->sanitizer->sanitizeDigits($phone);
        if ($normalized === '') {
            return null;
        }

        return $this->repository->findByPhone($normalized);
    }

    private function normalizePlots(string $plots): string
    {
        $parts = array_map('trim', explode(',', $plots));
        $parts = array_map(static fn (string $value) => preg_replace('~\D+~', '', $value) ?? '', $parts);
        $parts = array_filter($parts, static fn (string $value) => $value !== '');

        return implode(',', $parts);
    }
}
