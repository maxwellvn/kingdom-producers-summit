#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Minimal migration runner.
 *   php database/migrate.php
 * Creates the database if missing and applies any *.sql files in
 * database/migrations that haven't been recorded in the `migrations` table.
 */

use App\Core\Database;

require dirname(__DIR__) . '/app/bootstrap.php';

$cfg = config('database');
$dbName = $cfg['name'];

$createDatabase = filter_var(env('DB_CREATE_DATABASE', 'true'), FILTER_VALIDATE_BOOLEAN);
if ($createDatabase) {
    $server = Database::server();
    $server->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}

echo "Database `{$dbName}` ready.\n";

$pdo = Database::connection();
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(190) NOT NULL,
        ran_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_migrations_name (name)
    ) ENGINE=InnoDB'
);

$ran = $pdo->query('SELECT name FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
$files = glob(__DIR__ . '/migrations/*.sql') ?: [];
sort($files);

$applied = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $ran, true)) {
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, "Could not read {$name}\n");
        exit(1);
    }

    try {
        // DDL in MySQL/MariaDB implicitly commits, so we don't wrap it in a
        // transaction. Run the migration, then record it.
        $pdo->exec($sql);
        $pdo->prepare('INSERT IGNORE INTO migrations (name) VALUES (?)')->execute([$name]);
        echo "Applied {$name}\n";
        $applied++;
    } catch (Throwable $e) {
        fwrite(STDERR, "Failed {$name}: {$e->getMessage()}\n");
        exit(1);
    }
}

echo $applied === 0 ? "Nothing to migrate.\n" : "Done. {$applied} migration(s) applied.\n";
