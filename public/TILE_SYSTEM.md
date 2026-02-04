# Tile System Dokumentation v2.2

## Übersicht

Das erweiterte Tile-System generiert hochoptimierte Karten-Tiles in mehreren Formaten für maximale Performance und Kompatibilität.

## Features

### Multi-Format Support
- **WebP**: Modernes Format mit bester Kompression (~30% kleiner als JPEG)
- **JPEG**: Universelle Kompatibilität für ältere Browser
- **PNG**: Verlustfreie Qualität als Fallback

### Intelligente Format-Auswahl
Der Browser wählt automatisch das beste verfügbare Format:
1. **WebP** (wenn unterstützt) - Beste Performance
2. **JPEG** (Fallback) - Universelle Kompatibilität
3. **PNG** (Fallback) - Maximale Qualität

```javascript
// Browser-Erkennung
if (formats.includes('webp') && supportsWebP()) {
    tileExt = '.webp';
} else if (formats.includes('jpeg')) {
    tileExt = '.jpg';
} else {
    tileExt = '.png';
}
```

### Qualitätseinstellungen

**Standard-Konfiguration:**
- **WebP**: 92% Qualität - Optimale Balance zwischen Größe und Qualität
- **JPEG**: 85% Qualität - Gute Qualität bei kleiner Dateigröße
- **PNG**: Kompression Level 6 - Schnelle Kompression mit guter Größe

**Anpassung:**
```php
$generator = new TileGenerator();
$generator->setQuality(90, 95, 9); // JPEG, WebP, PNG
```

### Progress-Tracking

Fortschritts-Callbacks ermöglichen Echtzeit-Updates:

```php
$generator->setProgressCallback(function($current, $total, $message) {
    $percent = round(($current / $total) * 100);
    echo "[$percent%] $message\n";
});
```

### Erweiterte Metadaten

Das System generiert umfassende Metadaten in `metadata.json`:

```json
{
    "version": "2.2",
    "tileSize": 512,
    "maxZoom": 10,
    "minZoom": 0,
    "sourceWidth": 3000,
    "sourceHeight": 3000,
    "tilesGenerated": 250,
    "generatedAt": "2024-02-04 14:30:00",
    "executionTime": 45.23,
    "formats": ["webp", "jpeg", "png"],
    "quality": {
        "jpeg": 85,
        "webp": 92,
        "png": 6
    },
    "estimatedSize": "4.5 MB"
}
```

## Technische Details

### Speicheroptimierung

- **Auto-Adjustment**: Erhöht PHP-Memory-Limit auf 512M
- **Schrittweise Verarbeitung**: Tiles werden einzeln verarbeitet
- **Ressourcen-Freigabe**: Bildressourcen werden sofort freigegeben

### Skalierungs-Algorithmus

Verwendet `IMG_BICUBIC_FIXED` für optimale Bildqualität:

```php
imagecopyresampled(
    $scaled, $source,
    0, 0, 0, 0,
    $width, $height,
    $sourceWidth, $sourceHeight
);
```

### Error Handling

**Canvas-basierte Error-Tiles:**
- Diagonales Streifenmuster
- Klar erkennbare Fehlertiles
- Verhindert kaputte Bilder

```javascript
function createErrorTile() {
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = 256;
    const ctx = canvas.getContext('2d');
    
    ctx.fillStyle = '#1a1a1a';
    ctx.fillRect(0, 0, 256, 256);
    
    // Diagonal stripes
    ctx.strokeStyle = '#333';
    ctx.lineWidth = 2;
    for (let i = 0; i < 256; i += 20) {
        ctx.beginPath();
        ctx.moveTo(i, 0);
        ctx.lineTo(0, i);
        ctx.stroke();
    }
    
    return canvas.toDataURL();
}
```

## Admin-Oberfläche

### Metadaten-Anzeige

Die Admin-Oberfläche zeigt 10 detaillierte Informationen:

1. **Quell-Auflösung**: Original-Bildgröße
2. **Tile-Größe**: Pixel pro Tile (Standard: 512x512)
3. **Zoom-Stufen**: Min/Max Zoom + Anzahl
4. **Generierte Tiles**: Gesamtzahl der Tiles
5. **Gesamt-Größe**: Geschätzte Größe auf Disk
6. **Generierungs-Zeit**: Ausführungszeit in Sekunden
7. **Formate**: Liste verfügbarer Formate
8. **Qualität**: Qualitätseinstellungen pro Format
9. **Generiert am**: Zeitstempel der Generierung
10. **System-Version**: Tile-Generator Version

### Aktionen

- **Tiles neu generieren**: Regeneriert alle Tiles mit aktuellen Einstellungen
- **Tile-System deaktivieren**: Deaktiviert das Tile-System

## Performance

### Ladezeiten

**Durchschnittliche Verbesserungen:**
- WebP vs PNG: **~40% weniger Dateigröße**
- WebP vs JPEG: **~30% weniger Dateigröße**
- Intelligente Browser-Auswahl: **Keine zusätzliche Latenz**

