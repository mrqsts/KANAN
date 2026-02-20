<?php

namespace Models;

use Config\Database;
use PDO;
use Utils\Logger;

class Medication
{
    public static function allByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM medications WHERE user_id = :user_id ORDER BY created_at DESC'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function create(int $userId, string $nombre, string $dosis, string $horario): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO medications (user_id, nombre_medicamento, dosis, horario)
             VALUES (:user_id, :nombre, :dosis, :horario)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':nombre'  => $nombre,
            ':dosis'   => $dosis,
            ':horario' => $horario,
        ]);

        Logger::log($userId, "Alta de medicamento: {$nombre}");
    }

    public static function update(int $id, int $userId, string $nombre, string $dosis, string $horario): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE medications
             SET nombre_medicamento = :nombre, dosis = :dosis, horario = :horario, updated_at = NOW()
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            ':id'      => $id,
            ':user_id' => $userId,
            ':nombre'  => $nombre,
            ':dosis'   => $dosis,
            ':horario' => $horario,
        ]);

        Logger::log($userId, "Cambio de dosis/horario: {$nombre}");
    }

    public static function delete(int $id, int $userId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'DELETE FROM medications WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            ':id'      => $id,
            ':user_id' => $userId,
        ]);

        Logger::log($userId, "Eliminación de medicamento ID {$id}");
    }
}

