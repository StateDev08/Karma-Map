<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

Auth::requireAdmin();

// Karma-Inhalte aus der Datenbank laden
function getKarmaContents() {
    try {
        return db()->fetchAll("SELECT * FROM karma_content ORDER BY sort_order ASC");
    } catch (PDOException $e) {
        return [];
    }
}

$success = '';
$error = '';

// Verarbeiten von Formular-Aktionen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!Auth::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültiges CSRF-Token';
    } else {
        switch ($_POST['action']) {
            case 'update_content':
                try {
                    $updateData = [
                        'title' => $_POST['title'],
                        'content' => $_POST['content'],
                        'is_visible' => isset($_POST['is_visible']) ? 1 : 0,
                        'sort_order' => (int)$_POST['sort_order']
                    ];
                    
                    $userId = Auth::userId();
                    if ($userId !== null) {
                        $updateData['updated_by'] = $userId;
                    }
                    
                    db()->update('karma_content', $updateData, 'id = ?', [(int)$_POST['id']]);
                    $success = 'Inhalt erfolgreich aktualisiert!';
                } catch (PDOException $e) {
                    $error = 'Fehler beim Aktualisieren: ' . $e->getMessage();
                }
                break;
                
            case 'add_content':
                try {
                    $insertData = [
                        'section' => $_POST['section'],
                        'title' => $_POST['title'],
                        'content' => $_POST['content'],
                        'is_visible' => isset($_POST['is_visible']) ? 1 : 0,
                        'sort_order' => (int)$_POST['sort_order']
                    ];
                    
                    $userId = Auth::userId();
                    if ($userId !== null) {
                        $insertData['updated_by'] = $userId;
                    }
                    
                    db()->insert('karma_content', $insertData);
                    $success = 'Neuer Inhalt erfolgreich hinzugefügt!';
                } catch (PDOException $e) {
                    $error = 'Fehler beim Hinzufügen: ' . $e->getMessage();
                }
                break;
                
            case 'delete_content':
                try {
                    db()->delete('karma_content', 'id = ?', [(int)$_POST['id']]);
                    $success = 'Inhalt erfolgreich gelöscht!';
                } catch (PDOException $e) {
                    $error = 'Fehler beim Löschen: ' . $e->getMessage();
                }
                break;
                
            case 'update_settings':
                setSetting('karma_enabled', isset($_POST['karma_enabled']) ? '1' : '0');
                setSetting('karma_show_map_link', isset($_POST['karma_show_map_link']) ? '1' : '0');
                setSetting('karma_theme', $_POST['karma_theme']);
                setSetting('karma_hero_overlay', $_POST['karma_hero_overlay']);
                setSetting('karma_discord_link', $_POST['karma_discord_link'] ?? '');
                
                if (isset($_FILES['karma_background_image']) && $_FILES['karma_background_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../../uploads/';
                    $filename = 'karma_bg_' . time() . '_' . basename($_FILES['karma_background_image']['name']);
                    $uploadPath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($_FILES['karma_background_image']['tmp_name'], $uploadPath)) {
                        setSetting('karma_background_image', 'uploads/' . $filename);
                    }
                }
                
                if (isset($_FILES['karma_hero_logo']) && $_FILES['karma_hero_logo']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../../uploads/';
                    $filename = 'karma_logo_' . time() . '_' . basename($_FILES['karma_hero_logo']['name']);
                    $uploadPath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($_FILES['karma_hero_logo']['tmp_name'], $uploadPath)) {
                        setSetting('karma_hero_logo', 'uploads/' . $filename);
                    }
                }
                
                $success = 'Einstellungen erfolgreich gespeichert!';
                break;
        }
    }
}

