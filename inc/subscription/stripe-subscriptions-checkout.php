<?php
/**
 * Stripe Checkout Handler - Refactored for Native Subscriptions
 * 
 * Replaces the old process-payment endpoint with native subscription flow.
 */

require_once get_template_directory() . '/inc/subscription/stripe-subscriptions-native.php';

/**
 * REST API endpoint for creating subscriptions
 */
add_action('rest_api_init', function () {
    register_rest_route('property-theme/v1', '/create-subscription', array(
        'methods' => 'POST',
        'callback' => 'property_theme_create_subscription_endpoint',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));

    // Legacy endpoint for backward compatibility (deprecated)
    register_rest_route('property-theme/v1', '/process-payment', array(
        'methods' => 'POST',
        'callback' => 'property_theme_process_payment_legacy',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));
});

/**
 * Create subscription endpoint
 * 
 * POST /wp-json/property-theme/v1/create-subscription
 * 
 * Request body:
 * {
 *   "plan_id": 123,
 *   "payment_method_id": "pm_xxx",
 *   "billing_cycle": "monthly",
 *   "billing_details": {
 *     "name": "John Doe",
 *     "email": "john@example.com"
 *   }
 * }
 */
function property_theme_create_subscription_endpoint($request)
{
    $user_id = get_current_user_id();
    $plan_id = intval($request->get_param('plan_id'));
    $payment_method_id = sanitize_text_field($request->get_param('payment_method_id'));
    $billing_cycle = sanitize_text_field($request->get_param('billing_cycle')) ?: 'monthly';
    $billing_details = $request->get_param('billing_details') ?: array();

    // Validate inputs
    if (!$user_id || !$plan_id || !$payment_method_id) {
        return new WP_Error('missing_params', 'Missing required parameters (plan_id, payment_method_id)', array('status' => 400));
    }

    if (!in_array($billing_cycle, array('monthly', 'yearly'))) {
        return new WP_Error('invalid_billing_cycle', 'Billing cycle must be monthly or yearly', array('status' => 400));
    }

    $result = property_theme_create_stripe_native_subscription($user_id, $plan_id, $payment_method_id, $billing_cycle, $billing_details);

    if (is_wp_error($result)) {
        return $result;
    }

    return $result;
}

/**
 * Legacy payment endpoint (deprecated)
 * 
 * Kept for backward compatibility but redirects to native subscriptions
 */
function property_theme_process_payment_legacy($request)
{
    // For now, redirect to new endpoint
    return property_theme_create_subscription_endpoint($request);
}

/**
 * Cancel subscription endpoint
 * 
 * POST /wp-json/property-theme/v1/cancel-subscription
 */
add_action('rest_api_init', function () {
    register_rest_route('property-theme/v1', '/cancel-subscription', array(
        'methods' => 'POST',
        'callback' => 'property_theme_cancel_subscription_endpoint',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));
});

function property_theme_cancel_subscription_endpoint($request)
{
    global $wpdb;

    $user_id = get_current_user_id();
    $subscription_id = intval($request->get_param('id'));
    $at_period_end = $request->get_param('at_period_end') === 'true' || $request->get_param('at_period_end') === 1;

    // Verify user owns this subscription
    $subscription = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}user_subscriptions WHERE id = %d AND user_id = %d",
        $subscription_id,
        $user_id
    ));

    if (!$subscription) {
        return new WP_Error(
            'not_found',
            'Subscription not found. Data: ' . print_r($subscription, true),
            array('status' => 404)
        );
    }

    $result = property_theme_cancel_stripe_subscription($subscription_id, $at_period_end);

    if (is_wp_error($result)) {
        return $result;
    }

    return array(
        'success' => true,
        'message' => $at_period_end ? 'Subscription will cancel at period end' : 'Subscription has been canceled',
    );
}

/**
 * Update subscription plan endpoint
 * 
 * POST /wp-json/property-theme/v1/update-subscription-plan
 */
add_action('rest_api_init', function () {
    register_rest_route('property-theme/v1', '/update-subscription-plan', array(
        'methods' => 'POST',
        'callback' => 'property_theme_update_subscription_plan_endpoint',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));
});

function property_theme_update_subscription_plan_endpoint($request)
{
    global $wpdb;

    $user_id = get_current_user_id();
    $subscription_id = intval($request->get_param('subscription_id'));
    $new_plan_id = intval($request->get_param('plan_id'));
    $billing_cycle = sanitize_text_field($request->get_param('billing_cycle')) ?: 'monthly';
    $prorate = $request->get_param('prorate') !== 'false' && $request->get_param('prorate') !== 0;

    $subscription = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}user_subscriptions WHERE id = %d AND user_id = %d",
        $subscription_id,
        $user_id
    ));

    if (!$subscription) {
        return new WP_Error('not_found', 'Subscription not found', array('status' => 404));
    }

    $old_plan = property_theme_get_plan($subscription->package_id);
    $new_plan = property_theme_get_plan($new_plan_id);

    if (!$new_plan) {
        return new WP_Error('invalid_plan', 'Invalid plan', array('status' => 400));
    }

    $result = property_theme_update_subscription_plan($subscription_id, $new_plan_id, $billing_cycle, $prorate);

    if (is_wp_error($result)) {
        return $result;
    }

    // Downgrade: if new plan has fewer property slots, draft excess published properties
    $old_max = intval($old_plan['max_properties'] ?? 0);
    $new_max = intval($new_plan['max_properties'] ?? 0);

    if ($new_max > 0 && $new_max < $old_max) {
        $published = $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_author = %d AND post_type = 'property' AND post_status = 'publish'
             ORDER BY post_date ASC",
            $user_id
        ));

        if (count($published) > $new_max) {
            $to_draft = array_slice($published, $new_max);
            foreach ($to_draft as $pid) {
                wp_update_post(array('ID' => intval($pid), 'post_status' => 'draft'));
            }
        }
    }

    return array(
        'success' => true,
        'message' => 'Subscription plan updated successfully',
    );
}

