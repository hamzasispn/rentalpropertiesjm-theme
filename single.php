<?php
/**
 * Single Post Template
 * 
 * @package WordPress
 * @subpackage Theme
 */

get_header();
?>


<!-- Main Content -->
<main class="flex-1">
    <?php
    if (have_posts()):
        while (have_posts()):
            the_post();
            ?>

            <!-- Blog Grid Container -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content Column -->
                    <div class="lg:col-span-2">

                        <!-- Hero Section -->
                        <section class="relative">
                            <!-- Breadcrumb -->
                            <div class="mb-8 flex items-center gap-2 text-sm text-slate-600">
                                <a href="<?php echo esc_url(home_url('/')); ?>"
                                    class="hover:text-slate-900 transition">Home</a>
                                <span class="text-slate-400">/</span>
                                <?php
                                $categories = get_the_category();
                                if (!empty($categories)):
                                    foreach ($categories as $category):
                                        echo '<a href="' . esc_url(get_category_link($category->term_id)) . '" class="hover:text-slate-900 transition">' . esc_html($category->name) . '</a>';
                                    endforeach;
                                    echo '<span class="text-slate-400">/</span>';
                                endif;
                                ?>
                                <span class="text-slate-900 font-medium"><?php the_title(); ?></span>
                            </div>

                            <!-- Title -->
                            <h1 class="text-4xl md:text-5xl font-bold text-slate-900 mb-8 leading-tight text-balance">
                                <?php the_title(); ?>
                            </h1>
                        </section>

                        <!-- Featured Image -->
                        <?php
                        if (has_post_thumbnail()):
                            ?>
                            <section class="py-8">
                                <div class="rounded-xl overflow-hidden shadow-lg">
                                    <?php the_post_thumbnail('large', array('class' => 'w-full h-auto object-cover')); ?>
                                </div>
                            </section>
                            <?php
                        endif;
                        ?>

                        <!-- Post Content with Gutenberg Blocks -->
                        <section class="py-12 md:py-16">
                            <article>
                                <div class="prose prose-slate max-w-none
                                                prose-headings:font-bold prose-headings:text-slate-900
                                                prose-h2:text-3xl prose-h2:mt-8 prose-h2:mb-4
                                                prose-h3:text-2xl prose-h3:mt-6 prose-h3:mb-3
                                                prose-p:text-slate-700 prose-p:leading-relaxed prose-p:mb-4
                                                prose-a:text-[var(--primary-color)] prose-a:no-underline hover:prose-a:underline
                                                prose-strong:text-slate-900 prose-strong:font-bold
                                                prose-em:text-slate-700
                                                prose-blockquote:border-l-4 prose-blockquote:border-blue-500 prose-blockquote:pl-4 prose-blockquote:italic prose-blockquote:text-slate-700
                                                prose-pre:bg-slate-900 prose-pre:text-slate-100 prose-pre:rounded-lg prose-pre:overflow-x-auto
                                                prose-code:text-red-500 prose-code:bg-slate-100 prose-code:px-2 prose-code:rounded prose-code:font-mono
                                                prose-li:text-slate-700
                                                prose-ol:list-decimal prose-ol:ml-4
                                                prose-ul:list-disc prose-ul:ml-4
                                                prose-img:rounded-lg prose-img:shadow-md">
                                    <?php the_content(); ?>
                                </div>
                            </article>
                        </section>

                        <!-- Tags Section -->
                        <?php
                        $tags = get_the_tags();
                        if (!empty($tags)):
                            ?>
                            <section class="py-8 border-t border-b border-slate-200">
                                <div class="flex flex-wrap gap-3">
                                    <?php
                                    foreach ($tags as $tag):
                                        ?>
                                        <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>"
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200 transition font-medium text-sm">
                                            #<?php echo esc_html($tag->name); ?>
                                        </a>
                                        <?php
                                    endforeach;
                                    ?>
                                </div>
                            </section>
                            <?php
                        endif;
                        ?>

                        <!-- Navigation -->
                        <section class="py-8 border-t border-slate-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <?php
                                $prev_post = get_previous_post();
                                if (!empty($prev_post)):
                                    ?>
                                    <a href="<?php echo esc_url(get_permalink($prev_post->ID)); ?>"
                                        class="group block p-6 rounded-lg bg-white shadow-md hover:shadow-lg transition border border-slate-200">
                                        <p class="text-sm text-slate-600 font-medium mb-2">← Previous Post</p>
                                        <h3
                                            class="text-lg font-bold text-slate-900 group-hover:text-[var(--primary-color)] transition line-clamp-2">
                                            <?php echo esc_html($prev_post->post_title); ?></h3>
                                    </a>
                                    <?php
                                endif;

                                $next_post = get_next_post();
                                if (!empty($next_post)):
                                    ?>
                                    <a href="<?php echo esc_url(get_permalink($next_post->ID)); ?>"
                                        class="group block p-6 rounded-lg bg-white shadow-md hover:shadow-lg transition text-right border border-slate-200">
                                        <p class="text-sm text-slate-600 font-medium mb-2">Next Post →</p>
                                        <h3
                                            class="text-lg font-bold text-slate-900 group-hover:text-[var(--primary-color)] transition line-clamp-2">
                                            <?php echo esc_html($next_post->post_title); ?></h3>
                                    </a>
                                    <?php
                                endif;
                                ?>
                            </div>
                        </section>

                        <!-- Related Posts -->
                        <?php
                        $related_args = array(
                            'post_type' => 'post',
                            'posts_per_page' => 3,
                            'post__not_in' => array(get_the_ID()),
                            'orderby' => 'date',
                            'order' => 'DESC',
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'category',
                                    'field' => 'id',
                                    'terms' => wp_get_post_categories(get_the_ID()),
                                ),
                            ),
                        );

                        $related_posts = new WP_Query($related_args);

                        if ($related_posts->have_posts()):
                            ?>
                            <section class="py-12 md:py-16">
                                <h2 class="text-3xl font-bold text-slate-900 mb-8">Related Articles</h2>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <?php
                                    while ($related_posts->have_posts()):
                                        $related_posts->the_post();
                                        ?>
                                        <article
                                            class="rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-shadow duration-300 bg-white border border-slate-200">
                                            <?php
                                            if (has_post_thumbnail()):
                                                ?>
                                                <div class="h-48 overflow-hidden bg-slate-200">
                                                    <?php the_post_thumbnail('medium', array('class' => 'w-full h-full object-cover')); ?>
                                                </div>
                                                <?php
                                            endif;
                                            ?>
                                            <div class="p-6">
                                                <h3 class="text-lg font-bold text-slate-900 mb-2 line-clamp-2">
                                                    <a href="<?php the_permalink(); ?>"
                                                        class="hover:text-[var(--primary-color)] transition"><?php the_title(); ?></a>
                                                </h3>
                                                <p class="text-slate-600 text-sm mb-4"><?php echo get_the_date('M d, Y'); ?></p>
                                                <a href="<?php the_permalink(); ?>"
                                                    class="inline-flex items-center gap-2 text-[var(--primary-color)] font-medium hover:gap-3 transition-all">
                                                    Read More
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M9 5l7 7-7 7"></path>
                                                    </svg>
                                                </a>
                                            </div>
                                        </article>
                                        <?php
                                    endwhile;
                                    wp_reset_postdata();
                                    ?>
                                </div>
                            </section>
                            <?php
                        endif;
                        ?>

                        <!-- Comments Section -->
                        <section class="py-12 md:py-16 border-t border-slate-200">
                            <?php
                            if (comments_open() || get_comments_number()):
                                comments_template();
                            endif;
                            ?>
                        </section>

                    </div>

                    <!-- Sidebar -->
                    <aside class="lg:col-span-1">
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

                            <!-- Author Box -->
                            <div
                                class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-shadow duration-200">
                                <!-- Header with gradient -->
                                <div class="h-24 bg-gradient-to-r from-[var(--primary-color)] to-blue-500"></div>

                                <div class="px-6 pb-6">
                                    <!-- Author Avatar -->
                                    <div class="flex justify-center -mt-12 mb-4">
                                        <?php echo get_avatar(get_the_author_meta('ID'), 96, '', '', array('class' => '!w-24 !h-24 rounded-full border-4 border-white shadow-lg')); ?>
                                    </div>

                                    <!-- Author Name -->
                                    <h3 class="text-xl font-bold text-slate-900 text-center mb-1">
                                        <?php the_author_meta('display_name'); ?>
                                    </h3>

                                    <!-- Author Role/Title -->
                                    <?php if (get_the_author_meta('user_description')): ?>
                                        <p class="text-sm text-[var(--primary-color)] font-semibold text-center mb-4">
                                            <?php the_author_meta('user_description'); ?>
                                        </p>
                                    <?php endif; ?>

                                    <!-- Publication Meta -->
                                    <div
                                        class="flex items-center justify-center gap-4 mb-4 pb-4 border-b border-slate-200 text-xs text-slate-600">
                                        <div class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h2.25a2.25 2.25 0 012.25 2.25v11A2.25 2.25 0 0117.25 19H2.75A2.25 2.25 0 01.5 16.75V6.25A2.25 2.25 0 012.75 4H5V2.75A.75.75 0 015.75 2zm9.75 3.5H2.75a.75.75 0 00-.75.75v2.5h15V6.25a.75.75 0 00-.75-.75z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            <span><?php echo get_the_date('M d, Y'); ?></span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6.253v13m0-13C6.5 6.253 2 10.998 2 17s4.5 10.747 10 10.747c5.5 0 10-4.998 10-10.747S17.5 6.253 12 6.253z" />
                                            </svg>
                                            <span><?php echo absint(str_word_count(get_the_content()) / 200) . ' min read'; ?></span>
                                        </div>
                                    </div>

                                    <!-- Author Bio -->
                                    <?php
                                    $author_bio = get_the_author_meta('description');
                                    if ($author_bio):
                                        ?>
                                        <p class="text-sm text-slate-700 mb-4 leading-relaxed text-center">
                                            <?php echo wp_trim_words($author_bio, 40); ?>
                                        </p>
                                    <?php endif; ?>

                                    <!-- Social Links -->
                                    <?php
                                    $author_id = get_the_author_meta('ID');
                                    $author_url = get_the_author_meta('user_url', $author_id);
                                    $twitter = get_the_author_meta('twitter', $author_id);
                                    $facebook = get_the_author_meta('facebook', $author_id);
                                    $instagram = get_the_author_meta('instagram', $author_id);
                                    ?>

                                    <?php if ($author_url || $twitter || $facebook || $instagram): ?>
                                        <div class="flex gap-2 justify-center mb-5">
                                            <?php if ($author_url): ?>
                                                <a href="<?php echo esc_url($author_url); ?>" target="_blank" rel="noopener"
                                                    title="Website"
                                                    class="inline-flex items-center justify-center w-10 h-10 bg-slate-100 rounded-full text-slate-600 hover:bg-[var(--primary-color)] hover:text-white transition-all duration-200">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            d="M12.586 4.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L5 12.172V15h2.828l6.38-6.379-2.83-2.828z" />
                                                    </svg>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($twitter): ?>
                                                <a href="https://twitter.com/<?php echo esc_attr($twitter); ?>" target="_blank"
                                                    rel="noopener" title="Twitter"
                                                    class="inline-flex items-center justify-center w-10 h-10 bg-slate-100 rounded-full text-slate-600 hover:bg-blue-400 hover:text-white transition-all duration-200">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M23.953 4.57a10 10 0 002.856-3.288c-1.04.577-2.189.969-3.402 1.181.001-.066.016-.131.016-.196 0-5.116-4.13-9.27-9.27-9.27-9.27 0-16.808 7.537-16.808 16.808 0 1.261.14 2.495.405 3.69-2.176-.422-4.21-1.088-6.001-2.006-.395.337-.79.705-1.161 1.078.61 3.96 3.264 7.305 6.829 9.05-.704.191-1.448.293-2.206.293-.529 0-1.054-.049-1.569-.144 1.07 3.29 4.152 5.694 7.802 5.76-3.061 2.401-6.921 3.83-11.118 3.83-.721 0-1.431-.046-2.134-.136 3.154 2.019 6.904 3.19 10.918 3.19 13.102 0 20.252-10.856 20.252-20.252 0-.31-.01-.62-.029-.927.386-.281.721-.636 1.024-1.04z" />
                                                    </svg>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($facebook): ?>
                                                <a href="https://facebook.com/<?php echo esc_attr($facebook); ?>" target="_blank"
                                                    rel="noopener" title="Facebook"
                                                    class="inline-flex items-center justify-center w-10 h-10 bg-slate-100 rounded-full text-slate-600 hover:bg-[var(--primary-color)] hover:text-white transition-all duration-200">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                                    </svg>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($instagram): ?>
                                                <a href="https://instagram.com/<?php echo esc_attr($instagram); ?>" target="_blank"
                                                    rel="noopener" title="Instagram"
                                                    class="inline-flex items-center justify-center w-10 h-10 bg-slate-100 rounded-full text-slate-600 hover:bg-pink-600 hover:text-white transition-all duration-200">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.521 17.521h-11.042V6.479h11.042v11.042zm-5.521-9.038c-1.662 0-3.004-1.342-3.004-3.004 0-1.662 1.342-3.004 3.004-3.004 1.662 0 3.004 1.342 3.004 3.004 0 1.662-1.342 3.004-3.004 3.004z" />
                                                    </svg>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- View All Posts Button -->
                                    <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>"
                                        class="block w-full px-4 py-3 bg-[var(--primary-color)] text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors duration-200 text-center">
                                        View All Posts by
                                        <?php echo wp_kses_post(get_the_author_meta('first_name') ?: get_the_author_meta('display_name')); ?>
                                    </a>
                                </div>
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

            <?php
        endwhile;
    endif;
    ?>
</main>