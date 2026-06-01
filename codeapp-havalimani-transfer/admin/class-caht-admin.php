<?php
class CAHT_Admin {
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_post_actions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_caht_havalimani_guncelle', array($this, 'ajax_havalimani_guncelle'));
        
        // AJAX hooks
        add_action('wp_ajax_caht_rezervasyon_detay', array($this, 'ajax_rezervasyon_detay'));
        add_action('wp_ajax_caht_rezervasyon_durum_guncelle', array($this, 'ajax_rezervasyon_durum_guncelle'));
        add_action('wp_ajax_caht_toplu_durum_guncelle', array($this, 'ajax_toplu_durum_guncelle'));
        add_action('wp_ajax_caht_rezervasyon_sil_kalici', array($this, 'ajax_rezervasyon_sil_kalici'));
        add_action('wp_ajax_caht_bolge_guncelle', array($this, 'ajax_bolge_guncelle'));
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'caht-') === false) return;
        
        $api_key = get_option('caht_google_maps_api_key', '');
        if (!empty($api_key)) {
            wp_enqueue_script('caht-gmaps-admin', 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($api_key) . '&libraries=drawing,places', array('jquery'), null, true);
        }
        
        wp_enqueue_style('caht-admin', CAHT_PLUGIN_URL . 'admin/css/admin.css', array(), '1.0');
        wp_enqueue_script('caht-admin', CAHT_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), '1.0', true);
        wp_localize_script('caht-admin', 'caht_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('caht_admin_nonce')
        ));
    }
    
    public function add_admin_menu() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';
        $bekleyen = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}rezervasyonlar WHERE durum = 'yeni' AND okundu = 0");
        $badge = $bekleyen > 0 ? ' <span class="awaiting-mod">' . $bekleyen . '</span>' : '';
        
        add_menu_page('Transfer', 'Transfer', 'manage_options', 'caht-dashboard', array($this, 'render_dashboard'), 'dashicons-airplane', 30);
        add_submenu_page('caht-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'caht-dashboard', array($this, 'render_dashboard'));
        add_submenu_page('caht-dashboard', 'Rezervasyonlar', 'Rezervasyonlar' . $badge, 'manage_options', 'caht-rezervasyonlar', array($this, 'render_rezervasyonlar'));
        add_submenu_page('caht-dashboard', 'Araçlar', 'Araçlar', 'manage_options', 'caht-araclar', array($this, 'render_araclar'));
        add_submenu_page('caht-dashboard', 'Bölgeler', 'Bölgeler', 'manage_options', 'caht-bolgeler', array($this, 'render_bolgeler'));
        add_submenu_page('caht-dashboard', 'Havalimanları', 'Havalimanları', 'manage_options', 'caht-havalimanlari', array($this, 'render_havalimanlari'));
        add_submenu_page('caht-dashboard', 'Sabit Fiyatlar', 'Sabit Fiyatlar', 'manage_options', 'caht-sabit-fiyatlar', array($this, 'render_sabit_fiyatlar'));
        add_submenu_page('caht-dashboard', 'Ayarlar', 'Ayarlar', 'manage_options', 'caht-ayarlar', array($this, 'render_ayarlar'));
    }
    
    public function handle_post_actions() {
        if (!isset($_POST['caht_action'])) return;
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'caht_nonce')) {
            wp_die('Güvenlik kontrolü başarısız.');
        }
        
        $action = sanitize_text_field($_POST['caht_action']);
        switch ($action) {
            case 'arac_kaydet': $this->save_arac(); break;
            case 'bolge_kaydet': $this->save_bolge(); break;
            case 'havalimani_kaydet': $this->save_havalimani(); break;
            case 'sabit_fiyat_kaydet': $this->save_sabit_fiyat(); break;
            case 'sabit_fiyat_guncelle': $this->save_sabit_fiyat(); break;
            case 'toplu_fiyat_guncelle': $this->toplu_fiyat_guncelle(); break;
            case 'ayarlari_kaydet': $this->save_settings(); break;
        }
    }

    public function render_dashboard() { include CAHT_PLUGIN_DIR . 'admin/views/dashboard.php'; }
    
    public function render_rezervasyonlar() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';
        $durum = isset($_GET['durum']) ? sanitize_text_field($_GET['durum']) : 'yeni';
        $valid = array('yeni', 'tamamlanmis', 'iptal', 'silinmis');
        if (!in_array($durum, $valid)) $durum = 'yeni';
        
        $rezervasyonlar = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, a.ad as arac_ad FROM {$prefix}rezervasyonlar r 
             LEFT JOIN {$prefix}araclar a ON r.arac_id = a.id 
             WHERE r.durum = %s ORDER BY r.olusturma_tarihi DESC", $durum
        ));
        
        if ($durum === 'yeni') {
            $wpdb->query("UPDATE {$prefix}rezervasyonlar SET okundu = 1 WHERE durum = 'yeni' AND okundu = 0");
        }
        
        include CAHT_PLUGIN_DIR . 'admin/views/rezervasyonlar.php';
    }
    
    public function render_araclar() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';
        $araclar = $wpdb->get_results("SELECT * FROM {$prefix}araclar ORDER BY sira ASC");
        
        $edit_mode = false; $arac = null;
        if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
            $edit_mode = true;
            $arac = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$prefix}araclar WHERE id = %d", intval($_GET['edit'])));
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
    
    // Tüm bölgeleri çek
    $bolgeler = $wpdb->get_results("SELECT * FROM {$prefix}bolgeler ORDER BY olusturma_tarihi DESC");
    
    // DEBUG: Eğer koordinatlar boşsa logla
    if (defined('WP_DEBUG') && WP_DEBUG && !empty($bolgeler)) {
        foreach ($bolgeler as $b) {
            $k = json_decode($b->koordinatlar);
            if (empty($k)) {
                error_log('CAHT DEBUG - Bölge ID: ' . $b->id . ' | Ad: ' . $b->ad . ' | Koordinatlar: [' . $b->koordinatlar . '] | Uzunluk: ' . strlen($b->koordinatlar));
            }
        }
    }
    
    // Ekleme/Düzenleme sayfası - TEK SAYFA
    if ((isset($_GET['action']) && $_GET['action'] === 'ekle') || (isset($_GET['edit']) && is_numeric($_GET['edit']))) {
        $edit_mode = false;
        $bolge = null;
        if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
            $edit_mode = true;
            $bolge = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$prefix}bolgeler WHERE id = %d",
                intval($_GET['edit'])
            ));
        }
        include CAHT_PLUGIN_DIR . 'admin/views/bolge-ekle.php';
    } else {
        include CAHT_PLUGIN_DIR . 'admin/views/bolge-listesi.php';
    }
}
    
    public function render_havalimanlari() {
    global $wpdb;
    $prefix = $wpdb->prefix . 'caht_';
    
    // HER ZAMAN tüm havalimanlarını çek
    $havalimanlari = $wpdb->get_results("SELECT * FROM {$prefix}havalimanlar ORDER BY olusturma_tarihi DESC");
    
    // Ekleme/Düzenleme sayfası - TEK SAYFA
    if ((isset($_GET['action']) && $_GET['action'] === 'ekle') || (isset($_GET['edit']) && is_numeric($_GET['edit']))) {
        $edit_mode = false;
        $havalimani = null;
        if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
            $edit_mode = true;
            $havalimani = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$prefix}havalimanlar WHERE id = %d",
                intval($_GET['edit'])
            ));
        }
        include CAHT_PLUGIN_DIR . 'admin/views/havalimani-ekle.php';
    } else {
        // Liste sayfası - sadece tablo
        include CAHT_PLUGIN_DIR . 'admin/views/havalimani-listesi.php';
    }
}
    
    public function render_sabit_fiyatlar() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';
        
        $filtre_havalimani = isset($_GET['havalimani_id']) ? intval($_GET['havalimani_id']) : 0;
        $filtre_arac = isset($_GET['arac_id']) ? intval($_GET['arac_id']) : 0;
        
        $sql = "SELECT fs.*, a.ad as arac_adi, h.ad as havalimani_adi, b.ad as bolge_adi 
                FROM {$prefix}fiyat_sabitleri fs 
                LEFT JOIN {$prefix}araclar a ON fs.arac_id = a.id 
                LEFT JOIN {$prefix}havalimanlar h ON fs.havalimani_id = h.id 
                LEFT JOIN {$prefix}bolgeler b ON fs.bolge_id = b.id 
                WHERE 1=1";
        $params = array();
        
        if ($filtre_havalimani > 0) {
            $sql .= " AND fs.havalimani_id = %d";
            $params[] = $filtre_havalimani;
        }
        if ($filtre_arac > 0) {
            $sql .= " AND fs.arac_id = %d";
            $params[] = $filtre_arac;
        }
        
        $sql .= " ORDER BY fs.olusturma_tarihi DESC";
        
        $fiyatlar = !empty($params) ? $wpdb->get_results($wpdb->prepare($sql, ...$params)) : $wpdb->get_results($sql);
        
        $havalimanlari = $wpdb->get_results("SELECT id, ad FROM {$prefix}havalimanlar ORDER BY ad ASC");
        $araclar = $wpdb->get_results("SELECT id, ad FROM {$prefix}araclar ORDER BY ad ASC");
        
        if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
            $fiyat_id = intval($_GET['edit']);
            $fiyat = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$prefix}fiyat_sabitleri WHERE id = %d", $fiyat_id));
            $araclar_select = $wpdb->get_results("SELECT id, ad FROM {$prefix}araclar");
            $havalimanlari_select = $wpdb->get_results("SELECT id, ad FROM {$prefix}havalimanlar");
            $bolgeler_select = $wpdb->get_results("SELECT id, ad FROM {$prefix}bolgeler");
            include CAHT_PLUGIN_DIR . 'admin/views/sabit-fiyat-duzenle.php';
            return;
        }
        
        if (isset($_GET['action']) && $_GET['action'] === 'ekle') {
            $araclar_select = $wpdb->get_results("SELECT id, ad FROM {$prefix}araclar");
            $havalimanlari_select = $wpdb->get_results("SELECT id, ad FROM {$prefix}havalimanlar");
            $bolgeler_select = $wpdb->get_results("SELECT id, ad FROM {$prefix}bolgeler");
            $preselected_arac = isset($_GET['arac_id']) ? intval($_GET['arac_id']) : 0;
            include CAHT_PLUGIN_DIR . 'admin/views/sabit-fiyat-ekle.php';
        } else {
            include CAHT_PLUGIN_DIR . 'admin/views/sabit-fiyat-listesi.php';
        }
    }
    
    public function render_ayarlar() { include CAHT_PLUGIN_DIR . 'admin/views/ayarlar.php'; }
    
    private function save_arac() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';
        
        $data = array(
            'ad' => sanitize_text_field($_POST['ad']),
            'kapasite' => intval($_POST['kapasite']),
            'bavul_kapasite' => intval($_POST['bavul_kapasite']),
            'km_fiyat' => floatval($_POST['km_fiyat']),
            'acilis_ucreti' => floatval($_POST['acilis_ucreti']),
            'aciklama' => sanitize_textarea_field($_POST['aciklama']),
            'sira' => intval($_POST['sira']),
        );
        
        $resim_verisi = '';
        if (isset($_POST['resim']) && !empty($_POST['resim'])) {
            $resim_verisi = $_POST['resim'];
        } elseif (isset($_REQUEST['resim']) && !empty($_REQUEST['resim'])) {
            $resim_verisi = $_REQUEST['resim'];
        }
        
        if (!empty($resim_verisi)) {
            $resim_decoded = json_decode(stripslashes($resim_verisi), true);
            $data['resim'] = is_array($resim_decoded) ? stripslashes($resim_verisi) : json_encode(array(sanitize_text_field($resim_verisi)));
        } else {
            $data['resim'] = '[]';
        }
        
        if (!empty($_POST['arac_id'])) {
            $wpdb->update($prefix . 'araclar', $data, array('id' => intval($_POST['arac_id'])));
        } else {
            $wpdb->insert($prefix . 'araclar', $data);
        }
        
        wp_redirect(admin_url('admin.php?page=caht-araclar'));
        exit;
    }
    
    private function save_bolge() {
    global $wpdb;
    $prefix = $wpdb->prefix . 'caht_';
    
    $ad = sanitize_text_field($_POST['ad'] ?? '');
    $koordinatlar_raw = $_POST['koordinatlar'] ?? '';
    
    // BACKSLASH TEMİZLE - WordPress magic quotes etkisi
    $koordinatlar = stripslashes($koordinatlar_raw);
    
    // DEBUG
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('CAHT SAVE_BOLGE RAW: ' . $koordinatlar_raw);
        error_log('CAHT SAVE_BOLGE CLEAN: ' . $koordinatlar);
    }
    
    // JSON valid mi kontrol et
    $test_decode = json_decode($koordinatlar, true);
    if (!is_array($test_decode) || empty($test_decode)) {
        wp_die('Hata: Geçersiz koordinat formatı. Lütfen haritada poligon çizin ve tekrar deneyin.<br>Ham veri: ' . esc_html(substr($koordinatlar, 0, 200)));
    }
    
    $data = array(
        'ad' => $ad,
        'koordinatlar' => $koordinatlar,
    );
    
    if (!empty($_POST['bolge_id'])) {
        $wpdb->update($prefix . 'bolgeler', $data, array('id' => intval($_POST['bolge_id'])));
    } else {
        $wpdb->insert($prefix . 'bolgeler', $data);
    }
    
    wp_redirect(admin_url('admin.php?page=caht-bolgeler&action=ekle&eklendi=1'));
    exit;
}
    
   private function save_havalimani() {
    global $wpdb;
    $prefix = $wpdb->prefix . 'caht_';
    
    $ad = sanitize_text_field($_POST['ad'] ?? '');
    $koordinatlar_raw = $_POST['koordinatlar'] ?? '';
    
    // BACKSLASH TEMİZLE
    $koordinatlar = stripslashes($koordinatlar_raw);
    
    // JSON valid mi kontrol et
    $test_decode = json_decode($koordinatlar, true);
    if (!is_array($test_decode) || empty($test_decode)) {
        wp_die('Hata: Geçersiz koordinat formatı. Lütfen haritada poligon çizin ve tekrar deneyin.');
    }
    
    $data = array(
        'ad' => $ad,
        'koordinatlar' => $koordinatlar,
    );
    
    if (!empty($_POST['havalimani_id'])) {
        $wpdb->update($prefix . 'havalimanlar', $data, array('id' => intval($_POST['havalimani_id'])));
    } else {
        $wpdb->insert($prefix . 'havalimanlar', $data);
    }
    
    wp_redirect(admin_url('admin.php?page=caht-havalimanlari&action=ekle&eklendi=1'));
    exit;
}
    
    private function save_sabit_fiyat() {
    global $wpdb;
    $prefix = $wpdb->prefix . 'caht_';
    
    $id = !empty($_POST['fiyat_id']) ? intval($_POST['fiyat_id']) : 0;
    
    // VALIDASYON - Her iki alan da zorunlu
    $arac_id = intval($_POST['arac_id'] ?? 0);
    $havalimani_id = intval($_POST['havalimani_id'] ?? 0);
    $bolge_id = intval($_POST['bolge_id'] ?? 0);
    $sabit_fiyat = floatval($_POST['sabit_fiyat'] ?? 0);
    
    if ($arac_id <= 0) {
        wp_die('Lütfen bir araç seçin.');
    }
    if ($havalimani_id <= 0) {
        wp_die('Lütfen bir havalimanı seçin.');
    }
    if ($bolge_id <= 0) {
        wp_die('Lütfen bir bölge seçin.');
    }
    if ($sabit_fiyat <= 0) {
        wp_die('Lütfen geçerli bir fiyat girin.');
    }
    
    // HER İKİSİ DE KAYDEDİLİYOR - Artık null değil
    $data = array(
        'arac_id' => $arac_id,
        'havalimani_id' => $havalimani_id,
        'bolge_id' => $bolge_id,
        'sabit_fiyat' => $sabit_fiyat,
    );
    
    if ($id > 0) {
        $wpdb->update($prefix . 'fiyat_sabitleri', $data, array('id' => $id));
    } else {
        // Aynı kural var mı kontrol et
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$prefix}fiyat_sabitleri 
             WHERE arac_id = %d AND havalimani_id = %d AND bolge_id = %d",
            $arac_id, $havalimani_id, $bolge_id
        ));
        
        if ($existing) {
            wp_die('Bu araç için bu havalimanı-bölge kombinasyonunda zaten bir kural var. Lütfen mevcut kuralı düzenleyin.');
        }
        
        $wpdb->insert($prefix . 'fiyat_sabitleri', $data);
    }
    
    wp_redirect(admin_url('admin.php?page=caht-sabit-fiyatlar'));
    exit;
}
    
    private function toplu_fiyat_guncelle() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';
        
        $islem_tipi = sanitize_text_field($_POST['islem_tipi']);
        $miktar = floatval(str_replace(',', '.', $_POST['miktar']));
        $yon = sanitize_text_field($_POST['yon']);
        
        if ($miktar <= 0) {
            wp_redirect(admin_url('admin.php?page=caht-sabit-fiyatlar&hata=gecersiz_miktar'));
            exit;
        }
        
        $operator = ($yon === 'arttir') ? '+' : '-';
        
        if ($islem_tipi === 'yuzde') {
            $sql = "UPDATE {$prefix}fiyat_sabitleri SET sabit_fiyat = sabit_fiyat {$operator} (sabit_fiyat * ({$miktar} / 100))";
        } else {
            $sql = "UPDATE {$prefix}fiyat_sabitleri SET sabit_fiyat = sabit_fiyat {$operator} {$miktar}";
        }
        
        $wpdb->query($sql);
        wp_redirect(admin_url('admin.php?page=caht-sabit-fiyatlar&guncellendi=1'));
        exit;
    }
    
    private function save_settings() {
        update_option('caht_google_maps_api_key', sanitize_text_field($_POST['google_maps_api_key']));
        update_option('caht_whatsapp_token', sanitize_text_field($_POST['whatsapp_token']));
        update_option('caht_whatsapp_phone_id', sanitize_text_field($_POST['whatsapp_phone_id']));
        update_option('caht_whatsapp_template_name', sanitize_text_field($_POST['whatsapp_template_name']));
        
        $ek_hizmetler = array(
            'cocuk_koltugu' => floatval($_POST['cocuk_koltugu_fiyat']),
            'karsilama_hizmeti' => floatval($_POST['karsilama_hizmeti_fiyat']),
            'third_bridge' => floatval($_POST['third_bridge_fiyat']),
        );
        update_option('caht_ek_hizmetler', json_encode($ek_hizmetler));
        
        wp_redirect(admin_url('admin.php?page=caht-ayarlar&saved=1'));
        exit;
    }
    
    public function ajax_rezervasyon_detay() {
        check_ajax_referer('caht_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        
        $id = intval($_POST['id']);
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';
        
        $rez = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, a.ad as arac_ad FROM {$prefix}rezervasyonlar r 
             LEFT JOIN {$prefix}araclar a ON r.arac_id = a.id WHERE r.id = %d", $id
        ));
        
        if (!$rez) wp_send_json_error('Rezervasyon bulunamadı.');
        
        $ek_yolcular = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$prefix}ek_yolcular WHERE rezervasyon_id = %d", $id
        ));
        
        wp_send_json_success(array(
            'rezervasyon' => $rez,
            'ek_yolcular' => $ek_yolcular,
            'gidis_tarihi' => date('d.m.Y H:i', strtotime($rez->gidis_tarih)),
            'donus_tarihi' => $rez->donus_tarih ? date('d.m.Y H:i', strtotime($rez->donus_tarih)) : null,
        ));
    }
    
    public function ajax_rezervasyon_durum_guncelle() {
        check_ajax_referer('caht_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        
        $id = intval($_POST['id']);
        $durum = sanitize_text_field($_POST['durum']);
        $valid = array('yeni', 'tamamlanmis', 'iptal', 'silinmis');
        
        if (!in_array($durum, $valid)) wp_send_json_error('Geçersiz durum.');
        
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';
        $wpdb->update($prefix . 'rezervasyonlar', array('durum' => $durum), array('id' => $id));
        
        wp_send_json_success(array('message' => 'Durum güncellendi.'));
    }
    
    public function ajax_toplu_durum_guncelle() {
        check_ajax_referer('caht_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        
        $ids = array_map('intval', $_POST['ids'] ?? array());
        $durum = sanitize_text_field($_POST['durum']);
        $valid = array('yeni', 'tamamlanmis', 'iptal', 'silinmis');
        
        if (empty($ids) || !in_array($durum, $valid)) wp_send_json_error('Geçersiz parametre.');
        
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $wpdb->query($wpdb->prepare(
            "UPDATE {$prefix}rezervasyonlar SET durum = %s WHERE id IN ($placeholders)",
            array_merge(array($durum), $ids)
        ));
        
        wp_send_json_success(array('message' => count($ids) . ' rezervasyon güncellendi.'));
    }
    
    public function ajax_rezervasyon_sil_kalici() {
        check_ajax_referer('caht_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        
        $ids = array_map('intval', $_POST['ids'] ?? array());
        if (empty($ids)) wp_send_json_error('ID eksik.');
        
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$prefix}ek_yolcular WHERE rezervasyon_id IN ($placeholders)", $ids
        ));
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$prefix}rezervasyonlar WHERE id IN ($placeholders)", $ids
        ));
        
        wp_send_json_success(array('message' => count($ids) . ' rezervasyon kalıcı olarak silindi.'));
    }
    
    public function ajax_bolge_guncelle() {
        check_ajax_referer('caht_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        
        $id = intval($_POST['id']);
        $ad = sanitize_text_field($_POST['ad']);
        $koordinatlar = sanitize_text_field($_POST['koordinatlar']);
        
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';
        $wpdb->update($prefix . 'bolgeler', array('ad' => $ad, 'koordinatlar' => $koordinatlar), array('id' => $id));
        
        wp_send_json_success(array('message' => 'Bölge güncellendi.'));
    }
}