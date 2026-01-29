<?php
/**
 * Stripe Products & Prices Setup
 * 
 * Handles creation and synchronization of Stripe Products and Prices
 * from WordPress subscription plans.
 */

require_once get_template_directory() . '/inc/subscription/subscriptions.php';

/**
 * Create or update a Stripe Product for a subscription plan
 * 
 * @param int $plan_id WordPress post ID of the subscription plan
 * @return array|WP_Error Stripe product data or error
 */
function property_theme_sync_stripe_product($plan_id) {
    $plan = property_theme_get_plan($plan_id);
    
    if (!$plan) {
        return new WP_Error('invalid_plan', 'Plan not found');
    }

    // Check if product already exists
    $existing_product_id = get_post_meta($plan_id, '_stripe_product_id', true);
    
    $product_data = array(
        'name' => $plan['name'],
        'description' => get_post($plan_id)->post_content ?: 'Subscription plan',
        'metadata[plan_id]' => $plan_id,
        'metadata[plan_name]' => $plan['name'],
    );

    try {
        if ($existing_product_id) {
            // Update existing product
            $response = property_theme_stripe_api_call('POST', '/v1/products/' . $existing_product_id, $product_data, STRIPE_SECRET_KEY);
            
            if (isset($response['error'])) {
                throw new Exception('Stripe API Error: ' . $response['error']['message']);
            }

            property_theme_log_subscription_event(0, 'product_updated', 'success', 'Stripe product updated: ' . $existing_product_id, array('plan_id' => $plan_id));
            return $response;
        } else {
            // Create new product
            $response = property_theme_stripe_api_call('POST', '/v1/products', $product_data, STRIPE_SECRET_KEY);
            
            if (isset($response['error'])) {
                throw new Exception('Stripe API Error: ' . $response['error']['message']);
            }

            // Save product ID to plan
            update_post_meta($plan_id, '_stripe_product_id', $response['id']);

            property_theme_log_subscription_event(0, 'product_created', 'success', 'Stripe product created: ' . $response['id'], array('plan_id' => $plan_id));
            return $response;
        }
    } catch (Exception $e) {
        error_log('[PropertyTheme] Stripe Product Sync Error: ' . $e->getMessage());
        return new WP_Error('stripe_error', $e->getMessage());
    }
}

/**
 * Create or update Stripe Prices for a subscription plan
 * 
 * @param int $plan_id WordPress post ID of the subscription plan
 * @param string $billing_cycle 'monthly' or 'yearly'
 * @return array|WP_Error Stripe price data or error
 */
function property_theme_sync_stripe_price($plan_id, $billing_cycle = 'monthly') {
    $plan = property_theme_get_plan($plan_id);
    
    if (!$plan) {
        return new WP_Error('invalid_plan', 'Plan not found');
    }

    // Get or create the product first
    $product_id = get_post_meta($plan_id, '_stripe_product_id', true);
    if (!$product_id) {
        $product_response = property_theme_sync_stripe_product($plan_id);
        if (is_wp_error($product_response)) {
            return $product_response;
        }
        $product_id = $product_response['id'];
    }

    // Get base price from plan
    $base_price = floatval($plan['price']);
    
    if ($billing_cycle === 'yearly') {
        $interval = 'year';
        $meta_key = 'stripe_yearly_price_id';
        $discount_key = '_plan_yearly_discount';
    } else {
        $interval = 'month';
        $meta_key = 'stripe_price_id';
        $discount_key = '_plan_monthly_discount';
    }

    // Get discount percentage and apply it
    $discount_percent = floatval(get_post_meta($plan_id, $discount_key, true) ?: 0);
    if ($discount_percent > 0 && $discount_percent <= 100) {
        $base_price = $base_price * (1 - ($discount_percent / 100));
    }

    // Convert to cents for Stripe
    $price = intval($base_price * 100);

    // Check if price already exists
    $existing_price_id = get_post_meta($plan_id, $meta_key, true);

    $price_data = array(
        'product' => $product_id,
        'unit_amount' => $price,
        'currency' => 'usd',
        'recurring[interval]' => $interval,
        'recurring[usage_type]' => 'licensed',
        'metadata[plan_id]' => $plan_id,
        'metadata[billing_cycle]' => $billing_cycle,
        'metadata[discount_percent]' => $discount_percent,
    );

    try {
        if ($existing_price_id) {
            // Note: Prices cannot be updated in Stripe, only created or archived
            // Check if the existing price is still active
            $price_check = property_theme_stripe_api_call('GET', '/v1/prices/' . $existing_price_id, array(), STRIPE_SECRET_KEY);
            
            if (!isset($price_check['error']) && $price_check['active'] === true) {
                // Price exists and is active
                property_theme_log_subscription_event(0, 'price_verified', 'success', 'Stripe price verified: ' . $existing_price_id, array('plan_id' => $plan_id, 'billing_cycle' => $billing_cycle, 'price' => $price, 'discount' => $discount_percent));
                return $price_check;
            }
        }

        // Create new price
        $response = property_theme_stripe_api_call('POST', '/v1/prices', $price_data, STRIPE_SECRET_KEY);
        
        if (isset($response['error'])) {
            throw new Exception('Stripe API Error: ' . $response['error']['message']);
        }

        // Save price ID to plan - using consistent meta key naming
        update_post_meta($plan_id, $meta_key, $response['id']);

        property_theme_log_subscription_event(0, 'price_created', 'success', 'Stripe price created: ' . $response['id'], array('plan_id' => $plan_id, 'billing_cycle' => $billing_cycle, 'price' => $price, 'discount' => $discount_percent));
        return $response;

    } catch (Exception $e) {
        error_log('[PropertyTheme] Stripe Price Sync Error: ' . $e->getMessage());
        return new WP_Error('stripe_error', $e->getMessage());
    }
}

