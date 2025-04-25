<?php
/**
 * Plugin Name: Sucursales en Google Maps Reingeniería
 * Plugin URI: https://geckode.com.mx
 * Description: Plugin para mostrar sucursales de empresas en Google Maps (Versión reingeniería mejorada)
 * Version: 2.1.0
 * Author: Geckode (Reingeniería)
 * Author URI: https://geckode.com.mx
 * Text Domain: sucursales-maps-r
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class Sucursales_Maps_R {
    
    // Constructor
    public function __construct() {
        // Registrar las acciones principales
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomy'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_data'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Registrar shortcode
        add_shortcode('sucursales_mapa', array($this, 'shortcode_map'));
        
        // Menú de administración
        add_action('admin_menu', array($this, 'admin_menu'));
    }
    
    // Registrar tipo de post personalizado
    public function register_post_type() {
        $labels = array(
            'name'               => 'Sucursales',
            'singular_name'      => 'Sucursal',
            'menu_name'          => 'Sucursales',
            'add_new'            => 'Añadir Nueva',
            'add_new_item'       => 'Añadir Nueva Sucursal',
            'edit_item'          => 'Editar Sucursal',
            'new_item'           => 'Nueva Sucursal',
            'view_item'          => 'Ver Sucursal',
            'search_items'       => 'Buscar Sucursales',
            'not_found'          => 'No se encontraron sucursales',
            'not_found_in_trash' => 'No se encontraron sucursales en la papelera'
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'has_archive'         => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'query_var'           => true,
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'menu_position'       => 20,
            'menu_icon'           => 'dashicons-location-alt',
            'supports'            => array('title', 'editor', 'thumbnail')
        );
        
        register_post_type('sucursal_r', $args);
    }
    
    // Registrar taxonomía para estados
    public function register_taxonomy() {
        $labels = array(
            'name'              => 'Estados',
            'singular_name'     => 'Estado',
            'search_items'      => 'Buscar Estados',
            'all_items'         => 'Todos los Estados',
            'edit_item'         => 'Editar Estado',
            'update_item'       => 'Actualizar Estado',
            'add_new_item'      => 'Añadir Nuevo Estado',
            'new_item_name'     => 'Nombre del Nuevo Estado',
            'menu_name'         => 'Estados'
        );
        
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'estado-r'),
        );
        
        register_taxonomy('estado_r', array('sucursal_r'), $args);
        
        // Registrar estados de México
        $this->register_states();
    }
    
    // Registrar estados de México
    private function register_states() {
        $estados = array(
            'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 
            'Chiapas', 'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima', 
            'Durango', 'Estado de México', 'Guanajuato', 'Guerrero', 'Hidalgo', 
            'Jalisco', 'Michoacán', 'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca', 
            'Puebla', 'Querétaro', 'Quintana Roo', 'San Luis Potosí', 'Sinaloa', 
            'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán', 
            'Zacatecas'
        );
        
        foreach ($estados as $estado) {
            if (!term_exists($estado, 'estado_r')) {
                wp_insert_term($estado, 'estado_r');
            }
        }
    }
    
    // Agregar metaboxes para coordenadas
    public function add_meta_boxes() {
        add_meta_box(
            'sucursal_r_location',
            'Datos de ubicación',
            array($this, 'location_meta_box'),
            'sucursal_r',
            'normal',
            'high'
        );
    }
    
    // Renderizar metabox de ubicación
    public function location_meta_box($post) {
        // Crear nonce para seguridad
        wp_nonce_field('sucursal_r_location_nonce', 'sucursal_r_location_nonce');
        
        // Obtener datos guardados
        $latitude = get_post_meta($post->ID, '_latitude', true);
        $longitude = get_post_meta($post->ID, '_longitude', true);
        $address = get_post_meta($post->ID, '_address', true);
        $phone = get_post_meta($post->ID, '_phone', true);
        $schedule = get_post_meta($post->ID, '_schedule', true);
        
        // Renderizar campos
        ?>
        <div class="location-box">
            <div class="location-row">
                <div class="location-column">
                    <label for="latitude"><strong>Latitud:</strong></label>
                    <input type="text" id="latitude" name="latitude" value="<?php echo esc_attr($latitude); ?>" class="location-field">
                </div>
                <div class="location-column">
                    <label for="longitude"><strong>Longitud:</strong></label>
                    <input type="text" id="longitude" name="longitude" value="<?php echo esc_attr($longitude); ?>" class="location-field">
                </div>
            </div>
            
            <div class="location-row">
                <div class="location-column" style="flex: 2">
                    <label for="address"><strong>Dirección completa:</strong></label>
                    <textarea id="address" name="address" rows="3" class="location-field"><?php echo esc_textarea($address); ?></textarea>
                </div>
            </div>
            
            <div class="location-row">
                <div class="location-column">
                    <label for="phone"><strong>Teléfono:</strong></label>
                    <input type="text" id="phone" name="phone" value="<?php echo esc_attr($phone); ?>" class="location-field">
                </div>
                <div class="location-column">
                    <label for="schedule"><strong>Horario de atención:</strong></label>
                    <textarea id="schedule" name="schedule" rows="3" class="location-field"><?php echo esc_textarea($schedule); ?></textarea>
                </div>
            </div>
            
            <div class="location-help">
                <p>Para obtener las coordenadas puedes usar <a href="https://www.latlong.net/" target="_blank">latlong.net</a> o <a href="https://www.google.com/maps" target="_blank">Google Maps</a> (haz clic derecho en la ubicación y selecciona "¿Qué hay aquí?").</p>
            </div>
        </div>
        <?php
    }
    
    // Guardar datos del metabox
    public function save_meta_data($post_id) {
        // Verificar nonce
        if (!isset($_POST['sucursal_r_location_nonce']) || !wp_verify_nonce($_POST['sucursal_r_location_nonce'], 'sucursal_r_location_nonce')) {
            return;
        }
        
        // Verificar autoguardado
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Verificar permisos
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Guardar datos
        if (isset($_POST['latitude'])) {
            update_post_meta($post_id, '_latitude', sanitize_text_field($_POST['latitude']));
        }
        
        if (isset($_POST['longitude'])) {
            update_post_meta($post_id, '_longitude', sanitize_text_field($_POST['longitude']));
        }
        
        if (isset($_POST['address'])) {
            update_post_meta($post_id, '_address', sanitize_textarea_field($_POST['address']));
        }
        
        if (isset($_POST['phone'])) {
            update_post_meta($post_id, '_phone', sanitize_text_field($_POST['phone']));
        }
        
        if (isset($_POST['schedule'])) {
            update_post_meta($post_id, '_schedule', sanitize_textarea_field($_POST['schedule']));
        }
    }
    
    // Cargar scripts y estilos en el frontend
    public function enqueue_scripts() {
        // Solo cargar si el shortcode está en la página
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'sucursales_mapa')) {
            
            // Cargar dashicons (para los iconos en el listado)
            wp_enqueue_style('dashicons');
            
            // Estilos básicos
            wp_enqueue_style(
                'sucursales-maps-r-style',
                plugin_dir_url(__FILE__) . 'assets/css/sucursales-maps-r.css',
                array(),
                '2.1.0'
            );
            
            // Obtener API Key
            $api_key = get_option('sucursales_maps_r_api_key', '');
            
            if (!empty($api_key)) {
                // Google Maps API (ahora incluimos bibliotecas adicionales)
                wp_enqueue_script(
                    'google-maps-api',
                    'https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&libraries=places&callback=initSucursalesMap',
                    array(),
                    null,
                    true
                );
                
                // Script personalizado
                wp_enqueue_script(
                    'sucursales-maps-r-script',
                    plugin_dir_url(__FILE__) . 'assets/js/sucursales-maps-r.js',
                    array('jquery'),
                    '2.1.0',
                    true
                );
                
                // Generar CSS dinámico con los colores personalizados
                $this->generate_dynamic_styles();
            }
        }
    }
    
    // Generar estilos dinámicos basados en la configuración
    private function generate_dynamic_styles() {
        $header_color = get_option('sucursales_maps_r_header_color', '#0073aa');
        $primary_button_color = get_option('sucursales_maps_r_primary_button_color', '#0073aa');
        $secondary_button_color = get_option('sucursales_maps_r_secondary_button_color', '#28a745');
        
        $custom_css = "
            .sucursales-lista-header {
                background-color: {$header_color};
            }
            .sucursal-item.active {
                border-left: 3px solid {$header_color};
            }
            .sucursal-boton {
                background-color: {$primary_button_color};
            }
            .sucursal-boton:hover {
                background-color: " . $this->adjust_brightness($primary_button_color, -30) . ";
            }
            .sucursal-boton-direcciones, .directions-link {
                background-color: {$secondary_button_color};
            }
            .sucursal-boton-direcciones:hover, .directions-link:hover {
                background-color: " . $this->adjust_brightness($secondary_button_color, -30) . ";
            }
        ";
        
        wp_add_inline_style('sucursales-maps-r-style', $custom_css);
    }
    
    // Función para ajustar el brillo del color (para hover states)
    private function adjust_brightness($hex, $steps) {
        // Remover el # si existe
        $hex = ltrim($hex, '#');
        
        // Convertir a RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Ajustar brillo
        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));
        
        // Convertir de vuelta a HEX
        return '#' . sprintf('%02x', $r) . sprintf('%02x', $g) . sprintf('%02x', $b);
    }
    
    // Cargar estilos en admin
    public function admin_enqueue_scripts($hook) {
        // Para todas las páginas de administración relacionadas
        if ('post.php' === $hook || 'post-new.php' === $hook || 'sucursal_r_page_sucursales_r_settings' === $hook) {
            global $post;
            
            // En página de edición de sucursal o en la página de configuración
            if (('post.php' === $hook || 'post-new.php' === $hook) && (!$post || 'sucursal_r' !== $post->post_type)) {
                return;
            }
            
            // Cargar estilos admin
            wp_enqueue_style(
                'sucursales-maps-r-admin',
                plugin_dir_url(__FILE__) . 'assets/css/admin-styles.css',
                array(),
                '2.1.0'
            );
            
            // Si estamos en la página de configuración, cargar color picker
            if ('sucursal_r_page_sucursales_r_settings' === $hook) {
                wp_enqueue_style('wp-color-picker');
                wp_enqueue_script('wp-color-picker');
                
                // Script para inicializar color pickers
                wp_enqueue_script(
                    'sucursales-maps-r-admin-js',
                    plugin_dir_url(__FILE__) . 'assets/js/admin-script.js',
                    array('jquery', 'wp-color-picker'),
                    '2.1.0',
                    true
                );
            }
        }
    }
    
    // Shortcode para mostrar el mapa
    public function shortcode_map($atts) {
        // Atributos por defecto
        $atts = shortcode_atts(
            array(
                'altura' => '500px',
                'ancho' => '100%',
                'zoom' => '10',
                'id' => 0,
                'estado' => '',
                'lista' => 'si', // Mostrar/ocultar lista
                'streetview' => 'no', // Nuevo atributo para habilitar/deshabilitar Street View
            ),
            $atts,
            'sucursales_mapa'
        );
        
        // Verificar API Key
        $api_key = get_option('sucursales_maps_r_api_key', '');
        if (empty($api_key)) {
            return '<div class="sucursales-error">Error: No se ha configurado la API Key de Google Maps. Por favor, configúrela en Sucursales > Configuración.</div>';
        }
        
        // Consulta para obtener sucursales
        $args = array(
            'post_type' => 'sucursal_r',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );
        
        // Filtrar por ID si se especifica
        if (!empty($atts['id']) && $atts['id'] > 0) {
            $args['p'] = intval($atts['id']);
        }
        
        // Filtrar por estado si se especifica
        if (!empty($atts['estado'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'estado_r',
                    'field'    => 'slug',
                    'terms'    => $atts['estado'],
                ),
            );
        }
        
        $sucursales = new WP_Query($args);
        
        // Verificar si hay sucursales
        if (!$sucursales->have_posts()) {
            return '<div class="sucursales-error">No hay sucursales disponibles. Por favor, añada sucursales desde el panel de administración.</div>';
        }
        
        // Preparar datos para JavaScript
        $markers = array();
        $estados = array();
        
        if ($sucursales->have_posts()) {
            while ($sucursales->have_posts()) {
                $sucursales->the_post();
                $post_id = get_the_ID();
                
                $lat = get_post_meta($post_id, '_latitude', true);
                $lng = get_post_meta($post_id, '_longitude', true);
                
                if (!empty($lat) && !empty($lng)) {
                    // Obtener términos de estado para esta sucursal
                    $terminos_estado = get_the_terms($post_id, 'estado_r');
                    $estado_slug = '';
                    $estado_nombre = '';
                    
                    if ($terminos_estado && !is_wp_error($terminos_estado)) {
                        $estado_slug = $terminos_estado[0]->slug;
                        $estado_nombre = $terminos_estado[0]->name;
                        
                        // Agregar a la lista de estados si no existe
                        if (!empty($estado_slug)) {
                            $estado_existe = false;
                            foreach ($estados as $estado) {
                                if ($estado['slug'] === $estado_slug) {
                                    $estado_existe = true;
                                    break;
                                }
                            }
                            
                            if (!$estado_existe) {
                                $estados[] = array(
                                    'slug' => $estado_slug,
                                    'nombre' => $estado_nombre
                                );
                            }
                        }
                    }
                    
                    $marker = array(
                        'id' => $post_id,
                        'title' => get_the_title(),
                        'lat' => floatval($lat),
                        'lng' => floatval($lng),
                        'content' => $this->get_info_window_content($post_id),
                        'estado' => $estado_slug,
                        'address' => get_post_meta($post_id, '_address', true),
                        'phone' => get_post_meta($post_id, '_phone', true),
                        'schedule' => get_post_meta($post_id, '_schedule', true),
                    );
                    
                    $markers[] = $marker;
                }
            }
            wp_reset_postdata();
        }
        
        // Verificar si hay marcadores
        if (empty($markers)) {
            return '<div class="sucursales-error">No hay sucursales con coordenadas válidas. Por favor, añada coordenadas a sus sucursales.</div>';
        }
        
        // Generar ID único para el mapa
        $map_id = 'sucursales-map-' . wp_rand(1000, 9999);
        
        // Localizar el script
        wp_localize_script('sucursales-maps-r-script', 'sucursalesMapData', array(
            'markers' => $markers,
            'zoom' => intval($atts['zoom']),
            'mapId' => $map_id,
            'center' => !empty($markers) ? array('lat' => $markers[0]['lat'], 'lng' => $markers[0]['lng']) : array('lat' => 19.4326, 'lng' => -99.1332),
            'estados' => $estados,
            'mostrarLista' => ($atts['lista'] === 'si'),
            'streetViewControl' => ($atts['streetview'] === 'si') // Pasar configuración de Street View
        ));
        
        // Preparar HTML
        $output = '<div class="sucursales-container" id="sucursales-container-' . esc_attr($map_id) . '">';
        $output .= '<div id="' . esc_attr($map_id) . '" class="sucursales-map" style="height:' . esc_attr($atts['altura']) . '; width:' . esc_attr($atts['ancho']) . ';"></div>';
        $output .= '</div>';
        
        return $output;
    }
    
    // Generar contenido para info window
    private function get_info_window_content($post_id) {
        $title = get_the_title($post_id);
        $address = get_post_meta($post_id, '_address', true);
        $phone = get_post_meta($post_id, '_phone', true);
        $schedule = get_post_meta($post_id, '_schedule', true);
        $thumbnail = get_the_post_thumbnail($post_id, 'thumbnail');
        $lat = get_post_meta($post_id, '_latitude', true);
        $lng = get_post_meta($post_id, '_longitude', true);
        
        $content = '<div class="info-window">';
        
        if ($thumbnail) {
            $content .= '<div class="info-window-image">' . $thumbnail . '</div>';
        }
        
        $content .= '<h4>' . esc_html($title) . '</h4>';
        
        if (!empty($address)) {
            $content .= '<p><strong>Dirección:</strong> ' . esc_html($address) . '</p>';
        }
        
        if (!empty($phone)) {
            $content .= '<p><strong>Teléfono:</strong> ' . esc_html($phone) . '</p>';
        }
        
        if (!empty($schedule)) {
            $content .= '<p><strong>Horario:</strong> ' . esc_html($schedule) . '</p>';
        }
        
        // Botón "Cómo llegar"
        if (!empty($lat) && !empty($lng)) {
            $content .= '<a href="#" class="directions-link" data-lat="' . esc_attr($lat) . '" data-lng="' . esc_attr($lng) . '" data-id="' . esc_attr($post_id) . '">Cómo llegar</a>';
        }
        
        $content .= '</div>';
        
        return $content;
    }
    
    // Agregar menú de administración
    public function admin_menu() {
        add_submenu_page(
            'edit.php?post_type=sucursal_r',
            'Configuración',
            'Configuración',
            'manage_options',
            'sucursales_r_settings',
            array($this, 'settings_page')
        );
    }
    
    // Página de configuración
    public function settings_page() {
        // Guardar configuración
        if (isset($_POST['sucursales_maps_r_save_settings']) && check_admin_referer('sucursales_maps_r_settings_nonce')) {
            // Guardar API Key
            update_option('sucursales_maps_r_api_key', sanitize_text_field($_POST['api_key']));
            
            // Guardar colores
            update_option('sucursales_maps_r_header_color', sanitize_hex_color($_POST['header_color']));
            update_option('sucursales_maps_r_primary_button_color', sanitize_hex_color($_POST['primary_button_color']));
            update_option('sucursales_maps_r_secondary_button_color', sanitize_hex_color($_POST['secondary_button_color']));
            
            echo '<div class="notice notice-success is-dismissible"><p>Configuración guardada correctamente.</p></div>';
        }
        
        // Obtener valores guardados
        $api_key = get_option('sucursales_maps_r_api_key', '');
        $header_color = get_option('sucursales_maps_r_header_color', '#0073aa');
        $primary_button_color = get_option('sucursales_maps_r_primary_button_color', '#0073aa');
        $secondary_button_color = get_option('sucursales_maps_r_secondary_button_color', '#28a745');
        
        // Renderizar formulario
        ?>
        <div class="wrap">
            <h1>Configuración de Sucursales en Google Maps</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('sucursales_maps_r_settings_nonce'); ?>
                
                <h2 class="nav-tab-wrapper">
                    <a href="#tab-general" class="nav-tab nav-tab-active">General</a>
                    <a href="#tab-colores" class="nav-tab">Personalización de Colores</a>
                    <a href="#tab-shortcode" class="nav-tab">Uso del Shortcode</a>
                </h2>
                
                <div id="tab-general" class="tab-content active">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="api_key">API Key de Google Maps</label></th>
                            <td>
                                <input type="text" id="api_key" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
                                <p class="description">Ingresa tu API Key de Google Maps. <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">¿Cómo obtener una API Key?</a></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="tab-colores" class="tab-content">
                    <p>Personaliza los colores de la interfaz de sucursales.</p>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="header_color">Color de Cabecera</label></th>
                            <td>
                                <input type="text" id="header_color" name="header_color" value="<?php echo esc_attr($header_color); ?>" class="color-picker" data-default-color="#0073aa">
                                <p class="description">Color del encabezado "Nuestras Sucursales".</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="primary_button_color">Color de Botón Principal</label></th>
                            <td>
                                <input type="text" id="primary_button_color" name="primary_button_color" value="<?php echo esc_attr($primary_button_color); ?>" class="color-picker" data-default-color="#0073aa">
                                <p class="description">Color del botón "Ver en mapa".</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="secondary_button_color">Color de Botón Secundario</label></th>
                            <td>
                                <input type="text" id="secondary_button_color" name="secondary_button_color" value="<?php echo esc_attr($secondary_button_color); ?>" class="color-picker" data-default-color="#28a745">
                                <p class="description">Color del botón "Cómo llegar".</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="tab-shortcode" class="tab-content">
                    <div class="card" style="max-width: 100%; padding: 20px; margin-top: 20px; background-color: #f8f9fa;">
                        <h2 style="margin-top: 0;">Uso del shortcode</h2>
                        <p>Para mostrar el mapa de sucursales en cualquier página o entrada, utiliza el siguiente shortcode:</p>
                        <code>[sucursales_mapa]</code>
                        
                        <h3>Opciones disponibles:</h3>
                        <ul>
                            <li><code>altura</code> - Altura del mapa (default: 500px)</li>
                            <li><code>ancho</code> - Ancho del mapa (default: 100%)</li>
                            <li><code>zoom</code> - Nivel de zoom (default: 10)</li>
                            <li><code>id</code> - ID de una sucursal específica (default: 0, muestra todas)</li>
                            <li><code>estado</code> - Slug del estado para filtrar (default: vacío, muestra todos)</li>
                            <li><code>lista</code> - Mostrar listado de sucursales (valores: si, no; default: si)</li>
                            <li><code>streetview</code> - Habilitar Street View (valores: si, no; default: no)</li>
                        </ul>
                        
                        <h3>Ejemplos:</h3>
                        <ul>
                            <li><code>[sucursales_mapa altura="600px" ancho="80%" zoom="12"]</code></li>
                            <li><code>[sucursales_mapa id="42"]</code></li>
                            <li><code>[sucursales_mapa estado="jalisco"]</code></li>
                            <li><code>[sucursales_mapa lista="no"]</code> - Solo muestra el mapa sin listado</li>
                            <li><code>[sucursales_mapa streetview="si"]</code> - Habilita Street View en el mapa</li>
                        </ul>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="sucursales_maps_r_save_settings" class="button button-primary" value="Guardar cambios">
                </p>
            </form>
        </div>
        <?php
    }
    
    // Método para activación del plugin
    public static function activate() {
        // Crear opciones para configuración
        add_option('sucursales_maps_r_api_key', '');
        add_option('sucursales_maps_r_header_color', '#0073aa');
        add_option('sucursales_maps_r_primary_button_color', '#0073aa');
        add_option('sucursales_maps_r_secondary_button_color', '#28a745');
        
        // Crear directorios de assets si no existen
        self::create_asset_directories();
        
        // Limpiar las reglas de reescritura
        flush_rewrite_rules();
    }
    
    // Método para desactivación del plugin
    public static function deactivate() {
        // Limpiar las reglas de reescritura
        flush_rewrite_rules();
    }
    
    // Método para crear archivos CSS y JS necesarios
    private static function create_asset_directories() {
        // Definir directorios necesarios
        $plugin_dir = plugin_dir_path(__FILE__);
        $assets_dir = $plugin_dir . 'assets/';
        $css_dir = $assets_dir . 'css/';
        $js_dir = $assets_dir . 'js/';
        
        // Crear directorios si no existen
        if (!file_exists($assets_dir)) {
            mkdir($assets_dir, 0755);
        }
        
        if (!file_exists($css_dir)) {
            mkdir($css_dir, 0755);
        }
        
        if (!file_exists($js_dir)) {
            mkdir($js_dir, 0755);
        }
        
        // Crear archivo CSS si no existe
        if (!file_exists($css_dir . 'sucursales-maps-r.css')) {
            self::create_default_css_file($css_dir . 'sucursales-maps-r.css');
        }
        
        // Crear archivo CSS de admin si no existe
        if (!file_exists($css_dir . 'admin-styles.css')) {
            self::create_default_admin_css_file($css_dir . 'admin-styles.css');
        }
        
        // Crear archivo JS si no existe
        if (!file_exists($js_dir . 'sucursales-maps-r.js')) {
            self::create_default_js_file($js_dir . 'sucursales-maps-r.js');
        }
        
        // Crear archivo JS de admin si no existe
        if (!file_exists($js_dir . 'admin-script.js')) {
            self::create_default_admin_js_file($js_dir . 'admin-script.js');
        }
    }
    
    // Crear archivo CSS por defecto
    private static function create_default_css_file($file_path) {
        $css_content = file_get_contents(plugin_dir_path(__FILE__) . 'assets/css/sucursales-maps-r.css');
        if (empty($css_content)) {
            $css_content = "/**
 * Estilos para Sucursales en Google Maps (Reingeniería)
 * Version: 2.1.0
 */

