<?php
/**
 * Subscription Management Functions with Error Logging
 */

function property_theme_log_subscription_event($subscription_id, $event_type, $status, $message = '', $meta_data = array()) {
    global $wpdb;
    
    $log_data = array(
        'subscription_id' => $subscription_id,
        'event_type' => $event_type,
        'status' => $status,
        'message' => $message,
        'meta_data' => wp_json_encode($meta_data),
        'created_at' => current_time('mysql'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'CLI'
    );
    
    // Create table if doesn't exist
    $table_name = $wpdb->prefix . 'subscription_logs';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            subscription_id BIGINT(20) UNSIGNED NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            status VARCHAR(20) NOT NULL,
            message LONGTEXT,
            meta_data LONGTEXT,
            created_at DATETIME NOT NULL,
            ip_address VARCHAR(45),
            KEY subscription_id (subscription_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    $wpdb->insert($table_name, $log_data);
    
    // Also log to PHP error log for debugging
    error_log(sprintf(
        "[Property Theme Subscription] Event: %s | Subscription ID: %d | Status: %s | Message: %s",
        $event_type,
        $subscription_id,
        $status,
        $message
    ));
    
    return $wpdb->insert_id;
}

function property_theme_track_renewal_attempt($subscription_id, $success, $error_message = '') {
    global $wpdb;
    
    $current_attempts = intval(get_post_meta($subscription_id, '_renewal_attempts', true)) ?: 0;
    $current_failures = intval(get_post_meta($subscription_id, '_renewal_failures', true)) ?: 0;
    
    if ($success) {
        // Reset on successful renewal
        delete_post_meta($subscription_id, '_renewal_attempts');
        delete_post_meta($subscription_id, '_renewal_failures');
        delete_post_meta($subscription_id, '_last_renewal_error');
    } else {
        // Increment failure counter
        update_post_meta($subscription_id, '_renewal_attempts', $current_attempts + 1);
        update_post_meta($subscription_id, '_renewal_failures', $current_failures + 1);
        update_post_meta($subscription_id, '_last_renewal_error', $error_message);
        update_post_meta($subscription_id, '_last_renewal_attempt', current_time('mysql'));
    }
}

// Get user's current active subscription
function property_theme_get_user_subscription($user_id) {
    global $wpdb;
    
    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}user_subscriptions 
         WHERE user_id = %d AND status = 'active' 
         ORDER BY updated_at DESC LIMIT 1",
        $user_id
    ));

    return $result;
}

function property_theme_get_subscription_count($package_id, $status = null) {
    global $wpdb;

    $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}user_subscriptions WHERE package_id = %d";

    if ($status) {
        $sql .= " AND status = %s";
        return (int) $wpdb->get_var($wpdb->prepare($sql, $package_id, $status));
    }

    return (int) $wpdb->get_var($wpdb->prepare($sql, $package_id));
}

// Get plan details
function property_theme_get_plan($package_id) {
    $post = get_post($package_id);
    $subscription_count = property_theme_get_subscription_count($post->ID, 'active');

    if (!$post || $post->post_type !== 'subscription_plan') {
        return null;
    }

    return array(
        'id' => $post->ID,
        'name' => $post->post_title,
        'price' => get_post_meta($post->ID, '_plan_price', true) ?: 0,
        'billing_cycle' => get_post_meta($post->ID, '_plan_billing_cycle', true) ?: 'monthly',
        'max_properties' => intval(get_post_meta($post->ID, '_plan_max_properties', true)) ?: 0,
        'featured_limit' => intval(get_post_meta($post->ID, '_plan_featured_limit', true)) ?: 0,
        'analytics' => get_post_meta($post->ID, '_plan_analytics', true) ?: 0,
        'features' => array_filter(array_map('trim', explode(',', get_post_meta($post->ID, '_plan_features', true) ?: ''))),
        'subscription_count' => $subscription_count
    );
}

