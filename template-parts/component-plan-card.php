<?php
$all_plans = get_posts(array(
    'post_type' => 'subscription_plan',
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
$max_subscriptions = 0;

foreach ($plans_data as $plan) {
    if (!empty($plan['subscription_count']) && $plan['subscription_count'] > $max_subscriptions) {
        $max_subscriptions = $plan['subscription_count'];
        $best_seller_plan_id = $plan['id'];
    }
}

?>

<div class="!font-inter grid grid-cols-1 md:grid-cols-4 gap-4 w-full">

    <?php foreach ($plans_data as $plan):
        $is_current = $stats['subscription'] && $stats['subscription']->package_id == $plan['id'];
        $is_best_seller = ($plan['id'] === $best_seller_plan_id);
        $is_recommended = ($plan['id'] == 163);   // bold banner, pop treatment
        $is_highlighted = ($plan['id'] == 166);   // primary-colour card, no badge
    
        /* Duration label */
        if (!empty($plan['billing_cycle']) && $plan['billing_cycle'] === 'days') {
            $days = intval($plan['billing_days']);
            if ($days === 1) {
                $duration_label = '1 day';
            } else {
                $duration_label = $days . ' days';
            }
            $period_label = '/ ' . $duration_label;
        } else {
            $duration_label = ucfirst($plan['billing_cycle'] ?? 'listing');
            $period_label = '/ ' . strtolower($duration_label);
        }

        /* Featured listing */
        if ($plan['featured_limit'] == 0) {
            $featured_label = 'Not available';
            $featured_is_available = false;
        } elseif ($plan['featured_limit'] == 1) {
            $featured_label = '1 property';
            $featured_is_available = true;
        } else {
            $featured_label = 'Up to ' . $plan['featured_limit'] . ' properties';
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

        /* ----------------------------------------------------------------
         * Card styles
         * Priority: recommended (163) > highlighted (166) > current > default
         * ---------------------------------------------------------------- */
        if ($is_recommended) {
            // Bold pop card — primary bg + scale-up + glow
            $card_bg = 'bg-[var(--primary-color)]';
            $card_border = 'border-2 border-[var(--primary-color)]';
            $card_extra = 'shadow-2xl ring-4 ring-[var(--primary-color)]/40 scale-[1.03] z-10';
            $text_primary = 'text-white';
            $text_secondary = 'text-white/70';
            $text_label = 'text-white/60';
            $divider = 'border-white/20';
        } elseif ($is_highlighted) {
            // Highlighted — same colours as best-seller, no badge
            $card_bg = 'bg-[var(--primary-color)]';
            $card_border = 'border-2 border-[var(--primary-color)]';
            $card_extra = '';
            $text_primary = 'text-white';
            $text_secondary = 'text-white/70';
            $text_label = 'text-white/60';
            $divider = 'border-white/20';
        } elseif ($is_current) {
            $card_bg = 'bg-gray-100';
            $card_border = 'border-2 border-blue-400';
            $card_extra = '';
            $text_primary = 'text-[#1A1A1A]';
            $text_secondary = 'text-gray-500';
            $text_label = 'text-gray-800';
            $divider = 'border-black/[0.08]';
        } else {
            $card_bg = 'bg-gray-100';
            $card_border = 'border border-black/10';
            $card_extra = '';
            $text_primary = 'text-[#1A1A1A]';
            $text_secondary = 'text-gray-500';
            $text_label = 'text-gray-800';
            $divider = 'border-black/[0.08]';
        }

        /* CTA classes */
        $is_dark_card = $is_recommended || $is_highlighted || $is_best_seller;
        $btn_filled = 'bg-white text-[var(--primary-color)] hover:opacity-85';
        $btn_outline = 'border border-[var(--primary-color)] text-[var(--primary-color)] hover:opacity-85';
        $cta_class = $is_dark_card ? $btn_filled : $btn_outline;
        ?>

        <div
            class="relative flex flex-col gap-4 <?= $card_bg; ?> rounded-[24px] p-8 <?= $card_border; ?> <?= $card_extra; ?> hover:shadow-md transition-all duration-200 overflow-hidden">

            <?php if ($is_recommended): ?>
                <!-- Attention-grabbing "Recommended" banner -->
                <div class="absolute top-0 left-0 right-0 overflow-hidden rounded-t-[22px]">
                    <!-- Shimmer stripe -->
                    <div class="relative flex items-center justify-center gap-2 bg-white py-2 px-4">
                        <!-- animated shimmer layer -->
                        <div
                            class="absolute inset-0 -translate-x-full animate-[shimmer_2.2s_ease-in-out_infinite] bg-gradient-to-r from-transparent via-[var(--primary-color)]/10 to-transparent">
                        </div>
                        <svg class="w-3.5 h-3.5 text-[var(--primary-color)] shrink-0" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 1l1.85 3.75L14 5.69l-3 2.92.71 4.14L8 10.77l-3.71 1.98.71-4.14L2 5.69l4.15-.94z" />
                        </svg>
                        <span class="text-[var(--primary-color)] text-[11px] font-bold uppercase tracking-[0.18em] relative">
                            Most Popular
                        </span>
                        <svg class="w-3.5 h-3.5 text-[var(--primary-color)] shrink-0" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 1l1.85 3.75L14 5.69l-3 2.92.71 4.14L8 10.77l-3.71 1.98.71-4.14L2 5.69l4.15-.94z" />
                        </svg>
                    </div>
                </div>
                <!-- push content below the banner -->
                <div class="h-6"></div>

            <?php elseif ($is_current): ?>
                <div
                    class="absolute top-0 right-4 bg-blue-400 text-white text-[10px] font-semibold uppercase tracking-widest px-3 py-1 rounded-b-lg">
                    Current Plan
                </div>

            <?php elseif ($is_best_seller && !$is_highlighted): ?>
                <div
                    class="absolute top-0 right-4 bg-white text-[var(--primary-color)] text-[10px] font-semibold uppercase tracking-widest px-3 py-1 rounded-b-lg">
                    Best Seller
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div
                class="flex items-start gap-3 <?= ($is_best_seller || $is_current || $is_highlighted) && !$is_recommended ? 'mt-4' : ''; ?>">
                <div>
                    <p class="text-2xl font-semibold font-inter <?= $text_secondary; ?> m-0 mt-0.5">
                        <?= esc_html($duration_label); ?> listings</p>
                </div>
            </div>

            <!-- Price -->
            <div class="text-[28px] font-semibold font-inter <?= $text_primary; ?> leading-none">
                <?= empty($plan['price']) ? 'Free' : '$' . number_format($plan['price']); ?>
                <span class="text-sm font-normal font-inter <?= $text_secondary; ?>"><?= esc_html($period_label); ?></span>
            </div>

            <hr class="border-t <?= $divider; ?> m-0">

            <!-- Stats -->
            <div class="flex flex-col gap-2.5">

                <div class="flex justify-between items-center text-[13px]">
                    <span
                        class="<?= $text_label; ?> <?= $is_dark_card ? 'text-white/60' : 'text-gray-800'; ?> font-inter">Duration</span>
                    <span class="font-medium font-inter <?= $text_primary; ?>"><?= esc_html($duration_label); ?></span>
                </div>

                <div class="flex justify-between items-center text-[13px]">
                    <span
                        class="<?= $text_label; ?> <?= $is_dark_card ? 'text-white/60' : 'text-gray-800'; ?> font-inter">Listings</span>
                    <span class="font-medium font-inter <?= $text_primary; ?>"><?= esc_html($props_label); ?></span>
                </div>

                <div class="flex justify-between items-center text-[13px]">
                    <span
                        class="<?= $text_label; ?> <?= $is_dark_card ? 'text-white/60' : 'text-gray-800'; ?> font-inter">Featured
                        listing</span>
                    <?php if ($featured_is_available): ?>
                        <span class="font-medium font-inter <?= $is_dark_card ? 'text-white' : ''; ?>" <?= !$is_dark_card ? 'style="color: var(--primary-color);"' : ''; ?>><?= esc_html($featured_label); ?></span>
                    <?php else: ?>
                        <span class="<?= $text_secondary; ?> font-inter"><?= esc_html($featured_label); ?></span>
                    <?php endif; ?>
                </div>

                <div class="flex justify-between items-center text-[13px]">
                    <span
                        class="<?= $text_label; ?> <?= $is_dark_card ? 'text-white/60' : 'text-gray-800'; ?> font-inter">Advanced
                        analytics</span>
                    <?php if ($plan['analytics']): ?>
                        <span class="font-medium font-inter <?= $is_dark_card ? 'text-white' : ''; ?>" <?= !$is_dark_card ? 'style="color: var(--primary-color);"' : ''; ?>>Available</span>
                    <?php else: ?>
                        <span class="<?= $text_secondary; ?> font-inter">Not available</span>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Extra features -->
            <?php if (!empty($plan['features'])): ?>
                <hr class="border-t <?= $divider; ?> m-0">
                <div class="flex flex-col gap-1.5">
                    <?php foreach ($plan['features'] as $feature): ?>
                        <div class="flex items-start gap-2 md:text-[0.633vw] text-[13px] <?= $text_secondary; ?>">
                            <span class="text-sm mt-0.5 shrink-0 <?= $is_dark_card ? 'text-white' : ''; ?>" <?= !$is_dark_card ? 'style="color: var(--primary-color);"' : ''; ?>>✓</span>
                            <span class="font-inter"><?= esc_html($feature); ?></span>
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

<style>
    @keyframes shimmer {
        0% {
            transform: translateX(-100%);
        }

        60% {
            transform: translateX(100%);
        }

        100% {
            transform: translateX(100%);
        }
    }
</style>