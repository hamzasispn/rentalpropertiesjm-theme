<?php
/**
 * Admin Page for Stripe Subscription Migration
 * 
 * Provides UI for managing subscription migration process
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu page
add_action('admin_menu', function() {
    if (current_user_can('manage_options')) {
        add_menu_page(
            'Stripe Migration',
            'Stripe Migration',
            'manage_options',
            'stripe-migration',
            'property_theme_render_migration_page',
            'dashicons-update',
            99
        );
    }
});

function property_theme_render_migration_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $status = property_theme_get_migration_status();
    ?>
    <div class="wrap">
        <h1>Stripe Subscription Migration</h1>
        
        <div class="migration-status-card">
            <h2>Migration Progress</h2>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo esc_attr($status['progress']); ?>%">
                    <?php echo esc_html($status['progress']); ?>%
                </div>
            </div>
            <p>
                <strong><?php echo esc_html($status['migrated']); ?>/<?php echo esc_html($status['total']); ?></strong> subscriptions migrated
            </p>
            <p>
                <strong><?php echo esc_html($status['pending']); ?></strong> subscriptions pending
            </p>
        </div>

        <?php if ($status['pending'] > 0) : ?>
            <div class="migration-controls">
                <h2>Run Migration</h2>
                
                <form id="migration-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="batch_size">Batch Size</label></th>
                            <td>
                                <input type="number" id="batch_size" name="batch_size" value="10" min="1" max="100">
                                <p class="description">Number of subscriptions to process per batch (recommended: 10-20)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dry_run">Dry Run</label></th>
                            <td>
                                <input type="checkbox" id="dry_run" name="dry_run" value="1">
                                <p class="description">Simulate migration without making changes</p>
                            </td>
                        </tr>
                    </table>

                    <p>
                        <button type="button" class="button button-primary" id="start-migration">Start Migration</button>
                        <button type="button" class="button" id="dry-run-migration">Run Dry Run</button>
                    </p>
                </form>

                <div id="migration-log" style="display: none; margin-top: 20px;">
                    <h3>Migration Log</h3>
                    <div id="log-content" style="border: 1px solid #ccc; padding: 10px; max-height: 500px; overflow-y: auto; background: #f5f5f5; font-family: monospace; white-space: pre-wrap;">
                    </div>
                </div>
            </div>

            <style>
                .migration-status-card {
                    background: white;
                    border: 1px solid #ccc;
                    border-radius: 5px;
                    padding: 15px;
                    margin: 20px 0;
                }

                .progress-bar {
                    width: 100%;
                    height: 30px;
                    background: #e0e0e0;
                    border-radius: 3px;
                    overflow: hidden;
                    margin: 10px 0;
                }

                .progress-fill {
                    height: 100%;
                    background: #46b450;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-weight: bold;
                    transition: width 0.3s ease;
                }

                .migration-controls {
                    background: white;
                    border: 1px solid #ccc;
                    border-radius: 5px;
                    padding: 15px;
                    margin: 20px 0;
                }

                .form-table th { text-align: left; }
                .form-table td { padding: 10px; }
            </style>

            <script>
                document.getElementById('start-migration').addEventListener('click', function() {
                    runMigration(false);
                });

                document.getElementById('dry-run-migration').addEventListener('click', function() {
                    runMigration(true);
                });

                function runMigration(dryRun) {
                    const batchSize = document.getElementById('batch_size').value;
                    const logDiv = document.getElementById('migration-log');
                    const logContent = document.getElementById('log-content');

                    logDiv.style.display = 'block';
                    logContent.textContent = 'Starting migration...\n';

                    const formData = new FormData();
                    formData.append('batch_size', batchSize);
                    formData.append('dry_run', dryRun ? '1' : '0');

                    fetch('<?php echo esc_url(rest_url('property-theme/v1/migrate-subscriptions')); ?>', {
                        method: 'POST',
                        headers: {
                            'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>',
                        },
                        body: formData,
                    })
                    .then(response => response.json())
                    .then(data => {
                        let logText = logContent.textContent;
                        logText += '\n\nMigration ' + (dryRun ? '(DRY RUN) ' : '') + 'Completed:\n';
                        logText += '- Processed: ' + data.total_subscriptions + '\n';
                        logText += '- Successful: ' + data.successful + '\n';
                        logText += '- Failed: ' + data.failed + '\n';
                        logText += '- Customers Created: ' + data.stats.customers_created + '\n';
                        logText += '- Native Subscriptions Created: ' + data.stats.native_subscriptions_created + '\n';

                        if (data.failed_subscriptions.length > 0) {
                            logText += '\n\nFailed Subscriptions:\n';
                            data.failed_subscriptions.forEach(function(sub) {
                                logText += '- Sub ID ' + sub.subscription_id + ' (User ' + sub.user_id + '): ' + sub.error + '\n';
                            });
                        }

                        logContent.textContent = logText;
                        location.reload();
                    })
                    .catch(error => {
                        logContent.textContent += '\n\nError: ' + error.message;
                    });
                }
            </script>
        <?php else : ?>
            <div class="notice notice-success is-dismissible">
                <p>All subscriptions have been successfully migrated to Stripe Native Subscriptions!</p>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
