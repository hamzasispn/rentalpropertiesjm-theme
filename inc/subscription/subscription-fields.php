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
                    ? '<span class="text-green-600 inline-flex items-center gap-1"><svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>' . esc_html($stripe_product_id) . '</span>'
                    : '<span style="color:orange;">Pending sync…</span>'; ?><br>
                Monthly Price ID:
                <?php echo $stripe_price_id
                    ? '<span class="text-green-600 inline-flex items-center gap-1"><svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>' . esc_html($stripe_price_id) . '</span>'
                    : '<span style="color:orange;">Pending sync…</span>'; ?><br>
                Yearly Price ID:
                <?php echo $stripe_yearly_price_id
                    ? '<span class="text-green-600 inline-flex items-center gap-1"><svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>' . esc_html($stripe_yearly_price_id) . '</span>'
                    : '<span style="color:orange;">Pending sync…</span>'; ?><br>
                Custom Days Price ID:
                <?php
                $current_cycle = get_post_meta($post->ID, '_plan_billing_cycle', true);
                if ($current_cycle === 'days') {
                    echo $stripe_days_price_id
                        ? '<span class="text-green-600 inline-flex items-center gap-1"><svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>' . esc_html($stripe_days_price_id) . '</span>'
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
            <label for="plan_billing_days" class="flex items-center gap-1.5 mb-1 font-bold text-amber-700">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
                Number of Days
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
            <h4 class="m-0 mb-3 text-green-800 text-sm font-bold uppercase tracking-wider flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="4"/></svg>
                Media Upload Limits
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
            <h4 class="m-0 mb-3 text-blue-800 text-sm font-bold uppercase tracking-wider flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                Feature Toggles
            </h4>

            <label style="display:flex; align-items:center; gap:10px; margin-bottom:12px; cursor:pointer; padding:10px; background:white; border-radius:6px; border:1px solid #bfdbfe;">
                <input type="checkbox" id="plan_enable_whatsapp" name="plan_enable_whatsapp" value="1"
                       <?php checked($enable_whatsapp, 1); ?>
                       style="width:18px; height:18px; cursor:pointer;">
                <div>
                    <span class="font-semibold text-sm text-blue-800 inline-flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 640 640" fill="currentColor"><path d="M476.9 161.1C435 119.1 379.2 96 319.9 96 197.5 96 97.9 195.6 97.9 318c0 39.1 10.2 77.3 29.6 111L96 544l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222s-25.2-108.1-67.1-150zM319.9 502.7c-33.2 0-65.7-8.9-94-25.7L168 491.3l18.6-68.1C167.1 385.6 135.4 352.9 135.4 318c0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6z"/></svg>
                        Enable WhatsApp Contact Button
                    </span>
                    <p style="margin:2px 0 0; font-size:12px; color:#64748b;">Allow users on this plan to show WhatsApp contact on their listings</p>
                </div>
            </label>

            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; padding:10px; background:white; border-radius:6px; border:1px solid #bfdbfe;">
                <input type="checkbox" id="plan_enable_google_map" name="plan_enable_google_map" value="1"
                       <?php checked($enable_google_map, 1); ?>
                       style="width:18px; height:18px; cursor:pointer;">
                <div>
                    <span class="font-semibold text-sm text-blue-800 inline-flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><circle cx="12" cy="11" r="3"/></svg>
                        Enable Google Map / Location Pin
                    </span>
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