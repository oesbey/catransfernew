<?php
/**
 * Transfer Ödeme Sayfası - WooCommerce Entegrasyonlu
 * Checkout Bypass - Success on Same Page
 * 
 * Available variables:
 * $arac, $nereden, $nereye, $mesafe, $kisiler, $gidis_tarih, $donus_tarih, $gidis_donus
 * $para_birimi, $secilen_fiyat, $third_bridge_required, $sure_yazi, $sembol
 * $cocuk_koltugu_fiyat, $karsilama_hizmeti_fiyat, $third_bridge_fiyat
 * $wc_payment_methods
 */

// === DEBUG MODU (hatayı görmek için) ===
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Form gönderildiğinde
$errors = array();
$success = false;
$rezervasyon_id = 0;
$wc_order_id = 0;

// GET parametrelerinden ek hizmetleri oku (sonuc.php'den gelen)
$ek_cocuk_koltugu = isset($_GET['cocuk_koltugu']) && $_GET['cocuk_koltugu'] === '1' ? 1 : 0;
$ek_karsilama = isset($_GET['karsilama_hizmeti']) && $_GET['karsilama_hizmeti'] === '1' ? 1 : 0;
$ek_third_bridge = isset($_GET['third_bridge']) && $_GET['third_bridge'] === '1' ? 1 : 0;

// === KRİTİK DÜZELTME: global $wpdb tanımlaması ===
global $wpdb;
$prefix = $wpdb->prefix . 'caht_';

// Ek hizmet fiyatları (TL cinsinden - veritabanından)
$ek_hizmetler_tl = json_decode(get_option('caht_ek_hizmetler', '{}'), true);
$cocuk_koltugu_tl = isset($ek_hizmetler_tl['cocuk_koltugu']) ? floatval($ek_hizmetler_tl['cocuk_koltugu']) : 500;
$karsilama_hizmeti_tl = isset($ek_hizmetler_tl['karsilama_hizmeti']) ? floatval($ek_hizmetler_tl['karsilama_hizmeti']) : 300;
$third_bridge_tl = isset($ek_hizmetler_tl['third_bridge']) ? floatval($ek_hizmetler_tl['third_bridge']) : 700;

// Döviz kurları
$kurlar = CAHT_Public::get_exchange_rates_static();
$usd_kur = isset($kurlar['usd']) ? floatval($kurlar['usd']) : 34.50;
$eur_kur = isset($kurlar['eur']) ? floatval($kurlar['eur']) : 37.04;

// Seçili para birimine göre sembol ve kur
if ($para_birimi === 'USD') {
    $sembol = '$';
    $kur = $usd_kur;
} elseif ($para_birimi === 'EUR') {
    $sembol = '€';
    $kur = $eur_kur;
} else {
    $para_birimi = 'TL';
    $sembol = '₺';
    $kur = 1;
}

// === Fiyat hesaplama ===
$secilen_fiyat_tl = floatval($secilen_fiyat) * $kur;

// Ek hizmet fiyatlarını seçili para birimine çevir (gösterim için)
$cocuk_koltugu_pb = $cocuk_koltugu_tl / $kur;
$karsilama_hizmeti_pb = $karsilama_hizmeti_tl / $kur;
$third_bridge_pb = $third_bridge_tl / $kur;

// Toplam fiyat hesaplama (TL cinsinden - veritabanına kaydetmek için)
$toplam_fiyat_tl = $secilen_fiyat_tl;
if ($ek_cocuk_koltugu) $toplam_fiyat_tl += $cocuk_koltugu_tl;
if ($ek_karsilama) $toplam_fiyat_tl += $karsilama_hizmeti_tl;
if ($ek_third_bridge) $toplam_fiyat_tl += $third_bridge_tl;

// Toplam fiyat (seçili para biriminde - gösterim için)
$toplam_fiyat_pb = floatval($secilen_fiyat);
if ($ek_cocuk_koltugu) $toplam_fiyat_pb += $cocuk_koltugu_pb;
if ($ek_karsilama) $toplam_fiyat_pb += $karsilama_hizmeti_pb;
if ($ek_third_bridge) $toplam_fiyat_pb += $third_bridge_pb;