// Check if user can publish a property
function property_theme_can_publish_property($user_id) {
    global $wpdb;
    
    $subscription = property_theme_get_user_subscription($user_id);
    if (!$subscription) {
        return false;
    }

    $plan = property_theme_get_plan($subscription->package_id);
    if (!$plan) {
        return false;
    }

    // Count user's current properties
    $property_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author = %d AND post_type = 'property' AND post_status = 'publish'",
        $user_id
    ));

    return $property_count < $plan['max_properties'];
}

// Check if user can feature a property
function property_theme_can_feature_property($user_id) {
    global $wpdb;
    
    $subscription = property_theme_get_user_subscription($user_id);
    if (!$subscription) {
        return false;
    }

    $plan = property_theme_get_plan($subscription->package_id);
    if (!$plan) {
        return false;
    }

    if ($plan['featured_limit'] === 0) {
        return false;
    }

    // Count featured properties this month
    $featured_this_month = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
         WHERE p.post_author = %d AND pm.meta_key = '_property_featured' 
         AND pm.meta_value = '1' 
         AND MONTH(p.post_date) = MONTH(NOW())
         AND YEAR(p.post_date) = YEAR(NOW())",
        $user_id
    ));

    return $featured_this_month < $plan['featured_limit'];
}

// Create subscription for user
function property_theme_create_subscription($user_id, $package_id, $stripe_subscription_id = null, $auto_renew = true) {
    global $wpdb;

    $plan = property_theme_get_plan($package_id);
    if (!$plan) {
        return new WP_Error('invalid_plan', 'Invalid subscription plan');
    }

    // Calculate expiration date based on billing cycle
    $expiry_date = null;
    if ($plan['billing_cycle'] === 'monthly') {
        $expiry_date = date('Y-m-d H:i:s', strtotime('+1 month'));
    } else {
        $expiry_date = date('Y-m-d H:i:s', strtotime('+1 year'));
    }

    // Cancel any existing subscriptions
    $wpdb->update(
        $wpdb->prefix . 'user_subscriptions',
        array('status' => 'cancelled'),
        array('user_id' => $user_id, 'status' => 'active')
    );

    // Create new subscription
    $result = $wpdb->insert(
        $wpdb->prefix . 'user_subscriptions',
        array(
            'user_id' => $user_id,
            'package_id' => $package_id,
            'stripe_subscription_id' => $stripe_subscription_id,
            'status' => 'active',
            'expiry_date' => $expiry_date,
            'auto_renew' => $auto_renew ? 1 : 0,
        )
    );

    if ($result === false) {
        return new WP_Error('db_error', 'Failed to create subscription');
    }

    property_theme_log_subscription_event($wpdb->insert_id, 'subscription_created', 'success', 'Subscription created', array(
        'user_id' => $user_id,
        'package_id' => $package_id,
        'auto_renew' => $auto_renew,
        'expiry_date' => $expiry_date
    ));

    do_action('property_theme_subscription_created', $user_id, $package_id, $wpdb->insert_id);

    return array(
        'id' => $wpdb->insert_id,
        'user_id' => $user_id,
        'package_id' => $package_id,
        'status' => 'active',
        'expiry_date' => $expiry_date,
        'auto_renew' => $auto_renew,
    );
}

// Update payment method for user
function property_theme_update_payment_method($user_id, $payment_method_data) {
    $user_subscription = property_theme_get_user_subscription($user_id);
    if (!$user_subscription) {
        return new WP_Error('no_subscription', 'No active subscription found');
    }

    // Store payment method in user meta
    update_user_meta($user_id, '_payment_method', array(
        'card_last_four' => sanitize_text_field($payment_method_data['card_last_four'] ?? ''),
        'card_brand' => sanitize_text_field($payment_method_data['card_brand'] ?? ''),
        'exp_month' => intval($payment_method_data['exp_month'] ?? 0),
        'exp_year' => intval($payment_method_data['exp_year'] ?? 0),
        'billing_name' => sanitize_text_field($payment_method_data['billing_name'] ?? ''),
        'billing_email' => sanitize_email($payment_method_data['billing_email'] ?? ''),
    ));

    do_action('property_theme_payment_method_updated', $user_id);

    return array(
        'success' => true,
        'message' => 'Payment method updated successfully'
    );
}

