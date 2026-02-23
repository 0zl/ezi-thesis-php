<?php
require_once __DIR__ . '/config.php';

if (is_authenticated()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;
