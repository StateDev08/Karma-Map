<?php
/**
 * API Endpoint: Marker abrufen
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    $markers = getAllMarkers();
    
    echo json_encode([
        'success' => true,
        'markers' => $markers,
        'count' => count($markers)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fehler beim Laden der Marker: ' . $e->getMessage()
    ]);
}
