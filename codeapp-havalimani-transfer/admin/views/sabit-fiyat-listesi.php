<?php
if (isset($_GET['sil']) && is_numeric($_GET['sil'])) {
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'caht_fiyat_sabitleri', ['id' => intval($_GET['sil'])]);
    echo '<div class="notice notice-success"><p>Fiyat silindi.</p></div>';
}
?>

<div class="wrap">
    <h1>Sabit Fiyatlar <a href="<?php echo admin_url('admin.php?page=caht-sabit-fiyatlar&action=ekle'); ?>" class="page-title-action">Yeni Fiyat Ekle</a></h1>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Araç</th>
                <th>Havalimanı</th>
                <th>Bölge</th>
                <th>Sabit Fiyat</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($fiyatlar)): ?>
                <tr><td colspan="6" style="text-align: center;">Henüz sabit fiyat eklenmemiş.</td></tr>
            <?php else: ?>
                <?php foreach ($fiyatlar as $f): ?>
                <tr>
                    <td><?php echo $f->id; ?></td>
                    <td><?php echo esc_html($f->arac_adi); ?></td>
                    <td><?php echo $f->havalimani_adi ? esc_html($f->havalimani_adi) : '-'; ?></td>
                    <td><?php echo $f->bolge_adi ? esc_html($f->bolge_adi) : '-'; ?></td>
                    <td><strong><?php echo number_format($f->sabit_fiyat, 2, ',', '.'); ?> ₺</strong></td>
                    <td>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=caht-sabit-fiyatlar&sil=' . $f->id), 'caht_nonce'); ?>" class="button button-small" onclick="return confirm('Silmek istediğinize emin misiniz?')" style="color: #e74a3b;">Sil</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>