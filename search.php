<?php
/**
 * Search Results Template
 * 
 * @package WordPress
 * @subpackage Theme
 */

get_header();
?>

<main class="flex-1 bg-gradient-to-b from-slate-50 to-white">
    <!-- Search Header -->
    <section class="border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
            <h1 class="text-4xl md:text-5xl font-bold text-slate-900 mb-4 text-balance">
                Search Results
            </h1>
            <p class="text-lg text-slate-600">
                <?php
                if (have_posts()):
                    echo sprintf(
                        'Found <span class="font-semibold text-blue-600">%d</span> result%s for "<span class="font-semibold">%s</span>"',
                        $GLOBALS['wp_query']->found_posts,
                        $GLOBALS['wp_query']->found_posts > 1 ? 's' : '',
                        esc_html(get_search_query())
                    );
                else:
                    echo 'No results found for "' . esc_html(get_search_query()) . '"';
                endif;
                ?>
            </p>
        </div>
    </section>

    <!-- Content with Sidebar -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-3">
                <!-- Filter and Sort Section -->
                <div x-data="{ showFilters: false }" class="mb-8">
                    <!-- Mobile Filter Toggle -->
                    <button @click="showFilters = !showFilters"
                        class="lg:hidden flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-900 font-medium hover:bg-slate-50 transition mb-6">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filters
                    </button>

                    <!-- Sort Options -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 mb-8">
                        <label class="text-sm font-medium text-slate-700">Sort by:</label>
                        <form method="get" class="flex gap-2">
                            <input type="hidden" name="s" value="<?php echo esc_attr(get_search_query()); ?>">
                            <select name="orderby"
                                class="px-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                                onchange="this.form.submit()">
                                <option value="relevance" <?php echo isset($_GET['orderby']) && $_GET['orderby'] === 'relevance' ? 'selected' : 'selected'; ?>>Relevance</option>
                                <option value="date" <?php echo isset($_GET['orderby']) && $_GET['orderby'] === 'date' ? 'selected' : ''; ?>>Latest</option>
                                <option value="title" <?php echo isset($_GET['orderby']) && $_GET['orderby'] === 'title' ? 'selected' : ''; ?>>Title (A-Z)</option>
                            </select>
                        </form>
                    </div>
                </div>

                <?php if (have_posts()): ?>
                    <!-- Results List -->
                    <div class="space-y-6 mb-12">
                        <?php
                        while (have_posts()):
                            the_post();
                            ?>
                            <article x-data="{ hover: false }" @mouseenter="hover = true" @mouseleave="hover = false"
                                class="group rounded-xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 bg-white border border-slate-200 hover:-translate-y-1">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 p-6">
                                    <!-- Image -->
                                    <?php if (has_post_thumbnail()): ?>
                                        <div class="sm:col-span-1 h-48 sm:h-full overflow-hidden rounded-lg bg-slate-200">
                                            <a href="<?php the_permalink(); ?>" class="block h-full">
                                                <?php the_post_thumbnail('medium', array('class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-300')); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Content -->
                                    <div class="sm:col-span-2 flex flex-col justify-between">
                                        <!-- Category Badge -->
                                        <?php
                                        $categories = get_the_category();
                                        if (!empty($categories)):
                                            $category = $categories[0];
                                            ?>
                                            <a href="<?php echo esc_url(get_category_link($category->term_id)); ?>"
                                                class="inline-flex w-fit px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold mb-2 hover:bg-blue-200 transition">
                                                <?php echo esc_html($category->name); ?>
                                            </a>
                                        <?php endif; ?>

                                        <!-- Title -->
                                        <h2 class="text-2xl font-bold text-slate-900 mb-3 line-clamp-2">
                                            <a href="<?php the_permalink(); ?>"
                                                class="hover:text-blue-600 transition">
                                                <?php the_title(); ?>
                                            </a>
                                        </h2>

                                        <!-- Excerpt with Highlight -->
                                        <p class="text-slate-600 text-sm mb-4 line-clamp-2">
                                            <?php
                                            $excerpt = get_the_excerpt();
                                            $search_term = get_search_query();
                                            if (!empty($search_term)) {
                                                $excerpt = preg_replace(
                                                    '/(' . preg_quote($search_term, '/') . ')/i',
                                                    '<mark class="bg-yellow-200 font-semibold">$1</mark>',
                                                    $excerpt
                                                );
                                            }
                                            echo wp_kses_post($excerpt);
                                            ?>
                                        </p>

                                        <!-- Meta Info -->
                                        <div class="flex flex-wrap items-center justify-between gap-4 text-xs text-slate-600 mt-auto pt-4 border-t border-slate-200">
                                            <div class="flex items-center gap-3">
                                                <div class="flex items-center gap-2">
                                                    <?php echo get_avatar(get_the_author_meta('ID'), 24, '', '', array('class' => 'rounded-full')); ?>
                                                    <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>"
                                                        class="font-medium hover:text-blue-600 transition">
                                                        <?php the_author(); ?>
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span><?php echo get_the_date('M d, Y'); ?></span>
                                                <a href="<?php the_permalink(); ?>"
                                                    class="text-blue-600 font-semibold hover:text-blue-700 transition">
                                                    Read More →
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                            <?php
                        endwhile;
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

                <?php else: ?>
                    <!-- No Results -->
                    <div class="text-center py-16">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-4">
                            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-900 mb-2">No results found</h3>
                        <p class="text-slate-600 mb-8 max-w-md mx-auto">
                            We couldn't find any articles matching "<span class="font-semibold">{{ search_query }}</span>". 
                            Try adjusting your search terms or browse our categories.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="<?php echo esc_url(home_url('/')); ?>"
                                class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 1118 0 9 9 0 01-18 0z" />
                                </svg>
                                Back to Home
                            </a>
                            <a href="<?php echo esc_url(home_url('/blog')); ?>"
                                class="inline-flex items-center justify-center px-6 py-3 bg-slate-200 text-slate-900 rounded-lg hover:bg-slate-300 transition font-medium">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v4m6 0a2 2 0 012 2v10a2 2 0 01-2 2h-8a2 2 0 01-2-2v-4" />
                                </svg>
                                Browse All Posts
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <aside class="lg:col-span-1">
                <div class="space-y-6">
                    <!-- Refined Search Widget -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Refine Search</h3>
                        <form method="get" action="<?php echo esc_url(home_url('/')); ?>" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-900 mb-2">Search term</label>
                                <input type="search" name="s" placeholder="Search..."
                                    value="<?php echo esc_attr(get_search_query()); ?>"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                    required>
                            </div>
                            <button type="submit"
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                                Search Again
                            </button>
                        </form>
                    </div>

                    <!-- Categories Widget -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Categories</h3>
                        <ul class="space-y-2">
                            <?php
                            $categories = get_categories();
                            foreach ($categories as $category):
                                ?>
                                <li>
                                    <a href="<?php echo esc_url(get_category_link($category->term_id)); ?>"
                                        class="flex items-center justify-between px-3 py-2 text-slate-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition group">
                                        <span class="font-medium"><?php echo esc_html($category->name); ?></span>
                                        <span class="text-xs bg-slate-100 group-hover:bg-blue-100 px-2 py-1 rounded-full">
                                            <?php echo absint($category->count); ?>
                                        </span>
                                    </a>
                                </li>
                                <?php
                            endforeach;
                            ?>
                        </ul>
                    </div>

                    <!-- Popular Tags Widget -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Popular Tags</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php
                            $tags = get_tags(array('number' => 10));
                            if (!empty($tags)):
                                foreach ($tags as $tag):
                                    ?>
                                    <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>"
                                        class="inline-flex items-center gap-1 px-3 py-1 bg-slate-100 text-slate-700 rounded-full text-xs font-medium hover:bg-blue-100 hover:text-blue-700 transition">
                                        #<?php echo esc_html($tag->name); ?>
                                    </a>
                                    <?php
                                endforeach;
                            endif;
                            ?>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</main>

<?php get_footer(); ?>
