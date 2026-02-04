<?php
$markers = db()->fetchAll(
    "SELECT m.*, g.name as guild_name, g.tag as guild_tag, mt.name as type_name 
     FROM markers m 
     LEFT JOIN guilds g ON m.guild_id = g.id 
     LEFT JOIN marker_types mt ON m.marker_type_id = mt.id 
     ORDER BY m.created_at DESC"
);

$guilds = getActiveGuilds();
$markerTypes = getMarkerTypes();

// Marker hinzufügen/bearbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!Auth::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültiges CSRF-Token';
    } else {
        $action = $_POST['action'];
        
        if ($action === 'add' || $action === 'edit') {
            $data = [
                'name' => $_POST['name'],
                'description' => $_POST['description'] ?? '',
                'x_position' => (float)$_POST['x_position'],
                'y_position' => (float)$_POST['y_position'],
                'marker_type_id' => !empty($_POST['marker_type_id']) ? (int)$_POST['marker_type_id'] : null,
                'guild_id' => !empty($_POST['guild_id']) ? (int)$_POST['guild_id'] : null,
                'is_visible' => isset($_POST['is_visible']) ? 1 : 0
            ];
            
            if ($action === 'add') {
                $data['created_by'] = Auth::userId();
                db()->insert('markers', $data);
                $success = 'Marker erfolgreich hinzugefügt!';
            } else {
                $id = (int)$_POST['id'];
                db()->update('markers', $data, 'id = :id', ['id' => $id]);
                $success = 'Marker erfolgreich aktualisiert!';
            }
            
            // Reload markers
            $markers = db()->fetchAll(
                "SELECT m.*, g.name as guild_name, g.tag as guild_tag, mt.name as type_name 
                 FROM markers m 
                 LEFT JOIN guilds g ON m.guild_id = g.id 
                 LEFT JOIN marker_types mt ON m.marker_type_id = mt.id 
                 ORDER BY m.created_at DESC"
            );
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            db()->delete('markers', 'id = :id', ['id' => $id]);
            $success = 'Marker erfolgreich gelöscht!';
            
            // Reload markers
            $markers = db()->fetchAll(
                "SELECT m.*, g.name as guild_name, g.tag as guild_tag, mt.name as type_name 
                 FROM markers m 
                 LEFT JOIN guilds g ON m.guild_id = g.id 
                 LEFT JOIN marker_types mt ON m.marker_type_id = mt.id 
                 ORDER BY m.created_at DESC"
            );
        }
    }
}

// Marker zum Bearbeiten laden
$editMarker = null;
if (isset($_GET['edit'])) {
    $editMarker = db()->fetchOne("SELECT * FROM markers WHERE id = ?", [(int)$_GET['edit']]);
}
?>

<div class="page-header">
    <h1><i class="fas fa-map-marker-alt"></i> Marker verwalten</h1>
    <button class="btn btn-primary" onclick="showMarkerForm()">
        <i class="fas fa-plus"></i> Neuer Marker
    </button>
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

