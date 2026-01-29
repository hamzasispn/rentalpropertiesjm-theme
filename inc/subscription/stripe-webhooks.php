<?php
/**
 * Stripe Webhook Handler - Invoice & Subscription Events
 * 
 * Handles critical Stripe events:
 * - invoice.payment_succeeded: Mark subscription as active, update expiry date
 * - invoice.payment_failed: Mark subscription as past_due, notify user
 * - customer.subscription.deleted: Cancel subscription in DB
 * - customer.subscription.updated: Sync subscription changes
 */

require_once get_template_directory() . '/inc/subscription/stripe-subscriptions-native.php';
require_once get_template_directory() . '/inc/subscription/stripe-helper.php';

/**
 * Register webhook endpoint
 */
add_action('rest_api_init', function() {
    register_rest_route('property-theme/v1', '/stripe-webhook', array(
        'methods' => 'POST',
        'callback' => 'property_theme_stripe_webhook_handler',
        'permission_callback' => '__return_true', // Public endpoint - security via signature
    ));
});

/**
 * Main webhook handler
 * 
 * POST /wp-json/property-theme/v1/stripe-webhook
 */
function property_theme_stripe_webhook_handler($request) {
    $webhook_secret = defined('STRIPE_WEBHOOK_SECRET') ? STRIPE_WEBHOOK_SECRET : '';
    $payload = $request->get_body();
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

    if (!$webhook_secret) {
        error_log('[PropertyTheme] Stripe webhook secret not configured');
        return new WP_Error('webhook_error', 'Webhook not configured', array('status' => 403));
    }

    // Verify webhook signature
    if (!property_theme_verify_stripe_webhook($payload, $sig_header, $webhook_secret)) {
        error_log('[PropertyTheme] Stripe webhook signature verification failed');
        return new WP_Error('invalid_signature', 'Invalid signature', array('status' => 403));
    }

    $event = json_decode($payload, true);
    if (!$event) {
        error_log('[PropertyTheme] Stripe webhook JSON decode failed');
        return new WP_Error('invalid_payload', 'Invalid JSON payload', array('status' => 400));
    }

    // Prevent duplicate processing
    if (!property_theme_webhook_event_is_new($event['id'])) {
        // Event already processed
        return array('received' => true, 'status' => 'duplicate');
    }

    // Log webhook event
    error_log('[PropertyTheme] Stripe Webhook: ' . $event['type'] . ' | Event ID: ' . $event['id']);

    $event_object = $event['data']['object'];

    try {
        switch ($event['type']) {
            // Invoice payment succeeded - subscription is active and charged
            case 'invoice.payment_succeeded':
                property_theme_handle_invoice_payment_succeeded($event_object, $event['id']);
                break;

            // Invoice payment failed - subscription is past_due
            case 'invoice.payment_failed':
                property_theme_handle_invoice_payment_failed($event_object, $event['id']);
                break;

            // Subscription was updated
            case 'customer.subscription.updated':
                property_theme_handle_subscription_updated($event_object, $event['id']);
                break;

            // Subscription was deleted/canceled
            case 'customer.subscription.deleted':
                property_theme_handle_subscription_deleted($event_object, $event['id']);
                break;

            // Payment intent succeeded (for SCA/3D Secure)
            case 'payment_intent.succeeded':
                property_theme_handle_payment_intent_succeeded($event_object, $event['id']);
                break;

            // Payment intent failed
            case 'payment_intent.payment_failed':
                property_theme_handle_payment_intent_failed($event_object, $event['id']);
                break;

            default:
                // Log unhandled event for visibility
                error_log('[PropertyTheme] Unhandled webhook event: ' . $event['type']);
                break;
        }

        // Mark event as processed
        property_theme_mark_webhook_event_processed($event['id']);

        return array('received' => true, 'event_type' => $event['type']);

    } catch (Exception $e) {
        error_log('[PropertyTheme] Webhook handler exception: ' . $e->getMessage());
        return new WP_Error('webhook_error', 'Error processing webhook', array('status' => 500));
    }
}

/**
 * Check if webhook event is new (not yet processed)
 * 
 * @param string $stripe_event_id Stripe event ID
 * @return bool True if new, false if already processed
 */
