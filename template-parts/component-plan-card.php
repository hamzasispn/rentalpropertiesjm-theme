<?php
/**
 * Pricing / plan-card component.
 *
 * Data: every published `subscription_plan` post drives one card.
 *   - Plans with max_properties <= 1 render in the 4-up Individual Listings grid.
 *   - Plans with max_properties >  1 render in the Realtor / Portfolio Hub block.
 *
 * Markup: Tailwind utility classes only — no <style> block, no inline CSS files.
 *
 * Logged-in users get Upgrade / Current Plan buttons; visitors get a Choose Plan
 * link to /checkout?plan=ID (or /login if no checkout is desired before signup).
 */

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

// Pick the highest-subscriber plan as Most Popular (fallback only — designer's
// recommended plan still wins below if a specific ID is hard-coded).
$best_seller_plan_id = null;
$max_subscriptions   = 0;
foreach ($plans_data as $plan) {
    if (!empty($plan['subscription_count']) && $plan['subscription_count'] > $max_subscriptions) {
        $max_subscriptions   = $plan['subscription_count'];
        $best_seller_plan_id = $plan['id'];
    }
}

// Split plans: individuals vs realtor/portfolio (anything that allows >1 listing)
$individual_plans = array();
$realtor_plans    = array();
foreach ($plans_data as $p) {
    if (intval($p['max_properties'] ?? 0) > 1) $realtor_plans[]    = $p;
    else                                       $individual_plans[] = $p;
}

/**
 * Render the CTA button for a plan, respecting login + current-subscription state.
 * Variants control color/contrast so the same logic renders on both light and dark cards.
 */
$render_cta = function ($plan, $stats, $variant = 'outline-dark') {
    $is_current = $stats['subscription'] && $stats['subscription']->package_id == $plan['id'];

    // Map variant → tailwind classes for the CTA
    $variants = array(
        'outline-dark' => 'border border-[#0b1a3a] text-[#0b1a3a] bg-transparent hover:bg-[#0b1a3a] hover:text-white',
        'solid-white'  => 'bg-white text-[#0b1a3a] hover:bg-[#d4a93a]',
        'solid-gold'   => 'bg-[#d4a93a] text-[#0b1a3a] border border-[#d4a93a] hover:-translate-y-0.5 hover:shadow-[0_14px_30px_-12px_rgba(212,169,58,0.6)]',
    );
    $base = 'block w-full text-center py-3 px-4 rounded-full text-sm font-semibold transition';
    $cls  = $base . ' ' . ($variants[$variant] ?? $variants['outline-dark']);

    if (!is_user_logged_in()) {
        $href = esc_url(home_url('/login'));
        return '<a href="' . $href . '" class="' . esc_attr($cls) . '">Get Started</a>';
    }

    if ($is_current) {
        return '<button disabled class="' . esc_attr($base) . ' bg-gray-100 text-gray-400 cursor-not-allowed">Current Plan</button>';
    }

    if ($stats['subscription']) {
        return '<button class="upgrade-plan-btn ' . esc_attr($cls) . '"'
            . ' data-subscription-id="' . esc_attr($stats['subscription']->id) . '"'
            . ' data-plan-id="' . esc_attr($plan['id']) . '">'
            . 'Upgrade to ' . esc_html($plan['name'])
            . '</button>';
    }

    $href = esc_url(home_url('/checkout?plan=' . $plan['id']));
    return '<a href="' . $href . '" class="' . esc_attr($cls) . '">Choose Plan</a>';
};

/**
 * Format duration label from billing_cycle + billing_days.
 */
$duration_label = function ($plan) {
    if (!empty($plan['billing_cycle']) && $plan['billing_cycle'] === 'days') {
        $days = intval($plan['billing_days']);
        return $days === 1 ? '1 day' : ($days . ' days');
    }
    return ucfirst($plan['billing_cycle'] ?? 'listing');
};

/**
 * Format featured-listing label.
 */
$featured_label = function ($plan) {
    if (empty($plan['featured_limit'])) return array('Not available', false);
    if ($plan['featured_limit'] == 1)   return array('1 property', true);
    return array('Up to ' . $plan['featured_limit'] . ' properties', true);
};

/**
 * Format listings (max properties) label.
 */
$listings_label = function ($plan) {
    if (empty($plan['max_properties'])) return 'Unlimited';
    if ($plan['max_properties'] == 1)   return '1 property';
    return 'Up to ' . $plan['max_properties'] . ' properties';
};
?>

