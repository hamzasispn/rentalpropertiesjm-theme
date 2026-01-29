<?php
/**
 * Stripe Native Subscriptions - Refactored Subscription Creation
 * 
 * Replaces PaymentIntent-based billing with Stripe Native Subscriptions
 * for proper recurring billing with automatic renewal via webhooks.
 */

require_once get_template_directory() . '/inc/subscription/subscriptions.php';
require_once get_template_directory() . '/inc/subscription/stripe-products-setup.php';
require_once get_template_directory() . '/inc/subscription/stripe-helper.php';

/**
 * Create a Stripe Native Subscription
 * 
 * This replaces the old PaymentIntent flow. It:
 * 1. Gets or creates a Stripe customer
 * 2. Attaches the payment method to the customer
 * 3. Creates a subscription with collection_method = charge_automatically
 * 4. Saves subscription data to WordPress
 * 5. Returns subscription details
 * 
 * @param int $user_id WordPress user ID
 * @param int $plan_id WordPress plan post ID
 * @param string $payment_method_id Stripe payment method ID (pm_xxx)
 * @param string $billing_cycle 'monthly' or 'yearly'
 * @param array $billing_details Optional billing address info
 * @return array|WP_Error Subscription response or error
 */
function property_theme_create_stripe_native_subscription($user_id, $plan_id, $payment_method_id, $billing_cycle = 'monthly', $billing_details = array()) {
    try {
        // Validate inputs
        $user = get_userdata($user_id);
        if (!$user) {
            throw new Exception('User not found');
        }

        $plan = property_theme_get_plan($plan_id);
        if (!$plan) {
            throw new Exception('Invalid plan');
        }

        // Get or create Stripe customer
        $stripe_customer_id = property_theme_get_or_create_stripe_customer($user_id);
        if (!$stripe_customer_id) {
            throw new Exception('Failed to create Stripe customer');
        }

        // Attach payment method to customer
        $attach_response = property_theme_stripe_api_call('POST', '/v1/payment_methods/' . $payment_method_id . '/attach', array(
            'customer' => $stripe_customer_id,
        ), STRIPE_SECRET_KEY);

        if (isset($attach_response['error'])) {
            throw new Exception('Failed to attach payment method: ' . $attach_response['error']['message']);
        }

        // Set as default payment method for customer
        $update_response = property_theme_stripe_api_call('POST', '/v1/customers/' . $stripe_customer_id, array(
            'invoice_settings[default_payment_method]' => $payment_method_id,
        ), STRIPE_SECRET_KEY);

        if (isset($update_response['error'])) {
            throw new Exception('Failed to set default payment method: ' . $update_response['error']['message']);
        }

        // Get Stripe price ID for the plan and billing cycle
        $stripe_price_id = property_theme_get_stripe_price_id($plan_id, $billing_cycle);
        if (!$stripe_price_id) {
            throw new Exception('Stripe price not configured for this plan. Please sync products first.');
        }

        $subscription_data = array(
            'customer' => $stripe_customer_id,
            'items[0][price]' => $stripe_price_id,
            'default_payment_method' => $payment_method_id,
            'collection_method' => 'charge_automatically', // Auto-charge on renewal
            'payment_behavior' => 'allow_incomplete', // Allow incomplete for retries
            'off_session' => 'true',
            'expand[]' => 'latest_invoice.payment_intent', // Get payment status
            'metadata[user_id]' => $user_id,
            'metadata[plan_id]' => $plan_id,
            'metadata[billing_cycle]' => $billing_cycle,
            'description' => $plan['name'] . ' Subscription - ' . get_bloginfo('name'),
        );

        $subscription_response = property_theme_stripe_api_call('POST', '/v1/subscriptions', $subscription_data, STRIPE_SECRET_KEY);

        if (isset($subscription_response['error'])) {
            throw new Exception('Failed to create subscription: ' . $subscription_response['error']['message']);
        }

        // Get payment method details for storage
        $payment_method_response = property_theme_stripe_api_call('GET', '/v1/payment_methods/' . $payment_method_id, array(), STRIPE_SECRET_KEY);
        
        if (!isset($payment_method_response['error']) && isset($payment_method_response['card'])) {
            $card = $payment_method_response['card'];
            property_theme_update_payment_method($user_id, array(
                'card_last_four' => $card['last4'] ?? '',
                'card_brand' => $card['brand'] ?? '',
                'exp_month' => $card['exp_month'] ?? 0,
                'exp_year' => $card['exp_year'] ?? 0,
                'billing_name' => $billing_details['name'] ?? $user->display_name,
                'billing_email' => $user->user_email,
                'stripe_payment_method_id' => $payment_method_id,
            ));
        }

        property_theme_save_stripe_native_subscription($user_id, $plan_id, $subscription_response, $stripe_customer_id, $stripe_price_id);

        // Store Stripe customer ID
        update_user_meta($user_id, '_stripe_customer_id', $stripe_customer_id);

        // Log the subscription creation
        property_theme_log_subscription_event(0, 'subscription_created_native', 'success', 'Native Stripe subscription created', array(
            'user_id' => $user_id,
            'plan_id' => $plan_id,
            'stripe_subscription_id' => $subscription_response['id'],
            'stripe_customer_id' => $stripe_customer_id,
            'billing_cycle' => $billing_cycle,
        ));

        // Send confirmation email
        wp_mail(
            $user->user_email,
            'Subscription Confirmed - ' . get_bloginfo('name'),
            sprintf(
                "Thank you for subscribing to %s!\n\n" .
                "Plan: %s\n" .
                "Billing Cycle: %s\n" .
                "Next Billing Date: %s\n\n" .
                "Your subscription is now active and will automatically renew on the next billing date.\n\n" .
                "Manage your subscription: %s\n\n" .
                "Best regards,\n%s Team",
                $plan['name'],
                $plan['name'],
                ucfirst($billing_cycle),
                date('F j, Y', $subscription_response['current_period_end']),
                home_url('/dashboard/subscription/'),
                get_bloginfo('name')
            )
        );

        // Fire action hook for custom integrations
        do_action('property_theme_native_subscription_created', $user_id, $plan_id, $subscription_response);

        return array(
            'success' => true,
            'subscription_id' => $subscription_response['id'],
            'stripe_subscription_id' => $subscription_response['id'],
            'customer_id' => $stripe_customer_id,
            'status' => $subscription_response['status'],
            'current_period_end' => $subscription_response['current_period_end'],
            'message' => 'Subscription created successfully',
            'redirect_url' => home_url('/dashboard'),
        );

    } catch (Exception $e) {
        error_log('[PropertyTheme] Native Subscription Creation Error: ' . $e->getMessage());
        return new WP_Error('subscription_failed', $e->getMessage(), array('status' => 402));
    }
}

