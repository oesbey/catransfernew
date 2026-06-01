<?php
/**
 * Shortcodes sınıfı
 *
 * @package Codeapp_Havalimani_Transfer
 */

class CAHT_Shortcodes {

    public function init() {
        add_shortcode('caht_transfer_form', array($this, 'render_transfer_form'));
        add_shortcode('caht_transfer', array($this, 'render_transfer_form'));
        add_shortcode('caht_transfer_sonuc', array($this, 'render_sonuc'));
        add_shortcode('caht_transfer_odeme', array($this, 'render_odeme'));
        add_shortcode('caht_transfer_detay', array($this, 'render_rezervasyon_detay'));
    }

    /**
     * Transfer formu shortcode
     */
    public function render_transfer_form($atts) {
        static $form_count = 0;
        $form_count++;

        if ($form_count > 1) {
            return '<div class="caht-error">' . esc_html__('Bu sayfada sadece bir transfer formu olabilir.', 'codeapp-havalimani-transfer') . '</div>';
        }

        ob_start();
        $view_file = CAHT_PLUGIN_DIR . 'public/views/transfer-form.php';
        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo '<div class="caht-error">' . esc_html__('Transfer formu görünümü bulunamadı: ', 'codeapp-havalimani-transfer') . esc_html($view_file) . '</div>';
        }
        return ob_get_clean();
    }

    /**
     * Sonuç sayfası shortcode
     */
    public function render_sonuc($atts) {
        // HEM GET HEM POST PARAMETRELERİNİ OKU
        $nereden = isset($_GET['nereden']) ? sanitize_text_field($_GET['nereden']) : (isset($_POST['nereden']) ? sanitize_text_field($_POST['nereden']) : '');
        $nereye = isset($_GET['nereye']) ? sanitize_text_field($_GET['nereye']) : (isset($_POST['nereye']) ? sanitize_text_field($_POST['nereye']) : '');
        $mesafe = isset($_GET['mesafe']) ? floatval($_GET['mesafe']) : (isset($_POST['mesafe']) ? floatval($_POST['mesafe']) : 0);
        $kisiler = isset($_GET['kisi_sayisi']) ? intval($_GET['kisi_sayisi']) : (isset($_POST['kisi_sayisi']) ? intval($_POST['kisi_sayisi']) : 1);
        $gidis_tarih = isset($_GET['gidis_tarih']) ? sanitize_text_field($_GET['gidis_tarih']) : (isset($_POST['gidis_tarih']) ? sanitize_text_field($_POST['gidis_tarih']) : '');
        $donus_tarih = isset($_GET['donus_tarih']) ? sanitize_text_field($_GET['donus_tarih']) : (isset($_POST['donus_tarih']) ? sanitize_text_field($_POST['donus_tarih']) : '');
        $gidis_donus = (isset($_GET['gidis_donus']) && $_GET['gidis_donus'] === '1') || (isset($_POST['gidis_donus']) && $_POST['gidis_donus'] === '1') ? 1 : 0;
        $para_birimi = isset($_GET['para_birimi']) ? sanitize_text_field($_GET['para_birimi']) : (isset($_POST['para_birimi']) ? sanitize_text_field($_POST['para_birimi']) : '');
        $nereden_lat = isset($_GET['nereden_lat']) ? floatval($_GET['nereden_lat']) : (isset($_POST['nereden_lat']) ? floatval($_POST['nereden_lat']) : 0);
        $nereden_lng = isset($_GET['nereden_lng']) ? floatval($_GET['nereden_lng']) : (isset($_POST['nereden_lng']) ? floatval($_POST['nereden_lng']) : 0);
        $nereye_lat = isset($_GET['nereye_lat']) ? floatval($_GET['nereye_lat']) : (isset($_POST['nereye_lat']) ? floatval($_POST['nereye_lat']) : 0);
        $nereye_lng = isset($_GET['nereye_lng']) ? floatval($_GET['nereye_lng']) : (isset($_POST['nereye_lng']) ? floatval($_POST['nereye_lng']) : 0);

        if (empty($nereden) || empty($nereye) || $mesafe <= 0 || empty($gidis_tarih)) {
            return '<div class="caht-sonuc-bos"><h3>' . esc_html__('Geçerli bir sorgu yapmalısınız.', 'codeapp-havalimani-transfer') . '</h3><p>' . esc_html__('Lütfen transfer formunu doldurun.', 'codeapp-havalimani-transfer') . '</p></div>';
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';
        $araclar = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$prefix}araclar WHERE kapasite >= %d AND durum = 1 ORDER BY sira ASC, kapasite ASC",
            $kisiler
        ));

        if (empty($araclar)) {
            return '<div class="caht-sonuc-bos"><h3>' . esc_html__('Uygun araç bulunamadı.', 'codeapp-havalimani-transfer') . '</h3></div>';
        }

        $kurlar = CAHT_Public::get_exchange_rates_static();
        $usd_kur = $kurlar['usd'];
        $eur_kur = $kurlar['eur'];

        // WooCommerce varsayılan kurunu al
        $wc_currency = 'TL';
        if (class_exists('WooCommerce')) {
            $wc_currency_raw = get_woocommerce_currency();
            $currency_map = array('TRY' => 'TL', 'USD' => 'USD', 'EUR' => 'EUR');
            $wc_currency = isset($currency_map[$wc_currency_raw]) ? $currency_map[$wc_currency_raw] : 'TL';
        }

        // Eğer para_birimi boşsa WooCommerce varsayılanını kullan
        if (empty($para_birimi)) {
            $para_birimi = $wc_currency;
        }

        $third_bridge_required = CAHT_Public::check_third_bridge_static($nereden_lat, $nereden_lng, $nereye_lat, $nereye_lng);

        // Ödeme sayfası URL'si
        $odeme_page_id = get_option('caht_odeme_page_id', 0);
        $odeme_base_url = $odeme_page_id ? get_permalink($odeme_page_id) : home_url('/transfer-odeme/');

        ob_start();
        $view_file = CAHT_PLUGIN_DIR . 'public/views/sonuc.php';
        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo '<div class="caht-error">' . esc_html__('Sonuç sayfası görünümü bulunamadı.', 'codeapp-havalimani-transfer') . '</div>';
        }
        return ob_get_clean();
    }

    /**
     * Ödeme sayfası shortcode
     */
    public function render_odeme($atts) {
        if (!session_id()) {
            session_start();
        }

        $arac_id = isset($_GET['arac_id']) ? intval($_GET['arac_id']) : 0;
        if ($arac_id <= 0) {
            return '<div class="caht-error"><h3>' . esc_html__('Geçersiz araç seçimi.', 'codeapp-havalimani-transfer') . '</h3></div>';
        }

        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';

        $arac = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$prefix}araclar WHERE id = %d",
            $arac_id
        ));

        if (!$arac) {
            return '<div class="caht-error"><h3>' . esc_html__('Araç bulunamadı.', 'codeapp-havalimani-transfer') . '</h3></div>';
        }

        $nereden = isset($_GET['nereden']) ? sanitize_text_field(urldecode($_GET['nereden'])) : '';
        $nereye = isset($_GET['nereye']) ? sanitize_text_field(urldecode($_GET['nereye'])) : '';
        $mesafe = isset($_GET['mesafe']) ? floatval($_GET['mesafe']) : 0;
        $kisiler = isset($_GET['kisi_sayisi']) ? intval($_GET['kisi_sayisi']) : 1;
        $gidis_tarih = isset($_GET['gidis_tarih']) ? sanitize_text_field(urldecode($_GET['gidis_tarih'])) : '';
        $donus_tarih = isset($_GET['donus_tarih']) ? sanitize_text_field(urldecode($_GET['donus_tarih'])) : '';
        $gidis_donus = isset($_GET['gidis_donus']) && $_GET['gidis_donus'] === '1' ? 1 : 0;
        $para_birimi = isset($_GET['para_birimi']) ? sanitize_text_field($_GET['para_birimi']) : 'TL';
        $secilen_fiyat = isset($_GET['secilen_fiyat']) ? floatval($_GET['secilen_fiyat']) : 0;
        $third_bridge_required = isset($_GET['thirdBridgeRequired']) && $_GET['thirdBridgeRequired'] === '1' ? 1 : 0;

        $ek_hizmetler = json_decode(get_option('caht_ek_hizmetler', '{}'), true);
        $cocuk_koltugu_fiyat = $ek_hizmetler['cocuk_koltugu'] ?? 500;
        $karsilama_hizmeti_fiyat = $ek_hizmetler['karsilama_hizmeti'] ?? 300;
        $third_bridge_fiyat = $ek_hizmetler['third_bridge'] ?? 700;

        $kurlar = CAHT_Public::get_exchange_rates_static();
        if ($para_birimi === 'USD') {
            $cocuk_koltugu_fiyat /= $kurlar['usd'];
            $karsilama_hizmeti_fiyat /= $kurlar['usd'];
            $third_bridge_fiyat /= $kurlar['usd'];
            $sembol = '$';
        } elseif ($para_birimi === 'EUR') {
            $cocuk_koltugu_fiyat /= $kurlar['eur'];
            $karsilama_hizmeti_fiyat /= $kurlar['eur'];
            $third_bridge_fiyat /= $kurlar['eur'];
            $sembol = '€';
        } else {
            $sembol = '₺';
        }

        $hesaplanan_sure_saat = $mesafe / 80;
        $hesaplanan_sure_dakika = round($hesaplanan_sure_saat * 60);
        $sure_yazi = ($hesaplanan_sure_saat >= 1)
            ? floor($hesaplanan_sure_saat) . ' saat ' . ($hesaplanan_sure_dakika % 60) . ' dakika'
            : $hesaplanan_sure_dakika . ' dakika';

        // WooCommerce ödeme metodlarını al
        $wc_payment_methods = array();
        if (class_exists('WooCommerce')) {
            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
            foreach ($available_gateways as $gateway) {
                if ($gateway->enabled === 'yes') {
                    $wc_payment_methods[$gateway->id] = array(
                        'title' => $gateway->get_title(),
                        'description' => $gateway->get_description(),
                        'icon' => method_exists($gateway, 'get_icon') ? $gateway->get_icon() : '',
                    );
                }
            }
        }

        ob_start();
        $view_file = CAHT_PLUGIN_DIR . 'public/views/odeme.php';
        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo '<div class="caht-error">' . esc_html__('Ödeme sayfası görünümü bulunamadı.', 'codeapp-havalimani-transfer') . '</div>';
        }
        return ob_get_clean();
    }

    /**
     * Rezervasyon detay sayfası shortcode
     */
    public function render_rezervasyon_detay($atts) {
        ob_start();
        $view_file = CAHT_PLUGIN_DIR . 'public/views/rezervasyon-detay.php';
        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo '<div class="caht-error">' . esc_html__('Rezervasyon detay sayfası görünümü bulunamadı.', 'codeapp-havalimani-transfer') . '</div>';
        }
        return ob_get_clean();
    }
}