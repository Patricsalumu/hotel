<?php

namespace App\Support;

class Money
{
    public static function format(float|int|string|null $amount, ?string $currency = 'FC', int $decimals = 2): string
    {
        $numeric = (float) ($amount ?? 0);
        $currencyCode = trim((string) ($currency ?: 'FC'));

        return number_format($numeric, $decimals, ',', ' ') . ' ' . $currencyCode;
    }
}
