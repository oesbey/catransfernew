<?php
$google_maps_key = get_option('caht_google_maps_api_key', '');
$whatsapp_token = get_option('caht_whatsapp_token', '');
$whatsapp_phone_id = get_option('caht_whatsapp_phone_id', '');
$whatsapp_template = get_option('caht_whatsapp_template_name', 'sofor_bilgilendirem_sistemi');
$ek_hizmetler = json_decode(get_option('caht_ek_hizmetler', '{}'), true);
?>

<div class="wrap">
    <h1>Ayarlar</h1>
    
    <?php if (isset($_GET['saved'])): ?>
        <div class="notice notice-success"><p>Ayarlar kaydedildi.</p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo admin_url('admin.php?page=caht-ayarlar'); ?>">
        <?php wp_nonce_field('caht_nonce'); ?>
        <input type="hidden" name="caht_action" value="ayarlari_kaydet">

        <h2>Google Maps</h2>
        <table class="form-table">
            <tr>
                <th><label for="google_maps_api_key">API Anahtarı</label></th>
                <td>
                    <input type="text" name="google_maps_api_key" id="google_maps_api_key" class="regular-text" value="<?php echo esc_attr($google_maps_key); ?>">
                    <p class="description"><a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>'dan alabilirsiniz.</p>
                </td>
            </tr>
        </table>

        <h2>WhatsApp API</h2>
        <table class="form-table">
            <tr>
                <th><label for="whatsapp_token">Access Token</label></th>
                <td><input type="text" name="whatsapp_token" id="whatsapp_token" class="regular-text" value="<?php echo esc_attr($whatsapp_token); ?>"></td>
            </tr>
            <tr>
                <th><label for="whatsapp_phone_id">Phone Number ID</label></th>
                <td><input type="text" name="whatsapp_phone_id" id="whatsapp_phone_id" class="regular-text" value="<?php echo esc_attr($whatsapp_phone_id); ?>"></td>
            </tr>
            <tr>
                <th><label for="whatsapp_template_name">Template Adı</label></th>
                <td><input type="text" name="whatsapp_template_name" id="whatsapp_template_name" class="regular-text" value="<?php echo esc_attr($whatsapp_template); ?>"></td>
            </tr>
        </table>

        <h2>Ek Hizmet Fiyatları</h2>
        <table class="form-table">
            <tr>
                <th><label for="cocuk_koltugu_fiyat">Çocuk Koltuğu (₺)</label></th>
                <td><input type="number" name="cocuk_koltugu_fiyat" id="cocuk_koltugu_fiyat" class="regular-text" step="0.01" value="<?php echo esc_attr($ek_hizmetler['cocuk_koltugu'] ?? 500); ?>"></td>
            </tr>
            <tr>
                <th><label for="karsilama_hizmeti_fiyat">Karşılama Hizmeti (₺)</label></th>
                <td><input type="number" name="karsilama_hizmeti_fiyat" id="karsilama_hizmeti_fiyat" class="regular-text" step="0.01" value="<?php echo esc_attr($ek_hizmetler['karsilama_hizmeti'] ?? 300); ?>"></td>
            </tr>
            <tr>
                <th><label for="third_bridge_fiyat">3. Köprü (₺)</label></th>
                <td><input type="number" name="third_bridge_fiyat" id="third_bridge_fiyat" class="regular-text" step="0.01" value="<?php echo esc_attr($ek_hizmetler['third_bridge'] ?? 700); ?>"></td>
            </tr>
        </table>

        <?php submit_button('Kaydet'); ?>
    </form>
</div>