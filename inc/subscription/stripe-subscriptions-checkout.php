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

    // Verify user owns this subscription
    $subscription = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}user_subscriptions WHERE id = %d AND user_id = %d",
        $subscription_id,
        $user_id
    ));

    if (!$subscription) {
        return new WP_Error('not_found', 'Subscription not found', array('status' => 404));
    }

    $result = property_theme_update_subscription_plan($subscription_id, $new_plan_id, $billing_cycle, $prorate);

    if (is_wp_error($result)) {
        return $result;
    }

    return array(
        'success' => true,
        'message' => 'Subscription plan updated successfully',
    );
}
