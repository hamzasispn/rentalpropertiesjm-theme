<?php
$all_plans = get_posts(array(
    'post_type'   => 'subscription_plan',
    'post_status' => 'publish',
    'numberposts' => -1,
));

$stats = isset($args['subscription'])
    ? array('subscription' => $args['subscription'])
    : array('subscription' => null);

if ($stats['subscription'] && $stats['subscription']->status === 'canceled') {
    $stats['subscription'] = null;
}

$plans_data = array_map(function ($plan) {
    return property_theme_get_plan($plan->ID);
}, $all_plans);

usort($plans_data, function ($a, $b) {
    return $a['price'] <=> $b['price'];
});

$best_seller_plan_id = null;
$max_subscriptions   = 0;

foreach ($plans_data as $plan) {
    if (!empty($plan['subscription_count']) && $plan['subscription_count'] > $max_subscriptions) {
        $max_subscriptions   = $plan['subscription_count'];
        $best_seller_plan_id = $plan['id'];
    }
}

/* Helper: initials from plan name */
function plan_initials($name) {
    $words = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($words, 0, 2) as $w) {
        $initials .= strtoupper(mb_substr($w, 0, 1));
    }
    return $initials ?: 'PL';
}
?>

<style>
.pt-plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
    width: 100%;
    font-family: inherit;
}

.pt-plan-card {
    background: #ffffff;
    border: 0.5px solid rgba(0,0,0,0.1);
    border-radius: 18px;
    padding: 22px 22px 20px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    position: relative;
    transition: box-shadow 0.2s ease;
    overflow: hidden;
}

.pt-plan-card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.pt-plan-card.is-best-seller {
    border: 2px solid var(--primary-color);
}

.pt-plan-card.is-current {
    border: 2px solid #378ADD;
}

/* Badge */
.pt-badge {
    position: absolute;
    top: -1px;
    right: 16px;
    font-size: 10px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 0 0 10px 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.pt-badge-best {
    background: var(--primary-color);
    color: #fff;
}

.pt-badge-current {
    background: #378ADD;
    color: #E6F1FB;
}

/* Header row */
.pt-card-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.pt-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 600;
    flex-shrink: 0;
    background: color-mix(in srgb, var(--primary-color) 12%, transparent);
    color: var(--primary-color);
}

.pt-plan-name {
    font-size: 15px;
    font-weight: 600;
    color: #1A1A1A;
    margin: 0 0 2px;
    line-height: 1.2;
}

.pt-plan-sub {
    font-size: 12px;
    color: #888;
    margin: 0;
}

/* Price */
.pt-price {
    font-size: 28px;
    font-weight: 600;
    color: #1A1A1A;
    line-height: 1;
}

.pt-price-period {
    font-size: 13px;
    font-weight: 400;
    color: #999;
}

/* Divider */
.pt-divider {
    border: none;
    border-top: 0.5px solid rgba(0,0,0,0.09);
    margin: 0;
}

/* Stats */
.pt-stats {
    display: flex;
    flex-direction: column;
    gap: 9px;
}

.pt-stat-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
}

.pt-stat-label {
    color: #888;
}

.pt-stat-value {
    color: #1A1A1A;
    font-weight: 500;
}

.pt-stat-value.available {
    color: var(--primary-color);
}

.pt-stat-value.unavailable {
    color: #bbb;
    font-weight: 400;
}

/* Features */
.pt-features {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.pt-feature-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 12.5px;
    color: #666;
}

.pt-feature-check {
    color: var(--primary-color);
    font-size: 12px;
    flex-shrink: 0;
    margin-top: 1px;
}

/* CTA */
.pt-cta {
    margin-top: auto;
}

.pt-btn {
    display: block;
    width: 100%;
    text-align: center;
    padding: 10px 0;
    border-radius: 50px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    box-sizing: border-box;
    transition: opacity 0.15s, background 0.15s;
    border: none;
}

.pt-btn:hover {
    opacity: 0.88;
}

.pt-btn-primary {
    background: var(--primary-color);
    color: #fff;
}

.pt-btn-outline {
    background: transparent;
    border: 1.5px solid var(--primary-color);
    color: var(--primary-color);
}

.pt-btn-disabled {
    background: #f0f0f0;
    color: #aaa;
    cursor: default;
    pointer-events: none;
}
</style>

