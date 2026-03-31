<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireLogin();

$userId = currentUserId();
$admin = isAdmin();

$pdo = require __DIR__ . '/db.php';

$stmtItems = $admin
    ? $pdo->prepare(
        'SELECT media_id, user_id, filename, filesize, media_type, title, description, created_at
         FROM MediaItemsTask
         ORDER BY media_id DESC'
    )
    : $pdo->prepare(
        'SELECT media_id, user_id, filename, filesize, media_type, title, description, created_at
         FROM MediaItemsTask
         WHERE user_id = :user_id
         ORDER BY media_id DESC'
    );
$stmtItems->execute($admin ? [] : [':user_id' => $userId]);
$items = $stmtItems->fetchAll();

$stmtNames = $admin
    ? $pdo->prepare(
        'SELECT DISTINCT filename
         FROM MediaItemsTask
         ORDER BY filename ASC
         LIMIT 20'
    )
    : $pdo->prepare(
        'SELECT DISTINCT filename
         FROM MediaItemsTask
         WHERE user_id = :user_id
         ORDER BY filename ASC
         LIMIT 20'
    );
$stmtNames->execute($admin ? [] : [':user_id' => $userId]);
$availableFilenames = array_map(
    static fn(array $row): string => (string) $row['filename'],
    $stmtNames->fetchAll()
);
