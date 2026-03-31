<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pdo = require __DIR__ . '/db.php';

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfTokenOnPost();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $password2 = (string) ($_POST['password2'] ?? '');

    if ($username === '' || $password === '' || $password2 === '') {
        $error = 'Please fill in all fields';
    } elseif (strlen($username) < 3) {
        $error = 'Username is too short (min 3 characters)';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 4) {
        $error = 'Password is too short (min 4 characters)';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare('INSERT INTO UsersTask (username, password_hash, role) VALUES (:u, :p, :r)');
            $stmt->execute([':u' => $username, ':p' => $hash, ':r' => 'user']);
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Could not create user (username might be taken)';
        }
    }
}

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .card {
            max-width: 520px;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 14px;
        }

        label {
            display: block;
            font-weight: 600;
            margin: 10px 0 6px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            box-sizing: border-box;
            padding: 8px;
        }

        .muted {
            color: #666;
            font-size: 13px;
        }

        .error {
            color: #b00;
            margin: 10px 0;
        }

        button {
            padding: 8px 12px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <h1>Register</h1>
    <div class="card">
        <?php if ($error !== ''): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <label for="u">Username</label>
            <input id="u" type="text" name="username" value="<?= e($username) ?>" required>

            <label for="p">Password</label>
            <input id="p" type="password" name="password" required>

            <label for="p2">Repeat password</label>
            <input id="p2" type="password" name="password2" required>

            <div style="margin-top:14px; display:flex; gap:10px; align-items:center;">
                <button type="submit">Create account</button>
                <a href="login.php" class="muted">Back to login</a>
            </div>
        </form>
    </div>
</body>

</html>