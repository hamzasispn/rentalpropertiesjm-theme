<?php
/**
 * Stripe Subscription Migration
 * 
 * Migrates existing PaymentIntent-based subscriptions to Stripe Native Subscriptions.
 * 
 * This is a safe, non-destructive migration that:
 * 1. Validates all prerequisites are met
 * 2. Creates Stripe customers for users without them
 * 3. Creates native subscriptions in Stripe for existing PaymentIntent subs
 * 4. Stores Stripe subscription IDs in WordPress
 * 5. Maintains backward compatibility with payment method data
 */

require_once get_template_directory() . '/inc/subscription/stripe-subscriptions-native.php';

/**
 * Main migration function
 * 
 * Returns detailed migration report
 * 
 * @param array $options Migration options
 *   - 'batch_size' (int): Number of subscriptions to process per batch (default: 10)
 *   - 'dry_run' (bool): Simulate without making changes (default: false)
 *   - 'skip_errors' (bool): Continue on errors (default: true)
 * 
 * @return array Migration report
 */
function property_theme_migrate_to_native_subscriptions($options = array()) {
    global $wpdb;

    $defaults = array(
        'batch_size' => 10,
        'dry_run' => false,
        'skip_errors' => true,
    );

    $options = wp_parse_args($options, $defaults);
    
    $report = array(
        'start_time' => current_time('mysql'),
        'dry_run' => $options['dry_run'],
        'total_subscriptions' => 0,
        'successful' => 0,
        'failed' => 0,
        'skipped' => 0,
        'failed_subscriptions' => array(),
        'errors' => array(),
        'stats' => array(
            'customers_created' => 0,
            'native_subscriptions_created' => 0,
            'backup_created' => false,
        ),
    );

    try {
        // Pre-flight checks
        if (!defined('STRIPE_SECRET_KEY') || !STRIPE_SECRET_KEY) {
            $report['errors'][] = 'STRIPE_SECRET_KEY not configured';
            return $report;
        }

        // Create backup before migration
        if (!$options['dry_run']) {
            $backup_result = property_theme_create_migration_backup();
            if (!is_wp_error($backup_result)) {
                $report['stats']['backup_created'] = true;
            }
        }

        // Get all active subscriptions without stripe_subscription_id
        $active_subs = $wpdb->get_results(
            "SELECT id, user_id, package_id, auto_renew, expiry_date 
             FROM {$wpdb->prefix}user_subscriptions 
             WHERE status = 'active' 
             AND stripe_subscription_id IS NULL
             LIMIT " . intval($options['batch_size'])
        );

        $report['total_subscriptions'] = count($active_subs);

        if (empty($active_subs)) {
            $report['message'] = 'No subscriptions to migrate';
            return $report;
        }

        // Process each subscription
        foreach ($active_subs as $sub) {
            try {
                $migration_result = property_theme_migrate_single_subscription($sub, $options['dry_run']);
                
                if (is_wp_error($migration_result)) {
                    $report['failed']++;
                    $report['failed_subscriptions'][] = array(
                        'subscription_id' => $sub->id,
                        'user_id' => $sub->user_id,
                        'error' => $migration_result->get_error_message(),
                    );
                    
                    if (!$options['skip_errors']) {
                        throw new Exception($migration_result->get_error_message());
                    }
                } else {
                    $report['successful']++;
                    $report['stats']['customers_created'] += $migration_result['customers_created'];
                    $report['stats']['native_subscriptions_created'] += $migration_result['subscriptions_created'];
                }
            } catch (Exception $e) {
                $report['failed']++;
                $report['failed_subscriptions'][] = array(
                    'subscription_id' => $sub->id,
                    'user_id' => $sub->user_id,
                    'error' => $e->getMessage(),
                );

                if (!$options['skip_errors']) {
                    throw $e;
                }
            }
        }

        $report['end_time'] = current_time('mysql');

        return $report;

    } catch (Exception $e) {
        $report['errors'][] = $e->getMessage();
        $report['end_time'] = current_time('mysql');
        return $report;
    }
}

/**
 * Migrate a single subscription
 * 
 * @param object $subscription Subscription row from database
 * @param bool $dry_run Whether to perform actual migration
 * @return array|WP_Error
 */
