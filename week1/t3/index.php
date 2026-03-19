<?php
session_start();

$sessionKey = 'remembered_username';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $rememberMe = isset($_POST['remember_me']);

    if ($rememberMe && $username !== '') {
        $_SESSION[$sessionKey] = $username;
    } else {
        unset($_SESSION[$sessionKey]);
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$savedUsername = $_SESSION[$sessionKey] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment 3 - Sessions</title>
</head>

<body>
    <h1>Assignment 3</h1>

    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
        <label for="username">Username:</label>
        <input
            type="text"
            id="username"
            name="username"
            value="<?= htmlspecialchars($savedUsername) ?>">

        <br><br>

        <label>
            <input
                type="checkbox"
                name="remember_me"
                <?= $savedUsername !== '' ? 'checked' : '' ?>>
            Remember me
        </label>

        <br><br>

        <button type="submit">Submit</button>
    </form>

    <p>
        <?php if ($savedUsername !== ''): ?>
            Session saved for user: <strong><?= htmlspecialchars($savedUsername) ?></strong>
        <?php else: ?>
            No username stored in session.
        <?php endif; ?>
    </p>
</body>

</html>