-- Update-Script für PAX DEI Map v2.2.0
-- Fügt neue Map-Settings zur bestehenden Datenbank hinzu

USE pax_die_map;

-- Neue Map-Einstellungen hinzufügen (falls noch nicht vorhanden)
INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES
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
('map_auto_pan', '1', 'Auto-Pan bei Marker-Klick');

-- Erfolgsmeldung
SELECT 'Update auf Version 2.2.0 erfolgreich abgeschlossen!' AS Status;
SELECT COUNT(*) AS 'Neue Settings hinzugefügt' FROM settings WHERE setting_key LIKE 'map_%';
