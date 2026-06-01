<?php
$durumlar = array(
    'yeni' => array('label' => 'Yeni', 'icon' => 'bell', 'color' => '#4e73df'),
    'tamamlanmis' => array('label' => 'Tamamlanmış', 'icon' => 'yes', 'color' => '#1cc88a'),
    'iptal' => array('label' => 'İptal', 'icon' => 'no', 'color' => '#e74a3b'),
    'silinmis' => array('label' => 'Silinmiş', 'icon' => 'trash', 'color' => '#858796')
);
$aktif_durum = isset($_GET['durum']) ? sanitize_text_field($_GET['durum']) : 'yeni';
if (!isset($durumlar[$aktif_durum])) $aktif_durum = 'yeni';

global $wpdb;
$prefix = $wpdb->prefix . 'caht_';
$counts = array();
foreach ($durumlar as $key => $val) {
    $counts[$key] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}rezervasyonlar WHERE durum = %s", $key));
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Rezervasyonlar</h1>
    <hr class="wp-header-end">
    
    <h2 class="nav-tab-wrapper">
        <?php foreach ($durumlar as $key => $info): ?>
            <a href="<?php echo admin_url('admin.php?page=caht-rezervasyonlar&durum=' . $key); ?>" 
               class="nav-tab <?php echo $aktif_durum === $key ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-<?php echo $info['icon']; ?>" style="color: <?php echo $info['color']; ?>;"></span>
                <?php echo $info['label']; ?>
                <span class="count" style="background: <?php echo $info['color']; ?>; color: white; padding: 1px 7px; border-radius: 10px; font-size: 11px; margin-left: 4px;"><?php echo $counts[$key]; ?></span>
            </a>
        <?php endforeach; ?>
    </h2>
    
    <div class="tablenav top" style="margin-top: 15px;">
        <div class="alignleft actions bulkactions">
            <select id="toplu-islem" style="margin-right: 5px;">
                <option value="">Toplu İşlem Seçin</option>
                <?php if ($aktif_durum !== 'tamamlanmis'): ?><option value="tamamlanmis">Tamamlandı Olarak İşaretle</option><?php endif; ?>
                <?php if ($aktif_durum !== 'iptal'): ?><option value="iptal">İptal Et</option><?php endif; ?>
                <?php if ($aktif_durum !== 'silinmis'): ?><option value="silinmis">Çöp Kutusuna Taşı</option><?php endif; ?>
                <?php if ($aktif_durum !== 'yeni'): ?><option value="yeni">Yeni Olarak İşaretle</option><?php endif; ?>
                <?php if ($aktif_durum === 'silinmis'): ?><option value="kalici_sil" style="color: red;">KALICI SİL</option><?php endif; ?>
            </select>
            <button type="button" id="toplu-islem-uygula" class="button action">Uygula</button>
        </div>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <td class="manage-column column-cb check-column"><input type="checkbox" id="select-all"></td>
                <th>ID</th>
                <th>Yolcu</th>
                <th>İletişim</th>
                <th>Güzergah</th>
                <th>Tarih</th>
                <th>Araç</th>
                <th>Fiyat</th>
                <th>Ödeme</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rezervasyonlar)): ?>
                <tr><td colspan="10" style="text-align: center; padding: 40px; color: #999;">
                    <span class="dashicons dashicons-info" style="font-size: 40px; width: 40px; height: 40px; display: block; margin: 0 auto 10px;"></span>
                    Bu sekmede rezervasyon bulunmuyor.
                </td></tr>
            <?php else: ?>
                <?php foreach ($rezervasyonlar as $r): 
                    $odeme_renk = $r->odeme_durumu === 'tamamlandi' ? '#1cc88a' : ($r->odeme_durumu === 'basarisiz' ? '#e74a3b' : '#f6c23e');
                    $sembol = $r->para_birimi === 'USD' ? '$' : ($r->para_birimi === 'EUR' ? '€' : '₺');
                ?>
                <tr data-id="<?php echo $r->id; ?>">
                    <th class="check-column"><input type="checkbox" class="rez-checkbox" value="<?php echo $r->id; ?>"></th>
                    <td><?php echo $r->id; ?></td>
                    <td><strong><?php echo esc_html($r->yolcu_ad . ' ' . $r->yolcu_soyad); ?></strong></td>
                    <td><?php echo esc_html($r->telefon); ?><br><small><?php echo esc_html($r->eposta); ?></small></td>
                    <td>
                        <small><strong>K:</strong> <?php echo esc_html($r->nereden); ?></small><br>
                        <span class="dashicons dashicons-arrow-down-alt" style="color: #ccc; font-size: 12px;"></span><br>
                        <small><strong>V:</strong> <?php echo esc_html($r->nereye); ?></small>
                    </td>
                    <td><?php echo date('d.m.Y H:i', strtotime($r->gidis_tarih)); ?></td>
                    <td><?php echo esc_html($r->arac_ad); ?></td>
                    <td><strong><?php echo number_format($r->secilen_fiyat, 2, ',', '.'); ?> <?php echo $sembol; ?></strong></td>
                    <td><span style="color: <?php echo $odeme_renk; ?>; font-weight: bold; text-transform: uppercase; font-size: 11px;"><?php echo esc_html($r->odeme_durumu); ?></span></td>
                    <td>
                        <button type="button" class="button button-small btn-detay" data-id="<?php echo $r->id; ?>">
                            <span class="dashicons dashicons-visibility" style="margin-top: 2px;"></span> Detay
                        </button>
                        <?php if ($aktif_durum === 'silinmis'): ?>
                            <button type="button" class="button button-small btn-kalici-sil" data-id="<?php echo $r->id; ?>" style="color: #e74a3b; border-color: #e74a3b;">
                                <span class="dashicons dashicons-trash" style="margin-top: 2px;"></span> Sil
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="detay-modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6);">
    <div style="background: white; margin: 3% auto; padding: 0; border-radius: 8px; width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto; position: relative;">
        <div style="padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f8f9fc;">
            <h2 style="margin: 0; font-size: 18px;">Transfer Detayları <span id="modal-rez-id" style="color: #999; font-size: 14px; font-weight: normal;"></span></h2>
            <button type="button" class="button modal-kapat" style="background: none; border: none; font-size: 24px; cursor: pointer; padding: 0; line-height: 1;">&times;</button>
        </div>
        <div style="padding: 25px;" id="modal-icerik"><p>Yükleniyor...</p></div>
        <div style="padding: 15px 25px; border-top: 1px solid #eee; background: #f8f9fc; text-align: right;">
            <button type="button" class="button modal-kapat">Kapat</button>
            <button type="button" class="button button-primary" id="modal-durum-guncelle" style="margin-left: 10px;">Durum Değiştir</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#select-all').on('change', function() {
        $('.rez-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    $('.btn-detay').on('click', function() {
        var id = $(this).data('id');
        $('#modal-rez-id').text('#' + id);
        $('#detay-modal').fadeIn(200);
        $('#modal-icerik').html('<div style="text-align:center;padding:40px;"><span class="spinner is-active" style="float:none;"></span><p>Yükleniyor...</p></div>');
        
        $.post(caht_admin.ajax_url, {
            action: 'caht_rezervasyon_detay',
            nonce: caht_admin.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                var r = response.data.rezervasyon;
                var html = '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">';
                html += '<div class="postbox" style="padding: 15px;"><h4 style="margin-top:0;">Yolcu Bilgileri</h4>';
                html += '<p><strong>Ad Soyad:</strong> ' + r.yolcu_ad + ' ' + r.yolcu_soyad + '</p>';
                html += '<p><strong>Telefon:</strong> ' + r.telefon + '</p>';
                html += '<p><strong>E-posta:</strong> ' + r.eposta + '</p>';
                html += '<p><strong>Kişi Sayısı:</strong> ' + r.kisi_sayisi + '</p>';
                html += '</div>';
                
                html += '<div class="postbox" style="padding: 15px;"><h4 style="margin-top:0;">Transfer Bilgileri</h4>';
                html += '<p><strong>Gidiş:</strong> ' + response.data.gidis_tarihi + '</p>';
                html += '<p><strong>Dönüş:</strong> ' + (response.data.donus_tarihi || 'Tek Yön') + '</p>';
                html += '<p><strong>Mesafe:</strong> ' + r.mesafe + ' km</p>';
                html += '<p><strong>Araç:</strong> ' + (r.arac_ad || '-') + '</p>';
                html += '</div></div>';
                
                html += '<div class="postbox" style="padding: 15px; margin-bottom: 20px;"><h4 style="margin-top:0;">Güzergah</h4>';
                html += '<div style="display:flex; align-items:center; gap:20px;">';
                html += '<div style="flex:1; text-align:center; padding: 15px; background: #f0f0f0; border-radius: 6px;"><strong>KALKIŞ</strong><br>' + r.nereden + '</div>';
                html += '<div style="font-size:24px; color:#ccc;">→</div>';
                html += '<div style="flex:1; text-align:center; padding: 15px; background: #f0f0f0; border-radius: 6px;"><strong>VARIŞ</strong><br>' + r.nereye + '</div>';
                html += '</div></div>';
                
                html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">';
                html += '<div class="postbox" style="padding: 15px;"><h4 style="margin-top:0;">Fiyat & Ödeme</h4>';
                html += '<p><strong>Toplam Fiyat:</strong> ' + r.toplam_fiyat + ' ' + r.para_birimi + '</p>';
                html += '<p><strong>Seçilen Fiyat:</strong> ' + r.secilen_fiyat + ' ' + r.para_birimi + '</p>';
                html += '<p><strong>Ödeme Yöntemi:</strong> ' + r.odeme_yontemi + '</p>';
                html += '<p><strong>Ödeme Durumu:</strong> <span style="text-transform:uppercase;font-weight:bold;">' + r.odeme_durumu + '</span></p>';
                html += '</div>';
                
                html += '<div class="postbox" style="padding: 15px;"><h4 style="margin-top:0;">Ek Hizmetler & Durum</h4>';
                if (r.cocuk_koltugu == 1) html += '<p><span class="dashicons dashicons-yes" style="color:#1cc88a;"></span> Çocuk Koltuğu</p>';
                if (r.karsilama_hizmeti == 1) html += '<p><span class="dashicons dashicons-yes" style="color:#1cc88a;"></span> Karşılama Hizmeti</p>';
                if (r.third_bridge == 1) html += '<p><span class="dashicons dashicons-yes" style="color:#1cc88a;"></span> 3. Köprü</p>';
                html += '<p><strong>Mevcut Durum:</strong> <span style="text-transform:uppercase;font-weight:bold;color:#4e73df;">' + r.durum + '</span></p>';
                if (r.sofor_ad_soyad) html += '<p><strong>Şoför:</strong> ' + r.sofor_ad_soyad + '</p>';
                html += '</div></div>';
                
                if (response.data.ek_yolcular.length > 0) {
                    html += '<div class="postbox" style="padding: 15px; margin-top: 20px;"><h4 style="margin-top:0;">Ek Yolcular (' + response.data.ek_yolcular.length + ')</h4><ul>';
                    response.data.ek_yolcular.forEach(function(ey) {
                        html += '<li>' + ey.ad + ' ' + ey.soyad + '</li>';
                    });
                    html += '</ul></div>';
                }
                
                if (r.ek_detay) {
                    html += '<div class="postbox" style="padding: 15px; margin-top: 20px;"><h4 style="margin-top:0;">Ek Detay</h4><p>' + r.ek_detay + '</p></div>';
                }
                
                $('#modal-icerik').html(html);
                $('#modal-durum-guncelle').data('id', id).data('current', r.durum);
            } else {
                $('#modal-icerik').html('<p style="color:red;">Hata: ' + (response.data || 'Bilinmeyen hata') + '</p>');
            }
        }).fail(function() {
            $('#modal-icerik').html('<p style="color:red;">Bağlantı hatası.</p>');
        });
    });
    
    $('.modal-kapat').on('click', function() { $('#detay-modal').fadeOut(200); });
    $(document).on('keydown', function(e) { if (e.key === 'Escape') $('#detay-modal').fadeOut(200); });
    
    $('.btn-kalici-sil').on('click', function() {
        if (!confirm('BU İŞLEM GERİ ALINAMAZ! Rezervasyonu kalıcı olarak silmek istediğinize emin misiniz?')) return;
        var id = $(this).data('id');
        var btn = $(this);
        btn.prop('disabled', true).text('Siliniyor...');
        $.post(caht_admin.ajax_url, {
            action: 'caht_rezervasyon_sil_kalici',
            nonce: caht_admin.nonce,
            ids: [id]
        }, function(response) {
            if (response.success) btn.closest('tr').fadeOut(400, function(){ $(this).remove(); });
            else { alert('Hata: ' + response.data); btn.prop('disabled', false).text('Sil'); }
        });
    });
    
    $('#toplu-islem-uygula').on('click', function() {
        var islem = $('#toplu-islem').val();
        var secili = $('.rez-checkbox:checked').map(function(){ return $(this).val(); }).get();
        
        if (!islem) { alert('Lütfen bir işlem seçin.'); return; }
        if (secili.length === 0) { alert('Lütfen en az bir rezervasyon seçin.'); return; }
        
        if (islem === 'kalici_sil') {
            if (!confirm('SEÇİLİ ' + secili.length + ' REZERVASYONU KALICI OLARAK SİLMEK İSTEDİĞİNİZE EMİN MİSİNİZ?\n\nBU İŞLEM GERİ ALINAMAZ!')) return;
            $.post(caht_admin.ajax_url, {
                action: 'caht_rezervasyon_sil_kalici',
                nonce: caht_admin.nonce,
                ids: secili
            }, function(response) {
                if (response.success) location.reload();
                else alert('Hata: ' + response.data);
            });
        } else {
            var labels = {tamamlanmis: 'Tamamlandı', iptal: 'İptal', silinmis: 'Silinmiş', yeni: 'Yeni'};
            if (!confirm(secili.length + ' rezervasyonu "' + labels[islem] + '" durumuna almak istiyor musunuz?')) return;
            $.post(caht_admin.ajax_url, {
                action: 'caht_toplu_durum_guncelle',
                nonce: caht_admin.nonce,
                ids: secili,
                durum: islem
            }, function(response) {
                if (response.success) location.reload();
                else alert('Hata: ' + response.data);
            });
        }
    });
    
    $('#modal-durum-guncelle').on('click', function() {
        var id = $(this).data('id');
        var current = $(this).data('current');
        var yeni = prompt('Yeni durum girin (yeni, tamamlanmis, iptal, silinmis):', current);
        if (!yeni || yeni === current) return;
        $.post(caht_admin.ajax_url, {
            action: 'caht_rezervasyon_durum_guncelle',
            nonce: caht_admin.nonce,
            id: id,
            durum: yeni
        }, function(response) {
            if (response.success) location.reload();
            else alert('Hata: ' + response.data);
        });
    });
});
</script>