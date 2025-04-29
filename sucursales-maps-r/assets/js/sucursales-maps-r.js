/**
 * Script para Sucursales en Google Maps (Reingeniería) - MODIFICADO
 * Version: 2.1.0
 */

// Variables globales
var sucursalesMap = null;
var sucursalesInfoWindow = null;
var sucursalesMarkers = [];
var directionsService = null;
var directionsRenderer = null;
var sucursalesData = [];
var activeMarker = null;
var activeListItem = null;

// Función de inicialización llamada por la API de Google Maps
function initSucursalesMap() {
    if (typeof sucursalesMapData === 'undefined') {
        console.error('No hay datos para inicializar el mapa de sucursales');
        return;
    }

    // Guardar datos de sucursales
    sucursalesData = sucursalesMapData.markers;

    // Renderizar listado si es necesario
    if (sucursalesMapData.mostrarLista !== false) {
        createSucursalesLayout();
    }

    // Obtener elemento del mapa
    var mapElement = document.getElementById(sucursalesMapData.mapId);
    if (!mapElement) {
        console.error('No se encontró el elemento del mapa con ID: ' + sucursalesMapData.mapId);
        return;
    }

    // Crear el mapa
    sucursalesMap = new google.maps.Map(mapElement, {
        center: { lat: sucursalesMapData.center.lat, lng: sucursalesMapData.center.lng },
        zoom: sucursalesMapData.zoom,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        mapTypeControl: false,
        zoomControl: true,
        scaleControl: false,
        streetViewControl: false,
        fullscreenControl: false,
        scrollwheel: true,
        disableDefaultUI: true
    });

    // Crear servicios de direcciones
    directionsService = new google.maps.DirectionsService();
    directionsRenderer = new google.maps.DirectionsRenderer({
        suppressMarkers: false,
        preserveViewport: false
    });
    directionsRenderer.setMap(sucursalesMap);

    // Crear ventana de información compartida
    sucursalesInfoWindow = new google.maps.InfoWindow({
        pixelOffset: new google.maps.Size(0, -5)
    });

    // Crear marcadores
    var bounds = new google.maps.LatLngBounds();
    clearSucursalesMarkers();

    if (sucursalesData && sucursalesData.length > 0) {
        for (var i = 0; i < sucursalesData.length; i++) {
            var marker = createSucursalMarker(
                sucursalesData[i],
                sucursalesMap,
                sucursalesInfoWindow
            );
            sucursalesMarkers.push(marker);
            bounds.extend(marker.position);
        }

        if (sucursalesData.length > 1) {
            sucursalesMap.fitBounds(bounds);

            var listener = google.maps.event.addListener(sucursalesMap, 'idle', function () {
                if (sucursalesMap.getZoom() > 15) {
                    sucursalesMap.setZoom(15);
                }
                google.maps.event.removeListener(listener);
            });
        }
    }

    if (sucursalesMapData.estados && sucursalesMapData.estados.length > 1) {
        createEstadosFilter(sucursalesMapData.estados);
    }

    google.maps.event.addListener(sucursalesInfoWindow, 'closeclick', function () {
        resetActiveState();
    });

    google.maps.event.addListener(sucursalesInfoWindow, 'domready', function () {
        var directionsLinks = document.querySelectorAll('.directions-link');
        for (var i = 0; i < directionsLinks.length; i++) {
            directionsLinks[i].addEventListener('click', function (e) {
                e.preventDefault();
                var lat = parseFloat(this.getAttribute('data-lat'));
                var lng = parseFloat(this.getAttribute('data-lng'));
                var id = parseInt(this.getAttribute('data-id'));
                mostrarComoLlegar(lat, lng, id);
            });
        }
    });
}

