<?php
/**
 * Template Name: Pricing Page
 */
get_header();
?>

<!-- Plans Section -->
<section id="packages" class="flex flex-col gap-8 w-[90%] md:pt-[3.125vw] pt-[13.882vw] mx-auto">

    <div class="flex flex-col gap-3 mx-auto text-center mb-[2.708vw]">
        <h5 class="text-[#1A1A1A] text-[7.6vw] md:text-[2.5vw] font-bold mb-[3.765vw] md:mb-[0.833vw] text-center leading-[1]">
            Choose the Right Plan for Your Property
        </h5>
        <p class="text-center w-full md:w-[36.438vw] text-[#1A1A1A] font-inter text-[3.765vw] md:text-[1.042vw] mx-auto">
            List your property with confidence. All plans come with verified listing approval,
            dashboard access, and the ability to boost your ad anytime.
        </p>
    </div>

    <?php get_template_part('template-parts/component', 'plan-card'); ?>

</section>

<!-- Trust Signals Strip -->
<section class="w-[90%] mx-auto md:mt-[3.125vw] mt-[13.882vw]">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

        <div class="flex flex-col items-center text-center gap-2 bg-gray-50 rounded-2xl md:p-[1.5vw] p-[6vw]">
            <span class="md:text-[2vw] text-[8vw] font-bold text-[#1A1A1A]">12k+</span>
            <span class="text-[3.2vw] md:text-[0.833vw] text-gray-500 font-inter">Active listings</span>
        </div>

        <div class="flex flex-col items-center text-center gap-2 bg-gray-50 rounded-2xl md:p-[1.5vw] p-[6vw]">
            <span class="md:text-[2vw] text-[8vw] font-bold text-[#1A1A1A]">5k+</span>
            <span class="text-[3.2vw] md:text-[0.833vw] text-gray-500 font-inter">Verified sellers</span>
        </div>

        <div class="flex flex-col items-center text-center gap-2 bg-gray-50 rounded-2xl md:p-[1.5vw] p-[6vw]">
            <span class="md:text-[2vw] text-[8vw] font-bold text-[#1A1A1A]">98%</span>
            <span class="text-[3.2vw] md:text-[0.833vw] text-gray-500 font-inter">Satisfaction rate</span>
        </div>

        <div class="flex flex-col items-center text-center gap-2 bg-gray-50 rounded-2xl md:p-[1.5vw] p-[6vw]">
            <span class="md:text-[2vw] text-[8vw] font-bold text-[#1A1A1A]">24/7</span>
            <span class="text-[3.2vw] md:text-[0.833vw] text-gray-500 font-inter">Support available</span>
        </div>

    </div>
</section>

