<?php
/**
 * Authentifizierungs-Funktionen
 */

require_once __DIR__ . '/db.php';

class Auth {
    
    // Benutzer einloggen
    public static function login($username, $password) {
        $user = db()->fetchOne(
            "SELECT * FROM users WHERE username = ? AND is_admin = 1",
            [$username]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            // Session setzen
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = true;
            
            // Last Login aktualisieren
            db()->update('users', 
                ['last_login' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $user['id']]
            );
            
            return true;
        }
        
        return false;
    }
    
    // Benutzer ausloggen
    public static function logout() {
        $_SESSION = [];
        session_destroy();
        return true;
    }
    
    // Prüfen ob eingeloggt
    public static function check() {
        return isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }
    
    // Aktuelle User-ID
    public static function userId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    // Aktuelle Username
    public static function username() {
        return $_SESSION['username'] ?? null;
    }
    
    // Admin-Zugriff erzwingen (Redirect zu Login)
    public static function requireAdmin() {
        if (!self::check()) {
            // Dynamischer Redirect-Pfad
            $loginPath = 'login.php';
            if (strpos($_SERVER['PHP_SELF'], '/admin/pages/') !== false) {
                $loginPath = '../login.php';
            }
            header('Location: ' . $loginPath);
            exit;
        }
    }
    
    // CSRF-Token generieren
    public static function generateCsrfToken() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    // CSRF-Token validieren
    public static function validateCsrfToken($token) {
        return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
}
