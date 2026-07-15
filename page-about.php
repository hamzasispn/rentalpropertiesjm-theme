<?php
/**
 * Main template file for the property listing theme
 * Template Name: About Page
 *
 * Simple content-only layout per client:
 *   - Plain "Who We Are" heading (no eyebrow, no italic, theme font)
 *   - Four paragraphs of copy exactly as provided
 *   - "Why Property Owners and Buyers Trust Us?" kept below
 */
get_header();
?>

<!-- Who We Are -->
<section class="bg-slate-50">
    <div class="max-w-[880px] mx-auto px-6 md:px-8 py-16 md:py-24">

        <h2 class="font-bold text-slate-900 text-3xl md:text-4xl mb-8">
            Who We Are
        </h2>

        <div class="space-y-5 text-slate-700 text-base md:text-lg leading-relaxed">
            <p>
                RentalPropertiesJM is the smarter way to connect property with people. We bring
                landlords, sellers, renters, and buyers together in one dedicated space — built
                around a simple idea: real estate decisions move faster when the right people
                meet at the right moment.
            </p>

            <p>
                Landlords can list rental properties for free, and sellers and landlords looking
                for greater visibility can upgrade to featured listings at competitive rates that
                put their property in front of an audience already searching, comparing, and ready
                to act. Instead of chasing cold audiences scrolling past your post on social media,
                reach people in active decision-making mode, exactly when it matters most.
            </p>

            <p>
                Finding the right property is only half the journey — that's why we've built a
                growing library of guides, market insights, and practical resources to support
                every step of the process in our Blog and Resources section. Whether you're a
                first-time renter weighing your options, a buyer preparing for the biggest
                purchase of your life, or a landlord looking to attract better tenants, you'll
                find clear, trustworthy information designed to help you move forward with
                confidence.
            </p>

            <p>
                We're here to make property simpler, smarter, and a whole lot less stressful —
                for everyone involved.
            </p>
        </div>
    </div>
</section>

<!-- Why Choose Us Section (kept on /about only) -->
<?php get_template_part('template-parts/sections/section', 'why-choose-us'); ?>

<?php get_footer(); ?>
