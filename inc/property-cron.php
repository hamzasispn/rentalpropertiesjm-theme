<?php
/**
 * Property Cron Jobs and Approval Hooks
 *
 * Responsibilities:
 *   1. Hourly cron: enforce per-plan property cap for every active subscription
 *      (so a downgrade automatically drafts the over-cap listings, and an upgrade
 *      lets previously auto-deactivated listings re-publish).
 *   2. Hourly cron: catch downgrades made directly in Stripe / via webhook by
 *      re-checking caps against the current plan.
 *   3. Notify admins when a property is submitted as pending.
 *   4. Notify the property owner that their listing is in review.
 */

if (!defined('ABSPATH')) exit;

/* ────────────────────────────────────────────────────────────────────────── *
 *  CRON: Enforce property limits for every active user
 * ────────────────────────────────────────────────────────────────────────── */

add_action('init', function () {
    if (!wp_next_scheduled('property_theme_enforce_limits_cron')) {
        wp_schedule_event(time() + 60, 'hourly', 'property_theme_enforce_limits_cron');
    }
});

add_action('property_theme_enforce_limits_cron', 'property_theme_enforce_limits_cron_handler');

/**
 * For every user with an active subscription, draft any published listings
 * that exceed their plan's max_properties, and re-publish auto-deactivated
 * listings if the user now has spare capacity.
 */
function property_theme_enforce_limits_cron_handler() {
    global $wpdb;
    $table = $wpdb->prefix . 'user_subscriptions';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) return;

    $rows = $wpdb->get_results("SELECT DISTINCT user_id, package_id FROM $table WHERE status = 'active'");
    if (empty($rows)) return;

    foreach ($rows as $row) {
        $user_id = intval($row->user_id);
        $plan_id = intval($row->package_id);
        if (!$user_id || !$plan_id) continue;
        if (!function_exists('property_theme_get_plan')) continue;

        $plan = property_theme_get_plan($plan_id);
        if (!$plan) continue;

        $cap = intval($plan['max_properties'] ?? 0);
        if ($cap <= 0) continue;

        // 1. Draft the over-cap published listings (oldest first)
        if (function_exists('property_theme_enforce_property_limit')) {
            property_theme_enforce_property_limit($user_id, $cap);
        }

        // 2. Re-publish auto-deactivated drafts up to the remaining cap
        property_theme_restore_within_cap($user_id, $cap);
    }
}

/**
 * Re-publish auto-deactivated drafts up to the user's remaining cap.
 *
 * Sorts NEWEST first to mirror enforce_property_limit's keep-newest rule:
 * if a user upgrades from cap=1 to cap=5 and has 9 auto-drafts on disk,
 * we re-publish the 4 most recent ones (matching what the user last saw live).
 *
 * Idempotent — safely re-runnable from the hourly cron without conflicting
 * with the immediate post-upgrade call.
 */
function property_theme_restore_within_cap($user_id, $cap) {
    global $wpdb;
    $user_id = intval($user_id);
    $cap     = intval($cap);
    if ($user_id <= 0 || $cap <= 0) return 0;

    $live = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts}
         WHERE post_author = %d AND post_type = 'property' AND post_status = 'publish'",
        $user_id
    ));

    $remaining = $cap - $live;
    if ($remaining <= 0) return 0;

    $candidates = get_posts(array(
        'post_type'      => 'property',
        'post_status'    => 'draft',
        'author'         => $user_id,
        'posts_per_page' => $remaining,
        'orderby'        => 'date',
        'order'          => 'DESC', // newest first — matches enforce_property_limit
        'fields'         => 'ids',
        'meta_query'     => array(array(
            'key'     => '_property_auto_deactivated',
            'value'   => '1',
            'compare' => '=',
        )),
    ));

    $count = 0;
    foreach ($candidates as $pid) {
        $ok = wp_update_post(array('ID' => $pid, 'post_status' => 'publish'), true);
        if (!is_wp_error($ok)) {
            delete_post_meta($pid, '_property_auto_deactivated');
            $count++;
        }
    }

    if ($count > 0) {
        error_log(sprintf(
            '[property_listing] upgrade restored: re-published %d newest auto-drafts for user %d',
            $count, $user_id
        ));
    }
    return $count;
}

