<?php
/**
 * Plugin Name: cityevents
 * Description: CityEvents – Pubblica agenda eventi culturali del tuo comune
 * Version: 0.1.5
 * Author: Cognita.it
 * License: GPLv2 or later
 * Text Domain: cityevents
 */

if (!defined('ABSPATH')) { exit; }

class CityEvents_Plugin {
    const TD = 'cityevents';

    const VERSION = '0.1.5';


    public static $url;


    public static function init() {
        // Widget & shortcode
        add_action('widgets_init', function () { register_widget('CityEvents_Widget'); });
        add_shortcode('cityevents', [__CLASS__, 'shortcode']);

        self::$url  = plugin_dir_url( __FILE__ );

        wp_register_style(
            'cityevents-frontend',
            self::$url . 'assets/css/cityevents.css',
            [],
            self::VERSION
        );



        // Settings page (facoltativa, utile per default globali)
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);

        // Textdomain
        /*
        add_action('plugins_loaded', function () {
            load_plugin_textdomain('cityevents', false, dirname(plugin_basename(__FILE__)) . '/languages');
        });*/
    }

    /** Shortcode: [cityevents city="city_slug" limit="5" title="Prossimi eventi"] */
    public static function shortcode($atts = [], $content = null) {
        if ( ! wp_style_is( 'cityevents-frontend', 'enqueued' ) ) {
            wp_enqueue_style( 'cityevents-frontend' );
        }

        $atts = shortcode_atts([
            'title'         => '',
            'city'      => get_option('cityevents_default_city', 'roma'),
            'limit'         => get_option('cityevents_default_limit', 5),
            'show_date'     => 1,
            'show_location' => 1,
            'cache_minutes' => get_option('cityevents_default_cache_minutes', 15),
            'date_format'   => get_option('cityevents_default_date_format', get_option('date_format') . ' cityevents-plugin.php' . get_option('time_format')),
        ], $atts, 'cityevents');

        $args = [
            'title'         => sanitize_text_field($atts['title']),
            'city'          => sanitize_text_field($atts['city']),
            'limit'         => intval($atts['limit']),
            'show_date'     => intval($atts['show_date']) ? true : false,
            'show_location' => intval($atts['show_location']) ? true : false,
            'cache_minutes' => max(1, intval($atts['cache_minutes'])),
            'date_format'   => sanitize_text_field($atts['date_format']),
        ];

        return CityEvents_Renderer::render($args, true);
    }

    // === Settings page (opzionale) ===
    public static function add_settings_page() {
        add_options_page(
            __('CityEvents', 'cityevents'),
            __('CityEvents', 'cityevents'),
            'manage_options',
            'cityevents-settings',
            [__CLASS__, 'settings_page_html']
        );
    }

    public static function register_settings() {
        register_setting('cityevents_settings', 'cityevents_default_city', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'roma']);
        register_setting('cityevents_settings', 'cityevents_default_limit', ['type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 10]);
        register_setting('cityevents_settings', 'cityevents_default_cache_minutes', ['type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 15]);
        register_setting('cityevents_settings', 'cityevents_default_date_format', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => get_option('date_format') ]);

        add_settings_section('cityevents_main', __('Impostazioni predefinite', 'cityevents'), function () {
            echo '<p>' . esc_html__('Questi valori sono usati come default per widget e shortcode (possono essere sovrascritti).', 'cityevents') . '</p>';
        }, 'cityevents-settings');

        add_settings_field('cityevents_default_city', __('Citta predefinita', 'cityevents'), function () {
            printf('<input type="text" class="regular-text" name="cityevents_default_city" value="%s" placeholder="roma"/>',
                esc_attr(get_option('cityevents_default_city', '')));
        }, 'cityevents-settings', 'cityevents_main');

        add_settings_field('cityevents_default_limit', __('Numero eventi', 'cityevents'), function () {
            printf('<input type="number" min="1" name="cityevents_default_limit" value="%d" />', intval(get_option('cityevents_default_limit', 5)));
        }, 'cityevents-settings', 'cityevents_main');

        add_settings_field('cityevents_default_cache_minutes', __('Cache (minuti)', 'cityevents'), function () {
            printf('<input type="number" min="1" name="cityevents_default_cache_minutes" value="%d" />', intval(get_option('cityevents_default_cache_minutes', 15)));
        }, 'cityevents-settings', 'cityevents_main');

        add_settings_field('cityevents_default_date_format', __('Formato data', 'cityevents'), function () {
            printf('<input type="text" class="regular-text" name="cityevents_default_date_format" value="%s" placeholder="Y-m-d H:i" />',
                esc_attr(get_option('cityevents_default_date_format', get_option('date_format') . ' cityevents-plugin.php' . get_option('time_format'))));
            echo '<p class="description">' . esc_html__('Usa i formati di wp_date(), es. "d/m/Y H:i".', 'cityevents') . '</p>';
        }, 'cityevents-settings', 'cityevents_main');
    }

    public static function settings_page_html() {
        if (!current_user_can('manage_options')) { return; } ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Cityevents options', 'cityevents'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('cityevents_settings');
                do_settings_sections('cityevents-settings');
                submit_button();
                ?>
            </form>
        </div>
    <?php }
}
CityEvents_Plugin::init();

class CityEvents_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'CityEvents_Widget',
            __('Cityevents', 'cityevents'),
            ['description' => __('Display italy cultural events', 'cityevents')]
        );
    }

    public function form($instance) {
        $defaults = [
            'title'         => '',
            'city'      => get_option('cityevents_default_city', 'roma'),
            'limit'         => get_option('cityevents_default_limit', 10),
            'show_date'     => 1,
            'show_location' => 1,
            'cache_minutes' => get_option('cityevents_default_cache_minutes', 15),
            'date_format'   => get_option('cityevents_default_date_format', get_option('date_format') . ' cityevents-plugin.php' . get_option('time_format')),
        ];
        $instance = wp_parse_args((array) $instance, $defaults);

        $fields = [
            'title'         => ['label' => __('Titolo', 'cityevents'), 'type' => 'text'],
            'city'      => ['label' => __('City slug', 'cityevents'), 'type' => 'url'],
            'limit'         => ['label' => __('Numero eventi', 'cityevents'), 'type' => 'number', 'min' => 1],
            'cache_minutes' => ['label' => __('Cache (minuti)', 'cityevents'), 'type' => 'number', 'min' => 1],
            'date_format'   => ['label' => __('Formato data', 'cityevents'), 'type' => 'text'],
        ];
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php echo esc_html($fields['title']['label']); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($instance['title']); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('city')); ?>"><?php echo esc_html($fields['city']['label']); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('city')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('city')); ?>" type="url"
                   value="<?php echo esc_attr($instance['city']); ?>" placeholder="roma">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>"><?php echo esc_html($fields['limit']['label']); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('limit')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('limit')); ?>" type="number" min="1"
                   value="<?php echo esc_attr($instance['limit']); ?>">
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($instance['show_date'], 1); ?>
                   id="<?php echo esc_attr($this->get_field_id('show_date')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('show_date')); ?>" value="1">
            <label for="<?php echo esc_attr($this->get_field_id('show_date')); ?>"><?php esc_html_e('Mostra data', 'cityevents'); ?></label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($instance['show_location'], 1); ?>
                   id="<?php echo esc_attr($this->get_field_id('show_location')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('show_location')); ?>" value="1">
            <label for="<?php echo esc_attr($this->get_field_id('show_location')); ?>"><?php esc_html_e('Mostra luogo', 'cityevents'); ?></label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('cache_minutes')); ?>"><?php echo esc_html($fields['cache_minutes']['label']); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('cache_minutes')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('cache_minutes')); ?>" type="number" min="1"
                   value="<?php echo esc_attr($instance['cache_minutes']); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('date_format')); ?>"><?php echo esc_html($fields['date_format']['label']); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('date_format')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('date_format')); ?>" type="text"
                   value="<?php echo esc_attr($instance['date_format']); ?>" placeholder="d/m/Y H:i">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title']         = sanitize_text_field($new_instance['title'] ?? '');
        $instance['city']      = esc_url_raw($new_instance['city'] ?? '');
        $instance['limit']         = max(1, absint($new_instance['limit'] ?? 5));
        $instance['show_date']     = !empty($new_instance['show_date']) ? 1 : 0;
        $instance['show_location'] = !empty($new_instance['show_location']) ? 1 : 0;
        $instance['cache_minutes'] = max(1, absint($new_instance['cache_minutes'] ?? 15));
        $instance['date_format']   = sanitize_text_field($new_instance['date_format'] ?? (get_option('date_format') . ' cityevents-plugin.php' . get_option('time_format')));
        return $instance;
    }

    public function widget($args, $instance) {
        $params = [
            'title'         => $instance['title'] ?? '',
            'city'      => $instance['city'] ?? 'roma',
            'limit'         => intval($instance['limit'] ?? 10),
            'show_date'     => !empty($instance['show_date']),
            'show_location' => !empty($instance['show_location']),
            'cache_minutes' => intval($instance['cache_minutes'] ?? 15),
            'date_format'   => $instance['date_format'] ?? (get_option('date_format') . ' cityevents-plugin.php' . get_option('time_format')),
        ];
        echo ($args['before_widget']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme-provided HTML wrapper

        if ( ! wp_style_is( 'cityevents-frontend', 'enqueued' ) ) {
            wp_enqueue_style( 'cityevents-frontend' );
        }

        if (!empty($params['title'])) {
            echo $args['before_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme-provided HTML wrapper
            echo esc_html($params['title']);
            echo $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme-provided HTML wrapper
        }
        echo wp_kses_post(CityEvents_Renderer::render($params, true));
        echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme-provided HTML wrapper

    }
}

class CityEvents_Renderer {
    /**
     * Recupera, normalizza e renderizza gli eventi.
     * $return_html: se true ritorna stringa HTML; altrimenti echo.
     */
    public static function render($params, $return_html = false) {
        $defaults = [
            'city'      => 'roma',
            'limit'         => 10,
            'show_date'     => true,
            'show_location' => true,
            'cache_minutes' => 15,
            'date_format'   => get_option('date_format') . ' cityevents-plugin.php' . get_option('time_format'),
        ];
        $p = wp_parse_args($params, $defaults);

        $html = '';

        $events = self::fetch_events($p['city'], $p['cache_minutes']);

        if (is_wp_error($events)) {
            $html .= '<p>' . esc_html__('Errore nel recupero del feed:', 'cityevents') . ' ' . esc_html($events->get_error_message()) . '</p>';
            return self::output($html, $return_html);
        }

        if (empty($events)) {
            $html .= '<p>' . esc_html__('Nessun evento disponibile.', 'cityevents') . '</p>';
            return self::output($html, $return_html);
        }

        /*
        // Ordina per data di inizio se presente
        usort($events, function($a, $b) {
            $ta = isset($a['start_date']) ? strtotime($a['start_date']) : PHP_INT_MAX;
            $tb = isset($b['start_date']) ? strtotime($b['start_date']) : PHP_INT_MAX;
            if ($ta == $tb) { return 0; }
            return ($ta < $tb) ? -1 : 1;
        });*/

        $events = array_slice($events, 0, max(1, intval($p['limit'])));

        $html .= '<ul class="cityevents-events-list" itemscope itemtype="https://schema.org/Event">';
        foreach ($events as $ev) {
            $title     = isset($ev['title']) ? wp_strip_all_tags($ev['title']) : '';
            $url       = isset($ev['url']) ? esc_url($ev['url']) : '';
            $start_raw = $ev['start_date'] ?? '';
            $location  = isset($ev['location']) ? wp_strip_all_tags($ev['location']) : '';
            $image     = isset($ev['image']) ? esc_url($ev['image']) : '';
            $dt        = $start_raw ? strtotime($start_raw) : false;

            $html .= '<li class="cityevents-event" itemprop="event" itemscope itemtype="https://schema.org/Event">';
            if ($image) {
                $html .= '<div class="cityevents-event-thumb"><img loading="lazy" src="' . $image . '" alt="' . esc_attr($title) . '" itemprop="image"></div>';
            }
            $html .= '<div class="cityevents-event-body">';
            if ($url) {
                $html .= '<a class="cityevents-event-title" href="' . $url . '" target="_blank" rel="noopener" itemprop="url"><span itemprop="name">' . ($title) . '</span></a>';
            } else {
                $html .= '<span class="cityevents-event-title" itemprop="name">' . ($title) . '</span>';
            }

            if ($p['show_date'] && $dt) {
                $html .= '<div class="cityevents-event-date"><time itemprop="startDate" datetime="' . esc_attr(gmdate('c', $dt)) . '">'
                    . esc_html(wp_date($p['date_format'], $dt)) . '</time></div>';
             }


            if ($p['show_location'] && $location) {
                $html .= '<div class="cityevents-event-location" itemprop="location" itemscope itemtype="https://schema.org/Place">'
                    . '<span itemprop="name">' . esc_html($location) . '</span></div>';
            }

            // opzionale: breve descrizione
            if (!empty($ev['description'])) {
                $desc = wp_kses_post(wp_trim_words(wp_strip_all_tags($ev['description']), 30, '…'));
                $html .= '<div class="cityevents-event-desc" itemprop="description">' . $desc . '</div>';
            }

            $html .= '</div></li>';
        }
        $html .= '</ul>';

        return self::output($html, $return_html);
    }

    /** Recupera eventi dal feed con caching. */
    protected static function fetch_events($city_slug, $cache_minutes = 15) {

        $cache_key = 'cityevents_' . md5('feed:' . $city_slug);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $url = 'https://iltaccodibacco.it/'.$city_slug.'/events.json';

        $resp = wp_remote_get($url, [
            'timeout' => 10,
            'headers' => ['Accept' => 'application/json'],
        ]);

        if (is_wp_error($resp)) {
            return $resp;
        }

        $code = wp_remote_retrieve_response_code($resp);
        if ($code < 200 || $code >= 300) {
            /* translators: %d = error http  */
            return new WP_Error('cityevents_http_error', sprintf(__('HTTP %d dal feed', 'cityevents'), $code));
        }

        $body = wp_remote_retrieve_body($resp);
        $json = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('cityevents_json_error', __('JSON non valido', 'cityevents'));
        }

        // Supporta { "events": [ ... ] } o [ ... ]
        $items = [];
        if (is_array($json)) {
            if (isset($json['events']) && is_array($json['events'])) {
                $items = $json['events'];
            } elseif (array_is_list($json)) {
                $items = $json;
            } else {
                // Prova a cercare la prima chiave che sembra un array di eventi
                foreach ($json as $k => $v) {
                    if (is_array($v) && array_is_list($v)) {
                        $items = $v;
                        break;
                    }
                }
            }
        }

        // Normalizza campi minimi: title, start_date, url, location, image, description
        $events = [];
        foreach ($items as $it) {
            if (!is_array($it)) { continue; }
            $ev = [
                'title'       => self::pick($it, ['title','name','nome_evento']),
                'start_date'  => self::pick($it, ['start_date','start','date','datetime','startDate']),
                'end_date'    => self::pick($it, ['end_date','end','endDate']),
                'url'         => self::pick($it, ['url','link','url_evento']),
                'location'    => self::pick($it, ['location','venue','place']),
                'image'       => self::pick($it, ['image','cover','img']),
                'description' => self::pick($it, ['description','des_evento','summary']),
            ];
            // Richiede almeno il titolo
            if (!empty($ev['title'])) {
                $events[] = $ev;
            }
        }

        set_transient($cache_key, $events, $cache_minutes * MINUTE_IN_SECONDS);
        return $events;
    }

    protected static function pick($arr, $keys) {
        foreach ($keys as $k) {
            if (isset($arr[$k]) && $arr[$k] !== '') { return $arr[$k]; }
        }
        return '';
    }

    protected static function output($html, $return_html) {
        if ($return_html) { return $html; }
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render
        return '';
    }
}
