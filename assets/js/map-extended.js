// PAX DEI Map - Extended Map Features
// Erweiterte Map-Funktionen und Werkzeuge

let measurePath = null;
let measureMarkers = [];
let measureActive = false;
let drawingItems = new L.FeatureGroup();
let gridLayer = null;
let coordinatesControl = null;
let mouseCoordControl = null;

// Koordinaten-Anzeige Control
L.Control.Coordinates = L.Control.extend({
    onAdd: function(map) {
        const container = L.DomUtil.create('div', 'leaflet-control-coordinates');
        container.innerHTML = '<div class="coord-display"><i class="fas fa-crosshairs"></i> <span id="current-zoom">Zoom: 0</span> | <span id="current-coords">X: 0, Y: 0</span></div>';
        
        L.DomEvent.disableClickPropagation(container);
        return container;
    }
});

// Koordinaten-Suche Control
L.Control.CoordinateSearch = L.Control.extend({
    onAdd: function(map) {
        const container = L.DomUtil.create('div', 'leaflet-control-search leaflet-bar');
        container.innerHTML = `
            <div class="coord-search-panel">
                <button class="search-toggle" title="Zu Koordinaten springen">
                    <i class="fas fa-search-location"></i>
                </button>
                <div class="search-inputs" style="display: none;">
                    <input type="number" id="goto-x" placeholder="X" step="0.1">
                    <input type="number" id="goto-y" placeholder="Y" step="0.1">
                    <button id="goto-btn" title="Gehe zu Position">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        `;
        
        L.DomEvent.disableClickPropagation(container);
        
        const toggle = container.querySelector('.search-toggle');
        const inputs = container.querySelector('.search-inputs');
        const gotoBtn = container.querySelector('#goto-btn');
        
        toggle.addEventListener('click', function() {
            inputs.style.display = inputs.style.display === 'none' ? 'flex' : 'none';
        });
        
        gotoBtn.addEventListener('click', function() {
            const x = parseFloat(document.getElementById('goto-x').value);
            const y = parseFloat(document.getElementById('goto-y').value);
            if (!isNaN(x) && !isNaN(y)) {
                map.setView([y, x], map.getZoom(), {animate: true});
                L.marker([y, x]).addTo(map)
                    .bindPopup(`Position: X=${x.toFixed(2)}, Y=${y.toFixed(2)}`)
                    .openPopup();
            }
        });
        
        return container;
    }
});

// Messwerkzeug Control
L.Control.MeasureTool = L.Control.extend({
    onAdd: function(map) {
        const container = L.DomUtil.create('div', 'leaflet-control-measure leaflet-bar');
        container.innerHTML = '<button title="Distanz messen"><i class="fas fa-ruler"></i></button>';
        
        const button = container.querySelector('button');
        button.addEventListener('click', function() {
            toggleMeasure(map);
            button.classList.toggle('active');
        });
        
        L.DomEvent.disableClickPropagation(container);
        return container;
    }
});

