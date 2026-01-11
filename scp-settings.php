<?php
/**
 * Settings page handler for Search Posts By Address plugin
 * 
 * @package Search_Posts_By_Address
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings class
 */
class SCP_Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_options_page(
            'Search by Address',           // Page title
            'Search by Address',           // Menu title
            'manage_options',              // Capability
            'search-posts-by-address',     // Menu slug
            array($this, 'render_admin_page') // Callback function
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {

        //---------------------Register fields for the search form---------------------

        // Google Maps API Key
        register_setting(
            'scp_settings_group',
            'scp_google_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );

        // Search Form Title
        register_setting(
            'scp_settings_group',
            'scp_search_form_title',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );

        // Submit Button Title
        register_setting(
            'scp_settings_group',
            'scp_search_form_submit_button_title',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );

        // Address Field Label
        register_setting(
            'scp_settings_group',
            'scp_search_form_address_label',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );

        // Radius Field Label
        register_setting(
            'scp_settings_group',
            'scp_search_form_radius_label',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );

        // Search Placeholder
        register_setting(
            'scp_settings_group',
            'scp_search_form_placeholder',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );

        //---------------------Register fields for the search results---------------------

        // Posts Per Page
        register_setting(
            'scp_settings_group',
            'scp_search_results_posts_per_page',
            array(
                'type' => 'number',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 10
            )
        );

        // Page to Redirect to
        register_setting(
            'scp_settings_group',
            'scp_search_results_target_page',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );

        //---------------------Register fields for the search results---------------------
        $this->add_fields_to_admin_page();


    }

    public function add_fields_to_admin_page() {
        $this->add_search_form_fields();
        $this->add_search_results_fields();
    }

    public function add_search_form_fields() {
        add_settings_section(
            'scp_search_form_section',
            'Search Form Settings',
            array($this, 'render_search_form_section'),
            'search-posts-by-address'
        );
        
        add_settings_field(
            'scp_search_form_title',
            'Search Form Title',
            array($this, 'render_search_form_title_field'),
            'search-posts-by-address',
            'scp_search_form_section'
        );

        add_settings_field(
            'scp_search_form_submit_button_title',
            'Submit Button Title',
            array($this, 'render_search_form_submit_button_title_field'),
            'search-posts-by-address',
            'scp_search_form_section'
        );

        add_settings_field(
            'scp_search_form_placeholder',
            'Search Placeholder',
            array($this, 'render_search_form_placeholder_field'),
            'search-posts-by-address',
            'scp_search_form_section'
        );

        add_settings_field(
            'scp_search_form_address_label',
            'Address Field Label',
            array($this, 'render_search_form_address_label_field'),
            'search-posts-by-address',
            'scp_search_form_section'
        );

        add_settings_field(
            'scp_search_form_radius_label',
            'Radius Field Label',
            array($this, 'render_search_form_radius_label_field'),
            'search-posts-by-address',
            'scp_search_form_section'
        );

        add_settings_section(
            'scp_api_section',
            'Google Maps API Settings',
            array($this, 'render_api_section'),
            'search-posts-by-address'
        );
        
        add_settings_field(
            'scp_google_api_key',
            'Google Maps API Key',
            array($this, 'render_api_key_field'),
            'search-posts-by-address',
            'scp_api_section'
        );

    }

    /**
     * Add Search Results fields to the admin page
     */
    public function add_search_results_fields() {
        add_settings_section(
            'scp_search_results_section',
            'Search Results',
            array($this, 'render_search_results_section'),
            'search-posts-by-address'
        );
        
        add_settings_field(
            'scp_search_results_posts_per_page',
            'Posts Per Page',
            array($this, 'render_search_results_posts_per_page_field'),
            'search-posts-by-address',
            'scp_search_results_section'
        );

        add_settings_field(
            'scp_search_results_target_page',
            'Page to Redirect to',
            array($this, 'render_search_results_target_page_field'),
            'search-posts-by-address',
            'scp_search_results_section'
        );
    }
   
    /**
     * Render Search Form section description
     */
    public function render_search_form_section() {
        echo '<p>Configure the search form settings.</p>';
    }
    
    /**
     * Render Search Form title field
     */
    public function render_search_form_title_field() {
        $title = Search_Posts_By_Address::get_search_form_setting('title');
        ?>
        <input 
            type="text" 
            id="scp_search_form_title"
            name="scp_search_form_title"
            value="<?php echo esc_attr($title); ?>"
            class="regular-text"
            placeholder="Enter the search form title"
        />
        <?php
    }

    /**
     * Render Submit Button Title field
     */
    public function render_search_form_submit_button_title_field() {
        $submit_button_title = Search_Posts_By_Address::get_search_form_setting('submit_button_title');
        ?>
        <input 
            type="text" 
            id="scp_search_form_submit_button_title"
            name="scp_search_form_submit_button_title"
            value="<?php echo esc_attr($submit_button_title); ?>"
            class="regular-text"
            placeholder="Enter the submit button title"
        />
        <?php
    }

    /**
     * Render Address Field Label field
     */
    public function render_search_form_address_label_field() {
        $address_label = Search_Posts_By_Address::get_search_form_setting('address_label');
        ?>
        <input 
            type="text" 
            id="scp_search_form_address_label"
            name="scp_search_form_address_label"
            value="<?php echo esc_attr($address_label); ?>"
            class="regular-text"
            placeholder="Enter the address field label"
        />
        <?php
    }

    /**
     * Render Radius Field Label field
     */
    public function render_search_form_radius_label_field() {
        $radius_label = Search_Posts_By_Address::get_search_form_setting('radius_label');
        ?>
        <input 
            type="text" 
            id="scp_search_form_radius_label"
            name="scp_search_form_radius_label"
            value="<?php echo esc_attr($radius_label); ?>"
            class="regular-text"
            placeholder="Enter the radius field label"
        />
        <?php
    }

    /**
     * Render Search Placeholder field
     */
    public function render_search_form_placeholder_field() {
        $placeholder = Search_Posts_By_Address::get_search_form_setting('placeholder');
        ?>
        <input 
            type="text" 
            id="scp_search_form_placeholder"
            name="scp_search_form_placeholder"
            value="<?php echo esc_attr($placeholder); ?>"
            class="regular-text"
            placeholder="Enter the search placeholder"
        />
        <?php
    }

    /**
     * Render Search Results section description
     */
    public function render_search_results_section() {
        echo '<p>Configure the search results settings.</p>';
    }
    
    /**
     * Render Search Results posts per page field
     */
    public function render_search_results_posts_per_page_field() {
        $posts_per_page = get_option('scp_search_results_posts_per_page', 10);
        ?>
        <input 
            type="number" 
            id="scp_search_results_posts_per_page"
            name="scp_search_results_posts_per_page"
            value="<?php echo esc_attr($posts_per_page); ?>"
            class="regular-text"
            placeholder="Enter the number of posts to display per page"
        />
        <?php
    }
    
    /**
     * Render Search Results target page field
     */
    public function render_search_results_target_page_field() {
        $target_page = get_option('scp_search_results_target_page', '');
        $pages = get_pages(array(
            'sort_column' => 'post_title',
            'sort_order' => 'ASC'
        ));
        ?>
        <select 
            id="scp_search_results_target_page"
            name="scp_search_results_target_page"
            class="regular-text"
        >
            <option value=""><?php echo esc_html__('-- Select a page --', 'search-posts-by-address'); ?></option>
            <?php foreach ($pages as $page) : ?>
                <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($target_page, $page->ID); ?>>
                    <?php echo esc_html($page->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php echo esc_html__('Select the page where search results will be displayed.', 'search-posts-by-address'); ?>
        </p>
        <?php
    }
    
    /**
     * Render API section description
     */
    public function render_api_section() {
        echo '<p>Enter your Google Maps API key to enable the address autocomplete functionality.</p>';
    }
    
    /**
     * Render API key input field
     */
    public function render_api_key_field() {
        $api_key = get_option('scp_google_api_key', '');
        ?>
        <input 
            type="text" 
            id="scp_google_api_key" 
            name="scp_google_api_key" 
            value="<?php echo esc_attr($api_key); ?>" 
            class="regular-text"
            placeholder="Enter your Google Maps API key"
        />
        <p class="description">
            Get your API key from the <a href="https://console.cloud.google.com/google/maps-apis/credentials" target="_blank">Google Cloud Console</a>. 
            Make sure to enable the Maps JavaScript API and Places API.
        </p>
        <?php
    }
    
    /**
     * Render admin settings page
     */
    public function render_admin_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        settings_errors('scp_messages');
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('scp_settings_group');
                do_settings_sections('search-posts-by-address');
                submit_button('Save Settings');
                ?>
            </form>
            <hr>
            <div class="scp-admin-content">
                <h2>Usage</h2>
                <p>Use the following shortcodes to display the search form and results:</p>
                <ul>
                    <li><code>[show_search_form]</code> - Displays the search form with address autocomplete</li>
                    <li><code>[show_search_results]</code> - Displays the search results</li>
                </ul>
            </div>
        </div>
        <?php
    }
}

