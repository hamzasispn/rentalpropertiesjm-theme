<?php
// Add SVG arrow to menu items with children
function mytheme_add_submenu_svg($title, $item, $args, $depth)
{
    if (in_array('menu-item-has-children', $item->classes)) {
        $svg = '<svg width="9" height="6" viewBox="0 0 9 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.24267 3.3L7.54267 0L8.48533 0.943333L4.24267 5.186L0 0.943333L0.942667 0.000666936L4.24267 3.3Z" fill="white"/>
                </svg>';
        $title .= $svg;
    }
    return $title;
}
add_filter('nav_menu_item_title', 'mytheme_add_submenu_svg', 10, 4);

// Allow SVG upload
function allow_svg_uploads($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'allow_svg_uploads');

function mytheme_register_menus() {
    register_nav_menus([
        'main_menu'   => 'Main Menu',
    ]);
}
add_action('after_setup_theme', 'mytheme_register_menus');

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_media();
});

add_action('after_setup_theme', function() {
    if (!current_user_can('administrator')) {
        show_admin_bar(false);
    }
});

/**
 * Add WebP and WebM support to WordPress media library
 */
add_filter('upload_mimes', 'property_theme_allow_media_types');
function property_theme_allow_media_types($mimes) {
    $mimes['webp'] = 'image/webp';
    $mimes['webm'] = 'video/webm';
    return $mimes;
}

add_filter('wp_check_filetype_and_ext', 'property_theme_check_filetype', 10, 5);
function property_theme_check_filetype($data, $file, $filename, $mimes, $real_mime = null) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    
    if ($ext === 'webp') {
        $data['ext'] = 'webp';
        $data['type'] = 'image/webp';
    }
    
    if ($ext === 'webm') {
        $data['ext'] = 'webm';
        $data['type'] = 'video/webm';
    }
    
    return $data;
}

add_filter( 'cron_schedules', 'property_theme_add_every_minute_interval' );
function property_theme_add_every_minute_interval( $schedules ) {
    $schedules['every_minute'] = array(
        'interval' => 60, // Interval in seconds (60 seconds = 1 minute)
        'display'  => __( 'Every Minute' )
    );
    return $schedules;
}
