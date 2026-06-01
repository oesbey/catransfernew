<?php
if (isset($_GET['sil']) && is_numeric($_GET['sil'])) {
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'caht_bolgeler', ['id' => intval($_GET['sil'])]);
    echo '<div class="notice notice-success"><p>Bölge silindi.</p></div>';
}
?>

<div class="wrap">
    <h1>Bölgeler <a href="<?php echo admin_url('admin.php?page=caht-bolgeler&action=ekle'); ?>" class="page-title-action">Yeni Bölge Ekle</a></h1>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Bölge Adı</th>
                <th>Koordinat Sayısı</th>
                <th>Oluşturma Tarihi</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($bolgeler)): ?>
                <tr><td colspan="5" style="text-align: center;">Henüz bölge eklenmemiş.</td></tr>
            <?php else: ?>
                <?php foreach ($bolgeler as $bolge): 
                    $koordinatlar = json_decode($bolge->koordinatlar, true);
                    $koordinat_sayisi = is_array($koordinatlar) ? count($koordinatlar) : 0;
                ?>
                <tr>
                    <td><?php echo $bolge->id; ?></td>
                    <td><strong><?php echo esc_html($bolge->ad); ?></strong></td>
                    <td><?php echo $koordinat_sayisi; ?> Nokta</td>
                    <td><?php echo date('d.m.Y', strtotime($bolge->olusturma_tarihi)); ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=caht-bolgeler&edit=' . $bolge->id); ?>" class="button button-small">Düzenle</a>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=caht-bolgeler&sil=' . $bolge->id), 'caht_nonce'); ?>" class="button button-small" onclick="return confirm('Silmek istediğinize emin misiniz?')" style="color: #e74a3b;">Sil</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>