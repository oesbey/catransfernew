<?php
global $wpdb;
$prefix = $wpdb->prefix . 'caht_';
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Sabit Fiyatlar</h1>
    <a href="<?php echo admin_url('admin.php?page=caht-sabit-fiyatlar&action=ekle'); ?>" class="page-title-action">Yeni Kural Ekle</a>
    <hr class="wp-header-end">
    
    <div class="tablenav top">
        <form method="GET" action="" class="alignleft actions">
            <input type="hidden" name="page" value="caht-sabit-fiyatlar">
            <select name="havalimani_id" onchange="this.form.submit()">
                <option value="0">Tüm Havalimanları</option>
                <?php foreach ($havalimanlari as $h): ?>
                    <option value="<?php echo $h->id; ?>" <?php selected(isset($_GET['havalimani_id']) ? intval($_GET['havalimani_id']) : 0, $h->id); ?>><?php echo esc_html($h->ad); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="arac_id" onchange="this.form.submit()">
                <option value="0">Tüm Araçlar</option>
                <?php foreach ($araclar as $a): ?>
                    <option value="<?php echo $a->id; ?>" <?php selected(isset($_GET['arac_id']) ? intval($_GET['arac_id']) : 0, $a->id); ?>><?php echo esc_html($a->ad); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($_GET['havalimani_id']) || isset($_GET['arac_id'])): ?>
                <a href="<?php echo admin_url('admin.php?page=caht-sabit-fiyatlar'); ?>" class="button">Sıfırla</a>
            <?php endif; ?>
        </form>
        
        <div class="alignright">
            <button type="button" class="button" onclick="jQuery('#toplu-guncelle-modal').show();">Toplu Güncelle</button>
        </div>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Araç</th>
                <th>Güzergah</th>
                <th>Sabit Fiyat</th>
                <th>Tarih</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($fiyatlar)): ?>
                <tr><td colspan="6" style="text-align: center; padding: 30px;">Kayıt bulunamadı.</td></tr>
            <?php else: ?>
                <?php foreach ($fiyatlar as $f): ?>
                <tr>
                    <td><?php echo $f->id; ?></td>
                    <td><strong><?php echo esc_html($f->arac_adi); ?></strong></td>
                    <td>
                        <span style="color: #2271b1;">✈ <?php echo esc_html($f->havalimani_adi ?: '-'); ?></span>
                        <span style="margin: 0 8px; color: #999;">↔</span>
                        <span style="color: #1cc88a;">📍 <?php echo esc_html($f->bolge_adi ?: '-'); ?></span>
                    </td>
                    <td><strong style="font-size: 16px;"><?php echo number_format($f->sabit_fiyat, 2, ',', '.'); ?> ₺</strong></td>
                    <td><?php echo date('d.m.Y', strtotime($f->olusturma_tarihi)); ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=caht-sabit-fiyatlar&edit=' . $f->id); ?>" class="button button-small">Düzenle</a>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=caht-sabit-fiyatlar&sil=' . $f->id), 'sil_fiyat_' . $f->id); ?>" class="button button-small" onclick="return confirm('Bu kuralı silmek istediğinize emin misiniz?');" style="color: #e74a3b;">Sil</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="toplu-guncelle-modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
    <div style="background: white; margin: 10% auto; padding: 25px; border-radius: 8px; width: 90%; max-width: 500px;">
        <h2>Toplu Fiyat Güncelleme</h2>
        <p class="description" style="color: #e74a3b; margin-bottom: 20px;"><strong>Dikkat:</strong> Bu işlem TÜM sabit fiyatları etkiler!</p>
        
        <form method="POST" action="<?php echo admin_url('admin.php?page=caht-sabit-fiyatlar'); ?>">
            <?php wp_nonce_field('caht_nonce'); ?>
            <input type="hidden" name="caht_action" value="toplu_fiyat_guncelle">
            
            <table class="form-table">
                <tr>
                    <th>İşlem</th>
                    <td>
                        <label><input type="radio" name="yon" value="arttir" checked> Artır</label><br>
                        <label><input type="radio" name="yon" value="azalt"> Azalt</label>
                    </td>
                </tr>
                <tr>
                    <th>Yöntem</th>
                    <td>
                        <label><input type="radio" name="islem_tipi" value="yuzde" checked> Yüzde (%)</label><br>
                        <label><input type="radio" name="islem_tipi" value="tutar"> Sabit Tutar (₺)</label>
                    </td>
                </tr>
                <tr>
                    <th><label for="miktar">Miktar</label></th>
                    <td><input type="number" name="miktar" id="miktar" step="0.01" min="0.01" required class="regular-text"></td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary" onclick="return confirm('Emin misiniz?');">Uygula</button>
                <button type="button" class="button" onclick="jQuery('#toplu-guncelle-modal').hide();">İptal</button>
            </p>
        </form>
    </div>
</div>

<?php
if (isset($_GET['sil']) && is_numeric($_GET['sil']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'sil_fiyat_' . intval($_GET['sil']))) {
    $id = intval($_GET['sil']);
    $wpdb->query($wpdb->prepare("DELETE FROM {$prefix}fiyat_sabitleri WHERE id = %d", $id));
    echo '<script>location.href="' . admin_url('admin.php?page=caht-sabit-fiyatlar') . '";</script>';
    exit;
}
?>