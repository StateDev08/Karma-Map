<?php
/**
 * Map Tile Generator
 * Generiert Kacheln für verschiedene Zoom-Stufen wie Google Maps
 */

class TileGenerator {
    private $tileSize = 512;
    private $maxZoom = 10;
    private $minZoom = 0;
    private $tilesDir = 'uploads/tiles/';
    
    /**
     * Generiert Tiles aus einem Quellbild
     * 
     * @param string $sourceImage Pfad zum Quellbild
     * @return array Status und Informationen
     */
    public function generateTiles($sourceImage) {
        if (!file_exists($sourceImage)) {
            return ['success' => false, 'error' => 'Quellbild nicht gefunden'];
        }
        
        // Prüfe GD-Unterstützung
        if (!extension_loaded('gd')) {
            return ['success' => false, 'error' => 'GD-Extension nicht verfügbar'];
        }
        
        // Lade Quellbild
        $imageInfo = getimagesize($sourceImage);
        if (!$imageInfo) {
            return ['success' => false, 'error' => 'Ungültiges Bildformat'];
        }
        
        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        // Erstelle GD-Image
        switch ($mimeType) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($sourceImage);
                break;
            case 'image/png':
                $source = imagecreatefrompng($sourceImage);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($sourceImage);
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($sourceImage);
                break;
            default:
                return ['success' => false, 'error' => 'Nicht unterstütztes Bildformat'];
        }
        
        if (!$source) {
            return ['success' => false, 'error' => 'Bild konnte nicht geladen werden'];
        }
        
        // Lösche alte Tiles
        $this->clearTiles();
        
        // Erstelle Tiles-Verzeichnis
        if (!is_dir($this->tilesDir)) {
            mkdir($this->tilesDir, 0755, true);
        }
        
        $tilesGenerated = 0;
        
        // Generiere Tiles für jede Zoom-Stufe
        for ($zoom = $this->maxZoom; $zoom >= $this->minZoom; $zoom--) {
            $scale = pow(2, $zoom);
            $scaledWidth = (int) round($sourceWidth * $scale / pow(2, $this->maxZoom));
            $scaledHeight = (int) round($sourceHeight * $scale / pow(2, $this->maxZoom));
            
            // Stelle sicher dass Dimensionen mindestens 1px sind
            $scaledWidth = max(1, $scaledWidth);
            $scaledHeight = max(1, $scaledHeight);
            
            // Erstelle skaliertes Bild für diese Zoom-Stufe
            $scaledImage = imagecreatetruecolor($scaledWidth, $scaledHeight);
            
            // Aktiviere Antialiasing für bessere Qualität
            if (function_exists('imageantialias')) {
                imageantialias($scaledImage, true);
            }
            
            // Behalte Transparenz bei
            imagealphablending($scaledImage, false);
            imagesavealpha($scaledImage, true);
            
            // Skaliere das Bild mit bester Qualität
            // Verwende imagescale falls verfügbar (bessere Qualität)
            if (function_exists('imagescale')) {
                $tempScaled = imagescale($source, $scaledWidth, $scaledHeight, IMG_BICUBIC_FIXED);
                if ($tempScaled) {
                    imagedestroy($scaledImage);
                    $scaledImage = $tempScaled;
                } else {
                    // Fallback auf imagecopyresampled
                    imagecopyresampled(
                        $scaledImage, $source,
                        0, 0, 0, 0,
                        $scaledWidth, $scaledHeight,
                        (int) $sourceWidth, (int) $sourceHeight
                    );
                }
            } else {
                imagecopyresampled(
                    $scaledImage, $source,
                    0, 0, 0, 0,
                    $scaledWidth, $scaledHeight,
                    (int) $sourceWidth, (int) $sourceHeight
                );
            }
            
            // Erstelle Zoom-Verzeichnis
            $zoomDir = $this->tilesDir . $zoom . '/';
            if (!is_dir($zoomDir)) {
                mkdir($zoomDir, 0755, true);
            }
            
            // Berechne Anzahl der Tiles
            $cols = ceil($scaledWidth / $this->tileSize);
            $rows = ceil($scaledHeight / $this->tileSize);
            
            // Generiere einzelne Tiles
            for ($x = 0; $x < $cols; $x++) {
                $xDir = $zoomDir . $x . '/';
                if (!is_dir($xDir)) {
                    mkdir($xDir, 0755, true);
                }
                
                for ($y = 0; $y < $rows; $y++) {
                    $tile = imagecreatetruecolor($this->tileSize, $this->tileSize);
                    
                    // Aktiviere Antialiasing
                    if (function_exists('imageantialias')) {
                        imageantialias($tile, true);
                    }
                    
                    // Behalte Transparenz bei
                    imagealphablending($tile, false);
                    imagesavealpha($tile, true);
                    
                    // Fülle mit transparentem Hintergrund
                    $transparent = imagecolorallocatealpha($tile, 0, 0, 0, 127);
                    imagefill($tile, 0, 0, $transparent);
                    
                    // Kopiere Tile-Bereich
                    $srcX = $x * $this->tileSize;
                    $srcY = $y * $this->tileSize;
                    $copyWidth = (int) min($this->tileSize, $scaledWidth - $srcX);
                    $copyHeight = (int) min($this->tileSize, $scaledHeight - $srcY);
                    
                    // Verwende imagecopyresampled für bessere Qualität beim Kopieren
                    imagecopyresampled(
                        $tile, $scaledImage,
                        0, 0, (int) $srcX, (int) $srcY,
                        $copyWidth, $copyHeight,
                        $copyWidth, $copyHeight
                    );
                    
                    // Speichere Tile - versuche WebP für bessere Kompression
                    $tilePath = $xDir . $y;
                    $saved = false;
                    
                    // Versuche WebP (bessere Kompression bei gleicher Qualität)
                    if (function_exists('imagewebp')) {
                        $saved = imagewebp($tile, $tilePath . '.webp', 90);
                        if ($saved) {
                            // Erstelle auch PNG als Fallback
                            imagepng($tile, $tilePath . '.png', 6);
                        }
                    }
                    
                    // Fallback auf PNG
                    if (!$saved) {
                        imagepng($tile, $tilePath . '.png', 6);
                    }
                    
                    imagedestroy($tile);
                    
                    $tilesGenerated++;
                }
            }
            
            imagedestroy($scaledImage);
        }
        