/* Contenedor principal */
.sucursales-container {
    margin-bottom: 30px;
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    gap: 20px;
    width: 100%;
}

/* Mapa */
.sucursales-map {
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    min-height: 500px;
    flex: 1;
    min-width: 300px;
}

/* Mensaje de error */
.sucursales-error {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
    color: #721c24;
    background-color: #f8d7da;
    width: 100%;
}

/* Listado de sucursales */
.sucursales-lista {
    flex: 0 0 30%;
    min-width: 250px;
    max-width: 350px;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    background-color: #fff;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.sucursales-lista-header {
    padding: 15px 20px;
    background-color: #0073aa;
    color: white;
    font-size: 16px;
    font-weight: 600;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.sucursales-filtro {
    padding: 15px;
    border-bottom: 1px solid #eee;
    background-color: #f9f9f9;
}

.sucursales-filtro select {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: white;
    font-size: 14px;
}

.sucursales-items {
    overflow-y: auto;
    max-height: 410px; /* Ajustar según la altura del mapa */
    padding: 0;
    margin: 0;
    list-style: none;
}

.sucursal-item {
    padding: 15px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background-color 0.2s;
}

.sucursal-item:hover {
    background-color: #f5f8fa;
}

.sucursal-item.active {
    background-color: #edf7fd;
    border-left: 3px solid #0073aa;
}

.sucursal-item h4 {
    margin: 0 0 8px 0;
    font-size: 15px;
    color: #333;
}

.sucursal-item p {
    margin: 5px 0;
    font-size: 13px;
    color: #666;
    line-height: 1.4;
}

.sucursal-item-acciones {
    margin-top: 10px;
    display: flex;
    gap: 5px;
}

.sucursal-boton {
    padding: 6px 12px;
    font-size: 12px;
    color: white;
    background-color: #0073aa;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: background-color 0.2s;
}

.sucursal-boton:hover {
    background-color: #005177;
    color: white;
    text-decoration: none;
}

.sucursal-boton-direcciones {
    background-color: #28a745;
}

.sucursal-boton-direcciones:hover {
    background-color: #218838;
}

/* Ventana de información */
.info-window {
    max-width: 280px;
    padding: 10px;
}

.info-window h4 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 16px;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 8px;
}

