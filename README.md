# PAX DEI MAP - KARMA Gilde

Eine hochmoderne interaktive Map-Webanwendung für PAX Die mit professionellem Tile-System, Marker-Verwaltung, Gilden-System und vollständigem Admin Control Panel.

## 📦 Download & Installation

### Option 1: Download Release (Empfohlen)
Lade die neueste Version direkt herunter:
**[Download Latest Release](https://github.com/StateDev08/Karma-Map/releases)** 📥

### Option 2: Git Clone
```bash
git clone https://github.com/StateDev08/Karma-Map.git
cd Karma-Map
```

### Option 3: GitHub
Besuche das Repository: **[StateDev08/Karma-Map](https://github.com/StateDev08/Karma-Map)**

---

## ⭐ Highlights

🚀 **Google Maps-ähnliche Performance**
- Pixelfreies Zoomen mit Tile-System
- 11 Zoom-Stufen (Level 0-10) mit dynamischer Auflösung
- Smooth Animationen und Inertia-Panning
- WebP-Unterstützung für optimale Performance

🗺️ **Professionelle Karten-Technologie**
- Leaflet.js mit angepasstem Koordinatensystem
- Automatische Tile-Generierung (512x512px Kacheln)
- Retina-Display-Unterstützung
- Unbegrenztes Herauszoomen

## Features

✅ **Interaktive High-Performance Karte**
- Custom Map-Bild Upload mit automatischer Tile-Konvertierung
- Pixelfreies Zoomen bis zu 15x (virtuelle Vergrößerung)
- Marker mit verschiedenen Typen und Font Awesome Icons
- Echtzeit-Filterung nach Marker-Typen und Gilden
- Smooth Zoom und Navigation wie bei Google Maps
- Doppelklick zum Zoom-In, Mausrad-Zoom, Touch-Unterstützung

✅ **Gilden-System**
- Verwaltung mehrerer Gilden
- Individuelle Farben und Tags
- Mitglieder-Tracking
- Allianz-Informationen

✅ **Marker-Verwaltung**
- Verschiedene Marker-Typen (Territorium, Ressourcen, Dungeons, etc.)
- Position auf der Map
- Beschreibungen und Bilder
- Zuordnung zu Gilden

✅ **Advanced Tile-System**
- Automatische Tile-Generierung aus hochauflösenden Bildern
- 11 Zoom-Stufen für maximale Detailgenauigkeit
- WebP + PNG Fallback für optimale Kompatibilität
- Bicubic-Interpolation für beste Bildqualität
- Antialiasing für glatte Kanten
- Intelligentes Caching und Progressive Loading

✅ **Admin Control Panel**
- Vollständige CRUD-Operationen
- Logo-Verwaltung (Text oder Bild)
- Farbschema-Anpassung
- Map-Upload mit One-Click Tile-Generierung
- Marker-Typen konfigurieren
- Erweiterte Zoom-Einstellungen (-10 bis +15)
- Nur für Admins zugänglich mit CSRF-Schutz

✅ **Discord-Style Design**
- Schwarz/Rot Farbschema
- Moderne Discord-ähnliche UI
- Responsive Layout
- Dark Mode

## Installation

### Voraussetzungen
- PHP 7.4 oder höher (PHP 8.x empfohlen)
- MySQL/MariaDB 5.7 oder höher
- Webserver (Apache/Nginx) oder XAMPP/WAMP/Laragon für lokale Entwicklung
- **GD Library** (für Tile-Generierung - meist standardmäßig aktiviert)
- **PHP Extensions:** PDO, GD, JSON, mbstring
- Mindestens 256 MB PHP Memory (empfohlen: 512 MB für große Karten)

### Setup-Schritte

1. **Datenbank erstellen**
   - Importiere `database/schema.sql` in deine MySQL-Datenbank
   - Standard-Admin-User: `admin` / `admin123` (BITTE ÄNDERN!)

2. **Konfiguration anpassen**
   - Öffne `includes/config.php`
   - Passe Datenbank-Zugangsdaten an:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'pax_die_map');
     define('DB_USER', 'dein_user');
     define('DB_PASS', 'dein_passwort');
     ```

3. **Setup-Wizard öffnen** (EINFACHSTE METHODE)
   ```
   http://localhost/pax-die-map/setup.php
   ```
   - Folge dem 5-Schritt Installations-Wizard
   - Datenbank-Verbindung konfigurieren
   - Admin-User erstellen
   - Automatische Konfiguration
   - **⚠️ Lösche `setup.php` nach der Installation!**

### Alternative: Manuelle Installation

1. Kopiere Projekt nach `C:\xampp\htdocs\pax-die-map`
2. Starte XAMPP Control Panel
3. Starte Apache und MySQL
4. Öffne phpMyAdmin: http://localhost/phpmyadmin
5. Erstelle Datenbank `pax_die_map`
6. Importiere `database/schema.sql`
7. Passe `includes/config.php` an (DB-Zugangsdaten)
8. Erstelle Upload-Ordner: `uploads/`
9. Öffne http://localhost/pax-die-map/

## Verwendung

### Frontend (Map-Ansicht)
- Öffne `/index.php` oder einfach `/`
- Filtere Marker nach Typ und Gilde
- Klicke auf Marker für Details

### Admin Panel
- Login: `/admin/login.php`
- Standard-Login: `admin` / `admin123`
- **WICHTIG:** Ändere das Passwort sofort!

### Admin-Bereiche

**Dashboard**
- Statistiken und Übersicht
- Zuletzt hinzugefügte Marker

**Marker verwalten**
- Neue Marker hinzufügen
- Position, Typ, Gilde zuweisen
- Marker bearbeiten/löschen

**Gilden verwalten**
- Gilden erstellen
- Farben, Tags, Beschreibungen
- Mitglieder-Anzahl verwalten

**Marker-Typen**
- Neue Marker-Typen erstellen
- Icons (Font Awesome) zuweisen
- Farben und Sortierung hochladen
- Automatische Tile-Generierung mit einem Klick
- Unterstützt JPG, PNG, GIF, WebP
- Max. 10 MB (anpassbar in config.php)
- Empfohlene Auflösung: 3000x3000px oder höher für beste Qualität
- Zeigt Tile-Status und Metadaten an
- Option zum Deaktivieren des Tile-Systems (Fallback auf Standard-Bild)

**Einstellungen**
- Logo-Verwaltung (Text oder Bild)
- Farbschema (Rot/Schwarz)
- Erweiterte Map-Zoom-Einstellungen:
  - Minimaler Zoom: -10 bis +10 (negative Werte = weiter herauszoomen)
  - Maximaler Zoom: 1 bis 15
  - Standard-Zoom beim Lad
- Logo-Verwaltung (Text oder Bild)
- Farbschema (Rot/Schwarz)
- Ma│   ├── dashboard.php
│   │   ├── markers.php
│   │   ├── guilds.php
│   │   ├── marker-types.php
│   │   ├── map-upload.php  # Tile-System Management
│   │   └── settings.php
│   ├── index.php           # Admin Dashboard
│   ├── login.php           # Login
│   └── logout.php          # Logout
├── api/
│   └── markers.php         # Marker API
├── assets/
│   ├── css/                # Stylesheets
│   │   ├── style.css       # Frontend CSS
│   │   └── admin.css       # Admin CSS
│   └── js/                 # JavaScript
│       ├── map.js          # Leaflet Integration + Tile-System
│       └── admin.js        # Admin Funktionen
├── database/
│   └── schema.sql          # Datenbank-Schema
├── includes/
│   ├── config.php          # Konfiguration
│   ├── db.php              # Datenbank-Klasse
│   ├── auth.php            # Authentifizierung
│   ├── functions.php       # Helper-Funktionen
│   └── tile-generator. (PHP 8.x empfohlen)
- **Datenbank:** MySQL/MariaDB mit PDO
- **Frontend:** HTML5, CSS3, JavaScript (ES6+)
- **Map-Library:** Leaflet.js 1.9.4 mit CRS.Simple für benutzerdefinierte Koordinaten
- **Tile-System:** Custom PHP Tile-Generator mit GD Library
- **Bild-Verarbeitung:** 
  - GD Library mit bicubic Interpolation
  - WebP-Unterstützung für optimale Kompression
  - PNG-Fallback für Kompatibilität
- **Icons:** Font Awesome 6.5.1
- **Performance:**
  - Lazy Loading für Tiles
  - Progressive Image Loading
  - Browser-Caching mit .htaccess
  - CORS-Headers für Cross-Origin
- **Architektur:** MVC-ähnlich, PDO für sichere Datenbankabfragen
│   │   └── .htaccess       # Caching & CORS
│   └── schema.sql          # Datenbank-Schema
├── includes/
│   ├── config.php          # Konfiguration
│   ├── db.php              # Datenbank-Klasse
│   ├── auth.php            # Authentifizierung
│   └── functions.php       # Helper-Funktionen
├── uploads/                # Upload-Verzeichnis
│   ├── map/                # Map-Bilder
│   ├── logo/               # Logos
│   └── marker/             # Marker-Bilder
├── index.php               # Hauptseite (Map)
└── setup.php               # Installation
```

## Technologien

- **Backend:** PHP 7.4+
- **Datenbank:** MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript
- **Map-Library:** Leaflet.js 1.9.4
- **Icons:** Font Awesome 6.5.1
- **Architektur:** MVC-ähnlich, PDO für Datenbank


### Tile-System Konfiguration
- **Tile-Größe:** 512x512px (Standard, optimiert für Performance)
- **Zoom-Stufen:** 0-10 (11 Stufen, konfigurierbar in `tile-generator.php`)
- **Bildformat:** WebP (primär) + PNG (Fallback)
- **Qualität:** PNG Kompression Level 6, WebP 90%
- **Performance:** 
  - Bei 3000x3000px Bild: ~200-300 Tiles
  - Generierungszeit: 30-60 Sekunden (abhängig von Bildgröße)
  - Speicherplatz: 2-5 MB (mit WebP)

### Tile-System neu generieren
## Performance-Tipps

🚀 **Für große Karten (>5000x5000px):**
- Erhöhe PHP Memory Limit in `php.ini`: `memory_limit = 512M`
- Erhöhe max execution time: `max_execution_time = 300`
- Nutze WebP für 30-50% kleinere Dateien
- Cache-Header in `.htaccess` sind bereits optimiert

🎯 **Für beste Qualität:**
- Verwende hochauflösende PNG-Bilder als Quelle
- Aktiviere immer die automatische Tile-Generierung
- Mindestens 3000x3000px für große Maps
- Test verschiedene Zoom-Stufen nach der Generierung

## Bekannte Features

✨ **Tile-System Features:**
- Automatische Erkennung ob Tiles verfügbar sind
- Intelligenter Fallback auf Standard-Bild wenn Tiles fehlen
- Smooth Zoom-Animationen wie Google Maps
- Inertia-Panning (Schwungkraft beim Verschieben)
- Doppelklick zentriert und zoomt
- Retina-Display-Unterstützung
- Cross-Browser-kompatibel (Chrome, Firefox, Safari, Edge)

## Changelog

### Version 2.0.0 (04.02.2026)
- ✨ **NEU:** Google Maps-ähnliches Tile-System
- ✨ Pixelfreies Zoomen bis Level 15
- ✨ WebP-Unterstützung für optimale Performance
- ✨ Automatische Tile-Generierung im Admin-Panel
- ✨ 11 Zoom-Stufen mit bicubic Interpolation
- ✨ Smooth Animationen und Inertia-Panning
- ✨ Erweiterte Zoom-Einstellungen (-10 bis +15)
- 🔧 Verbesserte Bildqualität mit Antialiasing
- 🔧 Optimierte Performance mit Tile-Caching
- 🔧 Bessere Browser-Kompatibilität

### Version 1.0.0 (01.02.2026)
- 🎉 Initiale Veröffentlichung
- Interaktive Karte mit Leaflet.js
- Marker-System mit Typen und Gilden
- Admin Control Panel
- Discord-Style Design

---

**Erstellt am:** 01.02.2026  
**Letzte Aktualisierung:** 04.02.2026  
**Version:** 2.0.0  
**Für:** KARMA Gilde - PAX Die  
**Technologie:** PHP, MySQL, Leaflet.js, Tile-System
```

### Optimale Map-Einstellungen
- **Bildauflösung:** 3000x3000px oder höher
- **Format:** PNG (beste Qualität) oder JPG (kleinere Dateigröße)
- **Minimaler Zoom:** -10 (ermöglicht weites Herauszoomen)
- **Maximaler Zoom:** 10-15 (15 für virtuelle Über-Vergrößerung)
- **Standard-Zoom:** 2-3 (optimale Übersicht beim Laden)
## Sicherheit

⚠️ **Wichtige Sicherheitshinweise:**

1. **Passwort ändern:** Standard-Admin-Passwort sofort ändern!
2. **Produktions-Modus:** `display_errors` in `config.php` auf `0` setzen
3. **HTTPS:** In Produktion nur über HTTPS betreiben
4. **Upload-Limits:** Max. Dateigrößen prüfen
5. **SQL-Injection:** Alle Queries nutzen PDO Prepared Statements
6. **CSRF-Schutz:** Ist implementiert (Token-Validierung)
7. **XSS-Schutz:** `e()` Funktion für HTML-Escaping

## Anpassungen

### Farben ändern
- Admin Panel → Einstellungen → Farbschema
- Oder direkt in CSS: `/assets/css/style.css` (`:root` Variablen)

### Logo ändern
- Admin Panel → Einstellungen → Logo-Einstellungen
- Text-Logo oder Bild-Upload

### Marker-Icons
- Font Awesome Icons: https://fontawesome.com/icons
- Admin Panel → Marker-Typen → Icon auswählen

## Lizenz

Dieses Projekt wurde für die PAX Die Gilde KARMA erstellt.

## Support & Community

### GitHub
- **Repository:** [StateDev08/Karma-Map](https://github.com/StateDev08/Karma-Map)
- **Issues:** [Bug Reports & Feature Requests](https://github.com/StateDev08/Karma-Map/issues)
- **Releases:** [Download neueste Version](https://github.com/StateDev08/Karma-Map/releases)

### Hilfe & Troubleshooting
Bei Fragen oder Problemen:
1. Prüfe die Datenbank-Verbindung in `config.php`
2. Prüfe Schreibrechte für `uploads/` Ordner
3. Prüfe PHP-Fehlerlog
4. Erstelle ein [GitHub Issue](https://github.com/StateDev08/Karma-Map/issues) bei Bugs

### Beitragen
Contributions sind willkommen! 
1. Fork das Repository
2. Erstelle einen Feature-Branch (`git checkout -b feature/AmazingFeature`)
3. Commit deine Änderungen (`git commit -m 'Add some AmazingFeature'`)
4. Push zum Branch (`git push origin feature/AmazingFeature`)
5. Öffne einen Pull Request

---

**Erstellt am:** 01.02.2026  
**Letzte Aktualisierung:** 04.02.2026  
**Version:** 2.0.0  
**Für:** KARMA Gilde - PAX Die  
**Repository:** [github.com/StateDev08/Karma-Map](https://github.com/StateDev08/Karma-Map)
