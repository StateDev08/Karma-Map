<?php
// Dashboard-Statistiken
$stats = [
    'markers' => db()->fetchOne("SELECT COUNT(*) as count FROM markers")['count'],
    'guilds' => db()->fetchOne("SELECT COUNT(*) as count FROM guilds WHERE is_active = 1")['count'],
    'marker_types' => db()->fetchOne("SELECT COUNT(*) as count FROM marker_types WHERE is_visible = 1")['count'],
    'uploaded_images' => db()->fetchOne("SELECT COUNT(*) as count FROM uploaded_images")['count']
];

$recentMarkers = db()->fetchAll(
    "SELECT m.*, g.name as guild_name, mt.name as type_name 
     FROM markers m 
     LEFT JOIN guilds g ON m.guild_id = g.id 
     LEFT JOIN marker_types mt ON m.marker_type_id = mt.id 
     ORDER BY m.created_at DESC LIMIT 5"
);
?>

<div class="page-header">
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
    <p>Willkommen im KARMA Admin Control Panel</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: #FF0000;">
            <i class="fas fa-map-marker-alt"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $stats['markers']; ?></h3>
            <p>Marker gesamt</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #DC143C;">
            <i class="fas fa-shield-alt"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $stats['guilds']; ?></h3>
            <p>Aktive Gilden</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #8B0000;">
            <i class="fas fa-tags"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $stats['marker_types']; ?></h3>
            <p>Marker-Typen</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #A00000;">
            <i class="fas fa-image"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $stats['uploaded_images']; ?></h3>
            <p>Hochgeladene Bilder</p>
        </div>
    </div>
</div>

<div class="content-section">
    <h2><i class="fas fa-clock"></i> Zuletzt hinzugefügte Marker</h2>
    
    <?php if (empty($recentMarkers)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Noch keine Marker vorhanden. Erstelle deinen ersten Marker!
        </div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Typ</th>
                    <th>Gilde</th>
                    <th>Position</th>
                    <th>Erstellt am</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentMarkers as $marker): ?>
                <tr>
                    <td><strong><?php echo e($marker['name']); ?></strong></td>
                    <td><?php echo e($marker['type_name'] ?? 'N/A'); ?></td>
                    <td><?php echo e($marker['guild_name'] ?? 'N/A'); ?></td>
                    <td><?php echo number_format($marker['x_position'], 2); ?>, <?php echo number_format($marker['y_position'], 2); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($marker['created_at'])); ?></td>
                    <td>
                        <a href="?page=markers&edit=<?php echo $marker['id']; ?>" class="btn-icon" title="Bearbeiten">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="quick-actions">
    <h2><i class="fas fa-bolt"></i> Schnellaktionen</h2>
    <div class="action-buttons">
        <a href="?page=markers" class="btn btn-primary">
            <i class="fas fa-plus"></i> Neuer Marker
        </a>
        <a href="?page=guilds" class="btn btn-primary">
            <i class="fas fa-plus"></i> Neue Gilde
        </a>
        <a href="?page=map-upload" class="btn btn-primary">
            <i class="fas fa-upload"></i> Map hochladen
        </a>
        <a href="?page=settings" class="btn btn-secondary">
            <i class="fas fa-palette"></i> Design anpassen
        </a>
    </div>
</div>
