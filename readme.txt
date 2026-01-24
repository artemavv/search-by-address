=== Search Posts By Address ===
Contributors: yourname
Tags: search, custom posts, google maps, autocomplete, radius search
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 0.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin that provides address-based search functionality with Google Maps Autocomplete and radius filtering for custom posts.

== Description ==

Search Posts By Address is a WordPress plugin that allows users to search for posts (or custom post types) within a specific radius of a given address. It leverages the Google Maps API for address autocomplete and spatial calculations.

Key Features:
*   **Search Form**: Customizable search form with address autocomplete and radius selection.
*   **Short Form**: Compact inline version of the search form.
*   **Map Results**: Display search results on an interactive Google Map.
*   **Radius Filtering**: Filters posts based on distance from the selected location.
*   **Customizable**: Extensive settings for labels, placeholders, radius options, and result display.

Available Shortcodes:
*   `[show_search_by_address_form]` - Displays the standard search form.
*   `[show_search_by_address_short_form]` - Displays a compact, inline search form.
*   `[render_search_results_on_map]` - Displays search results on a Google Map.

== Installation ==

1.  Upload the `search-posts-by-address` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to **Settings > Search by Address** to configure the plugin.
4.  Enter your Google Maps API key (with Maps JavaScript API and Places API enabled).
5.  Configure post types and meta keys for latitude/longitude storage.
6.  Use the shortcodes in your posts or pages.

== Configuration ==

**Google Maps API Key:**
To use this plugin, you need a Google Maps API key with the **Maps JavaScript API** and **Places API** enabled.
Enter the key in the plugin settings page.

**Post Setup:**
Your posts must have latitude and longitude stored in custom fields (meta keys). Configure the specific meta key names in the plugin settings (e.g., `latitude` and `longitude`).

**Settings Page:**
Navigate to **Settings > Search by Address** to configure:
*   **Search Form:** Titles, button text/icons, labels, placeholders, radius options, and target country for autocomplete.
*   **Search Results:** Target post type, posts per page, target page for results, and coordinate meta keys.

== Usage ==

**Standard Search Form:**
`[show_search_by_address_form]`

Attributes:
*   `placeholder` - Placeholder text for the address input.
*   `button_text` - Text for the search button.
*   `address_label` - Label for the address field.
*   `radius_label` - Label for the radius dropdown.

Example:
`[show_search_by_address_form placeholder="Enter location" button_text="Find Locations" radius_label="Distance"]`

**Compact Search Form:**
`[show_search_by_address_short_form]`

Attributes:
*   `placeholder` - Placeholder text.
*   `button_text` - Text for the search button.

**Map Results:**
`[render_search_results_on_map]`

Attributes:
*   `height` - Height of the map container (default: "600px").

Example:
`[render_search_results_on_map height="500px"]`

== Changelog ==

= 0.7 =
*   Added extra setting to set text message when no location is provided


= 0.6 =
*  Made plugin translatable; Added Polish Translation