<?php
require_once __DIR__ . '/../config/helpers.php';
$_SESSION = [];
session_destroy();
header('Location: ' . url('auth/login.php'));
exit;