// Rezervasyon detay sayfası URL
$detay_page_id = get_option('caht_detay_page_id', 0);
$detay_base_url = $detay_page_id ? get_permalink($detay_page_id) : home_url('/transfer-detay/');
$home_url = home_url('/');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['caht_odeme_submit'])) {
    // Nonce kontrolü
    if (!wp_verify_nonce($_POST['caht_odeme_nonce'], 'caht_odeme_action')) {
        $errors[] = 'Security verification failed.';
    } else {
        $yolcu_ad = sanitize_text_field($_POST['yolcu_ad'] ?? '');
        $yolcu_soyad = sanitize_text_field($_POST['yolcu_soyad'] ?? '');
        $telefon = sanitize_text_field($_POST['telefon'] ?? '');
        $eposta = sanitize_email($_POST['eposta'] ?? '');
        $ek_detay = sanitize_textarea_field($_POST['ek_detay'] ?? '');
        $sozlesme_kabul = isset($_POST['sozlesme_kabul']) ? 1 : 0;
        $odeme_yontemi = sanitize_text_field($_POST['odeme_yontemi'] ?? '');
        $ek_yolcular = isset($_POST['ek_yolcu']) ? $_POST['ek_yolcu'] : array();

        // Validasyon
        if (empty($yolcu_ad)) $errors[] = 'First name is required.';
        if (empty($yolcu_soyad)) $errors[] = 'Last name is required.';
        if (empty($telefon)) $errors[] = 'Phone number is required.';
        if (empty($eposta) || !is_email($eposta)) $errors[] = 'Please enter a valid email address.';
        if (empty($odeme_yontemi)) $errors[] = 'Payment method must be selected.';
        if (!$sozlesme_kabul) $errors[] = "You must accept the Distance Sales Agreement.";

        // Ek yolcu kontrolü
        if (!empty($ek_yolcular)) {
            foreach ($ek_yolcular as $i => $yolcu) {
                if ($i >= ($kisiler - 1)) break;
                if (empty(trim($yolcu['ad'] ?? '')) || empty(trim($yolcu['soyad'] ?? ''))) {
                    $errors[] = 'Additional passenger information is incomplete, please complete all fields.';
                    break;
                }
            }
        }

        if (empty($errors)) {
            // Rezervasyon kaydet
            $wpdb->insert($prefix . 'rezervasyonlar', array(
                'yolcu_ad' => $yolcu_ad,
                'yolcu_soyad' => $yolcu_soyad,
                'telefon' => $telefon,
                'eposta' => $eposta,
                'ek_detay' => $ek_detay,
                'cocuk_koltugu' => $ek_cocuk_koltugu,
                'karsilama_hizmeti' => $ek_karsilama,
                'third_bridge' => $ek_third_bridge,
                'odeme_yontemi' => $odeme_yontemi,
                'nereden' => $nereden,
                'nereye' => $nereye,
                'nereden_lat' => isset($_GET['nereden_lat']) ? floatval($_GET['nereden_lat']) : 0,
                'nereden_lng' => isset($_GET['nereden_lng']) ? floatval($_GET['nereden_lng']) : 0,
                'nereye_lat' => isset($_GET['nereye_lat']) ? floatval($_GET['nereye_lat']) : 0,
                'nereye_lng' => isset($_GET['nereye_lng']) ? floatval($_GET['nereye_lng']) : 0,
                'mesafe' => $mesafe,
                'kisi_sayisi' => $kisiler,
                'gidis_tarih' => $gidis_tarih,
                'donus_tarih' => $donus_tarih,
                'gidis_donus' => $gidis_donus,
                'arac_id' => $arac->id,
                'toplam_fiyat' => $toplam_fiyat_tl,
                'para_birimi' => $para_birimi,
                'secilen_fiyat' => $toplam_fiyat_pb,
                'odeme_durumu' => 'bekliyor',
                'sozlesme_kabul' => $sozlesme_kabul,
                'durum' => 'yeni',
            ));

            $rezervasyon_id = $wpdb->insert_id;

            // Ek yolcuları kaydet
            if (!empty($ek_yolcular) && $rezervasyon_id) {
                foreach ($ek_yolcular as $i => $yolcu) {
                    if ($i >= ($kisiler - 1)) break;
                    if (!empty(trim($yolcu['ad'] ?? '')) && !empty(trim($yolcu['soyad'] ?? ''))) {
                        $wpdb->insert($prefix . 'ek_yolcular', array(
                            'rezervasyon_id' => $rezervasyon_id,
                            'ad' => sanitize_text_field($yolcu['ad']),
                            'soyad' => sanitize_text_field($yolcu['soyad']),
                        ));
                    }
                }
            }

            // WooCommerce siparişi oluştur - ama ödemeye yönlendirme!
            if (class_exists('WooCommerce') && $rezervasyon_id) {
                $order = wc_create_order();

                if ($order && !is_wp_error($order)) {
                    $wc_currency_code = ($para_birimi === 'TL') ? 'TRY' : $para_birimi;

                    // WooCommerce varsayılan para birimini geçici olarak değiştir
                    add_filter('woocommerce_currency', function($currency) use ($wc_currency_code) {
                        return $wc_currency_code;
                    }, 9999);

                    $wc_fiyat = $toplam_fiyat_pb;

                    $product_name = 'Airport Transfer - ' . esc_html($arac->ad);
                    $product = new WC_Product();
                    $product->set_name($product_name);
                    $product->set_regular_price($wc_fiyat);
                    $product->set_price($wc_fiyat);
                    $product->set_virtual(true);
                    $product->set_sold_individually(true);
                    $product_id = $product->save();

                    if ($product_id) {
                        $order->add_product($product, 1);
                    }

                    $order->set_currency($wc_currency_code);
                    $order->set_total($wc_fiyat);
                    $order->set_cart_tax(0);
                    $order->set_shipping_tax(0);
                    $order->set_shipping_total(0);

                    $order->set_billing_first_name($yolcu_ad);
                    $order->set_billing_last_name($yolcu_soyad);
                    $order->set_billing_phone($telefon);
                    $order->set_billing_email($eposta);

                    $order->update_meta_data('_caht_rezervasyon_id', $rezervasyon_id);
                    $order->update_meta_data('_caht_nereden', $nereden);
                    $order->update_meta_data('_caht_nereye', $nereye);
                    $order->update_meta_data('_caht_gidis_tarih', $gidis_tarih);
                    $order->update_meta_data('_caht_arac', $arac->ad);
                    $order->update_meta_data('_caht_mesafe', $mesafe);
                    $order->update_meta_data('_caht_para_birimi', $para_birimi);
                    $order->update_meta_data('_caht_secilen_fiyat', $toplam_fiyat_pb);
                    $order->update_meta_data('_caht_tl_fiyat', $toplam_fiyat_tl);

                    $order->save();

                    $wc_order_id = $order->get_id();

                    // Rezervasyona WooCommerce sipariş ID'sini kaydet
                    $wpdb->update($prefix . 'rezervasyonlar', 
                        array('woo_order_id' => $wc_order_id),
                        array('id' => $rezervasyon_id)
                    );

                    // Ödeme yöntemini ayarla
                    if (!empty($odeme_yontemi)) {
                        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
                        if (isset($available_gateways[$odeme_yontemi])) {
                            $order->set_payment_method($odeme_yontemi);
                            $order->save();
                        }
                    }

                    // === BYPASS: Ödemeye yönlendirme YOK! ===
                    // Sipariş "beklemede" olarak kalacak, admin onaylayacak
                    $order->update_status('pending', 'Reservation created - awaiting admin confirmation.');
                    $order->save();

                } else {
                    $errors[] = 'An error occurred while creating the order.';
                }
            }

            // Başarılı durum
            if (empty($errors) && $rezervasyon_id) {
                $success = true;
            }
        }
    }
}

