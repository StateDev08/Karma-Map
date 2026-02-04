# Admin-Panel Map-Einstellungen - Dokumentation

## 📍 Zugriff
**Admin Panel → Einstellungen → Map-Einstellungen**

---

## 🎛️ Zoom-Einstellungen

### Standard-Zoom
- **Bereich:** 1-10
- **Standard:** 2
- **Beschreibung:** Zoom-Level beim Laden der Map
- **Empfehlung:** 2-3 für gute Übersicht

### Maximaler Zoom
- **Bereich:** 1-10
- **Standard:** 5
- **Beschreibung:** Maximales Hineinzoomen
- **Empfehlung:** 10-15 für sehr detaillierte Ansicht

### Minimaler Zoom
- **Bereich:** Unbegrenzt (auch negative Werte)
- **Standard:** 1
- **Beschreibung:** Maximales Herauszoomen
- **Empfehlung:** -10 bis 0 für große Karten
- **Tipp:** Negative Werte ermöglichen Zoom weiter heraus als Karten-Größe

---

## 📐 Positions-Einstellungen

### Standard-Position X
- **Typ:** Dezimalzahl
- **Standard:** 0
- **Beschreibung:** X-Koordinate der Startposition
- **Verwendung:** 
  - 0 = Linke Kante
  - Positive Werte = Nach rechts
  - Negative Werte = Nach links

### Standard-Position Y
- **Typ:** Dezimalzahl
- **Standard:** 0
- **Beschreibung:** Y-Koordinate der Startposition
- **Verwendung:** 
  - 0 = Obere Kante
  - Positive Werte = Nach unten
  - Negative Werte = Nach oben

**So findest du die gewünschte Position:**
1. Aktiviere "Live Maus-Koordinaten"
2. Bewege Maus zu gewünschter Startposition
3. Notiere X/Y-Werte aus der Koordinaten-Anzeige
4. Trage Werte in Einstellungen ein

---

## 🔧 Map-Werkzeuge

### ✅ Koordinaten-Anzeige
**Standardwert:** Aktiviert
**Beschreibung:** Zeigt permanente Box mit Zoom-Level und Koordinaten unten links
**Empfehlung:** Aktiviert lassen für bessere Orientierung
**Deaktivieren wenn:** Minimalistisches UI gewünscht

### ✅ Mini-Map
**Standardwert:** Aktiviert
**Beschreibung:** Kleine Übersichtskarte unten rechts
**Empfehlung:** Aktiviert für große Karten
**Deaktivieren wenn:** Map klein ist oder Performance wichtig
**Performance-Impact:** Mittel

### ✅ Messwerkzeug
**Standardwert:** Aktiviert
**Beschreibung:** Button zum Messen von Distanzen
**Empfehlung:** Aktiviert für taktische Planung
**Deaktivieren wenn:** Nur Marker-Anzeige gewünscht
**Nutzung:** Klick auf Button, dann Punkte auf Map klicken

### ✅ Zeichenwerkzeuge
**Standardwert:** Aktiviert
**Beschreibung:** Marker, Linien, Polygone und Kreise zeichnen
**Empfehlung:** Aktiviert für Gilden-Planung
**Deaktivieren wenn:** Öffentliche Map ohne User-Interaktion
**Nutzung:** Dropdown mit 5 Werkzeugen

### ✅ Vollbild-Modus
**Standardwert:** Aktiviert
**Beschreibung:** Button zum Vollbild-Toggle
**Empfehlung:** Immer aktiviert lassen
**Deaktivieren wenn:** Embedded-Map in anderem System
**Tastenkombination:** ESC zum Beenden

### ✅ Koordinaten-Suche
**Standardwert:** Aktiviert
**Beschreibung:** Suchfeld zum Springen zu X/Y-Position
**Empfehlung:** Aktiviert für schnelle Navigation
**Deaktivieren wenn:** Nutzer sollen nur scrollen/zoomen
**Nutzung:** Button öffnet Eingabefelder für X und Y

