<?php
/**
 * Plugin Name: Codeapp Havalimanı Transfer
 * Description: VIP havalimanı transfer rezervasyon sistemi
 * Version: 1.0.0
 * Author: Codeapp
 * Text Domain: codeapp-havalimani-transfer
 */

if (!defined('ABSPATH')) exit;

define('CAHT_VERSION', '1.0.0');
define('CAHT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CAHT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CAHT_PLUGIN_BASENAME', plugin_basename(__FILE__));


/**
 * Aktivasyon



 */

// === SMTP TEST MAIL HANDLER ===
// Bunu eklenti ana dosyasına veya includes/class-caht.php gibi bir yere ekle

add_action('admin_post_caht_smtp_test', 'caht_handle_smtp_test');
add_action('admin_post_nopriv_caht_smtp_test', 'caht_handle_smtp_test'); // Güvenlik için nopriv'i de ekle ama redirect et

function caht_handle_smtp_test() {
    // Nonce kontrolü
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'caht_nonce')) {
        wp_die('Security check failed. Please refresh the page and try again.');
    }
    
    // Yetki kontrolü
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to perform this action.');
    }
    
    // SMTP ayarlarını al
    $smtp_enabled = get_option('caht_smtp_enabled', '0');
    $to = get_option('caht_smtp_from_email', get_option('admin_email'));
    
    // PHPMailer'ı manuel yapılandır (test için)
    $smtp_host = get_option('caht_smtp_host', '');
    $smtp_port = get_option('caht_smtp_port', '587');
    $smtp_encryption = get_option('caht_smtp_encryption', 'tls');
    $smtp_username = get_option('caht_smtp_username', '');
    $smtp_password = get_option('caht_smtp_password', '');
    $from_name = get_option('caht_smtp_from_name', get_bloginfo('name'));
    $from_email = get_option('caht_smtp_from_email', get_option('admin_email'));
    
    if (empty($smtp_host) || empty($smtp_username) || empty($smtp_password)) {
        wp_redirect(admin_url('admin.php?page=caht-smtp&test=error&reason=empty'));
        exit;
    }
    
    $subject = 'CAHT Transfer - SMTP Test Email';
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:30px;">
        <div style="background:linear-gradient(135deg,#1b510d,#237e12);padding:30px;border-radius:16px 16px 0 0;text-align:center;">
            <h2 style="color:#fff;margin:0;">SMTP Test Successful!</h2>
        </div>
        <div style="background:#fff;padding:30px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 16px 16px;">
            <p style="color:#1a1a1a;font-size:15px;line-height:1.6;">
                Your SMTP configuration is working correctly.
            </p>
            <table style="width:100%;border-collapse:collapse;margin-top:20px;">
                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td style="padding:10px;color:#6b7280;font-size:13px;font-weight:600;">Server</td>
                    <td style="padding:10px;color:#1a1a1a;font-size:14px;">' . esc_html($smtp_host) . ':' . esc_html($smtp_port) . '</td>
                </tr>
                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td style="padding:10px;color:#6b7280;font-size:13px;font-weight:600;">Encryption</td>
                    <td style="padding:10px;color:#1a1a1a;font-size:14px;">' . esc_html(strtoupper($smtp_encryption)) . '</td>
                </tr>
                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td style="padding:10px;color:#6b7280;font-size:13px;font-weight:600;">Username</td>
                    <td style="padding:10px;color:#1a1a1a;font-size:14px;">' . esc_html($smtp_username) . '</td>
                </tr>
                <tr>
                    <td style="padding:10px;color:#6b7280;font-size:13px;font-weight:600;">Time</td>
                    <td style="padding:10px;color:#1a1a1a;font-size:14px;">' . date('Y-m-d H:i:s') . ' (' . wp_timezone_string() . ')</td>
                </tr>
            </table>
        </div>
    </body>
    </html>';
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    // SMTP'yi geçici olarak force et
    add_filter('wp_mail_from', function($from) use ($from_email) {
        return $from_email;
    }, 9999);
    
    add_filter('wp_mail_from_name', function($from_name_orig) use ($from_name) {
        return $from_name;
    }, 9999);
    
    // PHPMailer'ı SMTP için yapılandır
    add_action('phpmailer_init', 'caht_force_smtp_config', 9999);
    
    $sent = wp_mail($to, $subject, $message, $headers);
    
    // Hook'ları temizle
    remove_action('phpmailer_init', 'caht_force_smtp_config', 9999);
    
    if ($sent) {
        wp_redirect(admin_url('admin.php?page=caht-smtp&test=success'));
    } else {
        global $phpmailer;
        $error_msg = '';
        if ($phpmailer && !empty($phpmailer->ErrorInfo)) {
            $error_msg = urlencode($phpmailer->ErrorInfo);
        }
        wp_redirect(admin_url('admin.php?page=caht-smtp&test=error&reason=' . $error_msg));
    }
    exit;
}

