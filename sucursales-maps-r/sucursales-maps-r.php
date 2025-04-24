<?php
/**
 * Plugin Name: Sucursales en Google Maps Reingeniería
 * Plugin URI: https://geckode.com.mx
 * Description: Plugin para mostrar sucursales de empresas en Google Maps (Versión reingeniería mejorada)
 * Version: 2.0.0
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
                '2.0.0'
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
                    '2.0.0',
                    true
                );
            }
        }
    }
    
    // Cargar estilos en admin
    public function admin_enqueue_scripts($hook) {
        // Solo en páginas de edición de sucursales
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        
        global $post;
        
        if ($post && 'sucursal_r' === $post->post_type) {
            wp_enqueue_style(
                'sucursales-maps-r-admin',
                plugin_dir_url(__FILE__) . 'assets/css/admin-styles.css',
                array(),
                '2.0.0'
            );
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
                'lista' => 'si', // Nuevo atributo para mostrar/ocultar lista
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
        if ($atts['id'] > 0) {
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
            'mostrarLista' => ($atts['lista'] === 'si')
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
            update_option('sucursales_maps_r_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success is-dismissible"><p>Configuración guardada correctamente.</p></div>';
        }
        
        // Obtener API Key guardada
        $api_key = get_option('sucursales_maps_r_api_key', '');
        
        // Renderizar formulario
        ?>
        <div class="wrap">
            <h1>Configuración de Sucursales en Google Maps</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('sucursales_maps_r_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="api_key">API Key de Google Maps</label></th>
                        <td>
                            <input type="text" id="api_key" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
                            <p class="description">Ingresa tu API Key de Google Maps. <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">¿Cómo obtener una API Key?</a></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="sucursales_maps_r_save_settings" class="button button-primary" value="Guardar cambios">
                </p>
            </form>
            
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
                </ul>
                
                <h3>Ejemplos:</h3>
                <ul>
                    <li><code>[sucursales_mapa altura="600px" ancho="80%" zoom="12"]</code></li>
                    <li><code>[sucursales_mapa id="42"]</code></li>
                    <li><code>[sucursales_mapa estado="jalisco"]</code></li>
                    <li><code>[sucursales_mapa lista="no"]</code> - Solo muestra el mapa sin listado</li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    // Método para activación del plugin
    public static function activate() {
        // Crear opción para la API key
        add_option('sucursales_maps_r_api_key', '');
        
        // Crear directorios de assets si no existen
        self::create_asset_directories();
        
        // Limpiar las reglas de reescritura
        flush_rewrite_rules();
    }
    
    // Crear directorios de assets
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
    }
    
    // Método para desactivación del plugin
    public static function deactivate() {
        // Limpiar las reglas de reescritura
        flush_rewrite_rules();
    }
}

// Inicializar el plugin
$sucursales_maps_r = new Sucursales_Maps_R();

// Hooks de activación y desactivación
register_activation_hook(__FILE__, array('Sucursales_Maps_R', 'activate'));
register_deactivation_hook(__FILE__, array('Sucursales_Maps_R', 'deactivate'));