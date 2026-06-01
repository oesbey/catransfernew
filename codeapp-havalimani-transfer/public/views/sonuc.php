<?php
/**
 * Transfer Result Page - Vehicle List (Shortcode Compatible)
 * Dark Green Header + Black Modern Theme
 * 
 * Available variables:
 * $araclar, $nereden, $nereye, $mesafe, $kisiler, $gidis_tarih, $donus_tarih, $gidis_donus
 * $para_birimi, $usd_kur, $eur_kur, $wc_currency, $third_bridge_required
 * $nereden_lat, $nereden_lng, $nereye_lat, $nereye_lng
 */

// ============================================
// PARA BİRİMİ DÜZELTME - BUG FIX #1
// ============================================

// Önce GET/POST parametresini al (kullanıcı butona tıkladıysa)
$para_birimi_param = isset($_GET['para_birimi']) ? sanitize_text_field($_GET['para_birimi']) : '';
if (empty($para_birimi_param) && isset($_POST['para_birimi'])) {
    $para_birimi_param = sanitize_text_field($_POST['para_birimi']);
}

// WooCommerce varsayılan kurunu al
$wc_currency_raw = 'TRY';
if (class_exists('WooCommerce')) {
    $wc_currency_raw = get_woocommerce_currency();
}

// WooCommerce kodunu CAHT koduna çevir
$wc_currency = 'TL';
$currency_map = array('TRY' => 'TL', 'USD' => 'USD', 'EUR' => 'EUR');
$wc_currency = isset($currency_map[$wc_currency_raw]) ? $currency_map[$wc_currency_raw] : 'TL';

// Eğer kullanıcı bir para birimi seçtiyse onu kullan, yoksa WooCommerce varsayılanını kullan
if (!empty($para_birimi_param)) {
    $para_birimi = $para_birimi_param;
} else {
    $para_birimi = $wc_currency;
}

// Sembol belirle
$sembol = '₺';
if ($para_birimi === 'USD') {
    $sembol = '$';
} elseif ($para_birimi === 'EUR') {
    $sembol = '€';
}

// ============================================
// KUR VERİLERİ
// ============================================
$kurlar = CAHT_Public::get_exchange_rates_static();
$usd_kur = isset($kurlar['usd']) ? floatval($kurlar['usd']) : 34.50;
$eur_kur = isset($kurlar['eur']) ? floatval($kurlar['eur']) : 37.04;

// ============================================
// SABİT FİYAT KONTROLÜ - BUG FIX #2
// ============================================
global $wpdb;
$prefix = $wpdb->prefix . 'caht_';

/**
 * Noktanın poligon içinde olup olmadığını kontrol et
 * Ray Casting algoritması
 */
function caht_point_in_polygon($lat, $lng, $polygon) {
    $inside = false;
    $n = count($polygon);
    $j = $n - 1;
    
    for ($i = 0; $i < $n; $i++) {
        $xi = $polygon[$i]['lat'];
        $yi = $polygon[$i]['lng'];
        $xj = $polygon[$j]['lat'];
        $yj = $polygon[$j]['lng'];
        
        if ((($yi > $lng) != ($yj > $lng)) &&
            ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi)) {
            $inside = !$inside;
        }
        $j = $i;
    }
    
    return $inside;
}

/**
 * Koordinatın hangi bölgede/havalimanında olduğunu bul
 */
function caht_find_zone($lat, $lng, $zones) {
    if (empty($zones) || $lat == 0 || $lng == 0) {
        return null;
    }
    
    foreach ($zones as $zone) {
        $coords = json_decode($zone->koordinatlar, true);
        if (!is_array($coords) || empty($coords)) {
            continue;
        }
        
        if (caht_point_in_polygon($lat, $lng, $coords)) {
            return $zone->id;
        }
    }
    
    return null;
}

/**
 * Sabit fiyat kuralı var mı kontrol et
 */