// Get payment method for user
function property_theme_get_payment_method($user_id) {
    $payment_method = get_user_meta($user_id, '_payment_method', true);
    return $payment_method ?: array();
}

// Toggle auto-renew for user's subscription
function property_theme_toggle_auto_renew($user_id, $enable = true) {
    global $wpdb;
    
    $subscription = property_theme_get_user_subscription($user_id);
    if (!$subscription) {
        return new WP_Error('no_subscription', 'No active subscription found');
    }

    $wpdb->update(
        $wpdb->prefix . 'user_subscriptions',
        array('auto_renew' => $enable ? 1 : 0),
        array('id' => $subscription->id)
    );

    do_action('property_theme_auto_renew_toggled', $user_id, $enable);

    return array(
        'success' => true,
        'auto_renew' => $enable,
        'message' => $enable ? 'Auto-renewal enabled' : 'Auto-renewal disabled'
    );
}

// Send expiration warning email
function property_theme_send_expiration_warning_email($subscription_id) {
    global $wpdb;
    
    $subscription = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}user_subscriptions WHERE id = %d",
        $subscription_id
    ));
    
    if (!$subscription) {
        property_theme_log_subscription_event($subscription_id, 'warning_email', 'failed', 'Subscription not found');
        return false;
    }
    
    $user = get_user_by('ID', $subscription->user_id);
    if (!$user) {
        property_theme_log_subscription_event($subscription_id, 'warning_email', 'failed', 'User not found');
        return false;
    }
    
    $plan = property_theme_get_plan($subscription->package_id);
    
    $expiry_date = date('F j, Y', strtotime($subscription->expiry_date));
    
    $subject = 'Your subscription is expiring soon - ' . get_bloginfo('name');
    
    $message = sprintf(
        "Hello %s,\n\n" .
        "Your %s subscription will expire on %s.\n\n" .
        "Your subscription details:\n" .
        "- Plan: %s\n" .
        "- Expiry Date: %s\n" .
        "- Auto-Renewal: %s\n\n" .
        "If auto-renewal is enabled, your subscription will be renewed automatically on the expiry date.\n" .
        "If auto-renewal is disabled, your subscription will be cancelled after the expiry date.\n\n" .
        "You can manage your subscription settings at: %s\n\n" .
        "Best regards,\n%s",
        $user->display_name,
        $plan['name'] ?? 'Premium',
        $expiry_date,
        $plan['name'] ?? 'N/A',
        $expiry_date,
        $subscription->auto_renew ? 'Yes' : 'No',
        home_url('/dashboard/subscription/'),
        get_bloginfo('name')
    );
    
    $email_sent = wp_mail(
        $user->user_email,
        $subject,
        $message,
        array('Content-Type: text/plain; charset=UTF-8')
    );
    
    if ($email_sent) {
        update_post_meta($subscription_id, '_expiration_warning_sent', 1);
        property_theme_log_subscription_event($subscription_id, 'warning_email', 'success', 'Warning email sent to ' . $user->user_email);
    } else {
        property_theme_log_subscription_event($subscription_id, 'warning_email', 'failed', 'Failed to send warning email to ' . $user->user_email);
    }
    
    return $email_sent;
}

