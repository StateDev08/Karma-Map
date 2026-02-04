# PAX Die Map - KARMA Gilde

Eine interaktive Map-Webanwendung für PAX Die mit Marker-System, Gilden-Verwaltung und vollständigem Admin Control Panel.

## Features

✅ **Interaktive Karte**
- Custom Map-Bild Upload
- Marker mit verschiedenen Typen
- Filterung nach Marker-Typen und Gilden
- Zoom und Navigation

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

✅ **Admin Control Panel**
- Vollständige CRUD-Operationen
- Logo-Verwaltung (Text oder Bild)
- Farbschema-Anpassung
- Map-Upload
- Marker-Typen konfigurieren
- Nur für Admins zugänglich

✅ **Discord-Style Design**
- Schwarz/Rot Farbschema
- Moderne Discord-ähnliche UI
- Responsive Layout
- Dark Mode

## Installation

### Voraussetzungen
- PHP 7.4 oder höher
- MySQL/MariaDB 5.7 oder höher
- Webserver (Apache/Nginx) oder XAMPP/WAMP für lokale Entwicklung

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
- Farben und Sortierung

**Map hochladen**
- Hintergrundbild für die Karte
- Max. 10 MB (anpassbar in config.php)

**Einstellungen**
- Logo-Verwaltung (Text oder Bild)
- Farbschema (Rot/Schwarz)
- Map-Zoom-Einstellungen

## Struktur

```
pax-die-map/
├── admin/                  # Admin Panel
│   ├── pages/              # Admin-Seiten
│   ├── index.php           # Admin Dashboard
│   ├── login.php           # Login
│   └── logout.php          # Logout
├── api/
│   └── markers.php         # Marker API
├── assets/
│   ├── css/                # Stylesheets
│   └── js/                 # JavaScript
├── database/
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

## Support

Bei Fragen oder Problemen:
1. Prüfe die Datenbank-Verbindung in `config.php`
2. Prüfe Schreibrechte für `uploads/` Ordner
3. Prüfe PHP-Fehlerlog

---

**Erstellt am:** 01.02.2026
**Version:** 1.0.0
**Für:** KARMA Gilde - PAX Die
