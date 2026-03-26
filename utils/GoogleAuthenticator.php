<?php

namespace Utils;

/**
 * Utilidad nativa para Google Authenticator (TOTP RFC 6238)
 * No requiere librerías externas.
 */
class GoogleAuthenticator
{
    private const ALLOWED_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** Genera un secreto aleatorio de 16 caracteres Base32 */
    public static function createSecret(int $length = 16): string
    {
        $secret = '';
        $bytes = random_bytes($length);
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::ALLOWED_CHARS[ord($bytes[$i]) & 31];
        }
        return $secret;
    }

    /** Verifica si el código de 6 dígitos es válido para el secreto dado */
    public static function verifyCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        $currentTimeSlice = floor(time() / 30);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = self::getCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /** Genera la URL para el código QR (formato otpauth://) */
    public static function getQrCodeUrl(string $label, string $secret, string $issuer): string
    {
        $otpauth = "otpauth://totp/" . rawurlencode($issuer) . ":" . rawurlencode($label) . "?secret=" . $secret . "&issuer=" . rawurlencode($issuer);
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($otpauth);
    }

    private static function getCode(string $secret, int $timeSlice): string
    {
        $secretKey = self::base32Decode($secret);

        // Pack time slice into binary string
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        
        // HMAC-SHA1
        $hmac = hash_hmac('sha1', $time, $secretKey, true);
        
        // Dynamic truncation
        $offset = ord($hmac[19]) & 0xf;
        $hashPart = substr($hmac, $offset, 4);
        
        $value = unpack('N', $hashPart)[1];
        $value = $value & 0x7fffffff;
        
        $modulo = pow(10, 6);
        return str_pad((string)($value % $modulo), 6, '0', STR_PAD_LEFT);
    }

    private static function base32Decode(string $secret): string
    {
        if (empty($secret)) return '';
        
        $base32chars = self::ALLOWED_CHARS;
        $base32charsFlipped = array_flip(str_split($base32chars));
        
        $secret = strtoupper($secret);
        $secret = str_replace('=', '', $secret);
        
        $binaryString = '';
        foreach (str_split($secret) as $char) {
            if (!isset($base32charsFlipped[$char])) continue;
            $binaryString .= str_pad(decbin($base32charsFlipped[$char]), 5, '0', STR_PAD_LEFT);
        }
        
        $binaries = str_split($binaryString, 8);
        $result = '';
        foreach ($binaries as $binary) {
            if (strlen($binary) < 8) continue;
            $result .= chr(bindec($binary));
        }
        
        return $result;
    }
}