### ✅ Raster-Overlay
**Standardwert:** Deaktiviert
**Beschreibung:** Koordinaten-Raster über der Karte
**Empfehlung:** Deaktiviert (nur bei Bedarf aktivieren)
**Deaktivieren wenn:** Map zu voll oder verwirrt Nutzer
**Performance-Impact:** Mittel bei hohem Zoom
**Raster-Größe:** Einstellbar 10-500 Pixel

### ✅ Live Maus-Koordinaten
**Standardwert:** Aktiviert
**Beschreibung:** Zeigt X/Y-Position des Mauszeigers in Echtzeit
**Empfehlung:** Aktiviert für präzise Arbeit
**Deaktivieren wenn:** Performance-Probleme oder nicht benötigt
**Performance-Impact:** Minimal

### ✅ Maßstabsleiste
**Standardwert:** Aktiviert
**Beschreibung:** Zeigt Pixel-Distanzen am unteren rechten Rand
**Empfehlung:** Aktiviert für Größenverständnis
**Deaktivieren wenn:** Nicht relevant für Spieler
**Passt sich an:** Automatisch an Zoom-Level

---

## ⚡ Interaktions-Einstellungen

### ✅ Zoom-Animation
**Standardwert:** Aktiviert
**Beschreibung:** Smooth Animation beim Zoomen
**Empfehlung:** Aktiviert für bessere UX
**Deaktivieren wenn:** Performance-Probleme oder Nutzer bevorzugen instant
**Performance-Impact:** Minimal

### ✅ Doppelklick-Zoom
**Standardwert:** Aktiviert
**Beschreibung:** Doppelklick zoomt hinein und zentriert
**Empfehlung:** Aktiviert (Standard-Verhalten)
**Deaktivieren wenn:** Konflikte mit anderen Interaktionen
**Alternative:** Zoom nur über Buttons/Mausrad

### ✅ Mausrad-Zoom
**Standardwert:** Aktiviert
**Beschreibung:** Scrollen zoomt hinein/heraus
**Empfehlung:** Aktiviert (intuitiv)
**Deaktivieren wenn:** Nutzer sollen nicht versehentlich zoomen
**Tastenkombination:** STRG+Scroll bleibt immer aktiv

### ✅ Marker-Clustering
**Standardwert:** Deaktiviert
**Beschreibung:** Gruppiert nahe Marker bei kleinem Zoom
**Empfehlung:** Aktiviert bei >100 Markern
**Deaktivieren wenn:** Wenige Marker oder alle immer sichtbar
**Performance-Impact:** Positiv bei vielen Markern
**Erfordert:** Zusätzliches Plugin (Leaflet.markercluster)

### ✅ Auto-Pan bei Marker-Klick
**Standardwert:** Aktiviert
**Beschreibung:** Map zentriert sich automatisch auf geklickten Marker
**Empfehlung:** Aktiviert für bessere Navigation
**Deaktivieren wenn:** Nutzer sollen Map-Position manuell kontrollieren
**Verhalten:** Smooth Pan-Animation zur Marker-Position

---

## 📏 Raster-Größe

### Wert: 10-500 Pixel
**Standardwert:** 100
**Beschreibung:** Größe der Quadrate im Raster-Overlay
**Empfehlung:** 
- **50-100:** Für präzise Koordinaten
- **100-200:** Standard-Nutzung
- **200-500:** Grobe Orientierung

**Berechnung der idealen Größe:**
```
Kartengröße: 3000x3000 Pixel
Gewünschte Unterteilung: 30x30 Quadrate
Raster-Größe = 3000 / 30 = 100 Pixel
```

---

## 💡 Empfohlene Konfigurationen

### Konfiguration 1: Maximale Features (Standard)
```
✅ Alle Werkzeuge aktiviert
✅ Alle Interaktionen aktiviert
✅ Live Maus-Koordinaten
✅ Maßstabsleiste
❌ Raster-Overlay (nur bei Bedarf)
❌ Marker-Clustering (bei <100 Markern)

Zoom: Min -10 / Default 2 / Max 15
Position: X=0, Y=0 (Zentriert)
Raster: 100px
```
**Für:** Vollständige Feature-Nutzung, Gilden-Planung

