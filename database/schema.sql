-- PAX Die Map - Datenbank Schema
-- Erstellt: 01.02.2026

CREATE DATABASE IF NOT EXISTS pax_die_map CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pax_die_map;

-- Benutzer-Tabelle (nur Admins)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    is_admin TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username)
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

-- Standard-Einstellungen einfügen
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_title', 'PAX Die Map - KARMA', 'Website-Titel'),
('primary_color', '#FF0000', 'Primärfarbe (Rot)'),
('secondary_color', '#000000', 'Sekundärfarbe (Schwarz)'),
('accent_color', '#DC143C', 'Akzentfarbe'),
('logo_type', 'text', 'Logo-Typ: text, image oder custom'),
('logo_text', 'KARMA', 'Text-Logo'),
('logo_image', '', 'Pfad zum Logo-Bild'),
('map_image', '', 'Pfad zum Map-Hintergrundbild'),
('map_default_zoom', '2', 'Standard-Zoom-Level'),
('map_max_zoom', '5', 'Maximaler Zoom'),
('map_min_zoom', '1', 'Minimaler Zoom');

-- Standard-Admin-User (Passwort: admin123 - BITTE ÄNDERN!)
-- Passwort-Hash für 'admin123'
INSERT INTO users (username, password, email, is_admin) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@karma.pax', 1);

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
('KARMA', 'KARMA', 'Die mächtigste Gilde von PAX Die', '#DC143C', 'Guild Master', 1);
