<div class="wrap">
    <h1 class="wp-heading-inline">Havalimanları</h1>
    <a href="<?php echo admin_url('admin.php?page=caht-havalimanlari&action=ekle'); ?>" class="page-title-action">Yeni Havalimanı Ekle</a>
    <hr class="wp-header-end">
    
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
                <tr><td colspan="5" style="text-align: center; padding: 30px;">Henüz havalimanı eklenmemiş.</td></tr>
            <?php else: ?>
                <?php foreach ($havalimanlari as $h): 
                    $koord = json_decode(stripslashes($h->koordinatlar), true);
                    $kSayisi = is_array($koord) ? count($koord) : 0;
                ?>
                <tr>
                    <td><?php echo $h->id; ?></td>
                    <td><strong><?php echo esc_html($h->ad); ?></strong></td>
                    <td><?php echo $kSayisi; ?> nokta</td>
                    <td><?php echo date('d.m.Y H:i', strtotime($h->olusturma_tarihi)); ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=caht-havalimanlari&action=ekle&edit=' . $h->id); ?>" class="button button-small">Düzenle</a>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=caht-havalimanlari&sil=' . $h->id), 'sil_havalimani_' . $h->id); ?>" class="button button-small" onclick="return confirm('Bu havalimanını silmek istediğinize emin misiniz?');" style="color: #e74a3b;">Sil</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
// Silme işlemi
if (isset($_GET['sil']) && is_numeric($_GET['sil']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'sil_havalimani_' . intval($_GET['sil']))) {
    $id = intval($_GET['sil']);
    global $wpdb;
    $prefix = $wpdb->prefix . 'caht_';
    $wpdb->delete($prefix . 'havalimanlar', array('id' => $id));
    echo '<script>location.href="' . admin_url('admin.php?page=caht-havalimanlari') . '";</script>';
    exit;
}
?>