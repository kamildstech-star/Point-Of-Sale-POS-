<?php

namespace App\Domain\Operations;

use Illuminate\Validation\ValidationException;

/** All monetary/quantity arithmetic is decimal. No PHP float calculations. */
final class Decimal
{
    public static function value(mixed $value, bool $signed = false): string
    {
        if (! is_string($value) || ! preg_match($signed ? '/^-?\d{1,12}(?:\.\d{1,4})?$/D' : '/^\d{1,12}(?:\.\d{1,4})?$/D', $value)) {
            self::invalid('Use a decimal string with at most 12 integer digits and four decimal places.');
        }

        return bcadd($value, '0', 4);
    }

    public static function add(string $a, string $b): string
    {
        return bcadd($a, $b, 4);
    }

    public static function sub(string $a, string $b): string
    {
        return bcsub($a, $b, 4);
    }

    public static function mul(string $a, string $b): string
    {
        return bcmul($a, $b, 8);
    }

    public static function cmp(string $a, string $b): int
    {
        return bccomp($a, $b, 4);
    }

    public static function round(string $value, int $scale): string
    {
        $half = $scale === 0 ? '0.5' : '0.'.str_repeat('0', $scale).'5';

        return bcadd($value, str_starts_with($value, '-') ? '-'.$half : $half, $scale);
    }

    public static function money(string $value, int $scale): string
    {
        $result = self::round($value, $scale);
        if (bccomp($result, '999999999999999', 4) > 0 || bccomp($result, '0', 4) < 0) {
            self::invalid('Amount is outside the supported range.');
        }

        return $result;
    }

    public static function invalid(string $message): never
    {
        throw ValidationException::withMessages(['operation' => [$message]]);
    }
}
