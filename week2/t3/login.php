<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$pdo = require __DIR__ . '/db.php';

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfTokenOnPost();

    // Clear current t2 session to allow switching accounts.
    unset($_SESSION[T2_SESSION_USER]);

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter username and password';
    } else {
        $stmt = $pdo->prepare('SELECT user_id, username, password_hash, role FROM UsersTask WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch();

        if ($row && password_verify($password, (string) $row['password_hash'])) {
            $_SESSION[T2_SESSION_USER] = [
                'user_id' => (int) $row['user_id'],
                'username' => (string) $row['username'],
                'role' => (string) $row['role'],
            ];
            header('Location: index.php');
            exit;
        }

        $error = 'Invalid username or password';
    }
}

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$alreadySignedIn = isLoggedIn();
$signedInUser = currentUser();

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
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
    <h1>Login</h1>
    <div class="card">
        <?php if ($alreadySignedIn): ?>
            <div class="muted" style="margin-bottom:12px;">
                You are already signed in as
                <b><?= e((string) ($signedInUser['username'] ?? '')) ?></b>
                (role: <b><?= e((string) ($signedInUser['role'] ?? 'user')) ?></b>).
                <a href="index.php">Continue</a> or <a href="logout.php">sign out</a>.
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <label for="u">Username</label>
            <input id="u" type="text" name="username" value="<?= e($username) ?>" required>

            <label for="p">Password</label>
            <input id="p" type="password" name="password" required>

            <div style="margin-top:14px; display:flex; gap:10px; align-items:center;">
                <button type="submit">Sign in</button>
                <a href="register.php" class="muted">Register</a>
            </div>
        </form>

        <p class="muted" style="margin-top:12px;">
            Default admin: <b>admin</b> / <b>admin</b>
        </p>
    </div>
</body>

</html>