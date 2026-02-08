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
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <title><?php echo e(getSetting('site_title', 'PAX DEI Map')); ?></title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Filter öffnen">
        <i class="fas fa-bars"></i>
        <span class="filter-badge" id="filterBadge"></span>
    </button>
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <?php
            $logoType = getSetting('logo_type', 'text');
            $logoText = getSetting('logo_text', 'KARMA');
            $logoImage = getSetting('logo_image', '');
            ?>
            
            <?php if ($logoType === 'text' || ($logoType === 'image' && empty($logoImage))): ?>
                <h1 class="logo-text"><?php echo e($logoText); ?></h1>
            <?php else: ?>
                <img src="<?php echo e($logoImage); ?>" alt="Logo" class="logo-image">
            <?php endif; ?>
            
            <p class="subtitle" style="color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px;">PAX DEI Map</p>
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
        
        <div class="sidebar-footer" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 0.8rem;">
            <button type="button" class="btn btn-primary sidebar-close-mobile" id="sidebarCloseMobile" aria-label="Filter schließen">
                <i class="fas fa-check"></i> Übernehmen
            </button>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Zur Startseite
            </a>
            <?php if ($isAdmin): ?>
                <a href="admin/" class="btn btn-primary">
                    <i class="fas fa-cog"></i> Admin Panel
                </a>
                <a href="admin/logout.php" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            <?php else: ?>
                <a href="admin/login.php" class="btn btn-secondary">
                    <i class="fas fa-sign-in-alt"></i> Admin Login
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div id="map"></div>
        
        <!-- Map Status & Fit Bounds -->
        <?php if (getSetting('map_show_status', '1') === '1'): ?>
        <div class="map-status" id="mapStatus" aria-live="polite">
            <span class="map-status-text">Lade Marker…</span>
            <button type="button" class="map-fit-bounds-btn" title="Alle sichtbaren Marker in den Ausschnitt zoomen" aria-label="Karte auf alle Marker zentrieren">
                <i class="fas fa-expand-alt"></i>
            </button>
        </div>
        <?php endif; ?>
        
        <!-- Legend -->
        <?php if (getSetting('map_show_legend', '1') === '1'): ?>
        <div class="map-legend" id="mapLegend">
            <button type="button" class="map-legend-toggle" id="legendToggle" aria-label="Legende ein-/ausblenden">
                <i class="fas fa-map-marker-alt"></i>
                <span>Legende</span>
                <i class="fas fa-chevron-down legend-chevron"></i>
            </button>
            <div class="legend-items-wrapper">
            <h4 class="legend-title"><i class="fas fa-map-marker-alt"></i> Legende</h4>
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
    </div>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Leaflet Plugins -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.Default.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.js"></script>
    
    <link rel="stylesheet" href="https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/leaflet.fullscreen.css" />
    <script src="https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/Leaflet.fullscreen.min.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet-minimap/3.6.1/Control.MiniMap.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-minimap/3.6.1/Control.MiniMap.min.js"></script>
    
    <!-- html2canvas für Screenshots (optional) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <!-- Custom JS -->
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
    </script>
    <script src="assets/js/map.js"></script>
    <script src="assets/js/map-extended.js"></script>
    <script>
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
            document.body.classList.add('sidebar-open');
        }
        function closeSidebar() {
            if (sidebar) sidebar.classList.remove('sidebar-open');
            if (backdrop) backdrop.classList.remove('active');
            document.body.classList.remove('sidebar-open');
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
        updateFilterBadge();
    })();
    </script>
</body>
</html>
