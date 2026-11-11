<?php
/**
 * Database Configuration
 * Database: medical_platform (MySQL 8.0.16+ InnoDB, utf8mb4)
 * Supports MySQL and SQLite (portable fallback).
 */

return [
    // Default driver: 'mysql' or 'sqlite'
    'driver' => getenv('DB_DRIVER') ?: 'mysql',

    'mysql' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_NAME') ?: 'medical_platform',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    ],

    'sqlite' => [
        'path' => __DIR__ . '/../storage/medical_platform.sqlite',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],

    // If MySQL connection fails, auto-fallback to SQLite so the app never crashes
    'auto_fallback_sqlite' => true,
];
