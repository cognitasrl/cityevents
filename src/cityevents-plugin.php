<?php
/**
 * Plugin Name: CityEvents JSON Widget
 * Description: Recupera eventi da un URL JSON e li mostra in un widget o tramite shortcode.
 * Version: 1.0.0
 * Author: ChatGPT
 * License: GPLv2 or later
 * Text Domain: events-json-widget
 */

if (!defined('ABSPATH')) { exit; }

class EJW_Plugin {
    const TD = 'cityevents-widget';

    public static function init() {
        // Widget & shortcode
        add_action('widgets_init', function () { register_widget('EJW_Widget'); });
        add_shortcode('events_widget', [__CLASS__, 'shortcode']);

        // Settings page (facoltativa, utile per default globali)
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);

        // Textdomain
        add_action('plugins_loaded', function () {
            load_plugin_textdomain(self::TD, false, dirname(plugin_basename(__FILE__)) . '/languages');
        });
    }

    /** Shortcode: [events_widget feed_url="https://..." limit="5" title="Prossimi eventi"] */
    public static function shortcode($atts = [], $content = null) {
        $atts = shortcode_atts([
            'title'         => '',
            'feed_url'      => get_option('ejw_default_feed_url', ''),
            'limit'         => get_option('ejw_default_limit', 5),
            'show_date'     => 1,
            'show_location' => 1,
            'cache_minutes' => get_option('ejw_default_cache_minutes', 15),
            'date_format'   => get_option('ejw_default_date_format', get_option('date_format') . ' ' . get_option('time_format')),
        ], $atts, 'events_widget');

        $args = [
            'title'         => sanitize_text_field($atts['title']),
            'feed_url'      => esc_url_raw($atts['feed_url']),
            'limit'         => intval($atts['limit']),
            'show_date'     => intval($atts['show_date']) ? true : false,
            'show_location' => intval($atts['show_location']) ? true : false,
            'cache_minutes' => max(1, intval($atts['cache_minutes'])),
            'date_format'   => sanitize_text_field($atts['date_format']),
        ];

        return EJW_Renderer::render($args, true);
    }

    // === Settings page (opzionale) ===
    public static function add_settings_page() {
        add_options_page(
            __('Events JSON Widget', self::TD),
            __('Events JSON Widget', self::TD),
            'manage_options',
            'ejw-settings',
            [__CLASS__, 'settings_page_html']
        );
    }

    public static function register_settings() {
        register_setting('ejw_settings', 'ejw_default_feed_url', ['type' => 'string', 'sanitize_callback' => 'esc_url_raw', 'default' => '']);
        register_setting('ejw_settings', 'ejw_default_limit', ['type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 5]);
        register_setting('ejw_settings', 'ejw_default_cache_minutes', ['type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 15]);
        register_setting('ejw_settings', 'ejw_default_date_format', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => get_option('date_format') . ' ' . get_option('time_format')]);

        add_settings_section('ejw_main', __('Impostazioni predefinite', self::TD), function () {
            echo '<p>' . esc_html__('Questi valori sono usati come default per widget e shortcode (possono essere sovrascritti).', self::TD) . '</p>';
        }, 'ejw-settings');

        add_settings_field('ejw_default_feed_url', __('URL JSON predefinito', self::TD), function () {
            printf('<input type="url" class="regular-text" name="ejw_default_feed_url" value="%s" placeholder="https://example.com/events.json"/>',
                esc_attr(get_option('ejw_default_feed_url', '')));
        }, 'ejw-settings', 'ejw_main');

        add_settings_field('ejw_default_limit', __('Numero eventi', self::TD), function () {
            printf('<input type="number" min="1" name="ejw_default_limit" value="%d" />', intval(get_option('ejw_default_limit', 5)));
        }, 'ejw-settings', 'ejw_main');

        add_settings_field('ejw_default_cache_minutes', __('Cache (minuti)', self::TD), function () {
            printf('<input type="number" min="1" name="ejw_default_cache_minutes" value="%d" />', intval(get_option('ejw_default_cache_minutes', 15)));
        }, 'ejw-settings', 'ejw_main');

        add_settings_field('ejw_default_date_format', __('Formato data', self::TD), function () {
            printf('<input type="text" class="regular-text" name="ejw_default_date_format" value="%s" placeholder="Y-m-d H:i" />',
                esc_attr(get_option('ejw_default_date_format', get_option('date_format') . ' ' . get_option('time_format'))));
            echo '<p class="description">' . esc_html__('Usa i formati di wp_date(), es. "d/m/Y H:i".', self::TD) . '</p>';
        }, 'ejw-settings', 'ejw_main');
    }

    public static function settings_page_html() {
        if (!current_user_can('manage_options')) { return; } ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Events JSON Widget', self::TD); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ejw_settings');
                do_settings_sections('ejw-settings');
                submit_button();
                ?>
            </form>
            <hr>
            <h2><?php echo esc_html__('Formato JSON atteso', self::TD); ?></h2>
            <p><?php echo esc_html__('Il feed deve restituire un array JSON di eventi. Campi consigliati:', self::TD); ?></p>
            <pre>{
  "events": [
    {
      "title": "Concerto di prova",
      "start_date": "2025-09-15T21:00:00+02:00",
      "end_date": "2025-09-15T23:00:00+02:00",
      "url": "https://esempio.it/evento",
      "location": "Teatro Comunale, Bari",
      "image": "https://esempio.it/img.jpg",
      "description": "Testo descrizione"
    }
  ]
}</pre>
            <p><?php echo esc_html__('Sono accettati anche JSON come semplice array (senza chiave "events").', self::TD); ?></p>
        </div>
    <?php }
}
EJW_Plugin::init();

class EJW_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'ejw_widget',
            __('Events JSON Widget', EJW_Plugin::TD),
            ['description' => __('Mostra eventi da un feed JSON', EJW_Plugin::TD)]
        );
    }

    public function form($instance) {
        $defaults = [
            'title'         => '',
            'feed_url'      => get_option('ejw_default_feed_url', ''),
            'limit'         => get_option('ejw_default_limit', 5),
            'show_date'     => 1,
            'show_location' => 1,
            'cache_minutes' => get_option('ejw_default_cache_minutes', 15),
            'date_format'   => get_option('ejw_default_date_format', get_option('date_format') . ' ' . get_option('time_format')),
        ];
        $instance = wp_parse_args((array) $instance, $defaults);

        $fields = [
            'title'         => ['label' => __('Titolo', EJW_Plugin::TD), 'type' => 'text'],
            'feed_url'      => ['label' => __('URL JSON', EJW_Plugin::TD), 'type' => 'url'],
            'limit'         => ['label' => __('Numero eventi', EJW_Plugin::TD), 'type' => 'number', 'min' => 1],
            'cache_minutes' => ['label' => __('Cache (minuti)', EJW_Plugin::TD), 'type' => 'number', 'min' => 1],
            'date_format'   => ['label' => __('Formato data', EJW_Plugin::TD), 'type' => 'text'],
        ];
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php echo esc_html($fields['title']['label']); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($instance['title']); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('feed_url')); ?>"><?php echo esc_html($fields['feed_url']['label']); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('feed_url')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('feed_url')); ?>" type="url"
                   value="<?php echo esc_attr($instance['feed_url']); ?>" placeholder="https://example.com/events.json">
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
            <label for="<?php echo esc_attr($this->get_field_id('show_date')); ?>"><?php esc_html_e('Mostra data', EJW_Plugin::TD); ?></label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($instance['show_location'], 1); ?>
                   id="<?php echo esc_attr($this->get_field_id('show_location')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('show_location')); ?>" value="1">
            <label for="<?php echo esc_attr($this->get_field_id('show_location')); ?>"><?php esc_html_e('Mostra luogo', EJW_Plugin::TD); ?></label>
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
        $instance['feed_url']      = esc_url_raw($new_instance['feed_url'] ?? '');
        $instance['limit']         = max(1, absint($new_instance['limit'] ?? 5));
        $instance['show_date']     = !empty($new_instance['show_date']) ? 1 : 0;
        $instance['show_location'] = !empty($new_instance['show_location']) ? 1 : 0;
        $instance['cache_minutes'] = max(1, absint($new_instance['cache_minutes'] ?? 15));
        $instance['date_format']   = sanitize_text_field($new_instance['date_format'] ?? (get_option('date_format') . ' ' . get_option('time_format')));
        return $instance;
    }

    public function widget($args, $instance) {
        $params = [
            'title'         => $instance['title'] ?? '',
            'feed_url'      => $instance['feed_url'] ?? '',
            'limit'         => intval($instance['limit'] ?? 5),
            'show_date'     => !empty($instance['show_date']),
            'show_location' => !empty($instance['show_location']),
            'cache_minutes' => intval($instance['cache_minutes'] ?? 15),
            'date_format'   => $instance['date_format'] ?? (get_option('date_format') . ' ' . get_option('time_format')),
        ];
        echo $args['before_widget'];
        if (!empty($params['title'])) {
            echo $args['before_title'] . esc_html($params['title']) . $args['after_title'];
        }
        echo EJW_Renderer::render($params, true);
        echo $args['after_widget'];
    }
}