function caht_get_sabit_fiyat($arac_id, $havalimani_id, $bolge_id) {
    global $wpdb;
    $prefix = $wpdb->prefix . 'caht_';
    
    if (empty($havalimani_id) || empty($bolge_id)) {
        return null;
    }
    
    $fiyat = $wpdb->get_var($wpdb->prepare(
        "SELECT sabit_fiyat FROM {$prefix}fiyat_sabitleri 
         WHERE arac_id = %d AND havalimani_id = %d AND bolge_id = %d",
        $arac_id, $havalimani_id, $bolge_id
    ));
    
    return $fiyat ? floatval($fiyat) : null;
}

// Tüm bölgeleri ve havalimanlarını çek
$bolgeler = $wpdb->get_results("SELECT id, ad, koordinatlar FROM {$prefix}bolgeler");
$havalimanlari = $wpdb->get_results("SELECT id, ad, koordinatlar FROM {$prefix}havalimanlar");

// Nereden ve Nereye koordinatlarının hangi bölgelerde/havalimanlarında olduğunu bul
$nereden_havalimani_id = null;
$nereden_bolge_id = null;
$nereye_havalimani_id = null;
$nereye_bolge_id = null;

// Nereden: Önce havalimanı kontrolü, sonra bölge
if ($nereden_lat != 0 && $nereden_lng != 0) {
    $nereden_havalimani_id = caht_find_zone($nereden_lat, $nereden_lng, $havalimanlari);
    if (!$nereden_havalimani_id) {
        $nereden_bolge_id = caht_find_zone($nereden_lat, $nereden_lng, $bolgeler);
    }
}

// Nereye: Önce havalimanı kontrolü, sonra bölge
if ($nereye_lat != 0 && $nereye_lng != 0) {
    $nereye_havalimani_id = caht_find_zone($nereye_lat, $nereye_lng, $havalimanlari);
    if (!$nereye_havalimani_id) {
        $nereye_bolge_id = caht_find_zone($nereye_lat, $nereye_lng, $bolgeler);
    }
}

// ============================================
// EK HİZMET FİYATLARI
// ============================================
$ek_hizmetler = json_decode(get_option('caht_ek_hizmetler', '{}'), true);
$cocuk_koltugu_tl = isset($ek_hizmetler['cocuk_koltugu']) ? floatval($ek_hizmetler['cocuk_koltugu']) : 500;
$karsilama_hizmeti_tl = isset($ek_hizmetler['karsilama_hizmeti']) ? floatval($ek_hizmetler['karsilama_hizmeti']) : 300;
$third_bridge_tl = isset($ek_hizmetler['third_bridge']) ? floatval($ek_hizmetler['third_bridge']) : 700;

// Seçili para birimine çevir
if ($para_birimi === 'USD') {
    $cocuk_koltugu_pb = $cocuk_koltugu_tl / $usd_kur;
    $karsilama_hizmeti_pb = $karsilama_hizmeti_tl / $usd_kur;
    $third_bridge_pb = $third_bridge_tl / $usd_kur;
} elseif ($para_birimi === 'EUR') {
    $cocuk_koltugu_pb = $cocuk_koltugu_tl / $eur_kur;
    $karsilama_hizmeti_pb = $karsilama_hizmeti_tl / $eur_kur;
    $third_bridge_pb = $third_bridge_tl / $eur_kur;
} else {
    $cocuk_koltugu_pb = $cocuk_koltugu_tl;
    $karsilama_hizmeti_pb = $karsilama_hizmeti_tl;
    $third_bridge_pb = $third_bridge_tl;
}

// ============================================
// FİYAT DÖNÜŞTÜRME FONKSİYONU
// ============================================
function caht_convert_price($tl_price, $para_birimi, $usd_kur, $eur_kur) {
    if ($para_birimi === 'USD') {
        return $tl_price / $usd_kur;
    } elseif ($para_birimi === 'EUR') {
        return $tl_price / $eur_kur;
    }
    return $tl_price;
}
?>

