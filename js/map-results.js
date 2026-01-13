/**
 * Google Maps Results functionality for Search Custom Posts plugin
 */
(function($) {
    'use strict';
    
    var map = null;
    var markers = [];
    var searchMarker = null;
    var searchCircle = null;
    var infoWindow = null;
    
    $(document).ready(function() {

        // Check if map data is available
        if (typeof scpMapData === 'undefined') {
            console.error('Map data not available');
            return;
        }
        
        // Wait for Google Maps API to load
        if (typeof google !== 'undefined' && google.maps) {
            initMap();
        } else {
            // Wait for Google Maps to load
            var checkGoogleMaps = setInterval(function() {
                if (typeof google !== 'undefined' && google.maps) {
                    clearInterval(checkGoogleMaps);
                    initMap();
                }
            }, 100);
        }
    });
   
    /**
     * Escape special regex characters
     */
    function escapeRegex(text) {
        return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    
    /**
     * Initialize the map with markers
     */
    function initMap() {
        var mapElement = document.getElementById(scpMapData.mapId);
        
        if (!mapElement) {
            console.error('Map element not found');
            return;
        }
        
        var searchLat = scpMapData.searchLocation.latitude;
        var searchLng = scpMapData.searchLocation.longitude;
        var radius = scpMapData.radius;
        var posts = scpMapData.posts;
        
        // Initialize map centered on search location
        map = new google.maps.Map(mapElement, {
            zoom: 12,
            center: { lat: searchLat, lng: searchLng },
            mapTypeControl: true,
            streetViewControl: true,
            fullscreenControl: true
        });
        
        // Create info window (reused for all markers)
        infoWindow = new google.maps.InfoWindow();
        
        // Add search location marker (center marker)
        searchMarker = new google.maps.Marker({
            position: { lat: searchLat, lng: searchLng },
            map: map,
            title: 'Search Location',
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 8,
                fillColor: '#4285F4',
                fillOpacity: 1,
                strokeColor: '#FFFFFF',
                strokeWeight: 2
            },
            zIndex: google.maps.Marker.MAX_ZINDEX + 1
        });
        
        // Add search location info window
        var searchInfoContent = '<div style="padding: 10px;">' +
            '<strong>Search Location</strong><br>' +
            'Latitude: ' + searchLat.toFixed(6) + '<br>' +
            'Longitude: ' + searchLng.toFixed(6) + '<br>' +
            'Radius: ' + (radius / 1000).toFixed(2) + ' km';
        
        searchMarker.addListener('click', function() {
            infoWindow.setContent(searchInfoContent);
            infoWindow.open(map, searchMarker);
        });
        
        // Add circle to show search radius
        searchCircle = new google.maps.Circle({
            strokeColor: '#4285F4',
            strokeOpacity: 0.6,
            strokeWeight: 2,
            fillColor: '#4285F4',
            fillOpacity: 0.15,
            map: map,
            center: { lat: searchLat, lng: searchLng },
            radius: radius // radius in meters
        });
        
        // Add markers for each post
        if (posts && posts.length > 0) {
            var bounds = new google.maps.LatLngBounds();
            
            // Add search location to bounds
            bounds.extend({ lat: searchLat, lng: searchLng });
            
            posts.forEach(function(post) {
                var marker = new google.maps.Marker({
                    position: { lat: post.latitude, lng: post.longitude },
                    map: map,
                    title: post.title,
                    animation: google.maps.Animation.DROP
                });
                
                // Create info window content for post
                var infoContent = '<div style="padding: 10px; max-width: 300px;">' +
                    '<strong><a href="' + post.url + '" target="_blank">' + escapeHtml(post.title) + '</a></strong>';
                
                if (post.excerpt) {
                    infoContent += '<br><br>' + escapeHtml(post.excerpt.substring(0, 150));
                    if (post.excerpt.length > 150) {
                        infoContent += '...';
                    }
                }
                
                infoContent += '<br><br><a href="' + post.url + '" target="_blank" style="color: #4285F4;">View Details →</a>';
                infoContent += '</div>';
                
                // Add click listener to marker
                marker.addListener('click', function() {
                    infoWindow.setContent(infoContent);
                    infoWindow.open(map, marker);
                });
                
                markers.push(marker);
                bounds.extend({ lat: post.latitude, lng: post.longitude });
            });
            
            // Fit map to show all markers and circle
            map.fitBounds(bounds, {
                padding: 50
            });
        } else {
            // If no posts, just show search location
            map.setZoom(13);
        }
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
})(jQuery);



