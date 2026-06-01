<?php
$api_key = get_option('caht_google_maps_api_key', '');
$edit_mode = isset($havalimani) && $havalimani;
$edit_koordinatlar_raw = $edit_mode && !empty($havalimani->koordinatlar) ? stripslashes($havalimani->koordinatlar) : '[]';
?>
<div class="wrap">
    <h1>Havalimanı Ekle ve Düzenle</h1>
    
    <?php if (isset($_GET['eklendi'])): ?>
        <div class="notice notice-success is-dismissible"><p>Havalimanı başarıyla eklendi!</p></div>
    <?php endif; ?>
    <?php if (isset($_GET['guncellendi'])): ?>
        <div class="notice notice-success is-dismissible"><p>Havalimanı başarıyla güncellendi!</p></div>
    <?php endif; ?>
    
    <div id="hata-mesaji" class="notice notice-error" style="display: none;"></div>
    <div id="basari-mesaji" class="notice notice-success" style="display: none;">Havalimanı başarıyla güncellendi!</div>
    
    <?php if (empty($api_key)): ?>
        <div class="notice notice-error">
            <p><strong>Hata:</strong> Google Maps API anahtarı ayarlarda tanımlanmamış! 
            <a href="<?php echo admin_url('admin.php?page=caht-ayarlar'); ?>">Ayarlara Git</a></p>
        </div>
    <?php else: ?>
    
    <!-- Mod Seçimi -->
    <div style="margin: 15px 0; padding: 15px; background: #f0f0f1; border-radius: 4px;">
        <button type="button" id="mod-ekle" class="button button-primary" style="margin-right: 10px;">
            <span class="dashicons dashicons-plus-alt" style="margin-top: 3px;"></span> Yeni Havalimanı Ekle
        </button>
        <button type="button" id="mod-duzenle" class="button">
            <span class="dashicons dashicons-edit" style="margin-top: 3px;"></span> Mevcut Havalimanı Düzenle
        </button>
        <span id="mod-durum" style="margin-left: 15px; font-weight: bold; color: #2271b1;">
            Mod: Yeni Havalimanı Ekleme
        </span>
    </div>
    
    <!-- Yeni Havalimanı Ekleme Formu -->
    <div id="panel-ekle" style="display: block;">
        <form method="POST" action="<?php echo admin_url('admin.php?page=caht-havalimanlari&action=ekle'); ?>" id="ekle-form">
            <?php wp_nonce_field('caht_nonce'); ?>
            <input type="hidden" name="caht_action" value="havalimani_kaydet">
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="ad">Havalimanı Adı</label></th>
                    <td>
                        <input type="text" name="ad" id="ad" class="regular-text" placeholder="Örn: Sabiha Gökçen Havalimanı" required>
                    </td>
                </tr>
            </table>
            
            <p class="description">Haritada havalimanı alanını bir poligon çizerek belirleyin. Önceki havalimanlarını görmek için çakışmaları önleyin.</p>
            
            <div style="margin: 15px 0;">
                <input type="text" id="search-location" placeholder="Konum ara (ör: Sabiha Gökçen)" style="width: 300px; padding: 6px;">
                <button type="button" id="reset-button" class="button" style="margin-left: 10px; background: #dc3545; color: white; border-color: #dc3545;">Poligonu Sıfırla</button>
                <button type="submit" class="button button-primary" style="margin-left: 10px;">Havalimanı Ekle</button>
            </div>
            
            <input type="hidden" name="koordinatlar" id="koordinatlar">
        </form>
    </div>
    
    <!-- Hızlı Düzenleme Paneli (Poligona tıklayınca açılır) -->
    <div id="duzenle-form" style="display: none; position: fixed; top: 220px; right: 20px; width: 300px; background: #f8f9fa; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 20px; z-index: 1100; border: 1px solid #dee2e6;">
        <h4 style="margin-top: 0; color: #343a40;">Havalimanı Düzenle</h4>
        <input type="hidden" id="duzenle-id">
        <p>
            <label><strong>Havalimanı Adı</strong></label><br>
            <input type="text" id="duzenle-ad" class="regular-text" style="width: 100%;" required>
        </p>
        <p>
            <label><strong>Koordinatlar</strong></label><br>
            <textarea id="duzenle-koordinatlar" rows="3" style="width: 100%; font-size: 11px;" readonly></textarea>
        </p>
        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" id="kaydet-button" class="button button-primary">Kaydet</button>
            <button type="button" id="iptal-button" class="button">İptal</button>
        </div>
        <span id="panel-mesaj" style="margin-top: 10px; display: block;"></span>
    </div>
    
    <!-- Harita -->
    <div id="map" style="height: 550px; width: 100%; border: 2px solid #c3c4c7; margin-bottom: 20px;"></div>
    
    <p><a href="<?php echo admin_url('admin.php?page=caht-havalimanlari'); ?>" class="button">← Havalimanlarına Dön</a></p>
    
    <script>
    // ===== PHP'DEN GELEN VERİLER =====
    var mevcutHavalimanlari = [];
    
    <?php 
    if (!empty($havalimanlari)) {
        foreach ($havalimanlari as $h) {
            $koord_str = stripslashes($h->koordinatlar);
            $koord_array = json_decode($koord_str, true);
            
            if (is_array($koord_array) && !empty($koord_array)) {
                echo "mevcutHavalimanlari.push({\n";
                echo "  id: " . intval($h->id) . ",\n";
                echo "  ad: " . json_encode($h->ad) . ",\n";
                echo "  koordinatlar: " . json_encode($koord_array) . "\n";
                echo "});\n";
            } else {
                echo "// DEBUG HATA - ID:" . $h->id . " decode basarisiz. Ham:[" . esc_js(substr($koord_str, 0, 80)) . "]\n";
            }
        }
    }
    ?>
    
    console.log('PHPden gelen havalimanlari:', mevcutHavalimanlari);
    
    var caht_ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';
    var caht_nonce = '<?php echo wp_create_nonce('caht_admin_nonce'); ?>';
    
    let mapInstance, drawingManager, polygons = [], labels = [], selectedPolygon = null;
    let aktifMod = 'ekle'; // 'ekle' veya 'duzenle'

    // Cırtlak ve farklı renkler
    const vibrantColors = [
        '#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF',
        '#00FFFF', '#FF4500', '#800080', '#32CD32', '#FFD700',
        '#FF1493', '#00CED1', '#FF8C00', '#9932CC', '#8B4513'
    ];
    let colorIndex = 0;

    function getNextColor() {
        return vibrantColors[colorIndex++ % vibrantColors.length];
    }

    // Poligonun merkezine etiket ekle
    function addLabelToPolygon(polygon, text, map, id) {
        const path = polygon.getPath().getArray();
        let bounds = new google.maps.LatLngBounds();
        path.forEach(coord => bounds.extend(coord));
        const center = bounds.getCenter();

        const label = new google.maps.Marker({
            position: center,
            map: map,
            label: {
                text: text,
                color: '#000000',
                fontWeight: 'bold',
                fontSize: '14px'
            },
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 0
            },
            customId: id
        });
        labels.push(label);
        return label;
    }

    function updateLabelPosition(polygon, label) {
        const path = polygon.getPath().getArray();
        let bounds = new google.maps.LatLngBounds();
        path.forEach(coord => bounds.extend(coord));
        label.setPosition(bounds.getCenter());
    }

    // AJAX ile havalimanı güncelleme
    function updateHavalimani(id, ad, koordinatlar) {
        jQuery('#panel-mesaj').html('<span style="color: #2271b1;">Kaydediliyor...</span>');
        
        jQuery.ajax({
            url: caht_ajax_url,
            type: 'POST',
            data: {
                action: 'caht_havalimani_guncelle',
                nonce: caht_nonce,
                id: id,
                ad: ad,
                koordinatlar: JSON.stringify(koordinatlar)
            },
            success: function(response) {
                if (response.success) {
                    jQuery('#panel-mesaj').html('<span style="color: green;">✓ Kaydedildi!</span>');
                    
                    // Label'ı güncelle
                    const label = labels.find(l => l.customId == id);
                    if (label) {
                        label.setLabel({
                            text: ad,
                            color: '#000000',
                            fontWeight: 'bold',
                            fontSize: '14px'
                        });
                        const poly = polygons.find(p => p.customId == id);
                        if (poly) updateLabelPosition(poly, label);
                    }
                    
                    // Veriyi güncelle
                    const havalimani = mevcutHavalimanlari.find(h => h.id == id);
                    if (havalimani) havalimani.ad = ad;
                    
                    setTimeout(() => {
                        jQuery('#panel-mesaj').fadeOut();
                    }, 2000);
                } else {
                    jQuery('#panel-mesaj').html('<span style="color: red;">✗ Hata: ' + (response.data || 'Bilinmiyor') + '</span>');
                }
            },
            error: function(xhr, status, error) {
                jQuery('#panel-mesaj').html('<span style="color: red;">✗ Bağlantı hatası</span>');
            }
        });
    }

    function initMap() {
        if (typeof google === 'undefined' || !google.maps) {
            document.getElementById('map').innerHTML = '<div style="padding:40px;text-align:center;color:red;">Google Maps yüklenemedi. API anahtarınızı veya internet bağlantınızı kontrol edin.</div>';
            return;
        }

        const mapDiv = document.getElementById('map');
        if (!mapDiv) return;

        mapInstance = new google.maps.Map(mapDiv, {
            center: { lat: 41.0082, lng: 28.9784 },
            zoom: 10
        });

        drawingManager = new google.maps.drawing.DrawingManager({
            drawingMode: google.maps.drawing.OverlayType.POLYGON,
            drawingControl: true,
            drawingControlOptions: {
                position: google.maps.ControlPosition.TOP_CENTER,
                drawingModes: [google.maps.drawing.OverlayType.POLYGON]
            },
            polygonOptions: {
                editable: true,
                draggable: false,
                fillOpacity: 0.5,
                strokeWeight: 3,
                clickable: true
            }
        });
        drawingManager.setMap(mapInstance);

        // ===== MEVCUT HAVALİMANLARINI YÜKLE =====
        console.log('Toplam havalimanı sayısı:', mevcutHavalimanlari.length);
        
        mevcutHavalimanlari.forEach(havalimani => {
            console.log('Havalimanı yükleniyor:', havalimani.id, havalimani.ad);
            
            if (!havalimani.koordinatlar || !Array.isArray(havalimani.koordinatlar) || havalimani.koordinatlar.length === 0) {
                console.error('Geçersiz koordinatlar:', havalimani.id);
                return;
            }
            
            const color = getNextColor();
            const polygon = new google.maps.Polygon({
                paths: havalimani.koordinatlar,
                strokeColor: color,
                strokeOpacity: 0.8,
                strokeWeight: 3,
                fillColor: color,
                fillOpacity: 0.35,
                map: mapInstance,
                editable: false, // Başlangıçta düzenleme kapalı
                draggable: false,
                customId: havalimani.id
            });
            polygons.push(polygon);
            const label = addLabelToPolygon(polygon, havalimani.ad, mapInstance, havalimani.id);

            // TIKLAMA OLAYI - Düzenle modunda
            google.maps.event.addListener(polygon, 'click', function() {
                if (aktifMod === 'duzenle') {
                    selectPolygonForEdit(polygon, havalimani);
                }
            });
        });

        // Yeni poligon çizildiğinde (SADECE ekle modunda)
        google.maps.event.addListener(drawingManager, 'polygoncomplete', function(polygon) {
            if (aktifMod !== 'ekle') {
                polygon.setMap(null);
                alert('Yeni havalimanı eklemek için "Yeni Havalimanı Ekle" moduna geçin.');
                return;
            }
            
            const color = getNextColor();
            polygon.setOptions({
                strokeColor: color,
                fillColor: color,
                fillOpacity: 0.5,
                draggable: false
            });
            polygons.push(polygon);
            updateCoordinates(polygon);
            
            google.maps.event.addListener(polygon.getPath(), 'set_at', () => updateCoordinates(polygon));
            google.maps.event.addListener(polygon.getPath(), 'insert_at', () => updateCoordinates(polygon));
            google.maps.event.addListener(polygon.getPath(), 'remove_at', () => updateCoordinates(polygon));
            
            drawingManager.setDrawingMode(null);
        });

        // Konum arama
        const searchInput = document.getElementById('search-location');
        if (searchInput) {
            const autocompleteSearch = new google.maps.places.Autocomplete(searchInput);
            autocompleteSearch.bindTo('bounds', mapInstance);
            autocompleteSearch.addListener('place_changed', function() {
                const place = autocompleteSearch.getPlace();
                if (!place.geometry) {
                    alert("Lütfen geçerli bir konum seçin.");
                    return;
                }
                mapInstance.setCenter(place.geometry.location);
                mapInstance.setZoom(15);
            });
        }

        // Sıfırla butonu
        document.getElementById('reset-button').addEventListener('click', function() {
            if (polygons.length > 0 && !selectedPolygon) {
                const lastPolygon = polygons[polygons.length - 1];
                // Sadece yeni çizilmiş (customId olmayan) poligonu sil
                if (!lastPolygon.customId) {
                    lastPolygon.setMap(null);
                    polygons.pop();
                    document.getElementById('koordinatlar').value = '';
                    drawingManager.setDrawingMode(google.maps.drawing.OverlayType.POLYGON);
                }
            }
        });

        // İptal butonu
        document.getElementById('iptal-button').addEventListener('click', deselectPolygon);

        // Kaydet butonu (hızlı düzenleme)
        document.getElementById('kaydet-button').addEventListener('click', function() {
            if (selectedPolygon) {
                const id = document.getElementById('duzenle-id').value;
                const ad = document.getElementById('duzenle-ad').value;
                const koordVal = document.getElementById('duzenle-koordinatlar').value;
                let coordinates;
                try {
                    coordinates = JSON.parse(koordVal || '[]');
                } catch (e) {
                    jQuery('#panel-mesaj').html('<span style="color: red;">Geçersiz koordinat formatı</span>');
                    return;
                }
                
                if (id && ad && coordinates.length > 0) {
                    updateHavalimani(id, ad, coordinates);
                } else {
                    jQuery('#panel-mesaj').html('<span style="color: red;">Lütfen tüm alanları doldurun</span>');
                }
            }
        });
        
        // FORM SUBMIT KONTROLÜ
        document.getElementById('ekle-form').addEventListener('submit', function(e) {
            const koord = document.getElementById('koordinatlar').value;
            if (!koord || koord === '[]' || koord === '') {
                e.preventDefault();
                alert('Lütfen haritada bir poligon çizin!');
                return false;
            }
            console.log('Form gönderiliyor, koordinatlar:', koord);
            return true;
        });
        
        // MOD DEĞİŞTİRME BUTONLARI
        document.getElementById('mod-ekle').addEventListener('click', function() {
            setMod('ekle');
        });
        
        document.getElementById('mod-duzenle').addEventListener('click', function() {
            setMod('duzenle');
        });
    }

    function setMod(mod) {
        aktifMod = mod;
        
        if (mod === 'ekle') {
            // Ekle modu
            document.getElementById('panel-ekle').style.display = 'block';
            document.getElementById('duzenle-form').style.display = 'none';
            document.getElementById('mod-durum').textContent = 'Mod: Yeni Havalimanı Ekleme';
            document.getElementById('mod-durum').style.color = '#2271b1';
            document.getElementById('mod-ekle').classList.add('button-primary');
            document.getElementById('mod-duzenle').classList.remove('button-primary');
            
            // Drawing manager'ı aktif et
            drawingManager.setOptions({
                drawingControl: true,
                drawingMode: google.maps.drawing.OverlayType.POLYGON
            });
            
            // Tüm mevcut poligonları düzenlenemez yap
            polygons.forEach(p => {
                if (p.customId) {
                    p.setOptions({ editable: false, fillOpacity: 0.35 });
                }
            });
            
            deselectPolygon();
            
        } else {
            // Düzenle modu
            document.getElementById('panel-ekle').style.display = 'none';
            document.getElementById('duzenle-form').style.display = 'none'; // Başlangıçta gizli
            document.getElementById('mod-durum').textContent = 'Mod: Havalimanı Düzenleme - Haritada bir havalimanına tıklayın';
            document.getElementById('mod-durum').style.color = '#d63638';
            document.getElementById('mod-duzenle').classList.add('button-primary');
            document.getElementById('mod-ekle').classList.remove('button-primary');
            
            // Drawing manager'ı kapat
            drawingManager.setOptions({
                drawingControl: false,
                drawingMode: null
            });
            
            // Tüm mevcut poligonları tıklanabilir yap
            polygons.forEach(p => {
                if (p.customId) {
                    p.setOptions({ editable: false, fillOpacity: 0.5, cursor: 'pointer' });
                }
            });
            
            deselectPolygon();
        }
    }

    function selectPolygonForEdit(polygon, havalimaniData) {
        // Önceki seçimi temizle
        deselectPolygon();
        
        selectedPolygon = polygon;
        
        // Poligonu düzenlenebilir yap
        polygon.setOptions({ editable: true, fillOpacity: 0.6 });
        
        // Paneli doldur ve göster
        document.getElementById('duzenle-form').style.display = 'block';
        document.getElementById('duzenle-id').value = havalimaniData.id;
        document.getElementById('duzenle-ad').value = havalimaniData.ad;
        
        const currentCoords = polygon.getPath().getArray().map(coord => ({
            lat: coord.lat(),
            lng: coord.lng()
        }));
        document.getElementById('duzenle-koordinatlar').value = JSON.stringify(currentCoords);
        
        // Haritayı poligonun ortasına getir
        let bounds = new google.maps.LatLngBounds();
        polygon.getPath().getArray().forEach(coord => bounds.extend(coord));
        mapInstance.fitBounds(bounds);
        
        // Koordinat değişikliklerini takip et
        google.maps.event.addListener(polygon.getPath(), 'set_at', function() {
            updateEditCoordinates(polygon);
        });
        google.maps.event.addListener(polygon.getPath(), 'insert_at', function() {
            updateEditCoordinates(polygon);
        });
        google.maps.event.addListener(polygon.getPath(), 'remove_at', function() {
            updateEditCoordinates(polygon);
        });
    }

    function deselectPolygon() {
        if (selectedPolygon && selectedPolygon.customId) {
            selectedPolygon.setOptions({ editable: false, fillOpacity: 0.35 });
        }
        selectedPolygon = null;
        document.getElementById('duzenle-form').style.display = 'none';
        document.getElementById('duzenle-id').value = '';
        document.getElementById('duzenle-ad').value = '';
        document.getElementById('duzenle-koordinatlar').value = '';
        document.getElementById('panel-mesaj').innerHTML = '';
    }

    function updateCoordinates(polygon) {
        const coordinates = polygon.getPath().getArray().map(coord => ({
            lat: coord.lat(),
            lng: coord.lng()
        }));
        document.getElementById('koordinatlar').value = JSON.stringify(coordinates);
        console.log('Ekle koordinatları:', coordinates);
    }

    function updateEditCoordinates(polygon) {
        const coordinates = polygon.getPath().getArray().map(coord => ({
            lat: coord.lat(),
            lng: coord.lng()
        }));
        document.getElementById('duzenle-koordinatlar').value = JSON.stringify(coordinates);
        
        // Label pozisyonunu güncelle
        const label = labels.find(l => l.customId == polygon.customId);
        if (label) updateLabelPosition(polygon, label);
    }

    window.addEventListener('load', initMap);
    </script>
    <?php endif; ?>
</div>