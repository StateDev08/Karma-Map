<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - PAX Die Map</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="login-page">
    <?php
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/functions.php';
    
    // Wenn bereits eingeloggt, redirect zum Dashboard
    if (Auth::check()) {
        header('Location: index.php');
        exit;
    }
    
    $error = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (Auth::login($username, $password)) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Ungültiger Benutzername oder Passwort';
        }
    }
    ?>
    
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1><i class="fas fa-shield-alt"></i> KARMA</h1>
                <p>Admin Control Panel</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo e($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Benutzername
                    </label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Passwort
                    </label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Einloggen
                </button>
            </form>
            
            <div class="login-footer">
                <a href="../" class="back-link">
                    <i class="fas fa-arrow-left"></i> Zurück zur Map
                </a>
            </div>
        </div>
    </div>
</body>
</html>
