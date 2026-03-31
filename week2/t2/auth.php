<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unique session keys
define('T2_SESSION_USER', 't2_user');
define('T2_SESSION_CSRF', 't2_csrf');

function currentUser(): ?array
{
    $u = $_SESSION[T2_SESSION_USER] ?? null;
    return is_array($u) ? $u : null;
}

function isLoggedIn(): bool
{
    return currentUser() !== null;
}

function currentUserId(): int
{
    $u = currentUser();
    return $u ? (int) ($u['user_id'] ?? 0) : 0;
}

function currentUserRole(): string
{
    $u = currentUser();
    $role = $u ? (string) ($u['role'] ?? 'user') : '';
    return $role !== '' ? $role : 'user';
}

function isAdmin(): bool
{
    return currentUserRole() === 'admin';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireCsrfTokenOnPost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $token = (string) ($_POST['csrf_token'] ?? '');
    $expected = (string) ($_SESSION[T2_SESSION_CSRF] ?? '');
    if ($token === '' || $expected === '' || !hash_equals($expected, $token)) {
        http_response_code(403);
        exit('CSRF token mismatch');
    }
}

function csrfToken(): string
{
    $t = (string) ($_SESSION[T2_SESSION_CSRF] ?? '');
    if ($t === '') {
        $t = bin2hex(random_bytes(16));
        $_SESSION[T2_SESSION_CSRF] = $t;
    }
    return $t;
}
