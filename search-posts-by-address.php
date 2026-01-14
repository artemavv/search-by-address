<?php
/**
 * Plugin Name: Search Posts By Address
 * Plugin URI: https://example.com/search-posts-by-address
 * Description: A plugin that provides search functionality with Google Maps Autocomplete for custom posts.
 * Version: 0.6
 * Author: Artem Avvakumov
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: search-posts-by-address
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SCP_VERSION', '0.6');
define('SCP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCP_TEXTDOMAIN', 'search-posts-by-address');

// Include settings file
require_once SCP_PLUGIN_DIR . 'scp-settings.php';

// Include country codes file
require_once SCP_PLUGIN_DIR . 'country-codes.php';

// Include create mass test posts file
require_once SCP_PLUGIN_DIR . 'create-mass-test-posts.php';

/**
 * Main plugin class
 */
class Search_Posts_By_Address {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Load plugin textdomain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            SCP_TEXTDOMAIN,
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Register shortcodes
        add_shortcode('show_search_by_address_form', array($this, 'render_search_form'));
        add_shortcode('show_search_by_address_short_form', array($this, 'render_search_short_form'));
        add_shortcode('render_search_results_on_map', array($this, 'render_search_results_on_map'));
        
        // Add filter for us_post_list_query_args
        add_filter('us_post_list_query_args', array($this, 'filter_post_list_query_args'), 10, 2);
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue on pages that use the shortcodes
        global $post;
        $has_form_shortcode = is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'show_search_by_address_form') || has_shortcode($post->post_content, 'show_search_by_address_short_form'));
        $has_results_shortcode = is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'render_search_results_on_map');
        
        if ($has_form_shortcode || $has_results_shortcode) {
            // Enqueue Google Maps JavaScript API with Places library
            wp_enqueue_script(
                'google-maps-autocomplete',
                'https://maps.googleapis.com/maps/api/js?key=' . $this->get_google_api_key() . '&libraries=places',
                array(),
                null,
                true
            );
            
            // Enqueue custom JavaScript
            wp_enqueue_script(
                'scp-autocomplete',
                SCP_PLUGIN_URL . 'js/autocomplete.js',
                array('jquery', 'google-maps-autocomplete'),
                SCP_VERSION,
                true
            );
            
            // Enqueue map results JavaScript if results shortcode is present
            if ($has_results_shortcode) {
                wp_enqueue_script(
                    'scp-map-results',
                    SCP_PLUGIN_URL . 'js/map-results.js',
                    array('jquery', 'google-maps-autocomplete'),
                    SCP_VERSION,
                    true
                );
            }
            
            // Enqueue styles
            wp_enqueue_style(
                'scp-style',
                SCP_PLUGIN_URL . 'css/style.css',
                array(),
                SCP_VERSION
            );
            
            // Localize script to pass data to JavaScript
            $target_country = self::get_search_form_setting('target_country');
            wp_localize_script('scp-autocomplete', 'scpData', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('scp_nonce'),
                'targetCountry' => $target_country ? $target_country : null,
                'missingPlaceholder' => self::get_search_results_setting('missing_placeholder'),
                'missingMessage' => self::get_search_results_setting('missing_message')
            ));
        }
    }
    
    /**
     * Get Google Maps API key
     * Retrieves the API key from wp_options table
     * Can still be overridden via filter: add_filter('scp_google_api_key', function() { return 'your-key'; });
     */
    private function get_google_api_key() {
        $api_key = get_option('scp_google_api_key', '');
        return apply_filters('scp_google_api_key', $api_key);
    }

    public static function get_search_form_setting( $setting_name) {
        $setting = get_option('scp_search_form_' . $setting_name, '');
        return apply_filters('scp_search_form_' . $setting_name, $setting);
    }

    public static function get_search_results_setting( $setting_name) {
        $setting = get_option('scp_search_results_' . $setting_name, '');
        return apply_filters('scp_search_results_' . $setting_name, $setting);
    }

    /**
     * Filter us_post_list_query_args to return single latest post when latitude is set
     * 
     * @param array $query_args Query arguments
     * @param array $filled_atts Filled attributes
     * @return array Modified query arguments
     */
    public function filter_post_list_query_args($query_args, $filled_atts) {
        // Check if latitude is set in GET parameters

        $latitude = $_GET['latitude'] ?? '';
        $longitude = $_GET['longitude'] ?? '';
        $radius = $_GET['radius'] ?? 500; // default radius is 500 meters

        if ( ! empty($latitude) && ! empty($longitude) && ! empty($radius) ) {
            // Modify query to return posts by latitude, longitude and radius
            $post_ids = $this->search_posts_by_latitude_longitude($latitude, $longitude, $radius, false);
            
            if ( ! empty($post_ids) ) {
                $query_args['post__in'] = $post_ids;
                $query_args['orderby'] = 'post__in';
                $query_args['posts_per_page'] = self::get_search_results_setting('posts_per_page');
    
            }
            else {
                // Modify query to return no posts 
                $query_args['post__in'] = array(0);
            }
        }
        
        return $query_args;
    }

    /**
     * Render search form shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_search_form($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => '',
            'button_text' => '',
            'address_label' => '',
            'radius_label' => ''
        ), $atts, 'show_search_form');
        
        $address_value = $_GET['address'] ?? '';
        $latitude_value = $_GET['latitude'] ?? '';
        $longitude_value = $_GET['longitude'] ?? '';
        $address_value = $_GET['address'] ?? '';

        // Get field labels from settings with fallbacks
        $address_label = $atts['address_label'] ?: self::get_search_form_setting('address_label');
        if (empty($address_label)) {
            $address_label = __('Address', SCP_TEXTDOMAIN);
        }
        
        $radius_label = $atts['radius_label'] ?: self::get_search_form_setting('radius_label');
        if (empty($radius_label)) {
            $radius_label = __('Search Radius', SCP_TEXTDOMAIN);
        }
        
        $submit_button_title = $atts['button_text'] ?: self::get_search_form_setting('submit_button_title');
        if (empty($submit_button_title)) {
            $submit_button_title = __('Search', SCP_TEXTDOMAIN);
        }

        $placeholder = $atts['placeholder'] ?: self::get_search_form_setting('placeholder');
        if (empty($placeholder)) {
            $placeholder = __('Enter an address...', SCP_TEXTDOMAIN);
        }
        
        $target_page_id = self::get_search_results_setting('target_page');

        $target_page = get_permalink($target_page_id);

        $selected_radius = $_GET['radius'] ?: 1000;

        $submit_button_icon = self::get_search_form_setting('submit_button_icon');
        $icon_html = '';
        if ( ! empty($submit_button_icon) ) {
            $icon_html = '<i class="material-icons">' . esc_attr($submit_button_icon) . '</i>';
        }
        ob_start();
        ?>
        <div class="scp-search-form-wrapper">
            <form id="scp-search-form" class="scp-search-form" target="<?php echo esc_attr($target_page); ?>">
                <div class="scp-form-group">
                    <label for="scp-address-input" class="scp-label"><?php echo esc_html($address_label); ?></label>
                    <input 
                        type="text" 
                        id="scp-address-input" 
                        name="address" 
                        class="scp-address-input" 
                        placeholder="<?php echo esc_attr($placeholder); ?>"
                        value="<?php echo esc_attr($address_value); ?>"
                        autocomplete="off"
                    />
                    <input type="hidden" id="scp-latitude" name="latitude" value="<?php echo esc_attr($latitude_value); ?>" />
                    <input type="hidden" id="scp-longitude" name="longitude" value="<?php echo esc_attr($longitude_value); ?>" />
                    <input type="hidden" id="scp-full-address" name="address" value="<?php echo esc_attr($address_value); ?>" />
                </div>
                <div class="scp-form-group">
                    <label for="scp-radius" class="scp-label"><?php echo esc_html($radius_label); ?></label>
                    <select id="scp-radius" name="radius" class="scp-radius-select">
                        <?php
                        $radius_options = self::get_search_form_setting('radius_options');
                        $radius_options = explode("\n", $radius_options);
                        foreach ($radius_options as $radius_option) :
                            $radius_option = explode(',', trim($radius_option));
                            $radius_value = trim($radius_option[0]);
                            $radius_text = trim($radius_option[1]);
                            echo '<option value="' . esc_attr($radius_value) . '" ' . selected($selected_radius, $radius_value) . '>' . esc_html($radius_text) . '</option>';
                        endforeach;
                        ?>
                    </select>
                </div>
                <div class="scp-form-group">
                    <button type="submit" class="scp-search-button w-btn">
                        <?php echo $icon_html; ?>
                        <?php echo esc_html($submit_button_title); ?>
                    </button>
                </div>
            </form>
            <div id="scp-form-message" class="scp-message" style="display: none;"></div>
            <div id="scp-map-container" class="scp-map-container" style="display: none;">
                <div id="scp-map" class="scp-map"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

     /**
     * Render short search form
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_search_short_form($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => '',
            'button_text' => '',
            'address_label' => '',
            'radius_label' => ''
        ), $atts, 'show_search_by_address_short_form');
        
        $address_value = $_GET['address'] ?? '';
        $latitude_value = $_GET['latitude'] ?? '';
        $longitude_value = $_GET['longitude'] ?? '';
        $address_value = $_GET['address'] ?? '';

        // Get field labels from settings with fallbacks
        $address_label = $atts['address_label'] ?: self::get_search_form_setting('address_label');
        if (empty($address_label)) {
            $address_label = __('Address', SCP_TEXTDOMAIN);
        }
        
        $radius_label = $atts['radius_label'] ?: self::get_search_form_setting('radius_label');
        if (empty($radius_label)) {
            $radius_label = __('Search Radius', SCP_TEXTDOMAIN);
        }
        
        $submit_button_title = $atts['button_text'] ?: self::get_search_form_setting('submit_button_title');
        if (empty($submit_button_title)) {
            $submit_button_title = __('Search', SCP_TEXTDOMAIN);
        }

        $placeholder = $atts['placeholder'] ?: self::get_search_form_setting('placeholder');
        if (empty($placeholder)) {
            $placeholder = __('Enter an address...', SCP_TEXTDOMAIN);
        }
        
        $target_page_id = self::get_search_results_setting('target_page');

        $target_page = get_permalink($target_page_id);

        $selected_radius = $_GET['radius'] ?: 1000;

        $submit_button_icon = self::get_search_form_setting('submit_button_icon');
        $icon_html = '';
        if ( ! empty($submit_button_icon) ) {
            $icon_html = '<i class="material-icons">' . esc_attr($submit_button_icon) . '</i>';
        }
        ob_start();
        ?>
        <div class="scp-search-form-wrapper scp-search-form-wrapper-short">
            <form id="scp-search-short-form" class="scp-search-form scp-search-form-short" target="<?php echo esc_attr($target_page); ?>">
                <input 
                    type="text" 
                    id="scp-address-input-short" 
                    name="address" 
                    class="scp-address-input" 
                    placeholder="<?php echo esc_attr($placeholder); ?>"
                    value="<?php echo esc_attr($address_value); ?>"
                    autocomplete="off"
                />
                <input type="hidden" id="scp-latitude-short" name="latitude" value="<?php echo esc_attr($latitude_value); ?>" />
                <input type="hidden" id="scp-longitude-short" name="longitude" value="<?php echo esc_attr($longitude_value); ?>" />
                <input type="hidden" id="scp-full-address-short" name="address" value="<?php echo esc_attr($address_value); ?>" />
                <select id="scp-radius-short" name="radius" class="scp-radius-select">
                    <?php
                    $radius_options = self::get_search_form_setting('radius_options');
                    $radius_options = explode("\n", $radius_options);
                    foreach ($radius_options as $radius_option) :
                        $radius_option = explode(',', trim($radius_option));
                        $radius_value = trim($radius_option[0]);
                        $radius_text = trim($radius_option[1]);
                        echo '<option value="' . esc_attr($radius_value) . '" ' . selected($selected_radius, $radius_value) . '>' . esc_html($radius_text) . '</option>';
                    endforeach;
                    ?>
                </select>
                <button type="submit" class="scp-search-button w-btn">
                    <?php echo $icon_html; ?>
                    <?php echo esc_html($submit_button_title); ?>
                </button>
            </form>
            <div id="scp-form-message-short" class="scp-message" style="display: none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }
   
     /**
     * Render search results shortcode
     * 
     * @param float $latitude Target latitude
     * @param float $longitude Target longitude
     * @param float $radius Search radius in meters
     * @return array IDs of posts within the radius
     */
    public function search_posts_by_latitude_longitude( $latitude, $longitude, $radius, $respect_pagination = true ) {
        global $wpdb;
        
        $meta_key_latitude = self::get_search_results_setting('meta_key_latitude');
        $meta_key_longitude = self::get_search_results_setting('meta_key_longitude');
        $post_type = self::get_search_results_setting('post_type');
        
        // Validate inputs
        if (empty($meta_key_latitude) || empty($meta_key_longitude) || empty($post_type)) {
            return array();
        }
        
        // Sanitize inputs
        $latitude = floatval($latitude);
        $longitude = floatval($longitude);
        $radius = floatval($radius);
        
        if ( $respect_pagination ) {
            // Get pagination parameters
            $paged = get_query_var('paged') ? intval(get_query_var('paged')) : 1;
            $posts_per_page = self::get_search_results_setting('posts_per_page');
            $posts_per_page = !empty($posts_per_page) ? intval($posts_per_page) : get_option('posts_per_page', 10);
            $offset = ($paged - 1) * $posts_per_page;
        }
        else {
            $offset = 0;
            $posts_per_page = 9999999; // No limit
        }
        
        // Get table names
        $posts_table = $wpdb->posts;
        $postmeta_table = $wpdb->postmeta;
        
        // Prepare the SQL query using ST_Distance_Sphere
        $query = $wpdb->prepare(
            "SELECT p.ID,
                (ST_Distance_Sphere(
                    point(CAST(pm_lng.meta_value AS DECIMAL(10,8)), CAST(pm_lat.meta_value AS DECIMAL(10,8))),
                    point(%f, %f)
                )) AS distance_meters
            FROM {$posts_table} p
            INNER JOIN {$postmeta_table} pm_lat ON p.ID = pm_lat.post_id AND pm_lat.meta_key = %s
            INNER JOIN {$postmeta_table} pm_lng ON p.ID = pm_lng.post_id AND pm_lng.meta_key = %s
            WHERE p.post_type = %s
                AND p.post_status = 'publish'
                AND pm_lat.meta_value BETWEEN -90 AND 90
                AND pm_lng.meta_value BETWEEN -180 AND 180
                AND ST_Distance_Sphere(
                    point(CAST(pm_lng.meta_value AS DECIMAL(10,8)), CAST(pm_lat.meta_value AS DECIMAL(10,8))),
                    point(%f, %f)
                ) <= %f
            ORDER BY distance_meters ASC
            LIMIT %d OFFSET %d",
            $longitude, // Target longitude
            $latitude,  // Target latitude
            $meta_key_latitude,
            $meta_key_longitude,
            $post_type,
            $longitude, // Target longitude (for WHERE clause)
            $latitude,  // Target latitude (for WHERE clause)
            $radius,    // Radius in meters
            $posts_per_page, // LIMIT
            $offset     // OFFSET
        );
        
        if ( isset( $_GET['debug_mode'] ) && $_GET['debug_mode'] == '1' ) {
            echo '<pre>';
            echo $query;
            echo '</pre>';
            exit();
        }
        $results = $wpdb->get_results($query, ARRAY_A);
        
        $post_ids = array();
        if ($results) {
            foreach ($results as $row) {
                $post_ids[] = intval($row['ID']);
            }
        }
        
        return $post_ids;
    }
    /**
     * Render search results on map shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_search_results_on_map($atts) {
        $atts = shortcode_atts(array(
            'height' => '600px'
        ), $atts, 'show_search_by_address_results');
        
        // Get search parameters from URL
        $search_latitude = isset($_GET['latitude']) ? floatval($_GET['latitude']) : null;
        $search_longitude = isset($_GET['longitude']) ? floatval($_GET['longitude']) : null;
        $radius = isset($_GET['radius']) ? floatval($_GET['radius']) : 1000;
        
        // Validate search parameters
        if (empty($search_latitude) || empty($search_longitude)) {
            return '<div class="scp-map-error">' . esc_html__('Sorry, the search results are not available (location is not provided).', SCP_TEXTDOMAIN) . '</div>';
        }
        
        $meta_key_latitude = self::get_search_results_setting('meta_key_latitude');
        $meta_key_longitude = self::get_search_results_setting('meta_key_longitude');
        
        // Get post IDs within radius
        $post_ids = $this->search_posts_by_latitude_longitude($search_latitude, $search_longitude, $radius);
        
        if (empty($post_ids)) {
            return '<div class="scp-map-no-results"></div>';
        }
        
        // Get post data with coordinates
        $posts_data = array();
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post) {
                continue;
            }
            
            $post_lat = get_post_meta($post_id, $meta_key_latitude, true);
            $post_lng = get_post_meta($post_id, $meta_key_longitude, true);
            
            if (empty($post_lat) || empty($post_lng)) {
                continue;
            }
            
            $posts_data[] = array(
                'id' => $post_id,
                'title' => get_the_title($post_id),
                'url' => get_permalink($post_id),
                'latitude' => floatval($post_lat),
                'longitude' => floatval($post_lng),
                'excerpt' => get_the_excerpt($post_id)
            );
        }
        
        // Generate unique map ID
        $map_id = 'scp-results-map-' . uniqid();
        
        // Get no results settings
        $missing_placeholder = self::get_search_results_setting('missing_placeholder');
        $missing_message = self::get_search_results_setting('missing_message');
        
        // Prepare map data for JavaScript
        $map_data = array(
            'mapId' => $map_id,
            'searchLocation' => array(
                'latitude' => $search_latitude,
                'longitude' => $search_longitude
            ),
            'radius' => $radius,
            'posts' => $posts_data,
            'missingPlaceholder' => $missing_placeholder,
            'missingMessage' => $missing_message
        );
        
        // Enqueue Google Maps if not already enqueued
        if (!wp_script_is('google-maps-autocomplete', 'enqueued')) {
            wp_enqueue_script(
                'google-maps-autocomplete',
                'https://maps.googleapis.com/maps/api/js?key=' . $this->get_google_api_key() . '&libraries=places',
                array(),
                null,
                true
            );
        }
        
        // Enqueue map results script if not already enqueued
        if (!wp_script_is('scp-map-results', 'enqueued')) {
            wp_enqueue_script(
                'scp-map-results',
                SCP_PLUGIN_URL . 'js/map-results.js',
                array('jquery', 'google-maps-autocomplete'),
                SCP_VERSION,
                true
            );
        }
        
        // Add inline script with map data
        wp_add_inline_script('scp-map-results', 'var scpMapData = ' . wp_json_encode($map_data) . ';', 'before');
        
        ob_start();
        ?>
        <div class="scp-results-map-wrapper">
            <div id="<?php echo esc_attr($map_id); ?>" class="scp-results-map" style="height: <?php echo esc_attr($atts['height']); ?>; width: 100%;"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize the plugin
new Search_Posts_By_Address();

// Initialize settings
new SCP_Settings();