<style>
/* ============================================
   GREEN HEADER + BLACK MODERN THEME
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
    --caht-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    --caht-shadow-hover: 0 12px 40px rgba(0, 0, 0, 0.15);
    --caht-radius: 16px;
    --caht-radius-sm: 12px;
}

.caht-sonuc-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 30px 20px;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    background: var(--caht-bg);
    min-height: 100vh;
}

/* Header - Original Green Tones */
.caht-sonuc-header {
    background: linear-gradient(135deg, #1b510d 0%, #237e12 50%, #2d8a5a 100%);
    color: #fff;
    padding: 35px 40px;
    border-radius: var(--caht-radius);
    margin-bottom: 35px;
    box-shadow: var(--caht-shadow);
    position: relative;
    overflow: hidden;
}

.caht-sonuc-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(74, 222, 128, 0.15) 0%, transparent 70%);
    border-radius: 50%;
}

.caht-sonuc-header h2 {
    margin: 0 0 18px 0;
    font-size: 32px;
    font-weight: 700;
    letter-spacing: -0.5px;
    position: relative;
    z-index: 1;
    color: white !important;
}

.caht-sonuc-header .route-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    font-size: 15px;
    opacity: 0.95;
    position: relative;
    z-index: 1;
}

.caht-sonuc-header .route-info i {
    color: #4ade80;
    font-size: 14px;
}

.caht-sonuc-header .route-info .badge {
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(10px);
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 13px;
    width:150px;
    font-weight: 500;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Currency Selector */
.caht-para-birimi-secici {
    display: flex;
    gap: 12px;
    margin-bottom: 30px;
    justify-content: flex-end;
}

.caht-para-birimi-secici button {
    padding: 12px 24px;
    border: 2px solid var(--caht-primary);
    background: var(--caht-card-bg);
    color: var(--caht-primary);
    border-radius: var(--caht-radius-sm);
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.caht-para-birimi-secici button i {
    font-size: 13px;
}

.caht-para-birimi-secici button.active,
.caht-para-birimi-secici button:hover {
    background: var(--caht-primary);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}

/* Vehicle List Grid */
.caht-arac-listesi {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 28px;
}

/* Vehicle Card */
.caht-arac-kart {
    background: var(--caht-card-bg);
    border-radius: var(--caht-radius);
    overflow: hidden;
    box-shadow: var(--caht-shadow);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid var(--caht-border);
    display: flex;
    flex-direction: column;
}

.caht-arac-kart:hover {
    transform: translateY(-8px);
    box-shadow: var(--caht-shadow-hover);
    border-color: #d1d5db;
}

/* Image Area - Carousel */
.caht-arac-kart .arac-resim-wrapper {
    position: relative;
    width: 100%;
    height: 240px;
    overflow: hidden;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
}

.caht-arac-kart .arac-resim {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}

.caht-arac-kart:hover .arac-resim {
    transform: scale(1.08);
}

.caht-arac-kart .arac-resim-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    color: var(--caht-primary-light);
    gap: 10px;
}

.caht-arac-kart .arac-resim-placeholder i {
    font-size: 60px;
    opacity: 0.4;
}

.caht-arac-kart .arac-resim-placeholder span {
    font-size: 13px;
    opacity: 0.6;
    font-weight: 500;
}

/* Carousel Controls */
.caht-carousel {
    position: relative;
    width: 100%;
    height: 100%;
}

.caht-carousel-inner {
    display: flex;
    transition: transform 0.5s ease;
    height: 100%;
}

.caht-carousel-item {
    min-width: 100%;
    height: 100%;
}

.caht-carousel-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.caht-carousel-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0,0,0,0.5);
    color: #fff;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    z-index: 3;
    transition: background 0.3s;
}

.caht-carousel-nav:hover {
    background: rgba(0,0,0,0.7);
}

.caht-carousel-nav.prev { left: 10px; }
.caht-carousel-nav.next { right: 10px; }

.caht-carousel-dots {
    position: absolute;
    bottom: 10px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 6px;
    z-index: 3;
}

.caht-carousel-dots .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255,255,255,0.5);
    cursor: pointer;
    transition: all 0.3s;
}

.caht-carousel-dots .dot.active {
    background: #fff;
    width: 20px;
    border-radius: 4px;
}

/* Image Counter */
.caht-arac-kart .arac-resim-sayac {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
    color: #fff;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    z-index: 2;
}