class EJW_Renderer {
    /**
     * Recupera, normalizza e renderizza gli eventi.
     * $return_html: se true ritorna stringa HTML; altrimenti echo.
     */
    public static function render($params, $return_html = false) {
        $defaults = [
            'feed_url'      => '',
            'limit'         => 5,
            'show_date'     => true,
            'show_location' => true,
            'cache_minutes' => 15,
            'date_format'   => get_option('date_format') . ' ' . get_option('time_format'),
        ];
        $p = wp_parse_args($params, $defaults);

        $html = '';

        if (empty($p['feed_url'])) {
            $html .= '<p>' . esc_html__('Nessun URL del feed specificato.', EJW_Plugin::TD) . '</p>';
            return self::output($html, $return_html);
        }

        $events = self::fetch_events($p['feed_url'], $p['cache_minutes']);

        if (is_wp_error($events)) {
            $html .= '<p>' . esc_html__('Errore nel recupero del feed:', EJW_Plugin::TD) . ' ' . esc_html($events->get_error_message()) . '</p>';
            return self::output($html, $return_html);
        }

        if (empty($events)) {
            $html .= '<p>' . esc_html__('Nessun evento disponibile.', EJW_Plugin::TD) . '</p>';
            return self::output($html, $return_html);
        }

        // Ordina per data di inizio se presente
        usort($events, function($a, $b) {
            $ta = isset($a['start_date']) ? strtotime($a['start_date']) : PHP_INT_MAX;
            $tb = isset($b['start_date']) ? strtotime($b['start_date']) : PHP_INT_MAX;
            if ($ta == $tb) { return 0; }
            return ($ta < $tb) ? -1 : 1;
        });

        $events = array_slice($events, 0, max(1, intval($p['limit'])));

        $html .= '<ul class="ejw-events-list" itemscope itemtype="https://schema.org/Event">';
        foreach ($events as $ev) {
            $title     = isset($ev['title']) ? wp_strip_all_tags($ev['title']) : '';
            $url       = isset($ev['url']) ? esc_url($ev['url']) : '';
            $start_raw = $ev['start_date'] ?? '';
            $location  = isset($ev['location']) ? wp_strip_all_tags($ev['location']) : '';
            $image     = isset($ev['image']) ? esc_url($ev['image']) : '';
            $dt        = $start_raw ? strtotime($start_raw) : false;

            $html .= '<li class="ejw-event" itemprop="event" itemscope itemtype="https://schema.org/Event">';
            if ($image) {
                $html .= '<div class="ejw-event-thumb"><img loading="lazy" src="' . $image . '" alt="' . esc_attr($title) . '" itemprop="image"></div>';
            }
            $html .= '<div class="ejw-event-body">';
            if ($url) {
                $html .= '<a class="ejw-event-title" href="' . $url . '" target="_blank" rel="noopener" itemprop="url"><span itemprop="name">' . esc_html($title) . '</span></a>';
            } else {
                $html .= '<span class="ejw-event-title" itemprop="name">' . esc_html($title) . '</span>';
            }

            if ($p['show_date'] && $dt) {
                $html .= '<div class="ejw-event-date"><time itemprop="startDate" datetime="' . esc_attr(gmdate('c', $dt)) . '">'
                    . esc_html(wp_date($p['date_format'], $dt)) . '</time></div>';
            }

            if ($p['show_location'] && $location) {
                $html .= '<div class="ejw-event-location" itemprop="location" itemscope itemtype="https://schema.org/Place">'
                    . '<span itemprop="name">' . esc_html($location) . '</span></div>';
            }

            // opzionale: breve descrizione
            if (!empty($ev['description'])) {
                $desc = wp_kses_post(wp_trim_words(wp_strip_all_tags($ev['description']), 30, '…'));
                $html .= '<div class="ejw-event-desc" itemprop="description">' . $desc . '</div>';
            }

            $html .= '</div></li>';
        }
        $html .= '</ul>';

        // Stili base minimi
        $html .= '<style>
            .ejw-events-list{list-style:none;margin:0;padding:0}
            .ejw-event{display:flex;gap:12px;margin:0 0 12px 0}
            .ejw-event-thumb img{width:72px;height:72px;object-fit:cover;border-radius:8px}
            .ejw-event-title{font-weight:600;display:block;margin-bottom:2px;text-decoration:none}
            .ejw-event-date,.ejw-event-location{font-size:0.9em;opacity:0.8}
            .ejw-event-desc{margin-top:4px;font-size:0.95em}
        </style>';

        return self::output($html, $return_html);
    }

