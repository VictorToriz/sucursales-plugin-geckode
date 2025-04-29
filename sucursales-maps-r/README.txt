# 📍 Plugin: Sucursales Maps R (versión reparada)

## 🚀 Mejoras aplicadas
- Listado de sucursales ocupa **ancho completo** en versión móvil.
- Botón **"Cómo llegar"** mantiene su color después de hacer clic.
- Se implementó compatibilidad con **exclusión de LazyLoad** de imágenes para WP Rocket.

---

## ⚙️ Configuración para excluir imágenes del LazyLoad (WP Rocket)

Para evitar que las imágenes de sucursales sean cargadas de manera diferida (*LazyLoad*), sigue estos pasos:

1. Asegúrate de que WP Rocket esté instalado y activo en tu sitio.
2. Ve a **WP Rocket > Ajustes > Medios**.
3. Localiza la sección **LazyLoad**.
4. En el campo **Excluir imágenes**, agrega:

```
no-lazy
```

5. Guarda los cambios.

**Importante:**  
El plugin ya añade automáticamente la clase `no-lazy` a las imágenes de las sucursales, así que no necesitas hacer ningún cambio manual en el código.

---

## 📄 Notas
- Compatible con WordPress 5.8+.
- Requiere API Key de Google Maps.
- Las imágenes del mapa siempre se cargarán sin retraso para evitar errores visuales.