<!-- Feature Comparison -->
<section class="w-[90%] mx-auto md:mt-[3.125vw] mt-[13.882vw]">

    <div class="flex flex-col gap-3 mx-auto text-center mb-[2.708vw]">
        <h5 class="text-[#1A1A1A] text-[7.6vw] md:text-[2.5vw] font-bold leading-[1]">
            Everything Included in Every Plan
        </h5>
        <p class="text-center w-full md:w-[36vw] text-[#1A1A1A] font-inter text-[3.765vw] md:text-[1.042vw] mx-auto">
            No hidden fees. No surprises. Just the tools you need to list and manage your properties.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <?php
        $features = [
            [
                'title'       => 'Verified Listings',
                'description' => 'Every property is reviewed for accuracy before going live, giving buyers and renters confidence.',
                'icon'        => '<path fill-rule="evenodd" clip-rule="evenodd" d="M12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21ZM11.768 15.64L16.768 9.64L15.232 8.36L10.932 13.519L8.707 11.293L7.293 12.707L10.293 15.707L11.067 16.481L11.768 15.64Z" fill="currentColor"/>',
            ],
            [
                'title'       => 'Dashboard Access',
                'description' => 'Manage, renew, edit, or upgrade your listings any time from your personal landlord dashboard.',
                'icon'        => '<rect x="3" y="3" width="7" height="7" rx="1" fill="currentColor"/><rect x="14" y="3" width="7" height="7" rx="1" fill="currentColor"/><rect x="3" y="14" width="7" height="7" rx="1" fill="currentColor"/><rect x="14" y="14" width="7" height="7" rx="1" fill="currentColor"/>',
            ],
            [
                'title'       => 'Boost Anytime',
                'description' => 'Upgrade or feature your listing at any point to push it to the top of search results.',
                'icon'        => '<path d="M13 2L3 14H12L11 22L21 10H12L13 2Z" fill="currentColor"/>',
            ],
            [
                'title'       => 'No Spam Guarantee',
                'description' => 'We manually review listings to ensure only genuine properties from verified owners appear.',
                'icon'        => '<path fill-rule="evenodd" clip-rule="evenodd" d="M12 2a10 10 0 100 20A10 10 0 0012 2zm-1 14.414l-3.707-3.707 1.414-1.414L11 13.586l5.293-5.293 1.414 1.414L11 16.414z" fill="currentColor"/>',
            ],
            [
                'title'       => 'Transparent Pricing',
                'description' => 'Pay only for what you choose. No subscription traps or automatic renewals without notice.',
                'icon'        => '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17.93V18c0-.55-.45-1-1-1s-1 .45-1 1v1.93C7.06 19.44 4.56 16.94 4.07 13H6c.55 0 1-.45 1-1s-.45-1-1-1H4.07C4.56 7.06 7.06 4.56 11 4.07V6c0 .55.45 1 1 1s1-.45 1-1V4.07C16.94 4.56 19.44 7.06 19.93 11H18c-.55 0-1 .45-1 1s.45 1 1 1h1.93c-.49 3.94-2.99 6.44-6.93 6.93z" fill="currentColor"/>',
            ],
            [
                'title'       => 'Mobile Friendly',
                'description' => 'Your listings look great on any device — phones, tablets, and desktops all fully supported.',
                'icon'        => '<path d="M17 1H7C5.9 1 5 1.9 5 3v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm-5 20c-.83 0-1.5-.67-1.5-1.5S11.17 18 12 18s1.5.67 1.5 1.5S12.83 21 12 21zm5-4H7V4h10v13z" fill="currentColor"/>',
            ],
        ];
        ?>

        <?php foreach ($features as $feature): ?>
            <div class="flex flex-col gap-3 bg-gray-50 rounded-2xl md:p-[1.5vw] p-[6vw]">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: color-mix(in srgb, var(--primary-color) 12%, transparent);">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: var(--primary-color);">
                        <?= $feature['icon']; ?>
                    </svg>
                </div>
                <h6 class="text-[4.5vw] md:text-[1.042vw] font-semibold text-[#1A1A1A] font-inter"><?= esc_html($feature['title']); ?></h6>
                <p class="text-[3.5vw] md:text-[0.833vw] text-gray-500 font-inter leading-relaxed"><?= esc_html($feature['description']); ?></p>
            </div>
        <?php endforeach; ?>

    </div>
</section>

