<?php
/**
 * Reservation Detail Page - WooCommerce Integrated
 * 
 * Available variables:
 * $rezervasyon_id (GET parameter)
 */

// Reservation ID check
$rezervasyon_id = isset($_GET['rezervasyon_id']) ? intval($_GET['rezervasyon_id']) : 0;

if ($rezervasyon_id <= 0) {
    echo '<div style="color: red; text-align: center; margin: 20px;">Invalid reservation ID.</div>';
    return;
}

global $wpdb;
$prefix = $wpdb->prefix . 'caht_';

// Fetch reservation info
$rezervasyon = $wpdb->get_row($wpdb->prepare(
    "SELECT r.*, a.ad AS arac_ad, a.resim AS arac_resim, a.kapasite, a.bavul_kapasite
     FROM {$prefix}rezervasyonlar r
     LEFT JOIN {$prefix}araclar a ON r.arac_id = a.id
     WHERE r.id = %d",
    $rezervasyon_id
));

if (!$rezervasyon) {
    echo '<div style="color: red; text-align: center; margin: 20px;">Reservation not found.</div>';
    return;
}

// Fetch additional passengers
$ek_yolcular = $wpdb->get_results($wpdb->prepare(
    "SELECT ad, soyad FROM {$prefix}ek_yolcular WHERE rezervasyon_id = %d",
    $rezervasyon_id
));

// Currency symbol
$para_birimi = $rezervasyon->para_birimi;
if ($para_birimi === 'USD') {
    $sembol = '$';
} elseif ($para_birimi === 'EUR') {
    $sembol = '€';
} else {
    $sembol = '₺';
}

// Exchange rates
$kurlar = CAHT_Public::get_exchange_rates_static();
$usd_kur = $kurlar['usd'];
$eur_kur = $kurlar['eur'];

// Extra service prices from database
$ek_hizmet_fiyatlari = json_decode(get_option('caht_ek_hizmetler', '{}'), true);

// Convert prices to selected currency
$cocuk_koltugu_fiyat = $ek_hizmet_fiyatlari['cocuk_koltugu'] ?? 500;
$karsilama_hizmeti_fiyat = $ek_hizmet_fiyatlari['karsilama_hizmeti'] ?? 300;
$third_bridge_fiyat = $ek_hizmet_fiyatlari['third_bridge'] ?? 700;

if ($para_birimi === 'USD') {
    $cocuk_koltugu_fiyat = $cocuk_koltugu_fiyat / $usd_kur;
    $karsilama_hizmeti_fiyat = $karsilama_hizmeti_fiyat / $usd_kur;
    $third_bridge_fiyat = $third_bridge_fiyat / $usd_kur;
} elseif ($para_birimi === 'EUR') {
    $cocuk_koltugu_fiyat = $cocuk_koltugu_fiyat / $eur_kur;
    $karsilama_hizmeti_fiyat = $karsilama_hizmeti_fiyat / $eur_kur;
    $third_bridge_fiyat = $third_bridge_fiyat / $eur_kur;
}

// Date formatting
$gidis_tarih = date('d.m.Y H:i', strtotime($rezervasyon->gidis_tarih));
$donus_tarih = $rezervasyon->donus_tarih ? date('d.m.Y H:i', strtotime($rezervasyon->donus_tarih)) : null;

// Duration calculation
$hesaplananSureSaat = $rezervasyon->mesafe / 80;
$hesaplananSureDakika = round($hesaplananSureSaat * 60);
$sureYazi = ($hesaplananSureSaat >= 1)
    ? floor($hesaplananSureSaat) . " hr " . ($hesaplananSureDakika % 60) . " min"
    : $hesaplananSureDakika . " min";

// Vehicle images
$arac_resimler = array();
if (!empty($rezervasyon->arac_resim)) {
    $decoded = json_decode($rezervasyon->arac_resim, true);
    if (is_array($decoded) && !empty($decoded)) {
        $arac_resimler = $decoded;
    }
}
$ana_resim = !empty($arac_resimler) ? $arac_resimler[0] : '';