// Zeichenwerkzeuge Control
L.Control.DrawingTools = L.Control.extend({
    onAdd: function(map) {
        const container = L.DomUtil.create('div', 'leaflet-control-draw leaflet-bar');
        container.innerHTML = `
            <div class="draw-tools">
                <button class="draw-toggle" title="Zeichenwerkzeuge">
                    <i class="fas fa-pencil-alt"></i>
                </button>
                <div class="draw-buttons" style="display: none;">
                    <button id="draw-marker" title="Marker setzen">
                        <i class="fas fa-map-pin"></i>
                    </button>
                    <button id="draw-line" title="Linie zeichnen">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button id="draw-polygon" title="Polygon zeichnen">
                        <i class="fas fa-draw-polygon"></i>
                    </button>
                    <button id="draw-circle" title="Kreis zeichnen">
                        <i class="fas fa-circle"></i>
                    </button>
                    <button id="draw-clear" title="Alle löschen">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        
        L.DomEvent.disableClickPropagation(container);
        
        const toggle = container.querySelector('.draw-toggle');
        const buttons = container.querySelector('.draw-buttons');
        
        toggle.addEventListener('click', function() {
            buttons.style.display = buttons.style.display === 'none' ? 'block' : 'none';
        });
        
        // Drawing handlers
        container.querySelector('#draw-marker').addEventListener('click', () => startDrawing(map, 'marker'));
        container.querySelector('#draw-line').addEventListener('click', () => startDrawing(map, 'polyline'));
        container.querySelector('#draw-polygon').addEventListener('click', () => startDrawing(map, 'polygon'));
        container.querySelector('#draw-circle').addEventListener('click', () => startDrawing(map, 'circle'));
        container.querySelector('#draw-clear').addEventListener('click', () => clearDrawings(map));
        
        return container;
    }
});

// Screenshot Control
L.Control.Screenshot = L.Control.extend({
    onAdd: function(map) {
        const container = L.DomUtil.create('div', 'leaflet-control-screenshot leaflet-bar');
        container.innerHTML = '<button title="Screenshot erstellen"><i class="fas fa-camera"></i></button>';
        
        const button = container.querySelector('button');
        button.addEventListener('click', function() {
            takeScreenshot(map);
        });
        
        L.DomEvent.disableClickPropagation(container);
        return container;
    }
});

// Messwerkzeug aktivieren/deaktivieren
function toggleMeasure(map) {
    measureActive = !measureActive;
    
    if (measureActive) {
        map.getContainer().style.cursor = 'crosshair';
        map.on('click', onMeasureClick);
    } else {
        map.getContainer().style.cursor = '';
        map.off('click', onMeasureClick);
        clearMeasure();
    }
}

function onMeasureClick(e) {
    const latlng = e.latlng;
    
    // Marker hinzufügen
    const marker = L.circleMarker(latlng, {
        radius: 5,
        color: '#ff0000',
        fillColor: '#ff0000',
        fillOpacity: 0.8
    }).addTo(map);
    measureMarkers.push(marker);
    
    // Wenn mindestens 2 Punkte, Linie zeichnen
    if (measureMarkers.length >= 2) {
        const points = measureMarkers.map(m => m.getLatLng());
        
        if (measurePath) {
            measurePath.remove();
        }
        
        measurePath = L.polyline(points, {
            color: '#ff0000',
            weight: 2,
            dashArray: '5, 10'
        }).addTo(map);
        
        // Distanz berechnen
        let totalDistance = 0;
        for (let i = 0; i < points.length - 1; i++) {
            totalDistance += map.distance(points[i], points[i + 1]);
        }
        
        // Popup mit Distanz
        const popup = L.popup()
            .setLatLng(latlng)
            .setContent(`<b>Distanz:</b> ${totalDistance.toFixed(2)} Pixel<br><small>Rechtsklick zum Beenden</small>`)
            .openOn(map);
    }
    
    // Rechtsklick zum Beenden
    map.once('contextmenu', function() {
        clearMeasure();
        toggleMeasure(map);
        document.querySelector('.leaflet-control-measure button').classList.remove('active');
    });
}

function clearMeasure() {
    measureMarkers.forEach(m => m.remove());
    measureMarkers = [];
    if (measurePath) {
        measurePath.remove();
        measurePath = null;
    }
}

// Zeichenwerkzeuge
let currentDrawing = null;
let drawingPoints = [];

function startDrawing(map, type) {
    clearCurrentDrawing(map);
    
    const container = map.getContainer();
    container.style.cursor = 'crosshair';
    
    switch(type) {
        case 'marker':
            map.once('click', function(e) {
                const marker = L.marker(e.latlng, {
                    draggable: true
                }).addTo(drawingItems);
                marker.bindPopup('Eigener Marker<br><small>Ziehbar</small>');
                container.style.cursor = '';
            });
            break;
            
        case 'polyline':
            drawingPoints = [];
            map.on('click', onDrawPolylineClick);
            map.once('contextmenu', finishPolyline);
            break;
            
        case 'polygon':
            drawingPoints = [];
            map.on('click', onDrawPolygonClick);
            map.once('contextmenu', finishPolygon);
            break;
            
        case 'circle':
            let circleCenter = null;
            map.once('click', function(e) {
                circleCenter = e.latlng;
                map.once('click', function(e2) {
                    const radius = map.distance(circleCenter, e2.latlng);
                    const circle = L.circle(circleCenter, {
                        radius: radius,
                        color: '#3388ff',
                        fillColor: '#3388ff',
                        fillOpacity: 0.2
                    }).addTo(drawingItems);
                    circle.bindPopup(`Radius: ${radius.toFixed(2)} Pixel`);
                    container.style.cursor = '';
                });
            });
            break;
    }
}

function onDrawPolylineClick(e) {
    drawingPoints.push(e.latlng);
    
    if (currentDrawing) {
        currentDrawing.remove();
    }
    
    currentDrawing = L.polyline(drawingPoints, {
        color: '#3388ff',
        weight: 3
    }).addTo(map);
}

function finishPolyline() {
    if (currentDrawing) {
        currentDrawing.remove();
        if (drawingPoints.length >= 2) {
            const line = L.polyline(drawingPoints, {
                color: '#3388ff',
                weight: 3
            }).addTo(drawingItems);
            
            let totalDistance = 0;
            for (let i = 0; i < drawingPoints.length - 1; i++) {
                totalDistance += map.distance(drawingPoints[i], drawingPoints[i + 1]);
            }
            line.bindPopup(`Länge: ${totalDistance.toFixed(2)} Pixel`);
        }
    }
    clearCurrentDrawing(map);
}

function onDrawPolygonClick(e) {
    drawingPoints.push(e.latlng);
    
    if (currentDrawing) {
        currentDrawing.remove();
    }
    
    if (drawingPoints.length >= 2) {
        currentDrawing = L.polygon(drawingPoints, {
            color: '#3388ff',
            fillColor: '#3388ff',
            fillOpacity: 0.2
        }).addTo(map);
    }
}

function finishPolygon() {
    if (currentDrawing) {
        currentDrawing.remove();
        if (drawingPoints.length >= 3) {
            const polygon = L.polygon(drawingPoints, {
                color: '#3388ff',
                fillColor: '#3388ff',
                fillOpacity: 0.2
            }).addTo(drawingItems);
            
            polygon.bindPopup('Polygon-Fläche');
        }
    }
    clearCurrentDrawing(map);
}

function clearCurrentDrawing(map) {
    map.off('click', onDrawPolylineClick);
    map.off('click', onDrawPolygonClick);
    currentDrawing = null;
    drawingPoints = [];
    map.getContainer().style.cursor = '';
}

function clearDrawings(map) {
    drawingItems.clearLayers();
}

// Screenshot-Funktion
function takeScreenshot(map) {
    const mapContainer = map.getContainer();
    
    // html2canvas verwenden (muss eingebunden werden)
    if (typeof html2canvas !== 'undefined') {
        html2canvas(mapContainer).then(canvas => {
            const link = document.createElement('a');
            link.download = `map-screenshot-${Date.now()}.png`;
            link.href = canvas.toDataURL();
            link.click();
        });
    } else {
        alert('Screenshot-Funktion benötigt html2canvas Library. Bitte in HTML einbinden:\n<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>');
    }
}

// Raster-Overlay erstellen
function createGridOverlay(map, size) {
    if (gridLayer) {
        gridLayer.remove();
    }
    
    const bounds = map.getBounds();
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    // Canvas größe basierend auf Map-Bounds
    const pixelBounds = map.getPixelBounds();
    canvas.width = pixelBounds.max.x - pixelBounds.min.x;
    canvas.height = pixelBounds.max.y - pixelBounds.min.y;
    
    // Raster zeichnen
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.3)';
    ctx.lineWidth = 1;
    
    for (let x = 0; x < canvas.width; x += size) {
        ctx.beginPath();
        ctx.moveTo(x, 0);
        ctx.lineTo(x, canvas.height);
        ctx.stroke();
    }
    
    for (let y = 0; y < canvas.height; y += size) {
        ctx.beginPath();
        ctx.moveTo(0, y);
        ctx.lineTo(canvas.width, y);
        ctx.stroke();
    }
    
    // Als ImageOverlay zur Map hinzufügen
    const imageUrl = canvas.toDataURL();
    gridLayer = L.imageOverlay(imageUrl, bounds, {
        opacity: 0.5,
        interactive: false
    }).addTo(map);
}

// Koordinaten-Display aktualisieren
function updateCoordinatesDisplay(map, latlng) {
    const zoomSpan = document.getElementById('current-zoom');
    const coordsSpan = document.getElementById('current-coords');
    
    if (zoomSpan) {
        zoomSpan.textContent = `Zoom: ${map.getZoom().toFixed(1)}`;
    }
    
    if (coordsSpan && latlng) {
        coordsSpan.textContent = `X: ${latlng.lng.toFixed(2)}, Y: ${latlng.lat.toFixed(2)}`;
    }
}

// Initialisiere erweiterte Map-Features
function initExtendedMapFeatures(map, config) {
    // Zeichnungs-Layer zur Map hinzufügen
    drawingItems.addTo(map);
    
    // Koordinaten-Anzeige
    if (config.showCoordinates) {
        coordinatesControl = new L.Control.Coordinates({ position: 'bottomleft' });
        map.addControl(coordinatesControl);
        
        map.on('zoom', function() {
            updateCoordinatesDisplay(map);
        });
    }
    
    // Live Maus-Koordinaten
    if (config.mouseCoordinates) {
        map.on('mousemove', function(e) {
            updateCoordinatesDisplay(map, e.latlng);
        });
    }
    
    // Koordinaten-Suche (oben links unter Zoom-Controls)
    if (config.enableSearch) {
        map.addControl(new L.Control.CoordinateSearch({ position: 'topleft' }));
    }
    
    // Messwerkzeug (oben rechts)
    if (config.enableMeasure) {
        map.addControl(new L.Control.MeasureTool({ position: 'topright' }));
    }
    
    // Zeichenwerkzeuge (oben rechts, unter Messwerkzeug)
    if (config.enableDrawing) {
        map.addControl(new L.Control.DrawingTools({ position: 'topright' }));
    }
    
    // Screenshot (oben rechts, unter Zeichenwerkzeug)
    map.addControl(new L.Control.Screenshot({ position: 'topright' }));
    
    // Vollbild-Modus (oben rechts, unter Screenshot)
    if (config.enableFullscreen && L.control.fullscreen) {
        map.addControl(new L.Control.Fullscreen({ position: 'topright' }));
    }
    
    // Maßstabsleiste
    if (config.scaleControl) {
        L.control.scale({
            position: 'bottomright',
            imperial: false,
            metric: true
        }).addTo(map);
    }
    
    // Mini-Map
    if (config.showMinimap && config.mapImage && L.Control.MiniMap) {
        const minimapLayer = L.imageOverlay(config.mapImage, map.getBounds());
        const miniMap = new L.Control.MiniMap(minimapLayer, {
            toggleDisplay: true,
            minimized: false,
            position: 'bottomright'
        }).addTo(map);
    }
    
    // Raster-Overlay
    if (config.gridEnabled) {
        map.on('moveend zoomend', function() {
            createGridOverlay(map, config.gridSize);
        });
        createGridOverlay(map, config.gridSize);
    }
    
    // Doppelklick-Zoom
    if (!config.doubleClickZoom) {
        map.doubleClickZoom.disable();
    }
    
    // Mausrad-Zoom
    if (!config.scrollWheelZoom) {
        map.scrollWheelZoom.disable();
    }
}

// Export für globale Nutzung
window.initExtendedMapFeatures = initExtendedMapFeatures;
