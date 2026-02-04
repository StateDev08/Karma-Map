<?php
require_once __DIR__ . '/../../includes/tile-generator.php';

$currentMapImage = getSetting('map_image', '');
$tileGenerator = new TileGenerator();
$tileMetadata = $tileGenerator->getMetadata();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!Auth::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültiges CSRF-Token';
    } else {
        try {
            if ($_POST['action'] === 'upload') {
                $uploadedFile = handleImageUpload('map_image', 'map');
                setSetting('map_image', $uploadedFile['url']);
                $currentMapImage = $uploadedFile['url'];
                
                // Automatisch Tiles generieren falls aktiviert
                if (isset($_POST['generate_tiles']) && $_POST['generate_tiles'] === '1') {
                    $tileResult = $tileGenerator->generateTiles($uploadedFile['path']);
                    if ($tileResult['success']) {
                        setSetting('use_tiles', '1');
                        $tileMetadata = $tileResult['metadata'];
                        $success = 'Map-Bild hochgeladen und ' . $tileResult['tiles'] . ' Tiles generiert!';
                    } else {
                        $success = 'Map-Bild hochgeladen, aber Tile-Generierung fehlgeschlagen: ' . $tileResult['error'];
                    }
                } else {
                    setSetting('use_tiles', '0');
                    $success = 'Map-Bild erfolgreich hochgeladen!';
                }
            } elseif ($_POST['action'] === 'generate_tiles') {
                // Tiles aus bestehendem Bild generieren
                if ($currentMapImage) {
                    $imagePath = str_replace(getBaseUrl() . '/', '', $currentMapImage);
                    $tileResult = $tileGenerator->generateTiles($imagePath);
                    if ($tileResult['success']) {
                        setSetting('use_tiles', '1');
                        $tileMetadata = $tileResult['metadata'];
                        $success = $tileResult['tiles'] . ' Tiles erfolgreich generiert!';
                    } else {
                        $error = 'Tile-Generierung fehlgeschlagen: ' . $tileResult['error'];
                    }
                } else {
                    $error = 'Kein Map-Bild vorhanden zum Generieren von Tiles';
                }
            } elseif ($_POST['action'] === 'disable_tiles') {
                setSetting('use_tiles', '0');
                $success = 'Tile-System deaktiviert. Verwende Standard-Bild.';
            }
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
                <?php if ($tileMetadata): ?>
                    <br>
                    <i class="fas fa-th"></i> <strong>Tile-System aktiv:</strong> 
                    <?php echo $tileMetadata['tilesGenerated']; ?> Tiles, 
                    generiert am <?php echo date('d.m.Y H:i', strtotime($tileMetadata['generatedAt'])); ?>
                <?php endif; ?>
            </p>
        </div>
        
        <?php if ($tileMetadata): ?>
        <div class="tile-status">
            <h3><i class="fas fa-check-circle"></i> Tile-System ist aktiviert</h3>
            <div class="tile-info">
                <p><strong>Auflösung:</strong> <?php echo $tileMetadata['sourceWidth']; ?> x <?php echo $tileMetadata['sourceHeight']; ?> px</p>
                <p><strong>Tile-Größe:</strong> <?php echo $tileMetadata['tileSize']; ?> x <?php echo $tileMetadata['tileSize']; ?> px</p>
                <p><strong>Zoom-Stufen:</strong> <?php echo $tileMetadata['minZoom']; ?> bis <?php echo $tileMetadata['maxZoom']; ?></p>
                <p><strong>Anzahl Tiles:</strong> <?php echo $tileMetadata['tilesGenerated']; ?></p>
            </div>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="disable_tiles">
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-times"></i> Tile-System deaktivieren (Standard-Bild verwenden)
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="tile-generate">
            <h3><i class="fas fa-magic"></i> Tiles für bessere Qualität generieren</h3>
            <p>Generiere ein Tile-System wie bei Google Maps für pixelfreies Zoomen!</p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="generate_tiles">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-th"></i> Tiles aus aktuellem Bild generieren
                </button>
            </form>
        </div>
        <?php endif; ?>
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
            <li><strong>Neu:</strong> Tiles können automatisch beim Upload generiert werden für pixelfreies Zoomen</li>
        </ul>
    </div>
    
    <form method="POST" enctype="multipart/form-data" class="upload-form">
        <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCsrfToken(); ?>">
        <input type="hidden" name="action" value="upload">
        
        <div class="form-group" style="margin-bottom: 20px;">
            <label style="display: flex; align-items: center; cursor: pointer;">
                <input type="checkbox" name="generate_tiles" value="1" checked 
                       style="margin-right: 10px; width: 20px; height: 20px;">
                <span>
                    <strong><i class="fas fa-th"></i> Automatisch Tiles generieren (empfohlen)</strong>
                    <br>
                    <small style="color: var(--text-muted);">
                        Aktiviere dies für pixelfreies Zoomen wie bei Google Maps. 
                        Die Generierung kann bei großen Bildern einige Sekunden dauern.
                    </small>
                </span>
            </label>
        </div>
        
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