// Sözleşme metni
$sozlesme = $wpdb->get_row("SELECT mss_tr, mss_en FROM {$prefix}sozlesmeler WHERE id = 1");
$sozlesme_metin = '';
if ($sozlesme && !empty($sozlesme->mss_tr)) {
    $sozlesme_metin = $sozlesme->mss_tr;
} else {
    $sozlesme_metin = 'Distance Sales Agreement text not found.';
}
?>

<style>
/* ============================================
   MODERN PAYMENT PAGE - GREEN HEADER + BLACK
   ============================================ */

:root {
    --caht-header-dark: #1b510d;
    --caht-header-mid: #237e12;
    --caht-header-light: #2d8a5a;
    --caht-primary: #1a1a1a;
    --caht-primary-light: #333333;
    --caht-primary-dark: #0d0d0d;
    --caht-accent: #4a4a4a;
    --caht-bg: #f8f9fa;
    --caht-card-bg: #ffffff;
    --caht-text: #1a1a1a;
    --caht-text-muted: #6b7280;
    --caht-border: #e5e7eb;
    --caht-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
    --caht-shadow-hover: 0 20px 60px rgba(0, 0, 0, 0.12);
    --caht-radius: 20px;
    --caht-radius-sm: 14px;
}

.caht-odeme-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 40px 20px;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    background: var(--caht-bg);
    min-height: 100vh;
}

/* Modern Glassmorphism Header */
.caht-odeme-baslik {
    background: linear-gradient(135deg, #1b510d 0%, #237e12 50%, #2d8a5a 100%);
    color: #fff;
    padding: 45px 40px;
    border-radius: var(--caht-radius);
    margin-bottom: 40px;
    box-shadow: 0 20px 60px rgba(27, 81, 13, 0.25);
    position: relative;
    overflow: hidden;
}

.caht-odeme-baslik::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.caht-odeme-baslik::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
}

.caht-odeme-baslik h2 {
    margin: 0 0 12px 0;
    font-size: 34px;
    font-weight: 800;
    letter-spacing: -0.5px;
    position: relative;
    z-index: 1;
    color: white !important;
}

.caht-odeme-baslik .route {
    opacity: 0.9;
    font-size: 16px;
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.caht-odeme-baslik .route i {
    color: #4ade80;
    font-size: 14px;
}

/* Modern Grid Layout */
.caht-odeme-grid {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 35px;
    align-items: start;
}

@media (max-width: 900px) {
    .caht-odeme-grid {
        grid-template-columns: 1fr;
    }
}

/* Modern Card Design */
.caht-odeme-kart {
    background: var(--caht-card-bg);
    border-radius: var(--caht-radius);
    padding: 40px;
    box-shadow: var(--caht-shadow);
    border: 1px solid rgba(0,0,0,0.04);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.caht-odeme-kart:hover {
    box-shadow: var(--caht-shadow-hover);
    transform: translateY(-2px);
}

.caht-odeme-kart h3 {
    margin: 0 0 28px 0;
    color: var(--caht-text);
    font-size: 22px;
    font-weight: 700;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--caht-border);
    display: flex;
    align-items: center;
    gap: 12px;
}

.caht-odeme-kart h3 i {
    color: var(--caht-header-mid);
    font-size: 20px;
    width: 36px;
    height: 36px;
    background: rgba(35, 126, 18, 0.08);
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Modern Form Elements */
.caht-form-grup {
    margin-bottom: 24px;
    position: relative;
}

.caht-form-grup label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--caht-text);
    font-size: 14px;
    letter-spacing: 0.2px;
}

