<?php
/**
 * Plan selection at submit time
 *
 * The add-property form is open to every logged-in user. The plan is chosen
 * when they hit Submit, from a popup:
 *
 *   - Free plan ($0)  → activated in place, form submits immediately.
 *                       Never routes through /checkout/.
 *   - Paid plan       → straight to /checkout?plan=ID.
 *
 * Users who already hold an active plan with capacity left never see the
 * popup — their submit goes through untouched.
 *
 * Everything here is also enforced server-side; the popup is only the UI.
 */

if (!defined('ABSPATH')) exit;

/* ────────────────────────────────────────────────────────────
 *  Plan predicates
 * ──────────────────────────────────────────────────────────── */

/**
 * A plan is "free" when it costs nothing. Guards against the price meta
 * being an empty string, "0", "0.00", or null.
 */
function pt_plan_is_free($plan) {
    if (empty($plan)) return false;
    $price = is_array($plan) ? ($plan['price'] ?? 0) : ($plan->price ?? 0);
    return (float) $price <= 0;
}

/**
 * Every published plan, cheapest first — the popup and the pricing page
 * both render from this.
 */
function pt_get_selectable_plans() {
    $posts = get_posts(array(
        'post_type'   => 'subscription_plan',
        'post_status' => 'publish',
        'numberposts' => -1,
    ));

    $plans = array();
    foreach ($posts as $post) {
        $plan = property_theme_get_plan($post->ID);
        if ($plan) $plans[] = $plan;
    }

    usort($plans, function ($a, $b) {
        return ($a['price'] ?? 0) <=> ($b['price'] ?? 0);
    });

    return $plans;
}

/**
 * How many listing slots does this user have left?
 *
 * Counts publish + pending + draft, because a pending listing already
 * occupies a slot as far as the plan cap is concerned. Mirrors the counter
 * shown in the add-property form.
 *
 * @return int  PHP_INT_MAX for unlimited plans, 0 when there is no plan.
 */
function pt_get_remaining_listing_slots($user_id) {
    global $wpdb;

    $user_id = (int) $user_id;
    if ($user_id <= 0) return 0;

    $subscription = property_theme_get_user_subscription($user_id);
    if (!$subscription) return 0;

    $plan = property_theme_get_plan($subscription->package_id);
    if (!$plan) return 0;

    $cap = (int) ($plan['max_properties'] ?? 0);
    if ($cap <= 0) return PHP_INT_MAX; // unlimited

    $used = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts}
         WHERE post_author = %d AND post_type = 'property'
         AND post_status IN ('publish', 'pending', 'draft')",
        $user_id
    ));

    return max(0, $cap - $used);
}

/**
 * The plans the submit-time popup should offer.
 *
 * Two cases, and they need different lists:
 *
 *   No subscription  → everything, free plans included. This is the normal
 *                      "first listing" path.
 *   Cap reached      → only plans with MORE capacity than the current one.
 *                      Re-picking the same plan would dead-end (the server
 *                      guard would still reject the submit), and offering a
 *                      free/smaller plan here would silently downgrade a paid
 *                      subscriber — cancelling their row in the DB while
 *                      Stripe keeps billing them. Downgrades belong in the
 *                      billing tab, which cancels Stripe properly.
 */
function pt_get_plans_for_modal($user_id) {
    $plans = pt_get_selectable_plans();

    $subscription = property_theme_get_user_subscription((int) $user_id);
    if (!$subscription) return $plans;

    $current = property_theme_get_plan($subscription->package_id);
    if (!$current) return $plans;

    $current_cap = (int) ($current['max_properties'] ?? 0);
    // 0 means unlimited — nothing can beat it, so there is nothing to offer.
    if ($current_cap === 0) return array();

    return array_values(array_filter($plans, function ($plan) use ($current, $current_cap) {
        if ((int) $plan['id'] === (int) $current['id']) return false;
        if (pt_plan_is_free($plan)) return false;
        $cap = (int) ($plan['max_properties'] ?? 0);
        return $cap === 0 || $cap > $current_cap; // unlimited, or genuinely bigger
    }));
}

