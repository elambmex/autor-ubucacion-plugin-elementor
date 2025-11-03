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

        $this->add_control('foto', [
            'label' => __('Foto del autor (opcional)', 'arp-elementor'),
            'type' => Controls_Manager::MEDIA,
            'default' => ['url' => ''],
        ]);

        $this->add_control('nombre_forzado', [
            'label' => __('Nombre del autor (opcional)', 'arp-elementor'),
            'type' => Controls_Manager::TEXT,
            'placeholder' => __('Ej: María López'),
        ]);

        $this->add_control('ubicacion_forzada', [
            'label' => __('Ubicación (opcional - override)', 'arp-elementor'),
            'type' => Controls_Manager::TEXT,
            'placeholder' => __('Ej: Reportando desde Ciudad de México'),
        ]);

        $this->add_control('fecha_forzada', [
            'label' => __('Texto de fecha (opcional)', 'arp-elementor'),
            'type' => Controls_Manager::TEXT,
            'placeholder' => __('Ej: Publicado el 25 de enero de 2025 a las 12:45'),
        ]);

        // ⚙️ Nueva opción: zona horaria del reporte
        $this->add_control('zona_horaria', [
            'label' => __('Zona horaria del reporte', 'arp-elementor'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'hora_centro' => __('Hora del Centro', 'arp-elementor'),
                'hora_pacifico' => __('Hora del Pacífico', 'arp-elementor'),
                'hora_montana' => __('Hora de la Montaña', 'arp-elementor'),
                'hora_oriente' => __('Hora del Oriente', 'arp-elementor'),
                'hora_occidental' => __('Hora Occidental', 'arp-elementor'),
                'personalizada' => __('Personalizada (usar ubicación)', 'arp-elementor'),
            ],
            'default' => 'hora_centro',
        ]);

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

        // Datos base
        $author_id = $post->post_author;
        $author_name_wp = get_the_author_meta('display_name', $author_id);
        $author_avatar_wp = get_avatar_url($author_id, ['size' => 120]);
        $ubicacion_meta = function_exists('arp_get_post_ubicacion') ? arp_get_post_ubicacion($post->ID) : '';

        // Overrides de Elementor
        $foto = !empty($settings['foto']['url']) ? $settings['foto']['url'] : $author_avatar_wp;
        $author_name = !empty($settings['nombre_forzado']) ? $settings['nombre_forzado'] : $author_name_wp;
        $ubicacion = !empty($settings['ubicacion_forzada']) ? $settings['ubicacion_forzada'] : $ubicacion_meta;
        $fecha_override = !empty($settings['fecha_forzada']) ? $settings['fecha_forzada'] : '';
        $zona_horaria = !empty($settings['zona_horaria']) ? $settings['zona_horaria'] : 'hora_centro';

        // --- INICIO DE LA LÓGICA DE FECHA MODIFICADA ---
        if ($fecha_override) {
            $texto_fecha = $fecha_override;
        } else {
            setlocale(LC_TIME, get_locale());
            $pub_ts = get_the_time('U', $post->ID);
            $mod_ts = get_the_modified_time('U', $post->ID);
            $current_ts = current_time('U');

            // Ajuste de zona horaria manual
            $ajuste_horas = 0;
            switch ($zona_horaria) {
                case 'hora_pacifico': $ajuste_horas = -2; break;
                case 'hora_montana': $ajuste_horas = -1; break;
                case 'hora_centro': $ajuste_horas = 0; break;
                case 'hora_oriente': $ajuste_horas = +1; break;
                case 'hora_occidental': $ajuste_horas = -3; break;
                default: $ajuste_horas = 0; break;
            }

            $pub_ts_ajustada = strtotime("{$ajuste_horas} hours", $pub_ts);
            $mod_ts_ajustada = strtotime("{$ajuste_horas} hours", $mod_ts);

            if ($mod_ts - $pub_ts > 60) {
                $diff_seconds = $current_ts - $mod_ts;
                $hora_modificada = date('G', $mod_ts_ajustada);
                $prefijo_hora = ($hora_modificada == 1) ? 'a la' : 'a las';

                if ($diff_seconds < HOUR_IN_SECONDS) {
                    $texto_fecha = sprintf(__('Actualizado hace %s'), human_time_diff($mod_ts, $current_ts));
                } elseif (date('Ymd', $mod_ts) === date('Ymd', $current_ts)) {
                    $texto_fecha = "Actualizado hoy {$prefijo_hora} " . date('H:i', $mod_ts_ajustada);
                } elseif (date('Ymd', $mod_ts) === date('Ymd', $current_ts - DAY_IN_SECONDS)) {
                    $texto_fecha = "Actualizado ayer {$prefijo_hora} " . date('H:i', $mod_ts_ajustada);
                } else {
                    $fecha_local = strftime('%e de %B', $mod_ts_ajustada);
                    $texto_fecha = "Actualizado el {$fecha_local} {$prefijo_hora} " . date('H:i', $mod_ts_ajustada);
                }
            } else {
                $hora_publicada = date('G', $pub_ts_ajustada);
                $prefijo_pub = ($hora_publicada == 1) ? 'a la' : 'a las';
                $fecha_local_pub = strftime('%e de %B de %Y', $pub_ts_ajustada);
                $texto_fecha = "Publicado el {$fecha_local_pub} {$prefijo_pub} " . date('H:i', $pub_ts_ajustada);
            }
        }
        // --- FIN DE LA LÓGICA DE FECHA MODIFICADA ---

        // --- INICIO DE LA ZONA HORARIA PERSONALIZADA ---
        switch ($zona_horaria) {
            case 'hora_pacifico': $etiqueta_hora = ' — Hora del Pacífico'; break;
            case 'hora_montana': $etiqueta_hora = ' — Hora de la Montaña'; break;
            case 'hora_oriente': $etiqueta_hora = ' — Hora del Oriente'; break;
            case 'hora_occidental': $etiqueta_hora = ' — Hora Occidental'; break;
            case 'personalizada':
                $etiqueta_hora = $ubicacion ? " — {$ubicacion}" : ' — Hora local';
                break;
            default:
                $etiqueta_hora = ' — Hora del Centro';
                break;
        }
        $texto_fecha .= $etiqueta_hora;
        // --- FIN DE LA ZONA HORARIA PERSONALIZADA ---

        $foto_size = intval($settings['foto_size']) ?: 55;
        $foto_size_style = esc_attr($foto_size);
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
