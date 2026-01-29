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
    <!-- <div class="absolute bottom-[-6%] left-1/2 transform -translate-x-1/2 bg-white rounded-[16px] shadow-xl w-[68.958vw]"
        x-data="searchComponent()">
        <div class="flex items-stretch">
            <div
                class="border-r border-solid border-[#1a1a1a5c] pl-[32px] pr-[28px] py-[19px] w-[22.5%]">
                <label class="block text-[1.042vw] font-semibold text-[var(--text-primary-color)] mb-[1.354vw]">Search</label>
                <input type="text" x-model="filters.search" placeholder="Location or keyword..."
                    class="w-full text-[var(--text-primary-color)] font-inter outline-none">
            </div>
            <div
                class="border-r border-solid border-[#1a1a1a5c] px-[28px] py-[19px] w-[22.5%] outline-none">
                <label class="block text-[1.042vw] font-semibold text-[var(--text-primary-color)] mb-[1.354vw]">Property
                    Type</label>
                <select x-model="filters.property_type"
                    class="w-full text-slate-900 font-inter">
                    <option value="">All Types</option>
                    <option value="house">House</option>
                    <option value="apartment">Apartment</option>
                    <option value="commercial">Commercial</option>
                </select>
            </div>
            <div
                class="px-[28px] py-[19px] w-[22.5%]">
                <label class="block text-[1.042vw] font-semibold text-[var(--text-primary-color)] mb-[1.354vw]">Bedrooms</label>
                <select x-model="filters.bedrooms"
                    class="w-full text-slate-900 font-[var(--secondary-font)] font-inter outline-none">
                    <option value="">Any</option>
                    <option value="1">1+</option>
                    <option value="2">2+</option>
                    <option value="3">3+</option>
                    <option value="4">4+</option>
                </select>
            </div>
            <div class="bg-[var(--primary-color)] border-r-[0.6px] border-solid border-[var(--text-secondary-color)] w-[10%]">
                <button @click="search()" class="w-full h-full text-white flex items-center justify-center">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 8H8V14H6V8H0V6H6V0H8V6H14V8Z" fill="white" />
                    </svg>
                </button>
            </div>
            <div class="bg-[var(--primary-color)] flex items-center justify-center w-[22.5%] rounded-r-[24px]">
                <button @click="search()" class="w-full h-full text-white text-[1.25vw] font-semibold !font-[var(--secondary-font)]">
                    Search
                </button>
            </div>
        </div>
    </div> -->
</section>