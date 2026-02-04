# Map-Funktionen & Werkzeuge - Übersicht

## 🎯 Neue Map-Werkzeuge (Version 2.2.0)

### 1. Messwerkzeug 📏
**Button:** Lineal-Icon (Ruler)
**Funktion:** Distanzen zwischen Punkten auf der Karte messen

**Bedienung:**
1. Werkzeug aktivieren (Button klicken)
2. Punkte auf der Karte klicken
3. Automatische Distanzberechnung in Pixeln
4. Rechtsklick zum Beenden
5. Button erneut klicken zum Deaktivieren

### 2. Koordinaten-Anzeige 🎯
**Position:** Unten links
**Funktion:** Zeigt aktuellen Zoom-Level und Koordinaten an

**Informationen:**
- Aktueller Zoom-Level
- X/Y-Koordinaten der Mausposition (live)
- Immer sichtbar während der Map-Nutzung

### 3. Koordinaten-Suche 🔍
**Button:** Suchlinse-Icon (Search Location)
**Funktion:** Direkt zu bestimmten Koordinaten springen

**Bedienung:**
1. Button klicken zum Öffnen
2. X- und Y-Koordinate eingeben
3. Pfeil-Button klicken
4. Map zoomt zur Position und setzt einen temporären Marker

### 4. Vollbild-Modus 🖥️
**Button:** Expand-Icon
**Funktion:** Map im Vollbildmodus anzeigen

**Bedienung:**
- Klick zum Aktivieren des Vollbildmodus
- ESC oder erneuter Klick zum Beenden
- Alle Werkzeuge bleiben verfügbar

### 5. Mini-Map 🗺️
**Position:** Unten rechts
**Funktion:** Kleine Übersichtskarte zur Navigation

**Features:**
- Zeigt gesamte Karte in klein
- Aktueller Viewport als Rechteck markiert
- Klick in Mini-Map zum Navigieren
- Minimierbar/Maximierbar

### 6. Zeichenwerkzeuge ✏️
**Button:** Stift-Icon (Pencil)
**Funktion:** Formen und Marker auf der Karte zeichnen

**Verfügbare Werkzeuge:**
- **Marker:** Eigene Punkte setzen (ziehbar)
- **Linie:** Polylinien zeichnen (mit Längenberechnung)
- **Polygon:** Flächen zeichnen
- **Kreis:** Kreise mit Radius zeichnen
- **Löschen:** Alle Zeichnungen entfernen

**Bedienung Linie/Polygon:**
1. Werkzeug auswählen
2. Punkte durch Klicken setzen
3. Rechtsklick zum Beenden

**Bedienung Kreis:**
1. Werkzeug auswählen
2. Erster Klick = Mittelpunkt
3. Zweiter Klick = Radius-Punkt

### 7. Screenshot-Funktion 📷
**Button:** Kamera-Icon
**Funktion:** Aktuellen Map-Ausschnitt als PNG speichern

**Bedienung:**
- Gewünschten Ausschnitt wählen
- Button klicken
- Browser lädt PNG-Datei herunter

### 8. Maßstabsleiste 📐
**Position:** Unten rechts
**Funktion:** Zeigt Größenverhältnisse auf der Karte

**Features:**
- Metrische Angaben
- Passt sich automatisch an Zoom-Level an
- Zeigt tatsächliche Pixel-Distanzen

### 9. Raster-Overlay 📊
**Funktion:** Koordinaten-Raster über der Karte

**Features:**
- Anpassbare Raster-Größe (Standard: 100px)
- Semi-transparente weiße Linien
- Hilft bei der Orientierung
- Im Admin-Panel konfigurierbar

### 10. Live Maus-Koordinaten 🖱️
**Position:** In Koordinaten-Anzeige (unten links)
**Funktion:** Zeigt Echtzeit-Position des Mauszeigers

**Features:**
- Automatische Aktualisierung bei Mausbewegung
- Präzision auf 2 Dezimalstellen
- Nützlich für genaue Positionierung

---

## ⚙️ Admin-Einstellungen

