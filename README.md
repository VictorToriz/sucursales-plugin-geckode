# Plugin WordPress: Sucursales en Google Maps (Reingeniería)

**Versión:** 2.0.0  
**Autor:** Geckode/Victor Toriz
**Licencia:** MIT

## 🧭 Descripción

Este plugin permite mostrar un **mapa interactivo con sucursales** utilizando Google Maps dentro de tu sitio WordPress. Ofrece una experiencia amigable para el usuario con funcionalidades como filtros por estado, listado dinámico, y botón de "Cómo llegar" con geolocalización.

## 🚀 Características

- Muestra sucursales con marcadores en Google Maps.
- Vista combinada de mapa y listado lateral.
- Filtro dinámico por estado o región.
- Ventanas de información personalizadas.
- Compatibilidad con la API de Google Directions.
- Estilo moderno y diseño responsivo.
- Panel de administración personalizado en WordPress.

## 📦 Archivos Principales

| Archivo | Descripción |
|--------|-------------|
| `sucursales-maps-r.php` | Código principal del plugin WordPress. |
| `sucursales-maps-r.js` | Lógica de interacción con Google Maps y listado. |
| `sucursales-maps-r.css` | Estilos front-end para mapa y listado. |
| `admin-styles.css` | Estilos del panel de administración. |

## 🛠️ Requisitos

- WordPress 5.0 o superior
- Una clave válida de la API de Google Maps con acceso a:
  - Maps JavaScript API
  - Places API
  - Directions API

## ⚙️ Instalación

1. Sube el contenido del plugin al directorio `/wp-content/plugins/`.
2. Activa el plugin desde el panel de administración de WordPress.
3. Configura tus sucursales desde el menú de administración.
4. Inserta el mapa usando el shortcode:

```php
[sucursales_mapa]
```

## 💡 Shortcode Opciones

Puedes personalizar el mapa con los siguientes atributos:

```php
[sucursales_mapa zoom="12" center_lat="19.4326" center_lng="-99.1332" mostrar_lista="true"]
```

| Atributo       | Descripción                          | Valor por defecto |
|----------------|--------------------------------------|-------------------|
| `zoom`         | Nivel de zoom del mapa               | `10`              |
| `center_lat`   | Latitud del centro del mapa          | `0.0`             |
| `center_lng`   | Longitud del centro del mapa         | `0.0`             |
| `mostrar_lista`| Mostrar el listado lateral de sucursales | `true`         |

## ✏️ Personalización

Los estilos están divididos para facilidad de edición:
- `sucursales-maps-r.css`: interfaz pública.
- `admin-styles.css`: panel de administración.

## 🧪 Desarrollo

Este plugin fue desarrollado con énfasis en modularidad y buenas prácticas. El archivo JavaScript utiliza eventos delegados y se adapta dinámicamente a los datos proporcionados por la API de Google.

## 📄 Licencia

Este plugin está licenciado bajo la [MIT License](LICENSE).