function property_theme_auto_renew_subscription($subscription_id) {
    global $wpdb;
    
    try {
        $subscription = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}user_subscriptions WHERE id = %d",
            $subscription_id
        ));
        
        if (!$subscription) {
            property_theme_log_subscription_event($subscription_id, 'renewal', 'failed', 'Subscription record not found');
            return false;
        }
        
        if (!$subscription->auto_renew) {
            property_theme_log_subscription_event($subscription_id, 'renewal', 'skipped', 'Auto-renewal is disabled');
            return false;
        }
        
        $plan = property_theme_get_plan($subscription->package_id);
        if (!$plan) {
            $error_msg = 'Invalid plan ID: ' . $subscription->package_id;
            property_theme_log_subscription_event($subscription_id, 'renewal', 'failed', $error_msg);
            property_theme_track_renewal_attempt($subscription_id, false, $error_msg);
            return false;
        }
        
        // Calculate new expiry date based on billing cycle
        $new_expiry_date = null;
        if ($plan['billing_cycle'] === 'monthly') {
            $new_expiry_date = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($subscription->expiry_date)));
        } else {
            $new_expiry_date = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($subscription->expiry_date)));
        }
        
        // Update subscription
        $result = $wpdb->update(
            $wpdb->prefix . 'user_subscriptions',
            array(
                'expiry_date' => $new_expiry_date,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $subscription_id)
        );
        
        if ($result === false) {
            $error_msg = 'Database update failed: ' . $wpdb->last_error;
            property_theme_log_subscription_event($subscription_id, 'renewal', 'failed', $error_msg, array(
                'old_expiry' => $subscription->expiry_date,
                'new_expiry' => $new_expiry_date
            ));
            property_theme_track_renewal_attempt($subscription_id, false, $error_msg);
            return false;
        }
        
        // Send renewal confirmation email
        $email_sent = property_theme_send_renewal_email($subscription->user_id, $subscription->package_id, $new_expiry_date);
        
        if ($email_sent) {
            property_theme_log_subscription_event($subscription_id, 'renewal', 'success', 'Subscription renewed successfully', array(
                'old_expiry' => $subscription->expiry_date,
                'new_expiry' => $new_expiry_date,
                'plan' => $plan['name']
            ));
            property_theme_track_renewal_attempt($subscription_id, true);
        } else {
            property_theme_log_subscription_event($subscription_id, 'renewal', 'success_no_email', 'Subscription renewed but email failed', array(
                'new_expiry' => $new_expiry_date
            ));
        }
        
        do_action('property_theme_subscription_renewed', $subscription->user_id, $subscription_id, $new_expiry_date);
        
        return true;
        
    } catch (Exception $e) {
        property_theme_log_subscription_event($subscription_id, 'renewal', 'error', 'Exception: ' . $e->getMessage());
        property_theme_track_renewal_attempt($subscription_id, false, $e->getMessage());
        return false;
    }
}

// Send renewal email
function property_theme_send_renewal_email($user_id, $package_id, $new_expiry_date) {
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        return false;
    }
    
    $plan = property_theme_get_plan($package_id);
    
    $expiry_date = date('F j, Y', strtotime($new_expiry_date));
    
    $subject = 'Subscription Renewed - ' . get_bloginfo('name');
    
    $message = sprintf(
        "Hello %s,\n\n" .
        "Your subscription has been successfully renewed!\n\n" .
        "Renewal details:\n" .
        "- Plan: %s\n" .
        "- New Expiry Date: %s\n\n" .
        "Your subscription continues uninterrupted. Thank you for being a valued member!\n\n" .
        "Manage your subscription: %s\n\n" .
        "Best regards,\n%s",
        $user->display_name,
        $plan['name'] ?? 'Premium',
        $expiry_date,
        home_url('/dashboard/subscription/'),
        get_bloginfo('name')
    );
    
    return wp_mail(
        $user->user_email,
        $subject,
        $message,
        array('Content-Type: text/plain; charset=UTF-8')
    );
}

