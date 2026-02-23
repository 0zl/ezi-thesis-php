<?php
define('APP_NAME', 'Posyandu');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '/ezi-thesis-php');

define('AUTH_USERNAME', 'admin');
define('AUTH_PASSWORD', 'admin');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

function is_authenticated(): bool {
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

function require_auth(): void {
    if (!is_authenticated()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function get_user_display_name(): string {
    return $_SESSION['username'] ?? 'Admin';
}
