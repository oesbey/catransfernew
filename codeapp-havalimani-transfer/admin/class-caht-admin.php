<?php
/**
 * Admin sınıfı - Yönetim paneli işlemleri
 */

if (!defined('ABSPATH')) {
    exit;
}

class CAHT_Admin {

    public function init() {
        add_action('admin_menu', [$this, 'register_menus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_init', [$this, 'handle_post_actions']);
    }

    /**
     * Admin menülerini kaydet
     */
    public function register_menus() {
        // Ana menü
        add_menu_page(
            'Havalimanı Transfer',
            'CA Transfer',
            'manage_options',
            'caht-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-airplane',
            30
        );

        // Alt menüler
        add_submenu_page(
            'caht-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'caht-dashboard',
            [$this, 'render_dashboard']
        );

        add_submenu_page(
            'caht-dashboard',
            'Rezervasyonlar',
            'Rezervasyonlar',
            'manage_options',
            'caht-rezervasyonlar',
            [$this, 'render_rezervasyonlar']
        );

        add_submenu_page(
            'caht-dashboard',
            'Araçlar',
            'Araçlar',
            'manage_options',
            'caht-araclar',
            [$this, 'render_araclar']
        );

        add_submenu_page(
            'caht-dashboard',
            'Bölgeler',
            'Bölgeler',
            'manage_options',
            'caht-bolgeler',
            [$this, 'render_bolgeler']
        );

        add_submenu_page(
            'caht-dashboard',
            'Havalimanları',
            'Havalimanları',
            'manage_options',
            'caht-havalimanlari',
            [$this, 'render_havalimanlari']
        );

        add_submenu_page(
            'caht-dashboard',
            'Sabit Fiyatlar',
            'Sabit Fiyatlar',
            'manage_options',
            'caht-sabit-fiyatlar',
            [$this, 'render_sabit_fiyatlar']
        );

        add_submenu_page(
            'caht-dashboard',
            'Ayarlar',
            'Ayarlar',
            'manage_options',
            'caht-ayarlar',
            [$this, 'render_ayarlar']
        );
    }

    /**
     * CSS/JS dosyalarını yükle
     */
    public function enqueue_assets($hook) {
        // === KONTROL: Sadece eklenti sayfalarında yükle ===
        if (strpos($hook, 'caht-') === false && strpos($hook, 'toplevel_page_caht-') === false) {
            return;
        }

        // Font Awesome
        wp_enqueue_style(
            'caht-font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            null
        );

        // WordPress Media Library (Araç resim ekleme için - her CAHT sayfasında yüklensin)
        wp_enqueue_media();

        // Admin JS
        wp_enqueue_script(
            'caht-admin-js',
            CAHT_PLUGIN_URL . 'admin/js/admin-script.js',
            ['jquery'],
            CAHT_VERSION,
            true
        );

        // Google Maps API (bölge ve havalimanı sayfalarında)
        // Hook kontrolü: caht-bolgeler veya caht-havalimanlari içeriyorsa
        if (strpos($hook, 'caht-bolgeler') !== false || strpos($hook, 'caht-havalimanlari') !== false) {
            $api_key = get_option('caht_google_maps_api_key', '');
            if ($api_key) {
                wp_enqueue_script(
                    'google-maps',
                    'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($api_key) . '&libraries=drawing,places',
                    [],
                    null,
                    true
                );
            }
        }

        wp_localize_script('caht-admin-js', 'caht_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('caht_nonce')
        ]);
    }

    /**
     * POST işlemlerini yönet
     */
    public function handle_post_actions() {
        if (!isset($_POST['caht_action'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'caht_nonce')) {
            wp_die('Güvenlik kontrolü başarısız.');
        }

        $action = sanitize_text_field($_POST['caht_action']);

        switch ($action) {
            case 'arac_kaydet':
                $this->save_arac();
                break;
            case 'bolge_kaydet':
                $this->save_bolge();
                break;
            case 'havalimani_kaydet':
                $this->save_havalimani();
                break;
            case 'sabit_fiyat_kaydet':
                $this->save_sabit_fiyat();
                break;
            case 'ayarlari_kaydet':
                $this->save_settings();
                break;
        }
    }

    // ==================== SAYFA RENDER METODLARI ====================

    public function render_dashboard() {
        include CAHT_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public function render_rezervasyonlar() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';

        // Durum filtresi
        $durum = isset($_GET['durum']) ? sanitize_text_field($_GET['durum']) : 'yeni';
        $valid_durumlar = ['yeni', 'tamamlanmis', 'iptal', 'silinmis'];

        if (!in_array($durum, $valid_durumlar)) {
            $durum = 'yeni';
        }

        // Rezervasyonları çek
        $rezervasyonlar = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, a.ad as arac_ad 
             FROM {$prefix}rezervasyonlar r 
             LEFT JOIN {$prefix}araclar a ON r.arac_id = a.id 
             WHERE r.durum = %s 
             ORDER BY r.olusturma_tarihi DESC",
            $durum
        ));

