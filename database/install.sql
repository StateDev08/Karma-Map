-- PAX DEI Map - Komplettes Datenbank Schema & Initialisierung
-- Aktualisiert: 08.02.2026 (Zusammengefügt aus schema.sql, update_v2.2.0.sql und karma_update.sql)

CREATE DATABASE IF NOT EXISTS pax_die_map CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pax_die_map;

-- ############################################################
-- 1. TABELLEN-STRUKTUR
-- ############################################################

-- Benutzer-Tabelle (nur Admins)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    full_name VARCHAR(100),
    role ENUM('super_admin', 'admin', 'moderator', 'editor') DEFAULT 'editor',
    is_active TINYINT(1) DEFAULT 1,
    is_admin TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    created_by INT NULL,
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_active (is_active),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Berechtigungen-Tabelle
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rollen-Berechtigungen Zuordnung
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('super_admin', 'admin', 'moderator', 'editor') NOT NULL,
    permission_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_role_permission (role, permission_name),
    FOREIGN KEY (permission_name) REFERENCES permissions(name) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gilden-Tabelle
CREATE TABLE IF NOT EXISTS guilds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    tag VARCHAR(10),
    description TEXT,
    color VARCHAR(7) DEFAULT '#FF0000',
    member_count INT DEFAULT 0,
    leader_name VARCHAR(100),
    alliance TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Marker-Typen
