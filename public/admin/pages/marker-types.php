<?php
$markerTypes = db()->fetchAll("SELECT * FROM marker_types ORDER BY sort_order, name");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!Auth::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültiges CSRF-Token';
    } else {
        $action = $_POST['action'];
        
        if ($action === 'add' || $action === 'edit') {
            $data = [
                'name' => $_POST['name'],
                'icon' => $_POST['icon'],
                'color' => $_POST['color'],
                'description' => $_POST['description'] ?? '',
                'is_visible' => isset($_POST['is_visible']) ? 1 : 0,
                'sort_order' => (int)($_POST['sort_order'] ?? 0)
            ];
            
            if ($action === 'add') {
                db()->insert('marker_types', $data);
                $success = 'Marker-Typ erfolgreich hinzugefügt!';
            } else {
                $id = (int)$_POST['id'];
                db()->update('marker_types', $data, 'id = :id', ['id' => $id]);
                $success = 'Marker-Typ erfolgreich aktualisiert!';
            }
            
            $markerTypes = db()->fetchAll("SELECT * FROM marker_types ORDER BY sort_order, name");
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            db()->delete('marker_types', 'id = :id', ['id' => $id]);
            $success = 'Marker-Typ erfolgreich gelöscht!';
            
            $markerTypes = db()->fetchAll("SELECT * FROM marker_types ORDER BY sort_order, name");
        }
    }
}

$editType = null;
if (isset($_GET['edit'])) {
    $editType = db()->fetchOne("SELECT * FROM marker_types WHERE id = ?", [(int)$_GET['edit']]);
}

// Font Awesome Icons für Marker
$icons = ['flag', 'tree', 'dungeon', 'city', 'crossed-swords', 'skull', 'store', 'marker', 
          'home', 'fort', 'mountain', 'anchor', 'campground', 'church', 'landmark', 
          'coins', 'gem', 'hammer', 'shield-alt', 'crown', 'star', 'fire'];
?>

<div class="page-header">
    <h1><i class="fas fa-tags"></i> Marker-Typen verwalten</h1>
    <button class="btn btn-primary" onclick="showTypeForm()">
        <i class="fas fa-plus"></i> Neuer Marker-Typ
    </button>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?php echo e($success); ?>
</div>
<?php endif; ?>

<!-- Marker-Typ Formular -->
<div id="typeForm" class="modal" style="display: <?php echo $editType ? 'flex' : 'none'; ?>;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php echo $editType ? 'Marker-Typ bearbeiten' : 'Neuer Marker-Typ'; ?></h2>
            <button class="modal-close" onclick="hideTypeForm()">&times;</button>
        </div>
        
        <form method="POST" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="<?php echo $editType ? 'edit' : 'add'; ?>">
            <?php if ($editType): ?>
            <input type="hidden" name="id" value="<?php echo $editType['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo e($editType['name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="description">Beschreibung</label>
                <textarea id="description" name="description" rows="2"><?php echo e($editType['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="icon">Icon (Font Awesome)</label>
                    <select id="icon" name="icon" required>
                        <?php foreach ($icons as $icon): ?>
                        <option value="<?php echo $icon; ?>" 
                                <?php echo (isset($editType['icon']) && $editType['icon'] === $icon) ? 'selected' : ''; ?>>
                            <?php echo $icon; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="icon-preview">
                        <i class="fas fa-<?php echo e($editType['icon'] ?? 'marker'); ?>"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="color">Farbe</label>
                    <input type="color" id="color" name="color" 
                           value="<?php echo e($editType['color'] ?? '#FF0000'); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="sort_order">Sortierung</label>
                    <input type="number" id="sort_order" name="sort_order" 
                           value="<?php echo e($editType['sort_order'] ?? 0); ?>">
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_visible" 
                               <?php echo (!isset($editType) || $editType['is_visible']) ? 'checked' : ''; ?>>
                        <span>Sichtbar</span>
                    </label>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Speichern
                </button>
                <button type="button" class="btn btn-secondary" onclick="hideTypeForm()">
                    Abbrechen
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Marker-Typen Liste -->
<div class="content-section">
    <h2>Alle Marker-Typen (<?php echo count($markerTypes); ?>)</h2>
    
    <table class="data-table">
        <thead>
            <tr>
                <th width="50">Icon</th>
                <th>Name</th>
                <th>Beschreibung</th>
                <th>Farbe</th>
                <th>Sortierung</th>
                <th>Status</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($markerTypes as $type): ?>
            <tr>
                <td>
                    <i class="fas fa-<?php echo e($type['icon']); ?>" 
                       style="color: <?php echo e($type['color']); ?>; font-size: 20px;"></i>
                </td>
                <td><strong><?php echo e($type['name']); ?></strong></td>
                <td><?php echo e($type['description']); ?></td>
                <td>
                    <div class="color-preview" style="background: <?php echo e($type['color']); ?>"></div>
                    <?php echo e($type['color']); ?>
                </td>
                <td><?php echo $type['sort_order']; ?></td>
                <td>
                    <span class="badge <?php echo $type['is_visible'] ? 'badge-success' : 'badge-danger'; ?>">
                        <?php echo $type['is_visible'] ? 'Sichtbar' : 'Versteckt'; ?>
                    </span>
                </td>
                <td class="actions">
                    <a href="?page=marker-types&edit=<?php echo $type['id']; ?>" class="btn-icon" title="Bearbeiten">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Marker-Typ wirklich löschen?');">
                        <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $type['id']; ?>">
                        <button type="submit" class="btn-icon btn-danger" title="Löschen">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function showTypeForm() {
    document.getElementById('typeForm').style.display = 'flex';
}

function hideTypeForm() {
    document.getElementById('typeForm').style.display = 'none';
    if (!<?php echo json_encode((bool)$editType); ?>) {
        window.location.href = '?page=marker-types';
    }
}

// Icon Preview Update
document.getElementById('icon')?.addEventListener('change', function() {
    const preview = document.querySelector('.icon-preview i');
    if (preview) {
        preview.className = 'fas fa-' + this.value;
    }
});
</script>