        include CAHT_PLUGIN_DIR . 'admin/views/rezervasyonlar.php';
    }

    public function render_araclar() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';

        $araclar = $wpdb->get_results("SELECT * FROM {$prefix}araclar ORDER BY sira ASC");

        // Ekleme/Düzenleme modu
        $edit_mode = false;
        $arac = null;

        if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
            $edit_mode = true;
            $arac = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$prefix}araclar WHERE id = %d",
                intval($_GET['edit'])
            ));
        }

        if (isset($_GET['action']) && $_GET['action'] === 'ekle') {
            include CAHT_PLUGIN_DIR . 'admin/views/arac-ekle.php';
        } elseif ($edit_mode && $arac) {
            include CAHT_PLUGIN_DIR . 'admin/views/arac-duzenle.php';
        } else {
            include CAHT_PLUGIN_DIR . 'admin/views/arac-listesi.php';
        }
    }

    public function render_bolgeler() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';

        $bolgeler = $wpdb->get_results("SELECT * FROM {$prefix}bolgeler ORDER BY olusturma_tarihi DESC");

        if (isset($_GET['action']) && $_GET['action'] === 'ekle') {
            include CAHT_PLUGIN_DIR . 'admin/views/bolge-ekle.php';
        } elseif (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
            $bolge = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$prefix}bolgeler WHERE id = %d",
                intval($_GET['edit'])
            ));
            include CAHT_PLUGIN_DIR . 'admin/views/bolge-duzenle.php';
        } else {
            include CAHT_PLUGIN_DIR . 'admin/views/bolge-listesi.php';
        }
    }

    public function render_havalimanlari() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';

        $havalimanlari = $wpdb->get_results("SELECT * FROM {$prefix}havalimanlar ORDER BY olusturma_tarihi DESC");

        if (isset($_GET['action']) && $_GET['action'] === 'ekle') {
            include CAHT_PLUGIN_DIR . 'admin/views/havalimani-ekle.php';
        } elseif (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
            $havalimani = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$prefix}havalimanlar WHERE id = %d",
                intval($_GET['edit'])
            ));
            include CAHT_PLUGIN_DIR . 'admin/views/havalimani-duzenle.php';
        } else {
            include CAHT_PLUGIN_DIR . 'admin/views/havalimani-listesi.php';
        }
    }

    public function render_sabit_fiyatlar() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';

        $fiyatlar = $wpdb->get_results(
            "SELECT fs.*, a.ad as arac_adi, h.ad as havalimani_adi, b.ad as bolge_adi 
             FROM {$prefix}fiyat_sabitleri fs 
             LEFT JOIN {$prefix}araclar a ON fs.arac_id = a.id 
             LEFT JOIN {$prefix}havalimanlar h ON fs.havalimani_id = h.id 
             LEFT JOIN {$prefix}bolgeler b ON fs.bolge_id = b.id 
             ORDER BY fs.olusturma_tarihi DESC"
        );

        if (isset($_GET['action']) && $_GET['action'] === 'ekle') {
            $araclar = $wpdb->get_results("SELECT id, ad FROM {$prefix}araclar");
            $havalimanlari = $wpdb->get_results("SELECT id, ad FROM {$prefix}havalimanlar");
            $bolgeler = $wpdb->get_results("SELECT id, ad FROM {$prefix}bolgeler");
            include CAHT_PLUGIN_DIR . 'admin/views/sabit-fiyat-ekle.php';
        } else {
            include CAHT_PLUGIN_DIR . 'admin/views/sabit-fiyat-listesi.php';
        }
    }

    public function render_ayarlar() {
        include CAHT_PLUGIN_DIR . 'admin/views/ayarlar.php';
    }

    // ==================== KAYDETME METODLARI ====================

      private function save_arac() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';

        // === DEBUG: POST verilerini logla ===
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CAHT SAVE_ARAC - POST verileri: ' . print_r($_POST, true));
        }

        $data = [
            'ad' => sanitize_text_field($_POST['ad']),
            'kapasite' => intval($_POST['kapasite']),
            'bavul_kapasite' => intval($_POST['bavul_kapasite']),
            'km_fiyat' => floatval($_POST['km_fiyat']),
            'acilis_ucreti' => floatval($_POST['acilis_ucreti']),
            'aciklama' => sanitize_textarea_field($_POST['aciklama']),
            'sira' => intval($_POST['sira']),
        ];

        // === RESIM ISLEME - DUZELTILDI ===
        // $_POST yerine $_REQUEST kullan (bazen POST gelmeyebilir)
        $resim_verisi = '';
        
        if (isset($_POST['resim']) && !empty($_POST['resim'])) {
            $resim_verisi = $_POST['resim'];
        } elseif (isset($_REQUEST['resim']) && !empty($_REQUEST['resim'])) {
            $resim_verisi = $_REQUEST['resim'];
        }
        
        if (!empty($resim_verisi)) {
            // JSON valid mi kontrol et
            $resim_decoded = json_decode(stripslashes($resim_verisi), true);
            if (is_array($resim_decoded)) {
                $data['resim'] = stripslashes($resim_verisi);
            } else {
                // Tek URL olabilir, JSON array'e çevir
                $data['resim'] = json_encode([sanitize_text_field($resim_verisi)]);
            }
        } else {
            // Boşsa boş array kaydet
            $data['resim'] = '[]';
        }

        // === DEBUG ===
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CAHT SAVE_ARAC - Kaydedilecek data: ' . print_r($data, true));
        }

        if (!empty($_POST['arac_id'])) {
            $wpdb->update($prefix . 'araclar', $data, ['id' => intval($_POST['arac_id'])]);
        } else {
            $wpdb->insert($prefix . 'araclar', $data);
        }

        wp_redirect(admin_url('admin.php?page=caht-araclar'));
        exit;
    }

    private function save_bolge() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';

        $data = [
            'ad' => sanitize_text_field($_POST['ad']),
            'koordinatlar' => sanitize_text_field($_POST['koordinatlar']),
        ];

        if (!empty($_POST['bolge_id'])) {
            $wpdb->update($prefix . 'bolgeler', $data, ['id' => intval($_POST['bolge_id'])]);
        } else {
            $wpdb->insert($prefix . 'bolgeler', $data);
        }

        wp_redirect(admin_url('admin.php?page=caht-bolgeler'));
        exit;
    }

    private function save_havalimani() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';

        $data = [
            'ad' => sanitize_text_field($_POST['ad']),
            'koordinatlar' => sanitize_text_field($_POST['koordinatlar']),
        ];

        if (!empty($_POST['havalimani_id'])) {
            $wpdb->update($prefix . 'havalimanlar', $data, ['id' => intval($_POST['havalimani_id'])]);
        } else {
            $wpdb->insert($prefix . 'havalimanlar', $data);
        }

        wp_redirect(admin_url('admin.php?page=caht-havalimanlari'));
        exit;
    }

    private function save_sabit_fiyat() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';

        $data = [
            'arac_id' => intval($_POST['arac_id']),
            'sabit_fiyat' => floatval($_POST['sabit_fiyat']),
        ];

        // Havalimanı veya bölge seçimi
        if (!empty($_POST['havalimani_id'])) {
            $data['havalimani_id'] = intval($_POST['havalimani_id']);
            $data['bolge_id'] = null;
        } elseif (!empty($_POST['bolge_id'])) {
            $data['bolge_id'] = intval($_POST['bolge_id']);
            $data['havalimani_id'] = null;
        }

        $wpdb->insert($prefix . 'fiyat_sabitleri', $data);

        wp_redirect(admin_url('admin.php?page=caht-sabit-fiyatlar'));
        exit;
    }

    private function save_settings() {
        update_option('caht_google_maps_api_key', sanitize_text_field($_POST['google_maps_api_key']));
        update_option('caht_whatsapp_token', sanitize_text_field($_POST['whatsapp_token']));
        update_option('caht_whatsapp_phone_id', sanitize_text_field($_POST['whatsapp_phone_id']));
        update_option('caht_whatsapp_template_name', sanitize_text_field($_POST['whatsapp_template_name']));

        $ek_hizmetler = [
            'cocuk_koltugu' => floatval($_POST['cocuk_koltugu_fiyat']),
            'karsilama_hizmeti' => floatval($_POST['karsilama_hizmeti_fiyat']),
            'third_bridge' => floatval($_POST['third_bridge_fiyat']),
        ];
        update_option('caht_ek_hizmetler', json_encode($ek_hizmetler));

        wp_redirect(admin_url('admin.php?page=caht-ayarlar&saved=1'));
        exit;
    }
}