<?php
/**
 * Deaktivasyon sınıfı
 */

if (!defined('ABSPATH')) {
    exit;
}

class CAHT_Deactivator {

    public static function deactivate() {
        // Rewrite kurallarını temizle
        flush_rewrite_rules();
    }
}