function property_theme_migrate_single_subscription($subscription, $dry_run = false) {
    global $wpdb;

    $result = array(
        'customers_created' => 0,
        'subscriptions_created' => 0,
    );

    try {
        $user = get_userdata($subscription->user_id);
        if (!$user) {
            return new WP_Error('invalid_user', 'User not found: ' . $subscription->user_id);
        }

        $plan = property_theme_get_plan($subscription->package_id);
        if (!$plan) {
            return new WP_Error('invalid_plan', 'Plan not found: ' . $subscription->package_id);
        }

        // Get or create Stripe customer
        $stripe_customer_id = get_user_meta($subscription->user_id, '_stripe_customer_id', true);
        
        if (!$stripe_customer_id) {
            if ($dry_run) {
                $stripe_customer_id = 'cus_dry_run_' . $subscription->user_id;
                $result['customers_created']++;
            } else {
                $stripe_customer_id = property_theme_get_or_create_stripe_customer($subscription->user_id);
                if (!$stripe_customer_id) {
                    return new WP_Error('customer_create_failed', 'Failed to create Stripe customer');
                }
                $result['customers_created']++;
            }
        }

        // Get payment method stored in user meta (from old system)
        $payment_method = property_theme_get_payment_method($subscription->user_id);
        $stored_pm_id = $payment_method['stripe_payment_method_id'] ?? null;

        if (!$stored_pm_id) {
            // No payment method stored - use a default test PM for migration
            // In production, you might want to skip these or use customer's default PM
            return new WP_Error('no_payment_method', 'No payment method stored for user');
        }

        // Determine billing cycle from plan
        $billing_cycle = $plan['billing_cycle'] ?? 'monthly';
        
        // Get Stripe price ID
        $stripe_price_id = property_theme_get_stripe_price_id($subscription->package_id, $billing_cycle);
        if (!$stripe_price_id) {
            return new WP_Error('no_price', 'Stripe price not configured for plan: ' . $subscription->package_id);
        }

        if (!$dry_run) {
            // Create native subscription in Stripe
            $subscription_data = array(
                'customer' => $stripe_customer_id,
                'items[0][price]' => $stripe_price_id,
                'default_payment_method' => $stored_pm_id,
                'collection_method' => 'charge_automatically',
                'payment_behavior' => 'allow_incomplete',
                'off_session' => 'true',
                'expand[]' => 'latest_invoice.payment_intent',
                'metadata[user_id]' => $subscription->user_id,
                'metadata[plan_id]' => $subscription->package_id,
                'metadata[migration]' => 'true',
                'metadata[migrated_at]' => current_time('mysql'),
                'description' => $plan['name'] . ' Subscription - Migrated - ' . get_bloginfo('name'),
            );

            $stripe_subscription = property_theme_stripe_api_call('POST', '/v1/subscriptions', $subscription_data, STRIPE_SECRET_KEY);

            if (isset($stripe_subscription['error'])) {
                return new WP_Error('stripe_error', 'Stripe API error: ' . $stripe_subscription['error']['message']);
            }

            // Update WordPress subscription with Stripe data
            $wpdb->update(
                $wpdb->prefix . 'user_subscriptions',
                array(
                    'stripe_subscription_id' => $stripe_subscription['id'],
                    'stripe_customer_id' => $stripe_customer_id,
                    'stripe_price_id' => $stripe_price_id,
                    'status' => $stripe_subscription['status'],
                    'current_period_start' => date('Y-m-d H:i:s', $stripe_subscription['current_period_start']),
                    'current_period_end' => date('Y-m-d H:i:s', $stripe_subscription['current_period_end']),
                    'expiry_date' => date('Y-m-d H:i:s', $stripe_subscription['current_period_end']),
                    'auto_renew' => 1,
                ),
                array('id' => $subscription->id)
            );

            property_theme_log_subscription_event($subscription->id, 'migration_completed', 'success', 'Migrated to native subscription', array(
                'stripe_subscription_id' => $stripe_subscription['id'],
                'stripe_customer_id' => $stripe_customer_id,
                'old_expiry' => $subscription->expiry_date,
                'new_expiry' => date('Y-m-d H:i:s', $stripe_subscription['current_period_end']),
            ));

            $result['subscriptions_created']++;
        }

        return $result;

    } catch (Exception $e) {
        return new WP_Error('exception', $e->getMessage());
    }
}

