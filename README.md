# 🗺️ Sucursales en Google Maps Reingeniería

![Version](https://img.shields.io/badge/version-2.1.0-blue.svg) ![WordPress](https://img.shields.io/badge/WordPress-5.8+-green.svg) ![PHP](https://img.shields.io/badge/PHP-7.0+-orange.svg)

Plugin de WordPress para mostrar sucursales o puntos de venta en Google Maps con diversas opciones de personalización. Versión mejorada y optimizada.

## 👀 Vista previa

![Sucursales en Google Maps - Vista Principal](https://github.com/VictorToriz/sucursales-plugin-geckode/blob/a3a189318820b9858a7b024b1091a36888c0604e/sucursales-maps-r/screenshots/1.png?raw=true)

*Vista del listado de sucursales con mapa interactivo*

## ✨ Características

- 📍 Muestra múltiples ubicaciones en un mapa interactivo
- 📱 Diseño 100% responsive para móviles, tablets y escritorio
- 🔍 Filtrado de sucursales por estado
- 📋 Visualización de lista de sucursales con detalles
- 🚗 Botón "Cómo llegar" que mantiene su estilo visual
- 🎨 Personalización de colores y estilos desde el panel de administración
- 🚀 Compatible con WP Rocket y optimización LazyLoad
- 🔎 Street View integrado (opcional)

## 📸 Capturas de pantalla

### Vista principal con listado y mapa
![Listado de sucursales y mapa](https://github.com/VictorToriz/sucursales-plugin-geckode/blob/a3a189318820b9858a7b024b1091a36888c0604e/sucursales-maps-r/screenshots/1.png?raw=true)

### Filtrado por estados con selector desplegable
![Filtrado por estados](https://github.com/VictorToriz/sucursales-plugin-geckode/blob/a3a189318820b9858a7b024b1091a36888c0604e/sucursales-maps-r/screenshots/2.png?raw=true)

### Ventana de información al hacer clic en un marcador
![Ventana de información](https://github.com/VictorToriz/sucursales-plugin-geckode/blob/a3a189318820b9858a7b024b1091a36888c0604e/sucursales-maps-r/screenshots/3.png?raw=true)

## 📋 Requisitos

- WordPress 5.8 o superior
- PHP 7.0 o superior
- API Key de Google Maps (con APIs: Maps JavaScript, Geocoding, Places)

## 🔧 Instalación

1. Sube la carpeta `sucursales-maps-r` al directorio `/wp-content/plugins/`
2. Activa el plugin desde el panel de administración de WordPress
3. Ve a **Sucursales > Configuración** y añade tu API Key de Google Maps
4. Personaliza los colores según tu tema
5. Crea las sucursales desde el menú **Sucursales > Añadir Nueva**

## 📝 Uso del Shortcode

Para insertar el mapa en cualquier página o entrada, utiliza el siguiente shortcode:

```
[sucursales_mapa]
```

### Opciones disponibles:

| Parámetro | Descripción | Valor predeterminado |
|-----------|-------------|----------------------|
| `altura`  | Altura del mapa | `500px` |
| `ancho` | Ancho del mapa | `100%` |
| `zoom` | Nivel de zoom inicial | `10` |
| `id` | ID de sucursal específica | `0` (todas) |
| `estado` | Slug del estado para filtrar | `''` (todos) |
| `lista` | Mostrar lista de sucursales | `si` |
| `streetview` | Habilitar Street View | `no` |

### Ejemplos:

```
[sucursales_mapa altura="600px" ancho="90%" zoom="12"]
[sucursales_mapa id="42"]
[sucursales_mapa estado="jalisco"]
[sucursales_mapa lista="no"]
[sucursales_mapa streetview="si"]
```

## 🔄 Compatibilidad con WP Rocket

Para evitar problemas con LazyLoad en WP Rocket:

1. Ve a **WP Rocket > Ajustes > Medios**
2. En la sección **LazyLoad**, añade la clase `no-lazy` en el campo **Excluir imágenes**
3. Guarda los cambios

El plugin ya añade automáticamente la clase `no-lazy` a las imágenes de las sucursales.

## 📱 Diseño Responsive

El plugin está optimizado para visualización en dispositivos móviles:

- Listado de sucursales ajustado al ancho de la pantalla
- Controles adaptados para uso táctil
- Selector de estados mejorado para dispositivos móviles
- Botones más grandes para fácil interacción

## 🎯 Mejoras en la versión 2.1.0

- ✅ El listado de sucursales ocupa ancho completo en versión móvil
- ✅ El botón "Cómo llegar" mantiene su color después de hacer clic
- ✅ Compatibilidad con exclusión de LazyLoad para WP Rocket
- ✅ Optimización de rendimiento y código JS/CSS
- ✅ Corregidos problemas con selectores en la vista móvil

## 🔮 Próximas funcionalidades

- 📱 Aplicación móvil complementaria
- 🌍 Soporte para múltiples idiomas
- 📊 Estadísticas de visualización de sucursales
- 📷 Galería de imágenes por sucursal

## 🛠️ Personalización avanzada

El plugin permite personalizar:

- 🎨 Colores de cabecera y botones
- 📋 Información mostrada en cada sucursal
- 🗺️ Opciones de visualización del mapa
- 🔍 Comportamiento del filtro de estados

## 📜 Licencia

Este plugin está licenciado bajo [GPLv2 o posterior](https://www.gnu.org/licenses/gpl-2.0.html).

## 👨‍💻 Desarrollado por

[Geckode](https://geckode.com.mx) - Especialistas en Desarrollo WordPress

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor, envía tus pull requests o reporta cualquier problema que encuentres.

---

¿Necesitas ayuda? [Contacta con nosotros](https://geckode.com.mx/contacto)
