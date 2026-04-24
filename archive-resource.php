<?php
/**
 * Resources Archive Template
 */
get_header();

$search   = sanitize_text_field($_GET['search'] ?? '');
$category = sanitize_text_field($_GET['category'] ?? '');
$paged    = max(1, intval(get_query_var('paged')));

$args = [
    'post_type'      => 'resource',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
];
if ($search) $args['s'] = $search;
if ($category) {
    $args['tax_query'] = [[
        'taxonomy' => 'resource_category',
        'field'    => 'slug',
        'terms'    => $category,
    ]];
}

$query = new WP_Query($args);
$cats  = get_terms(['taxonomy' => 'resource_category', 'hide_empty' => true]);
$file_icons = ['pdf' => '📄', 'doc' => '📝', 'docx' => '📝', 'ppt' => '📊', 'pptx' => '📊', 'xls' => '📈', 'xlsx' => '📈', 'zip' => '🗜️'];
?>

<main class="min-h-screen bg-slate-50">

    <!-- ── Hero ── -->
    <section class="bg-gradient-to-br from-[#080d1a] via-[#0f1729] to-[#1a2744] relative overflow-hidden">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute -top-1/3 -right-1/4 w-2/3 h-2/3 rounded-full opacity-15" style="background:radial-gradient(circle,#3b82f6 0%,transparent 70%)"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-20">
            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold tracking-widest uppercase mb-4" style="background:rgba(59,130,246,0.15);color:#60a5fa;border:1px solid rgba(59,130,246,0.3)">Resource Library</span>
            <h1 class="text-4xl md:text-5xl font-extrabold text-white leading-tight tracking-tight mb-3">
                Documents &amp; Guides
            </h1>
            <p class="text-slate-400 text-lg max-w-xl">
                Free and members-only PDF guides, templates, and resources for Jamaica's property market.
            </p>
        </div>
    </section>

    <!-- ── Leaderboard Ad ── -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        <?php get_template_part('template-parts/component', 'ad-space', ['slot' => 'leaderboard', 'label' => 'Advertisement']); ?>
    </div>

    <!-- ── Filter Bar ── -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <form method="get" class="flex flex-wrap gap-3 items-center">
            <input type="search" name="search" value="<?php echo esc_attr($search); ?>"
                placeholder="Search resources…"
                class="flex-1 min-w-0 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm">

            <select name="category" onchange="this.form.submit()"
                class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm">
                <option value="">All Categories</option>
                <?php if (!is_wp_error($cats)): foreach ($cats as $cat): ?>
                <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($category, $cat->slug); ?>>
                    <?php echo esc_html($cat->name); ?> (<?php echo $cat->count; ?>)
                </option>
                <?php endforeach; endif; ?>
            </select>

            <button type="submit" class="px-5 py-2.5 bg-[var(--primary-color)] text-white rounded-xl text-sm font-semibold hover:opacity-90 transition shadow-sm">
                Search
            </button>
            <?php if ($search || $category): ?>
            <a href="<?php echo get_post_type_archive_link('resource'); ?>"
                class="px-4 py-2.5 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-medium hover:bg-slate-50 transition shadow-sm">
                Clear
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- ── Content + Sidebar ── -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
        <div class="grid grid-cols-1 lg:grid-cols-6 gap-10">

            <!-- Resources grid -->
            <div class="lg:col-span-4">

                <?php if ($query->have_posts()): ?>
                <div class="text-sm text-slate-500 mb-5 font-medium"><?php echo $query->found_posts; ?> resource<?php echo $query->found_posts !== 1 ? 's' : ''; ?> found</div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <?php
                $count = 0;
                while ($query->have_posts()):
                    $query->the_post();
                    $pid       = get_the_ID();
                    $file_type = get_post_meta($pid, '_resource_file_type', true);
                    $is_free   = get_post_meta($pid, '_resource_is_free', true) !== '0';
                    $dl_count  = (int) get_post_meta($pid, '_resource_download_count', true);
                    $icon      = $file_icons[$file_type] ?? '📋';
                    $res_cats  = get_the_terms($pid, 'resource_category');
                    $count++;
                ?>
                <article class="group bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden flex flex-col hover:-translate-y-0.5">

                    <a href="<?php the_permalink(); ?>" class="block relative">
                        <?php if (has_post_thumbnail()): ?>
                        <div class="h-40 overflow-hidden bg-slate-100">
                            <?php the_post_thumbnail('medium', ['class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500']); ?>
                        </div>
                        <?php else: ?>
                        <div class="h-36 flex items-center justify-center" style="background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 100%)">
                            <span class="text-5xl"><?php echo $icon; ?></span>
                        </div>
                        <?php endif; ?>

                        <span class="absolute top-3 right-3 text-xs font-bold px-2.5 py-1 rounded-full <?php echo $is_free ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'; ?>">
                            <?php echo $is_free ? 'Free' : 'Members'; ?>
                        </span>
                        <?php if ($file_type): ?>
                        <span class="absolute bottom-3 left-3 text-xs font-bold px-2.5 py-1 rounded-full bg-white/90 text-slate-700 shadow">
                            <?php echo strtoupper(esc_html($file_type)); ?>
                        </span>
                        <?php endif; ?>
                    </a>

                    <div class="p-5 flex flex-col flex-1">
                        <?php if (!is_wp_error($res_cats) && $res_cats): ?>
                        <div class="flex gap-1.5 mb-2.5 flex-wrap">
                            <?php foreach (array_slice($res_cats, 0, 2) as $rc): ?>
                            <span class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded-full text-xs font-semibold"><?php echo esc_html($rc->name); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <h3 class="text-base font-bold text-slate-900 mb-2 line-clamp-2 group-hover:text-blue-600 transition">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>

                        <?php if (has_excerpt()): ?>
                        <p class="text-sm text-slate-500 line-clamp-2 mb-3 flex-1"><?php the_excerpt(); ?></p>
                        <?php else: ?>
                        <div class="flex-1"></div>
                        <?php endif; ?>

                        <div class="flex items-center justify-between pt-3 border-t border-slate-100 mt-auto">
                            <span class="text-xs text-slate-400"><?php the_date('M j, Y'); ?></span>
                            <?php if ($dl_count > 0): ?>
                            <span class="text-xs text-slate-400"><?php echo $dl_count; ?> downloads</span>
                            <?php endif; ?>
                        </div>
                    </div>

                </article>

                <?php
                if ($count % 6 === 0 && $query->current_post < $query->post_count - 1):
                ?>
                <div class="sm:col-span-2 my-2">
                    <?php get_template_part('template-parts/component', 'ad-space', ['slot' => 'banner', 'label' => 'Advertisement']); ?>
                </div>
                <?php endif; ?>

                <?php endwhile; wp_reset_postdata(); ?>
                </div>

                <?php if ($query->max_num_pages > 1): ?>
                <div class="flex items-center justify-center gap-2 mt-10">
                    <?php echo paginate_links([
                        'total'     => $query->max_num_pages,
                        'current'   => $paged,
                        'type'      => 'list',
                        'prev_text' => '&larr;',
                        'next_text' => '&rarr;',
                    ]); ?>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div class="text-center py-20">
                    <span class="text-5xl block mb-4">📂</span>
                    <h3 class="text-xl font-bold text-slate-700 mb-2">No resources found</h3>
                    <p class="text-slate-500 mb-6">Try a different search or category.</p>
                    <a href="<?php echo get_post_type_archive_link('resource'); ?>"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-[var(--primary-color)] text-white rounded-xl text-sm font-semibold hover:opacity-90 transition">
                        View All Resources
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <aside class="lg:col-span-2">
                <div class="sticky top-24 space-y-6">

                    <?php get_template_part('template-parts/component', 'ad-space', ['slot' => 'sidebar', 'label' => 'Advertisement']); ?>

                    <?php if (!is_wp_error($cats) && $cats): ?>
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-4">Categories</h3>
                        <div class="space-y-1.5">
                            <a href="<?php echo get_post_type_archive_link('resource'); ?>"
                                class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition <?php echo !$category ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-50'; ?>">
                                <span>All</span>
                            </a>
                            <?php foreach ($cats as $cat): ?>
                            <a href="<?php echo add_query_arg(['category' => $cat->slug], get_post_type_archive_link('resource')); ?>"
                                class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition <?php echo $category === $cat->slug ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-50'; ?>">
                                <span><?php echo esc_html($cat->name); ?></span>
                                <span class="text-xs px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full"><?php echo $cat->count; ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="rounded-2xl p-5 text-white relative overflow-hidden" style="background:linear-gradient(135deg,#1e3a8a 0%,#1d4ed8 100%)">
                        <div class="absolute -top-6 -right-6 w-24 h-24 rounded-full opacity-10 bg-white"></div>
                        <div class="text-3xl mb-3">📚</div>
                        <h4 class="font-bold text-base mb-1">Members Get More</h4>
                        <p class="text-blue-200 text-sm mb-4">Subscribe to unlock premium PDF guides and resources.</p>
                        <a href="<?php echo esc_url(home_url('/pricing')); ?>"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white text-blue-700 rounded-xl text-sm font-bold hover:bg-blue-50 transition">
                            View Plans →
                        </a>
                    </div>

                </div>
            </aside>

        </div>
    </div>

</main>

<?php get_footer(); ?>
