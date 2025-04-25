/**
 * Script de administración para Sucursales en Google Maps (Reingeniería)
 * Version: 2.1.0
 */

jQuery(document).ready(function($) {
    // Inicializar los color pickers
    $('.color-picker').wpColorPicker({
        change: function(event, ui) {
            // Actualizar previsualización al cambiar color
            var id = $(this).attr('id');
            var color = ui.color.toString();
            
            switch(id) {
                case 'header_color':
                    $('.color-preview-header').css('background-color', color);
                    break;
                case 'primary_button_color':
                    $('.color-preview-primary').css('background-color', color);
                    break;
                case 'secondary_button_color':
                    $('.color-preview-secondary').css('background-color', color);
                    break;
            }
        }
    });
    
    // Manejo de pestañas en la página de configuración
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Quitar clase activa de todas las pestañas
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Ocultar todos los contenidos de pestaña
        $('.tab-content').removeClass('active').hide();
        
        // Mostrar el contenido correspondiente
        var target = $(this).attr('href');
        $(target).addClass('active').show();
    });
    
    // Mostrar la primera pestaña por defecto
    $('.tab-content').hide();
    $('.tab-content.active').show();
});