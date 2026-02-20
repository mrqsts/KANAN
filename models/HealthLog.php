<?php

namespace Models;

use Config\Database;
use PDO;

class HealthLog
{
    public static function create(
        int $userId,
        ?float $temperatura,
        ?string $presion,
        ?float $peso,
        ?int $nivelEnergia,
        ?string $sintomas
    ): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO health_logs (user_id, temperatura, presion, peso, nivel_energia, sintomas)
             VALUES (:user_id, :temperatura, :presion, :peso, :nivel_energia, :sintomas)'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':temperatura', $temperatura, $temperatura === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':presion', $presion, $presion === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':peso', $peso, $peso === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':nivel_energia', $nivelEnergia, $nivelEnergia === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':sintomas', $sintomas, $sintomas === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
    }

    public static function getLastWeek(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM health_logs
             WHERE user_id = :user_id AND fecha >= (NOW() - INTERVAL 7 DAY)
             ORDER BY fecha DESC'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getRecent(int $userId, int $limit = 10): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM health_logs WHERE user_id = :user_id ORDER BY fecha DESC LIMIT :limit'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