function property_theme_webhook_event_is_new($stripe_event_id) {
    global $wpdb;
    
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}subscription_events WHERE stripe_event_id = %s LIMIT 1",
        $stripe_event_id
    ));

    return is_null($result);
}

/**
 * Mark webhook event as processed
 * 
 * @param string $stripe_event_id Stripe event ID
 */
function property_theme_mark_webhook_event_processed($stripe_event_id) {
    global $wpdb;
    
    // Create entry if doesn't exist
    $wpdb->insert(
        $wpdb->prefix . 'subscription_events',
        array(
            'stripe_event_id' => $stripe_event_id,
            'event_type' => 'webhook_received',
            'status' => 'processed',
            'created_at' => current_time('mysql'),
            'processed_at' => current_time('mysql'),
        )
    );
}

/**
 * invoice.payment_succeeded
 * 
 * Triggered when an invoice is successfully paid.
 * Updates subscription status to active and syncs expiry date from Stripe.
 */
function property_theme_handle_invoice_payment_succeeded($invoice, $event_id) {
    global $wpdb;

    $subscription_id = $invoice['subscription'] ?? null;
    if (!$subscription_id) {
        error_log('[PropertyTheme] invoice.payment_succeeded: No subscription ID in invoice');
        return;
    }

    // Get subscription from Stripe to sync data
    $stripe_subscription = property_theme_stripe_api_call('GET', '/v1/subscriptions/' . $subscription_id, array(), STRIPE_SECRET_KEY);

    if (isset($stripe_subscription['error'])) {
        error_log('[PropertyTheme] Failed to fetch subscription: ' . $stripe_subscription['error']['message']);
        return;
    }

    // Update WordPress subscription from Stripe data
    $subscription = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}user_subscriptions WHERE stripe_subscription_id = %s",
        $subscription_id
    ));

    if (!$subscription) {
        error_log('[PropertyTheme] invoice.payment_succeeded: Subscription not found in WordPress');
        return;
    }

    property_theme_update_subscription_from_stripe($subscription_id, $stripe_subscription);

    // Get user for email notification
    $user = get_userdata($subscription->user_id);
    $plan = property_theme_get_plan($subscription->package_id);

    // Send payment confirmation email
    if ($user) {
        $next_billing_date = date('F j, Y', $stripe_subscription['current_period_end']);
        $amount = property_theme_format_price($invoice['amount_paid'], strtoupper($invoice['currency']));

        wp_mail(
            $user->user_email,
            'Payment Confirmed - ' . get_bloginfo('name'),
            sprintf(
                "Your subscription payment has been processed successfully!\n\n" .
                "Payment Details:\n" .
                "- Plan: %s\n" .
                "- Amount: %s\n" .
                "- Invoice ID: %s\n" .
                "- Next Billing Date: %s\n\n" .
                "Your subscription is active and will continue to the next billing period.\n\n" .
                "View your invoice: %s\n\n" .
                "Best regards,\n%s Team",
                $plan['name'] ?? 'Premium',
                $amount,
                $invoice['number'] ?? $invoice['id'],
                $next_billing_date,
                $invoice['hosted_invoice_url'] ?? home_url('/dashboard/invoices/'),
                get_bloginfo('name')
            )
        );
    }

    property_theme_log_subscription_event($subscription->id, 'payment_succeeded', 'success', 'Invoice payment succeeded', array(
        'invoice_id' => $invoice['id'],
        'amount' => $invoice['amount_paid'],
        'stripe_event_id' => $event_id,
    ));

    do_action('property_theme_subscription_payment_succeeded', $subscription->user_id, $subscription->id, $invoice);
}

/**
 * invoice.payment_failed
 * 
 * Triggered when an invoice payment fails.
 * Marks subscription as past_due and notifies user to update payment method.
 */