// Payment method name mapping
$payment_methods = array(
    'kredi_karti' => 'Credit Card',
    'nakit' => 'Cash',
    'bacs' => 'Bank Transfer',
    'cod' => 'Cash on Delivery',
    'cheque' => 'Cheque',
    'paypal' => 'PayPal',
    'stripe' => 'Stripe',
);

// Status mapping
$durumlar = array(
    'bekliyor' => array('label' => 'Pending', 'class' => 'pending'),
    'tamamlandi' => array('label' => 'Completed', 'class' => 'completed'),
    'iptal' => array('label' => 'Cancelled', 'class' => 'cancelled'),
    'yeni' => array('label' => 'New', 'class' => 'new'),
    'onaylandi' => array('label' => 'Confirmed', 'class' => 'confirmed'),
);

$durum = $durumlar[$rezervasyon->odeme_durumu] ?? array('label' => ucfirst($rezervasyon->odeme_durumu), 'class' => 'pending');

// Google Maps API key
$google_maps_api_key = get_option('caht_google_maps_api_key', '');
$home_url = home_url('/');
?>

<style>
/* ============================================
   RESERVATION DETAIL - MODERN COMPACT DESIGN
   ============================================ */

:root {
    --caht-header-dark: #1b510d;
    --caht-header-mid: #237e12;
    --caht-header-light: #2d8a5a;
    --caht-primary: #1a1a1a;
    --caht-primary-light: #333333;
    --caht-accent: #4a4a4a;
    --caht-bg: #f8f9fa;
    --caht-card-bg: #ffffff;
    --caht-text: #1a1a1a;
    --caht-text-muted: #6b7280;
    --caht-border: #e5e7eb;
    --caht-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
    --caht-radius: 20px;
    --caht-radius-sm: 14px;
}

.caht-detay-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 40px 20px;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    background: var(--caht-bg);
    min-height: 100vh;
}

