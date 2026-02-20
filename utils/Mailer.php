<?php

namespace Utils;

/**
 * Envío de correos (MFA).
 * - Si existe config/mail.php con SMTP configurado, usa PHPMailer para enviar por SMTP.
 * - Si no, usa mail() de PHP (en local suele no llegar).
 * - El código siempre se escribe en logs/mfa_codes.log para poder probar aunque el correo falle.
 */
class Mailer
{
    private static bool $logOnly = false;

    public static function setLogOnly(bool $value): void
    {
        self::$logOnly = $value;
    }

    public static function sendMfaCode(string $toEmail, string $code): bool
    {
        self::logMfaCode($toEmail, $code);

        if (self::$logOnly) {
            return true;
        }

        $configFile = __DIR__ . '/../config/mail.php';
        if (file_exists($configFile)) {
            $config = include $configFile;
            if (!empty($config['enabled']) && !empty($config['smtp_host']) && !empty($config['smtp_user'])) {
                return self::sendViaSmtp($toEmail, $code, $config);
            }
        }

        return self::sendViaMail($toEmail, $code);
    }

    private static function sendViaSmtp(string $toEmail, string $code, array $config): bool
    {
        if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            return self::sendViaMail($toEmail, $code);
        }

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $config['smtp_host'];
            $mail->Port       = (int) ($config['smtp_port'] ?? 587);
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['smtp_user'];
            $mail->Password   = $config['smtp_pass'];
            $mail->SMTPSecure = $config['smtp_secure'] ?? 'tls';
            $mail->CharSet    = 'UTF-8';

            $from = $config['from_email'] ?? $config['smtp_user'];
            $name = $config['from_name'] ?? 'Kanan Web';
            $mail->setFrom($from, $name);
            $mail->addAddress($toEmail);
            $mail->Subject = 'Tu código de verificación - Kanan Web';
            $mail->Body    = "Tu código de verificación es: {$code}\n\nVálido durante 10 minutos.\n\nSi no solicitaste este código, ignora este mensaje.";

            $mail->send();
            return true;
        } catch (\Throwable $e) {
            error_log('Mailer SMTP: ' . $e->getMessage());
            return false;
        }
    }

    private static function sendViaMail(string $toEmail, string $code): bool
    {
        $subject = 'Tu código de verificación - Kanan Web';
        $body = "Tu código de verificación es: {$code}\n\nVálido durante 10 minutos.\n\nSi no solicitaste este código, ignora este mensaje.";
        $headers = [
            'From: Kanan Web <noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '>',
            'Reply-To: noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
            'X-Mailer: PHP/' . PHP_VERSION,
            'Content-Type: text/plain; charset=UTF-8',
        ];

        return @mail($toEmail, $subject, $body, implode("\r\n", $headers));
    }

    private static function logMfaCode(string $toEmail, string $code): void
    {
        $logFile = __DIR__ . '/../logs/mfa_codes.log';
        $line = '[' . date('Y-m-d H:i:s') . "] MFA para {$toEmail}: {$code}\n";
        @file_put_contents($logFile, $line, FILE_APPEND);
    }
}
