<nav class="hidden lg:flex items-center gap-[0.938vw]">
    <?php 
    $menu_name = 'main_menu';
    $locations = get_nav_menu_locations();
    $menu = wp_get_nav_menu_object( $locations[ $menu_name ] );
    $menu_items = wp_get_nav_menu_items( $menu->term_id, array(
        'orderby' => 'menu_order',
        'order'   => 'ASC',
    ) );

    if ($menu_items) {
        foreach ($menu_items as $item) {
            if ($item->menu_item_parent == 0) {
                $children = array_filter($menu_items, function($child) use ($item) {
                    return (int) $child->menu_item_parent === (int) $item->db_id;
                });
                $has_children = !empty($children);
                ?>
                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <a href="<?= esc_url($item->url); ?>" 
                       class="text-[0.833vw] font-medium <?php echo is_singular('property') ? 'text-[var(--primary-color)]' : 'text-white'; ?>  flex items-center gap-[0.4vw]">
                        <?= esc_html($item->title); ?>
                        <?php if ($has_children): ?>
                            <svg class="w-[0.5vw] h-[0.5vw] transition-transform" :style="open && 'transform: rotate(180deg)'" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        <?php endif; ?>
                    </a>

                    <?php if ($has_children): ?>
                    <div class="absolute top-full left-0 bg-white border border-gray-100 rounded-lg shadow-2xl py-[1vw] px-[1.2vw] min-w-[15vw] z-40 opacity-0 invisible transition-all duration-200" :class="open && '!opacity-100 !visible'">
                        <div class="grid gap-[0.5vw]">
                            <?php foreach ($children as $child): ?>
                                <a href="<?= esc_url($child->url); ?>" 
                                   class="block px-[0.8vw] py-[0.6vw] text-[0.95vw] text-slate-600 hover:text-slate-900 hover:bg-gray-50 rounded transition-colors whitespace-nowrap">
                                    <?= esc_html($child->title); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php
            }
        }
    } else {
        ?>
        <a href="#" class="text-[1.04vw] font-medium text-slate-700 hover:text-slate-900 py-[1vw] px-[0.8vw] transition-colors">Buy</a>
        <a href="#" class="text-[1.04vw] font-medium text-slate-700 hover:text-slate-900 py-[1vw] px-[0.8vw] transition-colors">Sell</a>
        <a href="#" class="text-[1.04vw] font-medium text-slate-700 hover:text-slate-900 py-[1vw] px-[0.8vw] transition-colors">Rent</a>
        <a href="#" class="text-[1.04vw] font-medium text-slate-700 hover:text-slate-900 py-[1vw] px-[0.8vw] transition-colors">About</a>
        <?php
    }
    ?>
</nav>
