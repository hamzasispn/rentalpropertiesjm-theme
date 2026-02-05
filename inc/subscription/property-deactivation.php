<?php
/**
 * Property Activation/Deactivation - After subscription status change
 */


add_action('init', function () {
    if (!wp_next_scheduled('check_user_subscription_status')) {
        wp_schedule_event(time(), 'hourly', 'check_user_subscription_status');
    }
});

add_action('check_user_subscription_status', 'handle_expired_paused_subscriptions');

function handle_expired_paused_subscriptions()
{
    global $wpdb;

    $table = $wpdb->prefix . 'user_subscriptions';

    $subscriptions = $wpdb->get_results(
        "SELECT DISTINCT user_id 
         FROM $table 
         WHERE status IN ('paused', 'expired', 'canceled')"
    );

    if (empty($subscriptions)) {
        return;
    }

    foreach ($subscriptions as $sub) {

        $properties = get_posts(array(
            'post_type'      => 'property',
            'post_status'    => 'publish',
            'author'         => $sub->user_id,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ));

        // Sabko draft karo
        foreach ($properties as $property_id) {
            wp_update_post(array(
                'ID'          => $property_id,
                'post_status'=> 'draft',
            ));
        }
    }
}
