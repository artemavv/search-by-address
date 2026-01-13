<?php

/**
 * Create mass test posts with random locations, centered around target location
 */
function create_mass_test_posts( $target_lat, $target_lng, $posts_count = 100) {
    // Get settings from get_search_results_setting()
    $post_type = Search_Posts_By_Address::get_search_results_setting('post_type');
    $meta_key_latitude = Search_Posts_By_Address::get_search_results_setting('meta_key_latitude');
    $meta_key_longitude = Search_Posts_By_Address::get_search_results_setting('meta_key_longitude');
    
    // Default to 'post' if no post type is set
    if (empty($post_type)) {
        $post_type = 'post';
    }
    
    // Default meta keys if not set
    if (empty($meta_key_latitude)) {
        $meta_key_latitude = 'latitude';
    }
    if (empty($meta_key_longitude)) {
        $meta_key_longitude = 'longitude';
    }
    
    for ($i = 0; $i < $posts_count; $i++) {
        // Generate random coordinates within ~10km radius of target location
        // Rough approximation: 1 degree latitude ≈ 111km, so ~0.09 degrees ≈ 10km
        $random_lat = $target_lat + (rand(-9000, 9000) / 100000); // ±0.09 degrees
        $random_lng = $target_lng + (rand(-9000, 9000) / 100000); // ±0.09 degrees
        
        $post_id = wp_insert_post(array(
            'post_title' => 'Test Post - ' . $random_lat . ' - ' . $random_lng . ' - ' . $i,
            'post_content' => 'This is a test post ' . $i,
            'post_status' => 'publish',
            'post_type' => $post_type
        ));
        
        // Add latitude and longitude metadata
        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, $meta_key_latitude, $random_lat);
            update_post_meta($post_id, $meta_key_longitude, $random_lng);
        }
        else {
            echo 'Error creating post: ' . $post_id->get_error_message();
            die();
        }
    }

    echo 'Mass test posts created successfully';
    die();
}

/**
 * Hook to WordPress init action to handle mass test post creation
 */
function handle_create_mass_test_posts() {
    if (isset($_GET['create_mass_test_posts'])) {
        $target_lat = isset($_GET['target_lat']) ? floatval($_GET['target_lat']) : 0;
        $target_lng = isset($_GET['target_lng']) ? floatval($_GET['target_lng']) : 0;
        $posts_count = isset($_GET['posts_count']) ? intval($_GET['posts_count']) : 100;
        
        create_mass_test_posts($target_lat, $target_lng, $posts_count);
    }
}
add_action('init', 'handle_create_mass_test_posts');

// http://wynaj.local/search-results/?target_lat=52.233553&target_lng=21.019315&create_mass_test_posts=1&posts_count=30