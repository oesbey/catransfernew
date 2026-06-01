/**
 * Transfer Form JavaScript
 *
 * @package Codeapp_Havalimani_Transfer
 */

(function($) {
    'use strict';

    var lang = document.documentElement.lang || 'tr';
    var isEnglish = lang.indexOf('en') !== -1;

    var priorityPlaces = [
        { name: "Sabiha Gökçen Havalimanı", placeId: "ChIJRUCHGUXHyhQRf2y6vQU8Zuo", type: "airport" },
        { name: "İstanbul Havalimanı", placeId: "ChIJ6bT6y6PHyhQRrS83yH-JUJI", type: "airport" },
        { name: "Atatürk Havalimanı", placeId: "ChIJGQX6lYbHyhQRjZ3Z3q3Z3q3", type: "airport" },
        { name: "Hilton Istanbul Bosphorus", placeId: "ChIJI8qB7pnAyhQRm3vQh5zQz3Q", type: "hotel" }
    ];

    var returnPicker = null;
    var googleMapsLoaded = false;
    var initAttempts = 0;
    var maxAttempts = 20;

    // ============================================
    // INIT
    // ============================================
    $(document).ready(function() {
        initGoogleMaps();
        initFlatpickr();
        bindEvents();
    });

    // ============================================
    // GOOGLE MAPS INIT (DAHA SAĞLAM)
    // ============================================
    function initGoogleMaps() {
        // Google Maps key yoksa console uyarısı ver ama devam et
        if (typeof caht_public !== 'undefined' && !caht_public.has_google_key) {
            console.warn('CAHT: Google Maps API key eksik. Yerel autocomplete calisacak.');
            initLocalAutocomplete();
            return;
        }

        if (typeof google !== 'undefined' && google.maps && google.maps.places) {
            googleMapsLoaded = true;
            initAutocomplete();
            return;
        }

        initAttempts++;
        if (initAttempts < maxAttempts) {
            setTimeout(initGoogleMaps, 500);
        } else {
            console.warn('CAHT: Google Maps yuklenemedi. Yerel autocomplete calisacak.');
            initLocalAutocomplete();
        }
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

        function handleLocalInput(input, suggestionsContainer) {
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

        handleLocalInput(neredenInput, neredenSuggestions);
        handleLocalInput(nereyeInput, nereyeSuggestions);
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
                        if (status === google.maps.places.PlacesServiceStatus.OK) {
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
            console.log('CAHT: Flatpickr henüz yüklenmedi, bekleniyor...');
            setTimeout(initFlatpickr, 500);
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
        // Gidiş-Dönüş Toggle
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

        // Gidiş tarihi değişince dönüş minDate güncelle
        var gidisPicker = document.getElementById('caht-datetime-picker');
        if (gidisPicker) {
            gidisPicker.addEventListener('change', function() {
                if (returnPicker && toggleSwitch && toggleSwitch.classList.contains('active')) {
                    returnPicker.set('minDate', this.value || 'today');
                }
            });
        }

        // Yer Değiştir
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

        // Form Submit
        var form = document.getElementById('caht-transfer-form');
        var searchBtn = document.getElementById('caht-search-btn');

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                var nereden = document.getElementById('caht-nereden').value.trim();
                var nereye = document.getElementById('caht-nereye').value.trim();
                var gidisTarih = document.getElementById('caht-datetime-picker').value;
                var gidisDonus = document.getElementById('caht-gidis-donus').value;
                var donusTarih = document.getElementById('caht-return-datetime-picker').value;

                // Validasyon
                if (!nereden || !nereye) {
                    alert(isEnglish ? 'Please fill in FROM and TO fields.' : 'Lütfen Nereden ve Nereye alanlarını doldurun.');
                    return;
                }

                if (!gidisTarih) {
                    alert(isEnglish ? 'Please select a date and time.' : 'Lütfen tarih ve saat seçin.');
                    return;
                }

                if (gidisDonus === '1' && !donusTarih) {
                    alert(isEnglish ? 'Please select a return date and time.' : 'Lütfen dönüş tarihini ve saatini seçin.');
                    return;
                }

                // Koordinat kontrolü
                var neredenLat = document.getElementById('caht-nereden-lat').value;
                var neredenLng = document.getElementById('caht-nereden-lng').value;
                var nereyeLat = document.getElementById('caht-nereye-lat').value;
                var nereyeLng = document.getElementById('caht-nereye-lng').value;

                if (neredenLat && neredenLng && nereyeLat && nereyeLng) {
                    calculateDistance(nereden, nereye);
                } else {
                    geocodeAddress(nereden, function(err, neredenCoords) {
                        if (err) {
                            alert(isEnglish ? 'Could not get coordinates for FROM address.' : 'Nereden adresi için koordinat alınamadı.');
                            return;
                        }
                        document.getElementById('caht-nereden-lat').value = neredenCoords.lat;
                        document.getElementById('caht-nereden-lng').value = neredenCoords.lng;

                        geocodeAddress(nereye, function(err, nereyeCoords) {
                            if (err) {
                                alert(isEnglish ? 'Could not get coordinates for TO address.' : 'Nereye adresi için koordinat alınamadı.');
                                return;
                            }
                            document.getElementById('caht-nereye-lat').value = nereyeCoords.lat;
                            document.getElementById('caht-nereye-lng').value = nereyeCoords.lng;

                            calculateDistance(nereden, nereye);
                        });
                    });
                }
            });
        }

        function geocodeAddress(address, callback) {
            if (!googleMapsLoaded || typeof google === 'undefined') {
                // Google yoksa direkt formu submit et (koordinatsız)
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

        function calculateDistance(nereden, nereye) {
            searchBtn.disabled = true;
            var originalText = searchBtn.innerHTML;
            searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (isEnglish ? 'Calculating...' : 'Hesaplanıyor...');

            if (!googleMapsLoaded || typeof google === 'undefined') {
                // Google yoksa mesafe hesaplayamayız, varsayılan değerle devam et
                document.getElementById('caht-mesafe').value = '0';
                document.getElementById('caht-sure').value = '0';
                searchBtn.disabled = false;
                searchBtn.innerHTML = originalText;
                form.submit();
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

                if (status !== 'OK') {
                    console.error('CAHT DistanceMatrix error:', status);
                    document.getElementById('caht-mesafe').value = '0';
                    document.getElementById('caht-sure').value = '0';
                    form.submit();
                    return;
                }

                var element = response.rows[0].elements[0];
                if (element.status !== 'OK') {
                    document.getElementById('caht-mesafe').value = '0';
                    document.getElementById('caht-sure').value = '0';
                    form.submit();
                    return;
                }

                var distanceVal = element.distance.value / 1000;
                var durationVal = element.duration.value / 60;

                document.getElementById('caht-mesafe').value = distanceVal.toFixed(1);
                document.getElementById('caht-sure').value = Math.round(durationVal);

                form.submit();
            });
        }
    }

})(jQuery);