/**
 * Save Stripe Native Subscription to WordPress database
 * 
 * @param int $user_id User ID
 * @param int $plan_id Plan ID
 * @param array $stripe_subscription Stripe subscription object
 * @param string $stripe_customer_id Stripe customer ID
 * @param string $stripe_price_id Stripe price ID
 * @return int|false Subscription ID or false on failure
 */
function property_theme_save_stripe_native_subscription($user_id, $plan_id, $stripe_subscription, $stripe_customer_id, $stripe_price_id) {
    global $wpdb;

    // Cancel any existing subscriptions
    $wpdb->update(
        $wpdb->prefix . 'user_subscriptions',
        array('status' => 'canceled'),
        array('user_id' => $user_id, 'status' => 'active')
    );

    // Insert new subscription with Stripe data
    $result = $wpdb->insert(
        $wpdb->prefix . 'user_subscriptions',
        array(
            'user_id' => $user_id,
            'package_id' => $plan_id,
            'stripe_subscription_id' => $stripe_subscription['id'],
            'stripe_customer_id' => $stripe_customer_id,
            'stripe_price_id' => $stripe_price_id,
            'status' => $stripe_subscription['status'], // active, incomplete, past_due, etc.
            'expiry_date' => date('Y-m-d H:i:s', $stripe_subscription['current_period_end']),
            'current_period_start' => date('Y-m-d H:i:s', $stripe_subscription['current_period_start']),
            'current_period_end' => date('Y-m-d H:i:s', $stripe_subscription['current_period_end']),
            'auto_renew' => 1, // Always true for native subscriptions
        )
    );

    if ($result === false) {
        error_log('[PropertyTheme] Database error saving subscription: ' . $wpdb->last_error);
        return false;
    }

    return $wpdb->insert_id;
}

