<?php

namespace Utils;

class Security
{
    public static function generateSalt(int $length = 32): string
    {
        return random_bytes($length);
    }

    public static function hashPin(string $pin, string $salt): string
    {
        $options = [
            'memory_cost' => 1 << 12,
            'time_cost'   => 3,
            'threads'     => 1,
            'salt'        => $salt,
        ];

        $hash = password_hash($pin . $salt, PASSWORD_ARGON2ID, $options);
        if ($hash === false) {
            throw new \RuntimeException('No se pudo generar el hash del PIN.');
        }
        return $hash;
    }

    public static function verifyPin(string $pin, string $salt, string $hash): bool
    {
        return password_verify($pin . $salt, $hash);
    }

    public static function isWeakPin(string $pin): bool
    {
        if (!preg_match('/^\d{6}$/', $pin)) {
            return true;
        }

        $blacklist = [
            '123456', '000000', '111111', '222222', '333333',
            '444444', '555555', '666666', '777777', '888888',
            '999999', '654321',
        ];

        if (in_array($pin, $blacklist, true)) {
            return true;
        }

        $asc  = '0123456789';
        $desc = '9876543210';
        if (str_contains($asc, $pin) || str_contains($desc, $pin)) {
            return true;
        }

        return false;
    }

    public static function sanitizeString(?string $value): string
    {
        $value = $value ?? '';
        $value = trim($value);
        // Evitar FILTER_SANITIZE_STRING (deprecado)
        $value = strip_tags($value);
        return $value;
    }

    public static function sanitizeInt(?string $value): ?int
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        return $value === false ? null : $value;
    }

    public static function sanitizeFloat(?string $value): ?float
    {
        $value = filter_var($value, FILTER_VALIDATE_FLOAT);
        return $value === false ? null : $value;
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    public static function getCsrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateToken(16);
        }

        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function getClientIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return substr($ip, 0, 45);
    }
}