/* Hero Banner */
.caht-detay-hero {
    background: linear-gradient(135deg, #1b510d 0%, #237e12 50%, #2d8a5a 100%);
    border-radius: var(--caht-radius);
    padding: 45px 35px;
    text-align: center;
    color: #fff;
    margin-bottom: 30px;
    box-shadow: 0 20px 60px rgba(27, 81, 13, 0.25);
    position: relative;
    overflow: hidden;
}

.caht-detay-hero::before {
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

.caht-detay-hero .check-ring {
    width: 75px;
    height: 75px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 18px auto;
    border: 2px solid rgba(255, 255, 255, 0.2);
    position: relative;
    z-index: 1;
    animation: cahtCheckPop 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes cahtCheckPop {
    0% { transform: scale(0); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.caht-detay-hero .check-ring svg {
    width: 32px;
    height: 32px;
    color: #4ade80;
    fill: none;
    stroke: currentColor;
    stroke-width: 3;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.caht-detay-hero h2 {
    margin: 0 0 8px 0;
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -0.5px;
    position: relative;
    z-index: 1;
    color: white !important;
}

.caht-detay-hero p {
    margin: 0;
    font-size: 15px;
    opacity: 0.9;
    position: relative;
    z-index: 1;
}

.caht-detay-hero .rez-no {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(10px);
    padding: 8px 20px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 600;
    margin-top: 15px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    position: relative;
    z-index: 1;
}

/* Compact Info Card */
.caht-info-card {
    background: var(--caht-card-bg);
    border-radius: var(--caht-radius);
    box-shadow: var(--caht-shadow);
    border: 1px solid rgba(0,0,0,0.04);
    overflow: hidden;
    margin-bottom: 25px;
}

.caht-info-header {
    padding: 25px 30px;
    border-bottom: 1px solid var(--caht-border);
    display: flex;
    align-items: center;
    gap: 12px;
}

.caht-info-header svg {
    width: 24px;
    height: 24px;
    color: var(--caht-header-mid);
    flex-shrink: 0;
}

.caht-info-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: var(--caht-text);
}

/* Info Grid - 2 columns compact */
.caht-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0;
}

.caht-info-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid var(--caht-border);
    border-right: 1px solid var(--caht-border);
    transition: background 0.2s;
}

.caht-info-item:hover {
    background: #fafafa;
}

.caht-info-item:nth-child(2n) {
    border-right: none;
}

.caht-info-item:nth-last-child(-n+2) {
    border-bottom: none;
}

.caht-info-item svg {
    width: 18px;
    height: 18px;
    color: var(--caht-header-mid);
    margin-top: 2px;
    flex-shrink: 0;
}

.caht-info-item .content {
    flex: 1;
    min-width: 0;
}

.caht-info-item .label {
    font-size: 11px;
    font-weight: 600;
    color: var(--caht-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 3px;
}

.caht-info-item .value {
    font-size: 14px;
    font-weight: 600;
    color: var(--caht-text);
    line-height: 1.4;
    word-break: break-word;
}

/* Status Badge */
.caht-detay-durum {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.caht-detay-durum::before {
    content: '';
    width: 7px;
    height: 7px;
    border-radius: 50%;
    display: inline-block;
}

.caht-detay-durum.pending { background: #fef3c7; color: #92400e; }
.caht-detay-durum.pending::before { background: #f59e0b; }

.caht-detay-durum.completed { background: #d1fae5; color: #065f46; }
.caht-detay-durum.completed::before { background: #10b981; }

.caht-detay-durum.cancelled { background: #fee2e2; color: #991b1b; }
.caht-detay-durum.cancelled::before { background: #ef4444; }

.caht-detay-durum.new { background: #dbeafe; color: #1e40af; }
.caht-detay-durum.new::before { background: #3b82f6; }

.caht-detay-durum.confirmed { background: #d1fae5; color: #065f46; }
.caht-detay-durum.confirmed::before { background: #10b981; }

/* Vehicle & Map Section */
.caht-visual-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 25px;
}

@media (max-width: 768px) {
    .caht-visual-section {
        grid-template-columns: 1fr;
    }
}

.caht-visual-card {
    background: var(--caht-card-bg);
    border-radius: var(--caht-radius);
    box-shadow: var(--caht-shadow);
    border: 1px solid rgba(0,0,0,0.04);
    overflow: hidden;
}

.caht-visual-card .card-header {
    padding: 20px 25px;
    border-bottom: 1px solid var(--caht-border);
    display: flex;
    align-items: center;
    gap: 10px;
}

.caht-visual-card .card-header svg {
    width: 20px;
    height: 20px;
    color: var(--caht-header-mid);
    flex-shrink: 0;
}

.caht-visual-card .card-header h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: var(--caht-text);
}

.caht-visual-card .card-body {
    padding: 0;
}

.caht-visual-card .card-body img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    display: block;
}

.caht-visual-card .placeholder {
    width: 100%;
    height: 220px;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    color: var(--caht-accent);
    gap: 8px;
}

.caht-visual-card .placeholder svg {
    width: 50px;
    height: 50px;
    opacity: 0.4;
}

.caht-visual-card .placeholder span {
    font-size: 13px;
    opacity: 0.6;
    font-weight: 500;
}

#caht-detay-map {
    width: 100%;
    height: 220px;
    display: block;
}

/* Price Card */
.caht-price-card {
    background: var(--caht-card-bg);
    border-radius: var(--caht-radius);
    box-shadow: var(--caht-shadow);
    border: 1px solid rgba(0,0,0,0.04);
    overflow: hidden;
    margin-bottom: 25px;
}

.caht-price-card .card-header {
    padding: 20px 25px;
    border-bottom: 1px solid var(--caht-border);
    display: flex;
    align-items: center;
    gap: 10px;
}

.caht-price-card .card-header svg {
    width: 20px;
    height: 20px;
    color: var(--caht-header-mid);
    flex-shrink: 0;
}

.caht-price-card .card-header h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: var(--caht-text);
}

.caht-price-body {
    padding: 25px;
}

.caht-price-total {
    background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%);
    color: #fff;
    padding: 20px;
    border-radius: var(--caht-radius-sm);
    text-align: center;
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}

.caht-price-total::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
    transition: left 0.5s;
}

.caht-price-total:hover::before {
    left: 100%;
}

.caht-price-total .sembol {
    font-size: 20px;
    font-weight: 600;
    margin-right: 2px;
}

.caht-price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    font-size: 14px;
    color: var(--caht-text-muted);
    border-bottom: 1px solid rgba(0,0,0,0.04);
}

.caht-price-row:last-child {
    border-bottom: none;
}

.caht-price-row.total {
    font-weight: 700;
    color: var(--caht-text);
    font-size: 15px;
    border-top: 2px dashed var(--caht-border);
    margin-top: 8px;
    padding-top: 14px;
}

/* Extras Tags */
.caht-extras-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 4px;
}

.caht-extra-tag {
    background: rgba(35, 126, 18, 0.08);
    color: var(--caht-header-mid);
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.caht-extra-tag svg {
    width: 12px;
    height: 12px;
}

/* Passenger Tags */
.caht-passenger-tag {
    background: #f3f4f6;
    color: var(--caht-text);
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.caht-passenger-tag svg {
    width: 12px;
    height: 12px;
    color: var(--caht-text-muted);
}

/* Action Bar */
.caht-action-bar {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.caht-btn {
    padding: 14px 30px;
    border-radius: var(--caht-radius-sm);
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    position: relative;
    overflow: hidden;
}

.caht-btn svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

.caht-btn-primary {
    background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%);
    color: #fff;
}

.caht-btn-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    transition: left 0.5s;
}

.caht-btn-primary:hover {
    transform: scale(1.03);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
}

.caht-btn-primary:hover::before {
    left: 100%;
}

.caht-btn-secondary {
    background: transparent;
    color: var(--caht-primary);
    border: 2px solid var(--caht-border);
}

.caht-btn-secondary:hover {
    border-color: var(--caht-primary);
    background: var(--caht-primary);
    color: #fff;
    transform: scale(1.03);
}

/* Map Error */
.caht-map-error {
    padding: 40px;
    text-align: center;
    color: var(--caht-text-muted);
    background: #fafafa;
}

.caht-map-error svg {
    width: 40px;
    height: 40px;
    margin-bottom: 10px;
    display: block;
    margin-left: auto;
    margin-right: auto;
    color: var(--caht-border);
}

/* Responsive */
@media (max-width: 768px) {
    .caht-detay-container {
        padding: 20px 15px;
    }

    .caht-detay-hero {
        padding: 30px 20px;
    }

    .caht-detay-hero h2 {
        font-size: 22px;
    }

    .caht-info-grid {
        grid-template-columns: 1fr;
    }

    .caht-info-item {
        border-right: none;
    }

    .caht-info-item:nth-last-child(-n+2) {
        border-bottom: 1px solid var(--caht-border);
    }

    .caht-info-item:last-child {
        border-bottom: none;
    }

    .caht-visual-section {
        grid-template-columns: 1fr;
    }

    .caht-action-bar {
        flex-direction: column;
    }

    .caht-btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .caht-detay-hero .check-ring {
        width: 55px;
        height: 55px;
    }

    .caht-detay-hero .check-ring svg {
        width: 24px;
        height: 24px;
    }

    .caht-price-total {
        font-size: 26px;
    }
}
</style>

<div class="caht-detay-container">

    <!-- Hero Banner -->
    <div class="caht-detay-hero">
        <div class="check-ring">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
        </div>
        <h2>Reservation Confirmed!</h2>
        <p>Your transfer has been successfully booked and is being processed.</p>
        <div class="rez-no">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="9" x2="20" y2="9"></line><line x1="4" y1="15" x2="20" y2="15"></line><line x1="10" y1="3" x2="8" y2="21"></line><line x1="16" y1="3" x2="14" y2="21"></line></svg>
            Reservation No: <strong><?php echo intval($rezervasyon->id); ?></strong>
        </div>
    </div>

    <!-- Transfer Info Card -->
    <div class="caht-info-card">
        <div class="caht-info-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            <h3>Transfer Details</h3>
        </div>
        <div class="caht-info-grid">
            <div class="caht-info-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"></path><circle cx="7" cy="17" r="2"></circle><circle cx="17" cy="17" r="2"></circle></svg>
                <div class="content">
                    <div class="label">Vehicle</div>
                    <div class="value"><?php echo esc_html($rezervasyon->arac_ad); ?></div>
                </div>
            </div>
            <div class="caht-info-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                <div class="content">
                    <div class="label">From</div>
                    <div class="value"><?php echo esc_html($rezervasyon->nereden); ?></div>
                </div>
            </div>
            <div class="caht-info-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                <div class="content">
                    <div class="label">To</div>
                    <div class="value"><?php echo esc_html($rezervasyon->nereye); ?></div>
                </div>
            </div>
            <div class="caht-info-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                <div class="content">
                    <div class="label">Distance</div>
                    <div class="value"><?php echo number_format($rezervasyon->mesafe, 2, ',', '.'); ?> km</div>
                </div>
            </div>
            <div class="caht-info-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <div class="content">
                    <div class="label">Passengers</div>
                    <div class="value"><?php echo intval($rezervasyon->kisi_sayisi); ?> Person</div>
                </div>
            </div>
            <div class="caht-info-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <div class="content">
                    <div class="label">Departure</div>
                    <div class="value"><?php echo esc_html($gidis_tarih); ?></div>
                </div>
            </div>
            <?php if ($rezervasyon->gidis_donus && $donus_tarih): ?>
            <div class="caht-info-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <div class="content">
                    <div class="label">Return</div>
                    <div class="value"><?php echo esc_html($donus_tarih); ?></div>
                </div>
            </div>
            <?php endif; ?>
            <div class="caht-info-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                <div class="content">
                    <div class="label">Est. Duration</div>
                    <div class="value"><?php echo esc_html($sureYazi); ?></div>
                </div>
            </div>
            <?php if ($rezervasyon->cocuk_koltugu || $rezervasyon->karsilama_hizmeti || $rezervasyon->third_bridge): ?>
            <div class="caht-info-item" style="grid-column: 1 / -1; border-right: none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                <div class="content">
                    <div class="label">Extra Services</div>
                    <div class="caht-extras-row">
                        <?php if ($rezervasyon->cocuk_koltugu): ?>
                        <span class="caht-extra-tag">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2V5z"></path><path d="M9 13h6"></path><path d="M12 13v4"></path><path d="M7 17h10v4H7z"></path></svg>
                            Child Seat
                        </span>
                        <?php endif; ?>
                        <?php if ($rezervasyon->karsilama_hizmeti): ?>
                        <span class="caht-extra-tag">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            Meet & Greet
                        </span>
                        <?php endif; ?>
                        <?php if ($rezervasyon->third_bridge): ?>
                        <span class="caht-extra-tag">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 20h12M6 20v-8m12 8v-8M6 12l6-6 6 6"></path></svg>
                            3rd Bridge
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($ek_yolcular)): ?>
            <div class="caht-info-item" style="grid-column: 1 / -1; border-right: none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <div class="content">
                    <div class="label">Additional Passengers</div>
                    <div class="caht-extras-row">
                        <?php foreach ($ek_yolcular as $yolcu): ?>
                        <span class="caht-passenger-tag">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <?php echo esc_html($yolcu->ad . ' ' . $yolcu->soyad); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Visual Section: Vehicle + Map -->
    <div class="caht-visual-section">
        <div class="caht-visual-card">
            <div class="card-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"></path><circle cx="7" cy="17" r="2"></circle><circle cx="17" cy="17" r="2"></circle></svg>
                <h4>Vehicle</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($ana_resim)): ?>
                    <img src="<?php echo esc_url($ana_resim); ?>" alt="<?php echo esc_attr($rezervasyon->arac_ad); ?>" loading="lazy">
                <?php else: ?>
                    <div class="placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"></path><circle cx="7" cy="17" r="2"></circle><circle cx="17" cy="17" r="2"></circle></svg>
                        <span>No Image Available</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="caht-visual-card">
            <div class="card-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="1 6 1 22 8 18 16 22 21 18 21 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line></svg>
                <h4>Route Map</h4>
            </div>
            <div class="card-body">
                <div id="caht-detay-map">
                    <div class="caht-map-error">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        Loading map...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Price Card -->
    <div class="caht-price-card">
        <div class="card-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
            <h4>Payment Summary</h4>
        </div>
        <div class="caht-price-body">
            <div class="caht-price-total">
                <span class="sembol"><?php echo esc_html($sembol); ?></span>
                <?php echo number_format($rezervasyon->secilen_fiyat, 2, ',', '.'); ?>
            </div>

            <div class="caht-price-row">
                <span>Base Price</span>
                <span><?php echo esc_html($sembol . number_format($rezervasyon->secilen_fiyat - 
                    ($rezervasyon->cocuk_koltugu ? $cocuk_koltugu_fiyat : 0) - 
                    ($rezervasyon->karsilama_hizmeti ? $karsilama_hizmeti_fiyat : 0) - 
                    ($rezervasyon->third_bridge ? $third_bridge_fiyat : 0), 2, ',', '.')); ?></span>
            </div>
            <?php if ($rezervasyon->cocuk_koltugu): ?>
            <div class="caht-price-row">
                <span>Child Seat</span>
                <span>+<?php echo esc_html($sembol . number_format($cocuk_koltugu_fiyat, 2, ',', '.')); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($rezervasyon->karsilama_hizmeti): ?>
            <div class="caht-price-row">
                <span>Meet & Greet</span>
                <span>+<?php echo esc_html($sembol . number_format($karsilama_hizmeti_fiyat, 2, ',', '.')); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($rezervasyon->third_bridge): ?>
            <div class="caht-price-row">
                <span>3rd Bridge Crossing</span>
                <span>+<?php echo esc_html($sembol . number_format($third_bridge_fiyat, 2, ',', '.')); ?></span>
            </div>
            <?php endif; ?>
            <div class="caht-price-row total">
                <span>Total Amount</span>
                <span><?php echo esc_html($sembol . number_format($rezervasyon->secilen_fiyat, 2, ',', '.')); ?></span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px;">
                <div class="caht-info-item" style="border: 1px solid var(--caht-border); border-radius: var(--caht-radius-sm);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                    <div class="content">
                        <div class="label">Payment Method</div>
                        <div class="value"><?php echo esc_html($payment_methods[$rezervasyon->odeme_yontemi] ?? ucfirst($rezervasyon->odeme_yontemi)); ?></div>
                    </div>
                </div>
                <div class="caht-info-item" style="border: 1px solid var(--caht-border); border-radius: var(--caht-radius-sm);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path></svg>
                    <div class="content">
                        <div class="label">Status</div>
                        <div class="value">
                            <span class="caht-detay-durum <?php echo esc_attr($durum['class']); ?>">
                                <?php echo esc_html($durum['label']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="caht-action-bar">
        <a href="<?php echo esc_url($home_url); ?>" class="caht-btn caht-btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            Back to Home
        </a>
    </div>

</div>

<?php if (!empty($google_maps_api_key)): ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo esc_attr($google_maps_api_key); ?>&libraries=places"></script>
<script>
function initDetayMap() {
    var nereden = '<?php echo esc_js($rezervasyon->nereden); ?>';
    var nereye = '<?php echo esc_js($rezervasyon->nereye); ?>';

    var map = new google.maps.Map(document.getElementById('caht-detay-map'), {
        zoom: 10,
        center: { lat: 41.0082, lng: 28.9784 },
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false,
    });

    var directionsService = new google.maps.DirectionsService();
    var directionsRenderer = new google.maps.DirectionsRenderer({
        suppressMarkers: false,
        polylineOptions: {
            strokeColor: '#237e12',
            strokeWeight: 5,
            strokeOpacity: 0.8
        }
    });
    directionsRenderer.setMap(map);

    var request = {
        origin: nereden,
        destination: nereye,
        travelMode: 'DRIVING'
    };

    directionsService.route(request, function(result, status) {
        if (status === 'OK') {
            directionsRenderer.setDirections(result);
        } else {
            document.getElementById('caht-detay-map').innerHTML = 
                '<div class="caht-map-error">' +
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>' +
                'Route could not be displayed.</div>';
        }
    });
}

if (typeof google !== 'undefined' && google.maps) {
    initDetayMap();
} else {
    window.addEventListener('load', initDetayMap);
}
</script>
<?php endif; ?>