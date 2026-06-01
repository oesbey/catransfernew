<?php
$edit_mode = isset($arac) && $arac;
$form_action = $edit_mode ? admin_url('admin.php?page=caht-araclar') : admin_url('admin.php?page=caht-araclar');

// Mevcut resimleri parse et
$mevcut_resimler = array();
if ($edit_mode && !empty($arac->resim)) {
    $parsed = json_decode($arac->resim, true);
    if (is_array($parsed)) {
        $mevcut_resimler = $parsed;
    }
}
?>

<div class="wrap">
    <h1><?php echo $edit_mode ? 'Araç Düzenle' : 'Yeni Araç Ekle'; ?></h1>
    
    <!-- === enctype="multipart/form-data" EKLENDI === -->
    <form method="post" action="<?php echo $form_action; ?>" id="caht-arac-form" enctype="multipart/form-data">
        <?php wp_nonce_field('caht_nonce'); ?>
        <input type="hidden" name="caht_action" value="arac_kaydet">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="arac_id" value="<?php echo $arac->id; ?>">
        <?php endif; ?>

        <table class="form-table">
            <tr>
                <th><label for="ad">Araç Adı</label></th>
                <td><input type="text" name="ad" id="ad" class="regular-text" value="<?php echo $edit_mode ? esc_attr($arac->ad) : ''; ?>" required></td>
            </tr>
            <tr>
                <th><label for="kapasite">Yolcu Kapasitesi</label></th>
                <td><input type="number" name="kapasite" id="kapasite" class="small-text" value="<?php echo $edit_mode ? esc_attr($arac->kapasite) : '4'; ?>" min="1" required></td>
            </tr>
            <tr>
                <th><label for="bavul_kapasite">Bavul Kapasitesi</label></th>
                <td><input type="number" name="bavul_kapasite" id="bavul_kapasite" class="small-text" value="<?php echo $edit_mode ? esc_attr($arac->bavul_kapasite) : '4'; ?>" min="1" required></td>
            </tr>
            <tr>
                <th><label for="km_fiyat">Km Başına Fiyat (₺)</label></th>
                <td><input type="number" name="km_fiyat" id="km_fiyat" class="regular-text" step="0.01" value="<?php echo $edit_mode ? esc_attr($arac->km_fiyat) : '10.00'; ?>" required></td>
            </tr>
            <tr>
                <th><label for="acilis_ucreti">Açılış Ücreti (₺)</label></th>
                <td><input type="number" name="acilis_ucreti" id="acilis_ucreti" class="regular-text" step="0.01" value="<?php echo $edit_mode ? esc_attr($arac->acilis_ucreti) : '100.00'; ?>" required></td>
            </tr>
            <tr>
                <th><label for="sira">Sıra</label></th>
                <td><input type="number" name="sira" id="sira" class="small-text" value="<?php echo $edit_mode ? esc_attr($arac->sira) : '0'; ?>"></td>
            </tr>
            <tr>
                <th><label for="aciklama">Açıklama</label></th>
                <td><textarea name="aciklama" id="aciklama" class="large-text" rows="4"><?php echo $edit_mode ? esc_textarea($arac->aciklama) : ''; ?></textarea></td>
            </tr>
            
            <!-- === WORDPRESS MEDIA LIBRARY RESIM YUKLEME === -->
            <tr>
                <th><label>Araç Resimleri</label></th>
                <td>
                    <div id="caht-resim-galeri" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:15px;">
                        <?php foreach ($mevcut_resimler as $index => $resim_url): ?>
                        <div class="caht-resim-item" style="position:relative;width:150px;height:150px;border:2px solid #ddd;border-radius:8px;overflow:hidden;">
                            <img src="<?php echo esc_url($resim_url); ?>" style="width:100%;height:100%;object-fit:cover;">
                            <button type="button" class="caht-resim-sil" data-url="<?php echo esc_attr($resim_url); ?>" style="position:absolute;top:5px;right:5px;background:#e74a3b;color:#fff;border:none;border-radius:50%;width:28px;height:28px;cursor:pointer;font-size:12px;line-height:28px;text-align:center;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="button" id="caht-resim-sec" class="button" style="background:#4e73df;color:#fff;border-color:#4e73df;">
                        <i class="fas fa-images"></i> Resim Ekle (Ortam Kütüphanesi)
                    </button>
                    
                    <p class="description">WordPress Ortam Kütüphanesi'nden resim seçin. Birden fazla resim ekleyebilirsiniz.</p>
                    
                    <!-- Gizli input: JSON olarak resim URL'leri -->
                    <input type="hidden" name="resim" id="caht-resim-json" value='<?php echo esc_attr(json_encode($mevcut_resimler)); ?>'>
                    
                    <!-- Debug: JSON içeriğini göster -->
                    <p class="description" style="color:#999;font-size:11px;">
                        Debug - Kaydedilecek JSON: <code id="caht-resim-debug" style="background:#f5f5f5;padding:2px 6px;border-radius:3px;"><?php echo esc_html(json_encode($mevcut_resimler)); ?></code>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button($edit_mode ? 'Güncelle' : 'Kaydet'); ?>
        <a href="<?php echo admin_url('admin.php?page=caht-araclar'); ?>" class="button">İptal</a>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    var resimler = <?php echo json_encode($mevcut_resimler); ?>;
    var frame;

    // Resim ekle butonu
    $('#caht-resim-sec').on('click', function(e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: 'Araç Resmi Seç',
            button: {
                text: 'Resimleri Ekle'
            },
            multiple: true,
            library: {
                type: 'image'
            }
        });

        frame.on('select', function() {
            var attachments = frame.state().get('selection').toJSON();
            
            attachments.forEach(function(attachment) {
                var resimUrl = attachment.url;
                if (attachment.sizes && attachment.sizes.medium) {
                    resimUrl = attachment.sizes.medium.url;
                }
                
                if (resimler.indexOf(resimUrl) === -1) {
                    resimler.push(resimUrl);
                    
                    var html = '<div class="caht-resim-item" style="position:relative;width:150px;height:150px;border:2px solid #ddd;border-radius:8px;overflow:hidden;">' +
                        '<img src="' + resimUrl + '" style="width:100%;height:100%;object-fit:cover;">' +
                        '<button type="button" class="caht-resim-sil" data-url="' + resimUrl + '" style="position:absolute;top:5px;right:5px;background:#e74a3b;color:#fff;border:none;border-radius:50%;width:28px;height:28px;cursor:pointer;font-size:12px;line-height:28px;text-align:center;">' +
                        '<i class="fas fa-times"></i>' +
                        '</button>' +
                        '</div>';
                    
                    $('#caht-resim-galeri').append(html);
                }
            });
            
            var jsonStr = JSON.stringify(resimler);
            $('#caht-resim-json').val(jsonStr);
            $('#caht-resim-debug').text(jsonStr);
            
            console.log('CAHT: Resimler güncellendi:', resimler);
            console.log('CAHT: JSON input değeri:', $('#caht-resim-json').val());
        });

        frame.open();
    });

    // Resim sil
    $(document).on('click', '.caht-resim-sil', function() {
        var url = $(this).data('url');
        resimler = resimler.filter(function(r) { return r !== url; });
        $(this).closest('.caht-resim-item').remove();
        
        var jsonStr = JSON.stringify(resimler);
        $('#caht-resim-json').val(jsonStr);
        $('#caht-resim-debug').text(jsonStr);
        
        console.log('CAHT: Resim silindi. Kalan:', resimler);
    });
    
    // Form submit öncesi kontrol
    $('#caht-arac-form').on('submit', function(e) {
        var jsonVal = $('#caht-resim-json').val();
        console.log('CAHT: Form gönderiliyor. Resim JSON:', jsonVal);
        
        try {
            JSON.parse(jsonVal);
        } catch(err) {
            console.error('CAHT: JSON hatası!', err);
            alert('Resim verisi hatalı. Lütfen sayfayı yenileyip tekrar deneyin.');
            e.preventDefault();
            return false;
        }
    });
});
</script>