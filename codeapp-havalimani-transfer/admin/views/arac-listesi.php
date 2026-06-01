<?php
global $wpdb;
$prefix = $wpdb->prefix . 'caht_';
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Araçlar</h1>
    <a href="<?php echo admin_url('admin.php?page=caht-araclar&action=ekle'); ?>" class="page-title-action">Yeni Ekle</a>
    <hr class="wp-header-end">
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Resim</th>
                <th>Araç Adı</th>
                <th>Kapasite</th>
                <th>Km Fiyat</th>
                <th>Açılış Ücreti</th>
                <th>Sıra</th>
                <th>Durum</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($araclar as $arac): 
                $resimler = json_decode($arac->resim, true);
                $resim_url = is_array($resimler) && !empty($resimler) ? $resimler[0] : '';
            ?>
            <tr>
                <td><?php echo $arac->id; ?></td>
                <td><?php if ($resim_url): ?><img src="<?php echo esc_url($resim_url); ?>" style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;"><?php else: ?>-<?php endif; ?></td>
                <td><strong><?php echo esc_html($arac->ad); ?></strong></td>
                <td><?php echo $arac->kapasite; ?> Kişi / <?php echo $arac->bavul_kapasite; ?> Bavul</td>
                <td><?php echo number_format($arac->km_fiyat, 2, ',', '.'); ?> ₺</td>
                <td><?php echo number_format($arac->acilis_ucreti, 2, ',', '.'); ?> ₺</td>
                <td><?php echo $arac->sira; ?></td>
                <td><?php echo $arac->durum ? '<span style="color:green;">Aktif</span>' : '<span style="color:red;">Pasif</span>'; ?></td>
                <td>
                    <a href="<?php echo admin_url('admin.php?page=caht-araclar&edit=' . $arac->id); ?>" class="button button-small">Düzenle</a>
                    <a href="<?php echo admin_url('admin.php?page=caht-sabit-fiyatlar&action=ekle&arac_id=' . $arac->id); ?>" class="button button-small" style="background: #4e73df; color: white; border-color: #4e73df;">
                        <span class="dashicons dashicons-money" style="margin-top: 2px;"></span> Fiyat Sabitle
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>