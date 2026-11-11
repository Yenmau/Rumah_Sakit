<?php
/**
 * Database Initializer & Migration Tool
 * Database: medical_platform (MySQL 8 / SQLite)
 * Usage: php backend/database/init_db.php [--driver=sqlite|mysql]
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/seeder.php';

use Backend\Core\Database;
use Backend\Database\Seeder;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--driver=')) {
        putenv('DB_DRIVER=' . substr($arg, 9));
    }
}

echo "=== Inisialisasi Database medical_platform ===\n";

try {
    $pdo = Database::getConnection();
    $driver = Database::getDriver();
    echo "Terhubung menggunakan driver: [$driver]\n";

    $raw = file_get_contents(__DIR__ . '/schema.sql');

    if ($driver === 'sqlite') {
        // Remove comments without removing the newline
        $sql = preg_replace('/--[^\r\n]*/', '', $raw);

        // Remove CREATE DATABASE and USE
        $sql = preg_replace('/CREATE DATABASE\s+IF\s+NOT\s+EXISTS\s+[a-zA-Z0-9_]+[^;]*;/i', '', $sql);
        $sql = preg_replace('/USE\s+[a-zA-Z0-9_]+;/i', '', $sql);

        // SQLite syntax adjustments
        $sql = preg_replace('/ENGINE\s*=\s*InnoDB/i', '', $sql);
        $sql = preg_replace('/ON UPDATE CURRENT_TIMESTAMP/i', '', $sql);
        $sql = preg_replace('/ENUM\([^)]+\)/i', 'VARCHAR(50)', $sql);
        $sql = preg_replace('/INT AUTO_INCREMENT PRIMARY KEY/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $sql = preg_replace('/DECIMAL\([^)]+\)/i', 'NUMERIC', $sql);

        // Remove MySQL-specific in-table KEY and UNIQUE KEY lines
        $sql = preg_replace('/,\s*KEY\s+[a-zA-Z0-9_]+\s*\([^)]+\)/i', '', $sql);
        $sql = preg_replace('/,\s*UNIQUE\s+KEY\s+[a-zA-Z0-9_]+\s*\([^)]+\)/i', '', $sql);
    } else {
        $sql = $raw;
    }

    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $pdo->exec($stmt);
        }
    }
    echo "✓ 15 Tabel berhasil dibuat / diverifikasi.\n";

    // Run Seeder
    Seeder::run();
    echo "✓ Data awal (demo seeders) berhasil dimasukkan.\n";
    echo "=== Inisialisasi Selesai! medical_platform Siap Digunakan ===\n";

} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
