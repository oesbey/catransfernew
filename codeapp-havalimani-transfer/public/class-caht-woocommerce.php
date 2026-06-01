<?php
/**
 * WooCommerce Entegrasyon Sınıfı
 * SADECE: Sipariş durum takibi ve doğru fiyat gösterimi
 * ASLA: Yönlendirme yapmaz - tüm yönlendirme odeme.php'den yapılır
 *
 * @package Codeapp_Havalimani_Transfer
 */

class CAHT_WooCommerce {

    public function init() {
        // WooCommerce sipariş tamamlandığında rezervasyonu güncelle
        add_action('woocommerce_order_status_completed', array($this, 'update_reservation_on_payment'));
        add_action('woocommerce_order_status_processing', array($this, 'update_reservation_on_payment'));

        // Sipariş detay sayfasında (Hesabım > Siparişler) transfer bilgilerini göster
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_transfer_info'));

        // Admin sipariş detayında transfer bilgilerini göster
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'admin_display_transfer_info'));

        // === KRİTİK: Thank you page ve sipariş detayında doğru fiyat gösterimi ===
        add_filter('woocommerce_get_formatted_order_total', array($this, 'format_order_total'), 9999, 2);

        // === KRİTİK: Para birimi sembolünü doğru göster ===
        add_filter('woocommerce_currency_symbol', array($this, 'custom_currency_symbol'), 9999, 2);

        // === KRİTİK: Thank you page'de transfer bilgilerini göster ===
        add_action('woocommerce_thankyou', array($this, 'thankyou_transfer_info'), 5);
    }

    /**
     * Ödeme tamamlandığında rezervasyonu güncelle
     */
    public function update_reservation_on_payment($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $rezervasyon_id = $order->get_meta('_caht_rezervasyon_id');
        if (!$rezervasyon_id) return;

        global $wpdb;
        $prefix = $wpdb->prefix . 'caht_';

        $wpdb->update(
            $prefix . 'rezervasyonlar',
            array(
                'odeme_durumu' => 'tamamlandi',
                'durum' => 'yeni',
                'woo_order_id' => $order_id
            ),
            array('id' => $rezervasyon_id)
        );
    }

    /**
     * === KRİTİK: Sipariş toplamını doğru formatta göster ===
     * WooCommerce'nin kendi formatlamasını override et
     */
    public function format_order_total($formatted_total, $order) {
        $para_birimi = $order->get_meta('_caht_para_birimi');
        $secilen_fiyat = $order->get_meta('_caht_secilen_fiyat');

        // Eğer bizim transfer siparişi değilse, varsayılan davranış
        if (empty($para_birimi) || empty($secilen_fiyat)) {
            return $formatted_total;
        }

        $sembol = '₺';
        if ($para_birimi === 'USD') $sembol = '$';
        elseif ($para_birimi === 'EUR') $sembol = '€';

        // Doğrudan meta'daki fiyatı göster (WooCommerce'nin hesaplamasını kullanma)
        return $sembol . number_format(floatval($secilen_fiyat), 2, ',', '.');
    }

    /**
     * === KRİTİK: Para birimi sembolünü doğru göster ===
     */
    public function custom_currency_symbol($currency_symbol, $currency) {
        // Mevcut siparişin para birimini al
        if (!is_admin() && function_exists('wc_get_order')) {
            $order_id = absint(get_query_var('order-received'));
            if ($order_id) {
                $order = wc_get_order($order_id);
                if ($order) {
                    $para_birimi = $order->get_meta('_caht_para_birimi');
                    if (!empty($para_birimi)) {
                        if ($para_birimi === 'USD') return '$';
                        if ($para_birimi === 'EUR') return '€';
                        if ($para_birimi === 'TL') return '₺';
                    }
                }
            }
        }
        return $currency_symbol;
    }

    /**
     * Rezervasyon detay sayfası URL'sini bul
     */
    private function get_transfer_detay_page_url($rezervasyon_id = 0) {
        $detay_page = get_page_by_path('transfer-detay');
        if ($detay_page) {
            $url = get_permalink($detay_page->ID);
        } else {
            $url = home_url('/transfer-detay/');
        }

        if ($rezervasyon_id) {
            $url = add_query_arg('rezervasyon_id', $rezervasyon_id, $url);
        }

        return $url;
    }

    /**
     * === KRİTİK: Thank you page'de transfer bilgilerini göster ===
     */
    public function thankyou_transfer_info($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $rezervasyon_id = $order->get_meta('_caht_rezervasyon_id');
        if (!$rezervasyon_id) return;

        $nereden = $order->get_meta('_caht_nereden');
        $nereye = $order->get_meta('_caht_nereye');
        $gidis_tarih = $order->get_meta('_caht_gidis_tarih');
        $arac = $order->get_meta('_caht_arac');
        $mesafe = $order->get_meta('_caht_mesafe');
        $para_birimi = $order->get_meta('_caht_para_birimi');
        $secilen_fiyat = $order->get_meta('_caht_secilen_fiyat');

        if (empty($nereden)) return;

        $sembol = '₺';
        if ($para_birimi === 'USD') $sembol = '$';
        elseif ($para_birimi === 'EUR') $sembol = '€';

        $detay_url = $this->get_transfer_detay_page_url($rezervasyon_id);
        ?>
        <div class="caht-thankyou-box" style="margin: 30px 0; padding: 30px; background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border-radius: 16px; border: 2px solid #d1fae5; text-align: center;">
            <div style="margin-bottom: 20px;">
                <i class="fas fa-check-circle" style="font-size: 60px; color: #237d12;"></i>
            </div>
            <h2 style="margin: 0 0 15px 0; color: #1b510d; font-size: 28px;">Transfer Rezervasyonunuz Alındı!</h2>
            <p style="margin: 0 0 20px 0; color: #666; font-size: 16px;">Rezervasyon numaranız: <strong style="color: #1b510d; font-size: 20px;">#<?php echo intval($rezervasyon_id); ?></strong></p>

            <div style="background: #fff; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: left; max-width: 500px; margin-left: auto; margin-right: auto;">
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee;">
                    <span style="color: #666;"><i class="fas fa-route" style="margin-right: 8px; color: #237d12;"></i>Güzergah:</span>
                    <span style="font-weight: 600;"><?php echo esc_html($nereden); ?> → <?php echo esc_html($nereye); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee;">
                    <span style="color: #666;"><i class="fas fa-car" style="margin-right: 8px; color: #237d12;"></i>Araç:</span>
                    <span style="font-weight: 600;"><?php echo esc_html($arac); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee;">
                    <span style="color: #666;"><i class="fas fa-calendar" style="margin-right: 8px; color: #237d12;"></i>Tarih:</span>
                    <span style="font-weight: 600;"><?php echo esc_html($gidis_tarih); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                    <span style="color: #666;"><i class="fas fa-money-bill-wave" style="margin-right: 8px; color: #237d12;"></i>Toplam Tutar:</span>
                    <span style="font-weight: 700; color: #1b510d; font-size: 18px;">
                        <?php echo esc_html($sembol . number_format(floatval($secilen_fiyat), 2)); ?>
                    </span>
                </div>
            </div>

            <a href="<?php echo esc_url($detay_url); ?>" style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #237d12 0%, #1b510d 100%); color: #fff; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 16px; margin-top: 10px;">
                <i class="fas fa-eye"></i> Rezervasyon Detayını Gör
            </a>
        </div>
        <?php
    }

    /**
     * Sipariş detay sayfasında transfer bilgilerini göster
     * (Hesabım > Siparişler sayfasında)
     */
    public function display_transfer_info($order) {
        $rezervasyon_id = $order->get_meta('_caht_rezervasyon_id');
        if (!$rezervasyon_id) return;

        $nereden = $order->get_meta('_caht_nereden');
        $nereye = $order->get_meta('_caht_nereye');
        $gidis_tarih = $order->get_meta('_caht_gidis_tarih');
        $arac = $order->get_meta('_caht_arac');
        $mesafe = $order->get_meta('_caht_mesafe');
        $para_birimi = $order->get_meta('_caht_para_birimi');
        $secilen_fiyat = $order->get_meta('_caht_secilen_fiyat');

        if (empty($nereden)) return;

        $sembol = '₺';
        if ($para_birimi === 'USD') $sembol = '$';
        elseif ($para_birimi === 'EUR') $sembol = '€';

        $detay_url = $this->get_transfer_detay_page_url($rezervasyon_id);
        ?>
        <div class="caht-order-transfer-info" style="margin: 20px 0; padding: 25px; background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border-radius: 16px; border: 2px solid #d1fae5;">
            <h3 style="margin: 0 0 20px 0; color: #1b510d; font-size: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-car" style="font-size: 24px;"></i> Transfer Bilgileri
            </h3>
            <table style="width: 100%; font-size: 15px; border-collapse: collapse;">
                <tr style="border-bottom: 1px solid #d1fae5;">
                    <td style="padding: 10px 0; color: #666; width: 140px; font-weight: 600;"><i class="fas fa-route" style="margin-right: 8px; color: #237d12;"></i>Güzergah:</td>
                    <td style="padding: 10px 0; color: #1a1a2e; font-weight: 500;"><?php echo esc_html($nereden); ?> <i class="fas fa-arrow-right" style="margin: 0 10px; color: #237d12;"></i> <?php echo esc_html($nereye); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #d1fae5;">
                    <td style="padding: 10px 0; color: #666; font-weight: 600;"><i class="fas fa-car" style="margin-right: 8px; color: #237d12;"></i>Araç:</td>
                    <td style="padding: 10px 0; color: #1a1a2e; font-weight: 500;"><?php echo esc_html($arac); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #d1fae5;">
                    <td style="padding: 10px 0; color: #666; font-weight: 600;"><i class="fas fa-road" style="margin-right: 8px; color: #237d12;"></i>Mesafe:</td>
                    <td style="padding: 10px 0; color: #1a1a2e; font-weight: 500;"><?php echo number_format(floatval($mesafe), 1); ?> km</td>
                </tr>
                <tr style="border-bottom: 1px solid #d1fae5;">
                    <td style="padding: 10px 0; color: #666; font-weight: 600;"><i class="fas fa-calendar" style="margin-right: 8px; color: #237d12;"></i>Tarih:</td>
                    <td style="padding: 10px 0; color: #1a1a2e; font-weight: 500;"><?php echo esc_html($gidis_tarih); ?></td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: #666; font-weight: 600;"><i class="fas fa-money-bill-wave" style="margin-right: 8px; color: #237d12;"></i>Tutar:</td>
                    <td style="padding: 10px 0; font-weight: 700; color: #1b510d; font-size: 18px;">
                        <?php echo esc_html($sembol . number_format(floatval($secilen_fiyat), 2)); ?>
                    </td>
                </tr>
            </table>
            <div style="margin-top: 20px; text-align: center;">
                <a href="<?php echo esc_url($detay_url); ?>" style="display: inline-block; padding: 14px 30px; background: linear-gradient(135deg, #237d12 0%, #1b510d 100%); color: #fff; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 15px;">
                    <i class="fas fa-eye"></i> Rezervasyon Detayını Gör
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Admin sipariş detayında transfer bilgilerini göster
     */
    public function admin_display_transfer_info($order) {
        $this->display_transfer_info($order);
    }
}