function createSucursalesLayout() {
    var originalContainer = document.getElementById('sucursales-container-' + sucursalesMapData.mapId);
    if (!originalContainer) {
        console.error('No se encontró el contenedor de sucursales');
        return;
    }

    originalContainer.className = 'sucursales-container';
    originalContainer.style.display = 'flex';
    originalContainer.style.flexDirection = 'row';
    originalContainer.style.gap = '20px';

    var listaContainer = document.createElement('div');
    listaContainer.className = 'sucursales-lista';
    listaContainer.style.flex = '0 0 30%';
    listaContainer.style.minWidth = '250px';
    listaContainer.style.maxWidth = '350px';

    var listaHeader = document.createElement('div');
    listaHeader.className = 'sucursales-lista-header';
    listaHeader.textContent = 'Nuestras Sucursales';
    listaContainer.appendChild(listaHeader);

    var filtroContainer = document.createElement('div');
    filtroContainer.className = 'sucursales-filtro';
    filtroContainer.id = 'sucursales-filtro';
    filtroContainer.style.display = 'none';
    listaContainer.appendChild(filtroContainer);

    var lista = document.createElement('ul');
    lista.className = 'sucursales-items';

    for (var i = 0; i < sucursalesData.length; i++) {
        var sucursal = sucursalesData[i];

        var item = document.createElement('li');
        item.className = 'sucursal-item';
        item.id = 'sucursal-item-' + sucursal.id;
        item.setAttribute('data-id', sucursal.id);
        item.setAttribute('data-estado', sucursal.estado || '');

        var html = '';
        html += '<h4>' + sucursal.title + '</h4>';
        if (sucursal.address) {
            html += '<p><i class="dashicons dashicons-location"></i> ' + sucursal.address + '</p>';
        }
        if (sucursal.phone) {
            html += '<p><i class="dashicons dashicons-phone"></i> ' + sucursal.phone + '</p>';
        }
        if (sucursal.schedule) {
            html += '<p><i class="dashicons dashicons-clock"></i> ' + sucursal.schedule + '</p>';
        }
        html += '<div class="sucursal-item-acciones">';
        html += '<a href="#" class="sucursal-boton sucursal-boton-ver" data-id="' + sucursal.id + '">Ver en mapa</a>';
        html += '<a href="#" class="sucursal-boton sucursal-boton-direcciones" data-id="' + sucursal.id + '" data-lat="' + sucursal.lat + '" data-lng="' + sucursal.lng + '">Cómo llegar</a>';
        html += '</div>';

        item.innerHTML = html;
        lista.appendChild(item);
    }

    listaContainer.appendChild(lista);

    var mapContainer = document.createElement('div');
    mapContainer.id = sucursalesMapData.mapId;
    mapContainer.className = 'sucursales-map';
    mapContainer.style.flex = '1';
    mapContainer.style.minHeight = '500px';

    while (originalContainer.firstChild) {
        originalContainer.removeChild(originalContainer.firstChild);
    }

    originalContainer.appendChild(listaContainer);
    originalContainer.appendChild(mapContainer);

    agregarEventosBotones();
}

function createSucursalMarker(markerData, map, infoWindow) {
    var marker = new google.maps.Marker({
        position: new google.maps.LatLng(markerData.lat, markerData.lng),
        map: map,
        title: markerData.title,
        animation: google.maps.Animation.DROP,
        sucursalId: markerData.id
    });

    marker.addListener('click', function () {
        directionsRenderer.setMap(null);
        directionsRenderer.setMap(map);
        resetActiveState();
        setActiveMarker(marker);
        setActiveListItem(markerData.id);
        infoWindow.setContent(markerData.content);
        infoWindow.open(map, marker);

        var listItem = document.getElementById('sucursal-item-' + markerData.id);
        if (listItem) {
            var listContainer = document.querySelector('.sucursales-items');
            if (listContainer) {
                listContainer.scrollTop = listItem.offsetTop - listContainer.offsetTop;
            }
        }
    });

    return marker;
}

function setActiveMarker(marker) {
    if (activeMarker) {
        activeMarker.setAnimation(null);
    }
    activeMarker = marker;
    if (marker) {
        marker.setAnimation(google.maps.Animation.BOUNCE);
        setTimeout(function () {
            marker.setAnimation(null);
        }, 700);
    }
}

function setActiveListItem(sucursalId) {
    if (activeListItem) {
        activeListItem.classList.remove('active');
    }
    activeListItem = document.getElementById('sucursal-item-' + sucursalId);
    if (activeListItem) {
        activeListItem.classList.add('active');
    }
}

function resetActiveState() {
    if (activeMarker) {
        activeMarker.setAnimation(null);
        activeMarker = null;
    }
    if (activeListItem) {
        activeListItem.classList.remove('active');
        activeListItem = null;
    }
}

function clearSucursalesMarkers() {
    for (var i = 0; i < sucursalesMarkers.length; i++) {
        sucursalesMarkers[i].setMap(null);
    }
    sucursalesMarkers = [];
}

