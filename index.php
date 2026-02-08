<?php
// Fehleranzeige für Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Includes laden
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Karma-Inhalte aus der Datenbank laden
function getKarmaContent($section) {
    try {
        return db()->fetchOne("SELECT * FROM karma_content WHERE section = ? AND is_visible = 1", [$section]);
    } catch (PDOException $e) {
        return null;
    }
}

function getAllKarmaContent() {
    try {
        return db()->fetchAll("SELECT * FROM karma_content WHERE is_visible = 1 ORDER BY sort_order ASC");
    } catch (PDOException $e) {
        return [];
    }
}

$karmaEnabled = getSetting('karma_enabled', '1') === '1';
$karmaContents = getAllKarmaContent();
$isAdmin = Auth::check();
$showMapLink = getSetting('karma_show_map_link', '1') === '1';
$karmaTheme = getSetting('karma_theme', 'dark');
$bgImage = getSetting('karma_background_image', '');
$heroOverlay = getSetting('karma_hero_overlay', '0.3');
$discordLink = getSetting('karma_discord_link', '');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e(getSetting('site_title', 'KARMA - PAX DEI')); ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    
    <!-- Custom CSS -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: <?php echo $karmaTheme === 'dark' ? '#fff' : '#333'; ?>;
            background: <?php echo $karmaTheme === 'dark' ? '#0a0a0a' : '#f4f4f4'; ?>;
        }
        
        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(0, 0, 0, 0.9);
            padding: 1rem 2rem;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .navbar .container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
            text-decoration: none;
        }
        
        .navbar .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }
        
        /* Mobile Menu */
        .nav-toggle {
            display: none;
            cursor: pointer;
            font-size: 1.5rem;
            color: #fff;
        }

        @media (max-width: 768px) {
            .nav-toggle {
                display: block;
            }
            .navbar .nav-links {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: rgba(0, 0, 0, 0.95);
                padding: 1rem;
                gap: 1rem;
                text-align: center;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }
            .navbar .nav-links.active {
                display: flex;
            }
            .hero-content img {
                max-width: 90% !important;
                height: auto !important;
            }
            .hero h1 {
                font-size: 2.5rem !important;
            }
            .hero p {
                font-size: 1.1rem !important;
            }
            .btn-neon {
                padding: 1rem 1.5rem !important;
                font-size: 1rem !important;
            }
            .section {
                padding: 3rem 1rem !important;
            }
            .section h2 {
                font-size: 1.8rem !important;
            }
        }
        
        .navbar .nav-links a {
            color: #fff;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .navbar .nav-links a:hover {
            color: <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
        }
        
        /* Hero Section */
        .hero {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            <?php if (!empty($bgImage)): ?>
            background: url('<?php echo e($bgImage); ?>') center/cover no-repeat;
            <?php else: ?>
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            <?php endif; ?>
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, <?php echo e($heroOverlay); ?>);
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            padding: 2rem;
        }
        
        .hero h1 {
            font-size: 5rem;
            margin-bottom: 1rem;
            color: <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
            text-shadow: 0 0 20px rgba(220, 20, 60, 0.5);
        }
        
        .hero p {
            font-size: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
            margin: 0.5rem;
        }
        
        .btn:hover {
            background: <?php echo e(getSetting('accent_color', '#FF0000')); ?>;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 20, 60, 0.4);
        }
        
        .btn-neon {
            background: #000;
            border: 3px solid <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
            color: #fff;
            padding: 1.2rem 3rem;
            font-size: 1.2rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            box-shadow: 0 0 20px rgba(220, 20, 60, 0.5),
                        0 0 40px rgba(220, 20, 60, 0.3),
                        inset 0 0 10px rgba(220, 20, 60, 0.2);
        }
        
        .btn-neon:hover {
            background: rgba(220, 20, 60, 0.1);
            box-shadow: 0 0 30px rgba(220, 20, 60, 0.8),
                        0 0 60px rgba(220, 20, 60, 0.6),
                        inset 0 0 20px rgba(220, 20, 60, 0.3);
            transform: translateY(-3px);
        }
        
        .btn-secondary {
            background: transparent;
            border: 2px solid <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
        }
        
        .btn-secondary:hover {
            background: <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
        }
        
        /* Content Sections */
        .section {
            padding: 5rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section:nth-child(even) {
            background: <?php echo $karmaTheme === 'dark' ? '#0f0f0f' : '#fff'; ?>;
        }
        
        .section h2 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
            text-align: center;
        }
        
        .section-content {
            font-size: 1.1rem;
            line-height: 1.8;
        }
        
        .section-content ul {
            list-style-position: inside;
            margin: 1rem 0;
        }
        
        .section-content li {
            margin: 0.5rem 0;
            padding-left: 1rem;
        }
        
        /* Footer */
        .footer {
            background: #000;
            color: #fff;
            text-align: center;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .footer a {
            color: <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
            text-decoration: none;
        }
        
        /* Admin Panel Link */
        .admin-panel {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1001;
        }
        
        .admin-panel a {
            display: block;
            padding: 1rem;
            background: <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
            color: #fff;
            text-decoration: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            transition: all 0.3s;
        }
        
        .admin-panel a:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 20px rgba(220, 20, 60, 0.5);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <?php
                $logoType = getSetting('logo_type', 'text');
                $logoText = getSetting('logo_text', 'KARMA');
                $logoImage = getSetting('logo_image', '');
                
                if ($logoType === 'text' || ($logoType === 'image' && empty($logoImage))) {
                    echo e($logoText);
                } else {
                    echo '<img src="' . e($logoImage) . '" alt="Logo" style="height: 40px;">';
                }
                ?>
            </a>
            <ul class="nav-links" id="navLinks">
                <li><a href="#home">Home</a></li>
                <?php if ($showMapLink): ?>
                <li><a href="map.php">Map</a></li>
                <?php endif; ?>
                <?php if (!empty($discordLink)): ?>
                <li><a href="<?php echo e($discordLink); ?>" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-discord"></i> Discord
                </a></li>
                <?php endif; ?>
                <?php if ($isAdmin): ?>
                <li><a href="admin/">Admin</a></li>
                <?php endif; ?>
            </ul>
            <div class="nav-toggle" id="navToggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <?php 
    $heroLogo = getSetting('karma_hero_logo', '');
    ?>
    <section class="hero" id="home">
        <div class="hero-content">
            <?php if (!empty($heroLogo)): ?>
                <img src="<?php echo e($heroLogo); ?>" alt="Logo" style="max-width: 600px; max-height: 400px; margin-bottom: 2rem;">
            <?php endif; ?>
            
            <div style="margin-top: 3rem;">
                <?php if ($showMapLink): ?>
                <a href="map.php" class="btn btn-neon">
                    <i class="fas fa-map"></i> Zur Map
                </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- Dynamic Content Sections -->
    <?php foreach ($karmaContents as $content): ?>
        <?php if ($content['section'] !== 'hero'): ?>
        <section class="section">
            <h2><?php echo e($content['title']); ?></h2>
            <div class="section-content">
                <?php echo $content['content']; ?>
            </div>
        </section>
        <?php endif; ?>
    <?php endforeach; ?>
    
    <!-- Footer -->
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> KARMA - PAX DEI. Alle Rechte vorbehalten.</p>
        <?php if ($isAdmin): ?>
        <p><a href="admin/?page=karma">Inhalte bearbeiten</a></p>
        <?php endif; ?>
    </footer>
    
    <!-- Admin Panel Link -->
    <?php if ($isAdmin): ?>
    <div class="admin-panel">
        <a href="admin/" title="Admin Panel">
            <i class="fas fa-cog fa-lg"></i>
        </a>
    </div>
    <?php endif; ?>

    <script>
        document.getElementById('navToggle').addEventListener('click', function() {
            document.getElementById('navLinks').classList.toggle('active');
            const icon = this.querySelector('i');
            if (icon.classList.contains('fa-bars')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });

        // Close menu when clicking a link
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                document.getElementById('navLinks').classList.remove('active');
                const icon = document.querySelector('.nav-toggle i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            });
        });
    </script>
</body>
</html>
