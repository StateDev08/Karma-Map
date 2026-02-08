<?php
/**
 * PAX DEI Map - Konfigurationsdatei
 * Automatisch generiert am: {date('d.m.Y H:i:s')}
 */

// Fehlerberichterstattung (für Produktion auf 0 setzen!)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Datenbank-Konfiguration
define('DB_HOST', 'localhost');
define('DB_NAME', 'karma');
define('DB_USER', 'karma');
define('DB_PASS', '$kvJs?p6ob5O0cSc');
define('DB_CHARSET', 'utf8mb4');

// Pfade
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('UPLOAD_URL', '/uploads');

// Upload-Einstellungen
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Session-Einstellungen
define('SESSION_LIFETIME', 3600 * 24); // 24 Stunden
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Sicherheit
define('HASH_ALGO', PASSWORD_DEFAULT);
define('CSRF_TOKEN_NAME', 'csrf_token');

// Zeitzone
date_default_timezone_set('Europe/Berlin');

// Auto-Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
