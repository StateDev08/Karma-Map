<?php
$guilds = db()->fetchAll("SELECT * FROM guilds ORDER BY is_active DESC, name");

// Gilde hinzufügen/bearbeiten/löschen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!Auth::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültiges CSRF-Token';
    } else {
        $action = $_POST['action'];
        
        if ($action === 'add' || $action === 'edit') {
            $data = [
                'name' => $_POST['name'],
                'tag' => $_POST['tag'],
                'description' => $_POST['description'] ?? '',
                'color' => $_POST['color'],
                'member_count' => (int)($_POST['member_count'] ?? 0),
                'leader_name' => $_POST['leader_name'] ?? '',
                'alliance' => $_POST['alliance'] ?? '',
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            if ($action === 'add') {
                db()->insert('guilds', $data);
                $success = 'Gilde erfolgreich hinzugefügt!';
            } else {
                $id = (int)$_POST['id'];
                db()->update('guilds', $data, 'id = :id', ['id' => $id]);
                $success = 'Gilde erfolgreich aktualisiert!';
            }
            
            $guilds = db()->fetchAll("SELECT * FROM guilds ORDER BY is_active DESC, name");
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            db()->delete('guilds', 'id = :id', ['id' => $id]);
            $success = 'Gilde erfolgreich gelöscht!';
            
            $guilds = db()->fetchAll("SELECT * FROM guilds ORDER BY is_active DESC, name");
        }
    }
}

$editGuild = null;
if (isset($_GET['edit'])) {
    $editGuild = db()->fetchOne("SELECT * FROM guilds WHERE id = ?", [(int)$_GET['edit']]);
}
?>

<div class="page-header">
    <h1><i class="fas fa-shield-alt"></i> Gilden verwalten</h1>
    <button class="btn btn-primary" onclick="showGuildForm()">
        <i class="fas fa-plus"></i> Neue Gilde
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

<!-- Gilden-Formular -->
<div id="guildForm" class="modal" style="display: <?php echo $editGuild ? 'flex' : 'none'; ?>;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php echo $editGuild ? 'Gilde bearbeiten' : 'Neue Gilde'; ?></h2>
            <button class="modal-close" onclick="hideGuildForm()">&times;</button>
        </div>
        
        <form method="POST" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="<?php echo $editGuild ? 'edit' : 'add'; ?>">
            <?php if ($editGuild): ?>
            <input type="hidden" name="id" value="<?php echo $editGuild['id']; ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Gilden-Name *</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo e($editGuild['name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="tag">Tag *</label>
                    <input type="text" id="tag" name="tag" required maxlength="10"
                           value="<?php echo e($editGuild['tag'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Beschreibung</label>
                <textarea id="description" name="description" rows="3"><?php echo e($editGuild['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="leader_name">Gildenleiter</label>
                    <input type="text" id="leader_name" name="leader_name" 
                           value="<?php echo e($editGuild['leader_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="member_count">Mitglieder-Anzahl</label>
                    <input type="number" id="member_count" name="member_count" min="0"
                           value="<?php echo e($editGuild['member_count'] ?? 0); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="color">Farbe</label>
                    <input type="color" id="color" name="color" 
                           value="<?php echo e($editGuild['color'] ?? '#FF0000'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="alliance">Allianz</label>
                    <input type="text" id="alliance" name="alliance" 
                           value="<?php echo e($editGuild['alliance'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" 
                           <?php echo (!isset($editGuild) || $editGuild['is_active']) ? 'checked' : ''; ?>>
                    <span>Gilde ist aktiv</span>
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Speichern
                </button>
                <button type="button" class="btn btn-secondary" onclick="hideGuildForm()">
                    Abbrechen
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Gilden-Liste -->
<div class="content-section">
    <h2>Alle Gilden (<?php echo count($guilds); ?>)</h2>
    
    <div class="guilds-grid">
        <?php foreach ($guilds as $guild): ?>
        <div class="guild-card <?php echo !$guild['is_active'] ? 'inactive' : ''; ?>">
            <div class="guild-header" style="border-left: 4px solid <?php echo e($guild['color']); ?>">
                <h3 style="color: <?php echo e($guild['color']); ?>">
                    [<?php echo e($guild['tag']); ?>] <?php echo e($guild['name']); ?>
                </h3>
                <?php if (!$guild['is_active']): ?>
                <span class="badge badge-danger">Inaktiv</span>
                <?php endif; ?>
            </div>
            
            <div class="guild-body">
                <?php if ($guild['description']): ?>
                <p><?php echo e($guild['description']); ?></p>
                <?php endif; ?>
                
                <div class="guild-info">
                    <?php if ($guild['leader_name']): ?>
                    <div class="info-item">
                        <i class="fas fa-crown"></i>
                        <span><?php echo e($guild['leader_name']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <i class="fas fa-users"></i>
                        <span><?php echo $guild['member_count']; ?> Mitglieder</span>
                    </div>
                    
                    <?php if ($guild['alliance']): ?>
                    <div class="info-item">
                        <i class="fas fa-handshake"></i>
                        <span><?php echo e($guild['alliance']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="guild-actions">
                <a href="?page=guilds&edit=<?php echo $guild['id']; ?>" class="btn btn-sm btn-secondary">
                    <i class="fas fa-edit"></i> Bearbeiten
                </a>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Gilde wirklich löschen?');">
                    <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $guild['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="fas fa-trash"></i> Löschen
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function showGuildForm() {
    document.getElementById('guildForm').style.display = 'flex';
}

function hideGuildForm() {
    document.getElementById('guildForm').style.display = 'none';
    if (!<?php echo json_encode((bool)$editGuild); ?>) {
        window.location.href = '?page=guilds';
    }
}
</script>
