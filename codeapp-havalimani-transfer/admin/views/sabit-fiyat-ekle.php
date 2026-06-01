<div class="wrap">
    <h1>Yeni Sabit Fiyat Ekle</h1>
    
    <form method="post" action="<?php echo admin_url('admin.php?page=caht-sabit-fiyatlar'); ?>">
        <?php wp_nonce_field('caht_nonce'); ?>
        <input type="hidden" name="caht_action" value="sabit_fiyat_kaydet">

        <table class="form-table">
            <tr>
                <th><label for="arac_id">Araç</label></th>
                <td>
                    <select name="arac_id" id="arac_id" required>
                        <option value="">Seçiniz...</option>
                        <?php foreach ($araclar as $a): ?>
                            <option value="<?php echo $a->id; ?>"><?php echo esc_html($a->ad); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="havalimani_id">Havalimanı (Opsiyonel)</label></th>
                <td>
                    <select name="havalimani_id" id="havalimani_id">
                        <option value="">Seçiniz...</option>
                        <?php foreach ($havalimanlari as $h): ?>
                            <option value="<?php echo $h->id; ?>"><?php echo esc_html($h->ad); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="bolge_id">Bölge (Opsiyonel)</label></th>
                <td>
                    <select name="bolge_id" id="bolge_id">
                        <option value="">Seçiniz...</option>
                        <?php foreach ($bolgeler as $b): ?>
                            <option value="<?php echo $b->id; ?>"><?php echo esc_html($b->ad); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="sabit_fiyat">Sabit Fiyat (₺)</label></th>
                <td><input type="number" name="sabit_fiyat" id="sabit_fiyat" class="regular-text" step="0.01" required></td>
            </tr>
        </table>

        <?php submit_button('Kaydet'); ?>
        <a href="<?php echo admin_url('admin.php?page=caht-sabit-fiyatlar'); ?>" class="button">İptal</a>
    </form>
</div>