<?php
/**
 * AI Package Inspector - Database Configuration & Auto Initialization
 * XAMPP Compatible (MySQL/MariaDB via PDO)
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'package_inspector');
define('DB_CHARSET', 'utf8mb4');

function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Database might not exist yet, attempt auto-creation and schema initialization
            if ($e->getCode() == 1049 || strpos($e->getMessage(), "Unknown database") !== false) {
                try {
                    $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
                    $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, $options);
                    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                    
                    // Re-connect to newly created DB
                    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                    
                    // Auto-import schema.sql if exists
                    $schemaFile = __DIR__ . '/../database/schema.sql';
                    if (file_exists($schemaFile)) {
                        $sql = file_get_contents($schemaFile);
                        // Execute multi-statement SQL script safely
                        $pdo->exec($sql);
                    }
                } catch (PDOException $ex) {
                    die("Database Initialization Error: " . $ex->getMessage());
                }
            } else {
                die("Database Connection Error: " . $e->getMessage());
            }
        }
    }

    return $pdo;
}
