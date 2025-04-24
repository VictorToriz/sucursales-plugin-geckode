/**
 * Script para Sucursales en Google Maps (Reingeniería)
 * Version: 2.0.0
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
    
    // Obtener el elemento del mapa
    var mapElement = document.getElementById(sucursalesMapData.mapId);
    if (!mapElement) {
        console.error('No se encontró el elemento del mapa con ID: ' + sucursalesMapData.mapId);
        return;
    }
    
    // Guardar datos de sucursales para usar en el listado
    sucursalesData = sucursalesMapData.markers;
    
    // Crear el mapa
    sucursalesMap = new google.maps.Map(mapElement, {
        center: { lat: sucursalesMapData.center.lat, lng: sucursalesMapData.center.lng },
        zoom: sucursalesMapData.zoom,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        mapTypeControl: true,
        zoomControl: true,
        scaleControl: true,
        streetViewControl: true,
        fullscreenControl: true
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
    
    // Limpiar marcadores anteriores si existen
    clearSucursalesMarkers();
    
    // Crear nuevos marcadores
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
        
        // Ajustar el mapa para mostrar todos los marcadores si hay más de uno
        if (sucursalesData.length > 1) {
            sucursalesMap.fitBounds(bounds);
            
            // Asegurar que el zoom no sea demasiado cercano
            var listener = google.maps.event.addListener(sucursalesMap, 'idle', function() {
                if (sucursalesMap.getZoom() > 15) {
                    sucursalesMap.setZoom(15);
                }
                google.maps.event.removeListener(listener);
            });
        }
    }
    
    // Verificar si debemos mostrar el listado
    if (sucursalesMapData.mostrarLista !== false) {
        // Renderizar el listado de sucursales
        renderSucursalesList();
        
        // Crear filtro de estados si hay más de un estado
        if (sucursalesMapData.estados && sucursalesMapData.estados.length > 1) {
            createEstadosFilter(sucursalesMapData.estados);
        }
    }
    
    // Evento cuando se cierra la ventana de información
    google.maps.event.addListener(sucursalesInfoWindow, 'closeclick', function() {
        resetActiveState();
    });
    
    // Agregar eventos para el botón "Cómo llegar" en la ventana de información
    google.maps.event.addListener(sucursalesInfoWindow, 'domready', function() {
        // Buscar botones de dirección en la ventana de info y agregar eventos
        var directionsLinks = document.querySelectorAll('.directions-link');
        for (var i = 0; i < directionsLinks.length; i++) {
            directionsLinks[i].addEventListener('click', function(e) {
                e.preventDefault();
                var lat = parseFloat(this.getAttribute('data-lat'));
                var lng = parseFloat(this.getAttribute('data-lng'));
                var id = parseInt(this.getAttribute('data-id'));
                mostrarComoLlegar(lat, lng, id);
            });
        }
    });
}

// Función para crear un marcador
function createSucursalMarker(markerData, map, infoWindow) {
    // Crear el marcador
    var marker = new google.maps.Marker({
        position: new google.maps.LatLng(markerData.lat, markerData.lng),
        map: map,
        title: markerData.title,
        animation: google.maps.Animation.DROP,
        sucursalId: markerData.id
    });
    
    // Añadir evento de clic
    marker.addListener('click', function() {
        // Cerrar dirección activa si existe
        directionsRenderer.setMap(null);
        directionsRenderer.setMap(map);
        
        // Resetear estado activo previo
        resetActiveState();
        
        // Activar el marcador actual
        setActiveMarker(marker);
        
        // Activar el elemento correspondiente en la lista
        setActiveListItem(markerData.id);
        
        // Abrir infoWindow
        infoWindow.setContent(markerData.content);
        infoWindow.open(map, marker);
        
        // Hacer scroll al elemento en la lista si existe
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

// Función para establecer marcador activo
function setActiveMarker(marker) {
    // Restablecer el marcador activo anterior
    if (activeMarker) {
        activeMarker.setAnimation(null);
    }
    
    // Establecer el nuevo marcador activo
    activeMarker = marker;
    if (marker) {
        marker.setAnimation(google.maps.Animation.BOUNCE);
        setTimeout(function() {
            marker.setAnimation(null);
        }, 700);
    }
}

// Función para establecer elemento de lista activo
function setActiveListItem(sucursalId) {
    // Restablecer el elemento activo anterior
    if (activeListItem) {
        activeListItem.classList.remove('active');
    }
    
    // Establecer el nuevo elemento activo
    activeListItem = document.getElementById('sucursal-item-' + sucursalId);
    if (activeListItem) {
        activeListItem.classList.add('active');
    }
}

// Función para resetear estados activos
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

// Función para limpiar marcadores
function clearSucursalesMarkers() {
    for (var i = 0; i < sucursalesMarkers.length; i++) {
        sucursalesMarkers[i].setMap(null);
    }
    sucursalesMarkers = [];
}

// Función para renderizar el listado de sucursales
function renderSucursalesList() {
    var mapContainer = document.getElementById(sucursalesMapData.mapId).parentNode;
    
    // Crear el contenedor del listado
    var listaContainer = document.createElement('div');
    listaContainer.className = 'sucursales-lista';
    
    // Crear encabezado
    var listaHeader = document.createElement('div');
    listaHeader.className = 'sucursales-lista-header';
    listaHeader.textContent = 'Nuestras Sucursales';
    listaContainer.appendChild(listaHeader);
    
    // Contenedor para el filtro (se llenará después si hay estados)
    var filtroContainer = document.createElement('div');
    filtroContainer.className = 'sucursales-filtro';
    filtroContainer.id = 'sucursales-filtro';
    filtroContainer.style.display = 'none';
    listaContainer.appendChild(filtroContainer);
    
    // Crear la lista de sucursales
    var lista = document.createElement('ul');
    lista.className = 'sucursales-items';
    
    // Añadir cada sucursal a la lista
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
        
        // Botones de acción
        html += '<div class="sucursal-item-acciones">';
        html += '<a href="#" class="sucursal-boton sucursal-boton-ver" data-id="' + sucursal.id + '">Ver en mapa</a>';
        html += '<a href="#" class="sucursal-boton sucursal-boton-direcciones" data-id="' + sucursal.id + '" data-lat="' + sucursal.lat + '" data-lng="' + sucursal.lng + '">Cómo llegar</a>';
        html += '</div>';
        
        item.innerHTML = html;
        lista.appendChild(item);
    }
    
    listaContainer.appendChild(lista);
    
    // Insertar el listado antes del mapa
    mapContainer.parentNode.insertBefore(listaContainer, mapContainer);
    
    // Añadir eventos a los botones
    agregarEventosBotones();
}

// Función para agregar eventos a los botones
function agregarEventosBotones() {
    // Botones "Ver en mapa"
    var botonesVer = document.querySelectorAll('.sucursal-boton-ver');
    for (var i = 0; i < botonesVer.length; i++) {
        botonesVer[i].addEventListener('click', function(e) {
            e.preventDefault();
            var sucursalId = parseInt(this.getAttribute('data-id'));
            mostrarSucursalEnMapa(sucursalId);
        });
    }
    
    // Botones "Cómo llegar"
    var botonesDirecciones = document.querySelectorAll('.sucursal-boton-direcciones');
    for (var i = 0; i < botonesDirecciones.length; i++) {
        botonesDirecciones[i].addEventListener('click', function(e) {
            e.preventDefault();
            var lat = parseFloat(this.getAttribute('data-lat'));
            var lng = parseFloat(this.getAttribute('data-lng'));
            var sucursalId = parseInt(this.getAttribute('data-id'));
            mostrarComoLlegar(lat, lng, sucursalId);
        });
    }
    
    // Click en el elemento de la lista
    var items = document.querySelectorAll('.sucursal-item');
    for (var i = 0; i < items.length; i++) {
        items[i].addEventListener('click', function(e) {
            // Evitar activación si se hace clic en un botón
            if (e.target.classList.contains('sucursal-boton')) {
                return;
            }
            
            var sucursalId = parseInt(this.getAttribute('data-id'));
            mostrarSucursalEnMapa(sucursalId);
        });
    }
}

// Función para mostrar una sucursal en el mapa
function mostrarSucursalEnMapa(sucursalId) {
    // Cerrar dirección activa si existe
    directionsRenderer.setMap(null);
    directionsRenderer.setMap(sucursalesMap);
    
    // Buscar el marcador correspondiente
    for (var i = 0; i < sucursalesMarkers.length; i++) {
        if (sucursalesMarkers[i].sucursalId === sucursalId) {
            // Simular un clic en el marcador
            google.maps.event.trigger(sucursalesMarkers[i], 'click');
            break;
        }
    }
}

// Función para mostrar cómo llegar a una sucursal
function mostrarComoLlegar(lat, lng, sucursalId) {
    // Verificar si el navegador soporta geolocalización
    if (navigator.geolocation) {
        // Primero activamos la sucursal en el mapa
        mostrarSucursalEnMapa(sucursalId);
        
        // Luego pedimos la ubicación del usuario
        navigator.geolocation.getCurrentPosition(
            function(position) {
                var origen = new google.maps.LatLng(
                    position.coords.latitude,
                    position.coords.longitude
                );
                
                var destino = new google.maps.LatLng(lat, lng);
                
                // Calcular la ruta
                var request = {
                    origin: origen,
                    destination: destino,
                    travelMode: google.maps.TravelMode.DRIVING
                };
                
                directionsService.route(request, function(result, status) {
                    if (status == google.maps.DirectionsStatus.OK) {
                        // Cerrar cualquier infowindow abierta
                        sucursalesInfoWindow.close();
                        
                        // Mostrar direcciones
                        directionsRenderer.setDirections(result);
                    } else {
                        alert('No se pudo calcular la ruta. Error: ' + status);
                    }
                });
            },
            function(error) {
                // Si hay error o se denegó el permiso
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        // Abrir Google Maps directamente
                        window.open('https://www.google.com/maps/dir//' + lat + ',' + lng, '_blank');
                        break;
                    default:
                        alert('Error al obtener su ubicación. Por favor, intente de nuevo.');
                        break;
                }
            }
        );
    } else {
        // Si no hay soporte de geolocalización, abrir Google Maps
        window.open('https://www.google.com/maps/dir//' + lat + ',' + lng, '_blank');
    }
}

// Función para crear el filtro de estados
function createEstadosFilter(estados) {
    if (!estados || estados.length <= 1) return;
    
    var filtroContainer = document.getElementById('sucursales-filtro');
    if (!filtroContainer) return;
    
    // Mostrar el contenedor
    filtroContainer.style.display = 'block';
    
    // Crear el select
    var select = document.createElement('select');
    select.id = 'filtro-estado';
    
    // Opción por defecto
    var defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'Todos los estados';
    select.appendChild(defaultOption);
    
    // Ordenar estados alfabéticamente
    estados.sort(function(a, b) {
        return a.nombre.localeCompare(b.nombre);
    });
    
    // Añadir cada estado
    for (var i = 0; i < estados.length; i++) {
        var option = document.createElement('option');
        option.value = estados[i].slug;
        option.textContent = estados[i].nombre;
        select.appendChild(option);
    }
    
    filtroContainer.appendChild(select);
    
    // Evento al cambiar el estado
    select.addEventListener('change', function() {
        var estadoSeleccionado = this.value;
        filtrarSucursalesPorEstado(estadoSeleccionado);
    });
}

// Función para filtrar sucursales por estado
function filtrarSucursalesPorEstado(estado) {
    var items = document.querySelectorAll('.sucursal-item');
    
    // Resetear vista
    directionsRenderer.setMap(null);
    directionsRenderer.setMap(sucursalesMap);
    sucursalesInfoWindow.close();
    resetActiveState();
    
    if (!estado) {
        // Mostrar todas las sucursales
        for (var i = 0; i < items.length; i++) {
            items[i].style.display = 'block';
        }
        
        // Mostrar todos los marcadores
        for (var i = 0; i < sucursalesMarkers.length; i++) {
            sucursalesMarkers[i].setVisible(true);
        }
        
        // Ajustar mapa para mostrar todos los marcadores
        var bounds = new google.maps.LatLngBounds();
        for (var i = 0; i < sucursalesMarkers.length; i++) {
            bounds.extend(sucursalesMarkers[i].getPosition());
        }
        sucursalesMap.fitBounds(bounds);
    } else {
        // Filtrar por estado
        var visibleMarkers = [];
        
        for (var i = 0; i < items.length; i++) {
            var itemEstado = items[i].getAttribute('data-estado');
            if (itemEstado === estado) {
                items[i].style.display = 'block';
                
                // Encontrar el marcador correspondiente
                var sucursalId = parseInt(items[i].getAttribute('data-id'));
                for (var j = 0; j < sucursalesMarkers.length; j++) {
                    if (sucursalesMarkers[j].sucursalId === sucursalId) {
                        sucursalesMarkers[j].setVisible(true);
                        visibleMarkers.push(sucursalesMarkers[j]);
                    }
                }
            } else {
                items[i].style.display = 'none';
                
                // Ocultar marcador correspondiente
                var sucursalId = parseInt(items[i].getAttribute('data-id'));
                for (var j = 0; j < sucursalesMarkers.length; j++) {
                    if (sucursalesMarkers[j].sucursalId === sucursalId) {
                        sucursalesMarkers[j].setVisible(false);
                    }
                }
            }
        }
        
        // Ajustar mapa para mostrar marcadores visibles
        if (visibleMarkers.length > 0) {
            var bounds = new google.maps.LatLngBounds();
            for (var i = 0; i < visibleMarkers.length; i++) {
                bounds.extend(visibleMarkers[i].getPosition());
            }
            sucursalesMap.fitBounds(bounds);
        }
    }
}

// Si la API de Google Maps ya está cargada, inicializar
if (typeof google === 'object' && typeof google.maps === 'object') {
    initSucursalesMap();
}

jQuery(document).ready(function($) {
    // Código adicional de jQuery si es necesario
});