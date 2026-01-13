/**
 * Google Maps Autocomplete functionality for Search Custom Posts plugin
 */
(function($) {
    'use strict';
    
    var map = null;
    var marker = null;
    var circle = null;
    
    $(document).ready(function() {
        // Initialize Google Maps Autocomplete
        if (typeof google !== 'undefined' && google.maps && google.maps.places) {
            initAutocomplete();
        } else {
            console.error('Google Maps API not loaded');
        }
    });
    
    /**
     * Initialize Google Maps Autocomplete
     */
    function initAutocomplete() {
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
        var autocomplete = new google.maps.places.Autocomplete(addressInput, autocompleteOptions);
        
        // When a place is selected, get the details
        autocomplete.addListener('place_changed', function() {
            var place = autocomplete.getPlace();
            
            if (!place.geometry) {
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
            console.log('Selected address:', {
                fullAddress: fullAddress,
                latitude: lat,
                longitude: lng,
                place: place
            });
        });
        
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

            if (!latitude || !longitude) {
                return;
            }
            
            // Get target URL from form's target attribute
            var targetUrl = $(this).attr('target');
            
            if (!targetUrl) {
                return;
            }
            
            // Build URL with query parameters
            var url = new URL(targetUrl, window.location.origin);
            url.searchParams.set('latitude', latitude);
            url.searchParams.set('longitude', longitude);
            url.searchParams.set('radius', radius);
            
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
    
})(jQuery);