// Send cancellation email
function property_theme_send_cancellation_email($user_id, $package_id, $expiry_date) {
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        return false;
    }
    
    $plan = property_theme_get_plan($package_id);
    
    $expired_date = date('F j, Y', strtotime($expiry_date));
    
    $subject = 'Your subscription has been cancelled - ' . get_bloginfo('name');
    
    $message = sprintf(
        "Hello %s,\n\n" .
        "Your subscription has been cancelled as the renewal period has expired.\n\n" .
        "Subscription details:\n" .
        "- Plan: %s\n" .
        "- Expiry Date: %s\n" .
        "- Auto-Renewal: No\n\n" .
        "To renew your subscription or upgrade to a different plan, visit: %s\n\n" .
        "We hope to see you again!\n\n" .
        "Best regards,\n%s",
        $user->display_name,
        $plan['name'] ?? 'Premium',
        $expired_date,
        home_url('/plans/'),
        get_bloginfo('name')
    );
    
    return wp_mail(
        $user->user_email,
        $subject,
        $message,
        array('Content-Type: text/plain; charset=UTF-8')
    );
}

// Get subscription usage stats
function property_theme_get_subscription_stats($user_id) {
    global $wpdb;

    $subscription = property_theme_get_user_subscription($user_id);
    
    if (!$subscription) {
        return array(
            'subscription' => null,
            'plan' => array(
                'id' => 0,
                'name' => 'No Active Subscription',
                'max_properties' => 0,
                'featured_limit' => 0,
            ),
            'published_properties' => 0,
            'properties_remaining' => 0,
            'featured_this_month' => 0,
            'featured_remaining' => 0,
            'total_views' => 0,
            'expiry_date' => null,
            'days_remaining' => 0,
            'auto_renew' => false,
            'payment_method' => array(),
        );
    }

    $plan = property_theme_get_plan($subscription->package_id);
    
    if (!$plan) {
        $plan = array(
            'id' => 0,
            'name' => 'Invalid Plan',
            'max_properties' => 0,
            'featured_limit' => 0,
        );
    }

    // Count published properties
    $published_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} 
         WHERE post_author = %d AND post_type = 'property' AND post_status = 'publish'",
        $user_id
    ));

    // Count featured properties this month
    $featured_this_month = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
         WHERE p.post_author = %d AND pm.meta_key = '_property_featured' 
         AND pm.meta_value = '1' 
         AND MONTH(p.post_date) = MONTH(NOW())
         AND YEAR(p.post_date) = YEAR(NOW())",
        $user_id
    ));

    // Get total views
    $total_views = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}property_analytics 
         WHERE event_type = 'page_view' 
         AND property_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_author = %d)",
        $user_id
    ));

    $days_remaining = 0;
    if ($subscription && $subscription->expiry_date) {
        $days_remaining = ceil((strtotime($subscription->expiry_date) - time()) / 86400);
    }

    $payment_method = property_theme_get_payment_method($user_id);

    return array(
        'subscription' => $subscription,
        'plan' => $plan,
        'published_properties' => intval($published_count) ?: 0,
        'properties_remaining' => max(0, $plan['max_properties'] - intval($published_count)),
        'featured_this_month' => intval($featured_this_month) ?: 0,
        'featured_remaining' => max(0, $plan['featured_limit'] - intval($featured_this_month)),
        'total_views' => intval($total_views) ?: 0,
        'expiry_date' => $subscription ? $subscription->expiry_date : null,
        'days_remaining' => $days_remaining,
        'auto_renew' => $subscription ? (bool)$subscription->auto_renew : false,
        'payment_method' => $payment_method,
    );
}

