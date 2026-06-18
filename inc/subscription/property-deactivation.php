<?php
/**
 * Property Activation/Deactivation - After subscription status change
 */

/**
 * Enforce a user's property cap by drafting OLDER published listings
 * once the user is over the limit. Keeps the user's NEWEST listings live
 * (matches client expectation: a downgrade drafts the older listings,
 * the latest ones stay published).
 *
 * Marks drafted ones with _property_auto_deactivated=1 so an upgrade later
 * can auto re-publish them via property_theme_restore_within_cap().
 *
 * @param int $user_id
 * @param int $max_properties Cap from the new plan; 0 means unlimited
 * @return int Number of properties drafted
 */
function property_theme_enforce_property_limit($user_id, $max_properties) {
    $user_id = intval($user_id);
    $max_properties = intval($max_properties);
    if ($user_id <= 0 || $max_properties <= 0) {
        return 0; // unlimited / nothing to do
    }

    global $wpdb;

    // Sort NEWEST first. array_slice($arr, $max) drops the first $max items
    // and returns the rest — i.e. the older ones beyond the cap. Those get
    // drafted; the newest $max stay live.
    $published = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts}
         WHERE post_author = %d AND post_type = 'property' AND post_status = 'publish'
         ORDER BY post_date DESC",
        $user_id
    ));

    if (count($published) <= $max_properties) {
        return 0;
    }

    $to_draft = array_slice($published, $max_properties);
    $count = 0;
    foreach ($to_draft as $pid) {
        $pid = intval($pid);
        $ok = wp_update_post(array('ID' => $pid, 'post_status' => 'draft'), true);
        if (!is_wp_error($ok)) {
            update_post_meta($pid, '_property_auto_deactivated', 1);
            $count++;
        }
    }

    if ($count > 0) {
        error_log(sprintf(
            '[property_listing] downgrade enforced: drafted %d older listings for user %d (kept newest %d live)',
            $count, $user_id, $max_properties
        ));
    }
    return $count;
}

/**
 * Schedule the hourly subscription-status sweep.
 *
 * `wp_schedule_event` writes to the `cron` option. Under load (parallel admin-ajax
 * or REST hits) two requests can race and one of them gets "The cron event list
 * could not be saved." The schedule is global state — losing one race is harmless
 * because the other request did persist the schedule. We swallow the WP_Error so
 * it doesn't pollute error.log on every request.
 *
 * Also: only attempt to schedule on full page loads, not REST/AJAX/cron requests
 * (which fire `init` constantly and don't need to own the scheduling decision).
 */
add_action('init', function () {
    if (defined('DOING_AJAX')   && DOING_AJAX)   return;
    if (defined('DOING_CRON')   && DOING_CRON)   return;
    if (defined('REST_REQUEST') && REST_REQUEST) return;

    if (wp_next_scheduled('check_user_subscription_status')) return;

    $result = wp_schedule_event(time() + 60, 'hourly', 'check_user_subscription_status', array(), true);
    // $result is true on success, WP_Error on failure (e.g. could_not_set race).
    // We deliberately don't log — the next request will reschedule cleanly.
    unset($result);
});

add_action('check_user_subscription_status', 'handle_subscription_property_status');

/**
 * Cron: handle subscription state → property visibility.
 *
 *   - User has NO active subscription (paused/expired/canceled and nothing else active):
 *       Draft every published property and tag it with _property_auto_deactivated=1
 *       so it can be restored automatically when the user re-subscribes.
 *
 *   - Active subscription: do NOT touch anything here. The single source of
 *     truth for "active user" cap enforcement and restore is
 *     `property_theme_enforce_limits_cron_handler` (in property-cron.php), which
 *     runs hourly on its own event and is cap-aware. This avoids the previous
 *     bug where this cron republished EVERY auto-deactivated draft on each tick
 *     (over the cap) and then the other cron drafted the overflow again — a
 *     visible flicker on every refresh.
 */
function handle_subscription_property_status()
{
    global $wpdb;

    $table = $wpdb->prefix . 'user_subscriptions';

    $inactive_users = $wpdb->get_col(
        "SELECT DISTINCT user_id
         FROM $table
         WHERE status IN ('paused', 'expired', 'canceled')"
    );

    if (empty($inactive_users)) return;

    foreach ($inactive_users as $user_id) {
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
            update_post_meta($property_id, '_property_auto_deactivated', 1);
        }
    }
}