<div class="pt-plans-grid">

    <?php foreach ($plans_data as $plan):
        $is_current     = $stats['subscription'] && $stats['subscription']->package_id == $plan['id'];
        $is_best_seller = ($plan['id'] === $best_seller_plan_id);

        /* Duration label */
        if ($plan['billing_cycle'] === 'days') {
            $days = intval($plan['billing_days']);
            if ($days === 1) {
                $duration_label = '1 day';
            } elseif ($days % 365 === 0) {
                $yrs = $days / 365;
                $duration_label = $yrs == 1 ? '1 year' : $yrs . ' years';
            } elseif ($days % 30 === 0) {
                $mos = $days / 30;
                $duration_label = $mos == 1 ? '1 month' : $mos . ' months';
            } else {
                $duration_label = $days . ' days';
            }
            $period_label = '/ ' . $duration_label;
        } else {
            $duration_label = ucfirst($plan['billing_cycle'] ?? 'listing');
            $period_label   = '/ ' . strtolower($duration_label);
        }

        /* Featured listing label */
        if ($plan['featured_limit'] == 0) {
            $featured_label = 'Not available';
            $featured_class = 'unavailable';
        } elseif ($plan['featured_limit'] == 1) {
            $featured_label = '1 property';
            $featured_class = 'available';
        } else {
            $featured_label = 'Up to ' . $plan['featured_limit'] . ' properties';
            $featured_class = 'available';
        }

        /* Max properties label */
        if (!empty($plan['max_properties'])) {
            $props_label = $plan['max_properties'] == 1
                ? '1 property'
                : 'Up to ' . $plan['max_properties'] . ' properties';
        } else {
            $props_label = 'Unlimited';
        }

        /* Card classes */
        $card_classes = 'pt-plan-card';
        if ($is_best_seller) $card_classes .= ' is-best-seller';
        if ($is_current)     $card_classes .= ' is-current';
    ?>

    <div class="<?= esc_attr($card_classes); ?>">

        <?php if ($is_best_seller): ?>
            <div class="pt-badge pt-badge-best">Best Seller</div>
        <?php elseif ($is_current): ?>
            <div class="pt-badge pt-badge-current">Current Plan</div>
        <?php endif; ?>

        <!-- Header -->
        <div class="pt-card-header">
            <div class="pt-icon"><?= esc_html(plan_initials($plan['name'])); ?></div>
            <div>
                <p class="pt-plan-name"><?= esc_html($plan['name']); ?></p>
                <p class="pt-plan-sub"><?= esc_html($duration_label); ?> access</p>
            </div>
        </div>

        <!-- Price -->
        <div class="pt-price">
            $<?= number_format($plan['price']); ?>
            <span class="pt-price-period"><?= esc_html($period_label); ?></span>
        </div>

        <hr class="pt-divider">

        <!-- Stats -->
        <div class="pt-stats">
            <div class="pt-stat-row">
                <span class="pt-stat-label">Duration</span>
                <span class="pt-stat-value"><?= esc_html($duration_label); ?></span>
            </div>
            <div class="pt-stat-row">
                <span class="pt-stat-label">Listings</span>
                <span class="pt-stat-value"><?= esc_html($props_label); ?></span>
            </div>
            <div class="pt-stat-row">
                <span class="pt-stat-label">Featured listing</span>
                <span class="pt-stat-value <?= esc_attr($featured_class); ?>"><?= esc_html($featured_label); ?></span>
            </div>
            <div class="pt-stat-row">
                <span class="pt-stat-label">Advanced analytics</span>
                <?php if ($plan['analytics']): ?>
                    <span class="pt-stat-value available">Available</span>
                <?php else: ?>
                    <span class="pt-stat-value unavailable">Not available</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Extra features -->
        <?php if (!empty($plan['features'])): ?>
            <hr class="pt-divider">
            <div class="pt-features">
                <?php foreach ($plan['features'] as $feature): ?>
                    <div class="pt-feature-item">
                        <span class="pt-feature-check">✓</span>
                        <span><?= esc_html($feature); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- CTA -->
        <div class="pt-cta">
            <?php if (is_user_logged_in()): ?>

                <?php if ($is_current): ?>
                    <button class="pt-btn pt-btn-disabled" disabled>Current Plan</button>

                <?php elseif ($stats['subscription']): ?>
                    <button
                        class="pt-btn <?= $is_best_seller ? 'pt-btn-primary' : 'pt-btn-outline'; ?> upgrade-plan-btn"
                        data-subscription-id="<?= esc_attr($stats['subscription']->id); ?>"
                        data-plan-id="<?= esc_attr($plan['id']); ?>">
                        Upgrade to <?= esc_html($plan['name']); ?>
                    </button>

                <?php else: ?>
                    <a href="<?= esc_url(home_url('/checkout?plan=' . $plan['id'])); ?>"
                       class="pt-btn <?= $is_best_seller ? 'pt-btn-primary' : 'pt-btn-outline'; ?>">
                        Choose Plan
                    </a>
                <?php endif; ?>

            <?php else: ?>
                <a href="<?= esc_url(home_url('/login')); ?>"
                   class="pt-btn <?= $is_best_seller ? 'pt-btn-primary' : 'pt-btn-outline'; ?>">
                    Get Started
                </a>
            <?php endif; ?>
        </div>

    </div>

    <?php endforeach; ?>

</div>