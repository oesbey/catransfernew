<?php
// Silme işlemi
if (isset($_GET['sil']) && is_numeric($_GET['sil'])) {
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'caht_araclar', ['id' => intval($_GET['sil'])]);
    echo '<div class="notice notice-success"><p>Araç silindi.</p></div>';
}
?>

<div class="wrap">
    <h1>Araçlar <a href="<?php echo admin_url('admin.php?page=caht-araclar&action=ekle'); ?>" class="page-title-action">Yeni Araç Ekle</a></h1>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Resim</th>
                <th>Araç Adı</th>
                <th>Kapasite</th>
                <th>Bavul</th>
                <th>Km Fiyat</th>
                <th>Açılış Ücreti</th>
                <th>Sıra</th>
                <th>Durum</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($araclar)): ?>
                <tr><td colspan="10" style="text-align: center;">Henüz araç eklenmemiş.</td></tr>
            <?php else: ?>
                <?php foreach ($araclar as $arac): 
                    $resimler = json_decode($arac->resim ?? '[]', true);
                    $ilk_resim = !empty($resimler[0]) ? $resimler[0] : '';
                ?>
                <tr>
                    <td><?php echo $arac->id; ?></td>
                    <td>
                        <?php if ($ilk_resim): ?>
                            <img src="<?php echo esc_url($ilk_resim); ?>" style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px;">
                        <?php else: ?>
                            <div style="width: 80px; height: 50px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 4px; color: #999; font-size: 11px;">Resim Yok</div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo esc_html($arac->ad); ?></strong></td>
                    <td><?php echo $arac->kapasite; ?> Kişi</td>
                    <td><?php echo $arac->bavul_kapasite; ?> Bavul</td>
                    <td><?php echo number_format($arac->km_fiyat, 2, ',', '.'); ?> ₺</td>
                    <td><?php echo number_format($arac->acilis_ucreti, 2, ',', '.'); ?> ₺</td>
                    <td><?php echo $arac->sira; ?></td>
                    <td><?php echo $arac->durum ? '<span style="color: #1cc88a;">Aktif</span>' : '<span style="color: #e74a3b;">Pasif</span>'; ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=caht-araclar&edit=' . $arac->id); ?>" class="button button-small">Düzenle</a>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=caht-araclar&sil=' . $arac->id), 'caht_nonce'); ?>" class="button button-small" onclick="return confirm('Silmek istediğinize emin misiniz?')" style="color: #e74a3b;">Sil</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>