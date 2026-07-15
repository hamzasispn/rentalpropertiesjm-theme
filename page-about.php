<?php
/**
 * Main template file for the property listing theme
 * Template Name: About Page
 *
 * Layout per May 22 + follow-up briefs:
 *   1. "Who We Are" — clean single-column editorial copy, no image collage,
 *       no pillar grid, no floating card. Just typography.
 *   2. "Why Property Owners and Buyers Trust Us?" (kept on /about only).
 *
 * Uses theme CSS variables (--primary-color, etc.) — no standalone hex values.
 */
get_header();
?>

<!-- ════════════════════════════════════════════════════════════════════
     Who We Are — content-only editorial
     ════════════════════════════════════════════════════════════════════ -->
<section class="relative bg-slate-50">
    <div class="max-w-[880px] mx-auto px-6 md:px-8 py-16 md:py-24">

        <!-- Eyebrow -->
        <div class="flex items-center gap-3 mb-5">
            <span class="block w-12 h-px" style="background-color: var(--primary-color);"></span>
            <span class="text-xs tracking-[0.32em] font-semibold uppercase" style="color: var(--primary-color);">About RentalPropertiesJM</span>
        </div>

        <!-- Headline -->
        <h2 class="font-bold tracking-tight leading-[1.05] text-4xl md:text-5xl lg:text-[3.4rem] mb-8"
            style="color: var(--primary-color);">
            The smarter way to connect
            <span class="relative inline-block">
                <span class="relative z-10 italic font-serif" style="color: var(--primary-color);">property</span>
                <span class="absolute left-0 right-0 bottom-1 h-3 -z-0"
                      style="background-color: color-mix(in srgb, var(--primary-color) 18%, transparent);"></span>
            </span>
            with people.
        </h2>

        <!-- Body copy -->
        <div class="space-y-5">
            <p class="text-base md:text-lg leading-relaxed
                      first-letter:float-left first-letter:mr-2 first-letter:text-5xl first-letter:font-bold first-letter:leading-none first-letter:mt-1"
               style="color: color-mix(in srgb, var(--primary-color) 88%, #000);">
                RentalPropertiesJM is the smarter way to connect property with people. We bring landlords,
                sellers, renters, and buyers together in one dedicated space — built around a simple idea:
                real estate decisions move faster when the right people meet at the right moment.
            </p>

            <p class="text-base leading-relaxed"
               style="color: color-mix(in srgb, var(--primary-color) 78%, #fff);">
                Landlords can list rental properties for
                <strong style="color: var(--primary-color);">free</strong>,
                and sellers and landlords looking for greater visibility can upgrade to
                <strong style="color: var(--primary-color);">featured listings at competitive rates</strong>
                that put their property in front of an audience already searching, comparing, and
                ready to act. Instead of chasing cold audiences scrolling past your post on social media,
                reach people in active decision-making mode — exactly when it matters most.
            </p>

            <!-- Pull quote -->
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

            <p class="text-base leading-relaxed"
               style="color: color-mix(in srgb, var(--primary-color) 78%, #fff);">
                Finding the right property is only half the journey — that's why we've built a growing
                library of guides, market insights, and practical resources to support every step of the
                process in our Blog and Resources section. Whether you're a first-time renter weighing
                your options, a buyer preparing for the biggest purchase of your life, or a landlord
                looking to attract better tenants, you'll find clear, trustworthy information designed
                to help you move forward with confidence.
            </p>

            <p class="text-base leading-relaxed"
               style="color: color-mix(in srgb, var(--primary-color) 78%, #fff);">
                We're here to make property simpler, smarter, and a whole lot less stressful —
                for everyone involved.
            </p>
        </div>
    </div>
</section>

<!-- Why Choose Us Section (kept on /about only) -->
<?php get_template_part('template-parts/sections/section', 'why-choose-us'); ?>

<?php get_footer(); ?>