function property_theme_process_all_subscriptions() {
    global $wpdb;
    
    $stats = array(
        'warning_emails' => 0,
        'auto_renewed' => 0,
        'renewal_failed' => 0,
        'cancelled' => 0,
        'errors' => array()
    );
    
    try {
        // 1. Send warning emails for subscriptions expiring in 7 days
        $upcoming_expiry = $wpdb->get_results(
            "SELECT id, user_id, package_id, expiry_date FROM {$wpdb->prefix}user_subscriptions 
             WHERE status = 'active' 
             AND expiry_date > NOW() 
             AND expiry_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)
             AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} 
                WHERE post_id = {$wpdb->prefix}user_subscriptions.id 
                AND meta_key = '_expiration_warning_sent'
             )"
        );
        
        foreach ($upcoming_expiry as $subscription) {
            if (property_theme_send_expiration_warning_email($subscription->id)) {
                $stats['warning_emails']++;
            }
        }
        
        // 2. Auto-renew subscriptions with auto_renew = 1 that have expired
        $expired_with_renewal = $wpdb->get_results(
            "SELECT id, user_id, package_id, expiry_date FROM {$wpdb->prefix}user_subscriptions 
             WHERE status = 'active' 
             AND auto_renew = 1 
             AND expiry_date <= NOW()"
        );
        
        foreach ($expired_with_renewal as $subscription) {
            $renewal_result = property_theme_auto_renew_subscription($subscription->id);
            
            if ($renewal_result) {
                $stats['auto_renewed']++;
            } else {
                $renewal_attempts = intval(get_post_meta($subscription->id, '_renewal_failures', true)) ?: 0;
                $stats['renewal_failed']++;
                
                // If renewal failed 3+ times over 3+ days, cancel the subscription
                if ($renewal_attempts >= 3) {
                    $last_attempt = get_post_meta($subscription->id, '_last_renewal_attempt', true);
                    $days_since_attempt = ceil((time() - strtotime($last_attempt)) / 86400);
                    
                    if ($days_since_attempt >= 3) {
                        property_theme_log_subscription_event($subscription->id, 'renewal_cancellation', 'success', 'Cancelled after ' . $renewal_attempts . ' failed renewal attempts');
                        
                        property_theme_send_cancellation_email($subscription->user_id, $subscription->package_id, $subscription->expiry_date);
                        
                        $wpdb->update(
                            $wpdb->prefix . 'user_subscriptions',
                            array(
                                'status' => 'cancelled',
                                'auto_renew' => 0,
                                'updated_at' => current_time('mysql')
                            ),
                            array('id' => $subscription->id)
                        );
                        
                        $stats['cancelled']++;
                        do_action('property_theme_subscription_auto_cancelled_renewal_failed', $subscription->user_id, $subscription->id);
                    }
                }
            }
        }
        
        // 3. Cancel subscriptions with auto_renew = 0 that have expired
        $expired_without_renewal = $wpdb->get_results(
            "SELECT id, user_id, package_id, expiry_date FROM {$wpdb->prefix}user_subscriptions 
             WHERE status = 'active' 
             AND auto_renew = 0 
             AND expiry_date <= NOW()"
        );
        
        foreach ($expired_without_renewal as $subscription) {
            try {
                property_theme_send_cancellation_email($subscription->user_id, $subscription->package_id, $subscription->expiry_date);
                
                $wpdb->update(
                    $wpdb->prefix . 'user_subscriptions',
                    array(
                        'status' => 'cancelled',
                        'auto_renew' => 0,
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => $subscription->id)
                );
                
                property_theme_log_subscription_event($subscription->id, 'expiration_cancellation', 'success', 'Subscription cancelled due to expiration');
                
                $stats['cancelled']++;
                do_action('property_theme_subscription_expired', $subscription->user_id, $subscription->id);
            } catch (Exception $e) {
                $error_msg = 'Error cancelling subscription: ' . $e->getMessage();
                property_theme_log_subscription_event($subscription->id, 'expiration_cancellation', 'failed', $error_msg);
                $stats['errors'][] = $error_msg;
            }
        }
        
    } catch (Exception $e) {
        $error_msg = 'Error in subscription processing: ' . $e->getMessage();
        error_log('[Property Theme Subscription Processing Error] ' . $error_msg);
        $stats['errors'][] = $error_msg;
    }
    
    // Log the complete processing run
    error_log(sprintf(
        '[Property Theme Subscription Processing] Warning Emails: %d | Auto Renewed: %d | Failed Renewals: %d | Cancelled: %d | Errors: %d',
        $stats['warning_emails'],
        $stats['auto_renewed'],
        $stats['renewal_failed'],
        $stats['cancelled'],
        count($stats['errors'])
    ));
    
    return $stats;
}

