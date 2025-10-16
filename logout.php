<?php
// logout.php
session_start();
require_once 'config/database.php';
require_once 'auth/auth.php';

$auth = new AuthHandler();
$auth->logout();

// Redirect to login page
redirect('index.php');
?>