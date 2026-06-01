<?php
/**
 * Transfer Form View — Tek Dosya (HTML + CSS + JS)
 *
 * @package Codeapp_Havalimani_Transfer
 */

if (!defined('ABSPATH')) exit;

$lang = substr(get_locale(), 0, 2);
$translations = array(
    'tr' => array(
        'from' => 'Nereden',
        'to' => 'Nereye',
        'date_time' => 'Tarih & Saat',
        'return_date_time' => 'Dönüş Tarihi',
        'round_trip' => 'Gidiş-Dönüş',
        'person_count' => 'Kişi Sayısı',
        'search' => 'Ara',
        'from_placeholder' => 'Adres, Havalimanı, Otel, Hastane...',
        'to_placeholder' => 'Adres, Havalimanı, Otel, Hastane...',
        'date_time_placeholder' => 'Transfer Tarihi',
        'return_date_time_placeholder' => 'Dönüş Tarihi',
    ),
    'en' => array(
        'from' => 'From Where',
        'to' => 'To Where',
        'date_time' => 'Date & Time',
        'return_date_time' => 'Return Date',
        'round_trip' => 'Round Trip',
        'person_count' => 'Passengers',
        'search' => 'Search',
        'from_placeholder' => 'Address, Airport, Hotel, Hospital...',
        'to_placeholder' => 'Address, Airport, Hotel, Hospital...',
        'date_time_placeholder' => 'Transfer Date',
        'return_date_time_placeholder' => 'Return Date',
    ),
);

$t = isset($translations[$lang]) ? $translations[$lang] : $translations['tr'];

// === KÖK SORUN BURADA ÇÖZÜLDÜ ===
// Artık caht_action parametresi yerine DOĞRUDAN sonuç sayfasına gidiyoruz
$sonuc_page_id = get_option('caht_sonuc_page_id', 0);
if ($sonuc_page_id) {
    $sonuc_url = get_permalink($sonuc_page_id);
} else {
    // Eğer ayar yapılmamışsa, /transfer-sonuc/ slug'ını dene
    $sonuc_page = get_page_by_path('transfer-sonuc');
    if ($sonuc_page) {
        $sonuc_url = get_permalink($sonuc_page->ID);
        update_option('caht_sonuc_page_id', $sonuc_page->ID);
    } else {
        // Son çare: home_url
        $sonuc_url = home_url('/transfer-sonuc/');
    }
}

$api_key = get_option('caht_google_maps_api_key', '');
?>

<!-- HARICI KUTUPHANELER (CDN) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<?php if (strpos(get_locale(), 'tr') !== false) : ?>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
<?php endif; ?>

<?php if (!empty($api_key)) : ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo esc_attr($api_key); ?>&libraries=places"></script>
<?php endif; ?>

<!-- INLINE CSS -->
<style>
/* ============================================
   CODEAPP TRANSFER FORM — INLINE STYLES
   ============================================ */

/* Google Places kendi oneri kutusunu GIZLE (bizim custom olan calissin) */
.pac-container {
    display: none !important;
}

/* Ana wrapper */
.caht-transfer-form-wrapper {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

/* Mavi gradient ana bar */
.caht-transfer-bar {
    display: flex;
    align-items: flex-end;
    gap: 0;
    background: linear-gradient(135deg, #1b510d 0%, #257b14 50%, #247d13 100%);
    padding: 20px 24px;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(30, 58, 95, 0.35);
    position: relative;
    flex-wrap: nowrap;
}

/* Beyaz input gruplari */
.caht-input-group {
    background: #ffffff;
    border-radius: 12px;
    padding: 10px 14px;
    margin-right: 8px;
    min-width: 140px;
    height: 80px;
    position: relative;
    flex: 1;
    transition: box-shadow 0.2s ease;
}

.caht-input-group:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

.caht-input-group-large {
    flex: 2.2;
    min-width: 220px;
}

.caht-input-group-small {
    min-width: 110px;
    flex: 0 0 auto;
}

/* Label */
.caht-input-label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* Input */
.caht-input {
    width: 100%;
    border: none;
    background: transparent;
    font-size: 14px;
    color: #2d3748;
    padding: 0;
    outline: none;
    font-family: inherit;
}

.caht-input::placeholder {
    color: #a0aec0;
    font-size: 13px;
}

.caht-input-datetime {
    cursor: pointer;
}

/* Select */
.caht-select {
    width: 100%;
    border: none;
    background: transparent;
    font-size: 14px;
    color: #2d3748;
    padding: 0;
    outline: none;
    font-family: inherit;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%234a5568' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right center;
    padding-right: 18px;
}

/* Yer degistir butonu */
.caht-swap-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    padding-bottom: 10px;
    margin-right: 8px;
}

.caht-swap-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #ffffff;
    border: 2px solid #e2e8f0;
    color: #3182ce;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.caht-swap-btn:hover {
    background: #3182ce;
    color: #ffffff;
    border-color: #3182ce;
    transform: rotate(180deg);
}

