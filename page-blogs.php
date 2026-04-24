<?php
/**
 * Template Name: Blogs Page
 */
get_header();

$orderby = in_array($_GET['orderby'] ?? '', ['date', 'title']) ? $_GET['orderby'] : 'date';

$all_posts = new WP_Query([
    'post_type' => 'post',
    'posts_per_page' => -1,
    'orderby' => $orderby,
    'order' => $orderby === 'title' ? 'ASC' : 'DESC',
]);

$posts_arr = [];
if ($all_posts->have_posts()) {
    while ($all_posts->have_posts()) {
        $all_posts->the_post();
        $posts_arr[] = get_post();
    }
    wp_reset_postdata();
}

$featured_post = $posts_arr[0] ?? null;
$grid_posts = array_slice($posts_arr, 1);
?>

<main class="min-h-screen bg-slate-50">

    <!-- ── Leaderboard Ad ── -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        <?php get_template_part('template-parts/component', 'ad-space', ['slot' => 'leaderboard', 'label' => 'Advertisement']); ?>
    </div>


    <!-- ── Main Content + Sidebar ── -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
        <div class="grid grid-cols-1 lg:grid-cols-6 gap-10">

            <!-- Posts Grid -->
            <div class="lg:col-span-4">
                <?php if (!empty($grid_posts)): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <?php foreach ($grid_posts as $i => $post):
                            setup_postdata($post);
                            $post_cats = get_the_category($post->ID);
                            ?>
                            <article
                                class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 border border-slate-100 hover:-translate-y-0.5 flex flex-col">

                                <!-- Image -->
                                <a href="<?php echo get_permalink($post); ?>"
                                    class="block h-48 overflow-hidden bg-slate-100 relative">
                                    <?php if (has_post_thumbnail($post->ID)): ?>
                                        <?php echo get_the_post_thumbnail($post->ID, 'medium', ['class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500']); ?>
                                    <?php else: ?>
                                        <div
                                            class="w-full h-full bg-gradient-to-br from-slate-200 to-slate-100 flex items-center justify-center">
                                            <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14" />
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </a>

                                <!-- Content -->
                                <div class="p-5 flex flex-col flex-1">
                                    <?php if (!empty($post_cats)): ?>
                                        <a href="<?php echo esc_url(get_category_link($post_cats[0]->term_id)); ?>"
                                            class="inline-flex w-fit px-2.5 py-0.5 bg-blue-50 text-blue-600 rounded-full text-xs font-semibold mb-2.5 hover:bg-blue-100 transition">
                                            <?php echo esc_html($post_cats[0]->name); ?>
                                        </a>
                                    <?php endif; ?>

                                    <h3
                                        class="text-base font-bold text-slate-900 mb-2 line-clamp-2 group-hover:text-blue-600 transition flex-1">
                                        <a
                                            href="<?php echo get_permalink($post); ?>"><?php echo esc_html($post->post_title); ?></a>
                                    </h3>

                                    <p class="text-slate-500 text-sm line-clamp-2 mb-4">
                                        <?php echo wp_trim_words(get_the_excerpt($post), 18); ?>
                                    </p>

                                    <div
                                        class="flex items-center justify-between text-xs text-slate-400 mt-auto pt-3 border-t border-slate-100">
                                        <div class="flex items-center gap-2">
                                            <?php echo get_avatar($post->post_author, 20, '', '', ['class' => 'rounded-full']); ?>
                                            <span
                                                class="font-medium text-slate-600"><?php echo esc_html(get_the_author_meta('display_name', $post->post_author)); ?></span>
                                        </div>
                                        <span><?php echo get_the_date('M d', $post); ?></span>
                                    </div>
                                </div>
                            </article>

                            <?php
                            // Inject ad after every 4th post in grid
                            if (($i + 1) % 4 === 0 && $i < count($grid_posts) - 1):
                                ?>
                                <div class="sm:col-span-2">
                                    <?php get_template_part('template-parts/component', 'ad-space', ['slot' => 'banner', 'label' => 'Advertisement']); ?>
                                </div>
                            <?php endif; ?>

                        <?php endforeach;
                        wp_reset_postdata(); ?>
                    </div>

                <?php else: ?>
                    <div class="text-center py-20">
                        <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="text-xl font-bold text-slate-700 mb-2">No posts yet</h3>
                        <p class="text-slate-500">Check back soon for articles and stories.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <aside class="lg:col-span-2">
                <div class="sticky top-24 space-y-6">

                    <!-- Search -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-3">Search</h3>
                        <form method="get" action="<?php echo esc_url(home_url('/')); ?>" class="flex gap-2">
                            <input type="search" name="s" placeholder="Search articles…"
                                value="<?php echo esc_attr(get_search_query()); ?>"
                                class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="submit"
                                class="p-2 bg-[var(--primary-color)] text-white rounded-lg hover:opacity-90 transition">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </form>
                    </div>

                    <!-- Recent Posts -->
                    <?php
                    $recent = get_posts(['numberposts' => 5, 'post__not_in' => $featured_post ? [$featured_post->ID] : []]);
                    if ($recent):
                        ?>
                        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-4">Recent Articles</h3>
                            <ul class="space-y-3">
                                <?php foreach ($recent as $rp): ?>
                                    <li class="flex gap-3 items-start">
                                        <?php if (has_post_thumbnail($rp->ID)): ?>
                                            <a href="<?php echo get_permalink($rp); ?>"
                                                class="flex-shrink-0 w-14 h-14 rounded-lg overflow-hidden bg-slate-100">
                                                <?php echo get_the_post_thumbnail($rp->ID, 'thumbnail', ['class' => 'w-full h-full object-cover']); ?>
                                            </a>
                                        <?php endif; ?>
                                        <div class="min-w-0">
                                            <a href="<?php echo get_permalink($rp); ?>"
                                                class="text-sm font-semibold text-slate-800 hover:text-blue-600 transition line-clamp-2"><?php echo esc_html($rp->post_title); ?></a>
                                            <div class="text-xs text-slate-400 mt-0.5">
                                                <?php echo get_the_date('M d, Y', $rp); ?></div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Categories -->
                    <?php $cats = get_categories(['hide_empty' => true]);
                    if ($cats): ?>
                        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-4">Categories</h3>
                            <div class="space-y-1.5">
                                <?php foreach ($cats as $cat):
                                    $active = is_category($cat->term_id) ? 'bg-blue-50 text-blue-700 border-blue-200 font-semibold' : 'text-slate-600 border-transparent hover:bg-slate-50';
                                    ?>
                                    <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>"
                                        class="flex items-center justify-between px-3 py-2 rounded-lg border text-sm transition <?php echo $active; ?>">
                                        <span><?php echo esc_html($cat->name); ?></span>
                                        <span
                                            class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 font-medium"><?php echo $cat->count; ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tags -->
                    <?php $tags = get_tags(['number' => 15, 'orderby' => 'count', 'order' => 'DESC']);
                    if ($tags): ?>
                        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-4">Popular Tags</h3>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($tags as $tag):
                                    $cls = has_tag($tag->term_id) ? 'bg-[var(--primary-color)] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200';
                                    ?>
                                    <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>"
                                        class="px-3 py-1 rounded-full text-xs font-medium transition <?php echo $cls; ?>">
                                        <?php echo esc_html($tag->name); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Sidebar Ad -->
                    <?php get_template_part('template-parts/component', 'ad-space', ['slot' => 'sidebar', 'label' => 'Advertisement']); ?>

                </div>
            </aside>

        </div>
    </div>

</main>

<?php get_footer(); ?>