/* ────────────────────────────────────────────────────────────────────────── *
 *  HOOKS: Plan change / subscription status change → re-enforce immediately
 * ────────────────────────────────────────────────────────────────────────── */

add_action('property_theme_subscription_plan_updated', function ($user_id, $subscription_id, $new_plan_id) {
    if (!function_exists('property_theme_get_plan') || !function_exists('property_theme_enforce_property_limit')) return;
    $plan = property_theme_get_plan($new_plan_id);
    $cap  = intval($plan['max_properties'] ?? 0);
    if ($cap > 0) {
        property_theme_enforce_property_limit($user_id, $cap);
        property_theme_restore_within_cap($user_id, $cap);
    }
}, 10, 3);

/* ────────────────────────────────────────────────────────────────────────── *
 *  PROPERTY APPROVAL FLOW: emails on submission and on admin decision
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * When a new property is published or pending, send the right notification.
 * Skips admin-published listings (they bypass review).
 */
add_action('transition_post_status', function ($new_status, $old_status, $post) {
    if (!$post || $post->post_type !== 'property') return;
    if ($old_status === $new_status) return;

    $author = get_userdata($post->post_author);
    if (!$author) return;

    // 1. Newly submitted for review (first save or moved into pending)
    if ($new_status === 'pending' && in_array($old_status, array('new', 'auto-draft', 'draft', 'publish'), true)) {
        // Email to author
        wp_mail(
            $author->user_email,
            'Your property listing is pending review',
            "Hi " . $author->display_name . ",\n\n" .
            'Thanks for submitting "' . $post->post_title . "\". Our team will review your listing and publish it once approved. You'll get an email as soon as it's live.\n\n" .
            "View status: " . home_url('/dashboard') . "\n\n" .
            "— " . get_bloginfo('name'),
            array('Content-Type: text/plain; charset=UTF-8')
        );

        // Email to admins
        $admin_emails = property_theme_get_admin_emails();
        if ($admin_emails) {
            $review_url = admin_url('post.php?post=' . $post->ID . '&action=edit');
            wp_mail(
                $admin_emails,
                '[Pending Review] New property submitted: ' . $post->post_title,
                "A new property listing is awaiting review.\n\n" .
                "Title: " . $post->post_title . "\n" .
                "Submitted by: " . $author->display_name . " <" . $author->user_email . ">\n\n" .
                "Review on the admin panel: " . home_url('/admin-panel') . "\n" .
                "Edit directly: " . $review_url,
                array('Content-Type: text/plain; charset=UTF-8')
            );
        }
    }
}, 10, 3);

/**
 * Collect admin emails (administrators only).
 */
function property_theme_get_admin_emails() {
    $admins = get_users(array('role' => 'administrator', 'fields' => array('user_email')));
    $emails = array();
    foreach ($admins as $a) {
        if (!empty($a->user_email)) $emails[] = $a->user_email;
    }
    return $emails;
}

/* ────────────────────────────────────────────────────────────────────────── *
 *  Block direct publishing for non-admin authors (safety net)
 *  Only force "pending" when the *current actor* is a non-admin who is trying
 *  to publish their own property for the first time. This must NOT trigger
 *  on:
 *    - admin approval (current user is admin),
 *    - cron / system updates (no current user — bypass),
 *    - re-saving an already-published listing,
 *    - the auto-deactivated → publish flow done programmatically.
 * ────────────────────────────────────────────────────────────────────────── */

add_filter('wp_insert_post_data', function ($data, $postarr) {
    if (($data['post_type'] ?? '') !== 'property') return $data;
    if (($data['post_status'] ?? '') !== 'publish') return $data;

    // System / cron / WP-CLI: no logged-in user → trust caller
    $current_user_id = get_current_user_id();
    if (!$current_user_id) return $data;

    // Admin actor: trust them (this is the approval path)
    if (user_can($current_user_id, 'manage_options')) return $data;

    // Already-published listings being re-saved are just edits, not new publishes
    if (!empty($postarr['ID'])) {
        $existing = get_post_status($postarr['ID']);
        if ($existing === 'publish') return $data;
    }

    // Non-admin user trying to publish — bounce to pending
    $data['post_status'] = 'pending';
    return $data;
}, 10, 2);
