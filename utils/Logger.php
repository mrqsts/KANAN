<?php

namespace Utils;

use Config\Database;
use PDO;

class Logger
{
    public static function log(?int $userId, string $action): void
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                'INSERT INTO audit_logs (user_id, accion, ip) VALUES (:user_id, :accion, :ip)'
            );
            if ($userId === null) {
                $stmt->bindValue(':user_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            }
            $stmt->bindValue(':accion', $action, PDO::PARAM_STR);
            $stmt->bindValue(':ip', Security::getClientIp(), PDO::PARAM_STR);
            $stmt->execute();
        } catch (\Throwable $e) {
            $logFile = __DIR__ . '/../logs/app.log';
            @file_put_contents(
                $logFile,
                '[' . date('Y-m-d H:i:s') . "] Audit log error: " . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );
        }
    }
}

