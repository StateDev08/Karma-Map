<?php
/**
 * Hilfs-Funktionen
 */

// HTML-Escaping
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// JSON-Antwort senden
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Erfolgsmeldung
function success($message, $data = []) {
    return jsonResponse(array_merge(['success' => true, 'message' => $message], $data));
}

// Fehlermeldung
function error($message, $statusCode = 400, $data = []) {
    return jsonResponse(array_merge(['success' => false, 'error' => $message], $data), $statusCode);
}

// Einstellung abrufen
function getSetting($key, $default = null) {
    $setting = db()->fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
    return $setting ? $setting['setting_value'] : $default;
}

// Einstellung speichern
function setSetting($key, $value, $description = '') {
    $existing = db()->fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
    
    if ($existing) {
        db()->update('settings', 
            ['setting_value' => $value],
            'setting_key = :key',
            ['key' => $key]
        );
    } else {
        db()->insert('settings', [
            'setting_key' => $key,
            'setting_value' => $value,
            'description' => $description
        ]);
    }
}

// Datei-Upload verarbeiten
function handleImageUpload($fileInput, $imageType = 'map') {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Keine Datei hochgeladen oder Upload-Fehler');
    }
    
    $file = $_FILES[$fileInput];
    
    // Validierung
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        throw new Exception('Datei zu groß (max. ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . ' MB)');
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        throw new Exception('Ungültiger Dateityp. Erlaubt: JPG, PNG, GIF, WebP');
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        throw new Exception('Ungültige Dateiendung');
    }
    
    // Upload-Verzeichnis erstellen
    $uploadDir = UPLOAD_PATH . '/' . $imageType;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Eindeutiger Dateiname
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . '/' . $filename;
    $relativeUrl = UPLOAD_URL . '/' . $imageType . '/' . $filename;
    
    // Datei verschieben
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Fehler beim Speichern der Datei');
    }
    
    // In Datenbank speichern
    $imageId = db()->insert('uploaded_images', [
        'filename' => $filename,
        'original_filename' => $file['name'],
        'file_path' => $relativeUrl,
        'file_size' => $file['size'],
        'mime_type' => $mimeType,
        'image_type' => $imageType,
        'uploaded_by' => Auth::userId()
    ]);
    
    return [
        'id' => $imageId,
        'filename' => $filename,
        'url' => $relativeUrl,
        'path' => $filePath,
        'size' => $file['size']
    ];
}

// Alle aktiven Gilden abrufen
function getActiveGuilds() {
    return db()->fetchAll("SELECT * FROM guilds WHERE is_active = 1 ORDER BY name");
}

// Alle Marker-Typen abrufen
function getMarkerTypes() {
    return db()->fetchAll("SELECT * FROM marker_types WHERE is_visible = 1 ORDER BY sort_order, name");
}

// Alle Marker für die Map abrufen
function getAllMarkers() {
    $sql = "SELECT m.*, mt.name as type_name, mt.icon, mt.color as type_color,
                   g.name as guild_name, g.tag as guild_tag, g.color as guild_color
            FROM markers m
            LEFT JOIN marker_types mt ON m.marker_type_id = mt.id
            LEFT JOIN guilds g ON m.guild_id = g.id
            WHERE m.is_visible = 1
            ORDER BY m.created_at DESC";
    
    $markers = db()->fetchAll($sql);
    
    // Koordinaten zu Floats konvertieren
    foreach ($markers as &$marker) {
        $marker['x_position'] = (float)$marker['x_position'];
        $marker['y_position'] = (float)$marker['y_position'];
    }
    
    return $markers;
}