function caht_force_smtp_config($phpmailer) {
    $smtp_host = get_option('caht_smtp_host', '');
    $smtp_port = get_option('caht_smtp_port', '587');
    $smtp_encryption = get_option('caht_smtp_encryption', 'tls');
    $smtp_username = get_option('caht_smtp_username', '');
    $smtp_password = get_option('caht_smtp_password', '');
    
    $phpmailer->isSMTP();
    $phpmailer->Host = $smtp_host;
    $phpmailer->SMTPAuth = true;
    $phpmailer->Port = intval($smtp_port);
    $phpmailer->Username = $smtp_username;
    $phpmailer->Password = $smtp_password;
    
    if ($smtp_encryption === 'ssl') {
        $phpmailer->SMTPSecure = 'ssl';
    } elseif ($smtp_encryption === 'tls') {
        $phpmailer->SMTPSecure = 'tls';
    } else {
        $phpmailer->SMTPSecure = '';
    }
    
    $phpmailer->SMTPDebug = 0;
    $phpmailer->Debugoutput = 'error_log';
}
function caht_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $prefix = $wpdb->prefix . 'caht_';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    // Mevcut require_once satırlarının yanına ekle:
require_once CAHT_PLUGIN_DIR . 'includes/class-caht-mail.php';

    // ARAÇLAR TABLOSU
    $sql = "CREATE TABLE IF NOT EXISTS {$prefix}araclar (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        ad varchar(255) NOT NULL,
        kapasite int(11) NOT NULL DEFAULT 1,
        bavul_kapasite int(11) NOT NULL DEFAULT 1,
        km_fiyat decimal(10,2) NOT NULL DEFAULT 0.00,
        acilis_ucreti decimal(10,2) NOT NULL DEFAULT 0.00,
        resim longtext,
        aciklama text,
        sira int(11) NOT NULL DEFAULT 0,
        durum tinyint(1) NOT NULL DEFAULT 1,
        olusturma_tarihi datetime DEFAULT CURRENT_TIMESTAMP,
        guncelleme_tarihi datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY sira (sira)
    ) {$charset_collate};";
    dbDelta($sql);

    // BÖLGELER TABLOSU
    $sql = "CREATE TABLE IF NOT EXISTS {$prefix}bolgeler (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        ad varchar(255) NOT NULL,
        koordinatlar longtext NOT NULL,
        olusturma_tarihi datetime DEFAULT CURRENT_TIMESTAMP,
        guncelleme_tarihi datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) {$charset_collate};";
    dbDelta($sql);

    // HAVALİMANLARI TABLOSU
    $sql = "CREATE TABLE IF NOT EXISTS {$prefix}havalimanlar (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        ad varchar(255) NOT NULL,
        koordinatlar longtext NOT NULL,
        olusturma_tarihi datetime DEFAULT CURRENT_TIMESTAMP,
        guncelleme_tarihi datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) {$charset_collate};";
    dbDelta($sql);

    // SABİT FİYAT TABLOSU
    $sql = "CREATE TABLE IF NOT EXISTS {$prefix}fiyat_sabitleri (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        arac_id bigint(20) unsigned NOT NULL,
        havalimani_id bigint(20) unsigned DEFAULT NULL,
        bolge_id bigint(20) unsigned DEFAULT NULL,
        sabit_fiyat decimal(10,2) NOT NULL,
        olusturma_tarihi datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY arac_id (arac_id),
        KEY havalimani_id (havalimani_id),
        KEY bolge_id (bolge_id)
    ) {$charset_collate};";
    dbDelta($sql);

    // REZERVASYONLAR TABLOSU
    $sql = "CREATE TABLE IF NOT EXISTS {$prefix}rezervasyonlar (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        yolcu_ad varchar(100) NOT NULL,
        yolcu_soyad varchar(100) NOT NULL,
        telefon varchar(20) NOT NULL,
        eposta varchar(100) NOT NULL,
        ek_detay text,
        cocuk_koltugu tinyint(1) NOT NULL DEFAULT 0,
        karsilama_hizmeti tinyint(1) NOT NULL DEFAULT 0,
        third_bridge tinyint(1) NOT NULL DEFAULT 0,
        odeme_yontemi varchar(50) DEFAULT 'nakit',
        nereden varchar(255) NOT NULL,
        nereye varchar(255) NOT NULL,
        nereden_lat decimal(10,8) DEFAULT NULL,
        nereden_lng decimal(11,8) DEFAULT NULL,
        nereye_lat decimal(10,8) DEFAULT NULL,
        nereye_lng decimal(11,8) DEFAULT NULL,
        mesafe decimal(10,2) NOT NULL,
        kisi_sayisi int(11) NOT NULL DEFAULT 1,
        gidis_tarih datetime NOT NULL,
        donus_tarih datetime DEFAULT NULL,
        gidis_donus tinyint(1) NOT NULL DEFAULT 0,
        arac_id bigint(20) unsigned NOT NULL,
        toplam_fiyat decimal(10,2) NOT NULL,
        para_birimi varchar(3) NOT NULL DEFAULT 'TL',
        secilen_fiyat decimal(10,2) NOT NULL,
        odeme_durumu varchar(50) DEFAULT 'bekliyor',
        iyzico_odeme_id varchar(255) DEFAULT NULL,
        sozlesme_kabul tinyint(1) NOT NULL DEFAULT 0,
        sofor_id bigint(20) unsigned DEFAULT NULL,
        sofor_ad_soyad varchar(255) DEFAULT NULL,
        durum varchar(50) DEFAULT 'yeni',
        okundu tinyint(1) NOT NULL DEFAULT 0,
        woo_order_id bigint(20) unsigned DEFAULT NULL,
        olusturma_tarihi datetime DEFAULT CURRENT_TIMESTAMP,
        guncelleme_tarihi datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY durum (durum),
        KEY arac_id (arac_id),
        KEY sofor_id (sofor_id)
    ) {$charset_collate};";
    dbDelta($sql);

    // EK YOLCULAR TABLOSU
    $sql = "CREATE TABLE IF NOT EXISTS {$prefix}ek_yolcular (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        rezervasyon_id bigint(20) unsigned NOT NULL,
        ad varchar(100) NOT NULL,
        soyad varchar(100) NOT NULL,
        PRIMARY KEY (id),
        KEY rezervasyon_id (rezervasyon_id)
    ) {$charset_collate};";
    dbDelta($sql);

    // ŞOFÖRLER TABLOSU
    $sql = "CREATE TABLE IF NOT EXISTS {$prefix}soforler (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        ad_soyad varchar(255) NOT NULL,
        telefon varchar(20) NOT NULL,
        durum tinyint(1) NOT NULL DEFAULT 1,
        olusturma_tarihi datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) {$charset_collate};";
    dbDelta($sql);

    // SÖZLEŞMELER TABLOSU
    $sql = "CREATE TABLE IF NOT EXISTS {$prefix}sozlesmeler (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        mss_tr longtext,
        mss_en longtext,
        PRIMARY KEY (id)
    ) {$charset_collate};";
    dbDelta($sql);

    // Varsayılan sözleşme ekle
    $sozlesme_var = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}sozlesmeler");
    if ($sozlesme_var == 0) {
        $wpdb->insert($prefix . 'sozlesmeler', array(
            'mss_tr' => '<h2>Mesafeli Satış Sözleşmesi</h2><p>Buraya sözleşme metninizi yazın...</p>',
            'mss_en' => '<h2>Distance Sales Agreement</h2><p>Write your agreement text here...</p>'
        ));
    }

    // Varsayılan ayarlar
    add_option('caht_google_maps_api_key', '');
    add_option('caht_ek_hizmetler', json_encode(array(
        'cocuk_koltugu' => 500.00,
        'karsilama_hizmeti' => 300.00,
        'third_bridge' => 700.00
    )));
    add_option('caht_whatsapp_token', '');
    add_option('caht_whatsapp_phone_id', '');
    add_option('caht_whatsapp_template_name', 'sofor_bilgilendirem_sistemi');

    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'caht_activate');