$karmaContents = getKarmaContents();
$karmaSettings = [
    'karma_enabled' => getSetting('karma_enabled', '1'),
    'karma_show_map_link' => getSetting('karma_show_map_link', '1'),
    'karma_theme' => getSetting('karma_theme', 'dark'),
    'karma_hero_overlay' => getSetting('karma_hero_overlay', '0.3'),
    'karma_background_image' => getSetting('karma_background_image', ''),
    'karma_hero_logo' => getSetting('karma_hero_logo', ''),
    'karma_discord_link' => getSetting('karma_discord_link', '')
];
?>

<div class="admin-page">
    <div class="page-header">
        <h1><i class="fas fa-home"></i> Karma-Seite verwalten</h1>
        <div class="header-actions">
            <a href="../../index.php" target="_blank" class="btn btn-secondary">
                <i class="fas fa-external-link-alt"></i> Vorschau
            </a>
        </div>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo e($success); ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i> <?php echo e($error); ?>
    </div>
    <?php endif; ?>
    
    <!-- Karma-Einstellungen -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-cog"></i> Karma-Einstellungen</h2>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="update_settings">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="karma_enabled" value="1" <?php echo $karmaSettings['karma_enabled'] === '1' ? 'checked' : ''; ?>>
                            Karma-Startseite aktivieren
                        </label>
                        <small>Wenn deaktiviert, wird direkt zur Map weitergeleitet</small>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="karma_show_map_link" value="1" <?php echo $karmaSettings['karma_show_map_link'] === '1' ? 'checked' : ''; ?>>
                            Map-Link in Navigation anzeigen
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="karma_discord_link">Discord-Link</label>
                        <input type="url" name="karma_discord_link" id="karma_discord_link" 
                               value="<?php echo e($karmaSettings['karma_discord_link']); ?>" 
                               class="form-control" 
                               placeholder="https://discord.gg/...">
                        <small>Discord-Einladungslink (erscheint in der Navigation)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="karma_theme">Theme</label>
                        <select name="karma_theme" id="karma_theme" class="form-control">
                            <option value="dark" <?php echo $karmaSettings['karma_theme'] === 'dark' ? 'selected' : ''; ?>>Dark</option>
                            <option value="light" <?php echo $karmaSettings['karma_theme'] === 'light' ? 'selected' : ''; ?>>Light</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="karma_hero_overlay">Hero Overlay Transparenz (0-1)</label>
                        <input type="number" step="0.1" min="0" max="1" name="karma_hero_overlay" id="karma_hero_overlay" 
                               value="<?php echo e($karmaSettings['karma_hero_overlay']); ?>" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="karma_background_image">Hintergrundbild</label>
                        <input type="file" name="karma_background_image" id="karma_background_image" class="form-control" accept="image/*">
                        <?php if (!empty($karmaSettings['karma_background_image'])): ?>
                        <small>Aktuell: <?php echo e($karmaSettings['karma_background_image']); ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="karma_hero_logo">Hero-Logo</label>
                        <input type="file" name="karma_hero_logo" id="karma_hero_logo" class="form-control" accept="image/*">
                        <?php if (!empty($karmaSettings['karma_hero_logo'])): ?>
                        <small>Aktuell: <?php echo e($karmaSettings['karma_hero_logo']); ?></small>
                        <br>
                        <img src="../../<?php echo e($karmaSettings['karma_hero_logo']); ?>" alt="Logo Preview" style="max-width: 300px; margin-top: 10px;">
                        <?php endif; ?>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Einstellungen speichern
                </button>
            </form>
        </div>
    </div>
    
    <!-- Inhalte verwalten -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-edit"></i> Inhalte verwalten</h2>
            <button class="btn btn-primary" onclick="showAddContentModal()">
                <i class="fas fa-plus"></i> Neuer Inhalt
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($karmaContents)): ?>
            <p class="text-center text-muted">Noch keine Inhalte vorhanden.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sektion</th>
                            <th>Titel</th>
                            <th>Reihenfolge</th>
                            <th>Sichtbar</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($karmaContents as $content): ?>
                        <tr>
                            <td><code><?php echo e($content['section']); ?></code></td>
                            <td><?php echo e($content['title']); ?></td>
                            <td><?php echo $content['sort_order']; ?></td>
                            <td>
                                <?php if ($content['is_visible']): ?>
                                <span class="badge badge-success"><i class="fas fa-eye"></i> Ja</span>
                                <?php else: ?>
                                <span class="badge badge-danger"><i class="fas fa-eye-slash"></i> Nein</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick='editContent(<?php echo json_encode($content); ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteContent(<?php echo $content['id']; ?>, '<?php echo e($content['title']); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Content Modal -->
