<?php
$cookieName = 'remembered_username';
$cookieLifetime = 60 * 60 * 24 * 30; // 30 days

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $rememberMe = isset($_POST['remember_me']);

    if ($rememberMe && $username !== '') {
        setcookie($cookieName, $username, time() + $cookieLifetime, '/');
    } else {
        setcookie($cookieName, '', time() - 3600, '/');
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$savedUsername = $_COOKIE[$cookieName] ?? '';
$isRemembered = $savedUsername !== '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment 2 - Cookies</title>
</head>

<body>
    <h1>Assignment 2</h1>

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
                <?= $isRemembered ? 'checked' : '' ?>>
            Remember me
        </label>

        <br><br>

        <button type="submit">Submit</button>
    </form>

    <p>
        <?php if ($isRemembered): ?>
            Cookie saved for user: <strong><?= htmlspecialchars($savedUsername) ?></strong>
        <?php else: ?>
            No username cookie saved.
        <?php endif; ?>
    </p>
</body>

</html>