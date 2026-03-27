<?php
/**
 * Subscription Custom Fields Registration and Meta Box Setup
 * Updated: Added 'days' billing cycle with custom day count input
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

    $price             = get_post_meta($post->ID, '_plan_price', true);
    $billing_cycle     = get_post_meta($post->ID, '_plan_billing_cycle', true);
    $billing_days      = get_post_meta($post->ID, '_plan_billing_days', true); // NEW: custom day count
    $monthly_discount  = get_post_meta($post->ID, '_plan_monthly_discount', true);
    $yearly_discount   = get_post_meta($post->ID, '_plan_yearly_discount', true);
    $max_properties    = get_post_meta($post->ID, '_plan_max_properties', true);
    $featured_limit    = get_post_meta($post->ID, '_plan_featured_limit', true);
    $analytics         = get_post_meta($post->ID, '_plan_analytics', true);
    $features          = get_post_meta($post->ID, '_plan_features', true);

    // NEW: Media limits
    $photo_limit       = get_post_meta($post->ID, '_plan_photo_limit', true);
    $video_limit       = get_post_meta($post->ID, '_plan_video_limit', true);

    // NEW: Feature toggles
    $enable_whatsapp   = get_post_meta($post->ID, '_plan_enable_whatsapp', true);
    $enable_google_map = get_post_meta($post->ID, '_plan_enable_google_map', true);

    $stripe_product_id      = get_post_meta($post->ID, 'stripe_product_id', true);
    $stripe_price_id        = get_post_meta($post->ID, 'stripe_price_id', true);
    $stripe_yearly_price_id = get_post_meta($post->ID, 'stripe_yearly_price_id', true);
    $stripe_days_price_id   = get_post_meta($post->ID, 'stripe_days_price_id', true);
    ?>
    <div style="padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">

        <!-- ─── Stripe Sync Status ─────────────────────────────────────── -->
        <div style="margin-bottom: 20px; padding: 15px; background:#f0f0f0; border-left:4px solid #0073aa; border-radius:4px;">
            <p style="margin:0; font-size:14px;">
                <strong>Stripe Sync Status:</strong><br>
                Product ID:
                <?php echo $stripe_product_id
                    ? '<span style="color:green;">✓ ' . esc_html($stripe_product_id) . '</span>'
                    : '<span style="color:orange;">Pending sync…</span>'; ?><br>
                Monthly Price ID:
                <?php echo $stripe_price_id
                    ? '<span style="color:green;">✓ ' . esc_html($stripe_price_id) . '</span>'
                    : '<span style="color:orange;">Pending sync…</span>'; ?><br>
                Yearly Price ID:
                <?php echo $stripe_yearly_price_id
                    ? '<span style="color:green;">✓ ' . esc_html($stripe_yearly_price_id) . '</span>'
                    : '<span style="color:orange;">Pending sync…</span>'; ?><br>
                Custom Days Price ID:
                <?php
                $current_cycle = get_post_meta($post->ID, '_plan_billing_cycle', true);
                if ($current_cycle === 'days') {
                    echo $stripe_days_price_id
                        ? '<span style="color:green;">✓ ' . esc_html($stripe_days_price_id) . '</span>'
                        : '<span style="color:orange;">Pending sync…</span>';
                } else {
                    echo '<span style="color:#aaa;">N/A (not a days plan)</span>';
                }
                ?>
            </p>
            <p style="margin:10px 0 0; font-size:12px; color:#666;">
                <em>Stripe IDs are automatically generated when you save this plan.</em>
            </p>
        </div>

        <!-- ─── Price ──────────────────────────────────────────────────── -->
        <div style="margin-bottom:15px;">
            <label for="plan_price" style="display:block; margin-bottom:5px; font-weight:bold;">Price ($)</label>
            <input type="number" id="plan_price" name="plan_price"
                   value="<?php echo esc_attr($price); ?>" step="0.01"
                   style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
        </div>

        <!-- ─── Billing Cycle ───────────────────────────────────────────── -->
        <div style="margin-bottom:15px;">
            <label for="plan_billing_cycle" style="display:block; margin-bottom:5px; font-weight:bold;">Billing Cycle</label>
            <select id="plan_billing_cycle" name="plan_billing_cycle"
                    style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;"
                    onchange="toggleDaysField(this.value)">
                <option value="monthly" <?php selected($billing_cycle, 'monthly'); ?>>Monthly</option>
                <option value="yearly"  <?php selected($billing_cycle, 'yearly');  ?>>Yearly</option>
                <option value="days"    <?php selected($billing_cycle, 'days');    ?>>Custom Days</option>
            </select>
        </div>

        <!-- ─── Custom Days (shown only when "days" selected) ──────────── -->
        <div id="billing_days_field"
             style="margin-bottom:15px; <?php echo ($billing_cycle !== 'days') ? 'display:none;' : ''; ?>
                    padding:15px; background:#fff8e1; border:1px solid #ffc107; border-radius:6px;">
            <label for="plan_billing_days" style="display:block; margin-bottom:5px; font-weight:bold; color:#856404;">
                📅 Number of Days
            </label>
            <input type="number" id="plan_billing_days" name="plan_billing_days"
                   value="<?php echo esc_attr($billing_days ?: 14); ?>"
                   min="1" max="365" step="1"
                   style="width:100%; padding:8px; border:1px solid #ffc107; border-radius:4px; font-size:16px; font-weight:600;">
            <small style="color:#856404; display:block; margin-top:6px;">
                Set the exact number of days this subscription lasts (e.g. 7, 10, 14, 30).
            </small>
        </div>

        <!-- ─── Discount Fields ─────────────────────────────────────────── -->
        <div style="margin-bottom:15px;">
            <label for="plan_monthly_discount" style="display:block; margin-bottom:5px; font-weight:bold;">Monthly Discount (%)</label>
            <input type="number" id="plan_monthly_discount" name="plan_monthly_discount"
                   value="<?php echo esc_attr($monthly_discount); ?>" min="0" max="100" step="0.01"
                   style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
            <small style="color:#666;">Leave blank or 0 for no discount</small>
        </div>

        <div style="margin-bottom:15px;">
            <label for="plan_yearly_discount" style="display:block; margin-bottom:5px; font-weight:bold;">Yearly Discount (%)</label>
            <input type="number" id="plan_yearly_discount" name="plan_yearly_discount"
                   value="<?php echo esc_attr($yearly_discount); ?>" min="0" max="100" step="0.01"
                   style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
            <small style="color:#666;">Leave blank or 0 for no discount</small>
        </div>

        <!-- ─── Property Limits ─────────────────────────────────────────── -->
        <div style="margin-bottom:15px;">
            <label for="plan_max_properties" style="display:block; margin-bottom:5px; font-weight:bold;">Max Properties</label>
            <input type="number" id="plan_max_properties" name="plan_max_properties"
                   value="<?php echo esc_attr($max_properties); ?>" min="1"
                   style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
        </div>

        <div style="margin-bottom:15px;">
            <label for="plan_featured_limit" style="display:block; margin-bottom:5px; font-weight:bold;">Featured Listings per Month</label>
            <input type="number" id="plan_featured_limit" name="plan_featured_limit"
                   value="<?php echo esc_attr($featured_limit); ?>" min="0"
                   style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
        </div>

        <!-- ─── NEW: Media Upload Limits ────────────────────────────────── -->
        <div style="margin-bottom:20px; padding:15px; background:#f0fdf4; border:1px solid #86efac; border-radius:6px;">
            <h4 style="margin:0 0 12px; color:#166534; font-size:14px; font-weight:700; text-transform:uppercase; letter-spacing:.05em;">
                📸 Media Upload Limits
            </h4>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div>
                    <label for="plan_photo_limit" style="display:block; margin-bottom:5px; font-weight:600; font-size:13px;">
                        Photos Limit (per property)
                    </label>
                    <input type="number" id="plan_photo_limit" name="plan_photo_limit"
                           value="<?php echo esc_attr($photo_limit ?: 10); ?>" min="1" max="100"
                           style="width:100%; padding:8px; border:1px solid #86efac; border-radius:4px; font-size:15px;">
                    <small style="color:#166534;">Max photos a user can upload per listing</small>
                </div>
                <div>
                    <label for="plan_video_limit" style="display:block; margin-bottom:5px; font-weight:600; font-size:13px;">
                        Videos Limit (per property)
                    </label>
                    <input type="number" id="plan_video_limit" name="plan_video_limit"
                           value="<?php echo esc_attr($video_limit ?: 2); ?>" min="0" max="20"
                           style="width:100%; padding:8px; border:1px solid #86efac; border-radius:4px; font-size:15px;">
                    <small style="color:#166534;">Max videos a user can upload per listing (0 = disabled)</small>
                </div>
            </div>
        </div>

        <!-- ─── NEW: Feature Toggles ────────────────────────────────────── -->
        <div style="margin-bottom:20px; padding:15px; background:#eff6ff; border:1px solid #93c5fd; border-radius:6px;">
            <h4 style="margin:0 0 12px; color:#1e40af; font-size:14px; font-weight:700; text-transform:uppercase; letter-spacing:.05em;">
                ⚙️ Feature Toggles
            </h4>

            <label style="display:flex; align-items:center; gap:10px; margin-bottom:12px; cursor:pointer; padding:10px; background:white; border-radius:6px; border:1px solid #bfdbfe;">
                <input type="checkbox" id="plan_enable_whatsapp" name="plan_enable_whatsapp" value="1"
                       <?php checked($enable_whatsapp, 1); ?>
                       style="width:18px; height:18px; cursor:pointer;">
                <div>
                    <span style="font-weight:600; font-size:14px; color:#1e40af;">💬 Enable WhatsApp Contact Button</span>
                    <p style="margin:2px 0 0; font-size:12px; color:#64748b;">Allow users on this plan to show WhatsApp contact on their listings</p>
                </div>
            </label>

            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; padding:10px; background:white; border-radius:6px; border:1px solid #bfdbfe;">
                <input type="checkbox" id="plan_enable_google_map" name="plan_enable_google_map" value="1"
                       <?php checked($enable_google_map, 1); ?>
                       style="width:18px; height:18px; cursor:pointer;">
                <div>
                    <span style="font-weight:600; font-size:14px; color:#1e40af;">📍 Enable Google Map / Location Pin</span>
                    <p style="margin:2px 0 0; font-size:12px; color:#64748b;">Allow users on this plan to add a Google Maps address and display a map on their listing</p>
                </div>
            </label>
        </div>

        <!-- ─── Analytics ───────────────────────────────────────────────── -->
        <div style="margin-bottom:15px;">
            <label style="display:flex; align-items:center; font-weight:bold; cursor:pointer;">
                <input type="checkbox" id="plan_analytics" name="plan_analytics" value="1"
                       <?php checked($analytics, 1); ?> style="margin-right:8px;">
                Includes Analytics
            </label>
        </div>

        <!-- ─── Features ─────────────────────────────────────────────────── -->
        <div style="margin-bottom:15px;">
            <label for="plan_features" style="display:block; margin-bottom:5px; font-weight:bold;">
                Features (comma-separated)
            </label>
            <textarea id="plan_features" name="plan_features" rows="4"
                      style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;"><?php echo esc_textarea($features); ?></textarea>
        </div>
    </div>

    <!-- Toggle script for days field -->
    <script>
    function toggleDaysField(value) {
        var field = document.getElementById('billing_days_field');
        field.style.display = (value === 'days') ? 'block' : 'none';
    }
    // Run on page load in case "days" was already saved
    document.addEventListener('DOMContentLoaded', function () {
        var select = document.getElementById('plan_billing_cycle');
        if (select) toggleDaysField(select.value);
    });
    </script>
    <?php
}

function subscription_theme_save_meta_boxes($post_id, $post)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if (!isset($_POST['subscription_nonce']) || !wp_verify_nonce($_POST['subscription_nonce'], 'subscription_nonce')) return;

    if (!current_user_can('edit_post', $post_id)) return;

    // Save core plan fields
    update_post_meta($post_id, '_plan_price',           floatval($_POST['plan_price']           ?? 0));
    update_post_meta($post_id, '_plan_billing_cycle',   sanitize_text_field($_POST['plan_billing_cycle'] ?? 'monthly'));
    update_post_meta($post_id, '_plan_billing_days',    intval($_POST['plan_billing_days']       ?? 14));
    update_post_meta($post_id, '_plan_monthly_discount',floatval($_POST['plan_monthly_discount'] ?? 0));
    update_post_meta($post_id, '_plan_yearly_discount', floatval($_POST['plan_yearly_discount']  ?? 0));
    update_post_meta($post_id, '_plan_max_properties',  intval($_POST['plan_max_properties']     ?? 1));
    update_post_meta($post_id, '_plan_featured_limit',  intval($_POST['plan_featured_limit']     ?? 0));
    update_post_meta($post_id, '_plan_analytics',       isset($_POST['plan_analytics']) ? 1 : 0);
    update_post_meta($post_id, '_plan_features',        sanitize_textarea_field($_POST['plan_features'] ?? ''));

    // NEW: Media limits
    update_post_meta($post_id, '_plan_photo_limit',     intval($_POST['plan_photo_limit']        ?? 10));
    update_post_meta($post_id, '_plan_video_limit',     intval($_POST['plan_video_limit']        ?? 2));

    // NEW: Feature toggles
    update_post_meta($post_id, '_plan_enable_whatsapp',   isset($_POST['plan_enable_whatsapp'])   ? 1 : 0);
    update_post_meta($post_id, '_plan_enable_google_map', isset($_POST['plan_enable_google_map']) ? 1 : 0);

    property_theme_sync_stripe_product($post->ID);
    $billing_cycle = get_post_meta($post->ID, '_plan_billing_cycle', true);
    if (in_array($billing_cycle, ['monthly', 'yearly', 'days'])) {
        property_theme_sync_stripe_price($post->ID, $billing_cycle);
    }
}
add_action('save_post_subscription_plan', 'subscription_theme_save_meta_boxes', 10, 2);