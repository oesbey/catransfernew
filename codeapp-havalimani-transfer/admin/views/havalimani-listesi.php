<?php
if (isset($_GET['sil']) && is_numeric($_GET['sil'])) {
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'caht_havalimanlar', ['id' => intval($_GET['sil'])]);
    echo '<div class="notice notice-success"><p>Havalimanı silindi.</p></div>';
}
?>

<div class="wrap">
    <h1>Havalimanları <a href="<?php echo admin_url('admin.php?page=caht-havalimanlari&action=ekle'); ?>" class="page-title-action">Yeni Havalimanı Ekle</a></h1>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Havalimanı Adı</th>
                <th>Koordinat Sayısı</th>
                <th>Oluşturma Tarihi</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($havalimanlari)): ?>
                <tr><td colspan="5" style="text-align: center;">Henüz havalimanı eklenmemiş.</td></tr>
            <?php else: ?>
                <?php foreach ($havalimanlari as $h): 
                    $koordinatlar = json_decode($h->koordinatlar, true);
                    $koordinat_sayisi = is_array($koordinatlar) ? count($koordinatlar) : 0;
                ?>
                <tr>
                    <td><?php echo $h->id; ?></td>
                    <td><strong><?php echo esc_html($h->ad); ?></strong></td>
                    <td><?php echo $koordinat_sayisi; ?> Nokta</td>
                    <td><?php echo date('d.m.Y', strtotime($h->olusturma_tarihi)); ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=caht-havalimanlari&edit=' . $h->id); ?>" class="button button-small">Düzenle</a>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=caht-havalimanlari&sil=' . $h->id), 'caht_nonce'); ?>" class="button button-small" onclick="return confirm('Silmek istediğinize emin misiniz?')" style="color: #e74a3b;">Sil</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>