<div class="font-inter text-[#0b1a3a]">

    <!-- ═════════════ Individual Listing Plans ═════════════ -->
    <?php if (!empty($individual_plans)): ?>
    <div class="max-w-[1280px] mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-5">

        <?php foreach ($individual_plans as $plan):
            $is_current   = $stats['subscription'] && $stats['subscription']->package_id == $plan['id'];
            $is_featured  = ($plan['id'] === $best_seller_plan_id) || ($plan['id'] == 205);
            $duration     = $duration_label($plan);
            list($featured_text, $featured_available) = $featured_label($plan);
            $listings_text = $listings_label($plan);

            // Card variants
            if ($is_featured) {
                $card_cls    = 'relative bg-[#0b1a3a] text-white border border-[#0b1a3a] shadow-[0_20px_50px_-18px_rgba(11,26,58,0.45)]';
                $muted_cls   = 'text-white/65';
                $primary_cls = 'text-white';
                $divider_cls = 'border-white/15';
                $check_cls   = 'text-[#f4e6b8]';
                $cta_variant = 'solid-white';
            } else {
                $card_cls    = 'bg-white text-[#0b1a3a] border border-[#e5e9f2] hover:-translate-y-1 hover:shadow-[0_18px_40px_-20px_rgba(11,26,58,0.25)] hover:border-[#d8deea]';
                $muted_cls   = 'text-gray-500';
                $primary_cls = 'text-[#0b1a3a]';
                $divider_cls = 'border-[#e5e9f2]';
                $check_cls   = 'text-[#d4a93a]';
                $cta_variant = 'outline-dark';
            }
        ?>
        <article class="<?= $card_cls; ?> rounded-2xl p-6 sm:p-7 flex flex-col transition duration-200">

            <?php if ($is_featured): ?>
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-white text-[#0b1a3a] text-[10px] font-bold tracking-[0.18em] uppercase px-3 py-1.5 rounded-full border border-[#0b1a3a] whitespace-nowrap">
                    <span class="text-[#d4a93a]">★</span> Most Popular <span class="text-[#d4a93a]">★</span>
                </span>
            <?php elseif ($is_current): ?>
                <span class="absolute -top-3 right-4 bg-blue-500 text-white text-[10px] font-bold tracking-widest uppercase px-3 py-1 rounded-full">
                    Current Plan
                </span>
            <?php endif; ?>

            <!-- Duration -->
            <p class="text-sm font-medium <?= $muted_cls; ?> mb-1.5">
                <?= esc_html($duration); ?> listings
            </p>

            <!-- Price -->
            <h3 class="text-3xl font-extrabold tracking-tight <?= $primary_cls; ?> mb-6">
                <?= empty($plan['price']) ? 'Free' : '$' . number_format($plan['price']); ?>
                <span class="text-sm font-medium <?= $muted_cls; ?> ml-1">/ <?= esc_html($duration); ?></span>
            </h3>

            <!-- Specs -->
            <ul class="border-y <?= $divider_cls; ?> py-3.5 mb-5 space-y-1.5">
                <li class="flex justify-between text-[13px]">
                    <span class="<?= $muted_cls; ?>">Duration</span>
                    <span class="font-semibold <?= $primary_cls; ?>"><?= esc_html($duration); ?></span>
                </li>
                <li class="flex justify-between text-[13px]">
                    <span class="<?= $muted_cls; ?>">Listings</span>
                    <span class="font-semibold <?= $primary_cls; ?>"><?= esc_html($listings_text); ?></span>
                </li>
                <li class="flex justify-between text-[13px]">
                    <span class="<?= $muted_cls; ?>">Featured listing</span>
                    <span class="font-semibold <?= $featured_available ? $primary_cls : $muted_cls; ?>">
                        <?= esc_html($featured_text); ?>
                    </span>
                </li>
                <li class="flex justify-between text-[13px]">
                    <span class="<?= $muted_cls; ?>">Advanced analytics</span>
                    <span class="font-semibold <?= !empty($plan['analytics']) ? $primary_cls : $muted_cls; ?>">
                        <?= !empty($plan['analytics']) ? 'Available' : 'Not available'; ?>
                    </span>
                </li>
            </ul>

            <!-- Feature bullets -->
            <?php if (!empty($plan['features'])): ?>
            <ul class="space-y-2 mb-6 flex-grow">
                <?php foreach ($plan['features'] as $feature): ?>
                <li class="flex items-start gap-2 text-sm <?= $primary_cls; ?>">
                    <svg class="w-4 h-4 mt-0.5 shrink-0 <?= $check_cls; ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span><?= esc_html($feature); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div class="flex-grow"></div>
            <?php endif; ?>

            <!-- CTA -->
            <div class="mt-auto"><?= $render_cta($plan, $stats, $cta_variant); ?></div>
        </article>
        <?php endforeach; ?>

    </div>
    <?php endif; ?>


    <!-- ═════════════ Realtor / Portfolio Hub ═════════════ -->
    <?php foreach ($realtor_plans as $rplan):
        $duration = $duration_label($rplan);
        list($featured_text) = $featured_label($rplan);
        $listings_text = $listings_label($rplan);
    ?>
    <aside class="relative max-w-[1280px] mx-auto mt-16 md:mt-20 rounded-3xl overflow-hidden p-8 md:p-14 text-white shadow-[0_30px_80px_-30px_rgba(11,26,58,0.5)]"
           style="background:
                radial-gradient(1200px 400px at 90% -10%, rgba(212,169,58,0.18), transparent 60%),
                radial-gradient(900px 500px at -10% 110%, rgba(26,47,107,0.6), transparent 60%),
                linear-gradient(135deg, #0b1a3a 0%, #122452 100%);">

        <!-- Decorative grid overlay -->
        <div class="pointer-events-none absolute inset-0 opacity-[0.04]"
             style="background-image:
                linear-gradient(rgba(255,255,255,1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,1) 1px, transparent 1px);
                background-size: 56px 56px;"></div>

        <div class="relative grid grid-cols-1 lg:grid-cols-[1.1fr_1fr] gap-10 lg:gap-14 items-center">

            <!-- LEFT: pitch -->
            <div>
                <p class="inline-flex items-center gap-2.5 text-[11px] tracking-[0.22em] font-bold uppercase text-[#d4a93a] mb-4">
                    <span class="inline-block w-7 h-px bg-[#d4a93a]"></span>
                    For Realtors &amp; Portfolio Owners
                </p>

                <h3 class="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight leading-[1.15] mb-4">
                    Your Portfolio, <span class="text-[#d4a93a]">One Powerful Hub.</span>
                </h3>

                <p class="text-base md:text-lg leading-relaxed text-white/75 mb-7 max-w-[520px]">
                    Manage and showcase up to <strong class="text-white"><?= esc_html($listings_text); ?></strong>
                    for rent or sale from a single dashboard. Built for realtors, investors, and owners
                    who need professional visibility, premium placement, and serious analytics — all in one place.
                </p>

                <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 mb-8">
                    <?php
                    $benefits = !empty($rplan['features']) ? $rplan['features'] : array(
                        'Centralized portfolio dashboard',
                        'All listings featured by default',
                        'Priority placement in search',
                        'Bulk upload & management',
                        'Lead routing via WhatsApp',
                        'Advanced analytics per property',
                    );
                    foreach ($benefits as $benefit): ?>
                    <li class="relative pl-7 text-sm text-white/90">
                        <span class="absolute left-0 top-1.5 w-3.5 h-3.5 rounded-full bg-[#d4a93a] shadow-[0_0_0_4px_rgba(212,169,58,0.18)]"></span>
                        <?= esc_html($benefit); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <div class="flex flex-wrap gap-3">
                    <?= $render_cta($rplan, $stats, 'solid-gold'); ?>
                </div>
            </div>

            <!-- RIGHT: glass card -->
            <div class="bg-white/[0.06] backdrop-blur-md border border-white/[0.14] rounded-2xl p-7 sm:p-9">
                <p class="text-xs font-bold tracking-[0.18em] uppercase text-[#f4e6b8] mb-2.5">Realtor Plan</p>
                <h4 class="text-xl sm:text-2xl font-bold mb-4"><?= esc_html($rplan['name']); ?></h4>

                <div class="flex items-baseline gap-1.5 pb-6 mb-6 border-b border-white/15">
                    <span class="text-4xl sm:text-5xl font-extrabold tracking-tight">
                        <?= empty($rplan['price']) ? 'Free' : '$' . number_format($rplan['price']); ?>
                    </span>
                    <span class="text-sm text-white/65">/ <?= esc_html($duration); ?></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <div>
                        <p class="text-[11px] tracking-[0.1em] uppercase text-white/55 mb-1">Duration</p>
                        <p class="text-base font-bold"><?= esc_html($duration); ?></p>
                    </div>
                    <div>
                        <p class="text-[11px] tracking-[0.1em] uppercase text-white/55 mb-1">Listings</p>
                        <p class="text-base font-bold"><?= esc_html($listings_text); ?></p>
                    </div>
                    <div>
                        <p class="text-[11px] tracking-[0.1em] uppercase text-white/55 mb-1">Featured</p>
                        <p class="text-base font-bold"><?= esc_html($featured_text); ?></p>
                    </div>
                    <div>
                        <p class="text-[11px] tracking-[0.1em] uppercase text-white/55 mb-1">Analytics</p>
                        <p class="text-base font-bold"><?= !empty($rplan['analytics']) ? 'Centralized dashboard' : 'Not available'; ?></p>
                    </div>
                    <div>
                        <p class="text-[11px] tracking-[0.1em] uppercase text-white/55 mb-1">Photos / listing</p>
                        <p class="text-base font-bold"><?= intval($rplan['photo_limit'] ?? 0); ?> per listing</p>
                    </div>
                    <div>
                        <p class="text-[11px] tracking-[0.1em] uppercase text-white/55 mb-1">Videos / listing</p>
                        <p class="text-base font-bold"><?= intval($rplan['video_limit'] ?? 0); ?> per listing</p>
                    </div>
                </div>

                <?= $render_cta($rplan, $stats, 'solid-white'); ?>
            </div>
        </div>
    </aside>
    <?php endforeach; ?>

</div>
