<?php
/**
 * Authentifizierungs-Funktionen
 */

require_once __DIR__ . '/db.php';

class Auth {
    
    // Benutzer einloggen
    public static function login($username, $password) {
        $user = db()->fetchOne(
            "SELECT * FROM users WHERE username = ? AND is_admin = 1 AND is_active = 1",
            [$username]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            // Session setzen
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];
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
    
    // Aktuelle User-Rolle
    public static function userRole() {
        return $_SESSION['user_role'] ?? 'editor';
    }
    
    // Prüfen ob Super Admin
    public static function isSuperAdmin() {
        return self::check() && self::userRole() === 'super_admin';
    }
    
    // Prüfen ob mindestens Admin
    public static function isAdmin() {
        return self::check() && in_array(self::userRole(), ['super_admin', 'admin']);
    }
    
    // Prüfen ob mindestens Moderator
    public static function isModerator() {
        return self::check() && in_array(self::userRole(), ['super_admin', 'admin', 'moderator']);
    }
    
    // Berechtigung prüfen
    public static function hasPermission($permissionName) {
        if (!self::check()) {
            return false;
        }
        
        $role = self::userRole();
        
        // Super Admin hat immer alle Rechte
        if ($role === 'super_admin') {
            return true;
        }
        
        // Prüfe in Datenbank
        $hasPermission = db()->fetchOne(
            "SELECT COUNT(*) as count FROM role_permissions 
             WHERE role = ? AND permission_name = ?",
            [$role, $permissionName]
        );
        
        return $hasPermission && $hasPermission['count'] > 0;
    }
    
    // Mehrere Berechtigungen prüfen (mindestens eine muss vorhanden sein)
    public static function hasAnyPermission($permissions) {
        foreach ($permissions as $permission) {
            if (self::hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }
    
    // Alle Berechtigungen müssen vorhanden sein
    public static function hasAllPermissions($permissions) {
        foreach ($permissions as $permission) {
            if (!self::hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }
    
    // Berechtigung erzwingen (sonst 403)
    public static function requirePermission($permissionName) {
        if (!self::hasPermission($permissionName)) {
            http_response_code(403);
            die('Keine Berechtigung für diese Aktion.');
        }
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
