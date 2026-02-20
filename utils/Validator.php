<?php

namespace Utils;

/**
 * Validación de entradas en backend (tipo, rango, formato).
 * Usar siempre en servidor; la validación en frontend es complementaria.
 */
class Validator
{
    public static function string(?string $value, int $minLength = 0, ?int $maxLength = null, ?string $pattern = null): bool
    {
        $value = $value ?? '';
        $len = mb_strlen($value);
        if ($len < $minLength) {
            return false;
        }
        if ($maxLength !== null && $len > $maxLength) {
            return false;
        }
        if ($pattern !== null && !preg_match($pattern, $value)) {
            return false;
        }
        return true;
    }

    public static function int($value, ?int $min = null, ?int $max = null): bool
    {
        $v = filter_var($value, FILTER_VALIDATE_INT);
        if ($v === false) {
            return false;
        }
        if ($min !== null && $v < $min) {
            return false;
        }
        if ($max !== null && $v > $max) {
            return false;
        }
        return true;
    }

    public static function float($value, ?float $min = null, ?float $max = null): bool
    {
        $v = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($v === false) {
            return false;
        }
        if ($min !== null && $v < $min) {
            return false;
        }
        if ($max !== null && $v > $max) {
            return false;
        }
        return true;
    }

    public static function email(?string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /** Formato PIN 6 dígitos (validación de formato; fortaleza en Security::isWeakPin) */
    public static function pinFormat(?string $value): bool
    {
        return $value !== null && preg_match('/^\d{6}$/', $value) === 1;
    }

    /** Tipo de sangre permitido (A+, A-, B+, B-, AB+, AB-, O+, O-) */
    public static function bloodType(?string $value): bool
    {
        $allowed = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        return $value !== null && in_array($value, $allowed, true);
    }

    /** URL válida para base pública (http/https, longitud razonable) */
    public static function url(?string $value, int $maxLength = 500): bool
    {
        if ($value === null || $value === '' || mb_strlen($value) > $maxLength) {
            return false;
        }
        return filter_var($value, FILTER_VALIDATE_URL) !== false
            && (str_starts_with($value, 'http://') || str_starts_with($value, 'https://'));
    }
}
