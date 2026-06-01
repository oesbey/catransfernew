<?php
$preselected_arac = isset($preselected_arac) ? $preselected_arac : 0;
?>
<div class="wrap">
    <h1>Sabit Fiyat Ekle</h1>
    <p class="description">
        Bir araç için <strong>havalimanı → bölge</strong> arası sabit fiyat tanımlayın.<br>
        Örnek: Sabiha Gökçen Havalimanı → Anadolu 1 Bölgesi = 1000₺<br>
        <span style="color: #d63638;">Not: Hem havalimanı hem bölge seçilmelidir.</span>
    </p>
    
    <form method="POST" action="<?php echo admin_url('admin.php?page=caht-sabit-fiyatlar'); ?>">
        <?php wp_nonce_field('caht_nonce'); ?>
        <input type="hidden" name="caht_action" value="sabit_fiyat_kaydet">
        
        <table class="form-table">
            <tr>
                <th scope="row"><label for="arac_id">Araç</label></th>
                <td>
                    <select name="arac_id" id="arac_id" class="regular-text" required>
                        <option value="">Araç Seçin</option>
                        <?php foreach ($araclar_select as $a): ?>
                            <option value="<?php echo $a->id; ?>" <?php selected($preselected_arac, $a->id); ?>><?php echo esc_html($a->ad); ?></option>
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
                            <option value="<?php echo $h->id; ?>"><?php echo esc_html($h->ad); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">Kalkış veya varış noktası olarak kullanılacak havalimanı.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="bolge_id">Bölge <span style="color: #d63638;">*</span></label></th>
                <td>
                    <select name="bolge_id" id="bolge_id" class="regular-text" required>
                        <option value="">Bölge Seçin</option>
                        <?php foreach ($bolgeler_select as $b): ?>
                            <option value="<?php echo $b->id; ?>"><?php echo esc_html($b->ad); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">Kalkış veya varış noktası olarak kullanılacak bölge.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="sabit_fiyat">Sabit Fiyat (₺)</label></th>
                <td>
                    <input type="number" name="sabit_fiyat" id="sabit_fiyat" class="regular-text" step="0.01" min="0" required>
                    <p class="description">Bu güzergah için sabitlenen fiyat. İki yönlü çalışır (havalimanından bölgeye veya bölgeden havalimanına).</p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" class="button button-primary">Sabit Fiyat Kuralı Ekle</button>
            <a href="<?php echo admin_url('admin.php?page=caht-sabit-fiyatlar'); ?>" class="button">İptal</a>
        </p>
    </form>
</div>