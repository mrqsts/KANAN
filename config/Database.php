<?php

namespace Config;

class Database
{
    private static $host = 'sql104.infinityfree.com';
    private static $db_name = 'if0_41481649_kanan_web';
    private static $username = 'if0_41481649';
    private static $password = 'KANANH34AL7H';
    private static $conn;

    public static function getConnection()
    {
        if (self::$conn === null) {
            try {
                self::$conn = new \PDO(
                    "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=utf8mb4",
                    self::$username,
                    self::$password
                );
                self::$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                self::$conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            } catch (\PDOException $exception) {
                die("Error de conexión: " . $exception->getMessage());
            }
        }
        return self::$conn;
    }
}
