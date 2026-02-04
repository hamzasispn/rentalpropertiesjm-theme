<?php
/**
 * Main template file for the property listing theme
 * Template Name: About Page
 */
get_header();
?>

<!-- Why Choose Us Section -->
<?php get_template_part('template-parts/sections/section', 'why-choose-us'); ?>

<!-- About Us Section -->
<?php get_template_part('template-parts/sections/section', 'about-us'); ?>

<?php get_footer(); ?>