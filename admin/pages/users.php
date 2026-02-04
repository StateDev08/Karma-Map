<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

Auth::requireAdmin();
Auth::requirePermission('users.view');

$canCreate = Auth::hasPermission('users.create');
$canEdit = Auth::hasPermission('users.edit');
$canDelete = Auth::hasPermission('users.delete');
$canManageRoles = Auth::hasPermission('users.manage_roles');

// Aktionen verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!Auth::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültiges CSRF-Token';
    } else {
        try {
            switch ($_POST['action']) {
                case 'create':
                    Auth::requirePermission('users.create');
                    
                    $username = trim($_POST['username']);
                    $password = $_POST['password'];
                    $email = trim($_POST['email']);
                    $fullName = trim($_POST['full_name']);
                    $role = $_POST['role'];
                    
                    // Validierung
                    if (empty($username) || empty($password)) {
                        throw new Exception('Benutzername und Passwort sind erforderlich');
                    }
                    
                    if (strlen($password) < 6) {
                        throw new Exception('Passwort muss mindestens 6 Zeichen lang sein');
                    }
                    
                    // Nur Super Admin kann andere Super Admins erstellen
                    if ($role === 'super_admin' && !Auth::isSuperAdmin()) {
                        throw new Exception('Keine Berechtigung Super Admins zu erstellen');
                    }
                    
                    // Prüfe ob Username bereits existiert
                    $existing = db()->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
                    if ($existing) {
                        throw new Exception('Benutzername bereits vergeben');
                    }
                    
                    // Benutzer erstellen
                    db()->insert('users', [
                        'username' => $username,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'email' => $email,
                        'full_name' => $fullName,
                        'role' => $role,
                        'is_admin' => 1,
                        'is_active' => 1,
                        'created_by' => Auth::userId()
                    ]);
                    
                    $success = 'Benutzer erfolgreich erstellt!';
                    break;
                    
                case 'edit':
                    Auth::requirePermission('users.edit');
                    
                    $userId = (int)$_POST['user_id'];
                    $email = trim($_POST['email']);
                    $fullName = trim($_POST['full_name']);
                    $role = $_POST['role'];
                    $isActive = isset($_POST['is_active']) ? 1 : 0;
                    
                    // Hole aktuellen User
                    $user = db()->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
                    if (!$user) {
                        throw new Exception('Benutzer nicht gefunden');
                    }
                    
                    // Nicht den eigenen Account deaktivieren
                    if ($userId == Auth::userId() && !$isActive) {
                        throw new Exception('Du kannst deinen eigenen Account nicht deaktivieren');
                    }
                    
                    // Nur Super Admin kann Super Admins bearbeiten
                    if ($user['role'] === 'super_admin' && !Auth::isSuperAdmin()) {
                        throw new Exception('Keine Berechtigung Super Admins zu bearbeiten');
                    }
                    
                    // Nur Super Admin kann zu Super Admin machen
                    if ($role === 'super_admin' && !Auth::isSuperAdmin()) {
                        throw new Exception('Keine Berechtigung Super Admin-Rolle zu vergeben');
                    }
                    
                    // Update
                    $updateData = [
                        'email' => $email,
                        'full_name' => $fullName,
                        'is_active' => $isActive
                    ];
                    
                    if ($canManageRoles) {
                        $updateData['role'] = $role;
                    }
                    
                    db()->update('users', $updateData, 'id = :id', ['id' => $userId]);
                    
                    $success = 'Benutzer erfolgreich aktualisiert!';
                    break;
                    
                case 'change_password':
                    Auth::requirePermission('users.edit');
                    
                    $userId = (int)$_POST['user_id'];
                    $newPassword = $_POST['new_password'];
                    
                    if (strlen($newPassword) < 6) {
                        throw new Exception('Passwort muss mindestens 6 Zeichen lang sein');
                    }
                    
                    db()->update('users', 
                        ['password' => password_hash($newPassword, PASSWORD_DEFAULT)],
                        'id = :id',
                        ['id' => $userId]
                    );
                    
                    $success = 'Passwort erfolgreich geändert!';
                    break;
                    
                case 'delete':
                    Auth::requirePermission('users.delete');
                    
                    $userId = (int)$_POST['user_id'];
                    
                    // Nicht sich selbst löschen
                    if ($userId == Auth::userId()) {
                        throw new Exception('Du kannst deinen eigenen Account nicht löschen');
                    }
                    
                    // Hole User
                    $user = db()->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
                    if (!$user) {
                        throw new Exception('Benutzer nicht gefunden');
                    }
                    
                    // Nur Super Admin kann Super Admins löschen
                    if ($user['role'] === 'super_admin' && !Auth::isSuperAdmin()) {
                        throw new Exception('Keine Berechtigung Super Admins zu löschen');
                    }
                    
                    db()->delete('users', 'id = :id', ['id' => $userId]);
                    
                    $success = 'Benutzer erfolgreich gelöscht!';
                    break;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Alle Benutzer laden
$users = db()->fetchAll("
    SELECT u.*, 
           creator.username as created_by_name
    FROM users u
    LEFT JOIN users creator ON u.created_by = creator.id
    ORDER BY u.created_at DESC
");

// Rollen-Labels
$roleLabels = [
    'super_admin' => 'Super Administrator',
    'admin' => 'Administrator',
    'moderator' => 'Moderator',
    'editor' => 'Editor'
];

// Rollen-Farben
$roleColors = [
    'super_admin' => '#DC143C',
    'admin' => '#FF4500',
    'moderator' => '#FFD700',
    'editor' => '#1E90FF'
];
?>

<div class="page-header">
    <h1><i class="fas fa-users"></i> Benutzerverwaltung</h1>
    <?php if ($canCreate): ?>
        <button class="btn btn-primary" onclick="showCreateModal()">
            <i class="fas fa-plus"></i> Neuer Benutzer
        </button>
    <?php endif; ?>
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
    <h2>Alle Benutzer</h2>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #DC143C;">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Gesamt</div>
                <div class="stat-value"><?php echo count($users); ?></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #00FF00;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Aktiv</div>
                <div class="stat-value">
                    <?php echo count(array_filter($users, fn($u) => $u['is_active'])); ?>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #FFD700;">
                <i class="fas fa-crown"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Admins</div>
                <div class="stat-value">
                    <?php echo count(array_filter($users, fn($u) => in_array($u['role'], ['super_admin', 'admin']))); ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Benutzername</th>
                    <th>Name</th>
                    <th>E-Mail</th>
                    <th>Rolle</th>
                    <th>Status</th>
                    <th>Letzter Login</th>
                    <th>Erstellt</th>
                    <?php if ($canEdit || $canDelete): ?>
                        <th>Aktionen</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <strong><?php echo e($user['username']); ?></strong>
                        <?php if ($user['id'] == Auth::userId()): ?>
                            <span class="badge" style="background: #1E90FF;">Du</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo e($user['full_name'] ?? '-'); ?></td>
                    <td><?php echo e($user['email'] ?? '-'); ?></td>
                    <td>
                        <span class="role-badge" style="background: <?php echo $roleColors[$user['role']]; ?>;">
                            <i class="fas fa-shield-alt"></i>
                            <?php echo $roleLabels[$user['role']]; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($user['is_active']): ?>
                            <span class="status-badge active">
                                <i class="fas fa-check-circle"></i> Aktiv
                            </span>
                        <?php else: ?>
                            <span class="status-badge inactive">
                                <i class="fas fa-times-circle"></i> Inaktiv
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($user['last_login']): ?>
                            <?php echo date('d.m.Y H:i', strtotime($user['last_login'])); ?>
                        <?php else: ?>
                            <span style="color: var(--text-muted);">Nie</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo date('d.m.Y', strtotime($user['created_at'])); ?>
                        <?php if ($user['created_by_name']): ?>
                            <br><small style="color: var(--text-muted);">von <?php echo e($user['created_by_name']); ?></small>
                        <?php endif; ?>
                    </td>
                    <?php if ($canEdit || $canDelete): ?>
                    <td>
                        <div class="action-buttons">
                            <?php if ($canEdit): ?>
                                <button class="btn btn-sm btn-secondary" 
                                        onclick="showEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" 
                                        onclick="showPasswordModal(<?php echo $user['id']; ?>, '<?php echo e($user['username']); ?>')">
                                    <i class="fas fa-key"></i>
                                </button>
                            <?php endif; ?>
                            <?php if ($canDelete && $user['id'] != Auth::userId()): ?>
                                <button class="btn btn-sm btn-danger" 
                                        onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo e($user['username']); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<?php if ($canCreate): ?>
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-user-plus"></i> Neuer Benutzer</h2>
            <button class="close-modal" onclick="closeModal('createModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label for="username">Benutzername *</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Passwort *</label>
                <input type="password" id="password" name="password" required minlength="6">
                <small>Mindestens 6 Zeichen</small>
            </div>
            
            <div class="form-group">
                <label for="full_name">Vollständiger Name</label>
                <input type="text" id="full_name" name="full_name">
            </div>
            
            <div class="form-group">
                <label for="email">E-Mail</label>
                <input type="email" id="email" name="email">
            </div>
            
            <div class="form-group">
                <label for="role">Rolle</label>
                <select id="role" name="role" required>
                    <option value="editor">Editor</option>
                    <option value="moderator">Moderator</option>
                    <option value="admin">Administrator</option>
                    <?php if (Auth::isSuperAdmin()): ?>
                        <option value="super_admin">Super Administrator</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">
                    Abbrechen
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Erstellen
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Edit Modal -->
<?php if ($canEdit): ?>
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Benutzer bearbeiten</h2>
            <button class="close-modal" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="form-group">
                <label>Benutzername</label>
                <input type="text" id="edit_username" readonly style="background: var(--bg-tertiary);">
            </div>
            
            <div class="form-group">
                <label for="edit_full_name">Vollständiger Name</label>
                <input type="text" id="edit_full_name" name="full_name">
            </div>
            
            <div class="form-group">
                <label for="edit_email">E-Mail</label>
                <input type="email" id="edit_email" name="email">
            </div>
            
            <?php if ($canManageRoles): ?>
            <div class="form-group">
                <label for="edit_role">Rolle</label>
                <select id="edit_role" name="role" required>
                    <option value="editor">Editor</option>
                    <option value="moderator">Moderator</option>
                    <option value="admin">Administrator</option>
                    <?php if (Auth::isSuperAdmin()): ?>
                        <option value="super_admin">Super Administrator</option>
                    <?php endif; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" id="edit_is_active" value="1">
                    Aktiv
                </label>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">
                    Abbrechen
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Speichern
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Password Modal -->
<div id="passwordModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-key"></i> Passwort ändern</h2>
            <button class="close-modal" onclick="closeModal('passwordModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="user_id" id="password_user_id">
            
            <div class="form-group">
                <label>Benutzer</label>
                <input type="text" id="password_username" readonly style="background: var(--bg-tertiary);">
            </div>
            
            <div class="form-group">
                <label for="new_password">Neues Passwort *</label>
                <input type="password" id="new_password" name="new_password" required minlength="6">
                <small>Mindestens 6 Zeichen</small>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('passwordModal')">
                    Abbrechen
                </button>
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-key"></i> Passwort ändern
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 4px;
    color: white;
    font-size: 13px;
    font-weight: 600;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
}

.status-badge.active {
    background: #00FF00;
    color: #000;
}

.status-badge.inactive {
    background: #666;
    color: #fff;
}

.action-buttons {
    display: flex;
    gap: 5px;
}
</style>

<script>
function showCreateModal() {
    document.getElementById('createModal').style.display = 'flex';
}

function showEditModal(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_full_name').value = user.full_name || '';
    document.getElementById('edit_email').value = user.email || '';
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_is_active').checked = user.is_active == 1;
    
    document.getElementById('editModal').style.display = 'flex';
}

function showPasswordModal(userId, username) {
    document.getElementById('password_user_id').value = userId;
    document.getElementById('password_username').value = username;
    document.getElementById('new_password').value = '';
    
    document.getElementById('passwordModal').style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function deleteUser(userId, username) {
    if (confirm('Bist du sicher, dass du den Benutzer "' + username + '" löschen möchtest?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo Auth::generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Schließe Modal bei Klick außerhalb
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>