        imagedestroy($source);
        
        // Speichere Metadaten
        $metadata = [
            'tileSize' => $this->tileSize,
            'maxZoom' => $this->maxZoom,
            'minZoom' => $this->minZoom,
            'sourceWidth' => $sourceWidth,
            'sourceHeight' => $sourceHeight,
            'tilesGenerated' => $tilesGenerated,
            'generatedAt' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents(
            $this->tilesDir . 'metadata.json',
            json_encode($metadata, JSON_PRETTY_PRINT)
        );
        
        return [
            'success' => true,
            'tiles' => $tilesGenerated,
            'metadata' => $metadata
        ];
    }
    
    /**
     * Löscht alle vorhandenen Tiles
     */
    private function clearTiles() {
        if (!is_dir($this->tilesDir)) {
            return;
        }
        
        $this->deleteDirectory($this->tilesDir);
    }
    
    /**
     * Löscht ein Verzeichnis rekursiv
     */
    private function deleteDirectory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
    
    /**
     * Prüft ob Tiles existieren
     */
    public function tilesExist() {
        return file_exists($this->tilesDir . 'metadata.json');
    }
    
    /**
     * Lädt Tile-Metadaten
     */
    public function getMetadata() {
        $metadataFile = $this->tilesDir . 'metadata.json';
        if (!file_exists($metadataFile)) {
            return null;
        }
        
        return json_decode(file_get_contents($metadataFile), true);
    }
    
    /**
     * Setzt benutzerdefinierte Tile-Einstellungen
     */
    public function setSettings($tileSize = null, $maxZoom = null, $minZoom = null) {
        if ($tileSize !== null) {
            $this->tileSize = $tileSize;
        }
        if ($maxZoom !== null) {
            $this->maxZoom = $maxZoom;
        }
        if ($minZoom !== null) {
            $this->minZoom = $minZoom;
        }
    }
}
