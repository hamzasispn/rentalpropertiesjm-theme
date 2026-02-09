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

    // 🔴 Inactive subscriptions → draft
    $inactive_users = $wpdb->get_col(
        "SELECT DISTINCT user_id 
         FROM $table 
         WHERE status IN ('paused', 'expired', 'canceled')"
    );

    if (!empty($inactive_users)) {
        foreach ($inactive_users as $user_id) {

            $properties = get_posts(array(
                'post_type'      => 'property',
                'post_status'    => 'publish',
                'author'         => $user_id,
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ));

            foreach ($properties as $property_id) {
                wp_update_post(array(
                    'ID' => $property_id,
                    'post_status' => 'draft',
                ));
            }
        }
    }

    // 🟢 Active subscriptions → publish
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
            ));

            foreach ($properties as $property_id) {
                wp_update_post(array(
                    'ID' => $property_id,
                    'post_status' => 'publish',
                ));
            }
        }
    }
}
