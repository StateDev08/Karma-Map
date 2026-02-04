<?php
$settings = [
    'site_title' => getSetting('site_title', 'PAX Die Map - KARMA'),
    'logo_type' => getSetting('logo_type', 'text'),
    'logo_text' => getSetting('logo_text', 'KARMA'),
    'logo_image' => getSetting('logo_image', ''),
    'primary_color' => getSetting('primary_color', '#FF0000'),
    'secondary_color' => getSetting('secondary_color', '#000000'),
    'accent_color' => getSetting('accent_color', '#DC143C'),
    'map_default_zoom' => getSetting('map_default_zoom', '2'),
    'map_max_zoom' => getSetting('map_max_zoom', '5'),
    'map_min_zoom' => getSetting('map_min_zoom', '1'),
    'map_show_coordinates' => getSetting('map_show_coordinates', '1'),
    'map_show_minimap' => getSetting('map_show_minimap', '1'),
    'map_enable_measure' => getSetting('map_enable_measure', '1'),
    'map_enable_drawing' => getSetting('map_enable_drawing', '1'),
    'map_enable_fullscreen' => getSetting('map_enable_fullscreen', '1'),
    'map_enable_search' => getSetting('map_enable_search', '1'),
    'map_default_position_x' => getSetting('map_default_position_x', '0'),
    'map_default_position_y' => getSetting('map_default_position_y', '0'),
    'map_grid_enabled' => getSetting('map_grid_enabled', '0'),
    'map_grid_size' => getSetting('map_grid_size', '100'),
    'map_mouse_coordinates' => getSetting('map_mouse_coordinates', '1'),
    'map_scale_control' => getSetting('map_scale_control', '1'),
    'map_zoom_animation' => getSetting('map_zoom_animation', '1'),
    'map_double_click_zoom' => getSetting('map_double_click_zoom', '1'),
    'map_scroll_wheel_zoom' => getSetting('map_scroll_wheel_zoom', '1'),
    'map_marker_clustering' => getSetting('map_marker_clustering', '0'),
    'map_auto_pan' => getSetting('map_auto_pan', '1')
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!Auth::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültiges CSRF-Token';
    } else {
        if ($_POST['action'] === 'save_settings') {
            setSetting('site_title', $_POST['site_title']);
            setSetting('logo_type', $_POST['logo_type']);
            setSetting('logo_text', $_POST['logo_text']);
            setSetting('primary_color', $_POST['primary_color']);
            setSetting('secondary_color', $_POST['secondary_color']);
            setSetting('accent_color', $_POST['accent_color']);
            setSetting('map_default_zoom', $_POST['map_default_zoom']);
            setSetting('map_max_zoom', $_POST['map_max_zoom']);
            setSetting('map_min_zoom', $_POST['map_min_zoom']);
            setSetting('map_show_coordinates', isset($_POST['map_show_coordinates']) ? '1' : '0');
            setSetting('map_show_minimap', isset($_POST['map_show_minimap']) ? '1' : '0');
            setSetting('map_enable_measure', isset($_POST['map_enable_measure']) ? '1' : '0');
            setSetting('map_enable_drawing', isset($_POST['map_enable_drawing']) ? '1' : '0');
            setSetting('map_enable_fullscreen', isset($_POST['map_enable_fullscreen']) ? '1' : '0');
            setSetting('map_enable_search', isset($_POST['map_enable_search']) ? '1' : '0');
            setSetting('map_default_position_x', $_POST['map_default_position_x']);
            setSetting('map_default_position_y', $_POST['map_default_position_y']);
            setSetting('map_grid_enabled', isset($_POST['map_grid_enabled']) ? '1' : '0');
            setSetting('map_grid_size', $_POST['map_grid_size']);
            setSetting('map_mouse_coordinates', isset($_POST['map_mouse_coordinates']) ? '1' : '0');
            setSetting('map_scale_control', isset($_POST['map_scale_control']) ? '1' : '0');
            setSetting('map_zoom_animation', isset($_POST['map_zoom_animation']) ? '1' : '0');
            setSetting('map_double_click_zoom', isset($_POST['map_double_click_zoom']) ? '1' : '0');
            setSetting('map_scroll_wheel_zoom', isset($_POST['map_scroll_wheel_zoom']) ? '1' : '0');
            setSetting('map_marker_clustering', isset($_POST['map_marker_clustering']) ? '1' : '0');
            setSetting('map_auto_pan', isset($_POST['map_auto_pan']) ? '1' : '0');
            
            // Logo-Upload
            if (isset($_FILES['logo_upload']) && $_FILES['logo_upload']['error'] === UPLOAD_ERR_OK) {
                try {
                    $uploadedLogo = handleImageUpload('logo_upload', 'logo');
                    setSetting('logo_image', $uploadedLogo['url']);
                    $settings['logo_image'] = $uploadedLogo['url'];
                } catch (Exception $e) {
                    $error = 'Logo-Upload: ' . $e->getMessage();
                }
            }
            
            if (!isset($error)) {
                $success = 'Einstellungen erfolgreich gespeichert!';
                // Reload settings
                foreach ($settings as $key => $value) {
                    $settings[$key] = getSetting($key, $value);
                }
            }
        }
    }
}
?>

