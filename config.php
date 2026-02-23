<?php
define('APP_NAME', 'Posyandu');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '');

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'posyandu_db');

define('AUTH_USERNAME', 'admin');
define('AUTH_PASSWORD', 'admin');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/includes/db.php';

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
