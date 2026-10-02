<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
logout();
header('Location: ' . BASE_URL . '/admin/login.php');
exit;
