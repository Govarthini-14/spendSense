<?php
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';

function ensureAuthenticated(): void
{
    if (!currentUser()) {
        $_SESSION['flash_error'] = 'Please log in first.';
        header('Location: login.php');
        exit;
    }
}

if (isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    $pdo = getPdo();
    $user = getUserById($pdo, (int)$_SESSION['user_id']);
    if ($user) {
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ];
    } else {
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit;
    }
}

if (!empty($_SESSION['user'])) {
    $_SESSION['user_id'] = (int)$_SESSION['user']['id'];
}

if (isset($_SESSION['flash_error']) && !empty($_SESSION['flash_error'])) {
    $_SESSION['flash']['error'] = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}
