/**
 * Google Maps Autocomplete functionality for Search Custom Posts plugin
 */
(function($) {
    'use strict';
    
    var map = null;
    var marker = null;
    var circle = null;
    console.log('[Autocomplete] jQuery loaded');
    $(document).ready(function() {

        console.log('[Autocomplete] Document ready');
        // Initialize Google Maps Autocomplete
        // Wait for Google Maps API to be fully loaded
        function waitForGoogleMaps(callback, maxAttempts) {
            maxAttempts = maxAttempts || 50; // Try for up to 5 seconds (50 * 100ms)
            var attempts = 0;
            
            function checkGoogleMaps() {
                attempts++;
                if (typeof google !== 'undefined' && google.maps && google.maps.places && google.maps.places.Autocomplete) {
                    console.log('[Autocomplete] Google Maps API loaded after ' + attempts + ' attempts');
                    callback();
                } else if (attempts < maxAttempts) {
                    setTimeout(checkGoogleMaps, 100);
                } else {
                    console.error('[Autocomplete] Google Maps API not loaded after ' + maxAttempts + ' attempts');
                }
            }
            
            checkGoogleMaps();
        }
        
        waitForGoogleMaps(function() {
            initAutocomplete();
        });
        
        // Update no results message with address from GET parameters
        updateNoResultsMessage();
    });
    
    /**
     * Initialize Google Maps Autocomplete
     */
    function initAutocomplete() {

        console.log('[Autocomplete] Initializing autocomplete');

        // Get target country from localized data
        var targetCountry = (typeof scpData !== 'undefined' && scpData.targetCountry) ? scpData.targetCountry : null;
        
        // Initialize for regular form
        var addressInput = document.getElementById('scp-address-input');
        if (addressInput) {
            initFormAutocomplete(
                'scp-address-input', 
                'scp-latitude', 
                'scp-longitude', 
                'scp-full-address', 
                'scp-radius', 
                'scp-search-form',
                true,
                targetCountry);
        }
        
        // Initialize for short form
        var addressInputShort = document.getElementById('scp-address-input-short');
        if (addressInputShort) {
            initFormAutocomplete(
                'scp-address-input-short', 
                'scp-latitude-short', 
                'scp-longitude-short', 
                'scp-full-address-short', 
                'scp-radius-short', 
                'scp-search-short-form',
                false,
                targetCountry);
        }
    }
    
    /**
     * Initialize autocomplete for a specific form
     */
    function initFormAutocomplete(inputId, latId, lngId, fullAddressId, radiusId, formId, showMap, targetCountry) {
        var addressInput = document.getElementById(inputId);
        
        if (!addressInput) {
            console.log('[Autocomplete] Input element not found:', inputId);
            return;
        }
        
        // Create Autocomplete options
        var autocompleteOptions = {
            types: ['address']
        };
        
        // Add country restriction if target country is set
        if (targetCountry) {
            autocompleteOptions.componentRestrictions = { country: targetCountry };
        }
        
        // Create Autocomplete instance
        // Note: fields option is used to get only the data we need
        autocompleteOptions.fields = ['geometry', 'formatted_address', 'name'];
        var autocomplete = new google.maps.places.Autocomplete(addressInput, autocompleteOptions);
        
        console.log('[Autocomplete] Initialized for input:', {
            inputId: inputId,
            formId: formId,
            targetCountry: targetCountry || 'none',
            showMap: showMap,
            hasValue: addressInput.value ? 'yes' : 'no'
        });
        
        // Function to handle place selection and populate fields
        function handlePlaceSelection(place) {
            if (!place || !place.geometry) {
                console.log('[Autocomplete] Place selected but geometry is missing:', {
                    inputId: inputId,
                    place: place,
                    placeName: place ? (place.name || 'Unknown') : 'No place',
                    formattedAddress: place ? (place.formatted_address || 'N/A') : 'N/A'
                });
                return;
            }
            
            // Get full address
            var fullAddress = place.formatted_address || place.name;
            
            // Get latitude and longitude
            var lat = place.geometry.location.lat();
            var lng = place.geometry.location.lng();
            
            // Store values in hidden fields
            $('#' + latId).val(lat);
            $('#' + lngId).val(lng);
            $('#' + fullAddressId).val(fullAddress);
            
            // Update map with selected location (only for regular form)
            if (showMap) {
                updateMap(lat, lng, fullAddress);
            }
            
            // Log for debugging (remove in production)
            console.log('[Autocomplete] Place successfully selected:', {
                inputId: inputId,
                fullAddress: fullAddress,
                latitude: lat,
                longitude: lng,
                place: place
            });
        }
        
        // When a place is selected, get the details
        autocomplete.addListener('place_changed', function() {
            console.log('[Autocomplete] place_changed event fired for:', inputId);
            var place = autocomplete.getPlace();
            handlePlaceSelection(place);
        });
        
        // If input has a pre-filled value from GET parameters, geocode it
        if (addressInput.value) {
            var existingLat = $('#' + latId).val();
            var existingLng = $('#' + lngId).val();
            
            // Only geocode if latitude/longitude are not already set
            if (!existingLat || !existingLng) {
                console.log('[Autocomplete] Input has pre-filled value, geocoding address:', addressInput.value);
                
                // Use PlacesService to find the place by the address string
                var geocoder = new google.maps.Geocoder();
                var geocodeRequest = {
                    address: addressInput.value
                };
                
                // Add country restriction if target country is set
                if (targetCountry) {
                    geocodeRequest.componentRestrictions = { country: targetCountry };
                }
                
                geocoder.geocode(geocodeRequest, function(results, status) {
                    if (status === 'OK' && results && results.length > 0) {
                        // Convert GeocoderResult to Place-like object
                        var result = results[0];
                        var place = {
                            geometry: {
                                location: result.geometry.location
                            },
                            formatted_address: result.formatted_address,
                            name: result.formatted_address
                        };
                        
                        console.log('[Autocomplete] Geocoding successful for pre-filled address');
                        handlePlaceSelection(place);
                    } else {
                        console.log('[Autocomplete] Geocoding failed for pre-filled address:', status);
                    }
                });
            } else {
                console.log('[Autocomplete] Input has pre-filled value and coordinates already exist, skipping geocoding');
            }
        }
        
        // Handle radius change (only for regular form with map)
        if (showMap) {
            $('#' + radiusId).on('change', function() {
                updateCircle();
            });
        }
        
        // Handle form submission
        $('#' + formId).on('submit', function(e) {
            e.preventDefault();
            
            var latitude = $('#' + latId).val();
            var longitude = $('#' + lngId).val();
            var radius = $('#' + radiusId).val();
            var fullAddress = $('#' + fullAddressId).val();

            if (!latitude || !longitude) {
                console.log('[Autocomplete] Form submitted but coordinates are missing:', {
                    formId: formId,
                    inputId: inputId,
                    latitude: latitude || 'missing',
                    longitude: longitude || 'missing',
                    fullAddress: fullAddress || 'missing',
                    addressInputValue: addressInput.value || 'empty'
                });
                return;
            }
            
            // Get target URL from form's target attribute
            var targetUrl = $(this).attr('target');
            
            if (!targetUrl) {
                console.log('[Autocomplete] Form submitted but target URL is missing:', {
                    formId: formId,
                    inputId: inputId
                });
                return;
            }
            
            // Build URL with query parameters
            var url = new URL(targetUrl, window.location.origin);
            url.searchParams.set('latitude', parseFloat(latitude).toFixed(6));
            url.searchParams.set('longitude', parseFloat(longitude).toFixed(6));
            url.searchParams.set('radius', radius);
            url.searchParams.set('address', fullAddress);
            
            // Redirect to target URL with parameters
            window.location.href = url.toString();
        });
    }
    
    /**
     * Initialize or update the map with the selected location
     */
    function updateMap(lat, lng, address) {
        var mapContainer = document.getElementById('scp-map');
        var mapWrapper = document.getElementById('scp-map-container');
        
        if (!mapContainer) {
            return;
        }
        
        // Show map container
        $(mapWrapper).fadeIn();
        
        // Initialize map if it doesn't exist
        if (!map) {
            map = new google.maps.Map(mapContainer, {
                zoom: 15,
                center: { lat: lat, lng: lng },
                mapTypeControl: false,
                streetViewControl: true,
                fullscreenControl: true
            });
        } else {
            // Update map center
            map.setCenter({ lat: lat, lng: lng });
        }
        
        // Remove existing marker if any
        if (marker) {
            marker.setMap(null);
        }
        
        // Create new marker
        marker = new google.maps.Marker({
            position: { lat: lat, lng: lng },
            map: map,
            title: address,
            animation: google.maps.Animation.DROP
        });
        
        // Create info window
        var infoWindow = new google.maps.InfoWindow({
            content: '<div style="padding: 5px;"><strong>' + address + '</strong></div>'
        });
        
        // Open info window
        //infoWindow.open(map, marker);
        
        // Add click listener to marker
        marker.addListener('click', function() {
            infoWindow.open(map, marker);
        });
        
        // Update circle if map is visible
        updateCircle();
    }
    
    /**
     * Update or create circle on the map based on radius input
     */
    function updateCircle() {
        var mapContainer = document.getElementById('scp-map');
        var radiusInput = $('#scp-radius');
        
        // Check if map is visible and exists
        if (!mapContainer || !map || !marker) {
            return;
        }
        
        // Check if map container is visible
        if ($(mapContainer).closest('#scp-map-container').is(':hidden')) {
            return;
        }
        
        // Get radius value in meters
        var radius = parseInt(radiusInput.val(), 10);
        
        if (isNaN(radius) || radius <= 0) {
            // Remove circle if radius is invalid
            if (circle) {
                circle.setMap(null);
                circle = null;
            }
            return;
        }
        
        // Get marker position
        var markerPosition = marker.getPosition();
        
        // Remove existing circle if any
        if (circle) {
            circle.setMap(null);
        }
        
        // Create new circle
        circle = new google.maps.Circle({
            strokeColor: '#FF0000',
            strokeOpacity: 0.8,
            strokeWeight: 2,
            fillColor: '#FF0000',
            fillOpacity: 0.35,
            map: map,
            center: markerPosition,
            radius: radius // radius in meters
        });
        
        // Adjust map zoom to show the entire circle
        var bounds = circle.getBounds();
        if (bounds) {
            map.fitBounds(bounds, {
                padding: 50 // Add padding in pixels to ensure circle is fully visible
            });
        }
    }
    
    /**
     * Show message to user
     */
    function showMessage(message, type) {
        var messageDiv = $('#scp-form-message');
        messageDiv.removeClass('scp-message-success scp-message-error')
                  .addClass('scp-message-' + type)
                  .text(message)
                  .fadeIn();
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            messageDiv.fadeOut();
        }, 5000);
    }
    
    /**
     * Trigger search results update (placeholder for future implementation)
     */
    function triggerSearchResults(latitude, longitude, fullAddress) {
        // This will be implemented when show_search_results is functional
        console.log('Search triggered with:', {
            latitude: latitude,
            longitude: longitude,
            address: fullAddress
        });
    }

     
    /**
     * Update no results message with address from GET parameters
     */
    function updateNoResultsMessage() {
        // Get settings from map data
        var placeholder = (typeof scpData !== 'undefined' && scpData.missingPlaceholder) ? scpData.missingPlaceholder : '';
        var message = (typeof scpData !== 'undefined' && scpData.missingMessage) ? scpData.missingMessage : '';
        

        console.log('scpData', scpData);
        console.log('placeholder', placeholder);
        console.log('message', message);
        // If no placeholder or message is configured, skip
        if (!placeholder || !message) {
            return;
        }
        
        // Get address from URL GET parameters
        var urlParams = new URLSearchParams(window.location.search);
        var address = urlParams.get('address');
        
        if (!address) {
            return;
        }
        
        // Replace {address} placeholder in message with actual address
        var finalMessage = message.replace(/{address}/gi, address);
        
        // Search for the placeholder marker - first in the specific div with class 'w-grid-none type_message'
        var noResultsDiv = document.querySelector('.w-grid-none.type_message'); // TODO - add settings in admin panel to select the div to replace the placeholder marker
      
        if (noResultsDiv) {
            // Check if the div contains the placeholder marker
            var divText = noResultsDiv.textContent || noResultsDiv.innerText || '';
            var divHtml = noResultsDiv.innerHTML || '';
            
            if (divText.indexOf(placeholder) !== -1 || divHtml.indexOf(placeholder) !== -1) {
                // Replace placeholder marker with the message
                if (divHtml && divHtml.indexOf(placeholder) !== -1) {
                    // Replace in HTML while preserving structure
                    noResultsDiv.innerHTML = divHtml.replace(new RegExp(escapeRegex(placeholder), 'gi'), escapeHtml(finalMessage));
                } else {
                    // Replace in text content
                    noResultsDiv.textContent = divText.replace(new RegExp(escapeRegex(placeholder), 'gi'), finalMessage);
                }
                return;
            }
        }
    }
    
    /**
     * Escape special regex characters
     */
    function escapeRegex(text) {
        return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    
    /**
     * Escape HTML characters
     */
    function escapeHtml(text) {
        return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
})(jQuery);

