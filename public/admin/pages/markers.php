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
                           <?php echo (!isset($editMarker) || $editMarker['is_visible']) ? 'checked' : ''; ?>>
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

<script>
function showMarkerForm() {
    document.getElementById('markerForm').style.display = 'flex';
}

function hideMarkerForm() {
    document.getElementById('markerForm').style.display = 'none';
    if (!<?php echo json_encode((bool)$editMarker); ?>) {
        window.location.href = '?page=markers';
    }
}
</script>
