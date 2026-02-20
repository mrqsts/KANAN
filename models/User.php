<?php

namespace Models;

use Config\Database;
use PDO;
use Utils\Security;
use Utils\Logger;

class User
{
    public int $id;
    public string $nombre;
    public ?string $email;
    public string $hash_pin;
    public string $salt;
    public int $failed_attempts;
    public ?string $locked_until;

    public static function findByName(string $nombre): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE nombre = :nombre LIMIT 1');
        $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $user = new self();
        $user->id = (int)$row['id'];
        $user->nombre = $row['nombre'];
        $user->email = isset($row['email']) && $row['email'] !== '' ? $row['email'] : null;
        $user->hash_pin = $row['hash_pin'];
        $user->salt = $row['salt'];
        $user->failed_attempts = (int)$row['failed_attempts'];
        $user->locked_until = $row['locked_until'];

        return $user;
    }

    public static function findById(int $id): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $user = new self();
        $user->id = (int)$row['id'];
        $user->nombre = $row['nombre'];
        $user->email = isset($row['email']) && $row['email'] !== '' ? $row['email'] : null;
        $user->hash_pin = $row['hash_pin'];
        $user->salt = $row['salt'];
        $user->failed_attempts = (int)$row['failed_attempts'];
        $user->locked_until = $row['locked_until'];

        return $user;
    }

    public static function create(string $nombre, string $pin, ?string $email = null): void
    {
        $pdo = Database::getConnection();
        $salt = Security::generateSalt(32);
        $hash = Security::hashPin($pin, $salt);

        $stmt = $pdo->prepare(
            'INSERT INTO users (nombre, email, hash_pin, salt) VALUES (:nombre, :email, :hash_pin, :salt)'
        );
        $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email ?: null, $email ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':hash_pin', $hash, PDO::PARAM_STR);
        $stmt->bindValue(':salt', $salt, PDO::PARAM_STR);
        $stmt->execute();

        $userId = (int) $pdo->lastInsertId();
        Logger::log($userId, 'Registro de nueva cuenta');
    }

    public static function updateEmail(int $userId, ?string $email): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET email = :email WHERE id = :id');
        $stmt->bindValue(':email', $email ?: null, $email ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        Logger::log($userId, 'Actualización de correo MFA');
    }

    /** Cambia el PIN del usuario. Devuelve true si el PIN actual es correcto y el nuevo se guardó. */
    public static function updatePin(int $userId, string $currentPin, string $newPin): bool
    {
        $user = self::findById($userId);
        if (!$user || !Security::verifyPin($currentPin, $user->salt, $user->hash_pin)) {
            return false;
        }

        $salt = Security::generateSalt(32);
        $hash = Security::hashPin($newPin, $salt);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET hash_pin = :hash_pin, salt = :salt WHERE id = :id');
        $stmt->bindValue(':hash_pin', $hash, PDO::PARAM_STR);
        $stmt->bindValue(':salt', $salt, PDO::PARAM_STR);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        Logger::log($userId, 'Cambio de PIN');
        return true;
    }

    /** Genera un código MFA de 6 dígitos, lo guarda y lo devuelve. Válido 10 minutos. */
    public static function createMfaCode(int $userId): string
    {
        $pdo = Database::getConnection();
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = (new \DateTime('+10 minutes'))->format('Y-m-d H:i:s');

        $stmt = $pdo->prepare(
            'INSERT INTO mfa_codes (user_id, code, expires_at) VALUES (:user_id, :code, :expires_at)'
        );
        $stmt->execute([
            ':user_id'   => $userId,
            ':code'      => $code,
            ':expires_at'=> $expiresAt,
        ]);

        return $code;
    }

    /** Valida el código y lo invalida (un solo uso). Devuelve true si es válido. */
    public static function consumeMfaCode(int $userId, string $code): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT id FROM mfa_codes WHERE user_id = :user_id AND code = :code AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([':user_id' => $userId, ':code' => $code]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }
        $stmt = $pdo->prepare('DELETE FROM mfa_codes WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
        return true;
    }

    public function isLocked(): bool
    {
        if ($this->locked_until === null) {
            return false;
        }
        return (new \DateTime($this->locked_until)) > new \DateTime();
    }

    public function registerFailedAttempt(): void
    {
        $this->failed_attempts++;
        $pdo = Database::getConnection();

        if ($this->failed_attempts >= 5) {
            $lockMinutes = 15;
            $lockedUntil = (new \DateTime())->modify("+{$lockMinutes} minutes")->format('Y-m-d H:i:s');

            $stmt = $pdo->prepare(
                'UPDATE users SET failed_attempts = :fa, locked_until = :lu WHERE id = :id'
            );
            $stmt->execute([
                ':fa' => $this->failed_attempts,
                ':lu' => $lockedUntil,
                ':id' => $this->id,
            ]);

            Logger::log($this->id, 'Cuenta bloqueada por intentos fallidos');
        } else {
            $stmt = $pdo->prepare(
                'UPDATE users SET failed_attempts = :fa WHERE id = :id'
            );
            $stmt->execute([
                ':fa' => $this->failed_attempts,
                ':id' => $this->id,
            ]);
        }
    }

    public function resetFailedAttempts(): void
    {
        $this->failed_attempts = 0;
        $this->locked_until = null;

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = :id'
        );
        $stmt->execute([':id' => $this->id]);
    }

    public static function verifyCredentials(string $nombre, string $pin): ?self
    {
        $user = self::findByName($nombre);
        if (!$user) {
            Logger::log(null, "Intento de login fallido (usuario inexistente: {$nombre})");
            return null;
        }

        if ($user->isLocked()) {
            Logger::log($user->id, 'Intento de login en cuenta bloqueada');
            return null;
        }

        if (!Security::verifyPin($pin, $user->salt, $user->hash_pin)) {
            $user->registerFailedAttempt();
            Logger::log($user->id, 'Intento de login fallido (PIN incorrecto)');
            return null;
        }

        $user->resetFailedAttempts();
        Logger::log($user->id, 'Login exitoso');

        return $user;
    }
}