// Toggle auto-renew endpoint
add_action('rest_api_init', function () {
    register_rest_route('property-theme/v1', '/toggle-auto-renew', array(
        'methods' => 'POST',
        'callback' => 'property_theme_toggle_auto_renew_endpoint',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));
});

function property_theme_toggle_auto_renew_endpoint($request) {
    $user_id = get_current_user_id();
    $enable  = (bool) $request->get_param('enable');

    $subscription = property_theme_get_user_subscription($user_id);
    if (!$subscription) {
        return new WP_Error('no_subscription', 'No active subscription', array('status' => 404));
    }

    // Sync with Stripe if subscription exists
    if (!empty($subscription->stripe_subscription_id)) {
        $cancel_at_period_end = $enable ? 'false' : 'true';
        $resp = property_theme_stripe_api_call('POST', '/v1/subscriptions/' . $subscription->stripe_subscription_id, array(
            'cancel_at_period_end' => $cancel_at_period_end,
        ), STRIPE_SECRET_KEY);

        if (isset($resp['error'])) {
            error_log('[PropertyTheme] toggle-auto-renew Stripe error: ' . $resp['error']['message']);
            return new WP_Error('stripe_error', $resp['error']['message'], array('status' => 502));
        }

        // Sync Stripe truth back to DB
        if (function_exists('property_theme_update_subscription_from_stripe') && isset($resp['id'])) {
            property_theme_update_subscription_from_stripe($resp['id'], $resp);
        }
    }

    return property_theme_toggle_auto_renew($user_id, $enable);
}

// Get user invoices endpoint
add_action('rest_api_init', function () {
    register_rest_route('property-theme/v1', '/user/invoices', array(
        'methods' => 'GET',
        'callback' => 'property_theme_get_user_invoices_endpoint',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));
});

function property_theme_get_user_invoices_endpoint() {
    global $wpdb;
    $user_id = get_current_user_id();

    $table = $wpdb->prefix . 'property_invoices';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        return new WP_REST_Response(array(), 200);
    }

    $invoices = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT 50",
        $user_id
    ));

    return new WP_REST_Response($invoices ?: array(), 200);
}

// Update payment method on Stripe and locally
add_action('rest_api_init', function () {
    register_rest_route('property/v1', '/user/payment-method', array(
        'methods' => 'POST',
        'callback' => 'property_theme_update_payment_method_endpoint',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));
});

function property_theme_update_payment_method_endpoint($request) {
    $user_id           = get_current_user_id();
    $payment_method_id = sanitize_text_field($request->get_param('payment_method_id'));

    if (!$payment_method_id) {
        return new WP_Error('missing_param', 'payment_method_id required', array('status' => 400));
    }

    $subscription = property_theme_get_user_subscription($user_id);
    if (!$subscription) {
        return new WP_Error('no_subscription', 'No active subscription', array('status' => 404));
    }

    $stripe_customer_id = get_user_meta($user_id, '_stripe_customer_id', true);
    if (!$stripe_customer_id) {
        return new WP_Error('no_customer', 'No Stripe customer found', array('status' => 404));
    }

    // Attach new payment method to customer
    $attach = property_theme_stripe_api_call('POST', '/v1/payment_methods/' . $payment_method_id . '/attach', array(
        'customer' => $stripe_customer_id,
    ), STRIPE_SECRET_KEY);

    if (isset($attach['error'])) {
        return new WP_Error('stripe_error', $attach['error']['message'], array('status' => 402));
    }

    // Set as default
    property_theme_stripe_api_call('POST', '/v1/customers/' . $stripe_customer_id, array(
        'invoice_settings[default_payment_method]' => $payment_method_id,
    ), STRIPE_SECRET_KEY);

    if (!empty($subscription->stripe_subscription_id)) {
        property_theme_stripe_api_call('POST', '/v1/subscriptions/' . $subscription->stripe_subscription_id, array(
            'default_payment_method' => $payment_method_id,
        ), STRIPE_SECRET_KEY);
    }

    // Get card details
    $pm = property_theme_stripe_api_call('GET', '/v1/payment_methods/' . $payment_method_id, array(), STRIPE_SECRET_KEY);
    if (!isset($pm['error']) && isset($pm['card'])) {
        $user = get_userdata($user_id);
        property_theme_update_payment_method($user_id, array(
            'card_last_four'           => $pm['card']['last4'] ?? '',
            'card_brand'               => $pm['card']['brand'] ?? '',
            'exp_month'                => $pm['card']['exp_month'] ?? 0,
            'exp_year'                 => $pm['card']['exp_year'] ?? 0,
            'billing_name'             => $user->display_name,
            'billing_email'            => $user->user_email,
            'stripe_payment_method_id' => $payment_method_id,
        ));
    }

    return array('success' => true, 'message' => 'Payment method updated');
}
