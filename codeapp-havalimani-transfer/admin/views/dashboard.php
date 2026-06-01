<?php
global $wpdb;
$prefix = $wpdb->prefix . 'caht_';

// İstatistikler
$toplam_rezervasyon = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}rezervasyonlar");
$yeni_rezervasyon = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}rezervasyonlar WHERE durum = 'yeni'");
$tamamlanan = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}rezervasyonlar WHERE durum = 'tamamlanmis'");
$toplam_arac = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}araclar WHERE durum = 1");

// Son 5 rezervasyon
$son_rezervasyonlar = $wpdb->get_results("
    SELECT r.*, a.ad as arac_ad 
    FROM {$prefix}rezervasyonlar r 
    LEFT JOIN {$prefix}araclar a ON r.arac_id = a.id 
    ORDER BY r.olusturma_tarihi DESC 
    LIMIT 5
");
?>

<div class="wrap">
    <h1>Codeapp Havalimanı Transfer - Dashboard</h1>
    
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 30px 0;">
        <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #4e73df;">
            <div style="font-size: 12px; color: #4e73df; font-weight: 700; text-transform: uppercase;">Toplam Rezervasyon</div>
            <div style="font-size: 28px; font-weight: 700; color: #5a5c69; margin-top: 5px;"><?php echo number_format($toplam_rezervasyon); ?></div>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #1cc88a;">
            <div style="font-size: 12px; color: #1cc88a; font-weight: 700; text-transform: uppercase;">Yeni Rezervasyon</div>
            <div style="font-size: 28px; font-weight: 700; color: #5a5c69; margin-top: 5px;"><?php echo number_format($yeni_rezervasyon); ?></div>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #36b9cc;">
            <div style="font-size: 12px; color: #36b9cc; font-weight: 700; text-transform: uppercase;">Tamamlanan</div>
            <div style="font-size: 28px; font-weight: 700; color: #5a5c69; margin-top: 5px;"><?php echo number_format($tamamlanan); ?></div>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #f6c23e;">
            <div style="font-size: 12px; color: #f6c23e; font-weight: 700; text-transform: uppercase;">Aktif Araç</div>
            <div style="font-size: 28px; font-weight: 700; color: #5a5c69; margin-top: 5px;"><?php echo number_format($toplam_arac); ?></div>
        </div>
    </div>

    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; color: #4e73df;">Son Rezervasyonlar</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Yolcu</th>
                    <th>Nereden</th>
                    <th>Nereye</th>
                    <th>Araç</th>
                    <th>Fiyat</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($son_rezervasyonlar)): ?>
                    <tr><td colspan="8" style="text-align: center;">Henüz rezervasyon yok.</td></tr>
                <?php else: ?>
                    <?php foreach ($son_rezervasyonlar as $r): 
                        $durum_renk = [
                            'yeni' => '#4e73df',
                            'tamamlanmis' => '#1cc88a',
                            'iptal' => '#e74a3b',
                            'silinmis' => '#858796'
                        ][$r->durum] ?? '#858796';
                    ?>
                    <tr>
                        <td><?php echo $r->id; ?></td>
                        <td><?php echo esc_html($r->yolcu_ad . ' ' . $r->yolcu_soyad); ?></td>
                        <td><?php echo esc_html($r->nereden); ?></td>
                        <td><?php echo esc_html($r->nereye); ?></td>
                        <td><?php echo esc_html($r->arac_ad); ?></td>
                        <td><?php echo number_format($r->secilen_fiyat, 2, ',', '.'); ?> <?php echo $r->para_birimi === 'USD' ? '$' : ($r->para_birimi === 'EUR' ? '€' : '₺'); ?></td>
                        <td><span style="background: <?php echo $durum_renk; ?>; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 12px;"><?php echo ucfirst($r->durum); ?></span></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($r->olusturma_tarihi)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>