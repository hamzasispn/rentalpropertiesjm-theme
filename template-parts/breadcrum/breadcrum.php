<?php
/**
 * Breadcrumb / page hero.
 * Image: real-estate stock photo from Unsplash (modern home exterior — free,
 * commercial-use OK). Replace with a self-hosted file in /assets/ when convenient
 * and swap the URL below.
 */
$breadcrumb_image = 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=2400&q=80';
?>
<!-- Header -->
<section
    class="relative bg-cover bg-center bg-no-repeat"
    style="background-image: linear-gradient(rgba(11,26,58,0.35), rgba(11,26,58,0.55)), url('<?php echo esc_url($breadcrumb_image); ?>');">
    <div
        class="flex pb-[7.229vw] md:pt-[16.229vw] items-start flex-col h-[40vh] justify-end md:h-[28.281vw] max-w-[90%] mx-auto">
        <?php if (is_archive()): ?>
            <h1 class="text-[8.471vw] leading-[1] md:text-[4.063vw] font-bold text-white mb-2">Find Homes and Spaces</h1>
            <p class="text-white font-bold text-[4.5vw] md:text-[2.917vw]">That Match Your Needs</p>
        <?php else: ?>
            <h1 class="text-[8.471vw] leading-[1] md:text-[4.063vw] font-bold text-white mb-2"><?php echo get_the_title(); ?></h1>
        <?php endif; ?>
    </div>
</section>