<div class="page-header">
    <h1><i class="fas fa-cog"></i> Einstellungen</h1>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?php echo e($success); ?>
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i> <?php echo e($error); ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="settings-form">
    <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCsrfToken(); ?>">
    <input type="hidden" name="action" value="save_settings">
    
    <!-- Allgemeine Einstellungen -->
    <div class="content-section">
        <h2><i class="fas fa-info-circle"></i> Allgemeine Einstellungen</h2>
        
        <div class="form-group">
            <label for="site_title">Website-Titel</label>
            <input type="text" id="site_title" name="site_title" 
                   value="<?php echo e($settings['site_title']); ?>">
        </div>
    </div>
    
    <!-- Logo-Einstellungen -->
    <div class="content-section">
        <h2><i class="fas fa-image"></i> Logo-Einstellungen</h2>
        
        <div class="form-group">
            <label>Logo-Typ</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="logo_type" value="text" 
                           <?php echo $settings['logo_type'] === 'text' ? 'checked' : ''; ?>>
                    <span>Text-Logo</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="logo_type" value="image" 
                           <?php echo $settings['logo_type'] === 'image' ? 'checked' : ''; ?>>
                    <span>Bild-Logo</span>
                </label>
            </div>
        </div>
        
        <div class="form-group">
            <label for="logo_text">Text-Logo</label>
            <input type="text" id="logo_text" name="logo_text" 
                   value="<?php echo e($settings['logo_text']); ?>">
            <small>Wird angezeigt wenn "Text-Logo" ausgewählt ist</small>
        </div>
        
        <div class="form-group">
            <label for="logo_upload">Bild-Logo hochladen</label>
            <input type="file" id="logo_upload" name="logo_upload" accept="image/*">
            <?php if ($settings['logo_image']): ?>
                <div class="logo-preview">
                    <img src="<?php echo e($settings['logo_image']); ?>" alt="Current Logo">
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Farbschema -->
    <div class="content-section">
        <h2><i class="fas fa-palette"></i> Farbschema</h2>
        
        <div class="form-row">
            <div class="form-group">
                <label for="primary_color">Primärfarbe (Rot)</label>
                <input type="color" id="primary_color" name="primary_color" 
                       value="<?php echo e($settings['primary_color']); ?>">
            </div>
            
            <div class="form-group">
                <label for="secondary_color">Sekundärfarbe (Schwarz)</label>
                <input type="color" id="secondary_color" name="secondary_color" 
                       value="<?php echo e($settings['secondary_color']); ?>">
            </div>
            
            <div class="form-group">
                <label for="accent_color">Akzentfarbe</label>
                <input type="color" id="accent_color" name="accent_color" 
                       value="<?php echo e($settings['accent_color']); ?>">
            </div>
        </div>
        
        <div class="color-preview-bar">
            <div class="color-sample" style="background: <?php echo e($settings['primary_color']); ?>">Primär</div>
            <div class="color-sample" style="background: <?php echo e($settings['secondary_color']); ?>; color: #fff;">Sekundär</div>
            <div class="color-sample" style="background: <?php echo e($settings['accent_color']); ?>">Akzent</div>
        </div>
    </div>
    
    <!-- Map-Einstellungen -->
    <div class="content-section">
        <h2><i class="fas fa-map"></i> Map-Einstellungen</h2>
        
        <div class="form-row">
            <div class="form-group">
                <label for="map_default_zoom">Standard-Zoom</label>
                <input type="number" id="map_default_zoom" name="map_default_zoom" 
                       min="1" max="10" value="<?php echo e($settings['map_default_zoom']); ?>">
            </div>
            
            <div class="form-group">
                <label for="map_max_zoom">Maximaler Zoom</label>
                <input type="number" id="map_max_zoom" name="map_max_zoom" 
                       min="1" max="10" value="<?php echo e($settings['map_max_zoom']); ?>">
            </div>
            
            <div class="form-group">
                <label for="map_min_zoom">Minimaler Zoom (negative Werte erlauben weiter herauszoomen)</label>
                <input type="number" id="map_min_zoom" name="map_min_zoom" 
                       value="<?php echo e($settings['map_min_zoom']); ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="map_default_position_x">Standard-Position X</label>
                <input type="number" id="map_default_position_x" name="map_default_position_x" 
                       step="0.1" value="<?php echo e($settings['map_default_position_x']); ?>">
                <small>X-Koordinate der Startposition</small>
            </div>
            
            <div class="form-group">
                <label for="map_default_position_y">Standard-Position Y</label>
                <input type="number" id="map_default_position_y" name="map_default_position_y" 
                       step="0.1" value="<?php echo e($settings['map_default_position_y']); ?>">
                <small>Y-Koordinate der Startposition</small>
            </div>
            
            <div class="form-group">
                <label for="map_grid_size">Raster-Größe (Pixel)</label>
                <input type="number" id="map_grid_size" name="map_grid_size" 
                       min="10" max="500" value="<?php echo e($settings['map_grid_size']); ?>">
                <small>Größe der Raster-Quadrate</small>
            </div>
        </div>
        
        <h3 style="margin-top: 20px;"><i class="fas fa-tools"></i> Map-Werkzeuge</h3>
        <div class="checkbox-grid">
            <label class="checkbox-label">
                <input type="checkbox" name="map_show_coordinates" value="1" 
                       <?php echo $settings['map_show_coordinates'] === '1' ? 'checked' : ''; ?>>
                <span><i class="fas fa-compass"></i> Koordinaten-Anzeige</span>
            </label>
            
            <label class="checkbox-label">
                <input type="checkbox" name="map_show_minimap" value="1" 
                       <?php echo $settings['map_show_minimap'] === '1' ? 'checked' : ''; ?>>
                <span><i class="fas fa-map-marked"></i> Mini-Map</span>
            </label>
            
            <label class="checkbox-label">
                <input type="checkbox" name="map_enable_measure" value="1" 
                       <?php echo $settings['map_enable_measure'] === '1' ? 'checked' : ''; ?>>
                <span><i class="fas fa-ruler"></i> Messwerkzeug</span>
            </label>
            
            <label class="checkbox-label">
                <input type="checkbox" name="map_enable_drawing" value="1" 
                       <?php echo $settings['map_enable_drawing'] === '1' ? 'checked' : ''; ?>>
                <span><i class="fas fa-pencil-alt"></i> Zeichenwerkzeuge</span>
            </label>
            
            <label class="checkbox-label">
                <input type="checkbox" name="map_enable_fullscreen" value="1" 
                       <?php echo $settings['map_enable_fullscreen'] === '1' ? 'checked' : ''; ?>>
                <span><i class="fas fa-expand"></i> Vollbild-Modus</span>
            </label>
            
            <label class="checkbox-label">
                <input type="checkbox" name="map_enable_search" value="1" 
                       <?php echo $settings['map_enable_search'] === '1' ? 'checked' : ''; ?>>
                <span><i class="fas fa-search-location"></i> Koordinaten-Suche</span>
            </label>
            
            <label class="checkbox-label">
                <input type="checkbox" name="map_grid_enabled" value="1" 
                       <?php echo $settings['map_grid_enabled'] === '1' ? 'checked' : ''; ?>>
                <span><i class="fas fa-th"></i> Raster-Overlay</span>
            </label>
            
            <label class="checkbox-label">
                <input type="checkbox" name="map_mouse_coordinates" value="1" 
                       <?php echo $settings['map_mouse_coordinates'] === '1' ? 'checked' : ''; ?>>
                <span><i class="fas fa-mouse-pointer"></i> Live Maus-Koordinaten</span>
            </label>
            
            <label class="checkbox-label">
                <input type="checkbox" name="map_scale_control" value="1" 
                       <?php echo $settings['map_scale_control'] === '1' ? 'checked' : ''; ?>>
                <span><i class="fas fa-arrows-alt-h"></i> Maßstabsleiste</span>
            </label>
        </div>
        
        <h3 style="margin-top: 20px;"><i class="fas fa-sliders-h"></i> Interaktions-Einstellungen</h3>
        <div class="checkbox-grid">
            <label class="checkbox-label">
                <input type="checkbox" name="map_zoom_animation" value="1" 
                       <?php echo $settings['map_zoom_animation'] === '1' ? 'checked' : ''; ?>>
                <span><i class="fas fa-magic"></i> Zoom-Animation</span>
            </label>
            
            <label class="checkbox-label">
                <input type="checkbox" name="map_double_click_zoom" value="1" 
                       <?php echo $settings['map_double_click_zoom'] === '1' ? 'checked' : ''; ?>>
                <span><i class="fas fa-hand-pointer"></i> Doppelklick-Zoom</span>
            </label>
            
            <label class="checkbox-label">
                <input type="checkbox" name="map_scroll_wheel_zoom" value="1" 
                       <?php echo $settings['map_scroll_wheel_zoom'] === '1' ? 'checked' : ''; ?>>
                <span><i class="fas fa-mouse"></i> Mausrad-Zoom</span>
            </label>
            
            <label class="checkbox-label">
                <input type="checkbox" name="map_marker_clustering" value="1" 
                       <?php echo $settings['map_marker_clustering'] === '1' ? 'checked' : ''; ?>>
                <span><i class="fas fa-layer-group"></i> Marker-Clustering</span>
            </label>
            
            <label class="checkbox-label">
                <input type="checkbox" name="map_auto_pan" value="1" 
                       <?php echo $settings['map_auto_pan'] === '1' ? 'checked' : ''; ?>>
                <span><i class="fas fa-arrows-alt"></i> Auto-Pan bei Marker-Klick</span>
            </label>
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> Alle Einstellungen speichern
        </button>
    </div>
</form>
