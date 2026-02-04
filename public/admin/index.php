<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PAX Die Map</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php
    // Dynamischer Pfad zu includes
    $includesPath = file_exists(__DIR__ . '/../../includes/config.php') 
        ? __DIR__ . '/../../includes/' 
        : (file_exists(__DIR__ . '/../../../includes/config.php') 
            ? __DIR__ . '/../../../includes/' 
            : __DIR__ . '/../../includes/');
    
    require_once $includesPath . 'config.php';
    require_once $includesPath . 'db.php';
    require_once $includesPath . 'auth.php';
    require_once $includesPath . 'functions.php';
    
    Auth::requireAdmin();
    
    $currentPage = $_GET['page'] ?? 'dashboard';
    ?>
    
    <div class="admin-layout">
        <!-- Admin Sidebar -->
        <div class="admin-sidebar">
            <div class="admin-header">
                <h2><i class="fas fa-shield-alt"></i> KARMA ACP</h2>
                <p class="admin-user">
                    <i class="fas fa-user-shield"></i> <?php echo e(Auth::username()); ?>
                </p>
            </div>
            
            <nav class="admin-nav">
                <a href="?page=dashboard" class="nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="?page=markers" class="nav-item <?php echo $currentPage === 'markers' ? 'active' : ''; ?>">
                    <i class="fas fa-map-marker-alt"></i> Marker verwalten
                </a>
                <a href="?page=guilds" class="nav-item <?php echo $currentPage === 'guilds' ? 'active' : ''; ?>">
                    <i class="fas fa-shield-alt"></i> Gilden verwalten
                </a>
                <a href="?page=marker-types" class="nav-item <?php echo $currentPage === 'marker-types' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i> Marker-Typen
                </a>
                <a href="?page=map-upload" class="nav-item <?php echo $currentPage === 'map-upload' ? 'active' : ''; ?>">
                    <i class="fas fa-image"></i> Map hochladen
                </a>
                <a href="?page=settings" class="nav-item <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> Einstellungen
                </a>
                
                <div class="nav-divider"></div>
                
                <a href="../" class="nav-item" target="_blank">
                    <i class="fas fa-external-link-alt"></i> Zur Map
                </a>
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="admin-content">
            <?php
            $pagePath = __DIR__ . '/pages/' . $currentPage . '.php';
            if (file_exists($pagePath)) {
                include $pagePath;
            } else {
                include __DIR__ . '/pages/dashboard.php';
            }
            ?>
        </div>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>
