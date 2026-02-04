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

// Tile Layer laden (wie Google Maps) - Enhanced Version 2.2
function loadTileLayer(metadata) {
    const tileSize = metadata.tileSize || 512;
    const maxNativeZoom = metadata.maxZoom || 10;
    const minZoom = metadata.minZoom || 0;
    const width = metadata.sourceWidth;
    const height = metadata.sourceHeight;
    const formats = metadata.formats || ['png'];
    
    // Berechne korrekte Bounds für Leaflet CRS.Simple
    const bounds = [[0, 0], [height, width]];
    
    // Berechne optimalen Zoom
    const mapElement = document.getElementById('map');
    const mapWidth = mapElement.clientWidth;
    const mapHeight = mapElement.clientHeight;
    const optimalZoom = Math.min(
        Math.log2(mapWidth / width),
        Math.log2(mapHeight / height)
    );
    
    // Intelligente Format-Auswahl: WebP > JPEG > PNG
    let tileFormat = 'png';
    let tileExt = '.png';
    
    // Prüfe WebP-Unterstützung des Browsers
    if (formats.includes('webp') && supportsWebP()) {
        tileFormat = 'webp';
        tileExt = '.webp';
    } else if (formats.includes('jpeg')) {
        tileFormat = 'jpeg';
        tileExt = '.jpg';
    }
    
    // Erstelle TileLayer mit Multi-Format-Fallback
    const tileLayer = L.tileLayer('uploads/tiles/{z}/{x}/{y}' + tileExt, {
        tileSize: tileSize,
        maxNativeZoom: maxNativeZoom,
        minNativeZoom: minZoom,
        minZoom: -2,
        maxZoom: 15,
        noWrap: true,
        bounds: bounds,
        className: 'map-tile-layer',
        errorTileUrl: createErrorTile(),
        keepBuffer: 8,
        updateWhenZooming: false,
        updateWhenIdle: true,
        crossOrigin: true,
        detectRetina: true,
        // Progressives Laden
        updateWhenZooming: false,
        updateInterval: 150
    });
    
    // Füge Fallback-Handler hinzu
    tileLayer.on('tileerror', function(error) {
        const tile = error.tile;
        const originalSrc = tile.src;
        
        // Versuche Fallback zu PNG wenn WebP/JPEG fehlschlägt
        if (tileFormat !== 'png' && originalSrc.indexOf('.png') === -1) {
            tile.src = originalSrc.replace(tileExt, '.png');
        }
    });
    
    tileLayer.addTo(map);
    
    // Setze erweiterte Bounds mit Padding
    const paddedBounds = [
        [-height * 0.1, -width * 0.1],
        [height * 1.1, width * 1.1]
    ];
    map.setMaxBounds(paddedBounds);
    
    // Zentriere Map
    map.fitBounds(bounds, { padding: [20, 20] });
    
    // Event-Listener für Performance
    map.on('zoomstart', function() {
        document.body.style.cursor = 'wait';
    });
    
    map.on('zoomend', function() {
        document.body.style.cursor = 'default';
    });
    
    // Tile-Loading-Progress (optional)
    let loadingTiles = 0;
    tileLayer.on('tileloadstart', function() {
        loadingTiles++;
        updateLoadingIndicator(true);
    });
    
    tileLayer.on('tileload', function() {
        loadingTiles--;
        if (loadingTiles === 0) {
            updateLoadingIndicator(false);
        }
    });
    
    tileLayer.on('tileerror', function() {
        loadingTiles--;
        if (loadingTiles === 0) {
            updateLoadingIndicator(false);
        }
    });
    
    console.log('Tile-System geladen:', {
        version: metadata.version || '2.0',
        format: tileFormat,
        tileSize: tileSize,
        zoomLevels: (maxNativeZoom - minZoom + 1),
        tiles: metadata.tilesGenerated,
        size: metadata.estimatedSize,
        executionTime: metadata.executionTime,
        bounds: bounds,
        optimalZoom: optimalZoom
    });
}

// WebP-Unterstützung prüfen
function supportsWebP() {
    if (typeof supportsWebP.result !== 'undefined') {
        return supportsWebP.result;
    }
    
    const canvas = document.createElement('canvas');
    if (canvas.getContext && canvas.getContext('2d')) {
        supportsWebP.result = canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
        return supportsWebP.result;
    }
    
    supportsWebP.result = false;
    return false;
}

// Error-Tile erstellen
function createErrorTile() {
    const canvas = document.createElement('canvas');
    canvas.width = 256;
    canvas.height = 256;
    const ctx = canvas.getContext('2d');
    
    // Halbtransparenter grauer Hintergrund
    ctx.fillStyle = 'rgba(26, 26, 26, 0.5)';
    ctx.fillRect(0, 0, 256, 256);
    
    // Diagonal-Streifen als Muster
    ctx.strokeStyle = 'rgba(100, 100, 100, 0.3)';
    ctx.lineWidth = 2;
    for (let i = -256; i < 512; i += 20) {
        ctx.beginPath();
        ctx.moveTo(i, 0);
        ctx.lineTo(i + 256, 256);
        ctx.stroke();
    }
    
    return canvas.toDataURL();
}

// Loading-Indikator aktualisieren
function updateLoadingIndicator(loading) {
    let indicator = document.getElementById('tile-loading-indicator');
    
    if (loading && !indicator) {
        indicator = document.createElement('div');
        indicator.id = 'tile-loading-indicator';
        indicator.className = 'tile-loading';
        indicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Lade Tiles...';
        document.body.appendChild(indicator);
    } else if (!loading && indicator) {
        indicator.remove();
    }
}

// Map initialisieren wenn Seite geladen
document.addEventListener('DOMContentLoaded', function() {
    initMap();
    
    // Erweiterte Features initialisieren wenn verfügbar
    if (typeof initExtendedMapFeatures === 'function' && window.mapConfig) {
        setTimeout(function() {
            initExtendedMapFeatures(map, window.mapConfig);
        }, 500);
    }
});