    /** Recupera eventi dal feed con caching. */
    protected static function fetch_events($url, $cache_minutes = 15) {
        $cache_key = 'ejw_' . md5('feed:' . $url);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $resp = wp_remote_get($url, [
            'timeout' => 10,
            'headers' => ['Accept' => 'application/json'],
        ]);

        if (is_wp_error($resp)) {
            return $resp;
        }

        $code = wp_remote_retrieve_response_code($resp);
        if ($code < 200 || $code >= 300) {
            return new WP_Error('ejw_http_error', sprintf(__('HTTP %d dal feed', EJW_Plugin::TD), $code));
        }

        $body = wp_remote_retrieve_body($resp);
        $json = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('ejw_json_error', __('JSON non valido', EJW_Plugin::TD));
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
                'title'       => self::pick($it, ['title','name','titolo']),
                'start_date'  => self::pick($it, ['start_date','start','date','datetime','data_inizio']),
                'end_date'    => self::pick($it, ['end_date','end','data_fine']),
                'url'         => self::pick($it, ['url','link','permalink']),
                'location'    => self::pick($it, ['location','luogo','venue','place']),
                'image'       => self::pick($it, ['image','cover','img']),
                'description' => self::pick($it, ['description','descrizione','summary']),
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
        echo $html;
        return '';
    }
}
