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

$title = trim((string) ($_POST['title'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$filenameInput = trim((string) ($_POST['filename'] ?? ''));

if ($mediaId <= 0) {
    http_response_code(400);
    exit('Invalid media_id');
}
if ($title === '') {
    http_response_code(400);
    exit('Title is required');
}
if ($filenameInput === '') {
    http_response_code(400);
    exit('Filename is required');
}

// Load existing record. Keep filesize/media_type if no new file is uploaded.
$stmtExisting = $admin
    ? $pdo->prepare(
        'SELECT user_id, filename, filesize, media_type
         FROM MediaItemsTask
         WHERE media_id = :media_id'
    )
    : $pdo->prepare(
        'SELECT user_id, filename, filesize, media_type
         FROM MediaItemsTask
         WHERE media_id = :media_id AND user_id = :user_id'
    );
$stmtExisting->execute($admin ? [':media_id' => $mediaId] : [':media_id' => $mediaId, ':user_id' => $userId]);
$existing = $stmtExisting->fetch();

if (!$existing) {
    http_response_code(404);
    exit('Record not found');
}

$ownerId = (int) $existing['user_id'];
if (!$admin && $ownerId !== $userId) {
    http_response_code(403);
    exit('Forbidden');
}

$oldFilename = (string) $existing['filename'];
$oldFilesize = (int) $existing['filesize'];
$oldMediaType = (string) $existing['media_type'];

$sanitizedFilename = basename(str_replace(["\0", "\n", "\r"], '', $filenameInput));
$sanitizedFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $sanitizedFilename) ?: 'file.bin';

$uploadsMaxBytes = 20 * 1024 * 1024; // 20 MB
$allowedExtToMime = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'mp4' => 'video/mp4',
];

$oldExt = strtolower((string) pathinfo($oldFilename, PATHINFO_EXTENSION));
$newExt = strtolower((string) pathinfo($sanitizedFilename, PATHINFO_EXTENSION));
if ($newExt === '' || !isset($allowedExtToMime[$newExt])) {
    http_response_code(400);
    exit('Invalid file extension');
}

$descriptionValue = $description !== '' ? $description : null;

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

$oldPath = $uploadsDir . '/' . $oldFilename;
$newPath = $uploadsDir . '/' . $sanitizedFilename;

$fileUploaded = isset($_FILES['file']) && is_array($_FILES['file']) && ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;

if ($fileUploaded) {
    $file = $_FILES['file'];
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

    // Trust server-side MIME detection, not $_FILES['type'] (which can be spoofed).
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedMime = $finfo->file($tmpName);
    if (!is_string($detectedMime) || $detectedMime === '') {
        http_response_code(400);
        exit('Could not detect file type');
    }
    if ($detectedMime !== $allowedExtToMime[$newExt]) {
        http_response_code(400);
        exit('File type mismatch');
    }

    // Extra validation for images (reject non-decodable payloads).
    if (str_starts_with($detectedMime, 'image/')) {
        $imgInfo = @getimagesize($tmpName);
        if ($imgInfo === false) {
            http_response_code(400);
            exit('Invalid image data');
        }
    }

    $mediaType = $detectedMime;

    // If file name changed and old file exists - remove it after saving replacement.
    if ($sanitizedFilename !== $oldFilename && file_exists($newPath)) {
        unlink($newPath);
    }

    if (!move_uploaded_file((string) $file['tmp_name'], $newPath)) {
        http_response_code(500);
        exit('Could not save uploaded file');
    }

    if ($sanitizedFilename !== $oldFilename && file_exists($oldPath)) {
        unlink($oldPath);
    }

    $stmt = $pdo->prepare(
        'UPDATE MediaItemsTask
         SET filename = :filename,
             filesize = :filesize,
             media_type = :media_type,
             title = :title,
             description = :description
         WHERE media_id = :media_id'
    );
    $stmt->execute([
        ':filename' => $sanitizedFilename,
        ':filesize' => $filesize,
        ':media_type' => $mediaType,
        ':title' => $title,
        ':description' => $descriptionValue,
        ':media_id' => $mediaId,
    ]);
} else {
    // No replacement file uploaded: rename the file if the filename changed.
    // To avoid turning an existing file into a different media type (or an executable),
    // require the extension to stay the same when not uploading a new payload.
    if ($newExt !== $oldExt) {
        http_response_code(400);
        exit('Filename extension cannot change without a new file');
    }

    if ($sanitizedFilename !== $oldFilename && file_exists($oldPath)) {
        $renamed = @rename($oldPath, $newPath);
        if ($renamed !== true) {
            // Keep DB consistent with the filesystem.
            $sanitizedFilename = $oldFilename;
        }
    } elseif ($sanitizedFilename === '') {
        $sanitizedFilename = $oldFilename;
    }

    $stmt = $pdo->prepare(
        'UPDATE MediaItemsTask
         SET filename = :filename,
             title = :title,
             description = :description
         WHERE media_id = :media_id'
    );
    $stmt->execute([
        ':filename' => $sanitizedFilename,
        ':title' => $title,
        ':description' => $descriptionValue,
        ':media_id' => $mediaId,
    ]);
}

header('Location: index.php');
exit;