/**
 * Update subscription from Stripe subscription object
 * 
 * Called by webhook handlers to sync Stripe data back to WordPress
 * 
 * @param string $stripe_subscription_id Stripe subscription ID
 * @param array $stripe_subscription Stripe subscription object
 * @return bool Success
 */
function property_theme_update_subscription_from_stripe($stripe_subscription_id, $stripe_subscription) {
    global $wpdb;

    $update_data = array(
        'status' => $stripe_subscription['status'],
        'current_period_start' => date('Y-m-d H:i:s', $stripe_subscription['current_period_start']),
        'current_period_end' => date('Y-m-d H:i:s', $stripe_subscription['current_period_end']),
        'expiry_date' => date('Y-m-d H:i:s', $stripe_subscription['current_period_end']),
        'last_webhook_event_at' => current_time('mysql'),
    );

    // Handle cancellation
    if ($stripe_subscription['status'] === 'canceled') {
        $update_data['canceled_at'] = date('Y-m-d H:i:s', $stripe_subscription['canceled_at'] ?? time());
    }

    // Handle cancel at period end
    if ($stripe_subscription['cancel_at']) {
        $update_data['cancel_at'] = date('Y-m-d H:i:s', $stripe_subscription['cancel_at']);
        $update_data['cancel_at_period_end'] = (bool)$stripe_subscription['cancel_at_period_end'];
    }

    $result = $wpdb->update(
        $wpdb->prefix . 'user_subscriptions',
        $update_data,
        array('stripe_subscription_id' => $stripe_subscription_id)
    );

    return $result !== false;
}

/**
 * Get or create Stripe customer
 * 
 * @param int $user_id WordPress user ID
 * @return string|false Stripe customer ID or false
 */
function property_theme_get_or_create_stripe_customer($user_id) {
    $stripe_customer_id = get_user_meta($user_id, '_stripe_customer_id', true);
    if ($stripe_customer_id) {
        return $stripe_customer_id;
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }

    $response = property_theme_stripe_api_call('POST', '/v1/customers', array(
        'email' => $user->user_email,
        'name' => $user->display_name,
        'metadata[user_id]' => $user_id,
        'metadata[site_url]' => home_url(),
        'metadata[created_at]' => current_time('mysql'),
    ), STRIPE_SECRET_KEY);

    if (isset($response['error'])) {
        error_log('[PropertyTheme] Stripe Customer Creation Error: ' . $response['error']['message']);
        return false;
    }

    if (isset($response['id'])) {
        update_user_meta($user_id, '_stripe_customer_id', $response['id']);
        return $response['id'];
    }

    return false;
}

/**
 * Cancel a Stripe subscription
 * 
 * @param int $subscription_id WordPress subscription ID
 * @param bool $at_period_end If true, subscription ends at period end. If false, immediately.
 * @return bool|WP_Error Success or error
 */
