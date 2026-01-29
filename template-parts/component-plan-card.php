<?php
// Get all plans for upgrade option
$all_plans = get_posts(array(
        'post_type' => 'subscription_plan',
        'post_status' => 'publish',
        'numberposts' => -1,
));

$plans_data = array_map(function ($plan) {
    return property_theme_get_plan($plan->ID);
}, $all_plans);

$best_seller_plan_id = null;
$max_subscriptions = 0;

foreach ($plans_data as $plan) {
    if (!empty($plan['subscription_count']) && $plan['subscription_count'] > $max_subscriptions) {
        $max_subscriptions = $plan['subscription_count'];
        $best_seller_plan_id = $plan['id'];
    }
}

?>

<?php foreach ($plans_data as $plan):
    $is_current = $stats['subscription'] && $stats['subscription']->package_id == $plan['id'];
    $is_best_seller = ($plan['id'] === $best_seller_plan_id);
    ?>


    <div
            class="px-[0.833vw] <?= $is_best_seller ? 'bg-[var(--primary-color)]' : 'bg-white'; ?> pb-6 pt-[2.2vw] relative overflow-hidden rounded-[16px] shadow-xl flex flex-col gap-4 <?php echo $is_current ? 'border-2 border-blue-600' : ''; ?>">
        <?php if ($is_best_seller): ?>
            <div class="bg-white text-[var(--primary-color)] absolute rounded-l-full right-[0px] top-[15px] py-2 px-4 text-[1.042vw] font-semibold font-inter text-center uppercase">
                Best Seller
            </div>
        <?php endif; ?>
        <div class="flex justify-between items-start mb-2">
            <h3 class="text-[1.875vw] font-bold w-[70%] leading-[1] <?= $is_best_seller ? 'text-white' : 'text-[#1A1A1A]'; ?>"><?php echo esc_html($plan['name']); ?></h3>
            <?php if ($is_current): ?>
                <span class="px-3 py-1 bg-blue-100 text-[var(--primary-color)] text-xs font-semibold rounded ">Current
                                    Plan</span>
            <?php endif; ?>

        </div>
        <p class="<?= $is_best_seller ? 'text-white' : 'text-[#1A1A1A]'; ?> text-[0.833vw] font-inter">Individual homeowners listing a
            single property</p>

        <h6 class="<?= $is_best_seller ? 'text-white' : 'text-[#1A1A1A]'; ?> text-[2.5vw] font-bold font-inter">$<?= $plan['price']; ?>
            <span
                    class="text-[0.833vw] font-light">/ <?= $plan['billing_cycle'] ?></span>
        </h6>

        <ul class=" flex flex-col gap-5 <?= $is_best_seller ? 'text-white' : 'text-[#1A1A1A]'; ?> text-[0.833vw]">
            <li class="font-bold font-inter text-[0.99vw]">
                Listings Included :
                <span class="font-light">
                    <?= $plan['max_properties'] == 1 ? '1 property' : 'Up to ' . esc_html($plan['max_properties']) . ' properties'; ?>
                 </span>
            </li>

            <li class="font-bold font-inter text-[0.99vw]">Featured Listing : <span
                        class="font-light"><?= $plan['featured_limit'] == 1 ? '1 property' : 'Up to ' . esc_html($plan['featured_limit']) . ' properties'; ?></span></li>
            <li class="font-bold font-inter text-[0.99vw]">Advanced Analytics : <span
                        class="font-light"><?php echo $plan['analytics'] ? 'Available' : 'Not Available'; ?></span></li>
            <?php if ($plan['features']): ?>
                <li class="font-bold font-inter text-[0.99vw] flex gap-6">Features
                    <ul class="list-disc">
                        <?php foreach ($plan['features'] as $feature): ?>
                            <li class="font-medium text-[0.729vw]"><?= esc_html($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endif; ?>
        </ul>

        <div class="mt-auto">
            <?php if (is_user_logged_in()): ?>
                <?php if (!$is_current && $stats['subscription']): ?>
                    <button
                            class="w-full px-4 py-2 <?= $is_best_seller ? 'bg-white text-[var(--primary-color)] hover:bg-white/80' : 'bg-[var(--primary-color)] text-white hover:bg-blue-700' ?> rounded-lg transition upgrade-to-plan-btn"
                            data-plan-id="<?php echo esc_attr($plan['id']); ?>">
                        Upgrade to <?php echo esc_html($plan['name']); ?>
                    </button>
                <?php elseif (!$stats['subscription']): ?>
                    <a href="<?php echo esc_url(home_url('/checkout?plan=' . $plan['id'])); ?>"
                       class="block w-full px-4 py-2 <?= $is_best_seller ? 'bg-white text-[var(--primary-color)] hover:bg-white/80' : 'bg-[var(--primary-color)] text-white hover:bg-blue-700' ?> rounded-lg transition text-center">
                        Choose Plan
                    </a>
                <?php endif; ?>

            <?php else: ?>
                <a href="<?= home_url() . '/login'; ?>"
                   class="block px-4 py-2 font-semibold font-inter <?= $is_best_seller ? 'bg-white text-[var(--primary-color)] hover:bg-white/80' : 'bg-[var(--primary-color)] text-white hover:bg-blue-700' ?> rounded-lg transition text-[0.938vw] w-full text-center">
                    Get
                    Started
                </a>
            <?php endif; ?>
        </div>

    </div>
<?php endforeach; ?>