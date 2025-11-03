<?php
namespace ARP\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class Autor_Ubicacion_Widget extends Widget_Base {

    public function get_name() {
        return 'autor_ubicacion_widget';
    }

    public function get_title() {
        return __('Autor y Ubicación', 'arp-elementor');
    }

    public function get_icon() {
        return 'eicon-person';
    }

    public function get_categories() {
        return ['elambientalista'];
    }

    protected function register_controls() {
        $this->start_controls_section('content_section', [
            'label' => __('Contenido (override opcional)', 'arp-elementor'),
            'tab' => Controls_Manager::TAB_CONTENT,
        ]);

        // Foto independiente
        $this->add_control('foto', [
            'label' => __('Foto del autor (opcional)', 'arp-elementor'),
            'type' => Controls_Manager::MEDIA,
            'default' => ['url' => ''],
        ]);

        // Nombre independiente (si quieres forzar otro nombre)
        $this->add_control('nombre_forzado', [
            'label' => __('Nombre del autor (opcional)', 'arp-elementor'),
            'type' => Controls_Manager::TEXT,
            'placeholder' => __('Ej: María López'),
        ]);

        // Ubicación independiente (override)
        $this->add_control('ubicacion_forzada', [
            'label' => __('Ubicación (opcional - override)', 'arp-elementor'),
            'type' => Controls_Manager::TEXT,
            'placeholder' => __('Ej: Reportando desde Ciudad de México'),
        ]);

        // Fecha independiente
        $this->add_control('fecha_forzada', [
            'label' => __('Texto de fecha (opcional)', 'arp-elementor'),
            'type' => Controls_Manager::TEXT,
            'placeholder' => __('Ej: Publicado el 25 de enero de 2025 a las 12:45 am'),
        ]);

        // Ajustes responsivos: imagen tamaño
        $this->add_control('foto_size', [
            'label' => __('Tamaño de la foto (px)', 'arp-elementor'),
            'type' => Controls_Manager::NUMBER,
            'default' => 55,
        ]);

        $this->end_controls_section();
    }

    protected function render() {
        global $post;
        if (!$post) return;

        $settings = $this->get_settings_for_display();

        // Datos base: autor WP
        $author_id = $post->post_author;
        $author_name_wp = get_the_author_meta('display_name', $author_id);
        $author_avatar_wp = get_avatar_url($author_id, ['size' => 120]);

        // Ubicación desde metadatos (prefijo + lugar)
        $ubicacion_meta = function_exists('arp_get_post_ubicacion') ? arp_get_post_ubicacion($post->ID) : '';

        // Valores finales (prioridad: Elementor override > WP post data)
        $foto = !empty($settings['foto']['url']) ? $settings['foto']['url'] : $author_avatar_wp;
        $author_name = !empty($settings['nombre_forzado']) ? $settings['nombre_forzado'] : $author_name_wp;
        $ubicacion = !empty($settings['ubicacion_forzada']) ? $settings['ubicacion_forzada'] : $ubicacion_meta;
        $fecha_override = !empty($settings['fecha_forzada']) ? $settings['fecha_forzada'] : '';

        // --- INICIO DE LA LÓGICA DE FECHA MODIFICADA ---
        if ($fecha_override) {
            $texto_fecha = $fecha_override;
        } else {
            // Usamos timestamps (formato 'U') para cálculos precisos.
            $pub_ts = get_the_time('U', $post->ID);
            $mod_ts = get_the_modified_time('U', $post->ID);
            $current_ts = current_time('U');

            // Se considera 'modificado' si hay más de 60 segundos de diferencia.
            if ($mod_ts - $pub_ts > 60) {
                $diff_seconds = $current_ts - $mod_ts;

                if ($diff_seconds < HOUR_IN_SECONDS) {
                    // 1. Actualización menor a 1 hora
                    $texto_fecha = sprintf(__('Actualizado hace %s'), human_time_diff($mod_ts, $current_ts));
                } elseif (date('Ymd', $mod_ts) === date('Ymd', $current_ts)) {
                    // 2. Actualización hoy (hace más de una hora)
                    $texto_fecha = "Actualizado hoy a las " . get_the_modified_time('g:i a', $post);
                } elseif (date('Ymd', $mod_ts) === date('Ymd', $current_ts - DAY_IN_SECONDS)) {
                    // 3. Actualización ayer
                    $texto_fecha = "Actualizado ayer a las " . get_the_modified_time('g:i a', $post);
                } else {
                    // 4. Actualización hace más de un día (y no fue ayer)
                    $texto_fecha = "Actualizado el " . get_the_modified_date('j \d\e F \a \l\a\s g:i a', $post);
                }
            } else {
                // No hay modificación real, mostrar fecha de publicación.
                $texto_fecha = "Publicado el " . get_the_date('j \d\e F \d\e Y \a \l\a\s g:i a', $post);
            }
        }
        // --- FIN DE LA LÓGICA DE FECHA MODIFICADA ---

        // Tamaño foto responsive
        $foto_size = intval($settings['foto_size']) ?: 55;
        $foto_size_style = esc_attr($foto_size);

        // Si no hay nada para mostrar, salir
        if (empty($author_name)) return;

        ?>

        <div class="arp-autor-widget" style="margin-top:20px;">
            <div class="arp-autor-inner">
                <div class="arp-autor-left">
                    <img src="<?php echo esc_url($foto); ?>"
                         alt="<?php echo esc_attr($author_name); ?>"
                         style="width:<?php echo $foto_size_style; ?>px;height:<?php echo $foto_size_style; ?>px;object-fit:cover;border-radius:50%;border:1px solid #ddd;">
                </div>

                <div class="arp-autor-right">
                    <?php if ($ubicacion): ?>
                        <div class="arp-ubicacion" aria-hidden="true"><?php echo esc_html($ubicacion); ?></div>
                    <?php endif; ?>

                    <div class="arp-por-nombre">Por <strong><?php echo esc_html($author_name); ?></strong></div>

                    <?php if ($texto_fecha): ?>
                        <div class="arp-fecha" aria-live="polite"><?php echo esc_html($texto_fecha); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php
    }
}