.caht-form-grup input,
.caht-form-grup textarea,
.caht-form-grup select {
    width: 100%;
    padding: 14px 18px;
    border: 2px solid var(--caht-border);
    border-radius: var(--caht-radius-sm);
    font-size: 15px;
    transition: all 0.3s ease;
    height:45px;
    box-sizing: border-box;
    background: #fafafa;
    color: var(--caht-text);
}

.caht-form-grup input:focus,
.caht-form-grup textarea:focus,
.caht-form-grup select:focus {
    border-color: var(--caht-header-mid);
    background: #fff;
    outline: none;
    box-shadow: 0 0 0 4px rgba(35, 126, 18, 0.08);
}

.caht-form-grup input::placeholder,
.caht-form-grup textarea::placeholder {
    color: #9ca3af;
}

.caht-form-grup textarea {
    resize: vertical;
    min-height: 100px;
}

/* Modern Passenger Row */
.caht-ek-yolcu-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 12px;
}

/* Modern Summary Box */
.caht-ozet {
    background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%);
    border-radius: var(--caht-radius-sm);
    padding: 28px;
    margin-bottom: 28px;
    border: 1px solid var(--caht-border);
}

.caht-ozet-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    font-size: 15px;
    color: var(--caht-text-muted);
}

.caht-ozet-item:last-child {
    border-bottom: none;
    font-weight: 800;
    font-size: 22px;
    color: var(--caht-primary);
    margin-top: 12px;
    padding-top: 16px;
    border-top: 2px solid var(--caht-primary);
}

.caht-ozet-item span:first-child {
    display: flex;
    align-items: center;
    gap: 8px;
}

.caht-ozet-item.ek-hizmet {
    color: var(--caht-header-mid);
    font-size: 14px;
    font-weight: 500;
}

.caht-ozet-item.ek-hizmet i {
    width: 20px;
    text-align: center;
}

/* Modern Payment Methods */
.caht-odeme-metodlari {
    margin-bottom: 28px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.caht-odeme-metod {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    border: 2px solid var(--caht-border);
    border-radius: var(--caht-radius-sm);
    cursor: pointer;
    transition: all 0.3s ease;
    background: #fafafa;
    position: relative;
}

.caht-odeme-metod:hover {
    border-color: #d1d5db;
    background: #f5f5f5;
    transform: translateX(4px);
}

.caht-odeme-metod.selected {
    border-color: var(--caht-header-mid);
    background: rgba(35, 126, 18, 0.04);
    box-shadow: 0 4px 12px rgba(35, 126, 18, 0.1);
}

.caht-odeme-metod input[type="radio"] {
    width: 22px;
    height: 22px;
    cursor: pointer;
    accent-color: var(--caht-header-mid);
    flex-shrink: 0;
}

.caht-odeme-metod div {
    flex: 1;
}

.caht-odeme-metod strong {
    display: block;
    font-size: 15px;
    color: var(--caht-text);
    margin-bottom: 2px;
}

.caht-odeme-metod small {
    display: block;
    color: var(--caht-text-muted);
    font-size: 13px;
}

/* Modern Agreement Box */
.caht-sozlesme-box {
    max-height: 220px;
    overflow-y: auto;
    border: 1px solid var(--caht-border);
    border-radius: var(--caht-radius-sm);
    padding: 20px;
    margin-bottom: 20px;
    background: #fafafa;
    font-size: 13px;
    line-height: 1.7;
    color: var(--caht-text-muted);
}

/* Modern Complete Button - Black */
.caht-btn-tamamla {
    width: 100%;
    padding: 10px;
    background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%);
    color: #fff;
    border: none;
    border-radius: var(--caht-radius-sm);
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    letter-spacing: 0.3px;
    position: relative;
    overflow: hidden;
}

.caht-btn-tamamla::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    transition: left 0.5s;
}

.caht-btn-tamamla:hover {
    transform: scale(1.02);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
}

.caht-btn-tamamla:hover::before {
    left: 100%;
}

/* Modern Error Box */
.caht-error-box {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
    padding: 20px;
    border-radius: var(--caht-radius-sm);
    margin-bottom: 28px;
    font-size: 14px;
}

.caht-error-box ul {
    margin: 0;
    padding-left: 20px;
}

.caht-error-box li {
    margin-bottom: 4px;
}

.caht-error-box li:last-child {
    margin-bottom: 0;
}

/* ============================================
   SUCCESS / CONFIRMATION SCREEN - RADICAL
   ============================================ */

.caht-success-wrapper {
    max-width: 800px;
    margin: 0 auto;
}