/**
 * Can this user submit a NEW listing without picking a plan first?
 *
 * Admins bypass entirely. Everyone else needs an active plan with at least
 * one slot free. Editing an existing listing is not a new listing, so
 * callers pass $is_edit to skip the cap.
 */
function pt_user_can_submit_property($user_id, $is_edit = false) {
    $user_id = (int) $user_id;
    if ($user_id <= 0) return false;
    if (user_can($user_id, 'manage_options')) return true;

    $subscription = property_theme_get_user_subscription($user_id);
    if (!$subscription) return false;

    if ($is_edit) return true;

    return pt_get_remaining_listing_slots($user_id) > 0;
}

/* ────────────────────────────────────────────────────────────
 *  Free-plan activation
 * ──────────────────────────────────────────────────────────── */

/**
 * Activate a free plan for a user.
 *
 * Refuses anything that costs money — this path must never hand out a paid
 * plan, no matter what plan_id the request carries.
 *
 * @return true|WP_Error
 */
function pt_activate_free_plan($user_id, $plan_id) {
    $user_id = (int) $user_id;
    $plan_id = (int) $plan_id;

    if ($user_id <= 0) {
        return new WP_Error('not_logged_in', 'You must be signed in to choose a plan.');
    }

    $plan = property_theme_get_plan($plan_id);
    if (!$plan) {
        return new WP_Error('invalid_plan', 'That plan no longer exists.');
    }

    if (!pt_plan_is_free($plan)) {
        return new WP_Error('not_free', 'This plan requires payment.');
    }

    $existing = property_theme_get_user_subscription($user_id);
    if ($existing && (int) $existing->package_id === $plan_id) {
        return true; // already on it — treat as success, nothing to do
    }

    $result = property_theme_create_subscription($user_id, $plan_id, null, false);
    if (is_wp_error($result)) return $result;

    do_action('property_theme_subscription_activated', $user_id);

    return true;
}

/**
 * Nonce-protected activation link, used by the pricing page cards.
 *
 *   /pricing/?pt_activate_free_plan=123&_wpnonce=abc
 */
function pt_get_free_plan_activation_url($plan_id) {
    return wp_nonce_url(
        add_query_arg('pt_activate_free_plan', (int) $plan_id, home_url('/pricing/')),
        'pt_activate_free_plan_' . (int) $plan_id
    );
}

add_action('template_redirect', function () {
    if (empty($_GET['pt_activate_free_plan'])) return;

    $plan_id = (int) $_GET['pt_activate_free_plan'];

    if (!is_user_logged_in()) {
        wp_safe_redirect(wp_login_url(home_url('/pricing/')));
        exit;
    }

    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'pt_activate_free_plan_' . $plan_id)) {
        wp_safe_redirect(add_query_arg('plan_error', 'security', home_url('/pricing/')));
        exit;
    }

    $result = pt_activate_free_plan(get_current_user_id(), $plan_id);
    if (is_wp_error($result)) {
        wp_safe_redirect(add_query_arg('plan_error', $result->get_error_code(), home_url('/pricing/')));
        exit;
    }

    wp_safe_redirect(trailingslashit(pt_get_dashboard_url()) . '#add-property');
    exit;
});

/**
 * AJAX twin of the above, used by the submit-time popup so the form can be
 * posted straight after activation without a round trip through /pricing/.
 */
add_action('wp_ajax_pt_activate_free_plan', function () {
    check_ajax_referer('pt_plan_modal', 'nonce');

    $plan_id = (int) ($_POST['plan_id'] ?? 0);
    $result  = pt_activate_free_plan(get_current_user_id(), $plan_id);

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()), 400);
    }

    wp_send_json_success(array('message' => 'Plan activated.'));
});

add_action('wp_ajax_nopriv_pt_activate_free_plan', function () {
    wp_send_json_error(array('message' => 'You must be signed in to choose a plan.'), 401);
});
