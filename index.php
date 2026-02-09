<?php
// Fehleranzeige für Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Includes laden
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$guilds = getActiveGuilds();
$markerTypes = getMarkerTypes();
$isAdmin = Auth::check();

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
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
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
            font-family: 'Open Sans', sans-serif;
            line-height: 1.6;
            color: <?php echo $karmaTheme === 'dark' ? '#fff' : '#333'; ?>;
            background: <?php echo $karmaTheme === 'dark' ? '#0a0a0a' : '#f4f4f4'; ?>;
            overflow-y: auto !important;
            height: auto !important;
            min-height: 100vh;
        }

        /* Map Integration Styles */
        #map-section {
            position: relative;
            height: 80vh;
            min-height: 600px;
            width: 100%;
            background: #000;
            overflow: hidden;
            border-top: 2px solid <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
            border-bottom: 2px solid <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
        }

        #map {
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        /* Sidebar Toggle – nur auf Mobile (Filter ist Desktop dauerhaft sichtbar) */
        .sidebar-toggle {
            display: none;
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 1000;
            width: 50px;
            height: 50px;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
            color: #fff;
            border-radius: 8px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            backdrop-filter: blur(5px);
            transition: all 0.3s;
        }

        .sidebar-toggle:hover {
            background: <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
        }

        .filter-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
            color: #fff;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: none;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .filter-badge.active {
            display: flex;
        }

        /* Sidebar Styles – dauerhaft sichtbar */
        .sidebar {
            position: absolute;
            left: 0;
            top: 0;
            width: 320px;
            height: 100%;
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(15px);
            z-index: 1001;
            transition: left 0.3s ease, transform 0.25s ease-out;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            overflow-y: auto;
        }

        .sidebar.sidebar-open {
            left: 0;
        }

        /* Karte neben Sidebar (Filter dauerhaft sichtbar) */
        #map-section #map {
            margin-left: 320px;
            width: calc(100% - 320px);
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .sidebar-section {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-section h3 {
            font-size: 1rem;
            margin-bottom: 1rem;
            color: <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-item {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .filter-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .sidebar-footer {
            padding: 1.5rem;
            margin-top: auto;
        }

        /* Map Status & Legend over Map */
        .map-status {
            position: absolute;
            bottom: 20px;
            left: 20px;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.8);
            padding: 8px 15px;
            border-radius: 20px;
            border: 1px solid <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
            color: #fff;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(5px);
        }

        .map-fit-bounds-btn {
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.2s;
        }

        .map-fit-bounds-btn:hover {
            transform: scale(1.2);
            color: <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
        }

        .map-legend {
            position: absolute;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 12px;
            border: 1px solid <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
            min-width: 180px;
            backdrop-filter: blur(5px);
            overflow: hidden;
        }

        .map-legend-toggle {
            width: 100%;
            padding: 10px 15px;
            background: none;
            border: none;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            font-weight: bold;
        }

        .legend-items-wrapper {
            padding: 0 15px 15px;
            max-height: 300px;
            overflow-y: auto;
        }

        .map-legend.legend-collapsed .legend-items-wrapper {
            display: none;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 8px;
            font-size: 13px;
        }

        /* Leaflet Popups */
        .custom-popup .leaflet-popup-content-wrapper {
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
            border: 1px solid <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
            border-radius: 8px;
        }
        .custom-popup .leaflet-popup-tip {
            background: rgba(0, 0, 0, 0.9);
            border: 1px solid <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
        }

        .marker-popup h3 {
            color: <?php echo e(getSetting('primary_color', '#DC143C')); ?>;
            margin-bottom: 8px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            font-size: 12px;
        }

        .guild-badge {
            padding: 2px 5px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }

        .guild-tag {
            font-weight: bold;
            padding: 0 3px;
            border-radius: 2px;
        }

        /* AOS Animation Delay */
        [data-aos] {
            pointer-events: none;
        }
        .aos-animate {
            pointer-events: auto;
        }

        .sidebar-backdrop {
            display: none;
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .sidebar-backdrop.active {
            display: block;
        }

        h1, h2, h3, .logo {
            font-family: 'Montserrat', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            z-index: 1000;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
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
            /* Mobile: Filter per Toggle einblendbar, Karte volle Breite */
            .sidebar-toggle {
                display: flex;
            }
            #map-section #map {
                margin-left: 0;
                width: 100%;
            }
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.sidebar-open {
                transform: translateX(0);
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
            text-shadow: 0 0 20px rgba(220, 20, 60, 0.5), 0 0 40px rgba(220, 20, 60, 0.3);
            animation: fadeInDown 1s ease-out;
        }
        
        .hero p {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            animation: fadeInUp 1s ease-out 0.3s backwards;
        }

        .hero .hero-scroll-hint {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
            margin: 0;
            font-size: 2.6rem;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #fff;
            text-shadow: 0 0 10px rgba(255,255,255,0.5);
            animation: hero-blink 1s ease-in-out infinite;
            -webkit-animation: hero-blink 1s ease-in-out infinite;
        }

        @keyframes hero-blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }
        @-webkit-keyframes hero-blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
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
            padding: 8rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
        }
        
        .section:nth-child(even) {
            background: <?php echo $karmaTheme === 'dark' ? 'rgba(255,255,255,0.03)' : 'rgba(0,0,0,0.02)'; ?>;
            border-radius: 20px;
            margin-top: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(5px);
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
                <li><a href="#map-section">Map</a></li>
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
            
        </div>
        <p class="hero-scroll-hint">Nach unten scrollen</p>
    </section>

    <!-- Map Section -->
    <section id="map-section">
        <!-- Leaflet CSS -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        
        <!-- Mobile Menu Toggle -->
        <div class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-filter"></i>
            <span class="filter-badge" id="filterBadge"></span>
        </div>
        
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>MAP FILTER</h3>
            </div>
            
            <div class="sidebar-section">
                <h3><i class="fas fa-filter"></i> Marker-Filter</h3>
                <div class="filter-group">
                    <label class="filter-item">
                        <input type="checkbox" id="toggleAll" checked>
                        <span>Alle anzeigen</span>
                    </label>
                </div>
                
                <div class="filter-group" id="markerTypeFilters">
                    <?php foreach ($markerTypes as $type): ?>
                    <label class="filter-item">
                        <input type="checkbox" class="marker-type-filter" 
                               data-type-id="<?php echo $type['id']; ?>" checked>
                        <span style="color: <?php echo e($type['color']); ?>">
                            <i class="fas fa-<?php echo e($type['icon']); ?>"></i>
                            <?php echo e($type['name']); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="sidebar-section">
                <h3><i class="fas fa-shield-alt"></i> Gilden-Filter</h3>
                <div class="filter-group">
                    <label class="filter-item">
                        <input type="checkbox" id="toggleAllGuilds" checked>
                        <span>Alle Gilden</span>
                    </label>
                </div>
                <div class="filter-group" id="guildFilters">
                    <?php foreach ($guilds as $guild): ?>
                    <label class="filter-item">
                        <input type="checkbox" class="guild-filter" 
                               data-guild-id="<?php echo $guild['id']; ?>" checked>
                        <span style="color: <?php echo e($guild['color']); ?>">
                            [<?php echo e($guild['tag']); ?>] <?php echo e($guild['name']); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="sidebar-footer">
                <button type="button" class="btn btn-primary btn-block" id="sidebarCloseMobile">
                    <i class="fas fa-check"></i> Filter schließen
                </button>
            </div>
        </div>
        
        <!-- Map Container -->
        <div id="map"></div>
        
        <!-- Map Status -->
        <?php if (getSetting('map_show_status', '1') === '1'): ?>
        <div class="map-status" id="mapStatus">
            <span class="map-status-text">Lade Marker...</span>
            <button type="button" class="map-fit-bounds-btn" title="Auf alle Marker zentrieren">
                <i class="fas fa-expand-alt"></i>
            </button>
        </div>
        <?php endif; ?>
        
        <!-- Legend -->
        <?php if (getSetting('map_show_legend', '1') === '1'): ?>
        <div class="map-legend" id="mapLegend">
            <button type="button" class="map-legend-toggle" id="legendToggle">
                <span><i class="fas fa-map-marker-alt"></i> Legende</span>
                <i class="fas fa-chevron-down legend-chevron"></i>
            </button>
            <div class="legend-items-wrapper">
                <div class="legend-items">
                    <?php foreach ($markerTypes as $type): ?>
                    <div class="legend-item">
                        <i class="fas fa-<?php echo e($type['icon']); ?>" 
                           style="color: <?php echo e($type['color']); ?>"></i>
                        <span><?php echo e($type['name']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
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

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.Default.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.js"></script>
    <link rel="stylesheet" href="https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/leaflet.fullscreen.css" />
    <script src="https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/Leaflet.fullscreen.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet-minimap/3.6.1/Control.MiniMap.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-minimap/3.6.1/Control.MiniMap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <script src="assets/js/map.js"></script>
    <script src="assets/js/map-extended.js"></script>

    <script>
        // Map-Daten an JavaScript übergeben
        window.mapConfig = {
            mapImage: <?php echo json_encode(getSetting('map_image', '')); ?>,
            defaultZoom: <?php echo json_encode((int)getSetting('map_default_zoom', 2)); ?>,
            maxZoom: <?php echo json_encode((int)getSetting('map_max_zoom', 5)); ?>,
            minZoom: <?php echo json_encode((int)getSetting('map_min_zoom', -10)); ?>,
            defaultPositionX: <?php echo json_encode((float)getSetting('map_default_position_x', 0)); ?>,
            defaultPositionY: <?php echo json_encode((float)getSetting('map_default_position_y', 0)); ?>,
            showCoordinates: <?php echo json_encode(getSetting('map_show_coordinates', '1') === '1'); ?>,
            showMinimap: <?php echo json_encode(getSetting('map_show_minimap', '1') === '1'); ?>,
            enableMeasure: <?php echo json_encode(getSetting('map_enable_measure', '1') === '1'); ?>,
            enableDrawing: <?php echo json_encode(getSetting('map_enable_drawing', '1') === '1'); ?>,
            enableFullscreen: <?php echo json_encode(getSetting('map_enable_fullscreen', '1') === '1'); ?>,
            enableSearch: <?php echo json_encode(getSetting('map_enable_search', '1') === '1'); ?>,
            gridEnabled: <?php echo json_encode(getSetting('map_grid_enabled', '0') === '1'); ?>,
            gridSize: <?php echo json_encode((int)getSetting('map_grid_size', 100)); ?>,
            mouseCoordinates: <?php echo json_encode(getSetting('map_mouse_coordinates', '1') === '1'); ?>,
            scaleControl: <?php echo json_encode(getSetting('map_scale_control', '1') === '1'); ?>,
            zoomAnimation: <?php echo json_encode(getSetting('map_zoom_animation', '1') === '1'); ?>,
            doubleClickZoom: <?php echo json_encode(getSetting('map_double_click_zoom', '1') === '1'); ?>,
            scrollWheelZoom: <?php echo json_encode(getSetting('map_scroll_wheel_zoom', '1') === '1'); ?>,
            markerClustering: <?php echo json_encode(getSetting('map_marker_clustering', '0') === '1'); ?>,
            autoPan: <?php echo json_encode(getSetting('map_auto_pan', '1') === '1'); ?>,
            isAdmin: <?php echo json_encode($isAdmin); ?>,
            useTiles: <?php 
                $useTiles = getSetting('use_tiles', '0') === '1';
                $tileMetaFile = __DIR__ . '/uploads/tiles/metadata.json';
                echo json_encode($useTiles && file_exists($tileMetaFile)); 
            ?>,
            tileMetadata: <?php 
                if ($useTiles && file_exists($tileMetaFile)) {
                    echo file_get_contents($tileMetaFile);
                } else {
                    echo 'null';
                }
            ?>
        };

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
                if (icon) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
        });

        // Einmal nach unten scrollen = direkt zur Map springen (wenn oben im Hero)
        (function() {
            var mapSection = document.getElementById('map-section');
            if (!mapSection) return;
            var scrollThreshold = 120;
            function jumpToMap(e) {
                if (window.scrollY > scrollThreshold) return;
                if (e.deltaY <= 0) return;
                e.preventDefault();
                mapSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            window.addEventListener('wheel', jumpToMap, { passive: false });
        })();

        // Map UI Logic
        (function() {
            var sidebar = document.getElementById('sidebar');
            var toggle = document.getElementById('sidebarToggle');
            var backdrop = document.getElementById('sidebarBackdrop');
            var closeBtn = document.getElementById('sidebarCloseMobile');
            var legend = document.getElementById('mapLegend');
            var legendToggle = document.getElementById('legendToggle');
            var filterBadge = document.getElementById('filterBadge');

            function openSidebar() {
                if (sidebar) sidebar.classList.add('sidebar-open');
                if (backdrop) backdrop.classList.add('active');
            }
            function closeSidebar() {
                if (sidebar) sidebar.classList.remove('sidebar-open');
                if (backdrop) backdrop.classList.remove('active');
            }
            
            function updateFilterBadge() {
                if (!filterBadge) return;
                var typeChecked = document.querySelectorAll('.marker-type-filter:checked').length;
                var typeTotal = document.querySelectorAll('.marker-type-filter').length;
                var guildChecked = document.querySelectorAll('.guild-filter:checked').length;
                var guildTotal = document.querySelectorAll('.guild-filter').length;
                var active = (typeChecked < typeTotal || guildChecked < guildTotal);
                filterBadge.textContent = active ? (typeTotal - typeChecked + guildTotal - guildChecked) : '';
                filterBadge.classList.toggle('active', active);
            }

            if (toggle) toggle.addEventListener('click', openSidebar);
            if (backdrop) backdrop.addEventListener('click', closeSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
            
            document.querySelectorAll('.marker-type-filter, .guild-filter').forEach(function(el) {
                el.addEventListener('change', updateFilterBadge);
            });

            if (legendToggle && legend) {
                legendToggle.addEventListener('click', function() {
                    legend.classList.toggle('legend-collapsed');
                });
            }

            // Map Initialization with Intersection Observer
            if (typeof initMap === 'function') {
                const mapSection = document.getElementById('map-section');
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            if (!window.mapInitialized) {
                                initMap();
                                window.mapInitialized = true;
                            } else if (window.map) {
                                window.map.invalidateSize();
                            }
                        }
                    });
                }, { threshold: 0.1 });
                observer.observe(mapSection);
            }

            updateFilterBadge();
        })();
    </script>
</body>
</html>
