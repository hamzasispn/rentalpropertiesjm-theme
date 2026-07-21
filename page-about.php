<?php
/**
 * Template Name: About Page
 *
 * Both sections use the same container width so the top prose and the
 * bottom "Why Choose Us" block share the same left/right edges — no more
 * misaligned rails that made the page look pushed to the right.
 */
get_header();
?>

<!-- Who We Are — same width + left edge as the section below -->
<section class="bg-slate-50">
    <div class="w-[90%] max-w-6xl mx-auto py-16 md:py-24">

        <h2 class="font-bold text-slate-900 text-3xl md:text-4xl mb-8 text-left">
            Who We Are
        </h2>

        <div class="space-y-5 text-slate-700 text-base md:text-lg leading-relaxed max-w-3xl text-left font-inter">
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

<div class="w-[90%] max-w-6xl mx-auto">
    <?php get_template_part('template-parts/sections/section', 'why-choose-us'); ?>
</div>

<?php get_footer(); ?>
