<?php
/**
 * Property Activation/Deactivation - After subscription status change
 */

/**
 * Enforce a user's property cap by drafting the oldest published listings
 * that exceed the limit. Marks them as auto-deactivated so they're auto
 * re-published if the user later upgrades back.
 *
 * @param int $user_id
 * @param int $max_properties Cap from the new plan; 0 means unlimited
 * @return int Number of properties drafted
 */
function property_theme_enforce_property_limit($user_id, $max_properties) {
    $user_id = intval($user_id);
    $max_properties = intval($max_properties);
    if ($user_id <= 0 || $max_properties <= 0) {
        return 0;
    }

    global $wpdb;
    $published = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts}
         WHERE post_author = %d AND post_type = 'property' AND post_status = 'publish'
         ORDER BY post_date ASC",
        $user_id
    ));

    if (count($published) <= $max_properties) {
        return 0;
    }

    $to_draft = array_slice($published, $max_properties);
    $count = 0;
    foreach ($to_draft as $pid) {
        $pid = intval($pid);
        // Use direct DB update to bypass any save_post filters that may re-publish
        $ok = wp_update_post(array('ID' => $pid, 'post_status' => 'draft'), true);
        if (!is_wp_error($ok)) {
            update_post_meta($pid, '_property_auto_deactivated', 1);
            $count++;
        }
    }
    return $count;
}

add_action('init', function () {
    if (!wp_next_scheduled('check_user_subscription_status')) {
        wp_schedule_event(time(), 'hourly', 'check_user_subscription_status');
    }
});

add_action('check_user_subscription_status', 'handle_subscription_property_status');

// Run the limit-enforcement cron alongside the subscription-status cron so
// downgrades are reflected even if no plan-change event fired.
add_action('check_user_subscription_status', 'property_theme_enforce_limits_cron_handler');

function handle_subscription_property_status()
{
    global $wpdb;

    $table = $wpdb->prefix . 'user_subscriptions';

    // 🔴 Inactive subscriptions → draft (only if no other active sub exists)
    $inactive_users = $wpdb->get_col(
        "SELECT DISTINCT user_id
         FROM $table
         WHERE status IN ('paused', 'expired', 'canceled')"
    );

    if (!empty($inactive_users)) {
        foreach ($inactive_users as $user_id) {
            // Skip if user has another active subscription
            $has_active = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE user_id = %d AND status = 'active'",
                $user_id
            ));
            if ($has_active > 0) continue;

            $properties = get_posts(array(
                'post_type'      => 'property',
                'post_status'    => 'publish',
                'author'         => $user_id,
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ));

            foreach ($properties as $property_id) {
                wp_update_post(array(
                    'ID'          => $property_id,
                    'post_status' => 'draft',
                ));
                // Mark as auto-deactivated so cron can restore it later
                update_post_meta($property_id, '_property_auto_deactivated', 1);
            }
        }
    }

    // 🟢 Active subscriptions → re-publish ONLY auto-deactivated properties
    // (never re-publish admin-rejected properties or pending-review properties)
    $active_users = $wpdb->get_col(
        "SELECT DISTINCT user_id
         FROM $table
         WHERE status = 'active'"
    );

    if (!empty($active_users)) {
        foreach ($active_users as $user_id) {

            $properties = get_posts(array(
                'post_type'      => 'property',
                'post_status'    => 'draft',
                'author'         => $user_id,
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'     => '_property_auto_deactivated',
                        'value'   => '1',
                        'compare' => '=',
                    ),
                ),
            ));

            foreach ($properties as $property_id) {
                wp_update_post(array(
                    'ID'          => $property_id,
                    'post_status' => 'publish',
                ));
                delete_post_meta($property_id, '_property_auto_deactivated');
            }
        }
    }
}
