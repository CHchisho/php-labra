<?php

declare(strict_types=1);

$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 1;

/** @var PDO $pdo */
$pdo = require __DIR__ . '/db.php';

$stmtItems = $pdo->prepare(
    'SELECT media_id, user_id, filename, filesize, media_type, title, description, created_at
     FROM MediaItemsTask
     WHERE user_id = :user_id
     ORDER BY media_id DESC'
);
$stmtItems->execute([':user_id' => $userId]);
$items = $stmtItems->fetchAll();

$stmtNames = $pdo->prepare(
    'SELECT DISTINCT filename
     FROM MediaItemsTask
     WHERE user_id = :user_id
     ORDER BY filename ASC
     LIMIT 10'
);
$stmtNames->execute([':user_id' => $userId]);
$availableFilenames = array_map(
    static fn (array $row): string => (string) $row['filename'],
    $stmtNames->fetchAll()
);

