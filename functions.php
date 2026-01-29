<?php
function zt_enqueue_assets()
{
    $manifest = json_decode(file_get_contents(get_theme_file_path('/assets/dist/.vite/manifest.json')), true);
    $main = $manifest['assets/src/main.js'];

    wp_enqueue_script('theme-js', get_theme_file_uri('/assets/dist/' . $main['file']), [], null, true);

    if (!empty($main['css'])) {
        foreach ($main['css'] as $css) {
            wp_enqueue_style('theme-css', get_theme_file_uri('/assets/dist/' . $css));
        }
    }

    $mapbox_key = defined('MAPBOX_PUBLIC_KEY') ? MAPBOX_PUBLIC_KEY : get_option('mapbox_public_key');

    // ✅ Global data for JS
    wp_localize_script('theme-js', 'propertyTheme', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wp_rest'),
        'home_url' => home_url(),
        'rest_url' => esc_url_raw(rest_url()),
        'mapbox_key' => esc_attr($mapbox_key),
    ]);

    wp_enqueue_style(
        'property-listing-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap',
        [],
        null
    );
}
add_action('wp_enqueue_scripts', 'zt_enqueue_assets');



/**
 * 🚀 Performance Optimization: Remove Unnecessary WordPress Header Code 🧹
 */
function optimize_wp_head_cleanup()
{
    // 1. Remove max-image-preview robots meta tag
    remove_filter('wp_robots', 'wp_robots_max_image_preview');

    // 2. Remove the 'contain-intrinsic-size' inline style for images
    remove_action('wp_head', 'wp_render_elements_assets', 20);

    // 3. Remove all WordPress Emoji scripts and styles
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    add_filter('tiny_mce_plugins', 'disable_emojis_tinymce');
    add_filter('wp_resource_hints', 'disable_emojis_dns_prefetch', 10, 2);
}
add_action('init', 'optimize_wp_head_cleanup');

function disable_emojis_tinymce($plugins)
{
    if (is_array($plugins)) {
        return array_diff($plugins, array('wpemoji'));
    }
    return $plugins;
}

function disable_emojis_dns_prefetch($urls, $relation_type)
{
    if ('dns-prefetch' === $relation_type) {
        $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/16.0.1/svg/');
        $urls = array_diff($urls, array($emoji_svg_url));
    }
    return $urls;
}


/**
 * 🧱 Remove the core WordPress Block Library CSS
 */
function remove_wp_block_library_css()
{
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
}
add_action('wp_enqueue_scripts', 'remove_wp_block_library_css', 100);

/**
 * Disable the 'global-styles-inline-css' block (Theme.json/FSE styles)
 */
function remove_global_styles()
{
    remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
    remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
}
add_action('init', 'remove_global_styles');

/**
 * Property Listing Theme - Main Functions File
 * Initializes all custom post types, taxonomies, and hooks
 */

// Register Custom Post Type: Property
function property_theme_register_cpt()
{
    register_post_type('property', array(
        'label' => 'Properties',
        'public' => true,
        'show_in_rest' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'has_archive' => true,
        'rewrite' => array('slug' => 'properties'),
        'menu_icon' => 'dashicons-building',
        'rest_base' => 'properties',
    ));

    // Register Property Type Taxonomy
    register_taxonomy('property_type', 'property', array(
        'label' => 'Property Type',
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'property-type'),
    ));

    // Register Status Taxonomy
    register_taxonomy('property_status', 'property', array(
        'label' => 'Status',
        'hierarchical' => false,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'property-status'),
    ));

    // Register Location Taxonomy
    register_taxonomy('property_location', 'property', array(
        'label' => 'Location',
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'property-location'),
    ));
}
add_action('init', 'property_theme_register_cpt');

// Register Custom Post Type: Subscription Plan
function property_theme_register_subscription_cpt()
{
    register_post_type('subscription_plan', array(
        'label' => 'Subscription Plans',
        'public' => false,
        'show_ui' => true,
        'show_in_rest' => true,
        'supports' => array('title'),
        'menu_icon' => 'dashicons-tickets',
    ));
}
add_action('init', 'property_theme_register_subscription_cpt');

