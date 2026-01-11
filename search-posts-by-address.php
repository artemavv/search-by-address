<?php
/**
 * Plugin Name: Search Posts By Address
 * Plugin URI: https://example.com/search-posts-by-address
 * Description: A plugin that provides search functionality with Google Maps Autocomplete for custom posts.
 * Version: 0.2
 * Author: Artem Avvakumov
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: search-posts-by-address
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SCP_VERSION', '0.2');
define('SCP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include settings file
require_once SCP_PLUGIN_DIR . 'scp-settings.php';

/**
 * Main plugin class
 */
class Search_Posts_By_Address {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Register shortcodes
        add_shortcode('show_search_form', array($this, 'render_search_form'));
        add_shortcode('show_search_by_address_results', array($this, 'render_search_results'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue on pages that use the shortcodes
        global $post;
        if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'show_search_form') || has_shortcode($post->post_content, 'show_search_results'))) {
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
            
            // Enqueue styles
            wp_enqueue_style(
                'scp-style',
                SCP_PLUGIN_URL . 'css/style.css',
                array(),
                SCP_VERSION
            );
            
            // Localize script to pass data to JavaScript
            wp_localize_script('scp-autocomplete', 'scpData', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('scp_nonce')
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
        
        // Get field labels from settings with fallbacks
        $address_label = $atts['address_label'] ?: self::get_search_form_setting('address_label');
        if (empty($address_label)) {
            $address_label = 'Address';
        }
        
        $radius_label = $atts['radius_label'] ?: self::get_search_form_setting('radius_label');
        if (empty($radius_label)) {
            $radius_label = 'Search Radius';
        }
        
        $submit_button_title = $atts['button_text'] ?: self::get_search_form_setting('submit_button_title');
        if (empty($submit_button_title)) {
            $submit_button_title = 'Search';
        }

        $placeholder = $atts['placeholder'] ?: self::get_search_form_setting('placeholder');
        if (empty($placeholder)) {
            $placeholder = 'Enter an address...';
        }
        
        $target_page_id = self::get_search_results_setting('target_page');

        $target_page = get_permalink($target_page_id);
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
                        autocomplete="off"
                    />
                    <input type="hidden" id="scp-latitude" name="latitude" />
                    <input type="hidden" id="scp-longitude" name="longitude" />
                    <input type="hidden" id="scp-full-address" name="full_address" />
                </div>
                <div class="scp-form-group">
                    <label for="scp-radius" class="scp-label"><?php echo esc_html($radius_label); ?></label>
                    <select id="scp-radius" name="radius" class="scp-radius-select">
                        <option value="100">100 meters</option>
                        <option value="250">250 meters</option>
                        <option value="500" selected>500 meters</option>
                        <option value="1000">1 kilometer</option>
                        <option value="2000">2 kilometers</option>
                        <option value="3000">3 kilometers</option>
                        <option value="4000">4 kilometers</option>
                        <option value="5000">5 kilometers</option>
                    </select>
                </div>
                <div class="scp-form-group">
                    <button type="submit" class="scp-search-button"><?php echo esc_html($submit_button_title); ?></button>
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
     * Render search results shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_search_results($atts) {
        $atts = shortcode_atts(array(
            'posts_per_page' => 10
        ), $atts, 'show_search_results');
        
        $latitude = $_GET['latitude'];
        $longitude = $_GET['longitude'];
        $radius = $_GET['radius'];
        $meta_key_latitude = self::get_search_results_setting('meta_key_latitude');
        $meta_key_longitude = self::get_search_results_setting('meta_key_longitude');
        
        $args = array(
            'post_type' => self::get_search_results_setting('post_type'),
            'posts_per_page' => self::get_search_results_setting('posts_per_page'),
            'meta_query' => array(
                array(
                    'key' => $meta_key_latitude,
                    'value' => $latitude,
                    'compare' => '!='
                ),
                array(
                    'key' => $meta_key_longitude,
                    'value' => $longitude,
                    'compare' => '!='
                )
            )
        );

        
        //exit();
        
        ob_start();

        $query = new WP_Query($args);
        $posts = $query->posts;
/*
        // debug print SQL query and args
        echo '<pre>';
        print_r($query->request);
        echo '</pre>';
        exit();
        */
        ?>
        <div id="scp-search-results" class="scp-search-results">
            <!-- Search results will be displayed here -->
            <?php foreach ($posts as $post) : ?>
                <div class="scp-search-result">
                    <h2><?php echo esc_html($post->post_title); ?></h2>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize the plugin
new Search_Posts_By_Address();

// Initialize settings
new SCP_Settings();