<!-- FAQ Section -->
<section class="w-[90%] mx-auto md:mt-[3.125vw] mt-[13.882vw]" x-data="{ open: null }">

    <div class="flex flex-col gap-3 mx-auto text-center mb-[2.708vw]">
        <h5 class="text-[#1A1A1A] text-[7.6vw] md:text-[2.5vw] font-bold leading-[1]">
            Frequently Asked Questions
        </h5>
        <p class="text-center w-full md:w-[36vw] text-[#1A1A1A] font-inter text-[3.765vw] md:text-[1.042vw] mx-auto">
            Have a question? We probably answered it below.
        </p>
    </div>

    <div class="max-w-3xl mx-auto flex flex-col gap-3">

        <?php
        $faqs = [
            [
                'q' => 'Can I cancel or change my plan at any time?',
                'a' => 'Yes. You can upgrade, downgrade, or cancel from your dashboard at any time. There are no lock-in periods.',
            ],
            [
                'q' => 'How long does it take for my listing to go live?',
                'a' => 'Most listings are reviewed and approved within 24 hours. You will receive an email notification once your listing is live.',
            ],
            [
                'q' => 'What does "Featured listing" mean?',
                'a' => 'Featured listings appear at the top of search results and are highlighted with a badge, giving your property significantly more visibility.',
            ],
            [
                'q' => 'Is there a free plan?',
                'a' => 'Yes, we offer a free plan that lets you list one property with basic visibility. Paid plans unlock more listings, featured slots, and advanced analytics.',
            ],
            [
                'q' => 'What payment methods do you accept?',
                'a' => 'We accept all major credit and debit cards, as well as PayPal. All payments are processed securely.',
            ],
            [
                'q' => 'Do I need to renew manually?',
                'a' => 'Paid plans can be set to auto-renew or manual renewal — it is your choice. You will always receive a reminder before any renewal.',
            ],
        ];
        $faq_index = 0;
        ?>

        <?php foreach ($faqs as $faq): ?>
            <div
                class="border border-black/10 rounded-2xl overflow-hidden"
                x-data="{ id: <?= $faq_index ?> }"
            >
                <button
                    class="w-full flex items-center justify-between text-left md:px-[1.5vw] md:py-[1vw] px-[5vw] py-[4vw] gap-4 bg-white hover:bg-gray-50 transition-colors"
                    @click="open === id ? open = null : open = id"
                    :aria-expanded="open === id"
                >
                    <span class="text-[3.8vw] md:text-[1.042vw] font-semibold text-[#1A1A1A] font-inter"><?= esc_html($faq['q']); ?></span>
                    <svg
                        class="w-5 h-5 flex-shrink-0 transition-transform duration-200 text-gray-400"
                        :class="open === id ? 'rotate-180' : ''"
                        viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                    >
                        <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div
                    x-show="open === id"
                    x-collapse
                    class="border-t border-black/[0.06] bg-gray-50 md:px-[1.5vw] md:py-[1vw] px-[5vw] py-[4vw]"
                >
                    <p class="text-[3.5vw] md:text-[0.9vw] text-gray-500 font-inter leading-relaxed"><?= esc_html($faq['a']); ?></p>
                </div>
            </div>
            <?php $faq_index++; ?>
        <?php endforeach; ?>

    </div>
</section>

<!-- CTA Banner -->
<section class="w-[90%] mx-auto md:mt-[3.125vw] mt-[13.882vw] md:mb-[3.125vw] mb-[13.882vw]">
    <div class="rounded-3xl md:px-[4vw] md:py-[3.5vw] px-[8vw] py-[10vw] text-center flex flex-col items-center gap-5" style="background: var(--primary-color);">
        <h5 class="text-white text-[7.6vw] md:text-[2.5vw] font-bold leading-[1]">
            Ready to List Your Property?
        </h5>
        <p class="text-white/80 font-inter text-[3.765vw] md:text-[1.042vw] max-w-lg mx-auto">
            Join thousands of landlords and agents who trust us to reach the right tenants and buyers.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-3">
            <a href="<?= esc_url(home_url('/register')); ?>"
               class="inline-block bg-white font-semibold font-inter rounded-full md:px-[2vw] md:py-[0.7vw] px-[7vw] py-[3.5vw] text-[3.5vw] md:text-[0.9vw] hover:opacity-90 transition-opacity"
               style="color: var(--primary-color);">
                Get Started Free
            </a>
            <a href="<?= esc_url(home_url('/properties')); ?>"
               class="inline-block border border-white text-white font-semibold font-inter rounded-full md:px-[2vw] md:py-[0.7vw] px-[7vw] py-[3.5vw] text-[3.5vw] md:text-[0.9vw] hover:bg-white/10 transition-colors">
                Browse Properties
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