/* Gidis-Donus Toggle */
.caht-toggle-wrapper {
    display: flex;
    align-items: center;
}

.caht-toggle-switch {
    width: 44px;
    height: 24px;
    background: #e2e8f0;
    border-radius: 12px;
    position: relative;
    cursor: pointer;
    transition: background 0.3s ease;
}

.caht-toggle-switch::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    background: #ffffff;
    border-radius: 50%;
    top: 2px;
    left: 2px;
    transition: transform 0.3s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.caht-toggle-switch.active {
    background: #3182ce;
}

.caht-toggle-switch.active::after {
    transform: translateX(20px);
}

/* Donus tarihi container (toggle acilinca) */
.caht-return-container {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    background: #ffffff;
    border-radius: 12px;
    padding: 10px 14px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    z-index: 100;
    min-width: 180px;
    border: 1px solid #e2e8f0;
}

/* Ara butonu */
.caht-search-wrapper {
    display: flex;
    align-items: flex-end;
    margin-left: 4px;
}

.caht-search-btn {
    padding: 14px 32px;
    background: linear-gradient(135deg, #ffffff 0%, #f5f5f5 100%);
    color: black;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    box-shadow: 0 4px 12px rgba(49, 130, 206, 0.4);
    height: 48px;
}

.caht-search-btn:hover {
    background: linear-gradient(135deg, #ffffff 0%, #f5f5f5 100%);
    box-shadow: 0 6px 20px rgba(49, 130, 206, 0.5);
    transform: translateY(-1px);
}

.caht-search-btn:active {
    transform: translateY(0);
}

.caht-search-btn:disabled {
    background: #a0aec0;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}

/* Custom Suggestions (Autocomplete) */
.caht-suggestions {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    max-height: 280px;
    overflow-y: auto;
    z-index: 9999;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    display: none;
}

.caht-suggestions.active {
    display: block;
}

.caht-suggestion-item {
    display: flex;
    align-items: center;
    padding: 12px 14px;
    font-size: 14px;
    color: #2d3748;
    cursor: pointer;
    border-bottom: 1px solid #f7fafc;
    transition: background-color 0.15s ease;
}

.caht-suggestion-item:last-child {
    border-bottom: none;
}

.caht-suggestion-item:hover {
    background-color: #ebf8ff;
}

.caht-suggestion-item i {
    margin-right: 10px;
    color: #3182ce;
    font-size: 14px;
    width: 20px;
    text-align: center;
}

.caht-suggestion-item.priority {
    background-color: #ebf8ff;
    font-weight: 600;
    color: #2b6cb0;
}

/* ============================================
   RESPONSIVE
   ============================================ */
@media (max-width: 992px) {
    .caht-transfer-bar {
        flex-wrap: wrap;
        gap: 8px;
        padding: 16px;
    }

    .caht-input-group {
        flex: 1 1 calc(50% - 8px);
        min-width: 160px;
        margin-right: 0;
    }

    .caht-input-group-large {
        flex: 1 1 100%;
    }

    .caht-swap-wrapper {
        display: none;
    }

    .caht-search-wrapper {
        flex: 1 1 100%;
        margin-left: 0;
    }

    .caht-search-btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 576px) {
    .caht-transfer-bar {
        border-radius: 12px;
    }

    .caht-input-group {
        flex: 1 1 100%;
        padding: 8px 12px;
    }

    .caht-input-group-small {
        flex: 1 1 calc(50% - 4px);
    }
}
</style>

<!-- HTML FORM -->
<div class="caht-transfer-form-wrapper">
    <!-- 
        === KÖK SORUN BURADA ÇÖZÜLDÜ ===
        action="" (boş) = aynı sayfaya submit et
        JS ile submit intercept edip window.location.href ile sonuç sayfasına yönlendireceğiz
    -->
    <form id="caht-transfer-form" method="GET" action="" class="caht-transfer-bar">
        
        <!-- NEREDEN -->
        <div class="caht-input-group caht-input-group-large">
            <label class="caht-input-label"><?php echo esc_html($t['from']); ?></label>
            <input 
                type="text" 
                id="caht-nereden" 
                name="nereden" 
                class="caht-input" 
                placeholder="<?php echo esc_attr($t['from_placeholder']); ?>" 
                autocomplete="off"
            >
            <div id="caht-nereden-suggestions" class="caht-suggestions"></div>
            <input type="hidden" id="caht-nereden-lat" name="nereden_lat">
            <input type="hidden" id="caht-nereden-lng" name="nereden_lng">
        </div>

        <!-- YER DEGISTIR -->
        <div class="caht-swap-wrapper">
            <button type="button" id="caht-swap-locations" class="caht-swap-btn" title="Yer Degistir">
                <i class="fas fa-exchange-alt"></i>
            </button>
        </div>

        <!-- NEREYE -->
        <div class="caht-input-group caht-input-group-large">
            <label class="caht-input-label"><?php echo esc_html($t['to']); ?></label>
            <input 
                type="text" 
                id="caht-nereye" 
                name="nereye" 
                class="caht-input" 
                placeholder="<?php echo esc_attr($t['to_placeholder']); ?>" 
                autocomplete="off"
            >
            <div id="caht-nereye-suggestions" class="caht-suggestions"></div>
            <input type="hidden" id="caht-nereye-lat" name="nereye_lat">
            <input type="hidden" id="caht-nereye-lng" name="nereye_lng">
        </div>

        <!-- TARIH & SAAT -->
        <div class="caht-input-group">
            <label class="caht-input-label"><?php echo esc_html($t['date_time']); ?></label>
            <input 
                type="text" 
                id="caht-datetime-picker" 
                name="gidis_tarih" 
                class="caht-input caht-input-datetime" 
                placeholder="<?php echo esc_attr($t['date_time_placeholder']); ?>" 
                readonly
            >
        </div>

        <!-- GIDIS-DONUS -->
        <div class="caht-input-group caht-input-group-small">
            <label class="caht-input-label"><?php echo esc_html($t['round_trip']); ?></label>
            <div class="caht-toggle-wrapper">
                <div id="caht-toggle-switch" class="caht-toggle-switch"></div>
            </div>
            <input type="hidden" id="caht-gidis-donus" name="gidis_donus" value="0">
            <div id="caht-return-datetime-container" class="caht-return-container" style="display:none;">
                <input 
                    type="text" 
                    id="caht-return-datetime-picker" 
                    name="donus_tarih" 
                    class="caht-input caht-input-datetime" 
                    placeholder="<?php echo esc_attr($t['return_date_time_placeholder']); ?>" 
                    readonly
                >
            </div>
        </div>

        <!-- KISI SAYISI -->
        <div class="caht-input-group caht-input-group-small">
            <label class="caht-input-label"><?php echo esc_html($t['person_count']); ?></label>
            <select name="kisi_sayisi" id="caht-kisi-sayisi" class="caht-select">
                <?php for ($i = 1; $i <= 20; $i++) : ?>
                    <option value="<?php echo $i; ?>" <?php selected($i, 1); ?>><?php echo $i; ?> <?php echo ($lang == 'tr') ? 'Kisi' : 'Person'; ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <!-- ARA BUTONU -->
        <div class="caht-search-wrapper">
            <button type="submit" id="caht-search-btn" class="caht-search-btn">
                <i class="fas fa-search"></i>
                <span><?php echo esc_html($t['search']); ?></span>
            </button>
        </div>

        <!-- GIZLI ALANLAR -->
        <input type="hidden" id="caht-mesafe" name="mesafe" value="">
        <input type="hidden" id="caht-sure" name="sure" value="">

    </form>
</div>

<!-- INLINE JS -->
<script>
(function() {
    'use strict';

    // === KÖK SORUN BURADA ÇÖZÜLDÜ ===
    // Sonuç sayfası URL'si - PHP'den alıyoruz
    var sonucUrl = '<?php echo esc_js($sonuc_url); ?>';
    console.log('CAHT: Sonuç sayfası URL =', sonucUrl);

    var lang = document.documentElement.lang || 'tr';
    var isEnglish = lang.indexOf('en') !== -1;

    var priorityPlaces = [
        { name: "Sabiha Gokcen Havalimani", placeId: "ChIJRUCHGUXHyhQRf2y6vQU8Zuo", type: "airport" },
        { name: "Istanbul Havalimani", placeId: "ChIJ6bT6y6PHyhQRrS83yH-JUJI", type: "airport" },
        { name: "Ataturk Havalimani", placeId: "ChIJGQX6lYbHyhQRjZ3Z3q3Z3q3", type: "airport" },
        { name: "Hilton Istanbul Bosphorus", placeId: "ChIJI8qB7pnAyhQRm3vQh5zQz3Q", type: "hotel" }
    ];

    var returnPicker = null;
    var googleMapsLoaded = false;
    var initAttempts = 0;

    // ============================================
    // INIT
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        initGoogleMaps();
        initFlatpickr();
        bindEvents();
    });

    // ============================================
    // GOOGLE MAPS INIT
    // ============================================
    function initGoogleMaps() {
        if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
            initAttempts++;
            if (initAttempts < 30) {
                setTimeout(initGoogleMaps, 500);
            } else {
                console.warn('CAHT: Google Maps yuklenemedi. Yerel liste calisacak.');
                initLocalAutocomplete();
            }
            return;
        }

        googleMapsLoaded = true;
        initAutocomplete();
    }

    // ============================================
    // LOCAL AUTOCOMPLETE (FALLBACK)
    // ============================================
    function initLocalAutocomplete() {
        var neredenInput = document.getElementById('caht-nereden');
        var nereyeInput = document.getElementById('caht-nereye');
        var neredenSuggestions = document.getElementById('caht-nereden-suggestions');
        var nereyeSuggestions = document.getElementById('caht-nereye-suggestions');

        if (!neredenInput || !nereyeInput) return;

        function handleInput(input, suggestionsContainer) {
            input.addEventListener('input', function() {
                var query = input.value.trim().toLowerCase();
                if (!query) {
                    suggestionsContainer.innerHTML = '';
                    suggestionsContainer.classList.remove('active');
                    return;
                }

                var matches = priorityPlaces.filter(function(place) {
                    return place.name.toLowerCase().indexOf(query) !== -1;
                });

                if (matches.length === 0) {
                    suggestionsContainer.innerHTML = '';
                    suggestionsContainer.classList.remove('active');
                    return;
                }

                suggestionsContainer.innerHTML = '';
                suggestionsContainer.classList.add('active');

                matches.forEach(function(place) {
                    var div = document.createElement('div');
                    div.className = 'caht-suggestion-item priority';
                    var iconClass = place.type === 'airport' ? 'fas fa-plane' : 'fas fa-hotel';
                    div.innerHTML = '<i class="' + iconClass + '"></i> ' + place.name;
                    div.addEventListener('click', function() {
                        input.value = place.name;
                        suggestionsContainer.innerHTML = '';
                        suggestionsContainer.classList.remove('active');
                    });
                    suggestionsContainer.appendChild(div);
                });
            });

            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                    suggestionsContainer.innerHTML = '';
                    suggestionsContainer.classList.remove('active');
                }
            });
        }

        handleInput(neredenInput, neredenSuggestions);
        handleInput(nereyeInput, nereyeSuggestions);
    }

    // ============================================
    // GOOGLE AUTOCOMPLETE
    // ============================================
    function initAutocomplete() {
        var neredenInput = document.getElementById('caht-nereden');
        var nereyeInput = document.getElementById('caht-nereye');
        var neredenSuggestions = document.getElementById('caht-nereden-suggestions');
        var nereyeSuggestions = document.getElementById('caht-nereye-suggestions');

        if (!neredenInput || !nereyeInput) return;

        var autocompleteService = new google.maps.places.AutocompleteService();
        var placesService = new google.maps.places.PlacesService(document.createElement('div'));

        function displaySuggestions(input, suggestionsContainer, suggestions) {
            suggestionsContainer.innerHTML = '';
            if (suggestions.length === 0) {
                suggestionsContainer.classList.remove('active');
                return;
            }
            suggestionsContainer.classList.add('active');

            suggestions.forEach(function(suggestion) {
                var div = document.createElement('div');
                div.className = 'caht-suggestion-item';

                var priorityPlace = priorityPlaces.find(function(p) {
                    return p.placeId === suggestion.place_id;
                });

                var iconClass = 'fas fa-map-marker-alt';
                if (priorityPlace) {
                    div.classList.add('priority');
                    iconClass = priorityPlace.type === 'airport' ? 'fas fa-plane' : 'fas fa-hotel';
                }

                div.innerHTML = '<i class="' + iconClass + '"></i> ' + suggestion.description;

                div.addEventListener('click', function() {
                    input.value = suggestion.description;
                    suggestionsContainer.innerHTML = '';
                    suggestionsContainer.classList.remove('active');

                    placesService.getDetails({ placeId: suggestion.place_id }, function(place, status) {
                        if (status === google.maps.places.PlacesServiceStatus.OK && place.geometry && place.geometry.location) {
                            var location = place.geometry.location;
                            if (input.id === 'caht-nereden') {
                                document.getElementById('caht-nereden-lat').value = location.lat();
                                document.getElementById('caht-nereden-lng').value = location.lng();
                            } else {
                                document.getElementById('caht-nereye-lat').value = location.lat();
                                document.getElementById('caht-nereye-lng').value = location.lng();
                            }
                        }
                    });
                });

                suggestionsContainer.appendChild(div);
            });
        }

        function handleInput(input, suggestionsContainer) {
            input.addEventListener('input', function() {
                var query = input.value.trim();
                if (!query) {
                    suggestionsContainer.innerHTML = '';
                    suggestionsContainer.classList.remove('active');
                    return;
                }

                var filteredPriority = priorityPlaces.filter(function(place) {
                    return place.name.toLowerCase().startsWith(query.toLowerCase());
                });

                autocompleteService.getPlacePredictions({
                    input: query,
                    types: ['establishment', 'geocode'],
                    componentRestrictions: { country: 'tr' },
                    location: new google.maps.LatLng(41.0082, 28.9784),
                    radius: 50000,
                    language: isEnglish ? 'en' : 'tr'
                }, function(predictions, status) {
                    if (status === google.maps.places.PlacesServiceStatus.OK && predictions) {
                        var suggestions = filteredPriority.map(function(place) {
                            return {
                                description: place.name,
                                place_id: place.placeId
                            };
                        }).concat(predictions.filter(function(pred) {
                            return !priorityPlaces.some(function(p) {
                                return p.placeId === pred.place_id;
                            });
                        }));

                        displaySuggestions(input, suggestionsContainer, suggestions);
                    } else {
                        if (filteredPriority.length > 0) {
                            displaySuggestions(input, suggestionsContainer, filteredPriority.map(function(p) {
                                return { description: p.name, place_id: p.placeId };
                            }));
                        } else {
                            suggestionsContainer.innerHTML = '';
                            suggestionsContainer.classList.remove('active');
                        }
                    }
                });
            });

            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                    suggestionsContainer.innerHTML = '';
                    suggestionsContainer.classList.remove('active');
                }
            });
        }

        handleInput(neredenInput, neredenSuggestions);
        handleInput(nereyeInput, nereyeSuggestions);
    }

    // ============================================
    // FLATPICKR
    // ============================================
    function initFlatpickr() {
        var locale = isEnglish ? 'en' : 'tr';

        if (typeof flatpickr === 'undefined') {
            setTimeout(initFlatpickr, 300);
            return;
        }

        flatpickr('#caht-datetime-picker', {
            enableTime: true,
            dateFormat: 'd.m.Y H:i',
            time_24hr: true,
            locale: locale,
            minDate: 'today',
            minuteIncrement: 5,
            allowInput: false
        });
    }

    // ============================================
    // EVENT BINDINGS
    // ============================================
    function bindEvents() {
        // Gidis-Donus Toggle
        var toggleSwitch = document.getElementById('caht-toggle-switch');
        var returnContainer = document.getElementById('caht-return-datetime-container');
        var gidisDonusInput = document.getElementById('caht-gidis-donus');

        if (toggleSwitch) {
            toggleSwitch.addEventListener('click', function() {
                var isActive = this.classList.toggle('active');
                gidisDonusInput.value = isActive ? '1' : '0';

                if (isActive) {
                    returnContainer.style.display = 'block';
                    if (!returnContainer.dataset.initialized) {
                        var locale = isEnglish ? 'en' : 'tr';
                        var gidisTarih = document.getElementById('caht-datetime-picker').value;

                        returnPicker = flatpickr('#caht-return-datetime-picker', {
                            enableTime: true,
                            dateFormat: 'd.m.Y H:i',
                            time_24hr: true,
                            locale: locale,
                            minDate: gidisTarih || 'today',
                            minuteIncrement: 5,
                            allowInput: false
                        });
                        returnContainer.dataset.initialized = 'true';
                    }
                } else {
                    returnContainer.style.display = 'none';
                    document.getElementById('caht-return-datetime-picker').value = '';
                }
            });
        }

        // Gidis tarihi degisince donus minDate guncelle
        var gidisPicker = document.getElementById('caht-datetime-picker');
        if (gidisPicker) {
            gidisPicker.addEventListener('change', function() {
                if (returnPicker && toggleSwitch && toggleSwitch.classList.contains('active')) {
                    returnPicker.set('minDate', this.value || 'today');
                }
            });
        }

        // Yer Degistir
        var swapBtn = document.getElementById('caht-swap-locations');
        if (swapBtn) {
            swapBtn.addEventListener('click', function(e) {
                e.preventDefault();
                var nereden = document.getElementById('caht-nereden');
                var nereye = document.getElementById('caht-nereye');
                var neredenLat = document.getElementById('caht-nereden-lat');
                var neredenLng = document.getElementById('caht-nereden-lng');
                var nereyeLat = document.getElementById('caht-nereye-lat');
                var nereyeLng = document.getElementById('caht-nereye-lng');

                var tempVal = nereden.value;
                nereden.value = nereye.value;
                nereye.value = tempVal;

                var tempLat = neredenLat.value;
                var tempLng = neredenLng.value;
                neredenLat.value = nereyeLat.value;
                neredenLng.value = nereyeLng.value;
                nereyeLat.value = tempLat;
                nereyeLng.value = tempLng;
            });
        }

        // === KÖK SORUN BURADA ÇÖZÜLDÜ ===
        // Form Submit - ARTIK form.submit() YERINE window.location.href KULLANIYORUZ
        var form = document.getElementById('caht-transfer-form');
        var searchBtn = document.getElementById('caht-search-btn');

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Native submit'i engelle

                var nereden = document.getElementById('caht-nereden').value.trim();
                var nereye = document.getElementById('caht-nereye').value.trim();
                var gidisTarih = document.getElementById('caht-datetime-picker').value;
                var gidisDonus = document.getElementById('caht-gidis-donus').value;
                var donusTarih = document.getElementById('caht-return-datetime-picker').value;

                // Validasyon
                if (!nereden || !nereye) {
                    alert(isEnglish ? 'Please fill in FROM and TO fields.' : 'Lutfen Nereden ve Nereye alanlarini doldurun.');
                    return;
                }

                if (!gidisTarih) {
                    alert(isEnglish ? 'Please select a date and time.' : 'Lutfen tarih ve saat secin.');
                    return;
                }

                if (gidisDonus === '1' && !donusTarih) {
                    alert(isEnglish ? 'Please select a return date and time.' : 'Lutfen donus tarihini ve saatini secin.');
                    return;
                }

                var neredenLat = document.getElementById('caht-nereden-lat').value;
                var neredenLng = document.getElementById('caht-nereden-lng').value;
                var nereyeLat = document.getElementById('caht-nereye-lat').value;
                var nereyeLng = document.getElementById('caht-nereye-lng').value;

                if (neredenLat && neredenLng && nereyeLat && nereyeLng) {
                    // Koordinatlar var, doğrudan yönlendir
                    redirectToSonuc(nereden, nereye, neredenLat, neredenLng, nereyeLat, nereyeLng);
                } else {
                    // Koordinatlar yok, geocode et
                    geocodeAddress(nereden, function(err, neredenCoords) {
                        if (err) {
                            alert(isEnglish ? 'Could not get coordinates for FROM address.' : 'Nereden adresi icin koordinat alinamadi.');
                            return;
                        }
                        document.getElementById('caht-nereden-lat').value = neredenCoords.lat;
                        document.getElementById('caht-nereden-lng').value = neredenCoords.lng;

                        geocodeAddress(nereye, function(err, nereyeCoords) {
                            if (err) {
                                alert(isEnglish ? 'Could not get coordinates for TO address.' : 'Nereye adresi icin koordinat alinamadi.');
                                return;
                            }
                            document.getElementById('caht-nereye-lat').value = nereyeCoords.lat;
                            document.getElementById('caht-nereye-lng').value = nereyeCoords.lng;

                            redirectToSonuc(nereden, nereye, neredenCoords.lat, neredenCoords.lng, nereyeCoords.lat, nereyeCoords.lng);
                        });
                    });
                }
            });
        }

        function geocodeAddress(address, callback) {
            if (!googleMapsLoaded || typeof google === 'undefined') {
                callback(null, { lat: '', lng: '' });
                return;
            }

            var geocoder = new google.maps.Geocoder();
            geocoder.geocode({
                address: address,
                language: isEnglish ? 'en' : 'tr'
            }, function(results, status) {
                if (status === 'OK' && results[0]) {
                    var location = results[0].geometry.location;
                    callback(null, { lat: location.lat(), lng: location.lng() });
                } else {
                    callback(new Error('Geocode failed: ' + status), null);
                }
            });
        }

        // === YENI FONKSIYON: DistanceMatrix hesapla ve yönlendir ===
        function redirectToSonuc(nereden, nereye, nLat, nLng, nyLat, nyLng) {
            var originalText = searchBtn.innerHTML;
            searchBtn.disabled = true;
            searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (isEnglish ? 'Calculating...' : 'Hesaplaniyor...');

            if (!googleMapsLoaded || typeof google === 'undefined') {
                // Google Maps yok, mesafe = 0 ile yönlendir
                buildAndRedirect(nereden, nereye, nLat, nLng, nyLat, nyLng, '0', '0');
                return;
            }

            var service = new google.maps.DistanceMatrixService();
            service.getDistanceMatrix({
                origins: [nereden],
                destinations: [nereye],
                travelMode: google.maps.TravelMode.DRIVING,
                unitSystem: google.maps.UnitSystem.METRIC,
                language: isEnglish ? 'en' : 'tr'
            }, function(response, status) {
                searchBtn.disabled = false;
                searchBtn.innerHTML = originalText;

                var mesafe = '0';
                var sure = '0';

                if (status === 'OK') {
                    var element = response.rows[0].elements[0];
                    if (element.status === 'OK') {
                        mesafe = (element.distance.value / 1000).toFixed(1);
                        sure = Math.round(element.duration.value / 60);
                    }
                }

                buildAndRedirect(nereden, nereye, nLat, nLng, nyLat, nyLng, mesafe, sure);
            });
        }

        // === YENI FONKSIYON: URL oluştur ve yönlendir ===
        function buildAndRedirect(nereden, nereye, nLat, nLng, nyLat, nyLng, mesafe, sure) {
            var gidisTarih = document.getElementById('caht-datetime-picker').value;
            var donusTarih = document.getElementById('caht-return-datetime-picker').value || '';
            var gidisDonus = document.getElementById('caht-gidis-donus').value;
            var kisiSayisi = document.getElementById('caht-kisi-sayisi').value;

            var params = new URLSearchParams();
            params.set('nereden', nereden);
            params.set('nereye', nereye);
            params.set('nereden_lat', nLat);
            params.set('nereden_lng', nLng);
            params.set('nereye_lat', nyLat);
            params.set('nereye_lng', nyLng);
            params.set('gidis_tarih', gidisTarih);
            params.set('donus_tarih', donusTarih);
            params.set('gidis_donus', gidisDonus);
            params.set('kisi_sayisi', kisiSayisi);
            params.set('mesafe', mesafe);
            params.set('sure', sure);

            var redirectUrl = sonucUrl + (sonucUrl.indexOf('?') !== -1 ? '&' : '?') + params.toString();
            console.log('CAHT: Yonlendiriliyor ->', redirectUrl);
            window.location.href = redirectUrl;
        }
    }

})();
</script>