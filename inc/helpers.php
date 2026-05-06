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
        'interval' => 60, 
        'display'  => __( 'Every Minute' )
    );
    return $schedules;
}

add_theme_support( 'post-thumbnails' );

function property_author_has_paid_plan(int $user_id): bool {
    if ($user_id <= 0) return false;

    // Use the canonical helper if available — it reads the active subscription
    // row using the same column name (`package_id`) used everywhere else in
    // the subscription code. The earlier version of this function read
    // `plan_id` directly, which doesn't exist in this schema, so it always
    // returned false → ads displayed for paid users too.
    if (!function_exists('property_theme_get_user_subscription')) return false;

    $subscription = property_theme_get_user_subscription($user_id);
    if (!$subscription) return false;

    // Resilient: handle whichever column the row actually carries.
    $plan_id = 0;
    if (isset($subscription->package_id) && $subscription->package_id) {
        $plan_id = intval($subscription->package_id);
    } elseif (isset($subscription->plan_id) && $subscription->plan_id) {
        $plan_id = intval($subscription->plan_id);
    }
    if (!$plan_id) return false;

    $price = (float) get_post_meta($plan_id, '_plan_price', true);
    return $price > 0;
}

/**
 * Free-plan author: either has no active subscription or their active plan
 * costs $0. Free listers' single-property pages monetise via ads.
 */
function property_author_is_free_plan(int $user_id): bool {
    return !property_author_has_paid_plan($user_id);
}