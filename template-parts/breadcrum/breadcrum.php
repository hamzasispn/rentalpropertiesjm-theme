<!-- Header -->
<section
    style="background: url('<?php echo get_template_directory_uri(); ?>/assets/archive-image.jpg') top/cover no-repeat;">
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