/**
 * Sync all subscription plans with Stripe
 * 
 * @return array Summary of sync results
 */
function property_theme_sync_all_stripe_products() {
    $args = array(
        'post_type' => 'subscription_plan',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    );

    $plans = get_posts($args);
    $results = array(
        'total' => count($plans),
        'successful' => 0,
        'failed' => 0,
        'products' => array(),
        'prices' => array(),
    );

    foreach ($plans as $plan) {
        // Create/update product
        $product_result = property_theme_sync_stripe_product($plan->ID);
        if (is_wp_error($product_result)) {
            $results['failed']++;
            $results['products'][$plan->ID] = array(
                'status' => 'failed',
                'error' => $product_result->get_error_message(),
            );
        } else {
            $results['successful']++;
            $results['products'][$plan->ID] = array(
                'status' => 'success',
                'stripe_product_id' => $product_result['id'],
            );
        }

        // Create prices for both monthly and yearly
        foreach (array('monthly', 'yearly') as $cycle) {
            $price_result = property_theme_sync_stripe_price($plan->ID, $cycle);
            if (is_wp_error($price_result)) {
                $results['prices'][$plan->ID][$cycle] = array(
                    'status' => 'failed',
                    'error' => $price_result->get_error_message(),
                );
            } else {
                $results['prices'][$plan->ID][$cycle] = array(
                    'status' => 'success',
                    'stripe_price_id' => $price_result['id'],
                );
            }
        }
    }

    return $results;
}

/**
 * REST API endpoint to manually trigger Stripe sync
 */
add_action('rest_api_init', function() {
    register_rest_route('property-theme/v1', '/sync-stripe-products', array(
        'methods' => 'POST',
        'callback' => function() {
            if (!current_user_can('manage_options')) {
                return new WP_Error('unauthorized', 'You do not have permission', array('status' => 403));
            }

            $results = property_theme_sync_all_stripe_products();
            return array(
                'success' => $results['failed'] === 0,
                'results' => $results,
            );
        },
        'permission_callback' => '__return_true',
    ));
});

/**
 * Get Stripe Price ID for a plan and billing cycle
 * 
 * @param int $plan_id Plan post ID
 * @param string $billing_cycle 'monthly' or 'yearly'
 * @return string|false Price ID or false if not found
 */
function property_theme_get_stripe_price_id($plan_id, $billing_cycle = 'monthly') {
    $meta_key = ($billing_cycle === 'yearly') ? 'stripe_yearly_price_id' : 'stripe_price_id';
    return get_post_meta($plan_id, $meta_key, true) ?: false;
}

/**
 * Get Stripe Product ID for a plan
 * 
 * @param int $plan_id Plan post ID
 * @return string|false Product ID or false if not found
 */
function property_theme_get_stripe_product_id($plan_id) {
    return get_post_meta($plan_id, '_stripe_product_id', true) ?: false;
}

/**
 * List all Stripe Products for site
 * 
 * @return array|WP_Error
 */
function property_theme_list_stripe_products() {
    try {
        $response = property_theme_stripe_api_call('GET', '/v1/products?limit=100', array(), STRIPE_SECRET_KEY);
        
        if (isset($response['error'])) {
            throw new Exception($response['error']['message']);
        }

        return $response['data'] ?? array();
    } catch (Exception $e) {
        error_log('[PropertyTheme] Stripe List Products Error: ' . $e->getMessage());
        return new WP_Error('stripe_error', $e->getMessage());
    }
}

/**
 * List all Stripe Prices for a product
 * 
 * @param string $product_id Stripe product ID
 * @return array|WP_Error
 */
function property_theme_list_stripe_prices($product_id) {
    try {
        $response = property_theme_stripe_api_call('GET', '/v1/prices?product=' . $product_id . '&limit=100', array(), STRIPE_SECRET_KEY);
        
        if (isset($response['error'])) {
            throw new Exception($response['error']['message']);
        }

        return $response['data'] ?? array();
    } catch (Exception $e) {
        error_log('[PropertyTheme] Stripe List Prices Error: ' . $e->getMessage());
        return new WP_Error('stripe_error', $e->getMessage());
    }
}

/**
 * Admin notice to run sync
 */
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $plans = get_posts(array(
        'post_type' => 'subscription_plan',
        'posts_per_page' => 1,
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_stripe_product_id',
                'compare' => 'NOT EXISTS',
            ),
        ),
    ));

    if (!empty($plans)) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>Property Theme:</strong> You have subscription plans not synced with Stripe. ';
        echo '<a href="' . wp_nonce_url(add_query_arg('action', 'sync_stripe_products'), 'sync_stripe_products') . '">Sync Now</a></p>';
        echo '</div>';
    }
});

/**
 * Handle admin action for syncing
 */
add_action('init', function() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'sync_stripe_products') {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'sync_stripe_products')) {
            wp_die('Security check failed');
        }

        $results = property_theme_sync_all_stripe_products();
        
        $message = sprintf(
            'Synced %d/%d plans. %d failed.',
            $results['successful'],
            $results['total'],
            $results['failed']
        );

        wp_redirect(add_query_arg('message', urlencode($message), admin_url('edit.php?post_type=subscription_plan')));
        exit;
    }
});
