<?php
if (!isset($fiyat)) { echo 'Hata: Fiyat bilgisi bulunamadı.'; return; }
?>
<div class="wrap">
    <h1>Sabit Fiyat Düzenle</h1>
    <p class="description">
        <strong>Havalimanı → Bölge</strong> arası sabit fiyat kuralını düzenleyin.
    </p>
    
    <form method="POST" action="<?php echo admin_url('admin.php?page=caht-sabit-fiyatlar'); ?>">
        <?php wp_nonce_field('caht_nonce'); ?>
        <input type="hidden" name="caht_action" value="sabit_fiyat_guncelle">
        <input type="hidden" name="fiyat_id" value="<?php echo intval($fiyat->id); ?>">
        
        <table class="form-table">
            <tr>
                <th scope="row"><label for="arac_id">Araç</label></th>
                <td>
                    <select name="arac_id" id="arac_id" class="regular-text" required>
                        <?php foreach ($araclar_select as $a): ?>
                            <option value="<?php echo $a->id; ?>" <?php selected($fiyat->arac_id, $a->id); ?>><?php echo esc_html($a->ad); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="havalimani_id">Havalimanı <span style="color: #d63638;">*</span></label></th>
                <td>
                    <select name="havalimani_id" id="havalimani_id" class="regular-text" required>
                        <option value="">Havalimanı Seçin</option>
                        <?php foreach ($havalimanlari_select as $h): ?>
                            <option value="<?php echo $h->id; ?>" <?php selected($fiyat->havalimani_id, $h->id); ?>><?php echo esc_html($h->ad); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="bolge_id">Bölge <span style="color: #d63638;">*</span></label></th>
                <td>
                    <select name="bolge_id" id="bolge_id" class="regular-text" required>
                        <option value="">Bölge Seçin</option>
                        <?php foreach ($bolgeler_select as $b): ?>
                            <option value="<?php echo $b->id; ?>" <?php selected($fiyat->bolge_id, $b->id); ?>><?php echo esc_html($b->ad); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="sabit_fiyat">Sabit Fiyat (₺)</label></th>
                <td><input type="number" name="sabit_fiyat" id="sabit_fiyat" class="regular-text" step="0.01" min="0" value="<?php echo esc_attr($fiyat->sabit_fiyat); ?>" required></td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" class="button button-primary">Güncelle</button>
            <a href="<?php echo admin_url('admin.php?page=caht-sabit-fiyatlar'); ?>" class="button">İptal</a>
        </p>
    </form>
</div>