/**
 * Create a backup of subscriptions before migration
 * 
 * @return bool|WP_Error Success or error
 */
function property_theme_create_migration_backup() {
    global $wpdb;

    try {
        $backup_table = $wpdb->prefix . 'user_subscriptions_backup_' . current_time('Ymd_His');

        $result = $wpdb->query(
            "CREATE TABLE IF NOT EXISTS $backup_table AS SELECT * FROM {$wpdb->prefix}user_subscriptions"
        );

        if ($result === false) {
            return new WP_Error('backup_failed', 'Failed to create backup table');
        }

        error_log('[PropertyTheme] Migration backup created: ' . $backup_table);
        return true;

    } catch (Exception $e) {
        return new WP_Error('backup_error', $e->getMessage());
    }
}

/**
 * Rollback migration by restoring from backup
 * 
 * @param string $backup_table Backup table name
 * @return bool|WP_Error
 */
function property_theme_rollback_migration($backup_table) {
    global $wpdb;

    try {
        // Verify backup table exists
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $backup_table
        ));

        if (!$table_exists) {
            return new WP_Error('backup_not_found', 'Backup table not found: ' . $backup_table);
        }

        // Clear current data
        $wpdb->query("DELETE FROM {$wpdb->prefix}user_subscriptions");

        // Restore from backup
        $result = $wpdb->query(
            "INSERT INTO {$wpdb->prefix}user_subscriptions SELECT * FROM $backup_table"
        );

        if ($result === false) {
            return new WP_Error('restore_failed', 'Failed to restore from backup');
        }

        error_log('[PropertyTheme] Migration rolled back from: ' . $backup_table);
        return true;

    } catch (Exception $e) {
        return new WP_Error('rollback_error', $e->getMessage());
    }
}

/**
 * Get migration status
 * 
 * @return array Status report
 */
function property_theme_get_migration_status() {
    global $wpdb;

    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}user_subscriptions WHERE status = 'active'");
    $migrated = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}user_subscriptions WHERE status = 'active' AND stripe_subscription_id IS NOT NULL");
    $not_migrated = $total - $migrated;

    return array(
        'total' => $total,
        'migrated' => $migrated,
        'pending' => $not_migrated,
        'progress' => $total > 0 ? round(($migrated / $total) * 100, 2) : 0,
    );
}

/**
 * REST API endpoint for migration
 */
add_action('rest_api_init', function() {
    register_rest_route('property-theme/v1', '/migrate-subscriptions', array(
        'methods' => 'POST',
        'callback' => 'property_theme_migrate_subscriptions_endpoint',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ));

    register_rest_route('property-theme/v1', '/migration-status', array(
        'methods' => 'GET',
        'callback' => 'property_theme_migration_status_endpoint',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ));
});

function property_theme_migrate_subscriptions_endpoint($request) {
    if (!current_user_can('manage_options')) {
        return new WP_Error('unauthorized', 'You do not have permission', array('status' => 403));
    }

    $batch_size = intval($request->get_param('batch_size')) ?: 10;
    $dry_run = $request->get_param('dry_run') === 'true' || $request->get_param('dry_run') === 1;

    $report = property_theme_migrate_to_native_subscriptions(array(
        'batch_size' => $batch_size,
        'dry_run' => $dry_run,
        'skip_errors' => true,
    ));

    return $report;
}

function property_theme_migration_status_endpoint($request) {
    if (!current_user_can('manage_options')) {
        return new WP_Error('unauthorized', 'You do not have permission', array('status' => 403));
    }

    $status = property_theme_get_migration_status();
    return $status;
}

/**
 * Admin notice to show migration status
 */
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $status = property_theme_get_migration_status();

    if ($status['pending'] > 0) {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>Property Theme Subscription Migration:</strong> ';
        echo 'You have ' . esc_html($status['pending']) . ' subscriptions pending migration to Stripe Native Subscriptions. ';
        echo 'Progress: ' . esc_html($status['progress']) . '% (' . esc_html($status['migrated']) . '/' . esc_html($status['total']) . '). ';
        echo '<a href="' . esc_url(add_query_arg('page', 'stripe-migration', admin_url('admin.php'))) . '">Start Migration</a></p>';
        echo '</div>';
    }
});
