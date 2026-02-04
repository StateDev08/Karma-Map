// PAX Die Map - Map JavaScript mit Leaflet.js

let map;
let markers = [];
let currentFilters = {
    types: new Set(),
    guilds: new Set()
};

// Map initialisieren
function initMap() {
    const mapConfig = window.mapConfig || {};
    
    // Leaflet Map erstellen
    map = L.map('map', {
        crs: L.CRS.Simple,
        minZoom: mapConfig.minZoom !== undefined ? mapConfig.minZoom : -2,
        maxZoom: 15,
        zoom: mapConfig.defaultZoom || 2,
        center: [0, 0],
        zoomControl: true,
        attributionControl: false,
        zoomSnap: 0.1,
        zoomDelta: 0.5,
        wheelPxPerZoomLevel: 60,
        wheelDebounceTime: 40,
        zoomAnimation: true,
        zoomAnimationThreshold: 4,
        fadeAnimation: true,
        markerZoomAnimation: true,
        inertia: true,
        inertiaDeceleration: 3000,
        inertiaMaxSpeed: 1500,
        worldCopyJump: false,
        maxBoundsViscosity: 0.5,
        preferCanvas: false,
        doubleClickZoom: 'center'
    });
    
    // Map-Hintergrundbild oder Tiles laden
    if (mapConfig.useTiles && mapConfig.tileMetadata) {
        // Verwende Tile-System für höchste Qualität
        loadTileLayer(mapConfig.tileMetadata);
    } else if (mapConfig.mapImage) {
        // Fallback: Einzelnes Bild
        const img = new Image();
        img.onload = function() {
            const bounds = [[0, 0], [this.height, this.width]];
            L.imageOverlay(mapConfig.mapImage, bounds, {
                className: 'map-image-layer',
                interactive: false,
                crossOrigin: true
            }).addTo(map);
            map.fitBounds(bounds);
        };
        img.crossOrigin = 'anonymous';
        img.src = mapConfig.mapImage;
    } else {
        // Fallback: Einfacher grauer Hintergrund
        const bounds = [[0, 0], [1000, 1000]];
        const svgData = `
            <svg xmlns="http://www.w3.org/2000/svg" width="1000" height="1000">
                <rect width="1000" height="1000" fill="#1a1a1a"/>
                <text x="500" y="500" text-anchor="middle" fill="#666" font-size="24">
                    Bitte Map-Bild im ACP hochladen
                </text>
            </svg>
        `;
        const svgBlob = new Blob([svgData], {type: 'image/svg+xml'});
        const url = URL.createObjectURL(svgBlob);
        L.imageOverlay(url, bounds).addTo(map);
        map.fitBounds(bounds);
    }
    
    // Marker laden
    loadMarkers();
    
    // Filter-Events
    setupFilters();
}

// Marker vom Server laden
async function loadMarkers() {
    try {
        const response = await fetch('api/markers.php');
        const data = await response.json();
        
        console.log('Marker API Response:', data);
        console.log('Anzahl Marker:', data.markers ? data.markers.length : 0);
        
        if (data.success) {
            displayMarkers(data.markers);
        } else {
            console.error('API Fehler:', data.error);
        }
    } catch (error) {
        console.error('Fehler beim Laden der Marker:', error);
    }
}

// Marker auf der Map anzeigen
function displayMarkers(markerData) {
    console.log('displayMarkers aufgerufen mit', markerData.length, 'Markern');
    
    // Bestehende Marker entfernen
    markers.forEach(marker => map.removeLayer(marker));
    markers = [];
    
    // Neue Marker hinzufügen
    markerData.forEach(data => {
        console.log('Verarbeite Marker:', data);
        
        // Prüfen ob Marker gefiltert werden soll
        if (!shouldShowMarker(data)) {
            console.log('Marker gefiltert:', data.name);
            return;
        }
        
        console.log('Füge Marker hinzu:', data.name, 'Position:', data.x_position, data.y_position);
        
        // Icon erstellen
        const iconHtml = `
            <div style="font-size: 24px; color: ${data.type_color || '#FF0000'}; 
                 text-shadow: 0 0 3px #000, 0 0 5px #000;">
                <i class="fas fa-${data.icon || 'map-marker-alt'}"></i>
            </div>
        `;
        
        const customIcon = L.divIcon({
            html: iconHtml,
            className: 'custom-marker-icon',
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });
        
        // Marker erstellen
        const marker = L.marker([data.y_position, data.x_position], {
            icon: customIcon,
            title: data.name
        });
        
        // Popup-Content
        const popupContent = createPopupContent(data);
        marker.bindPopup(popupContent, {
            maxWidth: 300,
            className: 'custom-popup'
        });
        
        marker.addTo(map);
        markers.push(marker);
    });
}