### Caching

Browser-Cache-Header werden automatisch gesetzt:
- Tiles werden im Browser gecached
- CDN-freundlich
- Minimale Server-Last

### Disk-Usage

**Beispiel für 3000x3000 Bild:**
- PNG only: ~15 MB
- Multi-Format (WebP + JPEG + PNG): ~20 MB (33% mehr Speicher)
- Netto-Gewinn: 40% schnellere Ladezeiten für WebP-Clients

## Browser-Kompatibilität

| Browser | WebP | JPEG | PNG |
|---------|------|------|-----|
| Chrome 90+ | ✅ | ✅ | ✅ |
| Firefox 85+ | ✅ | ✅ | ✅ |
| Safari 14+ | ✅ | ✅ | ✅ |
| Edge 90+ | ✅ | ✅ | ✅ |
| IE 11 | ❌ | ✅ | ✅ |

## API-Referenz

### TileGenerator Class

```php
class TileGenerator
{
    // Konstruktor
    public function __construct($tileSize = 512)
    
    // Tiles generieren
    public function generateTiles($sourcePath, $outputDir)
    
    // Qualität einstellen
    public function setQuality($jpeg, $webp, $png)
    
    // Multi-Format aktivieren/deaktivieren
    public function setMultiFormat($enabled)
    
    // Progress Callback setzen
    public function setProgressCallback($callback)
    
    // Verfügbare Formate abrufen
    public function getAvailableFormats()
}
```

### JavaScript API

```javascript
// Tile-Layer laden
loadTileLayer(metadata)

// WebP-Support prüfen
supportsWebP()

// Error-Tile erstellen
createErrorTile()

// Loading-Indikator aktualisieren
updateLoadingIndicator(loading)
```

## Best Practices

### 1. Optimale Quellbilder

- **Format**: PNG oder JPEG
- **Auflösung**: Vielfaches von 512px für beste Ergebnisse
- **Dateigröße**: < 50 MB für schnelle Verarbeitung

### 2. Zoom-Level

- **Kleine Karten (< 2000px)**: maxZoom: 3-4
- **Mittlere Karten (2000-5000px)**: maxZoom: 5-7
- **Große Karten (> 5000px)**: maxZoom: 8-10

### 3. Performance

- Tiles im ersten Durchlauf generieren
- Nicht bei jedem Upload regenerieren
- Cache-Header nutzen
- CDN für statische Tile-Dateien

### 4. Wartung

- Alte Tile-Ordner regelmäßig löschen
- Disk-Space überwachen
- Metadaten-Logs prüfen

## Troubleshooting

### Problem: Tiles werden nicht geladen

**Lösung:**
1. Metadaten-Datei prüfen: `uploads/tiles/metadata.json`
2. Tile-Ordner prüfen: `uploads/tiles/{z}/{x}/`
3. Browser-Konsole auf Fehler prüfen
4. Server-Logs prüfen

### Problem: Schlechte Bildqualität

**Lösung:**
1. Qualitätseinstellungen erhöhen:
   ```php
   $generator->setQuality(95, 98, 9);
   ```
2. Höhere Quellbild-Auflösung verwenden
3. PNG statt JPEG für verlustfreie Qualität

### Problem: Zu langsame Generierung

**Lösung:**
1. Memory-Limit erhöhen: `ini_set('memory_limit', '1024M')`
2. Kleinere Tile-Größe: `new TileGenerator(256)`
3. Weniger Zoom-Level
4. Multi-Format deaktivieren: `setMultiFormat(false)`

### Problem: Zu viel Speicherplatz

**Lösung:**
1. Nur WebP + JPEG generieren (PNG weglassen)
2. Qualität reduzieren
3. Kleinere Zoom-Bereiche
4. Alte Tiles löschen

## Migration von v2.1

1. **Backup erstellen**: Alte Tiles sichern
2. **Code aktualisieren**: Neue TileGenerator-Klasse deployen
3. **Tiles regenerieren**: Über Admin-Panel neu generieren
4. **Testen**: Alle Zoom-Level und Browser testen
5. **Alte Tiles löschen**: Nach erfolgreicher Migration

## Changelog

### v2.2 (2024-02-04)
- ✨ Multi-Format Support (WebP + JPEG + PNG)
- ✨ Progress-Tracking System
- ✨ Erweiterte Metadaten (10 Felder)
- ✨ Intelligente Browser-basierte Format-Auswahl
- ✨ Canvas-basierte Error-Tiles
- ✨ Loading-Indikator UI
- ✨ Disk-Size Berechnung
- ⚡ Performance-Optimierungen
- 🎨 Enhanced Admin-Oberfläche mit Grid-Layout

### v2.1 (2024-01-15)
- ✨ Basis Tile-System
- ✨ PNG-Export
- ✨ Zoom-Level 0-5

## Support

Bei Fragen oder Problemen:
1. Dokumentation prüfen
2. Browser-Konsole checken
3. PHP Error-Logs checken
4. GitHub Issues erstellen
