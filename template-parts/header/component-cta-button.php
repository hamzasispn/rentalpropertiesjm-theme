<div class="hidden lg:flex gap-[1vw] items-center">

    <?php
    $current_user      = wp_get_current_user();
    $is_property_page  = is_singular('property');
    $user_subscription = is_user_logged_in()
        ? property_theme_get_user_subscription($current_user->ID)
        : null;

    // Guest → /login, member → /pricing, agent → /dashboard/#add-property
    $list_url = function_exists('pt_get_list_property_url')
        ? pt_get_list_property_url()
        : home_url('/login');
    ?>

    <?php if (is_user_logged_in()): ?>
        <?php
        $display    = trim($current_user->display_name ?: $current_user->user_login);
        $first_word = strtok($display, ' ');
        $initial    = strtoupper(mb_substr($first_word ?: 'U', 0, 1));
        // Route the badge to whichever home this user actually has.
        $badge_url  = function_exists('pt_get_user_home_url')
            ? pt_get_user_home_url($current_user->ID)
            : home_url('/my-account/');
        ?>

        <!-- Clear "you are signed in" badge with name + initial avatar -->
        <a href="<?= esc_url($badge_url); ?>" title="<?= esc_attr($display); ?>"
           class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full transition
                  <?= $is_property_page ? 'bg-white/90 text-slate-900 hover:bg-white' : 'bg-white/15 backdrop-blur-[10px] text-white hover:bg-white/25'; ?>">
            <span class="w-[2vw] h-[2vw] min-w-[36px] min-h-[36px] rounded-full flex items-center justify-center text-[0.95vw] font-bold text-white"
                  style="background:var(--primary-color);">
                <?= esc_html($initial); ?>
            </span>
            <span class="text-[0.9vw] font-semibold hidden xl:inline">
                Hi, <?= esc_html($first_word); ?>
            </span>
        </a>

        <a href="<?= esc_url($list_url); ?>"
           class="btn-primary px-[1.2vw] py-[0.6vw] text-[1vw] font-semibold rounded-lg transition-all hover:shadow-lg">
            List Property Now
        </a>

    <?php else: ?>
        <a href="<?= home_url('/login'); ?>"
           class="flex items-center gap-2 px-3 py-2 rounded-full transition
                  <?= $is_property_page ? 'bg-[var(--primary-color)] text-white' : 'bg-white/15 backdrop-blur-[10px] text-white hover:bg-white/25'; ?>">
            <svg class="w-[1vw] h-[1vw]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/>
            </svg>
            <span class="text-[0.9vw] font-semibold">Sign in</span>
        </a>

        <a href="<?= home_url('/register'); ?>"
           class="btn-primary px-[1.2vw] py-[0.6vw] text-[1vw] font-semibold rounded-lg transition-all hover:shadow-lg">
            Create an account
        </a>
    <?php endif; ?>
</div>
