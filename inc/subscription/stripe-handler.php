<?php
/**
 * Stripe Payment Processing - Local Testable Version via cURL
 */

require_once get_template_directory() . '/inc/subscription/subscriptions.php';
/**
 * REST API endpoint registration
 */
add_action('rest_api_init', function() {
    register_rest_route('property-theme/v1', '/process-payment', array(
        'methods' => 'POST',
        'callback' => 'property_theme_process_payment',
        'permission_callback' => function() {
            return is_user_logged_in();
        },
    ));

    register_rest_route('property-theme/v1', '/stripe-webhook', array(
        'methods' => 'POST',
        'callback' => 'property_theme_stripe_webhook',
        'permission_callback' => '__return_true', // Public endpoint
    ));
});

/**
 * Process payment
 */
function property_theme_process_payment($request) {
    $user_id = get_current_user_id();
    $plan_id = intval($request->get_param('plan_id'));
    $payment_method_id = sanitize_text_field($request->get_param('payment_method_id'));
    $billing_details = $request->get_param('billing_details');

    if (!$user_id || !$plan_id || !$payment_method_id) {
        return new WP_Error('missing_params', 'Missing required parameters', array('status' => 400));
    }

    $plan = property_theme_get_plan($plan_id);
    if (!$plan) {
        return new WP_Error('invalid_plan', 'Invalid plan', array('status' => 400));
    }

    $user = get_userdata($user_id);

    try {
        // Create or retrieve Stripe customer
        $stripe_customer_id = property_theme_get_or_create_stripe_customer_old($user_id);
        if (!$stripe_customer_id) {
            throw new Exception('Failed to create Stripe customer');
        }

        property_theme_stripe_api_call_old('POST', '/v1/payment_methods/' . $payment_method_id . '/attach', array(
            'customer' => $stripe_customer_id,
        ), STRIPE_SECRET_KEY);

        property_theme_stripe_api_call_old('POST', '/v1/customers/' . $stripe_customer_id, array(
            'invoice_settings[default_payment_method]' => $payment_method_id,
        ), STRIPE_SECRET_KEY);

        // Create Stripe Payment Intent
        $amount = intval($plan['price'] * 100); // in cents
        $response = property_theme_stripe_api_call_old('POST', '/v1/payment_intents', array(
            'amount' => $amount,
            'currency' => 'usd',
            'customer' => $stripe_customer_id,
            'payment_method' => $payment_method_id,
            'confirm' => 'true',
            'off_session' => 'true',
            'metadata[user_id]' => $user_id,
            'metadata[plan_id]' => $plan_id,
            'description' => $plan['name'] . ' Subscription - ' . get_bloginfo('name'),
        ), STRIPE_SECRET_KEY);

        if (isset($response['error'])) {
            throw new Exception($response['error']['message']);
        }

        if (!in_array($response['status'], ['succeeded', 'requires_action'])) {
            throw new Exception('Payment processing failed: ' . $response['status']);
        }

        $payment_method_response = property_theme_stripe_api_call_old('GET', '/v1/payment_methods/' . $payment_method_id, array(), STRIPE_SECRET_KEY);
        
        if (isset($payment_method_response['card'])) {
            $card = $payment_method_response['card'];
            property_theme_update_payment_method($user_id, array(
                'card_last_four' => $card['last4'] ?? '',
                'card_brand' => $card['brand'] ?? '',
                'exp_month' => $card['exp_month'] ?? 0,
                'exp_year' => $card['exp_year'] ?? 0,
                'billing_name' => $billing_details['name'] ?? '',
                'billing_email' => $user->user_email,
                'stripe_payment_method_id' => $payment_method_id,
            ));
        }

        // Save subscription to database
        property_theme_create_subscription($user_id, $plan_id, $response['id'], true);

        // Store Stripe customer ID
        update_user_meta($user_id, '_stripe_customer_id', $stripe_customer_id);

        // Send confirmation email
        wp_mail(
            $user->user_email,
            'Subscription Confirmed - ' . get_bloginfo('name'),
            sprintf(
                "Thank you for subscribing to %s!\n\nYour subscription is now active. Log in to your dashboard to get started.\n\n%s\n\nBest regards,\n%s Team",
                $plan['name'],
                home_url('/dashboard'),
                get_bloginfo('name')
            )
        );

        return array(
            'success' => true,
            'subscription_id' => $response['id'],
            'message' => 'Subscription created successfully',
            'redirect_url' => home_url('/dashboard'),
        );

    } catch (Exception $e) {
        error_log('[PropertyTheme] Payment Error: ' . $e->getMessage());
        return new WP_Error('payment_failed', $e->getMessage(), array('status' => 402));
    }
}

