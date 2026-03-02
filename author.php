<?php
/**
 * Author Archive Template
 * 
 * @package WordPress
 * @subpackage Theme
 */

get_header();
$author = get_queried_object();
$author_bio = get_the_author_meta('description', $author->ID);
$author_url = get_the_author_meta('user_url', $author->ID);
$author_twitter = get_the_author_meta('twitter', $author->ID);
$author_facebook = get_the_author_meta('facebook', $author->ID);
$author_instagram = get_the_author_meta('instagram', $author->ID);
?>

<main class="flex-1 bg-gradient-to-b from-slate-50 to-white">
    <!-- Author Hero Section -->
    <section class="border-b border-slate-200 bg-gradient-to-br from-blue-600 via-blue-500 to-blue-700 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-center">
                <!-- Avatar -->
                <div class="flex justify-center md:justify-start">
                    <div class="relative">
                        <?php echo get_avatar($author->ID, 200, '', '', array('class' => 'w-32 h-32 md:w-48 md:h-48 rounded-full border-4 border-white shadow-2xl')); ?>
                        <div class="absolute inset-0 rounded-full border-4 border-white opacity-20"></div>
                    </div>
                </div>

                <!-- Author Info -->
                <div class="md:col-span-2">
                    <h1 class="text-4xl md:text-5xl font-bold mb-3 text-balance">
                        <?php echo esc_html($author->display_name); ?>
                    </h1>

                    <?php if (get_the_author_meta('user_description')): ?>
                        <p class="text-xl text-blue-100 mb-4 font-semibold">
                            <?php echo esc_html(get_the_author_meta('user_description')); ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($author_bio): ?>
                        <p class="text-lg text-blue-50 mb-6 leading-relaxed max-w-2xl">
                            <?php echo wp_kses_post(wpautop($author_bio)); ?>
                        </p>
                    <?php endif; ?>

                    <!-- Social Links -->
                    <?php if ($author_url || $author_twitter || $author_facebook || $author_instagram): ?>
                        <div class="flex flex-wrap gap-3">
                            <?php if ($author_url): ?>
                                <a href="<?php echo esc_url($author_url); ?>" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-full transition font-medium">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M12.586 4.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L5 12.172V15h2.828l6.38-6.379-2.83-2.828z" />
                                    </svg>
                                    Website
                                </a>
                            <?php endif; ?>

                            <?php if ($author_twitter): ?>
                                <a href="<?php echo esc_url('https://twitter.com/' . $author_twitter); ?>" target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-full transition font-medium">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M6.29 18.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0020 3.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.073 4.073 0 01.8 7.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 010 16.407a11.616 11.616 0 006.29 1.84" />
                                    </svg>
                                    Twitter
                                </a>
                            <?php endif; ?>

                            <?php if ($author_facebook): ?>
                                <a href="<?php echo esc_url('https://facebook.com/' . $author_facebook); ?>" target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-full transition font-medium">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M20 10a10 10 0 11-20 0 10 10 0 0120 0zm-4.5-6.5H12V4c0-.745.357-1 1.5-1s3 0 3 0V.5S14.957 0 13.5 0c-4 0-6 2.25-6 5.5V9h-3v3h3v8h4v-8h3l.5-3h-3.5V6.5c0-.75.5-1.5 1.5-1.5z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Facebook
                                </a>
                            <?php endif; ?>

                            <?php if ($author_instagram): ?>
                                <a href="<?php echo esc_url('https://instagram.com/' . $author_instagram); ?>" target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-full transition font-medium">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 0a5 5 0 110 10 5 5 0 010-10zm0 2a3 3 0 100 6 3 3 0 000-6z" />
                                    </svg>
                                    Instagram
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Content with Sidebar -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-3">
                <!-- Statistics -->
                <div class="grid grid-cols-3 gap-4 mb-12">
                    <div class="bg-white border border-slate-200 rounded-xl p-6 text-center shadow-sm hover:shadow-md transition-shadow">
                        <div class="text-3xl font-bold text-blue-600 mb-2">
                            <?php
                            $post_count = count_user_posts($author->ID);
                            echo $post_count;
                            ?>
                        </div>
                        <p class="text-sm text-slate-600 font-medium">Articles Published</p>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-xl p-6 text-center shadow-sm hover:shadow-md transition-shadow">
                        <div class="text-3xl font-bold text-green-600 mb-2">
                            <?php
                            $args = array(
                                'author' => $author->ID,
                                'type' => 'post',
                            );
                            $user_posts = get_posts($args);
                            $total_words = 0;
                            foreach ($user_posts as $post) {
                                $total_words += str_word_count(strip_tags($post->post_content));
                            }
                            echo intdiv($total_words, 1000) . 'K';
                            ?>
                        </div>
                        <p class="text-sm text-slate-600 font-medium">Words Written</p>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-xl p-6 text-center shadow-sm hover:shadow-md transition-shadow">
                        <div class="text-3xl font-bold text-purple-600 mb-2">
                            <?php
                            $categories = get_categories(array(
                                'object_ids' => get_posts(array(
                                    'author' => $author->ID,
                                    'fields' => 'ids',
                                    'numberposts' => -1,
                                )),
                            ));
                            echo count($categories);
                            ?>
                        </div>
                        <p class="text-sm text-slate-600 font-medium">Categories</p>
                    </div>
                </div>

                <!-- Filter and Sort Section -->
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-3xl font-bold text-slate-900">Latest Articles</h2>
                        <form method="get" class="flex gap-2">
                            <select name="orderby"
                                class="px-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                                onchange="this.form.submit()">
                                <option value="date" <?php echo isset($_GET['orderby']) && $_GET['orderby'] === 'date' ? 'selected' : 'selected'; ?>>Latest</option>
                                <option value="title" <?php echo isset($_GET['orderby']) && $_GET['orderby'] === 'title' ? 'selected' : ''; ?>>Title (A-Z)</option>
                            </select>
                        </form>
                    </div>
                </div>

                <!-- Posts Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
                    <?php
                    if (have_posts()):
                        while (have_posts()):
                            the_post();
                            ?>
                            <article x-data="{ hover: false }" @mouseenter="hover = true" @mouseleave="hover = false"
                                class="group rounded-xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 bg-white border border-slate-200 hover:-translate-y-1 flex flex-col">
                                <!-- Image Container -->
                                <?php if (has_post_thumbnail()): ?>
                                    <div class="h-48 overflow-hidden bg-slate-200 relative">
                                        <?php the_post_thumbnail('medium', array('class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-300')); ?>
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-300"></div>
                                    </div>
                                <?php endif; ?>

                                <!-- Content -->
                                <div class="p-6 flex flex-col flex-grow">
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
                                    <h3 class="text-lg font-bold text-slate-900 mb-2 line-clamp-2">
                                        <a href="<?php the_permalink(); ?>"
                                            class="hover:text-blue-600 transition">
                                            <?php the_title(); ?>
                                        </a>
                                    </h3>

                                    <!-- Excerpt -->
                                    <p class="text-slate-600 text-sm mb-4 line-clamp-2 flex-grow">
                                        <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                                    </p>

                                    <!-- Meta Info -->
                                    <div class="flex items-center justify-between text-xs text-slate-600 pt-4 border-t border-slate-200">
                                        <span><?php echo get_the_date('M d, Y'); ?></span>
                                        <span class="text-slate-500">
                                            <?php echo absint(str_word_count(get_the_content()) / 200) . ' min read'; ?>
                                        </span>
                                    </div>

                                    <!-- Read More -->
                                    <a href="<?php the_permalink(); ?>"
                                        class="inline-flex items-center gap-2 text-blue-600 font-semibold hover:gap-3 transition-all group-hover:text-blue-700 mt-4">
                                        Read Article
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                </div>
                            </article>
                            <?php
                        endwhile;
                    else:
                        ?>
                        <div class="col-span-1 md:col-span-2 text-center py-12">
                            <svg class="w-16 h-16 mx-auto mb-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="text-xl font-semibold text-slate-900 mb-2">No posts yet</h3>
                            <p class="text-slate-600">This author hasn't published any articles yet.</p>
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
            <aside class="lg:col-span-1">
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <a href="<?php echo esc_url(add_query_arg('author', $author->ID, home_url())); ?>"
                                class="block w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-center font-medium">
                                View All Posts
                            </a>
                            <a href="<?php echo esc_url(home_url()); ?>"
                                class="block w-full px-4 py-2 border border-slate-300 text-slate-900 rounded-lg hover:bg-slate-50 transition text-center font-medium">
                                Back to Home
                            </a>
                        </div>
                    </div>

                    <!-- Search Widget -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Search</h3>
                        <form method="get" action="<?php echo esc_url(home_url('/')); ?>" class="flex gap-2">
                            <input type="search" name="s" placeholder="Search articles..."
                                class="flex-1 px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                required>
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                        clip-rule="evenodd" />
                                </svg>
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

                    <!-- Other Authors Widget -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Other Authors</h3>
                        <ul class="space-y-3">
                            <?php
                            $authors = new WP_User_Query(array(
                                'who' => 'authors',
                                'number' => 5,
                            ));

                            if ($authors->get_results()):
                                foreach ($authors->get_results() as $user):
                                    if ($user->ID !== $author->ID):
                                        ?>
                                        <li class="flex items-center gap-3">
                                            <a href="<?php echo esc_url(get_author_posts_url($user->ID)); ?>"
                                                class="flex items-center gap-3 flex-1 hover:opacity-75 transition">
                                                <?php echo get_avatar($user->ID, 32, '', '', array('class' => 'rounded-full')); ?>
                                                <div class="flex-1">
                                                    <h4 class="text-sm font-semibold text-slate-900">
                                                        <?php echo esc_html($user->display_name); ?>
                                                    </h4>
                                                    <p class="text-xs text-slate-500">
                                                        <?php echo absint(count_user_posts($user->ID)); ?> posts
                                                    </p>
                                                </div>
                                            </a>
                                        </li>
                                        <?php
                                    endif;
                                endforeach;
                            endif;
                            ?>
                        </ul>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</main>

<?php get_footer(); ?>
