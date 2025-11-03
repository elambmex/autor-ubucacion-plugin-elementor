<?php
/*
Plugin Name: Información de Autor y Ubicación para Elementor
Description: Widget de Elementor para mostrar autor, foto, cargo, ubicación (prefijo + lugar) y fecha en reportajes. Incluye metabox, quick-edit y compatibilidad con Elementor.
Version: 2.9
Author: Marcos para El Ambientalista Post
*/

if (!defined('ABSPATH')) exit;

// -------------------------
// Encolar estilos frontend
// -------------------------
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('arp-elementor-style', plugin_dir_url(__FILE__) . 'style.css');
});

// -------------------------
// Registrar el widget en Elementor
// -------------------------
add_action('elementor/widgets/register', function($widgets_manager) {
    require_once(__DIR__ . '/widget-autor.php');
    $widgets_manager->register(new \ARP\Widgets\Autor_Ubicacion_Widget());
});

// -------------------------
// Añadir categoría de Elementor
// -------------------------
add_action('elementor/elements/categories_registered', function($elements_manager) {
    $elements_manager->add_category(
        'elambientalista',
        [
            'title' => esc_html__('El Ambientalista', 'arp-elementor'),
            'icon'  => 'fa fa-leaf',
        ]
    );
});

// -------------------------
// Metabox: Solo UN campo: prefijo + lugar
// -------------------------
add_action('add_meta_boxes', function() {
    add_meta_box(
        'arp_autor_ubicacion',
        'Agregar ubicación del autor',
        'arp_autor_ubicacion_metabox',
        ['post'],
        'side',
        'default'
    );
});

function arp_autor_ubicacion_metabox($post) {
    // Prefijo guardado y lugar
    $prefijo = get_post_meta($post->ID, '_arp_ubicacion_prefijo', true);
    $lugar = get_post_meta($post->ID, '_arp_ubicacion_lugar', true);

    // Opciones sugeridas
    $opciones = [
        '' => '-- Ninguno --',
        'Reportando' => 'Reportando',
        'Reportó' => 'Reportó',
        'Reportaron' => 'Reportaron',
        'En vivo desde' => 'En vivo desde',
        'Desde' => 'Desde'
    ];
    ?>
    <p>
        <label for="arp_ubicacion_prefijo"><strong>Tipo</strong></label>
        <select name="arp_ubicacion_prefijo" id="arp_ubicacion_prefijo" class="widefat">
            <?php foreach ($opciones as $k => $v): ?>
                <option value="<?php echo esc_attr($k); ?>" <?php selected($prefijo, $k); ?>><?php echo esc_html($v); ?></option>
            <?php endforeach; ?>
        </select>
    </p>

    <p>
        <label for="arp_ubicacion_lugar"><strong>Ciudad / País</strong></label>
        <input type="text" name="arp_ubicacion_lugar" id="arp_ubicacion_lugar" class="widefat"
               placeholder="Ej: Ciudad de México, México" value="<?php echo esc_attr($lugar); ?>">
    </p>

    <p style="font-size:12px;color:#666;margin-top:6px;">Si dejas ambos en blanco, no se mostrará la ubicación en el widget.</p>
    <?php
}

// -------------------------
// Guardar metadatos
// -------------------------
add_action('save_post', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Prefijo y lugar
    if (isset($_POST['arp_ubicacion_prefijo'])) {
        update_post_meta($post_id, '_arp_ubicacion_prefijo', sanitize_text_field($_POST['arp_ubicacion_prefijo']));
    }
    if (isset($_POST['arp_ubicacion_lugar'])) {
        update_post_meta($post_id, '_arp_ubicacion_lugar', sanitize_text_field($_POST['arp_ubicacion_lugar']));
    }
});

// -------------------------
// Helper: obtener ubicación completa (prefijo + lugar)
// -------------------------
function arp_get_post_ubicacion($post_id = null) {
    if (!$post_id) $post_id = get_the_ID();
    $prefijo = get_post_meta($post_id, '_arp_ubicacion_prefijo', true);
    $lugar = get_post_meta($post_id, '_arp_ubicacion_lugar', true);
    if (empty($prefijo) && empty($lugar)) return '';
    $parts = [];
    if (!empty($prefijo)) $parts[] = $prefijo;
    if (!empty($lugar)) $parts[] = $lugar;
    return implode(' ', $parts); // ej: "Reportando Ciudad de México"
}

// -------------------------
// Quick Edit: agregar columna y quick edit para ubicación
// -------------------------
add_filter('manage_posts_columns', function($columns) {
    $columns['arp_ubicacion'] = __('Ubicación', 'arp-elementor');
    return $columns;
});

add_action('manage_posts_custom_column', function($column, $post_id) {
    if ($column === 'arp_ubicacion') {
        $prefijo = get_post_meta($post_id, '_arp_ubicacion_prefijo', true);
        $lugar = get_post_meta($post_id, '_arp_ubicacion_lugar', true);
        if ($prefijo || $lugar) {
            echo esc_html(trim($prefijo . ' ' . $lugar));
        } else {
            echo '—';
        }
    }
}, 10, 2);

// Añadir contenido al quick edit (caja invisible, estilo admin)
add_action('quick_edit_custom_box', function($column_name, $post_type) {
    if ($column_name !== 'arp_ubicacion') return;
    ?>
    <fieldset class="inline-edit-col-left">
        <div class="inline-edit-col">
            <label>
                <span class="title">Tipo</span>
                <select name="arp_ubicacion_prefijo_qe" class="arp-quick-prefijo">
                    <option value="">-- Ninguno --</option>
                    <option value="Reportando">Reportando</option>
                    <option value="Reportó">Reportó</option>
                    <option value="Reportaron">Reportaron</option>
                    <option value="En vivo desde">En vivo desde</option>
                    <option value="Desde">Desde</option>
                </select>
            </label>
            <label>
                <span class="title">Ciudad / País</span>
                <input type="text" name="arp_ubicacion_lugar_qe" class="arp-quick-lugar" value="">
            </label>
        </div>
    </fieldset>
    <?php
}, 10, 2);

// Guardar Quick Edit (hook en save_post ya captura cambios de quick edit si hay datos)
// Para interceptar desde la lista necesitamos usar 'save_post' y valores que vienen por POST con names del quick edit.
add_action('save_post', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['arp_ubicacion_prefijo_qe'])) {
        update_post_meta($post_id, '_arp_ubicacion_prefijo', sanitize_text_field($_POST['arp_ubicacion_prefijo_qe']));
    }
    if (isset($_POST['arp_ubicacion_lugar_qe'])) {
        update_post_meta($post_id, '_arp_ubicacion_lugar', sanitize_text_field($_POST['arp_ubicacion_lugar_qe']));
    }
}, 20, 1);

// -------------------------
// Encolar scripts admin para Quick Edit y media uploader (si quieres editar/avatar desde admin)
// -------------------------
add_action('admin_enqueue_scripts', function($hook) {
    // Solo en listado de posts y editor
    wp_enqueue_script('arp-admin-js', plugin_dir_url(__FILE__) . 'arp-admin.js', ['jquery'], false, true);
    // Localizar texto y nonce si se requiere
    wp_localize_script('arp-admin-js', 'arpAdmin', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
});
