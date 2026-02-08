<?php
/**
 * PAX DEI Map - Installation Setup
 * Einfache Installation mit Web-Interface
 */

// Session für lokale Umgebung robuster machen (Cookie-Pfad, Schreiben vor Redirect)
$sessionPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
if ($sessionPath === '/' || $sessionPath === '\\') {
    $sessionPath = '/';
} else {
    $sessionPath = rtrim($sessionPath, '/\\') . '/';
}
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => $sessionPath,
    'domain'   => '',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// Fehlerberichterstattung für Setup
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Setup-Status
$step = $_GET['step'] ?? 'welcome';
$errors = [];
$success = [];

// Pfade
define('BASE_PATH', __DIR__);
define('CONFIG_FILE', BASE_PATH . '/includes/config.php');
define('SCHEMA_FILE', BASE_PATH . '/database/schema.sql');

// Setup-Schritte durchführen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Schritt 1: Datenbank-Verbindung testen
    if ($step === 'database') {
        $dbHost = $_POST['db_host'] ?? 'localhost';
        $dbName = $_POST['db_name'] ?? 'pax_die_map';
        $dbUser = $_POST['db_user'] ?? 'root';
        $dbPass = $_POST['db_pass'] ?? '';
        
        // Speichern in Session
        $_SESSION['setup'] = [
            'db_host' => $dbHost,
            'db_name' => $dbName,
            'db_user' => $dbUser,
            'db_pass' => $dbPass
        ];
        
        // Verbindung testen
        try {
            $dsn = "mysql:host={$dbHost};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // Datenbank erstellen falls nicht vorhanden
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");
            
            $success[] = "Datenbankverbindung erfolgreich!";
            $success[] = "Datenbank '{$dbName}' wurde erstellt/gefunden.";
            
            // Session vor Redirect schreiben (wichtig v. a. lokal)
            session_write_close();
            header('Location: ?step=install');
            exit;
            
        } catch (PDOException $e) {
            $errors[] = "Datenbankfehler: " . $e->getMessage();
        }
    }
    
    // Schritt 2: Installation durchführen
    if ($step === 'install') {
        $setup = $_SESSION['setup'] ?? null;
        
        if (!$setup) {
            $errors[] = "Setup-Daten fehlen. Bitte starte von vorne.";
            $errors[] = "Lokal oft: Session geht beim Weiterleiten verloren. Gehe auf „Zurück“, trage die Datenbank-Daten erneut ein, klicke auf „Verbindung testen & weiter“, dann sofort „Jetzt installieren“.";
        } else {
            try {
                // Datenbank-Verbindung
                $dsn = "mysql:host={$setup['db_host']};dbname={$setup['db_name']};charset=utf8mb4";
                $pdo = new PDO($dsn, $setup['db_user'], $setup['db_pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                // Schema-Datei laden
                if (!file_exists(SCHEMA_FILE)) {
                    throw new Exception("Schema-Datei nicht gefunden: " . SCHEMA_FILE);
                }
                
                $sql = file_get_contents(SCHEMA_FILE);
                
                // Datenbank explizit verwenden
                $pdo->exec("USE `{$setup['db_name']}`");
                
                // Multi-Query ausführen (komplettes Schema auf einmal)
                // Entferne CREATE DATABASE und USE Statements
                $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
                $sql = preg_replace('/USE .*;/i', '', $sql);
                
                // Führe das gesamte Schema aus
                $pdo->exec($sql);
                
                // Überprüfen ob Tabellen erstellt wurden
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                
                if (count($tables) < 6) {
                    throw new Exception("Nicht alle Tabellen wurden erstellt. Gefunden: " . count($tables) . " von 6 erwarteten Tabellen.");
                }
                
                $success[] = "Datenbank-Schema erfolgreich installiert!";
                $success[] = count($tables) . " Tabellen wurden erstellt.";
                $success[] = "Standard-Daten wurden eingefügt.";
                
                session_write_close();
                header('Location: ?step=admin');
                exit;
                
            } catch (Exception $e) {
                $errors[] = "Installationsfehler: " . $e->getMessage();
                $errors[] = "Tipp: Stelle sicher, dass die Datenbank leer ist oder lösche sie und versuche es erneut.";
            }
        }
    }
    
    // Schritt 3: Admin-User erstellen
    if ($step === 'admin') {
        $setup = $_SESSION['setup'] ?? null;
        $adminUser = $_POST['admin_user'] ?? '';
        $adminPass = $_POST['admin_pass'] ?? '';
        $adminPassConfirm = $_POST['admin_pass_confirm'] ?? '';
        $adminEmail = $_POST['admin_email'] ?? '';
        
        if (empty($adminUser) || empty($adminPass)) {
            $errors[] = "Benutzername und Passwort sind erforderlich!";
        } elseif ($adminPass !== $adminPassConfirm) {
            $errors[] = "Passwörter stimmen nicht überein!";
        } elseif (strlen($adminPass) < 6) {
            $errors[] = "Passwort muss mindestens 6 Zeichen lang sein!";
        } else {
            try {
                $dsn = "mysql:host={$setup['db_host']};dbname={$setup['db_name']};charset=utf8mb4";
                $pdo = new PDO($dsn, $setup['db_user'], $setup['db_pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                // Prüfen ob users-Tabelle existiert
                try {
                    $tableExists = $pdo->query("SELECT 1 FROM users LIMIT 1");
                } catch (PDOException $e) {
                    $errors[] = "Die users-Tabelle existiert nicht!";
                    $errors[] = "Bitte gehe zurück zu Schritt 3 und führe die Installation erneut durch.";
                    $errors[] = "Debug: " . $e->getMessage();
                }
                
                if (empty($errors)) {
                    // Standard-Admin löschen falls vorhanden
                    try {
                        $pdo->exec("DELETE FROM users WHERE username = 'admin'");
                    } catch (PDOException $e) {
                        // Ignorieren falls kein admin existiert
                    }
                
                // Neuen Admin erstellen
                $hashedPassword = password_hash($adminPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, is_admin) VALUES (?, ?, ?, 1)");
                $stmt->execute([$adminUser, $hashedPassword, $adminEmail]);
                
                $_SESSION['setup']['admin_user'] = $adminUser;
                
                $success[] = "Admin-User erfolgreich erstellt!";
                
                session_write_close();
                header('Location: ?step=config');
                exit;
                }
                
            } catch (Exception $e) {
                $errors[] = "Fehler beim Erstellen des Admin-Users: " . $e->getMessage();
            }
        }
    }
    
    // Schritt 4: Config-Datei erstellen
    if ($step === 'config') {
        $setup = $_SESSION['setup'] ?? null;
        
        if (!$setup) {
            $errors[] = "Setup-Daten fehlen! Bitte starte die Installation wieder bei Schritt 1 (Datenbank).";
        } else {
            try {
                // Upload-Verzeichnis erstellen
                $uploadDirs = [
                    BASE_PATH . '/uploads',
                    BASE_PATH . '/uploads/map',
                    BASE_PATH . '/uploads/logo',
                    BASE_PATH . '/uploads/marker'
                ];
                
                foreach ($uploadDirs as $dir) {
                    if (!is_dir($dir)) {
                        if (!mkdir($dir, 0755, true)) {
                            throw new Exception("Konnte Verzeichnis nicht erstellen: {$dir}");
                        }
                    }
                }
                
                $success[] = "Upload-Verzeichnisse erstellt!";
                
                // Config-Datei generieren
                $configContent = generateConfigFile($setup);
                
                if (!file_put_contents(CONFIG_FILE, $configContent)) {
                    throw new Exception("Konnte Config-Datei nicht schreiben!");
                }
                
                $success[] = "Konfigurationsdatei erstellt!";
                
                session_write_close();
                header('Location: ?step=complete');
                exit;
                
            } catch (Exception $e) {
                $errors[] = "Konfigurationsfehler: " . $e->getMessage();
            }
        }
    }
}

// Config-Datei generieren
function generateConfigFile($setup) {
    $dbHost = $setup['db_host'];
    $dbName = $setup['db_name'];
    $dbUser = $setup['db_user'];
    $dbPass = $setup['db_pass'];
    
    return <<<PHP
<?php
/**
 * PAX DEI Map - Konfigurationsdatei
 * Automatisch generiert am: {date('d.m.Y H:i:s')}
 */

// Fehlerberichterstattung (für Produktion auf 0 setzen!)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Datenbank-Konfiguration
define('DB_HOST', '{$dbHost}');
define('DB_NAME', '{$dbName}');
define('DB_USER', '{$dbUser}');
define('DB_PASS', '{$dbPass}');
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

PHP;
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAX DEI Map - Installation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <style>
        :root {
            --bg-primary: #000000;
            --bg-secondary: #1a1a1a;
            --bg-tertiary: #2a2a2a;
            --bg-hover: #3a3a3a;
            --color-primary: #FF0000;
            --color-secondary: #DC143C;
            --text-primary: #ffffff;
            --text-secondary: #b9bbbe;
            --text-muted: #72767d;
            --border-color: #FF0000;
            --shadow: 0 2px 10px rgba(255, 0, 0, 0.2);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-tertiary) 100%);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 700px;
            width: 100%;
        }
        
        .setup-box {
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.8);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 36px;
            color: var(--color-primary);
            text-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
            margin-bottom: 10px;
        }
        
        .header p {
            color: var(--text-secondary);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .progress {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        
        .progress::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--bg-tertiary);
            z-index: 0;
        }
        
        .step-indicator {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--bg-tertiary);
            border: 2px solid var(--bg-hover);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        
        .step-indicator.active {
            background: var(--color-primary);
            border-color: var(--color-primary);
            color: white;
        }
        
        .step-indicator.completed {
            background: var(--color-secondary);
            border-color: var(--color-secondary);
            color: white;
        }
        
        .content {
            margin-bottom: 30px;
        }
        
        h2 {
            color: var(--color-primary);
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .info-box {
            background: var(--bg-tertiary);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--color-primary);
        }
        
        .info-box h3 {
            color: var(--text-primary);
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .info-box ul {
            margin-left: 20px;
            color: var(--text-secondary);
        }
        
        .info-box li {
            margin: 5px 0;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: rgba(45, 125, 70, 0.2);
            border: 1px solid #2d7d46;
            color: #4CAF50;
        }
        
        .alert-error {
            background: rgba(139, 0, 0, 0.2);
            border: 1px solid #8B0000;
            color: #FF6B6B;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            background: var(--bg-tertiary);
            border: 1px solid var(--bg-hover);
            border-radius: 4px;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 2px rgba(255, 0, 0, 0.1);
        }
        
        .form-group small {
            display: block;
            color: var(--text-muted);
            font-size: 12px;
            margin-top: 5px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--color-primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--color-secondary);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        
        .btn-block {
            width: 100%;
            justify-content: center;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }
        
        .success-icon {
            font-size: 64px;
            color: var(--color-primary);
            text-align: center;
            margin: 30px 0;
        }
        
        .completion-links {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 30px;
        }
        
        code {
            background: var(--bg-tertiary);
            padding: 2px 6px;
            border-radius: 3px;
            color: var(--color-primary);
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-box">
            <div class="header">
                <h1><i class="fas fa-shield-alt"></i> KARMA</h1>
                <p>PAX DEI Map Installation</p>
            </div>
            
            <!-- Progress Bar -->
            <div class="progress">
                <div class="step-indicator <?php echo in_array($step, ['welcome', 'database', 'install', 'admin', 'config', 'complete']) ? 'completed' : ''; ?>">1</div>
                <div class="step-indicator <?php echo in_array($step, ['database', 'install', 'admin', 'config', 'complete']) ? 'active' : ''; ?> <?php echo in_array($step, ['install', 'admin', 'config', 'complete']) ? 'completed' : ''; ?>">2</div>
                <div class="step-indicator <?php echo in_array($step, ['install', 'admin', 'config', 'complete']) ? 'active' : ''; ?> <?php echo in_array($step, ['admin', 'config', 'complete']) ? 'completed' : ''; ?>">3</div>
                <div class="step-indicator <?php echo in_array($step, ['admin', 'config', 'complete']) ? 'active' : ''; ?> <?php echo in_array($step, ['config', 'complete']) ? 'completed' : ''; ?>">4</div>
                <div class="step-indicator <?php echo $step === 'complete' ? 'active completed' : ''; ?>">5</div>
            </div>
            
            <!-- Errors -->
            <?php foreach ($errors as $error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endforeach; ?>
            
            <!-- Success Messages -->
            <?php foreach ($success as $msg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($msg); ?></span>
            </div>
            <?php endforeach; ?>
            
            <div class="content">
                <?php if ($step === 'welcome'): ?>
                    <!-- Willkommen -->
                    <h2><i class="fas fa-rocket"></i> Willkommen!</h2>
                    
                    <div class="info-box">
                        <h3>Voraussetzungen:</h3>
                        <ul>
                            <li>PHP 7.4 oder höher</li>
                            <li>MySQL/MariaDB 5.7 oder höher</li>
                            <li>PDO MySQL Erweiterung</li>
                            <li>Schreibrechte für Verzeichnisse</li>
                        </ul>
                    </div>
                    
                    <div class="info-box">
                        <h3>Was wird installiert:</h3>
                        <ul>
                            <li>Datenbank-Tabellen erstellen</li>
                            <li>Standard-Einstellungen einfügen</li>
                            <li>Admin-Benutzer anlegen</li>
                            <li>Upload-Verzeichnisse erstellen</li>
                            <li>Konfigurationsdatei generieren</li>
                        </ul>
                    </div>
                    
                    <div class="actions">
                        <a href="?step=database" class="btn btn-primary btn-block">
                            <i class="fas fa-arrow-right"></i> Installation starten
                        </a>
                    </div>
                    
                <?php elseif ($step === 'database'): ?>
                    <!-- Datenbank-Konfiguration -->
                    <h2><i class="fas fa-database"></i> Datenbank-Verbindung</h2>
                    
                    <p style="color: var(--text-secondary); margin-bottom: 20px;">
                        Gib deine Datenbank-Zugangsdaten ein. Die Datenbank wird automatisch erstellt, falls sie nicht existiert.
                    </p>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="db_host">Datenbank-Host</label>
                            <input type="text" id="db_host" name="db_host" value="localhost" required>
                            <small>Normalerweise "localhost"</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="db_name">Datenbank-Name</label>
                            <input type="text" id="db_name" name="db_name" value="pax_die_map" required>
                            <small>Name der zu erstellenden Datenbank</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="db_user">Benutzername</label>
                            <input type="text" id="db_user" name="db_user" value="root" required>
                            <small>MySQL-Benutzername</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="db_pass">Passwort</label>
                            <input type="password" id="db_pass" name="db_pass">
                            <small>MySQL-Passwort (leer lassen falls kein Passwort)</small>
                        </div>
                        
                        <div class="actions">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-check"></i> Verbindung testen & weiter
                            </button>
                        </div>
                    </form>
                    
                <?php elseif ($step === 'install'): ?>
                    <!-- Installation durchführen -->
                    <h2><i class="fas fa-cog"></i> Datenbank installieren</h2>
                    
                    <p style="color: var(--text-secondary); margin-bottom: 20px;">
                        Die Datenbank-Tabellen werden jetzt erstellt und mit Standard-Daten gefüllt.
                    </p>
                    
                    <form method="POST">
                        <div class="info-box">
                            <h3>Es werden erstellt:</h3>
                            <ul>
                                <li>Benutzer-Tabelle (users)</li>
                                <li>Gilden-Tabelle (guilds)</li>
                                <li>Marker-Tabelle (markers)</li>
                                <li>Marker-Typen (marker_types)</li>
                                <li>Hochgeladene Bilder (uploaded_images)</li>
                                <li>Einstellungen (settings)</li>
                            </ul>
                        </div>
                        
                        <div class="actions">
                            <a href="?step=database" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Zurück
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-download"></i> Jetzt installieren
                            </button>
                        </div>
                    </form>
                    
                <?php elseif ($step === 'admin'): ?>
                    <!-- Admin-User erstellen -->
                    <h2><i class="fas fa-user-shield"></i> Admin-Benutzer</h2>
                    
                    <p style="color: var(--text-secondary); margin-bottom: 20px;">
                        Erstelle deinen Administrator-Account für das Admin Control Panel.
                    </p>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="admin_user">Benutzername *</label>
                            <input type="text" id="admin_user" name="admin_user" required>
                            <small>Dein Admin-Benutzername</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_pass">Passwort *</label>
                            <input type="password" id="admin_pass" name="admin_pass" required>
                            <small>Mindestens 6 Zeichen</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_pass_confirm">Passwort wiederholen *</label>
                            <input type="password" id="admin_pass_confirm" name="admin_pass_confirm" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_email">E-Mail</label>
                            <input type="email" id="admin_email" name="admin_email">
                            <small>Optional</small>
                        </div>
                        
                        <div class="actions">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-user-plus"></i> Admin erstellen & weiter
                            </button>
                        </div>
                    </form>
                    
                <?php elseif ($step === 'config'): ?>
                    <!-- Konfiguration -->
                    <h2><i class="fas fa-wrench"></i> Konfiguration</h2>
                    
                    <p style="color: var(--text-secondary); margin-bottom: 20px;">
                        Upload-Verzeichnisse erstellen und Konfigurationsdatei generieren.
                    </p>
                    
                    <form method="POST">
                        <div class="info-box">
                            <h3>Folgende Schritte werden durchgeführt:</h3>
                            <ul>
                                <li>Upload-Verzeichnisse erstellen (/uploads/)</li>
                                <li>Unterverzeichnisse für Map, Logo und Marker</li>
                                <li>Konfigurationsdatei (includes/config.php) erstellen</li>
                                <li>Berechtigungen setzen</li>
                            </ul>
                        </div>
                        
                        <div class="actions">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-magic"></i> Konfiguration abschließen
                            </button>
                        </div>
                    </form>
                    
                <?php elseif ($step === 'complete'): ?>
                    <!-- Installation abgeschlossen -->
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    
                    <h2 style="text-align: center;">Installation erfolgreich!</h2>
                    
                    <p style="color: var(--text-secondary); margin-bottom: 20px; text-align: center;">
                        PAX DEI Map wurde erfolgreich installiert und ist einsatzbereit.
                    </p>
                    
                    <div class="info-box">
                        <h3>Deine Login-Daten:</h3>
                        <ul>
                            <li>Benutzername: <code><?php echo htmlspecialchars($_SESSION['setup']['admin_user'] ?? 'admin'); ?></code></li>
                            <li>Passwort: <strong>Das von dir gewählte Passwort</strong></li>
                        </ul>
                    </div>
                    
                    <div class="info-box">
                        <h3>⚠️ Wichtige Sicherheitshinweise:</h3>
                        <ul>
                            <li><strong>Lösche diese setup.php Datei!</strong></li>
                            <li>Setze in <code>includes/config.php</code> für Produktion:<br>
                                <code>error_reporting(0);</code><br>
                                <code>ini_set('display_errors', 0);</code>
                            </li>
                            <li>Verwende HTTPS in Produktion</li>
                        </ul>
                    </div>
                    
                    <div class="info-box">
                        <h3>Nächste Schritte:</h3>
                        <ul>
                            <li>Im Admin Panel einloggen</li>
                            <li>Logo hochladen (Einstellungen)</li>
                            <li>Map-Hintergrundbild hochladen</li>
                            <li>Gilden anlegen</li>
                            <li>Marker platzieren</li>
                        </ul>
                    </div>
                    
                    <div class="completion-links">
                        <a href="index.php" class="btn btn-primary btn-block">
                            <i class="fas fa-map"></i> Zur Map-Ansicht
                        </a>
                        <a href="admin/login.php" class="btn btn-secondary btn-block">
                            <i class="fas fa-shield-alt"></i> Zum Admin Panel
                        </a>
                    </div>
                    
                    <?php
                    // Session aufräumen
                    unset($_SESSION['setup']);
                    ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
