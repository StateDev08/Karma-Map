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
    'map_min_zoom' => getSetting('map_min_zoom', '1')
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
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> Alle Einstellungen speichern
        </button>
    </div>
</form>
