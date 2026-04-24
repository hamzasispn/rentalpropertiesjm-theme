<?php
/**
 * Property Activation/Deactivation - After subscription status change
 */

add_action('init', function () {
    if (!wp_next_scheduled('check_user_subscription_status')) {
        wp_schedule_event(time(), 'hourly', 'check_user_subscription_status');
    }
});

add_action('check_user_subscription_status', 'handle_subscription_property_status');

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
