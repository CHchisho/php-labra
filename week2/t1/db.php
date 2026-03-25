<?php

declare(strict_types=1);

require __DIR__ . '/dbconfig.php';

// Create PDO connection to MySQL/MariaDB.
$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $dbname);

if (!extension_loaded('pdo_mysql')) {
    http_response_code(500);
    exit('PHP extension `pdo_mysql` is not installed/enabled. Enable `extension=pdo_mysql` in php.ini and restart the web server.');
}

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection error via PDO: ' . $e->getMessage());
}

$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS MediaItemsTask (
    media_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filesize INT NOT NULL,
    media_type VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
SQL);

return $pdo;
