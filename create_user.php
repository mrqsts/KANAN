<?php

require __DIR__ . '/autoload.php';

use Config\Database;
use Utils\Security;

echo "=== Crear usuario inicial para Kanan Web ===\n";

$nombre = readline("Nombre de usuario: ");
$pin = readline("PIN (6 dígitos): ");

if (Security::isWeakPin($pin)) {
    echo "PIN inválido o demasiado débil.\n";
    exit(1);
}

try {
    $salt = Security::generateSalt(16);
    $hash = Security::hashPin($pin, $salt);

    $pdo = Database::getConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO users (nombre, hash_pin, salt) VALUES (:nombre, :hash_pin, :salt)'
    );
    $stmt->bindValue(':nombre', $nombre);
    $stmt->bindValue(':hash_pin', $hash, \PDO::PARAM_STR);
    $stmt->bindValue(':salt', $salt, \PDO::PARAM_STR);
    $stmt->execute();

    echo "Usuario creado correctamente.\n";
} catch (\Throwable $e) {
    echo "Error creando usuario: " . $e->getMessage() . "\n";
    exit(1);
}

