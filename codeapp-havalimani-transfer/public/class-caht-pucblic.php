<?php

/**
 * Codeapp Havalimanı Transfer - Public Sınıfı
 */

if (!defined('ABSPATH')) {
    exit;
}

class CAHT_Public {

    /**
     * Sınıfı başlat
     */
    public function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        // template_redirect GERI EKLENDI - Eski caht_action parametrelerini yeni sayfalara yonlendiriyor
        add_action('template_redirect', array($this, 'handle_legacy_redirects'));
    }

    /**
     * Eski caht_action parametrelerini yeni sayfalara yonlendir
     */
    public function handle_legacy_redirects() {
        if (is_admin()) return;
        
        // Eski caht_action=sonuc parametresi varsa, yeni sonuc sayfasina yonlendir
        if (isset($_GET['caht_action']) && $_GET['caht_action'] === 'sonuc') {
            $sonuc_page_id = get_option('caht_sonuc_page_id', 0);
            if ($sonuc_page_id) {
                $sonuc_url = get_permalink($sonuc_page_id);
                if ($sonuc_url) {
                    // Mevcut GET parametrelerini koru
                    wp_redirect(add_query_arg($_GET, $sonuc_url));
                    exit;
                }
            }
        }
        
        // Eski caht_action=odeme parametresi varsa, yeni odeme sayfasina yonlendir
        if (isset($_GET['caht_action']) && $_GET['caht_action'] === 'odeme') {
            $odeme_page_id = get_option('caht_odeme_page_id', 0);
            if ($odeme_page_id) {
                $odeme_url = get_permalink($odeme_page_id);
                if ($odeme_url) {
                    wp_redirect(add_query_arg($_GET, $odeme_url));
                    exit;
                }
            }
        }
    }

    /**
     * Script ve stil dosyalarını yükle
     */
    public function enqueue_scripts() {
        if (!$this->is_caht_page()) {
            return;
        }

        // Flatpickr
        wp_enqueue_style('caht-flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), null);
        wp_enqueue_script('caht-flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), null, true);

        $locale = get_locale();
        if (strpos($locale, 'tr') !== false) {
            wp_enqueue_script('caht-flatpickr-tr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js', array('caht-flatpickr'), null, true);
        }

        // Font Awesome
        wp_enqueue_style('caht-font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), null);

        // jQuery
        wp_enqueue_script('jquery');

        // Google Maps API
        $api_key = get_option('caht_google_maps_api_key', '');
        if (!empty($api_key)) {
            wp_enqueue_script(
                'caht-google-maps',
                'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($api_key) . '&libraries=places',
                array('jquery'),
                null,
                true
            );
        } else {
            wp_add_inline_script('jquery', 'console.warn("CAHT: Google Maps API key eksik. Autocomplete calismayacak.");');
        }

        // Localize
        wp_localize_script('jquery', 'caht_public', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('caht_nonce'),
            'plugin_url' => CAHT_PLUGIN_URL,
            'home_url' => home_url(),
            'has_google_key' => !empty($api_key),
        ));
    }

    /**
     * CAHT sayfası mı kontrol et
     */
    private function is_caht_page() {
        if (is_admin()) {
            return false;
        }

        global $post;

        if (is_singular() && $post) {
            $shortcodes = array('caht_transfer_form', 'caht_transfer', 'caht_transfer_sonuc', 'caht_transfer_odeme');
            foreach ($shortcodes as $sc) {
                if (has_shortcode($post->post_content, $sc)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Döviz kurlarını al - STATIC (Shortcode'tan erişim için)
     */
    public static function get_exchange_rates_static() {
        $cached = get_transient('caht_exchange_rates');
        if ($cached) {
            return $cached;
        }

        $api_key = 'f00a9b3cf290fbee0ce61b15';
        $response = wp_remote_get("https://v6.exchangerate-api.com/v6/{$api_key}/latest/TRY");

        if (is_wp_error($response)) {
            return array('usd' => 34.50, 'eur' => 37.04);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!$body || $body['result'] !== 'success') {
            return array('usd' => 34.50, 'eur' => 37.04);
        }

        $rates = array(
            'usd' => 1 / $body['conversion_rates']['USD'],
            'eur' => 1 / $body['conversion_rates']['EUR']
        );

        set_transient('caht_exchange_rates', $rates, HOUR_IN_SECONDS);
        return $rates;
    }

    /**
     * 3. köprü kontrolü - STATIC (Shortcode'tan erişim için)
     */
    public static function check_third_bridge_static($nereden_lat, $nereden_lng, $nereye_lat, $nereye_lng) {
        $sabiha_gokcen = array('lat_min' => 40.8800, 'lat_max' => 40.9100, 'lng_min' => 29.3000, 'lng_max' => 29.3300);
        $istanbul_airport = array('lat_min' => 41.2600, 'lat_max' => 41.2800, 'lng_min' => 28.7300, 'lng_max' => 28.7500);
        $avrupa_yakasi = array('lat_min' => 40.7000, 'lat_max' => 42.0000, 'lng_min' => 26.0000, 'lng_max' => 29.0500);
        $anadolu_yakasi = array('lat_min' => 36.0000, 'lat_max' => 42.0000, 'lng_min' => 29.0000, 'lng_max' => 45.0000);

        $in_sabiha = ($nereden_lat >= $sabiha_gokcen['lat_min'] && $nereden_lat <= $sabiha_gokcen['lat_max'] && $nereden_lng >= $sabiha_gokcen['lng_min'] && $nereden_lng <= $sabiha_gokcen['lng_max']);
        $in_istanbul = ($nereden_lat >= $istanbul_airport['lat_min'] && $nereden_lat <= $istanbul_airport['lat_max'] && $nereden_lng >= $istanbul_airport['lng_min'] && $nereden_lng <= $istanbul_airport['lng_max']);
        $target_avrupa = ($nereye_lat >= $avrupa_yakasi['lat_min'] && $nereye_lat <= $avrupa_yakasi['lat_max'] && $nereye_lng >= $avrupa_yakasi['lng_min'] && $nereye_lng <= $avrupa_yakasi['lng_max']);
        $target_anadolu = ($nereye_lat >= $anadolu_yakasi['lat_min'] && $nereye_lat <= $anadolu_yakasi['lat_max'] && $nereye_lng >= $anadolu_yakasi['lng_min'] && $nereye_lng <= $anadolu_yakasi['lng_max']);

        return ($in_sabiha && $target_avrupa) || ($in_istanbul && $target_anadolu) || ($target_avrupa && $in_sabiha) || ($target_anadolu && $in_istanbul);
    }
}

/**
 * Ödeme URL'si oluştur - HELPER FONKSİYON
 */
if (!function_exists('caht_odeme_url')) {
    function caht_odeme_url($arac_id, $para_birimi, $secilen_fiyat, $extra_args = array()) {
        $odeme_page_id = get_option('caht_odeme_page_id', 0);
        $base_url = $odeme_page_id ? get_permalink($odeme_page_id) : home_url('/transfer-odeme/');
        
        $args = array(
            'arac_id' => $arac_id,
            'para_birimi' => $para_birimi,
            'secilen_fiyat' => $secilen_fiyat,
        );
        
        if (!empty($extra_args)) {
            $args = array_merge($args, $extra_args);
        }
        
        return add_query_arg($args, $base_url);
    }
}