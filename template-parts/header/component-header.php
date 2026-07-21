<?php
$logo = $args['logo'] ?? '';
?>

<header class="top-0 left-0 w-full z-50 bg-white md:bg-transparent fixed lg:absolute" x-data="{ mobileOpen: false }">
    <!-- Decorative background shape -->
    <div class="hidden lg:block bg-white rounded-br-[24px] absolute top-0 left-0 w-[21.51vw] h-full -z-10"></div>
    
    <!-- Top navigation bar -->
    <nav class="w-[90%] lg:w-[80%] mx-auto flex flex-wrap items-center justify-between py-[1.875vw] lg:py-[1.615vw]">
        <!-- Logo (bigger per client feedback) -->
        <div class="w-[40%] sm:w-[25%] lg:w-[13vw] flex-shrink-0">
            <a href="<?= home_url(); ?>">
                <?php if ($logo): ?>
                    <img src="<?php echo esc_url($logo); ?>" alt="Logo" class="h-[9vw] sm:h-[6vw] lg:h-[80px] object-contain">
                <?php else: ?>
                    <span class="text-[6vw] sm:text-[4vw] lg:text-2xl font-bold text-slate-900">PropertyHub</span>
                <?php endif; ?>
            </a>
        </div>

        <!-- Mobile login / account button (always visible) -->
        <?php if (!is_user_logged_in()): ?>
        <a href="<?= home_url('/login'); ?>" class="lg:hidden flex-shrink-0 flex items-center gap-1.5 px-3 py-2 rounded bg-[var(--primary-color)] text-white text-[3.5vw] sm:text-[2.5vw] font-bold" style="white-space:nowrap">
            <svg class="w-[4vw] h-[4vw] sm:w-[3vw] sm:h-[3vw]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                <polyline points="10 17 15 12 10 7"/>
                <line x1="15" y1="12" x2="3" y2="12"/>
            </svg>
            Sign in
        </a>
        <?php else:
            $mobile_user  = wp_get_current_user();
            $mobile_first = strtok(trim($mobile_user->display_name ?: $mobile_user->user_login), ' ');
            $mobile_url   = function_exists('pt_get_user_home_url')
                ? pt_get_user_home_url($mobile_user->ID)
                : home_url('/my-account/');
        ?>
        <a href="<?= esc_url($mobile_url); ?>"
           class="lg:hidden flex-shrink-0 flex items-center gap-1.5 px-3 py-2 rounded bg-[var(--primary-color)] text-white text-[3.5vw] sm:text-[2.5vw] font-bold"
           style="white-space:nowrap">
            <span class="w-[5vw] h-[5vw] sm:w-[3.5vw] sm:h-[3.5vw] rounded-full bg-white text-[var(--primary-color)] flex items-center justify-center text-[3vw] sm:text-[2vw] font-bold">
                <?= esc_html(strtoupper(mb_substr($mobile_first ?: 'U', 0, 1))); ?>
            </span>
            <?= esc_html($mobile_first); ?>
        </a>
        <?php endif; ?>

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