.caht-success-hero {
    background: linear-gradient(135deg, #000000 0%, #000000 50%, #424d47 100%);
    border-radius: var(--caht-radius);
    padding: 60px 40px;
    text-align: center;
    color: #fff;
    margin-bottom: 35px;
    box-shadow: 0 20px 60px rgba(27, 81, 13, 0.25);
    position: relative;
    overflow: hidden;
}

.caht-success-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.caht-success-hero .check-ring {
    width: 90px;
    height: 90px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 25px auto;
    border: 2px solid rgba(255, 255, 255, 0.2);
    position: relative;
    z-index: 1;
    animation: cahtCheckPop 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes cahtCheckPop {
    0% { transform: scale(0); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.caht-success-hero .check-ring i {
    font-size: 40px;
    color: #4ade80;
}

.caht-success-hero h2 {
    font-size: 32px;
    font-weight: 800;
    margin: 0 0 12px 0;
    position: relative;
    z-index: 1;
    color: white !important;
}

.caht-success-hero p {
    font-size: 17px;
    opacity: 0.9;
    margin: 0;
    position: relative;
    z-index: 1;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
}

/* Reservation Details Card */
.caht-rezervasyon-detay {
    background: var(--caht-card-bg);
    border-radius: var(--caht-radius);
    padding: 40px;
    box-shadow: var(--caht-shadow);
    border: 1px solid rgba(0,0,0,0.04);
    margin-bottom: 25px;
}

.caht-rezervasyon-detay h3 {
    margin: 0 0 28px 0;
    font-size: 20px;
    font-weight: 700;
    color: var(--caht-text);
    display: flex;
    align-items: center;
    gap: 10px;
}

.caht-rezervasyon-detay h3 i {
    color: var(--caht-header-mid);
    width: 36px;
    height: 36px;
    background: rgba(35, 126, 18, 0.08);
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

/* Detail Grid */
.caht-detay-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.caht-detay-item {
    background: #fafafa;
    border-radius: var(--caht-radius-sm);
    padding: 20px;
    border: 1px solid var(--caht-border);
    transition: all 0.3s ease;
}

.caht-detay-item:hover {
    background: #f5f5f5;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.04);
}

.caht-detay-item .label {
    font-size: 12px;
    font-weight: 600;
    color: var(--caht-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.caht-detay-item .label i {
    font-size: 13px;
    color: var(--caht-header-mid);
}

.caht-detay-item .value {
    font-size: 16px;
    font-weight: 700;
    color: var(--caht-text);
}

.caht-detay-item .value.price {
    font-size: 24px;
    color: var(--caht-primary);
}

.caht-detay-item.full-width {
    grid-column: 1 / -1;
}

/* Extras List */
.caht-detay-extras {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.caht-detay-extra-tag {
    background: rgba(35, 126, 18, 0.08);
    color: var(--caht-header-mid);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Contact Message Card */
.caht-contact-card {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    border-radius: var(--caht-radius);
    padding: 35px 40px;
    color: #fff;
    text-align: center;
    margin-bottom: 25px;
    position: relative;
    overflow: hidden;
}

.caht-contact-card::before {
    content: '';
    position: absolute;
    top: -30%;
    left: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
    border-radius: 50%;
}

.caht-contact-card i {
    font-size: 36px;
    color: #4ade80;
    margin-bottom: 15px;
    position: relative;
    z-index: 1;
}

.caht-contact-card h4 {
    font-size: 20px;
    font-weight: 700;
    margin: 0 0 10px 0;
    position: relative;
    z-index: 1;
    color:white;
}

.caht-contact-card p {
    font-size: 15px;
    opacity: 0.85;
    margin: 0;
    line-height: 1.6;
    position: relative;
    z-index: 1;
}

/* Action Buttons */
.caht-action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.caht-btn-home {
    padding: 16px 35px;
    background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%);
    color: #fff;
    border: none;
    border-radius: var(--caht-radius-sm);
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    position: relative;
    overflow: hidden;
}

.caht-btn-home::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    transition: left 0.5s;
}

.caht-btn-home:hover {
    transform: scale(1.03);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
}

.caht-btn-home:hover::before {
    left: 100%;
}

.caht-btn-detay {
    padding: 16px 35px;
    background: transparent;
    color: var(--caht-primary);
    border: 2px solid var(--caht-border);
    border-radius: var(--caht-radius-sm);
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.caht-btn-detay:hover {
    border-color: var(--caht-primary);
    background: var(--caht-primary);
    color: #fff;
    transform: scale(1.03);
}

/* Responsive */
@media (max-width: 768px) {
    .caht-odeme-container {
        padding: 20px 15px;
    }

    .caht-odeme-baslik {
        padding: 30px 25px;
    }

    .caht-odeme-baslik h2 {
        font-size: 26px;
    }

    .caht-odeme-kart {
        padding: 28px 22px;
    }

    .caht-ek-yolcu-row {
        grid-template-columns: 1fr;
        gap: 8px;
    }

    .caht-ozet {
        padding: 20px;
    }

    .caht-success-hero {
        padding: 40px 25px;
    }

    .caht-success-hero h2 {
        font-size: 24px;
    }

    .caht-rezervasyon-detay {
        padding: 28px 22px;
    }

    .caht-detay-grid {
        grid-template-columns: 1fr;
    }

    .caht-contact-card {
        padding: 28px 22px;
    }

    .caht-action-buttons {
        flex-direction: column;
    }

    .caht-btn-home,
    .caht-btn-detay {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .caht-odeme-baslik h2 {
        font-size: 22px;
    }

    .caht-odeme-kart h3 {
        font-size: 18px;
    }

    .caht-btn-tamamla {
        font-size: 16px;
        padding: 18px;
    }

    .caht-success-hero .check-ring {
        width: 70px;
        height: 70px;
    }

    .caht-success-hero .check-ring i {
        font-size: 30px;
    }
}
</style>

<div class="caht-odeme-container">

    <!-- Header -->
    <div class="caht-odeme-baslik">
        <h2><i class="fas fa-credit-card"></i> Payment Information</h2>
        <div class="route">
            <i class="fas fa-map-marker-alt"></i> <?php echo esc_html($nereden); ?> 
            <i class="fas fa-arrow-right" style="opacity:0.6;"></i> 
            <i class="fas fa-map-pin"></i> <?php echo esc_html($nereye); ?> 
            <span style="background:rgba(255,255,255,0.12);padding:4px 12px;border-radius:20px;font-size:13px;">
                <?php echo number_format($mesafe, 1); ?> km
            </span>
            <span style="background:rgba(255,255,255,0.12);padding:4px 12px;border-radius:20px;font-size:13px;">
                <?php echo isset($sure_yazi) ? esc_html($sure_yazi) : ''; ?>
            </span>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="caht-error-box">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo esc_html($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>

    <!-- ==========================================
         SUCCESS / CONFIRMATION SCREEN
         ========================================== -->
    <div class="caht-success-wrapper">

        <!-- Hero Banner -->
        <div class="caht-success-hero">
            <div class="check-ring">
                <i class="fas fa-check"></i>
            </div>
            <h2>Reservation Confirmed!</h2>
            <p>Your transfer has been successfully booked. Our team will contact you shortly to finalize the details.</p>
        </div>

        <!-- Reservation Details -->
        <div class="caht-rezervasyon-detay">
            <h3><i class="fas fa-clipboard-list"></i> Reservation Details</h3>
            
            <div class="caht-detay-grid">
                <div class="caht-detay-item">
                    <div class="label"><i class="fas fa-hashtag"></i> Reservation No</div>
                    <div class="value">#<?php echo intval($rezervasyon_id); ?></div>
                </div>

                <?php if ($wc_order_id): ?>
                <div class="caht-detay-item">
                    <div class="label"><i class="fas fa-shopping-bag"></i> Order No</div>
                    <div class="value">#<?php echo intval($wc_order_id); ?></div>
                </div>
                <?php endif; ?>

                <div class="caht-detay-item">
                    <div class="label"><i class="fas fa-car"></i> Vehicle</div>
                    <div class="value"><?php echo esc_html($arac->ad); ?></div>
                </div>

                <div class="caht-detay-item">
                    <div class="label"><i class="fas fa-road"></i> Distance</div>
                    <div class="value"><?php echo number_format($mesafe, 1); ?> km</div>
                </div>

                <div class="caht-detay-item">
                    <div class="label"><i class="fas fa-map-marker-alt"></i> From</div>
                    <div class="value"><?php echo esc_html($nereden); ?></div>
                </div>

                <div class="caht-detay-item">
                    <div class="label"><i class="fas fa-map-pin"></i> To</div>
                    <div class="value"><?php echo esc_html($nereye); ?></div>
                </div>

                <div class="caht-detay-item">
                    <div class="label"><i class="fas fa-calendar"></i> Date</div>
                    <div class="value"><?php echo esc_html($gidis_tarih); ?></div>
                </div>

                <?php if ($gidis_donus && !empty($donus_tarih)): ?>
                <div class="caht-detay-item">
                    <div class="label"><i class="fas fa-exchange-alt"></i> Return</div>
                    <div class="value"><?php echo esc_html($donus_tarih); ?></div>
                </div>
                <?php endif; ?>

                <div class="caht-detay-item">
                    <div class="label"><i class="fas fa-users"></i> Passengers</div>
                    <div class="value"><?php echo intval($kisiler); ?> Person</div>
                </div>

                <div class="caht-detay-item">
                    <div class="label"><i class="fas fa-clock"></i> Est. Duration</div>
                    <div class="value"><?php echo isset($sure_yazi) ? esc_html($sure_yazi) : 'N/A'; ?></div>
                </div>

                <div class="caht-detay-item">
                    <div class="label"><i class="fas fa-credit-card"></i> Payment Method</div>
                    <div class="value"><?php 
                        $method_title = 'N/A';
                        if (!empty($wc_payment_methods) && is_array($wc_payment_methods) && isset($wc_payment_methods[$odeme_yontemi])) {
                            $method_title = $wc_payment_methods[$odeme_yontemi]['title'];
                        }
                        echo esc_html($method_title);
                    ?></div>
                </div>

                <div class="caht-detay-item">
                    <div class="label"><i class="fas fa-money-bill-wave"></i> Currency</div>
                    <div class="value"><?php echo esc_html($para_birimi); ?></div>
                </div>

                <?php if ($ek_cocuk_koltugu || $ek_karsilama || $ek_third_bridge): ?>
                <div class="caht-detay-item full-width">
                    <div class="label"><i class="fas fa-plus-circle"></i> Extra Services</div>
                    <div class="caht-detay-extras">
                        <?php if ($ek_cocuk_koltugu): ?>
                        <span class="caht-detay-extra-tag"><i class="fas fa-baby-carriage"></i> Child Seat</span>
                        <?php endif; ?>
                        <?php if ($ek_karsilama): ?>
                        <span class="caht-detay-extra-tag"><i class="fas fa-user-tie"></i> Meet & Greet</span>
                        <?php endif; ?>
                        <?php if ($ek_third_bridge): ?>
                        <span class="caht-detay-extra-tag"><i class="fas fa-bridge"></i> 3rd Bridge</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="caht-detay-item full-width">
                    <div class="label"><i class="fas fa-wallet"></i> Total Amount</div>
                    <div class="value price"><?php echo esc_html($sembol . number_format($toplam_fiyat_pb, 2)); ?></div>
                </div>
            </div>
        </div>

        <!-- Contact Message -->
        <div class="caht-contact-card">
            <i class="fas fa-headset"></i>
            <h4>We Will Contact You Shortly</h4>
            <p>Our customer service team will reach out to you via phone or email within 15 minutes to confirm your reservation details and provide driver information. Thank you for choosing us!</p>
        </div>

        <!-- Action Buttons -->
        <div class="caht-action-buttons">
            <a href="<?php echo esc_url($home_url); ?>" class="caht-btn-home">
                <i class="fas fa-home"></i> Back to Home
            </a>
            <a href="<?php echo esc_url(add_query_arg('rezervasyon_id', $rezervasyon_id, $detay_base_url)); ?>" class="caht-btn-detay">
                <i class="fas fa-eye"></i> View Details
            </a>
        </div>

    </div>

    <?php else: ?>

    <!-- ==========================================
         PAYMENT FORM
         ========================================== -->
    <form method="post" action="" class="caht-odeme-grid">
        <?php wp_nonce_field('caht_odeme_action', 'caht_odeme_nonce'); ?>

        <!-- Left: Passenger Information -->
        <div class="caht-odeme-kart">
            <h3><i class="fas fa-user"></i> Passenger Information</h3>

            <div class="caht-form-grup">
                <label>First Name *</label>
                <input type="text" name="yolcu_ad" placeholder="Enter your first name" value="<?php echo esc_attr($_POST['yolcu_ad'] ?? ''); ?>" required>
            </div>

            <div class="caht-form-grup">
                <label>Last Name *</label>
                <input type="text" name="yolcu_soyad" placeholder="Enter your last name" value="<?php echo esc_attr($_POST['yolcu_soyad'] ?? ''); ?>" required>
            </div>

            <div class="caht-form-grup">
                <label>Phone Number *</label>
                <input type="tel" name="telefon" placeholder="+90 555 123 45 67" value="<?php echo esc_attr($_POST['telefon'] ?? ''); ?>" required>
            </div>

            <div class="caht-form-grup">
                <label>Email Address *</label>
                <input type="email" name="eposta" placeholder="your@email.com" value="<?php echo esc_attr($_POST['eposta'] ?? ''); ?>" required>
            </div>

            <div class="caht-form-grup">
                <label>Additional Details / Note</label>
                <textarea name="ek_detay" placeholder="Flight number, special requests, etc..."><?php echo esc_textarea($_POST['ek_detay'] ?? ''); ?></textarea>
            </div>

            <!-- Additional Passengers -->
            <?php if ($kisiler > 1): ?>
            <h3 style="margin-top:30px;font-size:17px;border:none;padding:0;margin-bottom:16px;">
                <i class="fas fa-users" style="color:var(--caht-header-mid);width:30px;"></i> 
                Additional Passengers (<?php echo intval($kisiler - 1); ?> person)
            </h3>
            <?php for ($i = 0; $i < ($kisiler - 1); $i++): ?>
            <div class="caht-ek-yolcu-row">
                <div class="caht-form-grup" style="margin-bottom:8px;">
                    <input type="text" name="ek_yolcu[<?php echo $i; ?>][ad]" placeholder="First Name" value="<?php echo esc_attr($_POST['ek_yolcu'][$i]['ad'] ?? ''); ?>">
                </div>
                <div class="caht-form-grup" style="margin-bottom:8px;">
                    <input type="text" name="ek_yolcu[<?php echo $i; ?>][soyad]" placeholder="Last Name" value="<?php echo esc_attr($_POST['ek_yolcu'][$i]['soyad'] ?? ''); ?>">
                </div>
            </div>
            <?php endfor; ?>
            <?php endif; ?>
        </div>

        <!-- Right: Summary & Payment -->
        <div class="caht-odeme-kart">
            <h3><i class="fas fa-receipt"></i> Transfer Summary</h3>

            <div class="caht-ozet">
                <div class="caht-ozet-item">
                    <span><i class="fas fa-car"></i> Vehicle</span>
                    <span style="font-weight:600;color:var(--caht-text);"><?php echo esc_html($arac->ad); ?></span>
                </div>
                <div class="caht-ozet-item">
                    <span><i class="fas fa-road"></i> Route</span>
                    <span style="font-weight:600;color:var(--caht-text);"><?php echo number_format($mesafe, 1); ?> km</span>
                </div>
                <div class="caht-ozet-item">
                    <span><i class="fas fa-users"></i> Passengers</span>
                    <span style="font-weight:600;color:var(--caht-text);"><?php echo intval($kisiler); ?></span>
                </div>
                <div class="caht-ozet-item">
                    <span><i class="fas fa-calendar"></i> Date</span>
                    <span style="font-weight:600;color:var(--caht-text);"><?php echo esc_html($gidis_tarih); ?></span>
                </div>
                <?php if ($gidis_donus): ?>
                <div class="caht-ozet-item">
                    <span><i class="fas fa-exchange-alt"></i> Return</span>
                    <span style="font-weight:600;color:var(--caht-text);"><?php echo esc_html($donus_tarih); ?></span>
                </div>
                <?php endif; ?>
                <div class="caht-ozet-item">
                    <span><i class="fas fa-clock"></i> Est. Duration</span>
                    <span style="font-weight:600;color:var(--caht-text);"><?php echo isset($sure_yazi) ? esc_html($sure_yazi) : ''; ?></span>
                </div>

                <!-- Extra Services Summary -->
                <?php if ($ek_cocuk_koltugu): ?>
                <div class="caht-ozet-item ek-hizmet">
                    <span><i class="fas fa-baby-carriage"></i> Child Seat</span>
                    <span>+<?php echo esc_html($sembol . number_format($cocuk_koltugu_pb, 2)); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($ek_karsilama): ?>
                <div class="caht-ozet-item ek-hizmet">
                    <span><i class="fas fa-user-tie"></i> Meet & Greet</span>
                    <span>+<?php echo esc_html($sembol . number_format($karsilama_hizmeti_pb, 2)); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($ek_third_bridge): ?>
                <div class="caht-ozet-item ek-hizmet">
                    <span><i class="fas fa-bridge"></i> 3rd Bridge</span>
                    <span>+<?php echo esc_html($sembol . number_format($third_bridge_pb, 2)); ?></span>
                </div>
                <?php endif; ?>

                <div class="caht-ozet-item">
                    <span><i class="fas fa-wallet"></i> Total Amount</span>
                    <span><?php echo esc_html($sembol . number_format($toplam_fiyat_pb, 2)); ?></span>
                </div>
            </div>

            <!-- Payment Methods -->
            <h3 style="margin-top:0;"><i class="fas fa-wallet"></i> Payment Method</h3>
            <div class="caht-odeme-metodlari">
                <?php if (!empty($wc_payment_methods) && is_array($wc_payment_methods)): ?>
                    <?php foreach ($wc_payment_methods as $id => $method): ?>
                    <label class="caht-odeme-metod <?php echo (isset($_POST['odeme_yontemi']) && $_POST['odeme_yontemi'] === $id) ? 'selected' : ''; ?>">
                        <input type="radio" name="odeme_yontemi" value="<?php echo esc_attr($id); ?>" <?php checked(isset($_POST['odeme_yontemi']) && $_POST['odeme_yontemi'] === $id); ?> required>
                        <div>
                            <strong><?php echo esc_html($method['title']); ?></strong>
                            <?php if (!empty($method['description'])): ?>
                                <small><?php echo esc_html($method['description']); ?></small>
                            <?php endif; ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#991b1b;font-size:14px;line-height:1.6;">
                        <i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>
                        No WooCommerce payment method found. Please enable at least one payment method in WooCommerce settings.
                    </p>
                <?php endif; ?>
            </div>

            <!-- Agreement -->
            <div class="caht-form-grup">
                <div class="caht-sozlesme-box">
                    <?php echo wp_kses_post($sozlesme_metin); ?>
                </div>
                <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;font-weight:400;font-size:14px;color:var(--caht-text-muted);">
                    <input type="checkbox" name="sozlesme_kabul" <?php checked(isset($_POST['sozlesme_kabul'])); ?> required style="margin-top:3px;width:18px;height:18px;accent-color:var(--caht-header-mid);">
                    <span>I have read and agree to the Distance Sales Agreement.</span>
                </label>
            </div>

            <button type="submit" name="caht_odeme_submit" class="caht-btn-tamamla">
                <i class="fas fa-lock"></i> Complete Reservation
            </button>
        </div>
    </form>

    <?php endif; ?>
</div>

<script>
// Payment method selection visual effect
document.querySelectorAll('input[name="odeme_yontemi"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.caht-odeme-metod').forEach(function(el) {
            el.classList.remove('selected');
        });
        this.closest('.caht-odeme-metod').classList.add('selected');
    });
});
</script>