/* Content */
.caht-arac-kart .arac-icerik {
    padding: 28px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.caht-arac-kart .arac-baslik {
    font-size: 24px;
    font-weight: 700;
    color: var(--caht-text);
    margin-bottom: 6px;
    letter-spacing: -0.3px;
}

.caht-arac-kart .arac-aciklama {
    color: var(--caht-text-muted);
    font-size: 14px;
    margin-bottom: 18px;
    line-height: 1.6;
}

/* Features - Black/Grey Badges */
.caht-arac-kart .arac-ozellikler {
    display: flex;
    gap: 10px;
    margin-bottom: 22px;
    flex-wrap: wrap;
}

.caht-arac-kart .arac-ozellik {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--caht-primary);
    background: #f3f4f6;
    padding: 8px 14px;
    border-radius: 30px;
    font-weight: 500;
    border: 1px solid #e5e7eb;
}

.caht-arac-kart .arac-ozellik i {
    color: var(--caht-accent);
    font-size: 12px;
}

/* Price Section - Black Theme */
.caht-arac-kart .fiyat-bolumu {
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    border-radius: var(--caht-radius-sm);
    padding: 20px;
    margin-bottom: 18px;
    border: 1px solid var(--caht-border);
}

.caht-arac-kart .fiyat-ana {
    font-size: 36px;
    font-weight: 800;
    color: var(--caht-primary);
    margin-bottom: 6px;
    display: flex;
    align-items: baseline;
    gap: 2px;
}

.caht-arac-kart .fiyat-ana .sembol {
    font-size: 22px;
    font-weight: 600;
}

.caht-arac-kart .fiyat-detay {
    font-size: 12px;
    color: var(--caht-text-muted);
    line-height: 1.5;
}

