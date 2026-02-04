<?php
/**
 * Map Tile Generator - Enhanced Version
 * Generiert Kacheln für verschiedene Zoom-Stufen wie Google Maps
 * Version 2.2 - Optimierte Performance und Qualität
 */

class TileGenerator {
    private $tileSize = 512;
    private $maxZoom = 10;
    private $minZoom = 0;
    private $tilesDir = 'uploads/tiles/';
    private $progressCallback = null;
    private $useProgressive = true;
    private $jpegQuality = 85;
    private $webpQuality = 92;
    private $pngCompression = 6;
    private $useMultiFormat = true; // WebP + PNG + JPEG
    
    /**
     * Setzt einen Callback für Fortschritts-Updates
     */
    public function setProgressCallback($callback) {
        $this->progressCallback = $callback;
    }
    
    /**
     * Meldet Fortschritt
     */
    private function reportProgress($current, $total, $message = '') {
        if ($this->progressCallback && is_callable($this->progressCallback)) {
            call_user_func($this->progressCallback, $current, $total, $message);
        }
    }
    
    /**
     * Generiert Tiles aus einem Quellbild
     */
    public function generateTiles($sourceImage) {
        $startTime = microtime(true);
        
        if (!file_exists($sourceImage)) {
            return ['success' => false, 'error' => 'Quellbild nicht gefunden'];
        }
        
        if (!extension_loaded('gd')) {
            return ['success' => false, 'error' => 'GD-Extension nicht verfügbar'];
        }
        
        // Memory Limit erhöhen
        $currentLimit = ini_get('memory_limit');
        if ($currentLimit !== '-1') {
            $limitValue = (int) $currentLimit;
            if ($limitValue < 512) {
                @ini_set('memory_limit', '512M');
            }
        }
        
        $imageInfo = getimagesize($sourceImage);
        if (!$imageInfo) {
            return ['success' => false, 'error' => 'Ungültiges Bildformat'];
        }
        
        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        $this->reportProgress(0, 100, 'Lade Quellbild...');
        
        $source = $this->loadImage($sourceImage, $mimeType);
        if (!$source) {
            return ['success' => false, 'error' => 'Bild konnte nicht geladen werden'];
        }
        
        $this->reportProgress(10, 100, 'Lösche alte Tiles...');
        $this->clearTiles();
        
        if (!is_dir($this->tilesDir)) {
            mkdir($this->tilesDir, 0755, true);
        }
        
        $tilesGenerated = 0;
        $totalTilesEstimate = 0;
        
        // Berechne geschätzte Gesamtzahl
        for ($zoom = $this->maxZoom; $zoom >= $this->minZoom; $zoom--) {
            $scale = pow(2, $zoom);
            $scaledWidth = max(1, (int) round($sourceWidth * $scale / pow(2, $this->maxZoom)));
            $scaledHeight = max(1, (int) round($sourceHeight * $scale / pow(2, $this->maxZoom)));
            $cols = (int) ceil($scaledWidth / $this->tileSize);
            $rows = (int) ceil($scaledHeight / $this->tileSize);
            $totalTilesEstimate += $cols * $rows;
        }
        
        $this->reportProgress(15, 100, "Generiere $totalTilesEstimate Tiles...");
        
        // Generiere Tiles für jede Zoom-Stufe
        for ($zoom = $this->maxZoom; $zoom >= $this->minZoom; $zoom--) {
            $zoomProgress = 15 + ((($this->maxZoom - $zoom) / ($this->maxZoom - $this->minZoom + 1)) * 80);
            $this->reportProgress((int) $zoomProgress, 100, "Zoom-Level $zoom...");
            
            $scale = pow(2, $zoom);
            $scaledWidth = max(1, (int) round($sourceWidth * $scale / pow(2, $this->maxZoom)));
            $scaledHeight = max(1, (int) round($sourceHeight * $scale / pow(2, $this->maxZoom)));
            
            $scaledImage = $this->createScaledImage($source, $scaledWidth, $scaledHeight);
            
            if (!$scaledImage) {
                continue;
            }
            
            $cols = (int) ceil($scaledWidth / $this->tileSize);
            $rows = (int) ceil($scaledHeight / $this->tileSize);
            
            $zoomDir = $this->tilesDir . $zoom . '/';
            if (!is_dir($zoomDir)) {
                mkdir($zoomDir, 0755, true);
            }
            
            for ($x = 0; $x < $cols; $x++) {
                $xDir = $zoomDir . $x . '/';
                if (!is_dir($xDir)) {
                    mkdir($xDir, 0755, true);
                }
                
                for ($y = 0; $y < $rows; $y++) {
                    $tile = imagecreatetruecolor($this->tileSize, $this->tileSize);
                    
                    imagealphablending($tile, false);
                    imagesavealpha($tile, true);
                    $transparent = imagecolorallocatealpha($tile, 0, 0, 0, 127);
                    imagefill($tile, 0, 0, $transparent);
                    imagealphablending($tile, true);
                    
                    $srcX = (int) ($x * $this->tileSize);
                    $srcY = (int) ($y * $this->tileSize);
                    $copyWidth = (int) min($this->tileSize, $scaledWidth - $srcX);
                    $copyHeight = (int) min($this->tileSize, $scaledHeight - $srcY);
                    
                    imagecopyresampled(
                        $tile, $scaledImage,
                        0, 0, $srcX, $srcY,
                        $copyWidth, $copyHeight,
                        $copyWidth, $copyHeight
                    );
                    
                    $tilePath = $xDir . $y;
                    $this->saveTile($tile, $tilePath);
                    
                    imagedestroy($tile);
                    $tilesGenerated++;
                }
            }
            
            imagedestroy($scaledImage);
        }
        
        imagedestroy($source);
        
        $executionTime = round(microtime(true) - $startTime, 2);
        
        $this->reportProgress(95, 100, 'Speichere Metadaten...');
        
        $metadata = [
            'version' => '2.2',
            'tileSize' => $this->tileSize,
            'maxZoom' => $this->maxZoom,
            'minZoom' => $this->minZoom,
            'sourceWidth' => $sourceWidth,
            'sourceHeight' => $sourceHeight,
            'tilesGenerated' => $tilesGenerated,
            'generatedAt' => date('Y-m-d H:i:s'),
            'executionTime' => $executionTime,
            'formats' => $this->getAvailableFormats(),
            'quality' => [
                'jpeg' => $this->jpegQuality,
                'webp' => $this->webpQuality,
                'png' => $this->pngCompression
            ],
            'estimatedSize' => $this->calculateDirectorySize($this->tilesDir)
        ];
        
        file_put_contents(
            $this->tilesDir . 'metadata.json',
            json_encode($metadata, JSON_PRETTY_PRINT)
        );
        
        $this->reportProgress(100, 100, 'Fertig!');
        
        return [
            'success' => true,
            'tiles' => $tilesGenerated,
            'metadata' => $metadata,
            'executionTime' => $executionTime
        ];
    }
    
