// PAX DEI Map - Map JavaScript mit Leaflet.js

let map;
let markerLayer; // LayerGroup oder MarkerClusterGroup
let markers = []; // Aktuelle Marker-Instanzen
let allMarkerData = [];
let currentFilters = {
    types: new Set(),
    guilds: new Set()
};

// Map initialisieren
function initMap() {
    const mapConfig = window.mapConfig || {};
    const minZ = mapConfig.minZoom !== undefined ? mapConfig.minZoom : -2;
    const maxZ = mapConfig.maxZoom !== undefined ? mapConfig.maxZoom : 15;
    
    // Leaflet Map erstellen (touch-optimiert für Mobile)
    var isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    map = L.map('map', {
        crs: L.CRS.Simple,
        minZoom: minZ,
        maxZoom: maxZ,
        zoom: mapConfig.defaultZoom || 2,
        center: [mapConfig.defaultPositionY || 0, mapConfig.defaultPositionX || 0],
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
        inertiaDeceleration: isTouch ? 2000 : 3000,
        inertiaMaxSpeed: isTouch ? 2000 : 1500,
        worldCopyJump: false,
        maxBoundsViscosity: 0.5,
        preferCanvas: true, // Performance-Schub
        doubleClickZoom: 'center',
        touchZoom: true,
        bounceAtZoomLimits: true
    });
    document.getElementById('map').style.touchAction = 'none';
    
    // Marker-Layer initialisieren
    if (mapConfig.markerClustering) {
        markerLayer = L.markerClusterGroup({
            showCoverageOnHover: false,
            maxClusterRadius: 50,
            spiderfyOnMaxZoom: true,
            disableClusteringAtZoom: 15
        });
    } else {
        markerLayer = L.layerGroup();
    }
    markerLayer.addTo(map);
    
    // Map-Hintergrundbild oder Tiles laden
    if (mapConfig.useTiles && mapConfig.tileMetadata) {
        loadTileLayer(mapConfig.tileMetadata);
    } else if (mapConfig.mapImage) {
        const img = new Image();
        img.onload = function() {
            const bounds = [[0, 0], [this.height, this.width]];
            L.imageOverlay(mapConfig.mapImage, bounds, {
                className: 'map-image-layer',
                interactive: false,
                crossOrigin: true
            }).addTo(map);
            map.fitBounds(bounds);
            maybeFitMarkersAfterLoad();
        };
        img.crossOrigin = 'anonymous';
        img.src = mapConfig.mapImage;
    } else {
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
    
    showMarkerStatus(null, 'loading');
    loadMarkers();
    setupFilters();
    setupMapControls();
}

function maybeFitMarkersAfterLoad() {
    if (markers.length === 0) return;
    var bounds = L.latLngBounds(markers.map(function(m) { return m.getLatLng(); }));
    if (bounds.isValid()) {
        map.fitBounds(bounds, { padding: [40, 40], maxZoom: map.getMaxZoom() - 1 });
    }
}

function setupMapControls() {
    var container = document.getElementById('mapStatus');
    if (!container) return;
    var btn = container.querySelector('.map-fit-bounds-btn');
    if (btn) {
        btn.addEventListener('click', function() {
            if (markers.length === 0) return;
            var bounds = L.latLngBounds(markers.map(function(m) { return m.getLatLng(); }));
            if (bounds.isValid()) {
                map.fitBounds(bounds, { padding: [50, 50], maxZoom: 14, animate: true });
            }
        });
    }
}

// Marker vom Server laden
async function loadMarkers() {
    try {
        const response = await fetch('api/markers.php');
        const data = await response.json();
        
        if (data.success && data.markers) {
            allMarkerData = data.markers;
            displayMarkers(data.markers);
            showMarkerStatus(markers.length, null);
        } else {
            showMarkerStatus(0, 'error');
            if (data && data.error) console.error('API:', data.error);
        }
    } catch (error) {
        showMarkerStatus(0, 'error');
        console.error('Marker laden:', error);
    }
}

function showMarkerStatus(count, state) {
    var el = document.getElementById('mapStatus');
    if (!el) return;
    var text = el.querySelector('.map-status-text');
    var btn = el.querySelector('.map-fit-bounds-btn');
    if (state === 'loading') {
        if (text) text.textContent = 'Lade Marker…';
        if (btn) btn.style.visibility = 'hidden';
        el.classList.remove('error');
        el.classList.add('loading');
    } else if (state === 'error') {
        if (text) text.textContent = 'Marker konnten nicht geladen werden';
        if (btn) btn.style.visibility = 'hidden';
        el.classList.add('error');
        el.classList.remove('loading');
    } else {
        el.classList.remove('loading', 'error');
        if (text) text.textContent = count === 1 ? '1 Marker' : count + ' Marker';
        if (btn) btn.style.visibility = count > 0 ? 'visible' : 'hidden';
    }
}

// Marker auf der Map anzeigen
function displayMarkers(markerData) {
    if (!markerLayer) return;
    
    // Bestehende Marker effizient entfernen
    markerLayer.clearLayers();
    markers = [];
    
    if (!markerData || !markerData.length) {
        showMarkerStatus(0, null);
        return;
    }
    
    const newMarkers = [];
    markerData.forEach(function(data) {
        if (!shouldShowMarker(data)) return;
        
        var iconHtml = '<div class="marker-icon-inner" style="color: ' + (data.type_color || '#FF0000') + '">' +
            '<i class="fas fa-' + (data.icon || 'map-marker-alt') + '"></i></div>';
        
        var customIcon = L.divIcon({
            html: iconHtml,
            className: 'custom-marker-icon',
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        });
        
        var marker = L.marker([data.y_position, data.x_position], {
            icon: customIcon,
            title: data.name
        });
        
        var popupContent = createPopupContent(data);
        marker.bindPopup(popupContent, {
            maxWidth: 320,
            className: 'custom-popup',
            autoPan: window.mapConfig.autoPan,
            autoPanPadding: [40, 40],
            autoPanSpeed: 10,
            closeButton: true
        });
        
        marker.on('popupopen', function() {
            // Optional: Andere Popups schließen, wenn gewünscht
            // (Standardmäßig schließt Leaflet andere Popups)
        });
        
        newMarkers.push(marker);
    });
    
    // Batch-Hinzufügen für bessere Performance
    if (newMarkers.length > 0) {
        markerLayer.addLayers(newMarkers);
        markers = newMarkers;
    }
    
    showMarkerStatus(markers.length, null);
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
                    <span class="guild-badge" style="border-left: 3px solid ${data.guild_color}">
                        ${escapeHtml(data.guild_name)}
                        <span class="guild-tag" style="background: ${data.guild_color}">
                            ${escapeHtml(data.guild_tag)}
                        </span>
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
    
    // Gilden-Filter (Alle umschalten)
    const toggleAllGuilds = document.getElementById('toggleAllGuilds');
    if (toggleAllGuilds) {
        toggleAllGuilds.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.guild-filter');
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
    
    map.fitBounds(bounds, { padding: [20, 20] });
    setTimeout(maybeFitMarkersAfterLoad, 100);
    
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
    
    // Mobile Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const closeBtn = document.getElementById('sidebarCloseMobile');

    if (sidebarToggle && sidebar && backdrop) {
        function toggleSidebar() {
            sidebar.classList.toggle('sidebar-open');
            backdrop.classList.toggle('active');
            document.body.classList.toggle('sidebar-open');
        }

        sidebarToggle.addEventListener('click', toggleSidebar);
        backdrop.addEventListener('click', toggleSidebar);
        if (closeBtn) closeBtn.addEventListener('click', toggleSidebar);
    }
    
    // Erweiterte Features initialisieren wenn verfügbar
    if (typeof initExtendedMapFeatures === 'function' && window.mapConfig) {
        setTimeout(function() {
            initExtendedMapFeatures(map, window.mapConfig);
        }, 500);
    }
});