function property_theme_handle_invoice_payment_failed($invoice, $event_id) {
    global $wpdb;

    $subscription_id = $invoice['subscription'] ?? null;
    if (!$subscription_id) {
        error_log('[PropertyTheme] invoice.payment_failed: No subscription ID in invoice');
        return;
    }

    $subscription = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}user_subscriptions WHERE stripe_subscription_id = %s",
        $subscription_id
    ));

    if (!$subscription) {
        error_log('[PropertyTheme] invoice.payment_failed: Subscription not found in WordPress');
        return;
    }

    // Update subscription status to past_due
    $wpdb->update(
        $wpdb->prefix . 'user_subscriptions',
        array(
            'status' => 'past_due',
            'last_webhook_event_at' => current_time('mysql'),
        ),
        array('id' => $subscription->id)
    );

    $user = get_userdata($subscription->user_id);
    $plan = property_theme_get_plan($subscription->package_id);
    $error_message = $invoice['last_payment_error']['message'] ?? 'Payment processing failed';

    // Send payment failure email with action items
    if ($user) {
        wp_mail(
            $user->user_email,
            'Payment Failed - Action Required - ' . get_bloginfo('name'),
            sprintf(
                "We were unable to process your subscription payment.\n\n" .
                "Failure Details:\n" .
                "- Plan: %s\n" .
                "- Reason: %s\n" .
                "- Invoice ID: %s\n\n" .
                "Your subscription status is now past due. Please update your payment method to avoid service interruption.\n\n" .
                "Update Payment Method: %s\n\n" .
                "Stripe will attempt to charge your account automatically up to 3 more times over the next few days.\n\n" .
                "If you need help, please contact us.\n\n" .
                "Best regards,\n%s Team",
                $plan['name'] ?? 'Premium',
                $error_message,
                $invoice['number'] ?? $invoice['id'],
                home_url('/dashboard/billing/'),
                get_bloginfo('name')
            )
        );
    }

    property_theme_log_subscription_event($subscription->id, 'payment_failed', 'failed', 'Invoice payment failed', array(
        'invoice_id' => $invoice['id'],
        'error' => $error_message,
        'stripe_event_id' => $event_id,
    ));

    do_action('property_theme_subscription_payment_failed', $subscription->user_id, $subscription->id, $invoice);
}

/**
 * customer.subscription.updated
 * 
 * Triggered when subscription is updated (pause, resume, plan change, etc).
 * Syncs the updated subscription data from Stripe to WordPress.
 */
function property_theme_handle_subscription_updated($stripe_subscription, $event_id) {
    global $wpdb;

    $subscription_id = $stripe_subscription['id'];
    
    $subscription = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}user_subscriptions WHERE stripe_subscription_id = %s",
        $subscription_id
    ));

    if (!$subscription) {
        error_log('[PropertyTheme] customer.subscription.updated: Subscription not found in WordPress');
        return;
    }

    // Sync from Stripe
    property_theme_update_subscription_from_stripe($subscription_id, $stripe_subscription);

    property_theme_log_subscription_event($subscription->id, 'subscription_updated', 'success', 'Subscription updated from Stripe', array(
        'status' => $stripe_subscription['status'],
        'stripe_event_id' => $event_id,
    ));

    do_action('property_theme_subscription_updated', $subscription->user_id, $subscription->id, $stripe_subscription);
}

/**
 * customer.subscription.deleted
 * 
 * Triggered when subscription is canceled/deleted in Stripe.
 * Marks subscription as canceled in WordPress and revokes access.
 */