### Map-Werkzeuge aktivieren/deaktivieren
- ✅ Koordinaten-Anzeige
- ✅ Mini-Map
- ✅ Messwerkzeug
- ✅ Zeichenwerkzeuge
- ✅ Vollbild-Modus
- ✅ Koordinaten-Suche
- ✅ Raster-Overlay
- ✅ Live Maus-Koordinaten
- ✅ Maßstabsleiste

### Interaktions-Einstellungen
- ✅ Zoom-Animation
- ✅ Doppelklick-Zoom
- ✅ Mausrad-Zoom
- ✅ Marker-Clustering
- ✅ Auto-Pan bei Marker-Klick

### Positions- und Raster-Einstellungen
- Standard-Position X (0)
- Standard-Position Y (0)
- Raster-Größe (10-500 Pixel)

---

## 🎮 Tastenkombinationen & Interaktionen

| Aktion | Steuerung |
|--------|-----------|
| Zoom In | Mausrad hoch / Doppelklick |
| Zoom Out | Mausrad runter |
| Verschieben | Linke Maustaste gedrückt + Ziehen |
| Messung beenden | Rechtsklick |
| Zeichnung beenden | Rechtsklick |
| Vollbild beenden | ESC |

---

## 💡 Tipps & Best Practices

### Messwerkzeug
- Für präzise Messungen auf höherer Zoom-Stufe arbeiten
- Mehrere Messpunkte für komplexe Routen möglich
- Distanz wird in Pixel angegeben (basierend auf Original-Bildgröße)

### Zeichenwerkzeuge
- Gezeichnete Elemente bleiben nur in aktueller Session
- Marker sind ziehbar nach dem Setzen
- "Alle löschen" entfernt alle Zeichnungen auf einmal

### Koordinaten-Suche
- Nutze die Koordinaten-Anzeige zum Ablesen von X/Y
- Hilfreich zum Teilen von Positionen mit anderen Spielern
- Koordinaten basieren auf Leaflet Simple CRS

### Screenshots
- Stelle sicher, dass html2canvas geladen ist
- Funktioniert am besten mit stabiler Internetverbindung
- Alle sichtbaren Marker und Overlays werden mit erfasst

### Performance
- Bei vielen Markern: Clustering aktivieren
- Raster-Overlay kann bei sehr hohen Zoom-Stufen Performance beeinflussen
- Mini-Map minimieren wenn nicht benötigt

---

## 🔧 Technische Details

### Verwendete Bibliotheken
- **Leaflet.js 1.9.4** - Basis-Map-Engine
- **Leaflet.Fullscreen** - Vollbild-Funktionalität
- **Leaflet.MiniMap** - Übersichtskarte
- **html2canvas 1.4.1** - Screenshot-Erstellung

### Custom Controls
Alle Werkzeuge sind als Leaflet Custom Controls implementiert:
- `L.Control.Coordinates` - Koordinaten-Anzeige
- `L.Control.CoordinateSearch` - Koordinaten-Suche
- `L.Control.MeasureTool` - Messwerkzeug
- `L.Control.DrawingTools` - Zeichenwerkzeuge
- `L.Control.Screenshot` - Screenshot-Funktion

### Datei-Struktur
```
assets/
├── js/
│   ├── map.js              # Haupt-Map-Logik
│   └── map-extended.js     # Erweiterte Werkzeuge (NEU)
└── css/
    └── style.css           # Styling inkl. Controls
```

---

## 📝 Changelog Map-Features

**Version 2.2.0 (04.02.2026)**
- ✨ 10 neue Map-Werkzeuge hinzugefügt
- ✨ 20+ neue Admin-Einstellungen
- ✨ Vollständige Werkzeug-Kontrolle im Admin-Panel
- 🔧 Custom Controls mit Discord-Style UI
- 🔧 Performance-Optimierungen
- 📚 Umfangreiche Dokumentation

---

**Für Fragen oder Feature-Requests:**
GitHub Issues: [github.com/StateDev08/Karma-Map/issues](https://github.com/StateDev08/Karma-Map/issues)
