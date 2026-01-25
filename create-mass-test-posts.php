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


// /?create_mass_test_posts=1&posts_count=30

    if (isset($_GET['create_mass_test_posts'])) {
        $target_lat = isset($_GET['target_lat']) ? floatval($_GET['target_lat']) : 0;
        $target_lng = isset($_GET['target_lng']) ? floatval($_GET['target_lng']) : 0;
        $posts_count = isset($_GET['posts_count']) ? intval($_GET['posts_count']) : 100;
        
        create_mass_test_posts($target_lat, $target_lng, $posts_count);
    }


// /?old_key=_test_lat_1&new_key=_latitude&update_meta_keys_for_post_type=1
// /?old_key=_test_lgn_2&new_key=_longitude&update_meta_keys_for_post_type=1


    if (isset($_GET['update_meta_keys_for_post_type'])) {
        $old_key = isset($_GET['old_key']) ? sanitize_text_field($_GET['old_key']) : '';
        $new_key = isset($_GET['new_key']) ? sanitize_text_field($_GET['new_key']) : '';

        $post_type = Search_Posts_By_Address::get_search_results_setting('post_type');
                
        update_meta_keys_for_post_type($old_key, $new_key, $post_type);
    }
}
add_action('init', 'handle_create_mass_test_posts');

/**
 * Update meta_key for posts of a specific post type using SQL JOIN
 *
 * @param string $old_key The current meta key (XXX)
 * @param string $new_key The new meta key (YYY)
 * @param string $post_type The post type to filter by (default: 'post')
 * @return int|false Number of rows affected or false on error
 */
function update_meta_keys_for_post_type($old_key, $new_key, $post_type = 'post') {
    global $wpdb;
    
    // Ensure inputs are valid
    if (empty($old_key) || empty($new_key)) {
        return false;
    }

    // Use $wpdb->posts and $wpdb->postmeta to ensure correct table prefixes
    $query = $wpdb->prepare(
        "UPDATE {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        SET pm.meta_key = %s
        WHERE pm.meta_key = %s
        AND p.post_type = %s",
        $new_key,
        $old_key,
        $post_type
    );

    // Execute the query
    $result = $wpdb->query($query);
    
    return $result;
}
