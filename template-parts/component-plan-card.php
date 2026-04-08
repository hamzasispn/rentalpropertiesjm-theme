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

function plan_initials($name) {
    $words    = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($words, 0, 2) as $w) {
        $initials .= strtoupper(mb_substr($w, 0, 1));
    }
    return $initials ?: 'PL';
}
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 w-full">

    <?php foreach ($plans_data as $plan):
        $is_current     = $stats['subscription'] && $stats['subscription']->package_id == $plan['id'];
        $is_best_seller = ($plan['id'] === $best_seller_plan_id);

        /* Duration label */
        if (!empty($plan['billing_cycle']) && $plan['billing_cycle'] === 'days') {
            $days = intval($plan['billing_days']);
            if ($days === 1) {
                $duration_label = '1 day';
            } elseif ($days % 365 === 0) {
                $yrs            = $days / 365;
                $duration_label = $yrs == 1 ? '1 year' : $yrs . ' years';
            } elseif ($days % 30 === 0) {
                $mos            = $days / 30;
                $duration_label = $mos == 1 ? '1 month' : $mos . ' months';
            } else {
                $duration_label = $days . ' days';
            }
            $period_label = '/ ' . $duration_label;
        } else {
            $duration_label = ucfirst($plan['billing_cycle'] ?? 'listing');
            $period_label   = '/ ' . strtolower($duration_label);
        }

        /* Featured listing */
        if ($plan['featured_limit'] == 0) {
            $featured_label        = 'Not available';
            $featured_is_available = false;
        } elseif ($plan['featured_limit'] == 1) {
            $featured_label        = '1 property';
            $featured_is_available = true;
        } else {
            $featured_label        = 'Up to ' . $plan['featured_limit'] . ' properties';
            $featured_is_available = true;
        }

        /* Max properties */
        if (!empty($plan['max_properties'])) {
            $props_label = $plan['max_properties'] == 1
                ? '1 property'
                : 'Up to ' . $plan['max_properties'] . ' properties';
        } else {
            $props_label = 'Unlimited';
        }

        /* Card border */
        if ($is_best_seller) {
            $card_border = 'border-2 border-[var(--primary-color)]';
        } elseif ($is_current) {
            $card_border = 'border-2 border-blue-400';
        } else {
            $card_border = 'border border-black/10';
        }

        /* CTA classes */
        $btn_filled  = 'bg-[var(--primary-color)] text-white hover:opacity-85';
        $btn_outline = 'border border-[var(--primary-color)] text-[var(--primary-color)] hover:opacity-85';
        $cta_class   = $is_best_seller ? $btn_filled : $btn_outline;
    ?>

    <div class="relative flex flex-col gap-4 bg-white rounded-[18px] p-6 <?= $card_border; ?> hover:shadow-md transition-shadow duration-200 overflow-hidden">

        <?php if ($is_best_seller): ?>
            <div class="absolute top-0 right-4 bg-[var(--primary-color)] text-white text-[10px] font-semibold uppercase tracking-widest px-3 py-1 rounded-b-lg">
                Best Seller
            </div>
        <?php elseif ($is_current): ?>
            <div class="absolute top-0 right-4 bg-blue-400 text-white text-[10px] font-semibold uppercase tracking-widest px-3 py-1 rounded-b-lg">
                Current Plan
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="flex items-start gap-3 <?= ($is_best_seller || $is_current) ? 'mt-4' : ''; ?>">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center text-sm font-semibold shrink-0"
                 style="background: color-mix(in srgb, var(--primary-color) 12%, transparent); color: var(--primary-color);">
                <?= esc_html(plan_initials($plan['name'])); ?>
            </div>
            <div>
                <p class="text-[15px] font-semibold text-[#1A1A1A] leading-tight m-0"><?= esc_html($plan['name']); ?></p>
                <p class="text-xs text-gray-400 m-0 mt-0.5"><?= esc_html($duration_label); ?> access</p>
            </div>
        </div>

        <!-- Price -->
        <div class="text-[28px] font-semibold text-[#1A1A1A] leading-none">
            $<?= number_format($plan['price']); ?>
            <span class="text-sm font-normal text-gray-400"><?= esc_html($period_label); ?></span>
        </div>

        <hr class="border-t border-black/[0.08] m-0">

        <!-- Stats -->
        <div class="flex flex-col gap-2.5">

            <div class="flex justify-between items-center text-[13px]">
                <span class="text-gray-400">Duration</span>
                <span class="font-medium text-[#1A1A1A]"><?= esc_html($duration_label); ?></span>
            </div>

            <div class="flex justify-between items-center text-[13px]">
                <span class="text-gray-400">Listings</span>
                <span class="font-medium text-[#1A1A1A]"><?= esc_html($props_label); ?></span>
            </div>

            <div class="flex justify-between items-center text-[13px]">
                <span class="text-gray-400">Featured listing</span>
                <?php if ($featured_is_available): ?>
                    <span class="font-medium" style="color: var(--primary-color);"><?= esc_html($featured_label); ?></span>
                <?php else: ?>
                    <span class="text-gray-300"><?= esc_html($featured_label); ?></span>
                <?php endif; ?>
            </div>

            <div class="flex justify-between items-center text-[13px]">
                <span class="text-gray-400">Advanced analytics</span>
                <?php if ($plan['analytics']): ?>
                    <span class="font-medium" style="color: var(--primary-color);">Available</span>
                <?php else: ?>
                    <span class="text-gray-300">Not available</span>
                <?php endif; ?>
            </div>

        </div>

        <!-- Extra features -->
        <?php if (!empty($plan['features'])): ?>
            <hr class="border-t border-black/[0.08] m-0">
            <div class="flex flex-col gap-1.5">
                <?php foreach ($plan['features'] as $feature): ?>
                    <div class="flex items-start gap-2 text-[12.5px] text-gray-500">
                        <span class="text-xs mt-0.5 shrink-0" style="color: var(--primary-color);">✓</span>
                        <span><?= esc_html($feature); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- CTA -->
        <div class="mt-auto pt-1">
            <?php if (is_user_logged_in()): ?>

                <?php if ($is_current): ?>
                    <button disabled
                        class="w-full py-2.5 rounded-full text-sm font-semibold bg-gray-100 text-gray-400 cursor-not-allowed">
                        Current Plan
                    </button>

                <?php elseif ($stats['subscription']): ?>
                    <button
                        class="w-full py-2.5 rounded-full text-sm font-semibold transition-opacity upgrade-plan-btn <?= $cta_class; ?>"
                        data-subscription-id="<?= esc_attr($stats['subscription']->id); ?>"
                        data-plan-id="<?= esc_attr($plan['id']); ?>">
                        Upgrade to <?= esc_html($plan['name']); ?>
                    </button>

                <?php else: ?>
                    <a href="<?= esc_url(home_url('/checkout?plan=' . $plan['id'])); ?>"
                       class="block w-full py-2.5 rounded-full text-sm font-semibold text-center transition-opacity <?= $cta_class; ?>">
                        Choose Plan
                    </a>
                <?php endif; ?>

            <?php else: ?>
                <a href="<?= esc_url(home_url('/login')); ?>"
                   class="block w-full py-2.5 rounded-full text-sm font-semibold text-center transition-opacity <?= $cta_class; ?>">
                    Get Started
                </a>
            <?php endif; ?>
        </div>

    </div>

    <?php endforeach; ?>

</div>