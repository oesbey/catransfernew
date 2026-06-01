<?php
$api_key = get_option('caht_google_maps_api_key', '');
?>
<div class="wrap">
    <h1>Bölge Ekle ve Düzenle</h1>
    
    <?php if (isset($_GET['eklendi'])): ?>
        <div class="notice notice-success is-dismissible"><p>Bölge başarıyla eklendi!</p></div>
    <?php endif; ?>
    <?php if (isset($_GET['guncellendi'])): ?>
        <div class="notice notice-success is-dismissible"><p>Bölge başarıyla güncellendi!</p></div>
    <?php endif; ?>
    
    <div id="hata-mesaji" class="notice notice-error" style="display: none;"></div>
    <div id="basari-mesaji" class="notice notice-success" style="display: none;">Bölge başarıyla güncellendi!</div>
    
    <?php if (empty($api_key)): ?>
        <div class="notice notice-error">
            <p><strong>Hata:</strong> Google Maps API anahtarı ayarlarda tanımlanmamış! 
            <a href="<?php echo admin_url('admin.php?page=caht-ayarlar'); ?>">Ayarlara Git</a></p>
        </div>
    <?php else: ?>
    
    <!-- Mod Seçimi -->
    <div style="margin: 15px 0; padding: 15px; background: #f0f0f1; border-radius: 4px;">
        <button type="button" id="mod-ekle" class="button button-primary" style="margin-right: 10px;">
            <span class="dashicons dashicons-plus-alt" style="margin-top: 3px;"></span> Yeni Bölge Ekle
        </button>
        <button type="button" id="mod-duzenle" class="button">
            <span class="dashicons dashicons-edit" style="margin-top: 3px;"></span> Mevcut Bölge Düzenle
        </button>
        <span id="mod-durum" style="margin-left: 15px; font-weight: bold; color: #2271b1;">
            Mod: Yeni Bölge Ekleme
        </span>
    </div>
    
    <!-- Yeni Bölge Ekleme Formu -->
    <div id="panel-ekle" style="display: block;">
        <form method="POST" action="<?php echo admin_url('admin.php?page=caht-bolgeler&action=ekle'); ?>" id="ekle-form">
            <?php wp_nonce_field('caht_nonce'); ?>
            <input type="hidden" name="caht_action" value="bolge_kaydet">
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="ad">Bölge Adı</label></th>
                    <td>
                        <input type="text" name="ad" id="ad" class="regular-text" placeholder="Örn: Kuzey Bölgesi" required>
                    </td>
                </tr>
            </table>
            
            <p class="description">Haritada bir poligon çizin. Önceki bölgeleri görmek için çakışmaları önleyin.</p>
            
            <div style="margin: 15px 0;">
                <input type="text" id="search-location" placeholder="Konum ara (ör: Sabiha Gökçen)" style="width: 300px; padding: 6px;">
                <button type="button" id="reset-button" class="button" style="margin-left: 10px; background: #dc3545; color: white; border-color: #dc3545;">Poligonu Sıfırla</button>
                <button type="submit" class="button button-primary" style="margin-left: 10px;">Bölge Ekle</button>
            </div>
            
            <input type="hidden" name="koordinatlar" id="koordinatlar">
        </form>
    </div>
    
    <!-- Düzenleme Paneli (Gizli başlangıç) -->
    <div id="panel-duzenle" style="display: none; background: #f0f6fc; border: 1px solid #c5d9ed; padding: 20px; border-radius: 4px; margin-bottom: 15px;">
        <h3 style="margin-top: 0; color: #1d2327;"><span class="dashicons dashicons-edit"></span> Bölge Düzenle</h3>
        <input type="hidden" id="duzenle-id">
        <table class="form-table">
            <tr>
                <th><label for="duzenle-ad">Bölge Adı</label></th>
                <td><input type="text" id="duzenle-ad" class="regular-text" style="width: 300px;"></td>
            </tr>
            <tr>
                <th><label>Koordinatlar</label></th>
                <td>
                    <textarea id="duzenle-koordinatlar" rows="4" style="width: 100%; max-width: 500px; font-family: monospace; font-size: 11px;" readonly></textarea>
                    <p class="description">Haritada poligonun köşelerini sürükleyerek düzenleyin.</p>
                </td>
            </tr>
        </table>
        <div style="margin-top: 15px;">
            <button type="button" id="kaydet-button" class="button button-primary">Değişiklikleri Kaydet</button>
            <button type="button" id="iptal-button" class="button">İptal</button>
            <span id="panel-mesaj" style="margin-left: 15px;"></span>
        </div>
    </div>
    
    <!-- Harita -->
    <div id="map" style="height: 550px; width: 100%; border: 2px solid #c3c4c7; margin-bottom: 20px;"></div>
    
    <p><a href="<?php echo admin_url('admin.php?page=caht-bolgeler'); ?>" class="button">← Bölgelere Dön</a></p>
    
    <script>
    // ===== PHP'DEN GELEN VERİLER =====
    var mevcutBolgeler = [];
    
    <?php 
    if (!empty($bolgeler)) {
        foreach ($bolgeler as $b) {
            $koord_str = stripslashes($b->koordinatlar);
            $koord_array = json_decode($koord_str, true);
            
            if (is_array($koord_array) && !empty($koord_array)) {
                echo "mevcutBolgeler.push({\n";
                echo "  id: " . intval($b->id) . ",\n";
                echo "  ad: " . json_encode($b->ad) . ",\n";
                echo "  koordinatlar: " . json_encode($koord_array) . "\n";
                echo "});\n";
            }
        }
    }
    ?>
    
    console.log('PHPden gelen bolgeler:', mevcutBolgeler);
    
    var caht_ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';
    var caht_nonce = '<?php echo wp_create_nonce('caht_admin_nonce'); ?>';
    
    let mapInstance, drawingManager, polygons = [], labels = [], selectedPolygon = null;
    let aktifMod = 'ekle'; // 'ekle' veya 'duzenle'

    const vibrantColors = [
        '#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF',
        '#00FFFF', '#FF4500', '#800080', '#32CD32', '#FFD700',
        '#FF1493', '#00CED1', '#FF8C00', '#9932CC', '#8B4513'
    ];
    let colorIndex = 0;

    function getNextColor() {
        return vibrantColors[colorIndex++ % vibrantColors.length];
    }

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

    function updateBolge(id, ad, koordinatlar) {
        jQuery('#panel-mesaj').html('<span style="color: #2271b1;">Kaydediliyor...</span>');
        
        jQuery.ajax({
            url: caht_ajax_url,
            type: 'POST',
            data: {
                action: 'caht_bolge_guncelle',
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
                    
                    // Bölge adını güncelle
                    const bolge = mevcutBolgeler.find(b => b.id == id);
                    if (bolge) bolge.ad = ad;
                    
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
            document.getElementById('map').innerHTML = '<div style="padding:40px;text-align:center;color:red;">Google Maps yüklenemedi.</div>';
            return;
        }

        mapInstance = new google.maps.Map(document.getElementById('map'), {
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

        // ===== MEVCUT BÖLGELERİ YÜKLE =====
        console.log('Toplam bölge sayısı:', mevcutBolgeler.length);
        
        mevcutBolgeler.forEach(bolge => {
            console.log('Bölge yükleniyor:', bolge.id, bolge.ad);
            
            if (!bolge.koordinatlar || !Array.isArray(bolge.koordinatlar) || bolge.koordinatlar.length === 0) {
                console.error('Geçersiz koordinatlar:', bolge.id);
                return;
            }
            
            const color = getNextColor();
            const polygon = new google.maps.Polygon({
                paths: bolge.koordinatlar,
                strokeColor: color,
                strokeOpacity: 0.8,
                strokeWeight: 3,
                fillColor: color,
                fillOpacity: 0.35,
                map: mapInstance,
                editable: false, // Başlangıçta düzenleme kapalı
                draggable: false,
                customId: bolge.id,
                customColor: color
            });
            polygons.push(polygon);
            const label = addLabelToPolygon(polygon, bolge.ad, mapInstance, bolge.id);

            // TIKLAMA OLAYI - Düzenle modunda
            google.maps.event.addListener(polygon, 'click', function() {
                if (aktifMod === 'duzenle') {
                    selectPolygonForEdit(polygon, bolge);
                }
            });
        });

        // Yeni poligon çizildiğinde (SADECE ekle modunda)
        google.maps.event.addListener(drawingManager, 'polygoncomplete', function(polygon) {
            if (aktifMod !== 'ekle') {
                polygon.setMap(null);
                alert('Yeni bölge eklemek için "Yeni Bölge Ekle" moduna geçin.');
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
            const autocomplete = new google.maps.places.Autocomplete(searchInput);
            autocomplete.bindTo('bounds', mapInstance);
            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();
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
            if (polygons.length > 0) {
                const lastPolygon = polygons[polygons.length - 1];
                if (!lastPolygon.customId) {
                    lastPolygon.setMap(null);
                    polygons.pop();
                    document.getElementById('koordinatlar').value = '';
                    drawingManager.setDrawingMode(google.maps.drawing.OverlayType.POLYGON);
                }
            }
        });

        // İptal butonu
        document.getElementById('iptal-button').addEventListener('click', function() {
            deselectPolygon();
        });

        // Kaydet butonu
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
                    updateBolge(id, ad, coordinates);
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
            document.getElementById('panel-duzenle').style.display = 'none';
            document.getElementById('mod-durum').textContent = 'Mod: Yeni Bölge Ekleme';
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
            document.getElementById('panel-duzenle').style.display = 'none'; // Başlangıçta gizli
            document.getElementById('mod-durum').textContent = 'Mod: Bölge Düzenleme - Haritada bir bölgeye tıklayın';
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

    function selectPolygonForEdit(polygon, bolgeData) {
        // Önceki seçimi temizle
        deselectPolygon();
        
        selectedPolygon = polygon;
        
        // Poligonu düzenlenebilir yap
        polygon.setOptions({ editable: true, fillOpacity: 0.6 });
        
        // Paneli doldur ve göster
        document.getElementById('panel-duzenle').style.display = 'block';
        document.getElementById('duzenle-id').value = bolgeData.id;
        document.getElementById('duzenle-ad').value = bolgeData.ad;
        
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
        document.getElementById('panel-duzenle').style.display = 'none';
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