.info-window p {
    margin: 6px 0;
    font-size: 14px;
    line-height: 1.4;
    color: #4a5568;
}

.info-window strong {
    color: #2d3748;
    font-weight: 600;
}

.info-window-image {
    float: right;
    margin: 0 0 10px 10px;
    max-width: 80px;
    max-height: 80px;
    border-radius: 4px;
    overflow: hidden;
}

.info-window-image img {
    max-width: 100%;
    height: auto;
    display: block;
}

.info-link, .directions-link {
    display: inline-block;
    margin-top: 10px;
    padding: 6px 12px;
    background-color: #0073aa;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 13px;
    transition: background-color 0.2s;
}

.directions-link {
    background-color: #28a745;
}

.info-link:hover {
    background-color: #005177;
    color: white;
    text-decoration: none;
}

.directions-link:hover {
    background-color: #218838;
    color: white;
    text-decoration: none;
}

/* Limpiar flotantes */
.info-window:after {
    content: \"\";
    display: table;
    clear: both;
}

/* Estilos responsive */
@media (max-width: 768px) {
    .sucursales-container {
        flex-direction: column;
    }
    
    .sucursales-lista {
        max-width: 100%;
        flex: 1 0 100%;
    }
    
    .sucursales-map {
        min-height: 350px;
    }
    
    .sucursales-filtro {
        padding: 10px;
    }
    
    .sucursales-items {
        max-height: 300px;
    }
}

