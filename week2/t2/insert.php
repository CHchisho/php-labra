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

$userId = currentUserId();
$title = trim((string) ($_POST['title'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$filenameInput = trim((string) ($_POST['filename'] ?? ''));

if ($title === '') {
    http_response_code(400);
    exit('Title is required');
}

if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
    http_response_code(400);
    exit('File is required');
}

$file = $_FILES['file'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    exit('File upload failed');
}

$originalName = (string) ($file['name'] ?? '');
$basenameOriginal = basename(str_replace(["\0", "\n", "\r"], '', $originalName));

// Use user-provided filename (from dropdown/text input) or fallback to original.
$desiredFilename = $filenameInput !== '' ? $filenameInput : $basenameOriginal;
$desiredFilename = basename(str_replace(["\0", "\n", "\r"], '', $desiredFilename));
$desiredFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $desiredFilename) ?: 'file.bin';

$uploadsDir = __DIR__ . '/uploads';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0775, true);
}

$destination = $uploadsDir . '/' . $desiredFilename;

// move_uploaded_file expects paths without extra query params etc.
if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
    http_response_code(500);
    exit('Could not save uploaded file');
}

$filesize = (int) ($file['size'] ?? 0);
$mediaType = (string) ($file['type'] ?? '');
if ($mediaType === '') {
    $mediaType = 'application/octet-stream';
}

$descriptionValue = $description !== '' ? $description : null;

$stmt = $pdo->prepare(
    'INSERT INTO MediaItemsTask (user_id, filename, filesize, media_type, title, description)
     VALUES (:user_id, :filename, :filesize, :media_type, :title, :description)'
);
$stmt->execute([
    ':user_id' => $userId,
    ':filename' => $desiredFilename,
    ':filesize' => $filesize,
    ':media_type' => $mediaType,
    ':title' => $title,
    ':description' => $descriptionValue,
]);

header('Location: index.php');
exit;
