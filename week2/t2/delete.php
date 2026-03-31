<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireLogin();
requireCsrfTokenOnPost();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

/** @var PDO $pdo */
$pdo = require __DIR__ . '/db.php';

$mediaId = isset($_POST['media_id']) ? (int) $_POST['media_id'] : 0;
$userId = currentUserId();
$admin = isAdmin();

if ($mediaId <= 0) {
    http_response_code(400);
    exit('Invalid media_id');
}

$stmt = $admin
    ? $pdo->prepare(
        'SELECT filename
         FROM MediaItemsTask
         WHERE media_id = :media_id'
    )
    : $pdo->prepare(
        'SELECT filename
         FROM MediaItemsTask
         WHERE media_id = :media_id AND user_id = :user_id'
    );
$stmt->execute($admin ? [':media_id' => $mediaId] : [':media_id' => $mediaId, ':user_id' => $userId]);
$row = $stmt->fetch();

if ($row) {
    $filename = (string) $row['filename'];
    $uploadsDir = __DIR__ . '/uploads';
    $path = $uploadsDir . '/' . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}

$stmtDel = $admin
    ? $pdo->prepare(
        'DELETE FROM MediaItemsTask
         WHERE media_id = :media_id'
    )
    : $pdo->prepare(
        'DELETE FROM MediaItemsTask
         WHERE media_id = :media_id AND user_id = :user_id'
    );
$stmtDel->execute($admin ? [':media_id' => $mediaId] : [':media_id' => $mediaId, ':user_id' => $userId]);

header('Location: index.php');
exit;
