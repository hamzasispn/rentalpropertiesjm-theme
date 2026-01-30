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
    <div class="absolute bg-white rounded-tl-[24px] bottom-0 right-0 w-[30.719vw] h-[8.75vw]"></div>
    <div class="w-[80%] mx-auto flex items-center justify-between">
        <div class="heroContent max-w-[80%] text-[var(--text-secondary-color)]">
            <h1 class="text-[3.563vw] font-bold leading-none"><?= esc_html($heroTitle); ?>
                <h2 class="text-[2.7vw] font-semibold"><?= esc_html($heroSubtitle); ?></h2>
            </h1>
            <div class="text-[0.833vw] !mb-[2.083vw] max-w-[34.063vw]"><?= $heroDesc ?></div>
            <div class="flex items-center gap-[1.25vw] mb-[2.5vw]">
                <?php if ($ctaOne): ?>
                    <a href="<?= esc_url($ctaOne['url']); ?>" class="btn-primary"><?= esc_html($ctaOne['title']); ?></a>
                <?php endif; ?>
                <?php if ($ctaTwo): ?>
                    <a href="<?= esc_url($ctaTwo['url']); ?>" class="btn-secondary"><?= esc_html($ctaTwo['title']); ?></a>
                <?php endif; ?>
            </div>
            <p class="text-[1.041vw] !mb-[2.083vw]"><?= $heroExc ?></p>
        </div>
    </div>



    <!-- Search Bar -->
    <?php get_template_part('template-parts/component', 'filter-search'); ?>
</section>