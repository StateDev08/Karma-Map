<?php
$currentMapImage = getSetting('map_image', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!Auth::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültiges CSRF-Token';
    } else {
        try {
            $uploadedFile = handleImageUpload('map_image', 'map');
            setSetting('map_image', $uploadedFile['url']);
            $currentMapImage = $uploadedFile['url'];
            $success = 'Map-Bild erfolgreich hochgeladen!';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<div class="page-header">
    <h1><i class="fas fa-image"></i> Map-Hintergrundbild hochladen</h1>
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

<div class="content-section">
    <h2>Aktuelles Map-Bild</h2>
    
    <?php if ($currentMapImage): ?>
        <div class="current-map-preview">
            <img src="<?php echo e($currentMapImage); ?>" alt="Current Map">
            <p class="map-info">
                <i class="fas fa-info-circle"></i>
                Aktuell verwendetes Map-Bild
            </p>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Noch kein Map-Bild hochgeladen. Die Karte wird mit einem Standard-Hintergrund angezeigt.
        </div>
    <?php endif; ?>
</div>

<div class="content-section">
    <h2>Neues Map-Bild hochladen</h2>
    
    <div class="upload-info">
        <h3><i class="fas fa-lightbulb"></i> Hinweise:</h3>
        <ul>
            <li>Erlaubte Formate: JPG, PNG, GIF, WebP</li>
            <li>Maximale Dateigröße: <?php echo (MAX_UPLOAD_SIZE / 1024 / 1024); ?> MB</li>
            <li>Empfohlene Auflösung: Mindestens 2000x2000 Pixel für beste Qualität</li>
            <li>Das hochgeladene Bild ersetzt das aktuelle Map-Bild</li>
        </ul>
    </div>
    
    <form method="POST" enctype="multipart/form-data" class="upload-form">
        <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCsrfToken(); ?>">
        <input type="hidden" name="action" value="upload">
        
        <div class="file-upload-area">
            <input type="file" id="map_image" name="map_image" accept="image/*" required>
            <label for="map_image" class="file-upload-label">
                <i class="fas fa-cloud-upload-alt"></i>
                <span>Datei auswählen oder hierher ziehen</span>
                <small>JPG, PNG, GIF oder WebP</small>
            </label>
            <div id="file-preview" class="file-preview"></div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-upload"></i> Map hochladen
            </button>
        </div>
    </form>
</div>

<script>
const fileInput = document.getElementById('map_image');
const filePreview = document.getElementById('file-preview');

fileInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            filePreview.innerHTML = `
                <div class="preview-container">
                    <img src="${e.target.result}" alt="Preview">
                    <div class="preview-info">
                        <strong>${file.name}</strong>
                        <span>${(file.size / 1024 / 1024).toFixed(2)} MB</span>
                    </div>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    }
});
</script>
