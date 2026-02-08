-- Karma-Seiten Datenbank-Update
-- Erstellt: 05.02.2026

USE pax_die_map;

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

-- Standard Karma-Sektionen einfügen
INSERT INTO karma_content (section, title, content, is_visible, sort_order) VALUES
('hero', 'Willkommen bei KARMA', '<h1>KARMA</h1><p>Die mächtigste Gilde von PAX DEI</p>', 1, 1),
('about', 'Über Uns', '<h2>Über KARMA</h2><p>KARMA ist die führende Gilde in PAX DEI. Wir dominieren die Karte und verteidigen unser Territorium mit eiserner Faust.</p>', 1, 2),
('features', 'Unsere Stärken', '<h2>Was uns auszeichnet</h2><ul><li>Erfahrene Leader</li><li>Aktive Community</li><li>Strategische Dominanz</li><li>Teamwork und Zusammenhalt</li></ul>', 1, 3),
('join', 'Mitglied werden', '<h2>Werde Teil von KARMA</h2><p>Interessiert daran, der Elite beizutreten? Kontaktiere unsere Rekrutierungsoffiziere und beweise deine Würdigkeit.</p>', 1, 4),
('stats', 'Statistiken', '<h2>Unsere Erfolge</h2><p>Territorien: 15+<br>Aktive Mitglieder: 100+<br>Gewonnene Schlachten: 500+</p>', 1, 5);

-- Einstellungen für Karma-Seite
INSERT INTO settings (setting_key, setting_value, description) VALUES
('karma_enabled', '1', 'Karma-Startseite aktiviert'),
('karma_background_image', '', 'Hintergrundbild für Karma-Seite'),
('karma_hero_logo', '', 'Hero-Logo für Karma-Seite'),
('karma_show_map_link', '1', 'Map-Link in Navigation anzeigen'),
('karma_hero_overlay', '0.3', 'Hero-Sektion Overlay-Transparenz (0-1)'),
('karma_theme', 'dark', 'Theme der Karma-Seite (dark/light)'),
('karma_discord_link', '', 'Discord-Einladungslink');

-- Berechtigungen für Karma-Verwaltung
INSERT INTO permissions (name, description, category) VALUES
('karma.view', 'Karma-Inhalte anzeigen', 'karma'),
('karma.edit', 'Karma-Inhalte bearbeiten', 'karma');

-- Super Admin Berechtigungen
INSERT INTO role_permissions (role, permission_name) VALUES
('super_admin', 'karma.view'),
('super_admin', 'karma.edit');

-- Admin Berechtigungen
INSERT INTO role_permissions (role, permission_name) VALUES
('admin', 'karma.view'),
('admin', 'karma.edit');

-- Moderator Berechtigungen
INSERT INTO role_permissions (role, permission_name) VALUES
('moderator', 'karma.view');
