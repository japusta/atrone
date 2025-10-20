<?php

namespace App\Core\Support;

final class PhoneFormatter
{
    public function format(string $phone): string
    {
        if (preg_match('~^[78]\d{10}$~', $phone)) {
            return preg_replace('~^([78])(\d{3})(\d{3})(\d{2})(\d{2})$~', '+$1 ($2) $3-$4-$5', $phone) ?? $phone;
        }

        return $phone;
    }
}
