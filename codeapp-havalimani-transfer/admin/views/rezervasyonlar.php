<?php
$durumlar = [
    'yeni' => ['label' => 'Yeni', 'renk' => '#4e73df'],
    'tamamlanmis' => ['label' => 'Tamamlanmış', 'renk' => '#1cc88a'],
    'iptal' => ['label' => 'İptal', 'renk' => '#e74a3b'],
    'silinmis' => ['label' => 'Silinmiş', 'renk' => '#858796']
];
$aktif_durum = isset($_GET['durum']) ? sanitize_text_field($_GET['durum']) : 'yeni';
?>

<div class="wrap">
    <h1>Rezervasyonlar</h1>
    
    <h2 class="nav-tab-wrapper">
        <?php foreach ($durumlar as $key => $d): ?>
            <a href="<?php echo admin_url('admin.php?page=caht-rezervasyonlar&durum=' . $key); ?>" 
               class="nav-tab <?php echo $aktif_durum === $key ? 'nav-tab-active' : ''; ?>"
               style="<?php echo $aktif_durum === $key ? 'border-bottom-color: ' . $d['renk'] . ';' : ''; ?>">
                <?php echo $d['label']; ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Yolcu</th>
                <th>Telefon</th>
                <th>Nereden</th>
                <th>Nereye</th>
                <th>Tarih</th>
                <th>Araç</th>
                <th>Fiyat</th>
                <th>Ödeme</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rezervasyonlar)): ?>
                <tr><td colspan="10" style="text-align: center;">Bu sekmede rezervasyon yok.</td></tr>
            <?php else: ?>
                <?php foreach ($rezervasyonlar as $r): 
                    $odeme_renk = $r->odeme_durumu === 'tamamlandi' ? '#1cc88a' : ($r->odeme_durumu === 'basarisiz' ? '#e74a3b' : '#f6c23e');
                ?>
                <tr>
                    <td><?php echo $r->id; ?></td>
                    <td><strong><?php echo esc_html($r->yolcu_ad . ' ' . $r->yolcu_soyad); ?></strong></td>
                    <td><?php echo esc_html($r->telefon); ?></td>
                    <td><?php echo esc_html($r->nereden); ?></td>
                    <td><?php echo esc_html($r->nereye); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($r->gidis_tarih)); ?></td>
                    <td><?php echo esc_html($r->arac_ad); ?></td>
                    <td><?php echo number_format($r->secilen_fiyat, 2, ',', '.'); ?> <?php echo $r->para_birimi === 'USD' ? '$' : ($r->para_birimi === 'EUR' ? '€' : '₺'); ?></td>
                    <td><span style="background: <?php echo $odeme_renk; ?>; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px;"><?php echo ucfirst($r->odeme_durumu); ?></span></td>
                    <td>
                        <a href="#" class="button button-small detay-btn" data-id="<?php echo $r->id; ?>">Detay</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>