    /**
     * Lädt ein Bild optimiert
     */
    private function loadImage($path, $mimeType) {
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    return imagecreatefromwebp($path);
                }
                return false;
            default:
                return false;
        }
    }
    
    /**
     * Erstellt skaliertes Bild mit optimaler Qualität
     */
    private function createScaledImage($source, $width, $height) {
        $scaledImage = imagecreatetruecolor($width, $height);
        
        if (function_exists('imageantialias')) {
            imageantialias($scaledImage, true);
        }
        
        imagealphablending($scaledImage, false);
        imagesavealpha($scaledImage, true);
        
        if (function_exists('imagescale')) {
            $tempScaled = imagescale($source, $width, $height, IMG_BICUBIC_FIXED);
            if ($tempScaled) {
                imagedestroy($scaledImage);
                return $tempScaled;
            }
        }
        
        imagecopyresampled(
            $scaledImage, $source,
            0, 0, 0, 0,
            $width, $height,
            imagesx($source), imagesy($source)
        );
        
        return $scaledImage;
    }
    
    /**
     * Speichert Tile in optimierten Formaten
     */
    private function saveTile($tile, $basePath) {
        $saved = false;
        
        if ($this->useMultiFormat) {
            // WebP (beste Kompression)
            if (function_exists('imagewebp')) {
                imagewebp($tile, $basePath . '.webp', $this->webpQuality);
                $saved = true;
            }
            
            // JPEG (gute Kompression, universal)
            if (function_exists('imagejpeg')) {
                imagejpeg($tile, $basePath . '.jpg', $this->jpegQuality);
                $saved = true;
            }
            
            // PNG (Fallback, Transparenz)
            imagepng($tile, $basePath . '.png', $this->pngCompression);
        } else {
            imagepng($tile, $basePath . '.png', $this->pngCompression);
        }
        
        return $saved;
    }
    
    /**
     * Gibt verfügbare Formate zurück
     */
    private function getAvailableFormats() {
        $formats = ['png'];
        
        if (function_exists('imagewebp')) {
            $formats[] = 'webp';
        }
        if (function_exists('imagejpeg')) {
            $formats[] = 'jpeg';
        }
        
        return $formats;
    }
    
    /**
     * Berechnet Verzeichnisgröße
     */
    private function calculateDirectorySize($dir) {
        $size = 0;
        
        if (!is_dir($dir)) {
            return '0 B';
        }
        
        try {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $file) {
                $size += $file->getSize();
            }
        } catch (Exception $e) {
            return '0 B';
        }
        
        return $this->formatBytes($size);
    }
    
    /**
     * Formatiert Bytes
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Löscht alle Tiles
     */
    private function clearTiles() {
        if (!is_dir($this->tilesDir)) {
            return;
        }
        
        $this->deleteDirectory($this->tilesDir);
    }
    
    /**
     * Löscht Verzeichnis rekursiv
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
     * Lädt Metadaten
     */
    public function getMetadata() {
        $metadataFile = $this->tilesDir . 'metadata.json';
        if (!file_exists($metadataFile)) {
            return null;
        }
        
        return json_decode(file_get_contents($metadataFile), true);
    }
    
    /**
     * Setzt Tile-Einstellungen
     */
    public function setSettings($tileSize = null, $maxZoom = null, $minZoom = null) {
        if ($tileSize !== null) {
            $this->tileSize = (int) $tileSize;
        }
        if ($maxZoom !== null) {
            $this->maxZoom = (int) $maxZoom;
        }
        if ($minZoom !== null) {
            $this->minZoom = (int) $minZoom;
        }
    }
    
    /**
     * Setzt Qualitäts-Einstellungen
     */
    public function setQuality($jpeg = null, $webp = null, $png = null) {
        if ($jpeg !== null) {
            $this->jpegQuality = max(0, min(100, (int) $jpeg));
        }
        if ($webp !== null) {
            $this->webpQuality = max(0, min(100, (int) $webp));
        }
        if ($png !== null) {
            $this->pngCompression = max(0, min(9, (int) $png));
        }
    }
    
    /**
     * Aktiviert/Deaktiviert Multi-Format
     */
    public function setMultiFormat($enabled) {
        $this->useMultiFormat = (bool) $enabled;
    }
}
