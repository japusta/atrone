<?php

namespace Modules\Users\Application;

use Modules\Users\Domain\UserRepository;

class UserService
{
    private const DEFAULT_ACCESS = 1;
    private const DEFAULT_PHONE_CODE = '1111';
    private const PAGE_SIZE = 20;

    private UserRepository $repository;

    public function __construct(?UserRepository $repository = null)
    {
        $this->repository = $repository ?? new UserRepository();
    }

    public function getAuthInfo(array $criteria): array
    {
        $userId = isset($criteria['user_id']) && is_numeric($criteria['user_id']) ? (int) $criteria['user_id'] : 0;
        $phone = isset($criteria['phone']) ? $this->normalizePhone($criteria['phone']) : '';

        return $this->repository->findAuthInfo($userId, $phone);
    }

    public function getOwnersByPlot(string $number): array
    {
        $owners = $this->repository->findOwnersByPlot($number);

        return array_map(function (array $owner) {
            $owner['phone_str'] = phone_formatting($owner['phone']);
            return $owner;
        }, $owners);
    }

    public function getPaginatedList(string $search, int $offset): array
    {
        $normalizedOffset = max(0, $offset);
        [$items, $total] = $this->repository->search($search, $normalizedOffset, self::PAGE_SIZE);

        $items = array_map(function (array $item) {
            $item['phone_str'] = phone_formatting($item['phone']);
            $item['last_login'] = $item['last_login'] ? date('Y/m/d', $item['last_login']) : '';
            return $item;
        }, $items);

        $paginatorHtml = '';
        $path = 'users?';
        $search = trim($search);
        if ($search !== '') {
            $path .= 'search=' . urlencode($search) . '&';
        }

        paginator($total, $normalizedOffset, self::PAGE_SIZE, $path, $paginatorHtml);

        return [
            'items' => $items,
            'paginator' => $paginatorHtml,
            'offset' => $normalizedOffset,
            'search' => $search,
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
        $firstName = isset($payload['first_name']) ? trim($payload['first_name']) : '';
        $lastName = isset($payload['last_name']) ? trim($payload['last_name']) : '';
        $phone = isset($payload['phone']) ? $this->normalizePhone($payload['phone']) : '';
        $email = isset($payload['email']) ? strtolower(trim($payload['email'])) : '';
        $plots = isset($payload['plots']) ? trim($payload['plots']) : '';

        if (!$firstName || !$lastName || !$phone || !$email) {
            return ['error_msg' => 'Please fill in all required fields.'];
        }

        $data = [
            'first_name' => flt_input($firstName),
            'last_name' => flt_input($lastName),
            'phone' => flt_input($phone),
            'email' => flt_input($email),
            'plot_id' => flt_input($plots),
            'access' => self::DEFAULT_ACCESS,
            'phone_code' => self::DEFAULT_PHONE_CODE,
        ];

        if ($userId) {
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
        if ($userId) {
            $this->repository->delete($userId);
        }

        $offset = isset($payload['offset']) && is_numeric($payload['offset']) ? (int) $payload['offset'] : 0;
        $search = isset($payload['search']) ? (string) $payload['search'] : '';

        return $this->getPaginatedList($search, $offset);
    }

    public function findUserByPhone(string $phone): ?array
    {
        $normalized = $this->normalizePhone($phone);
        if ($normalized === '') {
            return null;
        }

        return $this->repository->findByPhone($normalized);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('~\D+~', '', $phone);
    }
}