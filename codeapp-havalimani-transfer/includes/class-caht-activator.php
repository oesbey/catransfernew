<?php
/**
 * Aktivasyon sınıfı - Veritabanı tablolarını oluşturur
 */

if (!defined('ABSPATH')) {
    exit;
}

class CAHT_Activator {

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'caht_';

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // 1. ARAÇLAR TABLOSU
        $sql_araclar = "CREATE TABLE IF NOT EXISTS {$prefix}araclar (
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
        dbDelta($sql_araclar);

        // 2. BÖLGELER TABLOSU (Poligon koordinatları)
        $sql_bolgeler = "CREATE TABLE IF NOT EXISTS {$prefix}bolgeler (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ad varchar(255) NOT NULL,
            koordinatlar longtext NOT NULL COMMENT 'JSON poligon koordinatları',
            olusturma_tarihi datetime DEFAULT CURRENT_TIMESTAMP,
            guncelleme_tarihi datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset_collate};";
        dbDelta($sql_bolgeler);

        // 3. HAVALİMANLARI TABLOSU (Poligon koordinatları)
        $sql_havalimanlar = "CREATE TABLE IF NOT EXISTS {$prefix}havalimanlar (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ad varchar(255) NOT NULL,
            koordinatlar longtext NOT NULL COMMENT 'JSON poligon koordinatları',
            olusturma_tarihi datetime DEFAULT CURRENT_TIMESTAMP,
            guncelleme_tarihi datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset_collate};";
        dbDelta($sql_havalimanlar);

        // 4. SABİT FİYAT TABLOSU
        $sql_fiyat_sabitleri = "CREATE TABLE IF NOT EXISTS {$prefix}fiyat_sabitleri (
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
        dbDelta($sql_fiyat_sabitleri);

        // 5. REZERVASYONLAR TABLOSU
        $sql_rezervasyonlar = "CREATE TABLE IF NOT EXISTS {$prefix}rezervasyonlar (
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
            odeme_durumu varchar(50) DEFAULT 'bekliyor' COMMENT 'bekliyor, tamamlandi, basarisiz',
            iyzico_odeme_id varchar(255) DEFAULT NULL,
            sozlesme_kabul tinyint(1) NOT NULL DEFAULT 0,
            sofor_id bigint(20) unsigned DEFAULT NULL,
            sofor_ad_soyad varchar(255) DEFAULT NULL,
            durum varchar(50) DEFAULT 'yeni' COMMENT 'yeni, tamamlanmis, iptal, silinmis',
            okundu tinyint(1) NOT NULL DEFAULT 0,
            woo_order_id bigint(20) unsigned DEFAULT NULL COMMENT 'WooCommerce sipariş ID',
            olusturma_tarihi datetime DEFAULT CURRENT_TIMESTAMP,
            guncelleme_tarihi datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY durum (durum),
            KEY arac_id (arac_id),
            KEY sofor_id (sofor_id),
            KEY woo_order_id (woo_order_id)
        ) {$charset_collate};";
        dbDelta($sql_rezervasyonlar);

        // 6. EK YOLCULAR TABLOSU
        $sql_ek_yolcular = "CREATE TABLE IF NOT EXISTS {$prefix}ek_yolcular (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            rezervasyon_id bigint(20) unsigned NOT NULL,
            ad varchar(100) NOT NULL,
            soyad varchar(100) NOT NULL,
            PRIMARY KEY (id),
            KEY rezervasyon_id (rezervasyon_id)
        ) {$charset_collate};";
        dbDelta($sql_ek_yolcular);

        // 7. ŞOFÖRLER TABLOSU
        $sql_soforler = "CREATE TABLE IF NOT EXISTS {$prefix}soforler (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ad_soyad varchar(255) NOT NULL,
            telefon varchar(20) NOT NULL,
            durum tinyint(1) NOT NULL DEFAULT 1,
            olusturma_tarihi datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset_collate};";
        dbDelta($sql_soforler);

        // 8. SÖZLEŞMELER TABLOSU
        $sql_sozlesmeler = "CREATE TABLE IF NOT EXISTS {$prefix}sozlesmeler (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            mss_tr longtext,
            mss_en longtext,
            PRIMARY KEY (id)
        ) {$charset_collate};";
        dbDelta($sql_sozlesmeler);

        // Varsayılan sözleşme ekle
        $sozlesme_var = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}sozlesmeler");
        if ($sozlesme_var == 0) {
            $wpdb->insert($prefix . 'sozlesmeler', [
                'mss_tr' => '<h2>Mesafeli Satış Sözleşmesi</h2><p>Buraya sözleşme metninizi yazın...</p>',
                'mss_en' => '<h2>Distance Sales Agreement</h2><p>Write your agreement text here...</p>'
            ]);
        }

        // Varsayılan ayarları ekle
        add_option('caht_google_maps_api_key', '');
        add_option('caht_ek_hizmetler', json_encode([
            'cocuk_koltugu' => 500.00,
            'karsilama_hizmeti' => 300.00,
            'third_bridge' => 700.00
        ]));
        add_option('caht_whatsapp_token', '');
        add_option('caht_whatsapp_phone_id', '');
        add_option('caht_whatsapp_template_name', 'sofor_bilgilendirem_sistemi');

        // Rewrite kurallarını yeniden yaz
        flush_rewrite_rules();
    }
}