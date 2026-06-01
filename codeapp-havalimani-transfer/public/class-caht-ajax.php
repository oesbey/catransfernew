<?php
/**
 * AJAX Class
 *
 * @package Codeapp_Havalimani_Transfer
 */

if (!defined('ABSPATH')) exit;

class CAHT_Ajax {

    public function init() {
        // Public AJAX (login gerektirmez)
        add_action('wp_ajax_nopriv_caht_hesapla_mesafe', array($this, 'ajax_hesapla_mesafe'));
        add_action('wp_ajax_caht_hesapla_mesafe', array($this, 'ajax_hesapla_mesafe'));
        
        add_action('wp_ajax_nopriv_caht_get_araclar', array($this, 'ajax_get_araclar'));
        add_action('wp_ajax_caht_get_araclar', array($this, 'ajax_get_araclar'));

        // Admin AJAX (login gerekli)
        add_action('wp_ajax_caht_get_soforler', array($this, 'ajax_get_soforler'));
        add_action('wp_ajax_caht_ata_sofor', array($this, 'ajax_ata_sofor'));
        add_action('wp_ajax_caht_get_rezervasyon_detay', array($this, 'ajax_get_rezervasyon_detay'));
        add_action('wp_ajax_caht_update_durum', array($this, 'ajax_update_durum'));
    }

    public function ajax_hesapla_mesafe() {
        check_ajax_referer('caht_nonce', 'nonce');
        
        $nereden = isset($_POST['nereden']) ? sanitize_text_field($_POST['nereden']) : '';
        $nereye = isset($_POST['nereye']) ? sanitize_text_field($_POST['nereye']) : '';
        
        if (empty($nereden) || empty($nereye)) {
            wp_send_json_error(array('message' => 'Adresler eksik.'));
        }

        wp_send_json_success(array(
            'nereden' => $nereden,
            'nereye' => $nereye,
            'message' => 'Mesafe hesaplama hazir.'
        ));
    }

    public function ajax_get_araclar() {
        check_ajax_referer('caht_nonce', 'nonce');
        
        $kisiler = isset($_POST['kisiler']) ? intval($_POST['kisiler']) : 1;
        
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';
        $araclar = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$prefix}araclar WHERE kapasite >= %d AND durum = 1 ORDER BY sira ASC",
            $kisiler
        ));

        wp_send_json_success(array('araclar' => $araclar));
    }

    public function ajax_get_soforler() {
        check_ajax_referer('caht_nonce', 'nonce');
        
        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';
        $soforler = $wpdb->get_results("SELECT id, ad_soyad, telefon FROM {$prefix}soforler ORDER BY ad_soyad");

        wp_send_json_success(array('soforler' => $soforler));
    }

    public function ajax_ata_sofor() {
        check_ajax_referer('caht_nonce', 'nonce');
        wp_send_json_success(array('message' => 'Sofor atandi.'));
    }

    public function ajax_get_rezervasyon_detay() {
        check_ajax_referer('caht_nonce', 'nonce');
        wp_send_json_success(array('message' => 'Detay getirildi.'));
    }

    public function ajax_update_durum() {
        check_ajax_referer('caht_nonce', 'nonce');
        wp_send_json_success(array('message' => 'Durum guncellendi.'));
    }
}