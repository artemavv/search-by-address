=== Search Custom Posts ===
Contributors: yourname
Tags: search, custom posts, google maps, autocomplete
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin that provides search functionality with Google Maps Autocomplete for custom posts.

== Description ==

Search Custom Posts is a WordPress plugin that provides two shortcodes:
* `[show_search_form]` - Displays an address search form with Google Maps Autocomplete integration
* `[show_search_results]` - Displays search results (placeholder for future implementation)

== Installation ==

1. Upload the `search-custom-posts` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your Google Maps API key (see Configuration section)
4. Use the shortcodes in your posts or pages

== Configuration ==

To use Google Maps Autocomplete, you need a Google Maps API key with the Places API enabled.

You can set the API key in one of two ways:

1. In wp-config.php:
   `define('SCP_GOOGLE_API_KEY', 'your-api-key-here');`

2. Using a filter in your theme's functions.php:
   `add_filter('scp_google_api_key', function() { return 'your-api-key-here'; });`

== Usage ==

**Search Form Shortcode:**
`[show_search_form]`

Optional attributes:
* `placeholder` - Placeholder text for the address input (default: "Enter an address...")
* `button_text` - Text for the search button (default: "Search")

Example:
`[show_search_form placeholder="Type your address" button_text="Find"]`

**Search Results Shortcode:**
`[show_search_results]`

This shortcode is currently a placeholder and will be implemented in future versions.

== Changelog ==

= 1.0.0 =
* Initial release
* Added show_search_form shortcode with Google Maps Autocomplete
* Added show_search_results shortcode (placeholder)

