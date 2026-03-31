<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireLogin();

$user = currentUser();
$userId = currentUserId();
$admin = isAdmin();
$csrf = csrfToken();

require __DIR__ . '/select.php'; // provides: $items, $availableFilenames

$defaultFilenames = ['sunset.jpg', 'sample.mp4', 'ffd8.jpg', '2f9b.jpg'];
$filenames = !empty($availableFilenames) ? $availableFilenames : $defaultFilenames;
$filenameOptions = array_values(array_unique(array_merge(
    $filenames,
    array_map(
        static fn(array $it): string => (string) ($it['filename'] ?? ''),
        $items
    )
)));
sort($filenameOptions);
$initialFilename = $filenameOptions[0] ?? 'file.bin';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MediaItems CRUD</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        h1 {
            margin-top: 0;
        }

        form {
            margin: 0;
        }

        .grid {
            display: grid;
            gap: 16px;
            grid-template-columns: 1.1fr 1fr;
            align-items: start;
        }

        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 14px;
        }

        label {
            display: block;
            font-weight: 600;
            margin: 10px 0 6px;
        }

        input[type="text"],
        textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 8px;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        button,
        input[type="submit"] {
            padding: 8px 12px;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        th,
        td {
            border-bottom: 1px solid #eee;
            padding: 10px;
            vertical-align: top;
        }

        th {
            text-align: left;
            background: #fafafa;
        }

        .row-actions {
            white-space: nowrap;
        }

        .btn-edit {
            background: #0b5;
            border: none;
            color: #fff;
            border-radius: 6px;
        }

        .btn-delete {
            background: #c33;
            border: none;
            color: #fff;
            border-radius: 6px;
        }

        .btn-secondary {
            background: #eee;
            border: none;
            border-radius: 6px;
        }

        /* Simple modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .55);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
        }

        .modal {
            width: min(720px, 100%);
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, .25);
            overflow: hidden;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #f3f3f3;
        }

        .modal-body {
            padding: 16px;
        }

        .modal-close {
            border: none;
            background: transparent;
            font-size: 18px;
            cursor: pointer;
        }

        .muted {
            color: #666;
            font-size: 13px;
        }
    </style>
</head>

<body>
    <h1>MediaItems CRUD (PDO)</h1>
    <div class="muted" style="margin-bottom: 12px;"><a href="logout.php">Sign out</a>
    </div>

    <div class="grid">
        <div class="card">
            <h2 style="margin-top:0;">Insert Record</h2>
            <form action="insert.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

                <label for="insTitle">Title</label>
                <input id="insTitle" type="text" name="title" required>

                <label for="insDescription">Description</label>
                <textarea id="insDescription" name="description"></textarea>

                <label for="insFilename">Filename (choose from list)</label>
                <select id="insFilename" name="filename" required>
                    <?php foreach ($filenameOptions as $fn): ?>
                        <option value="<?= e($fn) ?>" <?= $fn === $initialFilename ? 'selected' : '' ?>>
                            <?= e($fn) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="insFile">File (required)</label>
                <input id="insFile" type="file" name="file" accept="image/*,video/*" required>

                <div style="margin-top:14px;">
                    <input type="submit" value="Insert">
                </div>
            </form>
            <p class="muted">The record is saved to the database and the uploaded file is placed into `uploads/`.</p>
        </div>

        <div class="card">
            <h2 style="margin-top:0;">Records List</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <?php if ($admin): ?>
                            <th>User</th>
                        <?php endif; ?>
                        <th>Title</th>
                        <th>Filename</th>
                        <th>Type/Size</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="<?= $admin ? '7' : '6' ?>" class="muted">
                                <?= $admin ? 'No records.' : 'No records for your account.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $it): ?>
                            <?php
                            $id = (string) $it['media_id'];
                            $ownerId = (string) ($it['user_id'] ?? '');
                            $title = (string) ($it['title'] ?? '');
                            $filename = (string) ($it['filename'] ?? '');
                            $filesize = (string) (isset($it['filesize']) ? (string) $it['filesize'] : '');
                            $mediaType = (string) ($it['media_type'] ?? '');
                            $desc = (string) ($it['description'] ?? '');
                            $createdAt = (string) ($it['created_at'] ?? '');
                            ?>
                            <tr>
                                <td><?= e($id) ?><div class="muted"><?= e($createdAt) ?></div>
                                </td>
                                <?php if ($admin): ?>
                                    <td><?= e($ownerId) ?></td>
                                <?php endif; ?>
                                <td><?= e($title) ?></td>
                                <td><?= e($filename) ?></td>
                                <td><?= e($mediaType) ?><div class="muted"><?= e($filesize) ?> bytes</div>
                                </td>
                                <td><?= e($desc) ?></td>
                                <td class="row-actions">
                                    <button
                                        type="button"
                                        class="btn-edit"
                                        data-media_id="<?= e($id) ?>"
                                        data-title="<?= e($title) ?>"
                                        data-description="<?= e($desc) ?>"
                                        data-filename="<?= e($filename) ?>"
                                        onclick="openEditModal(this)">
                                        Edit
                                    </button>

                                    <form action="delete.php" method="post" style="display:inline-block;">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                        <input type="hidden" name="media_id" value="<?= e($id) ?>">
                                        <input
                                            class="btn-delete"
                                            type="submit"
                                            value="Delete"
                                            onclick="return confirm('Delete record and file?')">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="editOverlay" class="modal-overlay" role="dialog" aria-modal="true">
        <div class="modal">
            <div class="modal-header">
                <div>
                    <strong>Update Record</strong>
                </div>
                <button class="modal-close" type="button" onclick="closeEditModal()">&times;</button>
            </div>

            <div class="modal-body">
                <form id="editForm" action="update.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="media_id" id="editMediaId">

                    <label for="editTitle">Title</label>
                    <input id="editTitle" type="text" name="title" required>

                    <label for="editDescription">Description</label>
                    <textarea id="editDescription" name="description"></textarea>

                    <label for="editFilenameSelect">Filename</label>
                    <select id="editFilenameSelect" name="filename" required>
                        <?php foreach ($filenameOptions as $fn): ?>
                            <option value="<?= e($fn) ?>"></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="editFile">New file (optional)</label>
                    <input id="editFile" type="file" name="file" accept="image/*,video/*">

                    <div style="margin-top:14px; display:flex; gap: 10px; justify-content:flex-end;">
                        <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
                        <input type="submit" value="Update">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const overlay = document.getElementById('editOverlay');

        function openEditModal(btn) {
            const mediaId = btn.getAttribute('data-media_id') || '';
            const title = btn.getAttribute('data-title') || '';
            const description = btn.getAttribute('data-description') || '';
            const filename = btn.getAttribute('data-filename') || '';

            document.getElementById('editMediaId').value = mediaId;
            document.getElementById('editTitle').value = title;
            document.getElementById('editDescription').value = description;
            document.getElementById('editFilenameSelect').value = filename;

            // Clear file input (cannot prefill for security).
            const fileEl = document.getElementById('editFile');
            if (fileEl) fileEl.value = '';

            overlay.style.display = 'flex';
        }

        function closeEditModal() {
            overlay.style.display = 'none';
        }

        // Close when clicking outside the modal.
        overlay.addEventListener('click', function(ev) {
            if (ev.target === overlay) closeEditModal();
        });
        document.addEventListener('keydown', function(ev) {
            if (ev.key === 'Escape') closeEditModal();
        });
    </script>
</body>

</html>