<?php

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

/** @var PDO $pdo */
$pdo = require __DIR__ . '/db.php';

$mediaId = isset($_POST['media_id']) ? (int) $_POST['media_id'] : 0;
$userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 1;

if ($mediaId <= 0) {
    http_response_code(400);
    exit('Invalid media_id');
}

$stmt = $pdo->prepare(
    'SELECT filename
     FROM MediaItemsTask
     WHERE media_id = :media_id AND user_id = :user_id'
);
$stmt->execute([':media_id' => $mediaId, ':user_id' => $userId]);
$row = $stmt->fetch();

if ($row) {
    $filename = (string) $row['filename'];
    $uploadsDir = __DIR__ . '/uploads';
    $path = $uploadsDir . '/' . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}

$stmtDel = $pdo->prepare(
    'DELETE FROM MediaItemsTask
     WHERE media_id = :media_id AND user_id = :user_id'
);
$stmtDel->execute([':media_id' => $mediaId, ':user_id' => $userId]);

header('Location: index.php');
exit;

