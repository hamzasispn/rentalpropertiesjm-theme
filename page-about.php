<?php
/**
 * Main template file for the property listing theme
 * Template Name: About Page
 *
 * Layout per May 22 client brief:
 *   1. "Who We Are" intro (new — defined inline below; pulls breadcrumb from header.php).
 *   2. "Why Property Owners and Buyers Trust Us?" (kept on /about, removed from /home).
 *   - Removed: "Find the Right Property for You" (was section-about-us — repetitive).
 */
get_header();
?>

<!-- ─── Who We Are ─────────────────────────────────────────────── -->
<section class="w-[90%] mx-auto md:pt-[3.125vw] pt-[13.882vw]">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-[#1A1A1A] text-[7.6vw] md:text-[2.5vw] font-bold mb-6 leading-tight">
            Who We Are
        </h2>

        <div class="flex flex-col gap-4 text-[#1A1A1A] font-inter text-[4vw] md:text-[1.05vw] leading-relaxed">
            <p>
                RentalPropertiesJM is the smarter way to connect property with people. We bring landlords,
                sellers, renters, and buyers together in one dedicated space — built around a simple idea:
                real estate decisions move faster when the right people meet at the right moment.
            </p>

            <p>
                Landlords can list rental properties for <strong>free</strong>, and sellers and landlords
                looking for greater visibility can upgrade to <strong>featured listings at competitive
                rates</strong> that put their property in front of an audience already searching, comparing,
                and ready to act. Instead of chasing cold audiences scrolling past your post on social
                media, reach people in active decision-making mode, exactly when it matters most.
            </p>

            <p>
                Finding the right property is only half the journey — that's why we've built a growing
                library of guides, market insights, and practical resources to support every step of the
                process in our Blog and Resources section. Whether you're a first-time renter weighing
                your options, a buyer preparing for the biggest purchase of your life, or a landlord
                looking to attract better tenants, you'll find clear, trustworthy information designed
                to help you move forward with confidence.
            </p>

            <p>
                We're here to make property simpler, smarter, and a whole lot less stressful — for
                everyone involved.
            </p>
        </div>
    </div>
</section>

<!-- ─── Why Choose Us Section (kept on /about only) ────────────── -->
<?php get_template_part('template-parts/sections/section', 'why-choose-us'); ?>

<?php get_footer(); ?>