CREATE TABLE IF NOT EXISTS marker_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    icon VARCHAR(100),
    color VARCHAR(7) DEFAULT '#FF0000',
    description TEXT,
    is_visible TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_visible (is_visible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Map-Marker
CREATE TABLE IF NOT EXISTS markers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    x_position DECIMAL(10,6) NOT NULL,
    y_position DECIMAL(10,6) NOT NULL,
    marker_type_id INT,
    guild_id INT,
    image_url VARCHAR(255),
    is_visible TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (marker_type_id) REFERENCES marker_types(id) ON DELETE SET NULL,
    FOREIGN KEY (guild_id) REFERENCES guilds(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_visible (is_visible),
    INDEX idx_type (marker_type_id),
    INDEX idx_guild (guild_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hochgeladene Bilder
CREATE TABLE IF NOT EXISTS uploaded_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255),
    file_path VARCHAR(255) NOT NULL,
    file_size INT,
    mime_type VARCHAR(50),
    image_type ENUM('map', 'logo', 'marker') NOT NULL,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_type (image_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Einstellungen
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabelle für Karma-Seiteninhalte
CREATE TABLE IF NOT EXISTS karma_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(255),
    content TEXT,
    is_visible TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_section (section),
    INDEX idx_visible (is_visible),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ############################################################
-- 2. STANDARD-DATEN (EINSTELLUNGEN)
-- ############################################################

INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_title', 'PAX DEI Map - KARMA', 'Website-Titel'),
('primary_color', '#FF0000', 'Primärfarbe (Rot)'),
('secondary_color', '#000000', 'Sekundärfarbe (Schwarz)'),
('accent_color', '#DC143C', 'Akzentfarbe'),
('logo_type', 'text', 'Logo-Typ: text, image oder custom'),
('logo_text', 'KARMA', 'Text-Logo'),
('logo_image', '', 'Pfad zum Logo-Bild'),
('map_image', '', 'Pfad zum Map-Hintergrundbild'),
('map_default_zoom', '2', 'Standard-Zoom-Level'),
('map_max_zoom', '5', 'Maximaler Zoom'),
('map_min_zoom', '1', 'Minimaler Zoom'),
('map_show_coordinates', '1', 'Koordinaten-Anzeige aktiviert'),
('map_show_minimap', '1', 'Mini-Map aktiviert'),
('map_enable_measure', '1', 'Messwerkzeug aktiviert'),
('map_enable_drawing', '1', 'Zeichenwerkzeuge aktiviert'),
('map_enable_fullscreen', '1', 'Vollbild-Modus aktiviert'),
('map_enable_search', '1', 'Koordinaten-Suche aktiviert'),
('map_default_position_x', '0', 'Standard-Position X'),
('map_default_position_y', '0', 'Standard-Position Y'),
('map_grid_enabled', '0', 'Raster-Overlay aktiviert'),
('map_grid_size', '100', 'Raster-Größe in Pixeln'),
('map_mouse_coordinates', '1', 'Maus-Koordinaten live anzeigen'),
('map_scale_control', '1', 'Maßstabsleiste anzeigen'),
('map_zoom_animation', '1', 'Zoom-Animation aktiviert'),
('map_double_click_zoom', '1', 'Doppelklick-Zoom aktiviert'),
('map_scroll_wheel_zoom', '1', 'Mausrad-Zoom aktiviert'),
('map_marker_clustering', '0', 'Marker-Clustering aktiviert'),
('map_auto_pan', '1', 'Auto-Pan bei Marker-Klick'),
('karma_enabled', '1', 'Karma-Startseite aktiviert'),
('karma_background_image', '', 'Hintergrundbild für Karma-Seite'),
('karma_hero_logo', '', 'Hero-Logo für Karma-Seite'),
('karma_show_map_link', '1', 'Map-Link in Navigation anzeigen'),
('karma_hero_overlay', '0.3', 'Hero-Sektion Overlay-Transparenz (0-1)'),
('karma_theme', 'dark', 'Theme der Karma-Seite (dark/light)'),
('karma_discord_link', '', 'Discord-Einladungslink');


-- ############################################################
-- 3. BERECHTIGUNGEN & ROLLEN
-- ############################################################

-- Standard-Berechtigungen
INSERT INTO permissions (name, description, category) VALUES
-- User Management
('users.view', 'Benutzer anzeigen', 'users'),
('users.create', 'Benutzer erstellen', 'users'),
('users.edit', 'Benutzer bearbeiten', 'users'),
('users.delete', 'Benutzer löschen', 'users'),
('users.manage_roles', 'Rollen verwalten', 'users'),
-- Marker Management
('markers.view', 'Marker anzeigen', 'markers'),
('markers.create', 'Marker erstellen', 'markers'),
('markers.edit', 'Marker bearbeiten', 'markers'),
('markers.delete', 'Marker löschen', 'markers'),
-- Guild Management
('guilds.view', 'Gilden anzeigen', 'guilds'),
('guilds.create', 'Gilden erstellen', 'guilds'),
('guilds.edit', 'Gilden bearbeiten', 'guilds'),
('guilds.delete', 'Gilden löschen', 'guilds'),
-- Marker Types
('marker_types.view', 'Marker-Typen anzeigen', 'marker_types'),
('marker_types.create', 'Marker-Typen erstellen', 'marker_types'),
('marker_types.edit', 'Marker-Typen bearbeiten', 'marker_types'),
('marker_types.delete', 'Marker-Typen löschen', 'marker_types'),
-- Map Management
('map.upload', 'Map hochladen', 'map'),
('map.generate_tiles', 'Tiles generieren', 'map'),
-- Settings
('settings.view', 'Einstellungen anzeigen', 'settings'),
('settings.edit', 'Einstellungen bearbeiten', 'settings'),
-- Karma Management
('karma.view', 'Karma-Inhalte anzeigen', 'karma'),
('karma.edit', 'Karma-Inhalte bearbeiten', 'karma');

-- Rollen-Berechtigungen
-- Super Admin
INSERT INTO role_permissions (role, permission_name) 
SELECT 'super_admin', name FROM permissions;

-- Admin
INSERT INTO role_permissions (role, permission_name) VALUES
('admin', 'users.view'), ('admin', 'users.create'), ('admin', 'users.edit'),
('admin', 'markers.view'), ('admin', 'markers.create'), ('admin', 'markers.edit'), ('admin', 'markers.delete'),
('admin', 'guilds.view'), ('admin', 'guilds.create'), ('admin', 'guilds.edit'), ('admin', 'guilds.delete'),
('admin', 'marker_types.view'), ('admin', 'marker_types.create'), ('admin', 'marker_types.edit'), ('admin', 'marker_types.delete'),
('admin', 'map.upload'), ('admin', 'map.generate_tiles'),
('admin', 'settings.view'), ('admin', 'settings.edit'),
('admin', 'karma.view'), ('admin', 'karma.edit');

-- Moderator
INSERT INTO role_permissions (role, permission_name) VALUES
('moderator', 'users.view'),
('moderator', 'markers.view'), ('moderator', 'markers.create'), ('moderator', 'markers.edit'), ('moderator', 'markers.delete'),
('moderator', 'guilds.view'), ('moderator', 'guilds.create'), ('moderator', 'guilds.edit'),
('moderator', 'marker_types.view'),
('moderator', 'map.upload'),
('moderator', 'settings.view'),
('moderator', 'karma.view');

-- Editor
INSERT INTO role_permissions (role, permission_name) VALUES
('editor', 'markers.view'), ('editor', 'markers.create'), ('editor', 'markers.edit'),
('editor', 'guilds.view'),
('editor', 'marker_types.view'),
('editor', 'settings.view');


-- ############################################################
-- 4. INHALTE (MARER-TYPEN, GILDEN, KARMA)
-- ############################################################

-- Standard-Marker-Typen
INSERT INTO marker_types (name, icon, color, description, sort_order) VALUES
('Gildenterritorium', 'flag', '#FF0000', 'Territorium einer Gilde', 1),
('Ressourcen', 'tree', '#00FF00', 'Ressourcen-Punkte', 2),
('Dungeon', 'dungeon', '#800080', 'Dungeon-Eingang', 3),
('Stadt', 'city', '#FFD700', 'Städte und Siedlungen', 4),
('PvP-Zone', 'crossed-swords', '#FF4500', 'PvP-Hotspots', 5),
('Boss', 'skull', '#8B0000', 'Boss-Spawns', 6),
('Handelsposten', 'store', '#1E90FF', 'Handelspunkte', 7),
('Sonstiges', 'marker', '#808080', 'Andere Marker', 99);

-- KARMA Gilde als Standard
INSERT INTO guilds (name, tag, description, color, leader_name, is_active) VALUES
('KARMA', 'KARMA', 'Die mächtigste Gilde von PAX DEI', '#DC143C', 'Guild Master', 1);

-- Standard Karma-Sektionen
INSERT INTO karma_content (section, title, content, is_visible, sort_order) VALUES
('hero', 'Willkommen bei KARMA', '<h1>KARMA</h1><p>Die mächtigste Gilde von PAX DEI</p>', 1, 1),
('about', 'Über Uns', '<h2>Über KARMA</h2><p>KARMA ist die führende Gilde in PAX DEI. Wir dominieren die Karte und verteidigen unser Territorium mit eiserner Faust.</p>', 1, 2),
('features', 'Unsere Stärken', '<h2>Was uns auszeichnet</h2><ul><li>Erfahrene Leader</li><li>Aktive Community</li><li>Strategische Dominanz</li><li>Teamwork und Zusammenhalt</li></ul>', 1, 3),
('join', 'Mitglied werden', '<h2>Werde Teil von KARMA</h2><p>Interessiert daran, der Elite beizutreten? Kontaktiere unsere Rekrutierungsoffiziere und beweise deine Würdigkeit.</p>', 1, 4),
('stats', 'Statistiken', '<h2>Unsere Erfolge</h2><p>Territorien: 15+<br>Aktive Mitglieder: 100+<br>Gewonnene Schlachten: 500+</p>', 1, 5);
