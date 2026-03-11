<?php
/**
 * Template Name: Blogs Page
 * 
 * @package WordPress
 * @subpackage Theme
 */

get_header();
?>

<main class="flex-1 bg-gradient-to-b from-slate-50 to-white">
    <!-- Archive Header -->
    <section class="border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
            <h1 class="text-4xl md:text-5xl font-bold text-slate-900 mb-4 text-balance">
                <?php
                if (is_category()) {
                    echo 'Category: ' . single_cat_title('', false);
                } elseif (is_tag()) {
                    echo 'Tag: ' . single_tag_title('', false);
                } else {
                    echo 'All Blogs';
                }
                ?>
            </h1>
            <p class="text-lg text-slate-600">
                <?php
                if (is_category()) {
                    echo 'Explore all posts in this category';
                } elseif (is_tag()) {
                    echo 'Explore all posts with this tag';
                } else {
                    echo 'Browse through all our latest articles and stories';
                }
                ?>
            </p>
        </div>
    </section>

    <!-- Content with Sidebar -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
        <div class="grid grid-cols-1 lg:grid-cols-6 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-4">
                <!-- Filter Controls -->
                <div x-data="{ open: false }" class="mb-8">
                    <button @click="open = !open"
                        class="lg:hidden flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-medium hover:bg-slate-50 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filters
                    </button>

                    <!-- Sort Options -->
                    <div class="flex items-center gap-4 mb-8">
                        <label class="text-sm font-medium text-slate-700">Sort by:</label>
                        <form method="get" class="flex gap-2">
                            <select name="orderby"
                                class="px-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                                onchange="this.form.submit()">
                                <option value="date" <?php echo isset($_GET['orderby']) && $_GET['orderby'] === 'date' ? 'selected' : ''; ?>>Latest</option>
                                <option value="title" <?php echo isset($_GET['orderby']) && $_GET['orderby'] === 'title' ? 'selected' : ''; ?>>Title (A-Z)</option>
                            </select>
                        </form>
                    </div>
                </div>

                <!-- Posts Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
                    <?php
                    $all_post = new WP_Query(array(
                        'posts_per_page' => -1,
                        'post_type' => 'post',
                        'orderby' => 'date',
                        'order' => 'DESC',
                    ));
                    if ($all_post->have_posts()):
                        while ($all_post->have_posts()):
                            $all_post->the_post();
                            ?>
                            <article x-data="{ hover: false }" @mouseenter="hover = true" @mouseleave="hover = false"
                                class="group rounded-xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 bg-white border border-slate-200 hover:-translate-y-1">
                                <!-- Image Container -->
                                <?php if (has_post_thumbnail()): ?>
                                    <div class="h-48 overflow-hidden bg-slate-200 relative">
                                        <?php the_post_thumbnail('medium', array('class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-300')); ?>
                                        <div
                                            class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-300">
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Content -->
                                <div class="p-6 flex flex-col">
                                    <!-- Category Badge -->
                                    <?php
                                    $categories = get_the_category();
                                    if (!empty($categories)):
                                        $category = $categories[0];
                                        ?>
                                        <a href="<?php echo esc_url(get_category_link($category->term_id)); ?>"
                                            class="inline-flex w-fit px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold mb-3 hover:bg-blue-200 transition">
                                            <?php echo esc_html($category->name); ?>
                                        </a>
                                    <?php endif; ?>

                                    <!-- Title -->
                                    <h2 class="text-xl font-bold text-slate-900 mb-2 line-clamp-2">
                                        <a href="<?php the_permalink(); ?>" class="hover:text-blue-600 transition">
                                            <?php the_title(); ?>
                                        </a>
                                    </h2>

                                    <!-- Excerpt -->
                                    <p class="text-slate-600 text-sm mb-4 line-clamp-2 flex-grow">
                                        <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                                    </p>

                                    <!-- Meta Info -->
                                    <div class="flex items-center justify-between text-xs text-slate-600 mb-4">
                                        <div class="flex items-center gap-2">
                                            <?php echo get_avatar(get_the_author_meta('ID'), 24, '', '', array('class' => 'rounded-full')); ?>
                                            <span><?php the_author(); ?></span>
                                        </div>
                                        <span><?php echo get_the_date('M d, Y'); ?></span>
                                    </div>

                                    <!-- Read More -->
                                    <a href="<?php the_permalink(); ?>"
                                        class="inline-flex items-center gap-2 text-blue-600 font-semibold hover:gap-3 transition-all group-hover:text-blue-700">
                                        Read Article
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                </div>
                            </article>
                            <?php
                        endwhile;
                    else:
                        ?>
                        <div class="col-span-1 md:col-span-2 text-center py-12">
                            <svg class="w-16 h-16 mx-auto mb-4 text-slate-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="text-xl font-semibold text-slate-900 mb-2">No posts found</h3>
                            <p class="text-slate-600 mb-6">We couldn't find any posts matching your criteria.</p>
                            <a href="<?php echo esc_url(home_url('/')); ?>"
                                class="inline-flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 12a9 9 0 1118 0 9 9 0 01-18 0z" />
                                </svg>
                                Back to Home
                            </a>
                        </div>
                        <?php
                    endif;
                    ?>
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-center gap-2">
                    <?php
                    echo paginate_links(array(
                        'type' => 'list',
                        'prev_text' => '<span class="sr-only">Previous</span><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>',
                        'next_text' => '<span class="sr-only">Next</span><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>',
                    ));
                    ?>
                </div>
            </div>

            <!-- Sidebar -->
            <aside class="lg:col-span-2">
                <div class="sticky top-24 space-y-6">
                    <!-- Search Widget -->
                    <div
                        class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Search</h3>
                        <form method="get" action="<?php echo esc_url(home_url('/')); ?>" class="flex gap-2">
                            <input type="search" name="s" placeholder="Search articles..."
                                value="<?php echo get_search_query(); ?>"
                                class="flex-1 px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <button type="submit"
                                class="px-4 py-2 bg-[var(--primary-color)] text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </form>
                    </div>

                    <!-- Recent Articles -->
                    <div
                        class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Recent Articles</h3>
                        <ul class="space-y-4">
                            <?php
                            $recent_posts = new WP_Query(array(
                                'posts_per_page' => 5,
                                'post__not_in' => array(get_the_ID()),
                            ));

                            if ($recent_posts->have_posts()) {
                                while ($recent_posts->have_posts()) {
                                    $recent_posts->the_post();
                                    ?>
                                    <li class="pb-4 border-b border-slate-100 last:pb-0 last:border-0">
                                        <a href="<?php the_permalink(); ?>" class="group block">
                                            <h4
                                                class="text-sm font-semibold text-slate-900 group-hover:text-[var(--primary-color)] transition-colors duration-200 line-clamp-2">
                                                <?php the_title(); ?>
                                            </h4>
                                            <time class="text-xs text-slate-500 mt-1 block">
                                                <?php echo get_the_date('M d, Y'); ?>
                                            </time>
                                        </a>
                                    </li>
                                    <?php
                                }
                                wp_reset_postdata();
                            }
                            ?>
                        </ul>
                    </div>

                    <!-- Categories -->
                    <div
                        class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Categories</h3>
                        <div class="space-y-2">
                            <?php
                            $categories = get_categories(array(
                                'hide_empty' => true,
                            ));

                            if (!empty($categories)) {
                                foreach ($categories as $category) {
                                    $active = in_category($category->term_id) ? 'bg-blue-50 text-blue-700 border-blue-200' : 'text-slate-700 border-slate-200 hover:border-slate-300';
                                    ?>
                                    <a href="<?php echo esc_url(get_category_link($category->term_id)); ?>"
                                        class="flex items-center justify-between px-3 py-2 rounded-lg border transition-all duration-200 text-sm <?php echo $active; ?>">
                                        <span class="font-medium"><?php echo esc_html($category->name); ?></span>
                                        <span class="text-xs font-semibold px-2 py-1 rounded bg-slate-200 text-slate-700">
                                            <?php echo $category->count; ?>
                                        </span>
                                    </a>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div
                        class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Popular Tags</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php
                            $tags = get_tags(array(
                                'number' => 15,
                                'orderby' => 'count',
                                'order' => 'DESC',
                            ));

                            if (!empty($tags)) {
                                foreach ($tags as $tag) {
                                    $is_current_tag = has_tag($tag->term_id);
                                    $tag_class = $is_current_tag ? 'bg-[var(--primary-color)] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200';
                                    ?>
                                    <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>"
                                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 <?php echo $tag_class; ?>">
                                        <?php echo esc_html($tag->name); ?>
                                    </a>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</main>

<?php get_footer(); ?>