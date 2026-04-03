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

$uploadsMaxBytes = 20 * 1024 * 1024; // 20 MB
$allowedExtToMime = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'mp4' => 'video/mp4',
];

$ext = strtolower((string) pathinfo($desiredFilename, PATHINFO_EXTENSION));
if ($ext === '' || !isset($allowedExtToMime[$ext])) {
    http_response_code(400);
    exit('Invalid file extension');
}

$tmpName = (string) ($file['tmp_name'] ?? '');
if ($tmpName === '' || !is_uploaded_file($tmpName)) {
    http_response_code(400);
    exit('Invalid upload');
}

$filesize = (int) ($file['size'] ?? 0);
if ($filesize <= 0 || $filesize > $uploadsMaxBytes) {
    http_response_code(400);
    exit('Invalid file size');
}

// Trust server-side MIME detection, not $_FILES['type']
$finfo = new finfo(FILEINFO_MIME_TYPE);
$detectedMime = $finfo->file($tmpName);
if (!is_string($detectedMime) || $detectedMime === '') {
    http_response_code(400);
    exit('Could not detect file type');
}
if ($detectedMime !== $allowedExtToMime[$ext]) {
    http_response_code(400);
    exit('File type mismatch');
}

// Extra validation for images (reject non-decodable payloads)
if (str_starts_with($detectedMime, 'image/')) {
    $imgInfo = @getimagesize($tmpName);
    if ($imgInfo === false) {
        http_response_code(400);
        exit('Invalid image data');
    }
}

$uploadsDir = __DIR__ . '/uploads';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0775, true);
}

$htaccessPath = $uploadsDir . '/.htaccess';
if (!is_file($htaccessPath)) {
    // Prevent direct web access to uploaded content.
    @file_put_contents(
        $htaccessPath,
        "Options -Indexes\n"
            . "<IfModule mod_authz_core.c>\n"
            . "  Require all denied\n"
            . "</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n"
            . "  Deny from all\n"
            . "</IfModule>\n",
        LOCK_EX
    );
}

$destination = $uploadsDir . '/' . $desiredFilename;

// move_uploaded_file expects paths without extra query params etc.
if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
    http_response_code(500);
    exit('Could not save uploaded file');
}

$mediaType = $detectedMime;

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
