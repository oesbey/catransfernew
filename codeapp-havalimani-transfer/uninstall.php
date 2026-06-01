<?php
// Güvenlik: WordPress dışından erişimi engelle
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Seçenekleri temizle
delete_option('caht_google_maps_api_key');
delete_option('caht_iyzico_api_key');
delete_option('caht_iyzico_secret_key');
delete_option('caht_iyzico_base_url');
delete_option('caht_whatsapp_token');
delete_option('caht_whatsapp_phone_id');
delete_option('caht_whatsapp_template_name');
delete_option('caht_ek_hizmetler');

// Tabloları sil (opsiyonel - dikkatli kullan)
/*
global $wpdb;
$tables = [
    $wpdb->prefix . 'caht_araclar',
    $wpdb->prefix . 'caht_bolgeler',
    $wpdb->prefix . 'caht_havalimanlar',
    $wpdb->prefix . 'caht_fiyat_sabitleri',
    $wpdb->prefix . 'caht_rezervasyonlar',
    $wpdb->prefix . 'caht_ek_yolcular',
    $wpdb->prefix . 'caht_soforler',
];
foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}
*/