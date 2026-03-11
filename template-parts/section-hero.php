<?php
$bgImg = get_field('hero_sec_bg');
$heroTitle = get_field('hero_sec_title');
$heroSubtitle = get_field('hero_sec_subtitle');
$heroDesc = get_field('hero_sec_desc');
$ctaOne = get_field('hero_sec_cta');
$ctaTwo = get_field('hero_sec_cta_two');
$heroExc = get_field('hero_sec_excerpt');
?>

<section class="heroSec h-screen flex items-center bg-cover bg-center relative"
    style="background-image: url('<?= esc_url($bgImg['url']) ?>');">
    <div class="absolute bg-white rounded-tl-[24px] bottom-0 right-0 w-[30.719vw] md:h-[8.75vw] h-[25vw]"></div>
    <div class="w-[80%] mx-auto flex items-center justify-between">
        <div class="heroContent w-full md:max-w-[80%] text-[var(--text-secondary-color)]">
            <h1 class="text-[10.353vw] md:text-[3.163vw] font-bold leading-none"><?= esc_html($heroTitle); ?>
                <h2 class="text-[8.176vw] md:text-[2.1vw] font-semibold leading-none md:leading-normal">
                    <?= esc_html($heroSubtitle); ?></h2>
            </h1>
            <div class="text-[3.765vw] md:text-[0.833vw] !mb-[2.083vw] md:max-w-[34.063vw] max-w-full my-[4vw] md:my-0">
                <?= $heroDesc ?></div>
            <div class="flex md:flex-row flex-col items-center gap-[3.765vw] md:gap-[1.25vw] mb-[2.5vw]">
                <?php if ($ctaOne): ?>
                    <a href="<?= esc_url($ctaOne['url']); ?>"
                        class="btn-primary block md:inline-block md:w-auto w-full text-center"><?= esc_html($ctaOne['title']); ?></a>
                <?php endif; ?>
                <?php if ($ctaTwo): ?>
                    <a href="<?= esc_url($ctaTwo['url']); ?>"
                        class="btn-secondary hidden md:inline-block md:w-auto w-full text-center"><?= esc_html($ctaTwo['title']); ?></a>
                <?php endif; ?>

                <!-- Search Bar -->
                <?php get_template_part('template-parts/filters/component', 'filter-mobile'); ?>
            </div>
            <p class="text-[3.765vw] md:text-[1.041vw] !mb-[2.083vw]"><?= $heroExc ?></p>
        </div>
    </div>



    <!-- Search Bar -->
    <?php get_template_part('template-parts/component', 'filter-search'); ?>
</section>