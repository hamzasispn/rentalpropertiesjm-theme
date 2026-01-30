<?php
$logo = $args['logo'] ?? '';
?>

<header class="top-0 left-0 w-full z-50 bg-white md:bg-transparent fixed lg:absolute" x-data="{ mobileOpen: false }">
    <!-- Decorative background shape -->
    <div class="hidden lg:block bg-white rounded-br-[24px] absolute top-0 left-0 w-[21.51vw] h-full -z-10"></div>
    
    <!-- Top navigation bar -->
    <nav class="w-[90%] lg:w-[80%] mx-auto flex flex-wrap items-center justify-between py-[1.875vw] lg:py-[1.615vw]">
        <!-- Logo -->
        <div class="w-[40%] sm:w-[25%] lg:w-[9.948vw] flex-shrink-0">
            <a href="<?= home_url(); ?>">
                <?php if ($logo): ?>
                    <img src="<?php echo esc_url($logo); ?>" alt="Logo" class="h-[7.5vw] sm:h-[5vw] lg:h-[60px] object-contain">
                <?php else: ?>
                    <span class="text-[6vw] sm:text-[4vw] lg:text-2xl font-bold text-slate-900">PropertyHub</span>
                <?php endif; ?>
            </a>
        </div>

        <!-- Mobile menu toggle -->
        <button class="lg:hidden flex-shrink-0 z-50 p-2  rounded bg-[var(--primary-color)]" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen" aria-label="Toggle menu">
            <svg class="w-[7vw] h-[7vw] sm:w-[5vw] sm:h-[5vw] text-white transition-transform duration-300" :style="mobileOpen ? 'transform: rotate(45deg)' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>

        <!-- Desktop navigation -->
        <div class="hidden lg:flex items-center justify-end flex-1 ml-[2vw] gap-[1.5vw]">
            <?php get_template_part('template-parts/header/component', 'main-menu'); ?>
            <?php get_template_part('template-parts/header/component', 'cta-button'); ?>
        </div>
    </nav>

    <!-- Mobile navigation menu -->
    <div class="lg:hidden absolute top-full left-0 right-0 bg-white border-t border-gray-200 z-40 max-h-[90vh] overflow-y-auto transition-all duration-300" :class="mobileOpen ? 'block' : 'hidden'">
        <div class="w-[90%] mx-auto py-[4vw] sm:py-[2.5vw]">
            <div class="mb-[3vw] sm:mb-[2vw]">
                <?php get_template_part('template-parts/header/component', 'main-menu-mobile'); ?>
            </div>
            <div class="border-t border-gray-200 pt-[3vw] sm:pt-[2vw]">
                <?php get_template_part('template-parts/header/component', 'cta-button-mobile'); ?>
            </div>
        </div>
    </div>
</header>
