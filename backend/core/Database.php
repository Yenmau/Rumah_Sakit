<?php
/**
 * Database Singleton & Connection Manager
 */

namespace Backend\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static string $driverUsed = 'mysql';

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    public static function getDriver(): string
    {
        return self::$driverUsed;
    }

    private static function connect(): void
    {
        $config = require __DIR__ . '/../config/database.php';
        $preferredDriver = $config['driver'] ?? 'mysql';

        if ($preferredDriver === 'mysql') {
            try {
                $my = $config['mysql'];
                // Connect without db first to ensure database exists if privileges allow
                $dsn = "mysql:host={$my['host']};port={$my['port']};charset={$my['charset']}";
                $pdo = new PDO($dsn, $my['username'], $my['password'], $my['options']);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$my['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `{$my['database']}`");
                
                self::$instance = $pdo;
                self::$driverUsed = 'mysql';
                return;
            } catch (PDOException $e) {
                if (!empty($config['auto_fallback_sqlite'])) {
                    // Fall back to SQLite
                    self::connectSqlite($config['sqlite']);
                    return;
                }
                throw $e;
            }
        }

        self::connectSqlite($config['sqlite']);
    }

    private static function connectSqlite(array $sqliteConfig): void
    {
        $dir = dirname($sqliteConfig['path']);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $dsn = "sqlite:" . $sqliteConfig['path'];
        $pdo = new PDO($dsn, null, null, $sqliteConfig['options']);
        $pdo->exec("PRAGMA foreign_keys = ON;");
        
        if (method_exists($pdo, 'sqliteCreateFunction')) {
            $pdo->sqliteCreateFunction('CONCAT', function (...$args) {
                return implode('', $args);
            });
            $pdo->sqliteCreateFunction('NOW', function () {
                return date('Y-m-d H:i:s');
            });
            $pdo->sqliteCreateFunction('CURDATE', function () {
                return date('Y-m-d');
            });
        }
        
        self::$instance = $pdo;
        self::$driverUsed = 'sqlite';
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }
}
