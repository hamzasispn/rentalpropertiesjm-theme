<nav class="lg:hidden" x-data="{ openItem: null }">
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
                    return $child->menu_item_parent == $item->ID;
                });
                $has_children = !empty($children);
                $item_id = 'menu-' . $item->ID;
                ?>
                <div class="mb-[2vw] sm:mb-[1.5vw]">
                    <button class="flex items-center justify-between w-full p-[2vw] text-[4.5vw] sm:text-[3.5vw] font-semibold text-white rounded bg-[var(--primary-color)]" 
                            @click="openItem = openItem === '<?= $item_id ?>' ? null : '<?= $item_id ?>'"
                            :aria-expanded="openItem === '<?= $item_id ?>'">
                        <span><?= esc_html($item->title); ?></span>
                        <?php if ($has_children): ?>
                            <svg class="w-[5vw] h-[5vw] sm:w-[4vw] sm:h-[4vw] transition-transform" :style="openItem === '<?= $item_id ?>' && 'transform: rotate(180deg)'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        <?php endif; ?>
                    </button>

                    <?php if ($has_children): ?>
                    <div class="ml-[2vw] sm:ml-[1vw] border-l-2 border-gray-200 pl-[2vw] sm:pl-[1.5vw] overflow-hidden transition-all duration-200" :class="openItem === '<?= $item_id ?>' ? 'block' : 'hidden'">
                        <?php foreach ($children as $child): ?>
                            <a href="<?= esc_url($child->url); ?>" 
                               class="block py-[1.8vw] sm:py-[1.2vw] px-[1.5vw] text-[3.8vw] sm:text-[3vw] text-slate-600 hover:text-slate-900 hover:bg-gray-50 rounded transition-colors">
                                <?= esc_html($child->title); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php
            }
        }
    } else {
        ?>
        <div class="mb-[2vw]">
            <a href="#" class="block p-[2vw] text-[4.5vw] font-semibold text-slate-900 hover:bg-gray-100 rounded transition-colors">Buy</a>
        </div>
        <div class="mb-[2vw]">
            <a href="#" class="block p-[2vw] text-[4.5vw] font-semibold text-slate-900 hover:bg-gray-100 rounded transition-colors">Sell</a>
        </div>
        <div class="mb-[2vw]">
            <a href="#" class="block p-[2vw] text-[4.5vw] font-semibold text-slate-900 hover:bg-gray-100 rounded transition-colors">Rent</a>
        </div>
        <div class="mb-[2vw]">
            <a href="#" class="block p-[2vw] text-[4.5vw] font-semibold text-slate-900 hover:bg-gray-100 rounded transition-colors">About</a>
        </div>
        <?php
    }
    ?>
</nav>
