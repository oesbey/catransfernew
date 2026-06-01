<div class="wrap">
    <h1 class="wp-heading-inline">Bölgeler</h1>
    <a href="<?php echo admin_url('admin.php?page=caht-bolgeler&action=ekle'); ?>" class="page-title-action">Yeni Bölge Ekle</a>
    <hr class="wp-header-end">
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Bölge Adı</th>
                <th>Koordinat Sayısı</th>
                <th>Ham Veri (İlk 50 karakter)</th>
                <th>Oluşturma Tarihi</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($bolgeler)): ?>
                <tr><td colspan="6" style="text-align: center; padding: 30px;">Henüz bölge eklenmemiş.</td></tr>
            <?php else: ?>
                <?php foreach ($bolgeler as $b): 
                    $koord = json_decode($b->koordinatlar, true);
                    $kSayisi = is_array($koord) ? count($koord) : 0;
                    $hamVeri = !empty($b->koordinatlar) ? substr($b->koordinatlar, 0, 50) : 'BOŞ';
                ?>
                <tr>
                    <td><?php echo $b->id; ?></td>
                    <td><strong><?php echo esc_html($b->ad); ?></strong></td>
                    <td><?php echo $kSayisi; ?> nokta</td>
                    <td><code style="font-size: 11px;"><?php echo esc_html($hamVeri); ?></code></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($b->olusturma_tarihi)); ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=caht-bolgeler&action=ekle&edit=' . $b->id); ?>" class="button button-small">Düzenle</a>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=caht-bolgeler&sil=' . $b->id), 'sil_bolge_' . $b->id); ?>" class="button button-small" onclick="return confirm('Bu bölgeyi silmek istediğinize emin misiniz?');" style="color: #e74a3b;">Sil</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
if (isset($_GET['sil']) && is_numeric($_GET['sil']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'sil_bolge_' . intval($_GET['sil']))) {
    $id = intval($_GET['sil']);
    global $wpdb;
    $prefix = $wpdb->prefix . 'caht_';
    $wpdb->delete($prefix . 'bolgeler', array('id' => $id));
    echo '<script>location.href="' . admin_url('admin.php?page=caht-bolgeler') . '";</script>';
    exit;
}
?>