add_action('init', function() {
    error_log('[v0] Subscription system checking cron...');
    error_log('[v0] Scheduling subscription processing cron...');
    wp_schedule_event(time(), 'every_minute', 'property_theme_subscription_processing');
});

// Hook to process subscriptions
add_action('property_theme_subscription_processing', 'property_theme_process_all_subscriptions');

add_action('rest_api_init', function() {
    register_rest_route('property-theme/v1', '/subscriptions/process', array(
        'methods' => 'POST',
        'callback' => function() {
            error_log('[v0] Manual subscription processing triggered via REST API');
            $result = property_theme_process_all_subscriptions();
            error_log('[v0] Manual subscription processing result: ' . wp_json_encode($result));
            return rest_ensure_response($result);
        },
        'permission_callback' => '__return_true' // For testing only, remove in production
    ));
});

add_action('wp_ajax_trigger_subscription_processing', function() {
    if (!current_user_can('manage_options')) {
        error_log('[v0] Unauthorized subscription processing attempt');
        wp_send_json_error('Unauthorized');
    }
    
    error_log('[v0] Admin triggered subscription processing');
    $result = property_theme_process_all_subscriptions();
    error_log('[v0] Admin subscription processing result: ' . wp_json_encode($result));
    wp_send_json_success($result);
});

add_action('admin_notices', function() {
    if (current_user_can('manage_options')) {
        ?>
        <div style="margin: 20px 0; padding: 15px; background: #f0f0f0; border: 1px solid #ccc;">
            <h3>Subscription Processing</h3>
            <p>Last cron event scheduled: <?php echo wp_next_scheduled('property_theme_subscription_processing') ? date('Y-m-d H:i:s', wp_next_scheduled('property_theme_subscription_processing')) : 'Not scheduled'; ?></p>
            <button id="trigger-subscription-processing" class="button button-primary" style="padding: 8px 15px;">Trigger Processing Now</button>
            <span id="processing-status" style="margin-left: 10px;"></span>
        </div>
        <script>
        document.getElementById('trigger-subscription-processing').addEventListener('click', function() {
            var btn = this;
            btn.disabled = true;
            btn.textContent = 'Processing...';
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=trigger_subscription_processing&nonce=<?php echo wp_create_nonce('trigger_subscription'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                console.log('[v0] Subscription processing response:', data);
                document.getElementById('processing-status').innerHTML = '<strong style="color: green;">✓ Processing completed!</strong>';
                btn.disabled = false;
                btn.textContent = 'Trigger Processing Now';
                if (data.data) {
                    alert('Results:\n- Warning Emails: ' + data.data.warning_emails + 
                          '\n- Auto Renewed: ' + data.data.auto_renewed + 
                          '\n- Cancelled: ' + data.data.cancelled);
                }
            })
            .catch(error => {
                console.error('[v0] Error:', error);
                document.getElementById('processing-status').innerHTML = '<strong style="color: red;">✗ Error occurred!</strong>';
                btn.disabled = false;
                btn.textContent = 'Trigger Processing Now';
            });
        });
        </script>
        <?php
    }
});

// Function to view subscription logs
function property_theme_get_subscription_logs($subscription_id, $limit = 50) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'subscription_logs';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name 
         WHERE subscription_id = %d 
         ORDER BY created_at DESC 
         LIMIT %d",
        $subscription_id,
        $limit
    ));
}

// Function to get renewal failure info
function property_theme_get_renewal_failure_info($subscription_id) {
    return array(
        'attempts' => intval(get_post_meta($subscription_id, '_renewal_attempts', true)) ?: 0,
        'failures' => intval(get_post_meta($subscription_id, '_renewal_failures', true)) ?: 0,
        'last_error' => get_post_meta($subscription_id, '_last_renewal_error', true) ?: '',
        'last_attempt' => get_post_meta($subscription_id, '_last_renewal_attempt', true) ?: ''
    );
}