### Konfiguration 2: Minimalistisch
```
✅ Koordinaten-Anzeige
✅ Vollbild-Modus
✅ Zoom-Animation
✅ Doppelklick-Zoom
✅ Mausrad-Zoom
❌ Alle anderen Werkzeuge

Zoom: Min 0 / Default 2 / Max 10
Position: X=0, Y=0
```
**Für:** Einfache Marker-Anzeige, öffentliche Ansicht

### Konfiguration 3: Performance-Optimiert
```
✅ Marker-Clustering
✅ Mausrad-Zoom
✅ Vollbild-Modus
❌ Zoom-Animation
❌ Mini-Map
❌ Raster-Overlay
❌ Live Maus-Koordinaten

Zoom: Min -5 / Default 2 / Max 12
Position: Nach Bedarf
```
**Für:** Viele Marker (>200), langsame Geräte

### Konfiguration 4: Taktische Planung
```
✅ Alle Werkzeuge aktiviert
✅ Raster-Overlay
✅ Messwerkzeug
✅ Zeichenwerkzeuge
✅ Koordinaten-Suche
✅ Live Maus-Koordinaten

Zoom: Min -10 / Default 3 / Max 15
Position: Zentrum der wichtigsten Zone
Raster: 50px (präzise)
```
**Für:** Gilden-Strategie, Territory-Planung

---

## 🔍 Troubleshooting

### Problem: Mini-Map wird nicht angezeigt
**Lösung:** 
- Prüfe ob Leaflet.MiniMap Plugin geladen ist
- Stelle sicher, dass ein Map-Bild vorhanden ist
- Überprüfe Browser-Konsole auf Fehler

### Problem: Screenshot-Funktion gibt Fehler
**Lösung:** 
- html2canvas.js muss in index.php eingebunden sein
- Internetverbindung muss aktiv sein (CDN)
- Alternative: Lokale Kopie von html2canvas verwenden

### Problem: Marker-Clustering funktioniert nicht
**Lösung:** 
- Plugin Leaflet.markercluster muss installiert werden
- Aktuelle Implementierung unterstützt Clustering noch nicht vollständig
- Geplant für Version 2.3.0

### Problem: Raster-Overlay verschwindet beim Zoomen
**Lösung:** 
- Das ist normales Verhalten - Raster wird neu gezeichnet
- Event-Listener sorgt für Aktualisierung nach Zoom/Pan
- Bei Problemen: Raster deaktivieren und reaktivieren

### Problem: Performance-Probleme mit allen Features
**Lösung:** 
- Deaktiviere nicht benötigte Werkzeuge
- Deaktiviere Zoom-Animation
- Reduziere Raster-Größe oder deaktiviere Raster
- Aktiviere Marker-Clustering bei vielen Markern
- Nutze WebP-Format für Tiles

---

## 📊 Performance-Impact Übersicht

| Feature | Performance-Impact | Empfehlung |
|---------|-------------------|------------|
| Koordinaten-Anzeige | Minimal | ✅ Immer |
| Mini-Map | Mittel | ✅ Bei großen Karten |
| Messwerkzeug | Minimal | ✅ Immer |
| Zeichenwerkzeuge | Minimal | ✅ Immer |
| Vollbild-Modus | Kein | ✅ Immer |
| Koordinaten-Suche | Minimal | ✅ Immer |
| Raster-Overlay | Mittel-Hoch | ⚠️ Bei Bedarf |
| Live Maus-Koordinaten | Minimal | ✅ Immer |
| Maßstabsleiste | Minimal | ✅ Immer |
| Zoom-Animation | Minimal | ✅ Immer |
| Marker-Clustering | Positiv! | ✅ Bei >100 Markern |

---

**Version:** 2.2.0  
**Stand:** 04.02.2026  
**Für weitere Hilfe:** [GitHub Issues](https://github.com/StateDev08/Karma-Map/issues)
