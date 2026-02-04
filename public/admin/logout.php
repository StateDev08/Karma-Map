<?php
// Dynamischer Pfad zu includes
$includesPath = file_exists(__DIR__ . '/../../includes/config.php') 
    ? __DIR__ . '/../../includes/' 
    : (file_exists(__DIR__ . '/../../../includes/config.php') 
        ? __DIR__ . '/../../../includes/' 
        : __DIR__ . '/../../includes/');

require_once $includesPath . 'config.php';
require_once $includesPath . 'auth.php';

Auth::logout();
header('Location: login.php');
exit;