@media (max-width: 480px) {
    .sucursales-map {
        min-height: 300px;
    }
    
    .sucursal-item-acciones {
        flex-direction: column;
        gap: 5px;
    }
    
    .sucursal-boton {
        width: 100%;
        text-align: center;
    }
}";
        }
        
        file_put_contents($file_path, $css_content);
    }
    
    // Crear archivo CSS de admin por defecto
    private static function create_default_admin_css_file($file_path) {
        $css_content = "/**
 * Estilos de administración para Sucursales en Google Maps (Reingeniería)
 * Version: 2.1.0
 */

/* Estilos básicos para formularios */
.location-box {
    background-color: #fff;
    padding: 15px;
    border-radius: 5px;
}

.location-row {
    margin-bottom: 15px;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.location-column {
    flex: 1;
    min-width: 200px;
}

.location-field {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: #f9f9f9;
}

.location-field:focus {
    border-color: #0073aa;
    box-shadow: 0 0 5px rgba(0, 115, 170, 0.25);
    outline: none;
}

.location-help {
    margin-top: 10px;
    padding: 12px;
    background: #f8f9fa;
    border-left: 4px solid #0073aa;
    color: #444;
    font-size: 13px;
}

/* Tarjetas informativas */
.card {
    padding: 20px;
    margin-top: 20px;
    background-color: #f8f9fa;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card h2, .card h3 {
    margin-top: 0;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
    color: #23282d;
}

.card ul {
    padding-left: 20px;
}

.card li {
    margin-bottom: 5px;
}

/* Opciones de página de configuración */
.form-table th {
    width: 200px;
}

/* Mensajes de notificación */
.sucursales-notice {
    padding: 12px;
    margin: 15px 0;
    border-radius: 4px;
}

.sucursales-notice-success {
    background-color: #dff0d8;
    border: 1px solid #d6e9c6;
    color: #3c763d;
}

.sucursales-notice-error {
    background-color: #f2dede;
    border: 1px solid #ebccd1;
    color: #a94442;
}

/* Estilo para el código en instrucciones */
code {
    background: #f4f4f4;
    padding: 2px 5px;
    border-radius: 3px;
    font-size: 13px;
    color: #333;
    font-family: Consolas, Monaco, monospace;
}

/* Pestañas de configuración */
.nav-tab-wrapper {
    margin-bottom: 20px;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Previsualización de colores */
.color-preview {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 3px;
    margin-left: 10px;
    vertical-align: middle;
    border: 1px solid #ddd;
}

.color-preview-header {
    background-color: #0073aa;
}

.color-preview-primary {
    background-color: #0073aa;
}

.color-preview-secondary {
    background-color: #28a745;
}";
        
        file_put_contents($file_path, $css_content);
    }
    
    // Crear archivo JS por defecto
    private static function create_default_js_file($file_path) {
        $js_content = "/**
 * Script para Sucursales en Google Maps (Reingeniería)
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
    
    // Guardar datos de sucursales para usar en el listado
    sucursalesData = sucursalesMapData.markers;
    
    // Verificar si debemos mostrar el listado
    if (sucursalesMapData.mostrarLista !== false) {
        // Renderizar el listado de sucursales primero
        createSucursalesLayout();
    }
    
    // Obtener el elemento del mapa (ahora dentro del contenedor)
    var mapElement = document.getElementById(sucursalesMapData.mapId);
    if (!mapElement) {
        console.error('No se encontró el elemento del mapa con ID: ' + sucursalesMapData.mapId);
        return;
    }
    
    // Crear el mapa (ahora con opción streetViewControl)
    sucursalesMap = new google.maps.Map(mapElement, {
        center: { lat: sucursalesMapData.center.lat, lng: sucursalesMapData.center.lng },
        zoom: sucursalesMapData.zoom,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        mapTypeControl: true,
        zoomControl: true,
        scaleControl: true,
        streetViewControl: sucursalesMapData.hasOwnProperty('streetViewControl') ? sucursalesMapData.streetViewControl : false,
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
    
    // Crear filtro de estados si hay más de un estado
    if (sucursalesMapData.estados && sucursalesMapData.estados.length > 1) {
        createEstadosFilter(sucursalesMapData.estados);
    }
    
    // Evento cuando se cierra la ventana de información
    google.maps.event.addListener(sucursalesInfoWindow, 'closeclick', function() {
        resetActiveState();
    });
    
    // Agregar eventos para el botón \"Cómo llegar\" en la ventana de información
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

// Nueva función para crear el layout completo
function createSucursalesLayout() {
    // Obtener el contenedor original
    var originalContainer = document.getElementById('sucursales-container-' + sucursalesMapData.mapId);
    if (!originalContainer) {
        console.error('No se encontró el contenedor de sucursales');
        return;
    }
    
    // Asegurarse de que el contenedor tenga la clase correcta para el layout
    originalContainer.className = 'sucursales-container';
    originalContainer.style.display = 'flex';
    originalContainer.style.flexDirection = 'row';
    originalContainer.style.gap = '20px';
    
    // Crear el contenedor del listado y agregarlo al contenedor principal
    var listaContainer = document.createElement('div');
    listaContainer.className = 'sucursales-lista';
    listaContainer.style.flex = '0 0 30%'; // 30% del ancho
    listaContainer.style.minWidth = '250px';
    listaContainer.style.maxWidth = '350px';
    
    // Crear encabezado
    var listaHeader = document.createElement('div');
    listaHeader.className = 'sucursales-lista-header';
    listaHeader.textContent = 'Nuestras Sucursales';
    listaContainer.appendChild(listaHeader);
    
    // Contenedor para el filtro
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
            html += '<p><i class=\"dashicons dashicons-location\"></i> ' + sucursal.address + '</p>';
        }
        
        if (sucursal.phone) {
            html += '<p><i class=\"dashicons dashicons-phone\"></i> ' + sucursal.phone + '</p>';
        }
        
        if (sucursal.schedule) {
            html += '<p><i class=\"dashicons dashicons-clock\"></i> ' + sucursal.schedule + '</p>';
        }
        
        // Botones de acción
        html += '<div class=\"sucursal-item-acciones\">';
        html += '<a href=\"#\" class=\"sucursal-boton sucursal-boton-ver\" data-id=\"' + sucursal.id + '\">Ver en mapa</a>';
        html += '<a href=\"#\" class=\"sucursal-boton sucursal-boton-direcciones\" data-id=\"' + sucursal.id + '\" data-lat=\"' + sucursal.lat + '\" data-lng=\"' + sucursal.lng + '\">Cómo llegar</a>';
        html += '</div>';
        
        item.innerHTML = html;
        lista.appendChild(item);
    }
    
    listaContainer.appendChild(lista);
    
    // Crear contenedor para el mapa
    var mapContainer = document.createElement('div');
    mapContainer.id = sucursalesMapData.mapId;
    mapContainer.className = 'sucursales-map';
    mapContainer.style.flex = '1'; // Toma el espacio restante
    mapContainer.style.minHeight = '500px';
    
    // Vaciar el contenedor original
    while (originalContainer.firstChild) {
        originalContainer.removeChild(originalContainer.firstChild);
    }
    
    // Agregar los nuevos elementos al contenedor original en el orden correcto
    originalContainer.appendChild(listaContainer);
    originalContainer.appendChild(mapContainer);
    
    // Añadir eventos a los botones
    agregarEventosBotones();
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

// Función para agregar eventos a los botones
function agregarEventosBotones() {
    // Botones \"Ver en mapa\"
    var botonesVer = document.querySelectorAll('.sucursal-boton-ver');
    for (var i = 0; i < botonesVer.length; i++) {
        botonesVer[i].addEventListener('click', function(e) {
            e.preventDefault();
            var sucursalId = parseInt(this.getAttribute('data-id'));
            mostrarSucursalEnMapa(sucursalId);
        });
    }
    
    // Botones \"Cómo llegar\"
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
});";
        
        file_put_contents($file_path, $js_content);
    }
    
    // Crear archivo JS de admin por defecto
    private static function create_default_admin_js_file($file_path) {
        $js_content = "/**
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
});";
        
        file_put_contents($file_path, $js_content);
    }
}

// Inicializar el plugin
$sucursales_maps_r = new Sucursales_Maps_R();

// Hooks de activación y desactivación
register_activation_hook(__FILE__, array('Sucursales_Maps_R', 'activate'));
register_deactivation_hook(__FILE__, array('Sucursales_Maps_R', 'deactivate'));