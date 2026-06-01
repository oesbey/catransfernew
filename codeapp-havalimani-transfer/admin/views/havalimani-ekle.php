<?php
$edit_mode = isset($havalimani) && $havalimani;
$koordinatlar_json = $edit_mode ? $havalimani->koordinatlar : '[]';

// Mevcut koordinatları parse et
$mevcut_koordinatlar = json_decode($koordinatlar_json, true);
if (!is_array($mevcut_koordinatlar)) {
    $mevcut_koordinatlar = array();
}

// Orta nokta hesapla (harita merkezi için)
$map_center_lat = 41.0082;
$map_center_lng = 28.9784;
if (!empty($mevcut_koordinatlar)) {
    $sum_lat = 0;
    $sum_lng = 0;
    foreach ($mevcut_koordinatlar as $k) {
        $sum_lat += floatval($k['lat']);
        $sum_lng += floatval($k['lng']);
    }
    $map_center_lat = $sum_lat / count($mevcut_koordinatlar);
    $map_center_lng = $sum_lng / count($mevcut_koordinatlar);
}

$api_key = get_option('caht_google_maps_api_key', '');
?>

<div class="wrap">
    <h1><?php echo $edit_mode ? 'Havalimanı Düzenle' : 'Yeni Havalimanı Ekle'; ?></h1>
    
    <form method="post" action="<?php echo admin_url('admin.php?page=caht-havalimanlari'); ?>">
        <?php wp_nonce_field('caht_nonce'); ?>
        <input type="hidden" name="caht_action" value="havalimani_kaydet">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="havalimani_id" value="<?php echo $havalimani->id; ?>">
        <?php endif; ?>

        <table class="form-table">
            <tr>
                <th><label for="ad">Havalimanı Adı</label></th>
                <td>
                    <input 
                        type="text" 
                        name="ad" 
                        id="caht-havalimani-ad" 
                        class="regular-text" 
                        value="<?php echo $edit_mode ? esc_attr($havalimani->ad) : ''; ?>" 
                        required
                        autocomplete="off"
                        style="width:100%;max-width:500px;"
                    >
                    <!-- Google Places Autocomplete önerileri -->
                    <div id="caht-havalimani-suggestions" class="caht-admin-suggestions"></div>
                    <p class="description">Yazmaya başlayın, Google Places önerileri görünecek. Seçtiğinizde harita otomatik güncellenir.</p>
                </td>
            </tr>
            <tr>
                <th>Harita</th>
                <td>
                    <div id="map" style="height: 500px; width: 100%; border: 1px solid #ccc; border-radius:8px;"></div>
                    <input type="hidden" name="koordinatlar" id="koordinatlar" value='<?php echo esc_attr($koordinatlar_json); ?>'>
                    <button type="button" id="reset-polygon" class="button" style="margin-top: 10px; background: #e74a3b; color: #fff;">
                        <i class="fas fa-trash"></i> Poligonu Sıfırla
                    </button>
                    <p class="description">Haritada poligon çizerek havalimanı alanını belirleyin. Veya yukarıdaki arama kutusundan seçim yapın.</p>
                </td>
            </tr>
        </table>

        <?php submit_button($edit_mode ? 'Güncelle' : 'Kaydet'); ?>
        <a href="<?php echo admin_url('admin.php?page=caht-havalimanlari'); ?>" class="button">İptal</a>
    </form>
</div>

<?php if (!empty($api_key)) : ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo esc_attr($api_key); ?>&libraries=places,drawing"></script>
<?php endif; ?>

<style>
.caht-admin-suggestions {
    position: absolute;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    max-height: 250px;
    overflow-y: auto;
    z-index: 9999;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: none;
    width: 100%;
    max-width: 500px;
}

.caht-admin-suggestions.active {
    display: block;
}

.caht-admin-suggestion-item {
    display: flex;
    align-items: center;
    padding: 10px 12px;
    font-size: 14px;
    color: #333;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.15s ease;
}

.caht-admin-suggestion-item:last-child {
    border-bottom: none;
}

.caht-admin-suggestion-item:hover {
    background-color: #ebf8ff;
}

.caht-admin-suggestion-item i {
    margin-right: 10px;
    color: #4e73df;
    font-size: 14px;
    width: 20px;
    text-align: center;
}

.caht-admin-suggestion-item .place-name {
    font-weight: 600;
}

