<?php
/**
 * Breadcrumb / page hero.
 *
 * Uses ONLY the theme's CSS variables (--primary-color, --secondary-color,
 * --text-primary-color, --text-secondary-color) set in header.php from the
 * theme options. No standalone gold / navy / cream hex values.
 *
 * Image: Unsplash modern villa (free + commercial-use OK). Replace `$hero_image`
 * to self-host (drop a JPG in /assets/ and update path).
 */

$hero_image = get_the_template_directory_uri() . '/assets/details-section-img.jpg ';

if (is_archive()) {
    $page_title  = 'Find Homes &amp; Spaces';
    $page_kicker = 'That Match Your Needs';
} else {
    $page_title  = get_the_title();
    $page_kicker = '';
}
?>
<section class="relative isolate overflow-hidden" style="background-color: var(--primary-color);">

    <!-- ── Background image + overlays ─────────────────────────────────── -->
    <div class="absolute inset-0 -z-10">
        <img src="<?php echo esc_url($hero_image); ?>"
             alt=""
             class="w-full h-full object-cover scale-105"
             loading="eager"
             fetchpriority="high">

        <!-- Primary readability wash: theme primary, fading toward translucent -->
        <div class="absolute inset-0"
             style="background: linear-gradient(to right, color-mix(in srgb, var(--primary-color) 92%, transparent), color-mix(in srgb, var(--primary-color) 65%, transparent) 60%, color-mix(in srgb, var(--primary-color) 25%, transparent));"></div>

        <!-- Vertical depth wash so the title floats off the photo -->
        <div class="absolute inset-0"
             style="background: linear-gradient(to bottom, color-mix(in srgb, var(--primary-color) 30%, transparent), transparent 40%, color-mix(in srgb, var(--primary-color) 45%, transparent));"></div>

        <!-- Decorative grid texture for atmosphere -->
        <div class="absolute inset-0 opacity-[0.07] pointer-events-none"
             style="background-image:
                linear-gradient(rgba(255,255,255,1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,1) 1px, transparent 1px);
                background-size: 64px 64px;"></div>
    </div>

    <!-- ── Content ─────────────────────────────────────────────────────── -->
    <div class="relative max-w-[90%] mx-auto pt-[24vw] pb-[10vw] md:pt-[15vw] md:pb-[7vw] lg:pt-44 lg:pb-24">

        <!-- Eyebrow with thin rule -->
        <div class="flex items-center gap-3 mb-5 md:mb-7">
            <span class="block w-10 md:w-14 h-px bg-white/70"></span>
            <span class="text-white/85 text-[2.5vw] md:text-[0.72vw] tracking-[0.32em] font-semibold uppercase">
                RentalPropertiesJM
            </span>
        </div>

        <!-- Title -->
        <h1 class="text-white font-bold tracking-tight leading-[0.95] text-[10vw] md:text-[4.2vw] max-w-[22ch]">
            <?php echo wp_kses_post($page_title); ?>
        </h1>

        <?php if ($page_kicker): ?>
            <p class="text-white/85 font-medium mt-3 md:mt-4 text-[4.5vw] md:text-[1.65vw] max-w-[40ch]">
                <?php echo esc_html($page_kicker); ?>
            </p>
        <?php endif; ?>

        <!-- Accent bar below title -->
        <div class="mt-6 md:mt-8 h-[3px] w-16 md:w-24 bg-white rounded-full"></div>

        <!-- Breadcrumb trail -->
        <?php if (!is_front_page() && !is_archive()): ?>
        <nav aria-label="Breadcrumb"
             class="mt-6 md:mt-10 flex items-center gap-2 text-white/75 text-[3vw] md:text-[0.82vw] font-medium tracking-wide">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-white transition-colors">
                Home
            </a>
            <svg class="w-3 h-3 md:w-3.5 md:h-3.5 text-white/50 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-white"><?php echo esc_html(get_the_title()); ?></span>
        </nav>
        <?php endif; ?>
    </div>
</section>