/**
 * Deaktivasyon
 */
// WordPress init hook'una ekle
add_action('admin_post_caht_smtp_test', 'caht_send_test_email');

function caht_send_test_email() {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'caht_nonce')) {
        wp_die('Security check failed.');
    }
    
    $to = get_option('caht_smtp_from_email', get_option('admin_email'));
    $subject = 'CAHT Transfer - SMTP Test Email';
    
    $message = '
    <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:30px;">
        <h2 style="color:#237e12;">SMTP Test Successful!</h2>
        <p>Your SMTP configuration is working correctly.</p>
        <p><strong>Server:</strong> ' . esc_html(get_option('caht_smtp_host')) . '</p>
        <p><strong>Time:</strong> ' . date('Y-m-d H:i:s') . '</p>
    </div>';
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    $sent = caht_send_email($to, $subject, $message, $headers);
    
    wp_redirect(admin_url('admin.php?page=caht-smtp&test=' . ($sent ? 'success' : 'error')));
    exit;
}
function caht_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'caht_deactivate');

/**
 * Eklenti çalıştır
 * 
 */
 
function caht_run() {
    // Dosyaları yükle - YOLLAR DÜZELTİLDİ
    $files = array(
        'includes/class-caht-activator.php'      => false,
        'includes/class-chat-deactivator.php'    => false, // DOSYA ADI DÜZELTİLDİ (chat değil caht ama mevcut dosya chat)
        'includes/class-caht-database.php'       => false,
        'public/class-caht-ajax.php'             => false, // YOL DÜZELTİLDİ (includes -> public)
        'includes/class-caht-google-maps.php'    => false,
        'includes/class-caht-fiyat-hesaplama.php'=> false,
        'includes/class-caht-rezervasyon.php'    => false,
        'includes/class-caht-woocommerce.php'    => false,
        'admin/class-caht-admin.php'             => true,
        'public/class-caht-pucblic.php'          => false, // MEVCUT DOSYA ADI KORUNDU
        'public/class-caht-shortcodes.php'       => false,
    );

    foreach ($files as $file => $is_admin) {
        $path = CAHT_PLUGIN_DIR . $file;
        if (file_exists($path)) {
            if ($is_admin && !is_admin()) {
                continue;
            }
            require_once $path;
        }
    }

    // Sınıfları başlat
    if (is_admin() && class_exists('CAHT_Admin')) {
        $admin = new CAHT_Admin();
        $admin->init();
    }

    if (class_exists('CAHT_Public')) {
        $public = new CAHT_Public();
        $public->init();
    }

    if (class_exists('CAHT_Ajax')) {
        $ajax = new CAHT_Ajax();
        $ajax->init();
    }

    if (class_exists('CAHT_Shortcodes')) {
        $shortcodes = new CAHT_Shortcodes();
        $shortcodes->init();
    }

    if (class_exists('WooCommerce') && class_exists('CAHT_WooCommerce')) {
        $wc = new CAHT_WooCommerce();
        $wc->init();
    }
}
add_action('plugins_loaded', 'caht_run');

/**
 * Admin uyarıları
 */
add_action('admin_notices', 'caht_admin_notices');
function caht_admin_notices() {
    if (!class_exists('WooCommerce')) {
        echo '<div class="notice notice-warning"><p><strong>Codeapp Havalimanı Transfer:</strong> Tam fonksiyonellik için WooCommerce önerilir. <a href="' . admin_url('plugin-install.php?s=woocommerce&tab=search&type=term') . '">WooCommerce\'i yükle</a></p></div>';
    }

    $api_key = get_option('caht_google_maps_api_key', '');
    if (empty($api_key)) {
        echo '<div class="notice notice-error"><p><strong>Codeapp Havalimanı Transfer:</strong> Google Maps API anahtarınızı <a href="' . admin_url('admin.php?page=caht-ayarlar') . '">ayarlar sayfasından</a> giriniz.</p></div>';
    }
}

/**
 * Eklenti linkleri
 */
add_filter('plugin_action_links_' . CAHT_PLUGIN_BASENAME, 'caht_action_links');
function caht_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=caht-ayarlar') . '">Ayarlar</a>';
    array_unshift($links, $settings_link);
    return $links;
}