.caht-admin-suggestion-item .place-address {
    font-size: 12px;
    color: #888;
    margin-left: 30px;
    display: block;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof google === 'undefined' || !google.maps) {
        alert('Google Maps API yüklenemedi. Lütfen API anahtarınızı kontrol edin.');
        return;
    }

    const map = new google.maps.Map(document.getElementById('map'), {
        center: { lat: <?php echo $map_center_lat; ?>, lng: <?php echo $map_center_lng; ?> },
        zoom: 14
    });

    let polygon = null;
    const existingCoords = <?php echo $koordinatlar_json; ?>;

    // Mevcut poligonu yükle
    if (existingCoords.length > 0) {
        polygon = new google.maps.Polygon({
            paths: existingCoords,
            editable: true,
            fillColor: '#4e73df',
            fillOpacity: 0.3,
            strokeColor: '#4e73df',
            strokeWeight: 2
        });
        polygon.setMap(map);
        
        const bounds = new google.maps.LatLngBounds();
        existingCoords.forEach(c => bounds.extend(new google.maps.LatLng(c.lat, c.lng)));
        map.fitBounds(bounds);
    }

    // Çizim yöneticisi
    const drawingManager = new google.maps.drawing.DrawingManager({
        drawingMode: google.maps.drawing.OverlayType.POLYGON,
        drawingControl: true,
        drawingControlOptions: {
            position: google.maps.ControlPosition.TOP_CENTER,
            drawingModes: [google.maps.drawing.OverlayType.POLYGON]
        },
        polygonOptions: {
            editable: true,
            fillColor: '#4e73df',
            fillOpacity: 0.3,
            strokeColor: '#4e73df',
            strokeWeight: 2
        }
    });
    drawingManager.setMap(map);

    // Yeni poligon çizildiğinde
    google.maps.event.addListener(drawingManager, 'polygoncomplete', function(newPolygon) {
        if (polygon) polygon.setMap(null);
        polygon = newPolygon;
        updateCoordinates();
        
        google.maps.event.addListener(polygon.getPath(), 'set_at', updateCoordinates);
        google.maps.event.addListener(polygon.getPath(), 'insert_at', updateCoordinates);
        google.maps.event.addListener(polygon.getPath(), 'remove_at', updateCoordinates);
        
        drawingManager.setDrawingMode(null);
    });

    function updateCoordinates() {
        if (!polygon) return;
        const coords = polygon.getPath().getArray().map(coord => ({
            lat: coord.lat(),
            lng: coord.lng()
        }));
        document.getElementById('koordinatlar').value = JSON.stringify(coords);
    }

    // Sıfırla
    document.getElementById('reset-polygon').addEventListener('click', function() {
        if (polygon) {
            polygon.setMap(null);
            polygon = null;
        }
        document.getElementById('koordinatlar').value = '[]';
        drawingManager.setDrawingMode(google.maps.drawing.OverlayType.POLYGON);
    });

    // ============================================
    // GOOGLE PLACES AUTOCOMPLETE (Transfer formundaki gibi)
    // ============================================
    var adInput = document.getElementById('caht-havalimani-ad');
    var suggestionsContainer = document.getElementById('caht-havalimani-suggestions');
    var autocompleteService = new google.maps.places.AutocompleteService();
    var placesService = new google.maps.places.PlacesService(document.createElement('div'));

    function displaySuggestions(suggestions) {
        suggestionsContainer.innerHTML = '';
        if (suggestions.length === 0) {
            suggestionsContainer.classList.remove('active');
            return;
        }
        suggestionsContainer.classList.add('active');

        suggestions.forEach(function(suggestion) {
            var div = document.createElement('div');
            div.className = 'caht-admin-suggestion-item';
            div.innerHTML = '<i class="fas fa-plane"></i> <span class="place-name">' + suggestion.description + '</span>';
            
            div.addEventListener('click', function() {
                adInput.value = suggestion.description;
                suggestionsContainer.innerHTML = '';
                suggestionsContainer.classList.remove('active');

                // Place detaylarını al ve haritaya git
                placesService.getDetails({ placeId: suggestion.place_id }, function(place, status) {
                    if (status === google.maps.places.PlacesServiceStatus.OK && place.geometry && place.geometry.location) {
                        var location = place.geometry.location;
                        
                        // Haritayı merkezle
                        map.setCenter(location);
                        map.setZoom(16);
                        
                        // Otomatik poligon oluştur (yerleşim alanı tahmini)
                        var lat = location.lat();
                        var lng = location.lng();
                        var offset = 0.005; // Yaklaşık 500m
                        
                        var autoCoords = [
                            { lat: lat + offset, lng: lng - offset },
                            { lat: lat + offset, lng: lng + offset },
                            { lat: lat - offset, lng: lng + offset },
                            { lat: lat - offset, lng: lng - offset }
                        ];
                        
                        if (polygon) polygon.setMap(null);
                        
                        polygon = new google.maps.Polygon({
                            paths: autoCoords,
                            editable: true,
                            fillColor: '#4e73df',
                            fillOpacity: 0.3,
                            strokeColor: '#4e73df',
                            strokeWeight: 2
                        });
                        polygon.setMap(map);
                        
                        google.maps.event.addListener(polygon.getPath(), 'set_at', updateCoordinates);
                        google.maps.event.addListener(polygon.getPath(), 'insert_at', updateCoordinates);
                        google.maps.event.addListener(polygon.getPath(), 'remove_at', updateCoordinates);
                        
                        updateCoordinates();
                        drawingManager.setDrawingMode(null);
                    }
                });
            });

            suggestionsContainer.appendChild(div);
        });
    }

    adInput.addEventListener('input', function() {
        var query = this.value.trim();
        if (!query) {
            suggestionsContainer.innerHTML = '';
            suggestionsContainer.classList.remove('active');
            return;
        }

        autocompleteService.getPlacePredictions({
            input: query,
            types: ['airport', 'establishment'],
            componentRestrictions: { country: 'tr' },
            language: 'tr'
        }, function(predictions, status) {
            if (status === google.maps.places.PlacesServiceStatus.OK && predictions) {
                displaySuggestions(predictions);
            } else {
                suggestionsContainer.innerHTML = '';
                suggestionsContainer.classList.remove('active');
            }
        });
    });

    // Dışarı tıklayınca önerileri kapat
    document.addEventListener('click', function(e) {
        if (!adInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
            suggestionsContainer.innerHTML = '';
            suggestionsContainer.classList.remove('active');
        }
    });
});
</script>