// Popup-Inhalt erstellen
function createPopupContent(data) {
    let content = `
        <div class="marker-popup">
            <h3>${escapeHtml(data.name)}</h3>
    `;
    
    if (data.description) {
        content += `<p>${escapeHtml(data.description)}</p>`;
    }
    
    if (data.type_name) {
        content += `
            <div class="info-row">
                <span class="info-label">Typ:</span>
                <span class="info-value" style="color: ${data.type_color}">
                    <i class="fas fa-${data.icon}"></i> ${escapeHtml(data.type_name)}
                </span>
            </div>
        `;
    }
    
    if (data.guild_name) {
        content += `
            <div class="info-row">
                <span class="info-label">Gilde:</span>
                <span class="info-value">
                    ${escapeHtml(data.guild_name)}
                    <span class="guild-tag" style="background: ${data.guild_color}">
                        ${escapeHtml(data.guild_tag)}
                    </span>
                </span>
            </div>
        `;
    }
    
    content += `
            <div class="info-row">
                <span class="info-label">Position:</span>
                <span class="info-value">${parseFloat(data.x_position).toFixed(2)}, ${parseFloat(data.y_position).toFixed(2)}</span>
            </div>
        </div>
    `;
    
    return content;
}

// Prüfen ob Marker angezeigt werden soll (Filter)
function shouldShowMarker(data) {
    // Wenn Filter leer sind, alles anzeigen
    if (currentFilters.types.size === 0 && currentFilters.guilds.size === 0) {
        return true;
    }
    
    // Typ-Filter
    if (currentFilters.types.size > 0) {
        if (!data.marker_type_id || !currentFilters.types.has(data.marker_type_id)) {
            return false;
        }
    }
    
    // Gilden-Filter
    if (currentFilters.guilds.size > 0) {
        if (!data.guild_id || !currentFilters.guilds.has(data.guild_id)) {
            return false;
        }
    }
    
    return true;
}

// Filter-Setup
function setupFilters() {
    // Toggle All
    const toggleAll = document.getElementById('toggleAll');
    if (toggleAll) {
        toggleAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.marker-type-filter, .guild-filter');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateFilters();
        });
    }
    
    // Marker-Typ Filter
    const typeFilters = document.querySelectorAll('.marker-type-filter');
    typeFilters.forEach(filter => {
        filter.addEventListener('change', updateFilters);
        if (filter.checked) {
            currentFilters.types.add(parseInt(filter.dataset.typeId));
        }
    });
    
    // Gilden-Filter
    const guildFilters = document.querySelectorAll('.guild-filter');
    guildFilters.forEach(filter => {
        filter.addEventListener('change', updateFilters);
        if (filter.checked) {
            currentFilters.guilds.add(parseInt(filter.dataset.guildId));
        }
    });
}

// Filter aktualisieren
function updateFilters() {
    // Filter-Sets leeren
    currentFilters.types.clear();
    currentFilters.guilds.clear();
    
    // Typ-Filter sammeln
    document.querySelectorAll('.marker-type-filter:checked').forEach(filter => {
        currentFilters.types.add(parseInt(filter.dataset.typeId));
    });
    
    // Gilden-Filter sammeln
    document.querySelectorAll('.guild-filter:checked').forEach(filter => {
        currentFilters.guilds.add(parseInt(filter.dataset.guildId));
    });
    
    // Marker neu laden
    loadMarkers();
}

// HTML Escaping
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Tile Layer laden (wie Google Maps)
function loadTileLayer(metadata) {
    const tileSize = metadata.tileSize || 512;
    const maxNativeZoom = metadata.maxZoom || 10;
    const minZoom = metadata.minZoom || 0;
    const width = metadata.sourceWidth;
    const height = metadata.sourceHeight;
    
    // Berechne korrekte Bounds für Leaflet CRS.Simple
    // Bei CRS.Simple: [y, x] statt [x, y]
    const bounds = [[0, 0], [height, width]];
    
    // Berechne optimalen Zoom basierend auf Bildgröße
    const mapElement = document.getElementById('map');
    const mapWidth = mapElement.clientWidth;
    const mapHeight = mapElement.clientHeight;
    const optimalZoom = Math.min(
        Math.log2(mapWidth / width),
        Math.log2(mapHeight / height)
    );
    
    // Erstelle TileLayer mit optimierten Einstellungen
    const tileLayer = L.tileLayer('uploads/tiles/{z}/{x}/{y}.png', {
        tileSize: tileSize,
        maxNativeZoom: maxNativeZoom,
        minNativeZoom: minZoom,
        minZoom: -2,
        maxZoom: 15,
        noWrap: true,
        bounds: bounds,
        className: 'map-tile-layer',
        errorTileUrl: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        keepBuffer: 8,
        updateWhenZooming: false,
        updateWhenIdle: true,
        crossOrigin: true,
        detectRetina: true
    });
    
    tileLayer.addTo(map);
    
    // Setze erweiterte Bounds mit etwas Padding
    const paddedBounds = [
        [-height * 0.1, -width * 0.1],
        [height * 1.1, width * 1.1]
    ];
    map.setMaxBounds(paddedBounds);
    
    // Zentriere Map auf das Bild
    map.fitBounds(bounds, { padding: [20, 20] });
    
    // Event-Listener für bessere Performance
    map.on('zoomstart', function() {
        document.body.style.cursor = 'wait';
    });
    
    map.on('zoomend', function() {
        document.body.style.cursor = 'default';
    });
    
    console.log('Tile-System geladen:', {
        ...metadata,
        bounds: bounds,
        optimalZoom: optimalZoom
    });
}

// Map initialisieren wenn Seite geladen
document.addEventListener('DOMContentLoaded', function() {
    initMap();
});