<div id="editContentModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Inhalt bearbeiten</h2>
            <button class="close" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST" id="editContentForm">
            <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="update_content">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_section">Sektion (eindeutig)</label>
                    <input type="text" name="section" id="edit_section" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label for="edit_title">Titel</label>
                    <input type="text" name="title" id="edit_title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_content">Inhalt (HTML erlaubt)</label>
                    <textarea name="content" id="edit_content" class="form-control" rows="10" required></textarea>
                    <small>Sie können HTML verwenden: &lt;h1&gt;, &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, etc.</small>
                </div>
                
                <div class="form-group">
                    <label for="edit_sort_order">Reihenfolge</label>
                    <input type="number" name="sort_order" id="edit_sort_order" class="form-control" value="0">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_visible" id="edit_is_visible" value="1" checked>
                        Sichtbar
                    </label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Speichern
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Content Modal -->
<div id="addContentModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-plus"></i> Neuer Inhalt</h2>
            <button class="close" onclick="closeAddModal()">&times;</button>
        </div>
        <form method="POST" id="addContentForm">
            <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="add_content">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="add_section">Sektion (eindeutig, z.B. "gallery", "team")</label>
                    <input type="text" name="section" id="add_section" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="add_title">Titel</label>
                    <input type="text" name="title" id="add_title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="add_content">Inhalt (HTML erlaubt)</label>
                    <textarea name="content" id="add_content" class="form-control" rows="10" required></textarea>
                    <small>Sie können HTML verwenden: &lt;h1&gt;, &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, etc.</small>
                </div>
                
                <div class="form-group">
                    <label for="add_sort_order">Reihenfolge</label>
                    <input type="number" name="sort_order" id="add_sort_order" class="form-control" value="99">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_visible" id="add_is_visible" value="1" checked>
                        Sichtbar
                    </label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Hinzufügen
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form method="POST" id="deleteForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCsrfToken(); ?>">
    <input type="hidden" name="action" value="delete_content">
    <input type="hidden" name="id" id="delete_id">
</form>

<style>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-content {
    background: #1a1a1a;
    padding: 0;
    border-radius: 8px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #333;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1.5rem;
    border-top: 1px solid #333;
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

.close {
    background: none;
    border: none;
    color: #fff;
    font-size: 2rem;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.close:hover {
    color: #DC143C;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.875rem;
}

.badge-success {
    background: #28a745;
    color: #fff;
}

.badge-danger {
    background: #dc3545;
    color: #fff;
}
</style>

<script>
function editContent(content) {
    document.getElementById('edit_id').value = content.id;
    document.getElementById('edit_section').value = content.section;
    document.getElementById('edit_title').value = content.title;
    document.getElementById('edit_content').value = content.content;
    document.getElementById('edit_sort_order').value = content.sort_order;
    document.getElementById('edit_is_visible').checked = content.is_visible == 1;
    document.getElementById('editContentModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editContentModal').style.display = 'none';
}

function showAddContentModal() {
    document.getElementById('addContentModal').style.display = 'flex';
}

function closeAddModal() {
    document.getElementById('addContentModal').style.display = 'none';
}

function deleteContent(id, title) {
    if (confirm('Möchten Sie den Inhalt "' + title + '" wirklich löschen?')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}

// Close modal on outside click
document.getElementById('editContentModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});

document.getElementById('addContentModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeAddModal();
});
</script>