/* Sabit Fiyat Badge */
.caht-sabit-fiyat-badge {
    background: linear-gradient(135deg, #1b510d 0%, #237e12 100%);
    color: #fff;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 12px;
}

.caht-sabit-fiyat-badge i {
    font-size: 11px;
}

/* Extra Services */
.caht-arac-kart .ek-hizmetler {
    margin-bottom: 20px;
}

.caht-arac-kart .ek-hizmet-baslik {
    font-size: 13px;
    font-weight: 600;
    color: var(--caht-text-muted);
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.caht-arac-kart .ek-hizmet {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: var(--caht-text);
    margin-bottom: 8px;
    padding: 8px 12px;
    border-radius: 8px;
    transition: background 0.2s;
    cursor: pointer;
}

.caht-arac-kart .ek-hizmet:hover {
    background: #f9fafb;
}

.caht-arac-kart .ek-hizmet input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: var(--caht-primary);
    border-radius: 4px;
}

.caht-arac-kart .ek-hizmet i {
    color: var(--caht-accent);
    width: 18px;
    text-align: center;
}

.caht-arac-kart .ek-hizmet .ek-fiyat {
    margin-left: auto;
    font-weight: 600;
    color: var(--caht-primary);
}

/* Payment Button - Black */
.caht-arac-kart .btn-odeme {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 16px;
       background: linear-gradient(135deg, #1d5a0e 0%, #227e14 100%);
    color: #fff;
    text-align: center;
    text-decoration: none;
    border-radius: var(--caht-radius-sm);
    font-weight: 700;
    font-size: 16px;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    margin-top: auto;
    gap: 10px;
    letter-spacing: 0.3px;
}

.caht-arac-kart .btn-odeme:hover {
    background: linear-gradient(135deg, #0d0d0d 0%, #1a1a1a 100%);
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.caht-arac-kart .btn-odeme i {
    font-size: 14px;
    transition: transform 0.3s;
}

.caht-arac-kart .btn-odeme:hover i {
    transform: translateX(4px);
}

/* Empty Result */
.caht-bos-sonuc {
    text-align: center;
    padding: 80px 20px;
    color: var(--caht-text-muted);
    background: var(--caht-card-bg);
    border-radius: var(--caht-radius);
    box-shadow: var(--caht-shadow);
}

.caht-bos-sonuc i {
    font-size: 70px;
    color: var(--caht-border);
    margin-bottom: 25px;
}

.caht-bos-sonuc h3 {
    font-size: 22px;
    color: var(--caht-text);
    margin-bottom: 10px;
}

/* Responsive */
@media (max-width: 768px) {
    .caht-sonuc-container {
        padding: 15px;
    }

    .caht-sonuc-header {
        padding: 25px 20px;
    }

    .caht-sonuc-header h2 {
        font-size: 24px;
    }

    .caht-arac-listesi {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .caht-para-birimi-secici {
        justify-content: center;
    }

    .caht-arac-kart .arac-resim-wrapper {
        height: 200px;
    }
}

@media (max-width: 480px) {
    .caht-arac-kart .arac-icerik {
        padding: 20px;
    }

    .caht-arac-kart .fiyat-ana {
        font-size: 28px;
    }
}
</style>

<div class="caht-sonuc-container">

    <!-- Header Info -->
    <div class="caht-sonuc-header">
        <h2><i class="fas fa-route"></i> Transfer Results</h2>
        <div class="route-info">
            <span><i class="fas fa-map-marker-alt"></i> <?php echo esc_html($nereden); ?></span>
            <i class="fas fa-arrow-right" style="opacity:0.6;"></i>
            <span><i class="fas fa-map-pin"></i> <?php echo esc_html($nereye); ?></span>
            <span class="badge"><i class="fas fa-road"></i> <?php echo number_format($mesafe, 1); ?> km</span>
            <span class="badge"><i class="fas fa-users"></i> <?php echo intval($kisiler); ?> passengers</span>
            <span class="badge"><i class="fas fa-calendar"></i> <?php echo esc_html($gidis_tarih); ?></span>
            <?php if ($gidis_donus && !empty($donus_tarih)): ?>
                <span class="badge"><i class="fas fa-exchange-alt"></i> Round Trip</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Currency Selector -->
    <div class="caht-para-birimi-secici">
        <button type="button" class="<?php echo $para_birimi === 'TL' ? 'active' : ''; ?>" onclick="cahtChangeCurrency('TL')">
            <span>₺</span> TL
        </button>
        <button type="button" class="<?php echo $para_birimi === 'USD' ? 'active' : ''; ?>" onclick="cahtChangeCurrency('USD')">
            <i class="fas fa-dollar-sign"></i> USD
        </button>
        <button type="button" class="<?php echo $para_birimi === 'EUR' ? 'active' : ''; ?>" onclick="cahtChangeCurrency('EUR')">
            <i class="fas fa-euro-sign"></i> EUR
        </button>
    </div>

    <!-- Vehicle List -->
    <div class="caht-arac-listesi">
        <?php foreach ($araclar as $arac): 
            // ============================================
            // SABİT FİYAT KONTROLÜ - HER ARAÇ İÇİN
            // ============================================
            $sabit_fiyat_tl = null;
            $sabit_fiyat_uygulandi = false;
            
            // Nereden = Havalimanı, Nereye = Bölge
            if ($nereden_havalimani_id && $nereye_bolge_id) {
                $sabit_fiyat_tl = caht_get_sabit_fiyat($arac->id, $nereden_havalimani_id, $nereye_bolge_id);
            }
            // Nereden = Bölge, Nereye = Havalimanı
            elseif ($nereden_bolge_id && $nereye_havalimani_id) {
                $sabit_fiyat_tl = caht_get_sabit_fiyat($arac->id, $nereye_havalimani_id, $nereden_bolge_id);
            }
            // Nereden = Havalimanı, Nereye = Havalimanı (nadir ama olabilir)
            elseif ($nereden_havalimani_id && $nereye_havalimani_id) {
                // İki havalimanı arası - önce havalimanı→bölge mantığıyla dene
                // veya direkt havalimanı→havalimanı kuralı varsa onu kullan
                $sabit_fiyat_tl = caht_get_sabit_fiyat($arac->id, $nereden_havalimani_id, $nereye_havalimani_id);
                if (!$sabit_fiyat_tl) {
                    $sabit_fiyat_tl = caht_get_sabit_fiyat($arac->id, $nereye_havalimani_id, $nereden_havalimani_id);
                }
            }
            
            if ($sabit_fiyat_tl !== null && $sabit_fiyat_tl > 0) {
                $sabit_fiyat_uygulandi = true;
            }

            // ============================================
            // FİYAT HESAPLAMA
            // ============================================
            if ($sabit_fiyat_uygulandi) {
                // Sabit fiyat kullan (TL cinsinden)
                $toplam_fiyat_tl = $sabit_fiyat_tl;
                if ($gidis_donus) {
                    $toplam_fiyat_tl *= 2;
                }
            } else {
                // Normal km hesaplaması
                $toplam_fiyat_tl = ($arac->km_fiyat * $mesafe) + $arac->acilis_ucreti;
                if ($gidis_donus) {
                    $toplam_fiyat_tl *= 2;
                }
            }

            // Seçili para birimine çevir
            $goster_fiyat = caht_convert_price($toplam_fiyat_tl, $para_birimi, $usd_kur, $eur_kur);

            // ============================================
            // GÖRSEL VERİLER
            // ============================================
            $resimler = array();
            if (!empty($arac->resim)) {
                $decoded = json_decode($arac->resim, true);
                if (is_array($decoded) && !empty($decoded)) {
                    $resimler = $decoded;
                }
            }

            $resim_sayisi = count($resimler);
            $has_image = $resim_sayisi > 0;

            // Araç fiyatlarını seçili para birimine çevir (detay gösterimi için)
            $km_fiyat_pb = caht_convert_price($arac->km_fiyat, $para_birimi, $usd_kur, $eur_kur);
            $acilis_ucreti_pb = caht_convert_price($arac->acilis_ucreti, $para_birimi, $usd_kur, $eur_kur);

            // Payment URL extra parameters
            $extra_args = array(
                'nereden' => urlencode($nereden),
                'nereye' => urlencode($nereye),
                'mesafe' => $mesafe,
                'kisi_sayisi' => $kisiler,
                'gidis_tarih' => urlencode($gidis_tarih),
                'donus_tarih' => urlencode($donus_tarih),
                'gidis_donus' => $gidis_donus,
                'thirdBridgeRequired' => $third_bridge_required ? '1' : '0',
                'nereden_lat' => $nereden_lat,
                'nereden_lng' => $nereden_lng,
                'nereye_lat' => $nereye_lat,
                'nereye_lng' => $nereye_lng,
            );

            $odeme_link = caht_odeme_url($arac->id, $para_birimi, number_format($goster_fiyat, 2, '.', ''), $extra_args);
        ?>

        <div class="caht-arac-kart" data-arac-id="<?php echo esc_attr($arac->id); ?>" data-base-fiyat="<?php echo esc_attr(number_format($goster_fiyat, 2, '.', '')); ?>" data-odeme-link="<?php echo esc_url($odeme_link); ?>">

            <!-- Image Area - Carousel -->
            <div class="arac-resim-wrapper">
                <?php if ($has_image): ?>
                    <?php if ($resim_sayisi > 1): ?>
                        <div class="caht-carousel" id="carousel-<?php echo esc_attr($arac->id); ?>">
                            <div class="caht-carousel-inner">
                                <?php foreach ($resimler as $index => $resim_url): ?>
                                <div class="caht-carousel-item" data-index="<?php echo $index; ?>">
                                    <img src="<?php echo esc_url($resim_url); ?>" alt="<?php echo esc_attr($arac->ad); ?>" loading="lazy">
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="caht-carousel-nav prev" onclick="cahtCarouselPrev(<?php echo esc_attr($arac->id); ?>)"><i class="fas fa-chevron-left"></i></button>
                            <button class="caht-carousel-nav next" onclick="cahtCarouselNext(<?php echo esc_attr($arac->id); ?>)"><i class="fas fa-chevron-right"></i></button>
                            <div class="caht-carousel-dots">
                                <?php foreach ($resimler as $index => $resim_url): ?>
                                <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="cahtCarouselGoTo(<?php echo esc_attr($arac->id); ?>, <?php echo $index; ?>)"></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <img src="<?php echo esc_url($resimler[0]); ?>" alt="<?php echo esc_attr($arac->ad); ?>" class="arac-resim" loading="lazy">
                    <?php endif; ?>
                    <div class="arac-resim-sayac">
                        <i class="fas fa-images"></i> <?php echo $resim_sayisi; ?>
                    </div>
                <?php else: ?>
                    <div class="arac-resim-placeholder">
                        <i class="fas fa-car-side"></i>
                        <span>No Image</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="arac-icerik">
                <div class="arac-baslik"><?php echo esc_html($arac->ad); ?></div>

                <?php if (!empty($arac->aciklama)): ?>
                    <div class="arac-aciklama"><?php echo esc_html($arac->aciklama); ?></div>
                <?php endif; ?>

                <?php if ($sabit_fiyat_uygulandi): ?>
                    <div class="caht-sabit-fiyat-badge">
                        <i class="fas fa-tag"></i> Fixed Price Route
                    </div>
                <?php endif; ?>

                <div class="arac-ozellikler">
                    <div class="arac-ozellik">
                        <i class="fas fa-users"></i> <?php echo intval($arac->kapasite); ?> Passengers
                    </div>
                    <div class="arac-ozellik">
                        <i class="fas fa-suitcase"></i> <?php echo intval($arac->bavul_kapasite); ?> Luggage
                    </div>
                    <?php if (!$sabit_fiyat_uygulandi): ?>
                        <div class="arac-ozellik">
                            <i class="fas fa-gas-pump"></i> <?php echo number_format($km_fiyat_pb, 2); ?> <?php echo esc_html($sembol); ?>/km
                        </div>
                        <?php if ($arac->acilis_ucreti > 0): ?>
                            <div class="arac-ozellik">
                                <i class="fas fa-hand-holding-usd"></i> Base fee <?php echo number_format($acilis_ucreti_pb, 2); ?> <?php echo esc_html($sembol); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="fiyat-bolumu">
                    <div class="fiyat-ana">
                        <span class="sembol"><?php echo esc_html($sembol); ?></span>
                        <span class="fiyat-deger"><?php echo number_format($goster_fiyat, 2); ?></span>
                    </div>
                    <div class="fiyat-detay">
                        <?php if ($sabit_fiyat_uygulandi): ?>
                            Fixed price for this route <?php if ($gidis_donus): ?>× 2 (Round Trip)<?php endif; ?>
                        <?php else: ?>
                            <?php echo number_format($mesafe, 1); ?> km × <?php echo number_format($km_fiyat_pb, 2); ?> <?php echo esc_html($sembol); ?>/km 
                            <?php if ($arac->acilis_ucreti > 0): ?>+ Base fee <?php echo number_format($acilis_ucreti_pb, 2); ?> <?php echo esc_html($sembol); ?><?php endif; ?>
                            <?php if ($gidis_donus): ?> × 2 (Round Trip)<?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Extra Services -->
                <div class="ek-hizmetler">
                    <div class="ek-hizmet-baslik">Extra Services</div>
                    <label class="ek-hizmet">
                        <input type="checkbox" class="caht-ek-hizmet" data-fiyat="<?php echo esc_attr(number_format($cocuk_koltugu_pb, 2, '.', '')); ?>" data-tl="<?php echo esc_attr($cocuk_koltugu_tl); ?>" data-ad="cocuk_koltugu">
                        <i class="fas fa-baby-carriage"></i>
                        <span>Child Seat</span>
                        <span class="ek-fiyat">+<?php echo esc_html($sembol . number_format($cocuk_koltugu_pb, 2)); ?></span>
                    </label>
                    <label class="ek-hizmet">
                        <input type="checkbox" class="caht-ek-hizmet" data-fiyat="<?php echo esc_attr(number_format($karsilama_hizmeti_pb, 2, '.', '')); ?>" data-tl="<?php echo esc_attr($karsilama_hizmeti_tl); ?>" data-ad="karsilama_hizmeti">
                        <i class="fas fa-user-tie"></i>
                        <span>Meet & Greet Service</span>
                        <span class="ek-fiyat">+<?php echo esc_html($sembol . number_format($karsilama_hizmeti_pb, 2)); ?></span>
                    </label>
                    <?php if ($third_bridge_required): ?>
                    <label class="ek-hizmet">
                        <input type="checkbox" class="caht-ek-hizmet" data-fiyat="<?php echo esc_attr(number_format($third_bridge_pb, 2, '.', '')); ?>" data-tl="<?php echo esc_attr($third_bridge_tl); ?>" data-ad="third_bridge">
                        <i class="fas fa-bridge"></i>
                        <span>3rd Bridge Crossing</span>
                        <span class="ek-fiyat">+<?php echo esc_html($sembol . number_format($third_bridge_pb, 2)); ?></span>
                    </label>
                    <?php endif; ?>
                </div>

                <a href="<?php echo esc_url($odeme_link); ?>" class="btn-odeme" id="btn-odeme-<?php echo esc_attr($arac->id); ?>">
                    Select & Pay <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <?php endforeach; ?>
    </div>

</div>

<script>
// Currency change
function cahtChangeCurrency(pb) {
    var url = new URL(window.location.href);
    url.searchParams.set('para_birimi', pb);
    window.location.href = url.toString();
}

// Carousel functions
var cahtCarousels = {};

document.querySelectorAll('.caht-carousel').forEach(function(carousel) {
    var aracId = carousel.id.replace('carousel-', '');
    cahtCarousels[aracId] = {
        current: 0,
        total: carousel.querySelectorAll('.caht-carousel-item').length
    };
});

function cahtCarouselUpdate(aracId) {
    var carousel = document.getElementById('carousel-' + aracId);
    if (!carousel) return;
    var inner = carousel.querySelector('.caht-carousel-inner');
    var dots = carousel.querySelectorAll('.caht-carousel-dots .dot');
    inner.style.transform = 'translateX(-' + (cahtCarousels[aracId].current * 100) + '%)';
    dots.forEach(function(dot, idx) {
        dot.classList.toggle('active', idx === cahtCarousels[aracId].current);
    });
}

function cahtCarouselNext(aracId) {
    if (!cahtCarousels[aracId]) return;
    cahtCarousels[aracId].current = (cahtCarousels[aracId].current + 1) % cahtCarousels[aracId].total;
    cahtCarouselUpdate(aracId);
}

function cahtCarouselPrev(aracId) {
    if (!cahtCarousels[aracId]) return;
    cahtCarousels[aracId].current = (cahtCarousels[aracId].current - 1 + cahtCarousels[aracId].total) % cahtCarousels[aracId].total;
    cahtCarouselUpdate(aracId);
}

function cahtCarouselGoTo(aracId, index) {
    if (!cahtCarousels[aracId]) return;
    cahtCarousels[aracId].current = index;
    cahtCarouselUpdate(aracId);
}

// Extra services checkboxes - price update and URL update
document.querySelectorAll('.caht-ek-hizmet').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        var kart = this.closest('.caht-arac-kart');
        var baseFiyat = parseFloat(kart.dataset.baseFiyat);
        var toplamEk = 0;
        var ekHizmetParams = [];

        kart.querySelectorAll('.caht-ek-hizmet:checked').forEach(function(cb) {
            toplamEk += parseFloat(cb.dataset.fiyat);
            ekHizmetParams.push(cb.dataset.ad + '=1');
        });

        var yeniFiyat = baseFiyat + toplamEk;
        var fiyatDeger = kart.querySelector('.fiyat-deger');
        if (fiyatDeger) {
            fiyatDeger.textContent = yeniFiyat.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        // Update payment button URL
        var btn = kart.querySelector('.btn-odeme');
        if (btn) {
            var baseUrl = kart.dataset.odemeLink;
            if (ekHizmetParams.length > 0) {
                var separator = baseUrl.indexOf('?') !== -1 ? '&' : '?';
                btn.href = baseUrl + separator + ekHizmetParams.join('&');
            } else {
                btn.href = baseUrl;
            }
        }
    });
});
</script>