// Register Custom Tables
function property_theme_create_tables()
{
    global $wpdb;

    $user_subscriptions = $wpdb->prefix . 'user_subscriptions';
    $property_analytics = $wpdb->prefix . 'property_analytics';
    $property_leads = $wpdb->prefix . 'property_leads';

    $sql = array();

    // User Subscriptions Table
    $sql[] = "CREATE TABLE IF NOT EXISTS $user_subscriptions (
        id bigint(20) AUTO_INCREMENT PRIMARY KEY,
        user_id bigint(20) NOT NULL,
        plan_id bigint(20) NOT NULL,
        stripe_subscription_id varchar(255),
        status varchar(50) DEFAULT 'active',
        started_at datetime DEFAULT CURRENT_TIMESTAMP,
        expires_at datetime,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY user_id (user_id),
        KEY plan_id (plan_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // Property Analytics Table
    $sql[] = "CREATE TABLE IF NOT EXISTS $property_analytics (
        id bigint(20) AUTO_INCREMENT PRIMARY KEY,
        property_id bigint(20) NOT NULL,
        user_id bigint(20),
        event_type varchar(50) NOT NULL,
        event_data longtext,
        ip_address varchar(45),
        user_agent text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        KEY property_id (property_id),
        KEY user_id (user_id),
        KEY event_type (event_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // Property Leads Table
    $sql[] = "CREATE TABLE IF NOT EXISTS $property_leads (
        id bigint(20) AUTO_INCREMENT PRIMARY KEY,
        property_id bigint(20) NOT NULL,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        phone varchar(20),
        message longtext,
        status varchar(50) DEFAULT 'new',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        KEY property_id (property_id),
        KEY status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    foreach ($sql as $query) {
        dbDelta($query);
    }
}
register_activation_hook(__FILE__, 'property_theme_create_tables');

require_once get_template_directory() . '/inc/custom-fields.php';
// require_once get_template_directory() . '/inc/subscription/subscriptions.php';
require_once get_template_directory() . '/inc/api-endpoints.php';
// require_once get_template_directory() . '/inc/subscription/stripe-handler.php';
require_once get_template_directory() . '/inc/subscription/subscription-fields.php';
require_once get_template_directory() . '/inc/theme-options.php';
require_once get_template_directory() . '/inc/helpers.php';
require_once get_template_directory() . '/inc/api-properties-search.php';

require_once get_template_directory() . '/inc/subscription/stripe-subscriptions-checkout.php';
require_once get_template_directory() . '/inc/subscription/stripe-subscriptions-native.php';
require_once get_template_directory() . '/inc/subscription/stripe-webhooks.php';
require_once get_template_directory() . '/inc/subscription/stripe-subscriptions-native.php';
require_once get_template_directory() . '/inc/subscription/stripe-migration.php';
require_once get_template_directory() . '/inc/subscription/stripe-products-setup.php';

require_once get_template_directory() . '/admin/migration-page.php';

add_action('init', function () {

    register_meta('user', '_card_last_four', [
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest' => true,
    ]);

    register_meta('user', '_card_brand', [
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest' => true,
    ]);

    register_meta('user', '_exp_month', [
        'type' => 'integer',
        'single' => true,
        'sanitize_callback' => 'absint',
        'show_in_rest' => true,
    ]);

    register_meta('user', '_exp_year', [
        'type' => 'integer',
        'single' => true,
        'sanitize_callback' => 'absint',
        'show_in_rest' => true,
    ]);

    register_meta('user', '_billing_name', [
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest' => true,
    ]);

    register_meta('user', '_billing_email', [
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'sanitize_email',
        'show_in_rest' => true,
    ]);

});

function get_jamaica_cities() {
    global $cities;
    
    $url = 'https://raw.githubusercontent.com/lmfmaier/cities-json/master/cities500.json';
    
    $response = wp_remote_get($url);
    
    if (is_wp_error($response)) {
        $cities = array();
        return $cities;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    $cities = array();
    
    if (is_array($data)) {
        foreach ($data as $city) {
            if (isset($city['country']) && $city['country'] === 'JM' && isset($city['name']) && isset($city['lat']) && isset($city['lon'])) {
                $name = $city['name'];
                $cities[$name] = array('lat' => (float)$city['lat'], 'lng' => (float)$city['lon']);
            }
        }
    }
    
    return $cities;
}