function agregarEventosBotones() {
    var botonesVer = document.querySelectorAll('.sucursal-boton-ver');
    for (var i = 0; i < botonesVer.length; i++) {
        botonesVer[i].addEventListener('click', function (e) {
            e.preventDefault();
            var sucursalId = parseInt(this.getAttribute('data-id'));
            mostrarSucursalEnMapa(sucursalId);
        });
    }

    var botonesDirecciones = document.querySelectorAll('.sucursal-boton-direcciones');
    for (var i = 0; i < botonesDirecciones.length; i++) {
        botonesDirecciones[i].addEventListener('click', function (e) {
            e.preventDefault();
            var lat = parseFloat(this.getAttribute('data-lat'));
            var lng = parseFloat(this.getAttribute('data-lng'));
            var sucursalId = parseInt(this.getAttribute('data-id'));
            mostrarComoLlegar(lat, lng, sucursalId);
        });
    }

    var items = document.querySelectorAll('.sucursal-item');
    for (var i = 0; i < items.length; i++) {
        items[i].addEventListener('click', function (e) {
            if (e.target.classList.contains('sucursal-boton')) {
                return;
            }
            var sucursalId = parseInt(this.getAttribute('data-id'));
            mostrarSucursalEnMapa(sucursalId);
        });
    }
}

function mostrarSucursalEnMapa(sucursalId) {
    directionsRenderer.setMap(null);
    directionsRenderer.setMap(sucursalesMap);

    for (var i = 0; i < sucursalesMarkers.length; i++) {
        if (sucursalesMarkers[i].sucursalId === sucursalId) {
            google.maps.event.trigger(sucursalesMarkers[i], 'click');
            break;
        }
    }
}

// 🚀 Función para abrir Google Maps en nueva pestaña (modificado)
function mostrarComoLlegar(lat, lng, sucursalId) {
    var url = 'https://www.google.com/maps/dir/?api=1&destination=' + lat + ',' + lng;
    window.open(url, '_blank');
}

function createEstadosFilter(estados) {
    if (!estados || estados.length <= 1) return;

    var filtroContainer = document.getElementById('sucursales-filtro');
    if (!filtroContainer) return;

    filtroContainer.style.display = 'block';

    var select = document.createElement('select');
    select.id = 'filtro-estado';
    select.style.backgroundImage = 'none';

    var defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'Todos los estados';
    select.appendChild(defaultOption);

    estados.sort(function (a, b) {
        return a.nombre.localeCompare(b.nombre);
    });

    for (var i = 0; i < estados.length; i++) {
        var option = document.createElement('option');
        option.value = estados[i].slug;
        option.textContent = estados[i].nombre;
        select.appendChild(option);
    }

    filtroContainer.appendChild(select);

    select.addEventListener('change', function () {
        var estadoSeleccionado = this.value;
        filtrarSucursalesPorEstado(estadoSeleccionado);
    });
}

function filtrarSucursalesPorEstado(estado) {
    var items = document.querySelectorAll('.sucursal-item');

    directionsRenderer.setMap(null);
    directionsRenderer.setMap(sucursalesMap);
    sucursalesInfoWindow.close();
    resetActiveState();

    if (!estado) {
        for (var i = 0; i < items.length; i++) {
            items[i].style.display = 'block';
        }
        for (var i = 0; i < sucursalesMarkers.length; i++) {
            sucursalesMarkers[i].setVisible(true);
        }
        var bounds = new google.maps.LatLngBounds();
        for (var i = 0; i < sucursalesMarkers.length; i++) {
            bounds.extend(sucursalesMarkers[i].getPosition());
        }
        sucursalesMap.fitBounds(bounds);
    } else {
        var visibleMarkers = [];

        for (var i = 0; i < items.length; i++) {
            var itemEstado = items[i].getAttribute('data-estado');
            if (itemEstado === estado) {
                items[i].style.display = 'block';

                var sucursalId = parseInt(items[i].getAttribute('data-id'));
                for (var j = 0; j < sucursalesMarkers.length; j++) {
                    if (sucursalesMarkers[j].sucursalId === sucursalId) {
                        sucursalesMarkers[j].setVisible(true);
                        visibleMarkers.push(sucursalesMarkers[j]);
                    }
                }
            } else {
                items[i].style.display = 'none';

                var sucursalId = parseInt(items[i].getAttribute('data-id'));
                for (var j = 0; j < sucursalesMarkers.length; j++) {
                    if (sucursalesMarkers[j].sucursalId === sucursalId) {
                        sucursalesMarkers[j].setVisible(false);
                    }
                }
            }
        }

        if (visibleMarkers.length > 0) {
            var bounds = new google.maps.LatLngBounds();
            for (var i = 0; i < visibleMarkers.length; i++) {
                bounds.extend(visibleMarkers[i].getPosition());
            }
            sucursalesMap.fitBounds(bounds);
        }
    }
}

// Inicializar si ya existe google maps
if (typeof google === 'object' && typeof google.maps === 'object') {
    initSucursalesMap();
}

jQuery(document).ready(function($) {
    // Código jQuery adicional si se requiere
});
