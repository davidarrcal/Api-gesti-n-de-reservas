<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    public static function connect(): PDO
    {
        // Railway exposes MYSQL* variables; local development uses the DB* names.
        $host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: '127.0.0.1');
        $port = getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: '3306');
        $name = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'gestion_reservas');
        $user = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root');
        $password = getenv('DB_PASSWORD') ?: (getenv('MYSQLPASSWORD') ?: '');

        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return $pdo;
    }
}