function property_theme_handle_subscription_deleted($stripe_subscription, $event_id) {
    global $wpdb;

    $subscription_id = $stripe_subscription['id'];
    
    $subscription = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}user_subscriptions WHERE stripe_subscription_id = %s",
        $subscription_id
    ));

    if (!$subscription) {
        error_log('[PropertyTheme] customer.subscription.deleted: Subscription not found in WordPress');
        return;
    }

    // Mark as canceled
    $wpdb->update(
        $wpdb->prefix . 'user_subscriptions',
        array(
            'status' => 'canceled',
            'canceled_at' => date('Y-m-d H:i:s', $stripe_subscription['canceled_at'] ?? time()),
            'last_webhook_event_at' => current_time('mysql'),
        ),
        array('id' => $subscription->id)
    );

    $user = get_userdata($subscription->user_id);
    $plan = property_theme_get_plan($subscription->package_id);

    // Send cancellation confirmation email
    if ($user) {
        wp_mail(
            $user->user_email,
            'Subscription Canceled - ' . get_bloginfo('name'),
            sprintf(
                "Your subscription has been canceled.\n\n" .
                "Cancellation Details:\n" .
                "- Plan: %s\n" .
                "- Canceled On: %s\n\n" .
                "You will lose access to premium features at the end of your current billing period.\n\n" .
                "If you would like to reactivate your subscription, you can do so anytime: %s\n\n" .
                "We hope to see you again soon!\n\n" .
                "Best regards,\n%s Team",
                $plan['name'] ?? 'Premium',
                date('F j, Y \a\t g:i A', $stripe_subscription['canceled_at'] ?? time()),
                home_url('/plans/'),
                get_bloginfo('name')
            )
        );
    }

    property_theme_log_subscription_event($subscription->id, 'subscription_deleted', 'success', 'Subscription deleted from Stripe', array(
        'canceled_at' => date('Y-m-d H:i:s', $stripe_subscription['canceled_at'] ?? time()),
        'stripe_event_id' => $event_id,
    ));

    do_action('property_theme_subscription_deleted', $subscription->user_id, $subscription->id);
}

/**
 * payment_intent.succeeded
 * 
 * Triggered when payment intent succeeds (for off-session payments).
 * Used for retry logic and SCA/3D Secure completions.
 */
function property_theme_handle_payment_intent_succeeded($payment_intent, $event_id) {
    $invoice_id = $payment_intent['invoice'] ?? null;
    
    if ($invoice_id) {
        // Get full invoice data
        $invoice = property_theme_stripe_api_call('GET', '/v1/invoices/' . $invoice_id, array(), STRIPE_SECRET_KEY);
        
        if (!isset($invoice['error'])) {
            property_theme_handle_invoice_payment_succeeded($invoice, $event_id);
        }
    }
}

/**
 * payment_intent.payment_failed
 * 
 * Triggered when payment intent fails.
 * Used for handling decline reasons and retry logic.
 */
function property_theme_handle_payment_intent_failed($payment_intent, $event_id) {
    $invoice_id = $payment_intent['invoice'] ?? null;
    
    if ($invoice_id) {
        // Get full invoice data
        $invoice = property_theme_stripe_api_call('GET', '/v1/invoices/' . $invoice_id, array(), STRIPE_SECRET_KEY);
        
        if (!isset($invoice['error'])) {
            property_theme_handle_invoice_payment_failed($invoice, $event_id);
        }
    }
}

/**
 * Get webhook signature for testing
 * 
 * Useful for local development and testing
 */
function property_theme_compute_webhook_signature($payload, $webhook_secret) {
    $timestamp = time();
    $signed_content = $timestamp . '.' . $payload;
    $signature = hash_hmac('sha256', $signed_content, $webhook_secret);
    return 't=' . $timestamp . ',v1=' . $signature;
}

/**
 * Admin page to view webhook logs
 */
add_action('admin_menu', function() {
    if (current_user_can('manage_options')) {
        add_submenu_page(
            'woocommerce',
            'Stripe Webhooks',
            'Stripe Webhooks',
            'manage_options',
            'stripe-webhooks',
            'property_theme_webhook_logs_page'
        );
    }
});

function property_theme_webhook_logs_page() {
    global $wpdb;
    
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    echo '<div class="wrap">';
    echo '<h1>Stripe Webhook Events</h1>';

    // Get recent webhook events
    $events = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}subscription_events ORDER BY created_at DESC LIMIT 100"
    );

    echo '<table class="widefat striped">';
    echo '<thead><tr><th>Date</th><th>Event Type</th><th>Status</th><th>Metadata</th></tr></thead>';
    echo '<tbody>';

    foreach ($events as $event) {
        $metadata = json_decode($event->meta_data, true);
        echo '<tr>';
        echo '<td>' . esc_html($event->created_at) . '</td>';
        echo '<td>' . esc_html($event->event_type) . '</td>';
        echo '<td>' . esc_html($event->status) . '</td>';
        echo '<td><pre>' . esc_html(json_encode($metadata, JSON_PRETTY_PRINT)) . '</pre></td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}