<!-- Marker-Formular -->
<div id="markerForm" class="modal" style="display: <?php echo $editMarker ? 'flex' : 'none'; ?>;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php echo $editMarker ? 'Marker bearbeiten' : 'Neuer Marker'; ?></h2>
            <button class="modal-close" onclick="hideMarkerForm()">&times;</button>
        </div>
        
        <form method="POST" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="<?php echo $editMarker ? 'edit' : 'add'; ?>">
            <?php if ($editMarker): ?>
            <input type="hidden" name="id" value="<?php echo $editMarker['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo e($editMarker['name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="description">Beschreibung</label>
                <textarea id="description" name="description" rows="3"><?php echo e($editMarker['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="x_position">X-Position *</label>
                    <input type="number" step="0.000001" id="x_position" name="x_position" required 
                           value="<?php echo e($editMarker['x_position'] ?? '0'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="y_position">Y-Position *</label>
                    <input type="number" step="0.000001" id="y_position" name="y_position" required 
                           value="<?php echo e($editMarker['y_position'] ?? '0'); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>Position auf der Karte wählen</label>
                <div id="markerPickerMap" style="width: 100%; height: 400px; border: 1px solid #ddd; border-radius: 4px;"></div>
                <small style="color: #666; display: block; margin-top: 5px;">
                    <i class="fas fa-info-circle"></i> Klicken Sie auf die Karte, um die Position auszuwählen.
                </small>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="marker_type_id">Marker-Typ</label>
                    <select id="marker_type_id" name="marker_type_id">
                        <option value="">-- Kein Typ --</option>
                        <?php foreach ($markerTypes as $type): ?>
                        <option value="<?php echo $type['id']; ?>" 
                                <?php echo (isset($editMarker['marker_type_id']) && $editMarker['marker_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                            <?php echo e($type['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="guild_id">Gilde</label>
                    <select id="guild_id" name="guild_id">
                        <option value="">-- Keine Gilde --</option>
                        <?php foreach ($guilds as $guild): ?>
                        <option value="<?php echo $guild['id']; ?>"
                                <?php echo (isset($editMarker['guild_id']) && $editMarker['guild_id'] == $guild['id']) ? 'selected' : ''; ?>>
                            [<?php echo e($guild['tag']); ?>] <?php echo e($guild['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_visible" 
                           <?php echo (!$editMarker || (isset($editMarker['is_visible']) && $editMarker['is_visible'])) ? 'checked' : ''; ?>>
                    <span>Sichtbar auf der Map</span>
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Speichern
                </button>
                <button type="button" class="btn btn-secondary" onclick="hideMarkerForm()">
                    Abbrechen
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Marker-Liste -->
<div class="content-section">
    <h2>Alle Marker (<?php echo count($markers); ?>)</h2>
    
    <?php if (empty($markers)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Noch keine Marker vorhanden.
        </div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Typ</th>
                    <th>Gilde</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th>Erstellt</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($markers as $marker): ?>
                <tr>
                    <td><?php echo $marker['id']; ?></td>
                    <td><strong><?php echo e($marker['name']); ?></strong></td>
                    <td><?php echo e($marker['type_name'] ?? '-'); ?></td>
                    <td>
                        <?php if ($marker['guild_name']): ?>
                            <span class="badge" style="background: <?php echo e($marker['guild_tag'] ? '#DC143C' : '#666'); ?>">
                                [<?php echo e($marker['guild_tag'] ?? ''); ?>] <?php echo e($marker['guild_name']); ?>
                            </span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?php echo number_format($marker['x_position'], 2); ?>, <?php echo number_format($marker['y_position'], 2); ?></td>
                    <td>
                        <span class="badge <?php echo $marker['is_visible'] ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo $marker['is_visible'] ? 'Sichtbar' : 'Versteckt'; ?>
                        </span>
                    </td>
                    <td><?php echo date('d.m.Y', strtotime($marker['created_at'])); ?></td>
                    <td class="actions">
                        <a href="?page=markers&edit=<?php echo $marker['id']; ?>" class="btn-icon" title="Bearbeiten">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Marker wirklich löschen?');">
                            <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCsrfToken(); ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $marker['id']; ?>">
                            <button type="submit" class="btn-icon btn-danger" title="Löschen">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Leaflet CSS für Admin-Panel -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
let markerPickerMap = null;
let markerPickerMarker = null;

function showMarkerForm() {
    document.getElementById('markerForm').style.display = 'flex';
    // Kurze Verzögerung, damit das Modal sichtbar ist, bevor die Karte initialisiert wird
    setTimeout(() => {
        initMarkerPickerMap();
    }, 100);
}

function hideMarkerForm() {
    document.getElementById('markerForm').style.display = 'none';
    if (markerPickerMap) {
        markerPickerMap.remove();
        markerPickerMap = null;
        markerPickerMarker = null;
    }
    if (!<?php echo json_encode((bool)$editMarker); ?>) {
        window.location.href = '?page=markers';
    }
}

function initMarkerPickerMap() {
    // Karte bereits initialisiert?
    if (markerPickerMap) {
        markerPickerMap.invalidateSize();
        return;
    }
    
    const mapDiv = document.getElementById('markerPickerMap');
    if (!mapDiv) return;
    
    // Map-Konfiguration laden
    const mapImage = <?php echo json_encode(getSetting('map_image', '')); ?>;
    const minZoom = parseInt(<?php echo json_encode(getSetting('map_min_zoom', '1')); ?>) || 1;
    const maxZoom = parseInt(<?php echo json_encode(getSetting('map_max_zoom', '5')); ?>) || 5;
    const defaultZoom = parseInt(<?php echo json_encode(getSetting('map_default_zoom', '2')); ?>) || 2;
    
    // Leaflet Map erstellen
    markerPickerMap = L.map('markerPickerMap', {
        crs: L.CRS.Simple,
        minZoom: minZoom,
        maxZoom: maxZoom,
        zoom: defaultZoom,
        center: [0, 0],
        zoomControl: true,
        attributionControl: false
    });
    
    // Map-Hintergrundbild laden
    if (mapImage) {
        const img = new Image();
        img.onload = function() {
            const bounds = [[0, 0], [this.height, this.width]];
            L.imageOverlay(mapImage, bounds).addTo(markerPickerMap);
            markerPickerMap.fitBounds(bounds);
            
            // Existierenden Marker anzeigen, falls vorhanden
            const currentX = parseFloat(document.getElementById('x_position').value) || 0;
            const currentY = parseFloat(document.getElementById('y_position').value) || 0;
            if (currentX !== 0 || currentY !== 0) {
                addMarkerToPickerMap(currentY, currentX);
                markerPickerMap.setView([currentY, currentX], defaultZoom);
            }
        };
        img.src = mapImage;
    } else {
        // Fallback: Einfacher grauer Hintergrund
        const bounds = [[0, 0], [1000, 1000]];
        const svgData = `
            <svg xmlns="http://www.w3.org/2000/svg" width="1000" height="1000">
                <rect width="1000" height="1000" fill="#1a1a1a"/>
                <text x="500" y="500" text-anchor="middle" fill="#666" font-size="24">
                    Bitte Map-Bild in den Einstellungen hochladen
                </text>
            </svg>
        `;
        const svgBlob = new Blob([svgData], {type: 'image/svg+xml'});
        const url = URL.createObjectURL(svgBlob);
        L.imageOverlay(url, bounds).addTo(markerPickerMap);
        markerPickerMap.fitBounds(bounds);
    }
    
    // Klick-Event auf der Karte
    markerPickerMap.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        
        // Position in Formularfelder eintragen
        document.getElementById('x_position').value = lng.toFixed(6);
        document.getElementById('y_position').value = lat.toFixed(6);
        
        // Marker auf der Karte setzen
        addMarkerToPickerMap(lat, lng);
    });
}

function addMarkerToPickerMap(lat, lng) {
    // Alten Marker entfernen, falls vorhanden
    if (markerPickerMarker) {
        markerPickerMap.removeLayer(markerPickerMarker);
    }
    
    // Neuen Marker erstellen
    markerPickerMarker = L.marker([lat, lng], {
        icon: L.divIcon({
            html: '<div style="font-size: 24px; color: #FF0000; text-shadow: 0 0 3px #000;"><i class="fas fa-map-marker-alt"></i></div>',
            className: 'custom-marker-icon',
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        })
    }).addTo(markerPickerMap);
}

// Bei Änderung der Positions-Inputs auch den Marker aktualisieren
document.addEventListener('DOMContentLoaded', function() {
    const xInput = document.getElementById('x_position');
    const yInput = document.getElementById('y_position');
    
    if (xInput && yInput) {
        const updateMarker = () => {
            const x = parseFloat(xInput.value) || 0;
            const y = parseFloat(yInput.value) || 0;
            if (markerPickerMap && (x !== 0 || y !== 0)) {
                addMarkerToPickerMap(y, x);
            }
        };
        
        xInput.addEventListener('change', updateMarker);
        yInput.addEventListener('change', updateMarker);
    }
});
</script>