function property_theme_cancel_stripe_subscription($subscription_id, $at_period_end = true) {
    global $wpdb;

    try {
        $subscription = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}user_subscriptions WHERE id = %d",
            $subscription_id
        ));

        if (!$subscription) {
            return new WP_Error('not_found', 'Subscription not found');
        }

        if (!$subscription->stripe_subscription_id) {
            return new WP_Error('invalid', 'No Stripe subscription ID found');
        }

        $cancel_data = array(
            'cancel_at_period_end' => $at_period_end ? 'true' : 'false',
        );

        $response = property_theme_stripe_api_call('DELETE', '/v1/subscriptions/' . $subscription->stripe_subscription_id, $cancel_data, STRIPE_SECRET_KEY);

        if (isset($response['error'])) {
            throw new Exception($response['error']['message']);
        }

        // Update WordPress subscription status
        property_theme_update_subscription_from_stripe($subscription->stripe_subscription_id, $response);

        // Log the cancellation
        $user = get_userdata($subscription->user_id);
        property_theme_log_subscription_event($subscription_id, 'subscription_canceled', 'success', 'Subscription canceled', array(
            'user_email' => $user->user_email,
            'at_period_end' => $at_period_end,
        ));

        // Send cancellation email
        if ($user) {
            wp_mail(
                $user->user_email,
                'Subscription Canceled - ' . get_bloginfo('name'),
                sprintf(
                    "Your subscription has been canceled.\n\n" .
                    "Cancellation Details:\n" .
                    "- Reason: User requested\n" .
                    "- Effective Date: %s\n\n" .
                    "If you have any questions, please contact us.\n\n" .
                    "Best regards,\n%s Team",
                    $at_period_end ? 'At period end' : 'Immediately',
                    get_bloginfo('name')
                )
            );
        }

        do_action('property_theme_subscription_canceled', $subscription->user_id, $subscription_id);

        return true;

    } catch (Exception $e) {
        error_log('[PropertyTheme] Subscription Cancellation Error: ' . $e->getMessage());
        return new WP_Error('stripe_error', $e->getMessage());
    }
}

/**
 * Update subscription to different plan/price (upgrade/downgrade)
 * 
 * @param int $subscription_id WordPress subscription ID
 * @param int $new_plan_id New plan post ID
 * @param string $billing_cycle 'monthly' or 'yearly'
 * @param bool $prorate Whether to prorate the change
 * @return bool|WP_Error Success or error
 */
function property_theme_update_subscription_plan($subscription_id, $new_plan_id, $billing_cycle = 'monthly', $prorate = true) {
    global $wpdb;

    try {
        $subscription = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}user_subscriptions WHERE id = %d",
            $subscription_id
        ));

        if (!$subscription) {
            return new WP_Error('not_found', 'Subscription not found');
        }

        $plan = property_theme_get_plan($new_plan_id);
        if (!$plan) {
            return new WP_Error('invalid_plan', 'Invalid plan');
        }

        $new_price_id = property_theme_get_stripe_price_id($new_plan_id, $billing_cycle);
        if (!$new_price_id) {
            return new WP_Error('no_price', 'Price not configured for plan');
        }

        // Get current subscription items from Stripe
        $items_response = property_theme_stripe_api_call('GET', '/v1/subscription_items?subscription=' . $subscription->stripe_subscription_id, array(), STRIPE_SECRET_KEY);

        if (isset($items_response['error'])) {
            throw new Exception($items_response['error']['message']);
        }

        $current_item_id = $items_response['data'][0]['id'] ?? null;
        if (!$current_item_id) {
            throw new Exception('No subscription items found');
        }

        // Update subscription item with new price
        $update_response = property_theme_stripe_api_call('POST', '/v1/subscription_items/' . $current_item_id, array(
            'price' => $new_price_id,
            'proration_behavior' => $prorate ? 'create_prorations' : 'none',
        ), STRIPE_SECRET_KEY);

        if (isset($update_response['error'])) {
            throw new Exception($update_response['error']['message']);
        }

        // Update WordPress database
        $wpdb->update(
            $wpdb->prefix . 'user_subscriptions',
            array(
                'package_id' => $new_plan_id,
                'stripe_price_id' => $new_price_id,
            ),
            array('id' => $subscription_id)
        );

        property_theme_log_subscription_event($subscription_id, 'plan_updated', 'success', 'Plan updated', array(
            'old_plan_id' => $subscription->package_id,
            'new_plan_id' => $new_plan_id,
            'proration' => $prorate,
        ));

        do_action('property_theme_subscription_plan_updated', $subscription->user_id, $subscription_id, $new_plan_id);

        return true;

    } catch (Exception $e) {
        error_log('[PropertyTheme] Plan Update Error: ' . $e->getMessage());
        return new WP_Error('stripe_error', $e->getMessage());
    }
}
