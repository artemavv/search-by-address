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

        // Submit Button Icon
        register_setting(
            'scp_settings_group',
            'scp_search_form_submit_button_icon',
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

        // Radius Field Options
        register_setting(
            'scp_settings_group',
            'scp_search_form_radius_options',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
                'default' => '500, 500 meters'
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

        // Target Country
        register_setting(
            'scp_settings_group',
            'scp_search_form_target_country',
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


        // Meta Key for Latitude    
        register_setting(
            'scp_settings_group',
            'scp_search_results_meta_key_latitude',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );


        // Meta Key for Longitude    
        register_setting(
            'scp_settings_group',
            'scp_search_results_meta_key_longitude',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
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

        // Post Type
        register_setting(
            'scp_settings_group',
            'scp_search_results_post_type',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'post'
            )
        );

        // No results placeholder marker
        register_setting(
            'scp_settings_group',
            'scp_search_results_missing_placeholder',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );

        // No results message
        register_setting(
            'scp_settings_group',
            'scp_search_results_missing_message',
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
            'scp_search_form_submit_button_icon',
            'Submit Button Icon',
            array($this, 'render_search_form_submit_button_icon_field'),
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

        add_settings_field(
            'scp_search_form_radius_options',
            'Radius Field Options',
            array($this, 'render_search_form_radius_options_field'),
            'search-posts-by-address',
            'scp_search_form_section'
        );

        add_settings_field(
            'scp_search_form_target_country',
            'Target Country',
            array($this, 'render_search_form_target_country_field'),
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
            'scp_search_results_post_type',
            'Post type to search in',
            array($this, 'render_search_results_post_type_field'),
            'search-posts-by-address',
            'scp_search_results_section'
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


        add_settings_field(
            'scp_search_results_meta_key_latitude',
            'Meta Key for Latitude',
            array($this, 'render_search_results_meta_key_latitude_field'),
            'search-posts-by-address',
            'scp_search_results_section'
        );

        add_settings_field(
            'scp_search_results_meta_key_longitude',
            'Meta Key for Longitude',
            array($this, 'render_search_results_meta_key_longitude_field'),
            'search-posts-by-address',
            'scp_search_results_section'
        );

        add_settings_field(
            'scp_search_results_missing_placeholder',
            'No results placeholder marker',
            array($this, 'render_search_results_missing_placeholder_field'),
            'search-posts-by-address',
            'scp_search_results_section'
        );

        add_settings_field(
            'scp_search_results_missing_message',
            'No results message',
            array($this, 'render_search_results_missing_message_field'),
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

    public function render_search_form_submit_button_icon_field() {
        $submit_button_icon = Search_Posts_By_Address::get_search_form_setting('submit_button_icon');
        ?>
        <input 
            type="text" 
            id="scp_search_form_submit_button_icon"
            name="scp_search_form_submit_button_icon"
            value="<?php echo esc_attr($submit_button_icon); ?>"
            class="regular-text"
            placeholder="Enter the submit button icon"
        />
        <p class="description">
            Enter the icon code for the submit button.
            E.g. search, location_on, location_off, etc.
        </p>
        <p class="description">
            You can find the icon code in the <a href="https://fonts.google.com/icons" target="_blank">Material Icons</a> list.
        </p>
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

    public function render_search_form_radius_options_field() {
        $radius_options = Search_Posts_By_Address::get_search_form_setting('radius_options');
        ?>
        <textarea 
            id="scp_search_form_radius_options"
            name="scp_search_form_radius_options"
            class="regular-text"
            rows="6"
            placeholder="Enter the radius options - one per line."
        ><?php echo esc_textarea($radius_options); ?></textarea>
        <p class="description">
            On  each line enter the radius option and the corresponding label separated by a comma.
            E.g. 500, 500 meters
  
        </p>
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
     * Render Target Country field
     */
    public function render_search_form_target_country_field() {
        $target_country = Search_Posts_By_Address::get_search_form_setting('target_country');
        $country_codes = scp_get_country_codes();
        ?>
        <select 
            id="scp_search_form_target_country"
            name="scp_search_form_target_country"
            class="regular-text"
        >
            <?php foreach ($country_codes as $code => $name) : ?>
                <option value="<?php echo esc_attr($code); ?>" <?php selected($target_country, $code); ?>>
                    <?php echo esc_html($name ? $name : $code); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            Select the target country to restrict address autocomplete results to a specific country.
        </p>
        <?php
    }

    /**
     * Render Search Results section description
     */
    public function render_search_results_section() {
        echo '<p>Configure the search results settings.</p>';
    }
    
    public function render_search_results_post_type_field() {
        $selected_post_type = Search_Posts_By_Address::get_search_results_setting('post_type');

        $post_types = get_post_types(array(
            'public' => true,
        ), 'objects');
        ?>
        <select 
            id="scp_search_results_post_type"
            name="scp_search_results_post_type"
            class="regular-text"
        >
            <option value=""><?php echo esc_html__('-- Select a post type --', 'search-posts-by-address'); ?></option>
            <?php foreach ($post_types as $post_type) : ?>
                <option value="<?php echo esc_attr($post_type->name); ?>" <?php selected($selected_post_type, $post_type->name); ?>>
                    <?php echo esc_html($post_type->label); ?>
                </option>
            <?php endforeach; ?>
        </select>   
        <?php
    }

    public function render_search_results_meta_key_latitude_field() {
        $meta_key_latitude = Search_Posts_By_Address::get_search_results_setting('meta_key_latitude');
        ?>
        <input 
            type="text" 
            id="scp_search_results_meta_key_latitude"
            name="scp_search_results_meta_key_latitude"
            value="<?php echo esc_attr($meta_key_latitude); ?>"
            class="regular-text"
            placeholder="Enter the meta key for latitude"
        />
        <?php
    }

    public function render_search_results_meta_key_longitude_field() {
        $meta_key_longitude = Search_Posts_By_Address::get_search_results_setting('meta_key_longitude');
        ?>
        <input 
            type="text" 
            id="scp_search_results_meta_key_longitude"
            name="scp_search_results_meta_key_longitude"
            value="<?php echo esc_attr($meta_key_longitude); ?>"
            class="regular-text"
            placeholder="Enter the meta key for longitude"
        />
        <?php
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
     * Render No results placeholder marker field
     */
    public function render_search_results_missing_placeholder_field() {
        $placeholder = get_option('scp_search_results_missing_placeholder', '');
        ?>
        <input 
            type="text" 
            id="scp_search_results_missing_placeholder"
            name="scp_search_results_missing_placeholder"
            value="<?php echo esc_attr($placeholder); ?>"
            class="regular-text"
            placeholder="Enter the no results placeholder marker"
        />
        <p class="description">
            You can use a placeholder marker to display a message when no results are found. A text entered here will be replaced with the correct message when no results are found.
            <br>You can place this marker anywhere in the page. 
            <br>In 'Post List' settings you can place the marker under 'Action when no results found' section ('Order & Quantity' tab)
        </p>
        <?php
    }

    /**
     * Render No results message field
     */
    public function render_search_results_missing_message_field() {
        $message = get_option('scp_search_results_missing_message', '');
        ?>
        <input 
            type="text" 
            id="scp_search_results_missing_message"
            name="scp_search_results_missing_message"
            value="<?php echo esc_attr($message); ?>"
            class="regular-text"
            placeholder="Enter the no results message"
        />
        <p class="description">
            A text entered here will be used as a message when no results are found - it will replace 'placeholder marker' defined above.
            <br>You can use {address} in the message text - it will be replaced with the actual address searched for.
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

