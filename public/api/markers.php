<?php
/**
 * API Endpoint: Marker abrufen
 */

header('Content-Type: application/json');

// Dynamischer Pfad zu includes
$includesPath = file_exists(__DIR__ . '/../../includes/config.php') 
    ? __DIR__ . '/../../includes/' 
    : (file_exists(__DIR__ . '/../../../includes/config.php') 
        ? __DIR__ . '/../../../includes/' 
        : __DIR__ . '/../../includes/');

require_once $includesPath . 'config.php';
require_once $includesPath . 'db.php';
require_once $includesPath . 'functions.php';

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
