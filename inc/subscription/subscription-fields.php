<?php
/**
 * Subscription Custom Fields Registration and Meta Box Setup
 */


require_once get_template_directory() . '/inc/subscription/stripe-products-setup.php';

function property_theme_register_meta_boxes()
{
    // Subscription Plan Details Meta Box
    add_meta_box(
        'subscription_plan_details',
        'Plan Details',
        'subscription_theme_subscription_details_callback',
        'subscription_plan',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'property_theme_register_meta_boxes');

function subscription_theme_subscription_details_callback($post)
{
    wp_nonce_field('subscription_nonce', 'subscription_nonce');

    $price = get_post_meta($post->ID, '_plan_price', true);
    $billing_cycle = get_post_meta($post->ID, '_plan_billing_cycle', true);
    $monthly_discount = get_post_meta($post->ID, '_plan_monthly_discount', true);
    $yearly_discount = get_post_meta($post->ID, '_plan_yearly_discount', true);
    $max_properties = get_post_meta($post->ID, '_plan_max_properties', true);
    $featured_limit = get_post_meta($post->ID, '_plan_featured_limit', true);
    $analytics = get_post_meta($post->ID, '_plan_analytics', true);
    $features = get_post_meta($post->ID, '_plan_features', true);

    $stripe_product_id = get_post_meta($post->ID, 'stripe_product_id', true);
    $stripe_price_id = get_post_meta($post->ID, 'stripe_price_id', true);
    $stripe_yearly_price_id = get_post_meta($post->ID, 'stripe_yearly_price_id', true);
    ?>
    <div style="padding: 20px;">
        <!-- Added Stripe sync status display with visual indicators -->
        <div
            style="margin-bottom: 20px; padding: 15px; background-color: #f0f0f0; border-left: 4px solid #0073aa; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px;">
                <strong>Stripe Sync Status:</strong><br>
                Product ID:
                <?php echo $stripe_product_id ? '<span style="color: green;">✓ ' . esc_html($stripe_product_id) . '</span>' : '<span style="color: orange;">Pending sync...</span>'; ?><br>
                Monthly Price ID:
                <?php echo $stripe_price_id ? '<span style="color: green;">✓ ' . esc_html($stripe_price_id) . '</span>' : '<span style="color: orange;">Pending sync...</span>'; ?><br>
                Yearly Price ID:
                <?php echo $stripe_yearly_price_id ? '<span style="color: green;">✓ ' . esc_html($stripe_yearly_price_id) . '</span>' : '<span style="color: orange;">Pending sync...</span>'; ?>
            </p>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #666;">
                <em>Stripe IDs are automatically generated when you save this plan.</em>
            </p>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="plan_price" style="display: block; margin-bottom: 5px; font-weight: bold;">Price ($)</label>
            <input type="number" id="plan_price" name="plan_price" value="<?php echo esc_attr($price); ?>" step="0.01"
                style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="plan_billing_cycle" style="display: block; margin-bottom: 5px; font-weight: bold;">Billing
                Cycle</label>
            <select id="plan_billing_cycle" name="plan_billing_cycle"
                style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="monthly" <?php selected($billing_cycle, 'monthly'); ?>>Monthly</option>
                <option value="yearly" <?php selected($billing_cycle, 'yearly'); ?>>Yearly</option>
            </select>
        </div>

        <!-- Added discount fields for monthly and yearly billing cycles -->
        <div style="margin-bottom: 15px;">
            <label for="plan_monthly_discount" style="display: block; margin-bottom: 5px; font-weight: bold;">Monthly
                Discount (%)</label>
            <input type="number" id="plan_monthly_discount" name="plan_monthly_discount"
                value="<?php echo esc_attr($monthly_discount); ?>" min="0" max="100" step="0.01"
                style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <small style="color: #666;">Leave blank or 0 for no discount</small>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="plan_yearly_discount" style="display: block; margin-bottom: 5px; font-weight: bold;">Yearly Discount
                (%)</label>
            <input type="number" id="plan_yearly_discount" name="plan_yearly_discount"
                value="<?php echo esc_attr($yearly_discount); ?>" min="0" max="100" step="0.01"
                style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <small style="color: #666;">Leave blank or 0 for no discount</small>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="plan_max_properties" style="display: block; margin-bottom: 5px; font-weight: bold;">Max
                Properties</label>
            <input type="number" id="plan_max_properties" name="plan_max_properties"
                value="<?php echo esc_attr($max_properties); ?>" min="1"
                style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="plan_featured_limit" style="display: block; margin-bottom: 5px; font-weight: bold;">Featured
                Listings per Month</label>
            <input type="number" id="plan_featured_limit" name="plan_featured_limit"
                value="<?php echo esc_attr($featured_limit); ?>" min="0"
                style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: flex; align-items: center; font-weight: bold; cursor: pointer;">
                <input type="checkbox" id="plan_analytics" name="plan_analytics" value="1" <?php checked($analytics, 1); ?>
                    style="margin-right: 8px;">
                Includes Analytics
            </label>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="plan_features" style="display: block; margin-bottom: 5px; font-weight: bold;">Features
                (comma-separated)</label>
            <textarea id="plan_features" name="plan_features" rows="4"
                style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"><?php echo esc_textarea($features); ?></textarea>
        </div>
    </div>
    <?php
}

function subscription_theme_save_meta_boxes($post_id, $post)
{
    // Prevent autosave and verify nonce
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!isset($_POST['subscription_nonce']) || !wp_verify_nonce($_POST['subscription_nonce'], 'subscription_nonce')) {
        return;
    }

    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save all plan fields
    update_post_meta($post_id, '_plan_price', floatval($_POST['plan_price'] ?? 0));
    update_post_meta($post_id, '_plan_billing_cycle', sanitize_text_field($_POST['plan_billing_cycle'] ?? 'monthly'));
    update_post_meta($post_id, '_plan_monthly_discount', floatval($_POST['plan_monthly_discount'] ?? 0));
    update_post_meta($post_id, '_plan_yearly_discount', floatval($_POST['plan_yearly_discount'] ?? 0));
    update_post_meta($post_id, '_plan_max_properties', intval($_POST['plan_max_properties'] ?? 1));
    update_post_meta($post_id, '_plan_featured_limit', intval($_POST['plan_featured_limit'] ?? 0));
    update_post_meta($post_id, '_plan_analytics', isset($_POST['plan_analytics']) ? 1 : 0);
    update_post_meta($post_id, '_plan_features', sanitize_textarea_field($_POST['plan_features'] ?? ''));

    property_theme_sync_stripe_product($post->ID);
    $billing_cycle = get_post_meta($post->ID, '_plan_billing_cycle', true);

    property_theme_sync_stripe_price($post->ID, $billing_cycle);
}
add_action('save_post_subscription_plan', 'subscription_theme_save_meta_boxes', 10, 2);
?>