/**
 * Helper: cURL call to Stripe
 */
function property_theme_stripe_api_call_old($method, $endpoint, $params, $api_key) {
    $url = 'https://api.stripe.com' . $endpoint;
    
    $ch = curl_init();
    
    if ($method === 'GET' && !empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $api_key . ':');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        error_log('[PropertyTheme] Stripe cURL Error: ' . $curl_error);
        return array('error' => array('message' => 'Connection error: ' . $curl_error));
    }

    $decoded = json_decode($response, true);
    return $decoded ?: array('error' => array('message' => 'Invalid response from Stripe'));
}

/**
 * Create or retrieve Stripe customer
 */
function property_theme_get_or_create_stripe_customer_old($user_id) {
    $stripe_customer_id = get_user_meta($user_id, '_stripe_customer_id', true);
    if ($stripe_customer_id) return $stripe_customer_id;

    $user = get_userdata($user_id);

    $response = property_theme_stripe_api_call_old('POST', '/v1/customers', array(
        'email' => $user->user_email,
        'name' => $user->display_name,
        'metadata[user_id]' => $user_id,
        'metadata[site_url]' => home_url(),
    ), STRIPE_SECRET_KEY);

    if (isset($response['id'])) {
        update_user_meta($user_id, '_stripe_customer_id', $response['id']);
        return $response['id'];
    }

    return false;
}

/**
 * Stripe Webhook Handler
 */
function property_theme_stripe_webhook($request) {
    $webhook_secret = STRIPE_WEBHOOK_SECRET;
    $payload = $request->get_body();
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

    if (!$webhook_secret || !$sig_header) {
        return new WP_Error('invalid_setup', 'Webhook not properly configured', array('status' => 403));
    }

    // Verify webhook signature (simplified for testing)
    $compute_sig = hash_hmac('sha256', $payload, $webhook_secret);
    $sig_parts = explode(',', $sig_header);
    $provided_sig = '';
    foreach ($sig_parts as $part) {
        if (strpos($part, 'v1=') === 0) $provided_sig = substr($part, 3);
    }

    if (!hash_equals($compute_sig, $provided_sig)) {
        return new WP_Error('invalid_signature', 'Invalid signature', array('status' => 403));
    }

    $event = json_decode($payload, true);
    if (!$event) return new WP_Error('invalid_payload', 'Invalid payload', array('status' => 400));

    switch ($event['type']) {
        case 'payment_intent.succeeded':
            property_theme_update_subscription_from_intent($event['data']['object']);
            break;
        case 'payment_intent.payment_failed':
            property_theme_handle_payment_failure($event['data']['object']);
            break;
    }

    return array('received' => true);
}

/**
 * Update subscription after successful payment
 */
function property_theme_update_subscription_from_intent($intent) {
    global $wpdb;
    $user_id = $intent['metadata']['user_id'] ?? null;
    $plan_id = $intent['metadata']['plan_id'] ?? null;

    if ($user_id && $plan_id) {
        $wpdb->update(
            $wpdb->prefix . 'user_subscriptions',
            array(
                'status' => 'active',
                'updated_at' => current_time('mysql'),
            ),
            array(
                'user_id' => intval($user_id),
                'plan_id' => intval($plan_id),
            )
        );
    }
}

/**
 * Handle failed payment
 */
function property_theme_handle_payment_failure($intent) {
    $user_id = $intent['metadata']['user_id'] ?? null;
    if ($user_id) {
        $user = get_userdata($user_id);
        if ($user) {
            wp_mail(
                $user->user_email,
                'Payment Failed - ' . get_bloginfo('name'),
                "Your recent payment failed. Update your payment method here: " . home_url('/dashboard/billing')
            );
        }
    }
}
?>
