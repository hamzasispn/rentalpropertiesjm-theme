<?php
/**
 * Main template file for the property listing theme
 * Template Name: About Page
 *
 * Layout per May 22 client brief:
 *   1. "Who We Are" — editorial hero with image collage + pull quote (NEW).
 *   2. "Why Property Owners and Buyers Trust Us?" (kept on /about, removed from /home).
 *   - Removed: "Find the Right Property for You" (was section-about-us — repetitive).
 *
 * Palette: uses ONLY the theme's CSS variables (--primary-color, --secondary-color,
 * --text-primary-color, --text-secondary-color). No standalone hex values — keeps
 * brand consistent with the rest of the site and respects whatever the admin sets
 * in Theme Options.
 */
get_header();

$wwa_primary_image   = 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1600&q=80';
$wwa_secondary_image = 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=900&q=80';
?>

<!-- ════════════════════════════════════════════════════════════════════
     Who We Are — editorial hero
     ════════════════════════════════════════════════════════════════════ -->
<section class="relative bg-slate-50 overflow-hidden">

    <!-- Decorative ring (top right) — uses theme primary -->
    <div class="hidden md:block absolute -top-20 -right-40 w-[480px] h-[480px] rounded-full pointer-events-none"
         style="border: 1px solid color-mix(in srgb, var(--primary-color) 18%, transparent);"></div>

    <!-- Decorative dotted grid (bottom left) — theme primary at low opacity -->
    <div class="hidden md:block absolute -bottom-10 -left-10 w-72 h-72 opacity-30 pointer-events-none"
         style="background-image: radial-gradient(circle, var(--primary-color) 1px, transparent 1px); background-size: 18px 18px;"></div>

    <div class="relative max-w-[1280px] mx-auto px-6 md:px-10 lg:px-12 py-20 md:py-28 lg:py-32">

        <!-- Eyebrow -->
        <div class="flex items-center gap-3 mb-5">
            <span class="block w-12 h-px" style="background-color: var(--primary-color);"></span>
            <span class="text-xs tracking-[0.32em] font-semibold uppercase" style="color: var(--primary-color);">About RentalPropertiesJM</span>
        </div>

        <div class="grid lg:grid-cols-12 gap-12 lg:gap-16 items-start">

            <!-- ─── LEFT: image collage ─────────────────────────────────── -->
            <div class="lg:col-span-5 relative">

                <!-- Primary image, portrait aspect -->
                <div class="relative aspect-[4/5] rounded-2xl overflow-hidden"
                     style="box-shadow: 0 30px 80px -30px color-mix(in srgb, var(--primary-color) 50%, transparent);">
                    <img src="<?php echo esc_url($wwa_primary_image); ?>"
                         alt="Modern rental home"
                         loading="lazy"
                         class="w-full h-full object-cover">
                    <!-- subtle theme-tinted wash for cohesion -->
                    <div class="absolute inset-0"
                         style="background: linear-gradient(to top, color-mix(in srgb, var(--primary-color) 30%, transparent), transparent 60%);"></div>
                </div>

                <!-- Secondary image, offset, hidden on mobile -->
                <div class="hidden md:block absolute -bottom-10 -right-8 w-[58%] aspect-[5/4] rounded-2xl overflow-hidden border-[6px] border-slate-50"
                     style="box-shadow: 0 20px 50px -20px color-mix(in srgb, var(--primary-color) 45%, transparent);">
                    <img src="<?php echo esc_url($wwa_secondary_image); ?>"
                         alt="Family touring a home"
                         loading="lazy"
                         class="w-full h-full object-cover">
                </div>

                <!-- Floating accent card (theme primary background) -->
                <div class="hidden lg:block absolute -top-6 -left-8 text-white p-5 rounded-xl max-w-[200px]"
                     style="background-color: var(--primary-color); box-shadow: 0 18px 40px -18px color-mix(in srgb, var(--primary-color) 55%, transparent);">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2l2.39 7.36H22l-6.18 4.49L18.21 22 12 17.27 5.79 22l2.39-8.15L2 9.36h7.61L12 2z"/>
                        </svg>
                        <span class="text-white text-[10px] tracking-[0.2em] uppercase font-bold">Built for Jamaica</span>
                    </div>
                    <p class="text-xs leading-relaxed text-white/85">
                        Local listings, local trust — designed for landlords and seekers who know the island.
                    </p>
                </div>
            </div>


            <!-- ─── RIGHT: copy ─────────────────────────────────────────── -->
            <div class="lg:col-span-7 lg:pl-4">

                <h2 class="font-bold tracking-tight leading-[1.05] text-4xl md:text-5xl lg:text-[3.4rem] mb-6"
                    style="color: var(--primary-color);">
                    The smarter way to connect
                    <span class="relative inline-block">
                        <span class="relative z-10 italic font-serif" style="color: var(--primary-color);">property</span>
                        <span class="absolute left-0 right-0 bottom-1 h-3 -z-0"
                              style="background-color: color-mix(in srgb, var(--primary-color) 18%, transparent);"></span>
                    </span>
                    with people.
                </h2>

                <!-- Lead paragraph with drop-cap (theme primary) -->
                <p class="text-base md:text-lg leading-relaxed mb-5
                          first-letter:float-left first-letter:mr-2 first-letter:text-5xl first-letter:font-bold first-letter:leading-none first-letter:mt-1"
                   style="color: color-mix(in srgb, var(--primary-color) 88%, #000);">
                    <span style="--tw-first-letter-color: var(--primary-color);"></span>
                    RentalPropertiesJM is the smarter way to connect property with people. We bring landlords,
                    sellers, renters, and buyers together in one dedicated space — built around a simple idea:
                    real estate decisions move faster when the right people meet at the right moment.
                </p>

                <p class="text-base leading-relaxed mb-6"
                   style="color: color-mix(in srgb, var(--primary-color) 78%, #fff);">
                    Landlords can list rental properties for
                    <strong style="color: var(--primary-color);">free</strong>,
                    and sellers and landlords looking for greater visibility can upgrade to
                    <strong style="color: var(--primary-color);">featured listings at competitive rates</strong>
                    that put their property in front of an audience already searching, comparing, and
                    ready to act. Instead of chasing cold audiences scrolling past your post on social media,
                    reach people in active decision-making mode — exactly when it matters most.
                </p>

                <!-- Pull quote — theme primary left rule + serif italic -->
                <blockquote class="relative my-8 pl-6"
                            style="border-left: 3px solid var(--primary-color);">
                    <svg class="absolute -left-2 -top-3 w-6 h-6" viewBox="0 0 24 24" fill="currentColor"
                         style="color: color-mix(in srgb, var(--primary-color) 35%, transparent);">
                        <path d="M9.13 9C9.6 8 10.5 7 12 6.5V4c-3.5.5-6 3.5-6 7v6h6V9H9.13zM21 9V6.5C17.5 7 15 10 15 13.5V19h6V9h-3.87z"/>
                    </svg>
                    <p class="text-lg md:text-xl font-medium italic leading-snug"
                       style="color: var(--primary-color);">
                        Real estate decisions move faster when the right people meet at the right moment.
                    </p>
                </blockquote>

                <p class="text-base leading-relaxed mb-5"
                   style="color: color-mix(in srgb, var(--primary-color) 78%, #fff);">
                    Finding the right property is only half the journey — that's why we've built a growing
                    library of guides, market insights, and practical resources to support every step of the
                    process in our Blog and Resources section. Whether you're a first-time renter weighing
                    your options, a buyer preparing for the biggest purchase of your life, or a landlord
                    looking to attract better tenants, you'll find clear, trustworthy information designed
                    to help you move forward with confidence.
                </p>

                <p class="text-base leading-relaxed mb-10"
                   style="color: color-mix(in srgb, var(--primary-color) 78%, #fff);">
                    We're here to make property simpler, smarter, and a whole lot less stressful —
                    for everyone involved.
                </p>

                <!-- Three-pillar grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-8"
                     style="border-top: 1px solid color-mix(in srgb, var(--primary-color) 12%, transparent);">
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--primary-color);"></span>
                            <p class="text-[10px] tracking-[0.18em] uppercase font-bold" style="color: var(--primary-color);">Free to list</p>
                        </div>
                        <p class="text-sm leading-snug" style="color: color-mix(in srgb, var(--primary-color) 70%, #fff);">
                            Landlords list rentals at zero cost. Upgrade only when you want reach.
                        </p>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--primary-color);"></span>
                            <p class="text-[10px] tracking-[0.18em] uppercase font-bold" style="color: var(--primary-color);">Verified</p>
                        </div>
                        <p class="text-sm leading-snug" style="color: color-mix(in srgb, var(--primary-color) 70%, #fff);">
                            Every listing reviewed before going live — no spam, no fakes.
                        </p>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--primary-color);"></span>
                            <p class="text-[10px] tracking-[0.18em] uppercase font-bold" style="color: var(--primary-color);">Ready to act</p>
                        </div>
                        <p class="text-sm leading-snug" style="color: color-mix(in srgb, var(--primary-color) 70%, #fff);">
                            Reach people in active decision-making mode — not a scrolling audience.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>


<!-- ════════════════════════════════════════════════════════════════════
     Why Choose Us — kept on /about per client brief
     ════════════════════════════════════════════════════════════════════ -->
<?php get_template_part('template-parts/sections/section', 'why-choose-us'); ?>

<?php get_footer(); ?>
