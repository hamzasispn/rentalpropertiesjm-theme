<?php
/**
 * Realtor Profile / Author Archive
 *
 * Shows all properties listed by a single realtor, plus their bio + contact info.
 *
 * @package WordPress
 * @subpackage PropertyListing
 */

get_header();

$author          = get_queried_object();
$author_id       = $author->ID ?? 0;
$author_bio      = get_the_author_meta('description', $author_id);
$author_url      = get_the_author_meta('user_url', $author_id);
$author_twitter  = get_the_author_meta('twitter', $author_id);
$author_facebook = get_the_author_meta('facebook', $author_id);
$author_instagram= get_the_author_meta('instagram', $author_id);
$display_name    = $author->display_name ?? '';

// Pagination — drive the SECOND query off the same `paged` var WordPress uses
// for the main author query so /author/{name}/page/2/ resolves correctly.
$paged    = max(1, intval(get_query_var('paged')) ?: intval(get_query_var('page')) ?: intval($_GET['paged'] ?? 1));
$per_page = 9;

$orderby_param = sanitize_key($_GET['orderby'] ?? 'date');
$query_args = array(
    'post_type'      => 'property',
    'post_status'    => 'publish',
    'author'         => $author_id,
    'posts_per_page' => $per_page,
    'paged'          => $paged,
    'order'          => 'DESC',
);
if ($orderby_param === 'meta_value_num') {
    $query_args['meta_key'] = '_property_price';
    $query_args['orderby']  = 'meta_value_num';
} else {
    $query_args['orderby'] = $orderby_param;
}

$properties_query = new WP_Query($query_args);

$total_properties = $properties_query->found_posts;
?>

<main class="flex-1 bg-gradient-to-b from-slate-50 to-white">
    <!-- Realtor Hero -->
    <section class="border-b border-slate-200 bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-700 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-center">
                <!-- Avatar -->
                <div class="flex justify-center md:justify-start">
                    <div class="relative">
                        <?php echo get_avatar($author_id, 200, '', '', array('class' => 'w-32 h-32 md:w-48 md:h-48 rounded-full border-4 border-white shadow-2xl')); ?>
                    </div>
                </div>

                <!-- Realtor Info -->
                <div class="md:col-span-2">
                    <p class="text-sm uppercase tracking-wider text-blue-200 mb-2">Estate Agent</p>
                    <h1 class="text-4xl md:text-5xl font-bold mb-3"><?php echo esc_html($display_name); ?></h1>

                    <?php if ($author_bio): ?>
                        <p class="text-lg text-blue-50 mb-6 leading-relaxed max-w-2xl">
                            <?php echo wp_kses_post(wpautop($author_bio)); ?>
                        </p>
                    <?php endif; ?>

                    <div class="flex flex-wrap gap-3 mt-4 items-center">
                        <span class="inline-flex items-center gap-2 px-4 py-2 bg-white/15 rounded-full font-semibold text-sm">
                            <?php echo intval($total_properties); ?> active listing<?php echo $total_properties === 1 ? '' : 's'; ?>
                        </span>

                        <?php if ($author_url): ?>
                            <a href="<?php echo esc_url($author_url); ?>" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-full transition font-medium text-sm">
                                Website
                            </a>
                        <?php endif; ?>
                        <?php if ($author_twitter): ?>
                            <a href="<?php echo esc_url('https://twitter.com/' . $author_twitter); ?>" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-full transition font-medium text-sm">Twitter</a>
                        <?php endif; ?>
                        <?php if ($author_facebook): ?>
                            <a href="<?php echo esc_url('https://facebook.com/' . $author_facebook); ?>" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-full transition font-medium text-sm">Facebook</a>
                        <?php endif; ?>
                        <?php if ($author_instagram): ?>
                            <a href="<?php echo esc_url('https://instagram.com/' . $author_instagram); ?>" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-full transition font-medium text-sm">Instagram</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Listings -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
        <div class="flex items-center justify-between mb-8 flex-wrap gap-4">
            <h2 class="text-3xl font-bold text-slate-900">Properties Listed by <?php echo esc_html($display_name); ?></h2>
            <form method="get" class="flex gap-2">
                <select name="orderby" onchange="this.form.submit()"
                    class="px-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="date"  <?php selected(($_GET['orderby'] ?? 'date'), 'date'); ?>>Newest</option>
                    <option value="title" <?php selected(($_GET['orderby'] ?? ''), 'title'); ?>>Title (A–Z)</option>
                    <option value="meta_value_num" <?php selected(($_GET['orderby'] ?? ''), 'meta_value_num'); ?>>Price</option>
                </select>
            </form>
        </div>

        <?php if ($properties_query->have_posts()) : ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($properties_query->have_posts()) : $properties_query->the_post();
                    $pid       = get_the_ID();
                    $price     = (float) get_post_meta($pid, '_property_price', true);
                    $area      = (int)   get_post_meta($pid, '_property_area',  true);
                    $address   = (string) (get_post_meta($pid, '_property_address', true) ?: '');
                    $city      = (string) (get_post_meta($pid, '_property_city',    true) ?: '');
                    $featured  = (bool)  get_post_meta($pid, '_property_featured', true);
                    $bedrooms  = wp_get_post_terms($pid, 'bedroom');
                    $bathrooms = wp_get_post_terms($pid, 'bathroom');
                    $bed       = !empty($bedrooms)  ? $bedrooms[0]->name  : '';
                    $bath      = !empty($bathrooms) ? $bathrooms[0]->name : '';
                    $listing_terms = wp_get_post_terms($pid, 'property_listing_status', array('fields' => 'slugs'));
                    $listing_slug  = !empty($listing_terms) ? $listing_terms[0] : '';
                    $thumb     = get_the_post_thumbnail_url($pid, 'medium') ?: 'https://via.placeholder.com/600x400?text=No+Image';
                ?>
                    <article class="bg-white rounded-xl shadow-sm hover:shadow-xl border border-slate-200 overflow-hidden transition-all duration-300 hover:-translate-y-1 flex flex-col">
                        <a href="<?php the_permalink(); ?>" class="block h-56 bg-slate-100 overflow-hidden relative">
                            <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" class="w-full h-full object-cover">
                            <?php if ($featured): ?>
                                <span class="absolute top-3 right-3 bg-gradient-to-r from-red-700 to-red-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow">Super Hot</span>
                            <?php endif; ?>
                            <?php if ($listing_slug): ?>
                                <span class="absolute bottom-3 right-3 px-3 py-1 rounded-full text-xs font-bold shadow <?php echo $listing_slug === 'rent' ? 'bg-blue-600 text-white' : 'bg-green-600 text-white'; ?>">
                                    <?php echo $listing_slug === 'rent' ? 'For Rent' : 'For Sale'; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div class="p-5 flex-1 flex flex-col">
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <h3 class="text-lg font-bold text-slate-900 line-clamp-2">
                                    <a href="<?php the_permalink(); ?>" class="hover:text-blue-600"><?php the_title(); ?></a>
                                </h3>
                                <span class="text-blue-700 font-bold whitespace-nowrap">$<?php echo number_format($price); ?></span>
                            </div>
                            <?php if ($address || $city): ?>
                                <p class="text-sm text-slate-600 mb-3 line-clamp-1"><?php echo esc_html(trim($address . ($address && $city ? ', ' : '') . $city)); ?></p>
                            <?php endif; ?>
                            <div class="flex flex-wrap gap-2 text-xs text-slate-700 mt-auto pt-3 border-t border-slate-100">
                                <?php if ($bed):  ?><span class="px-2 py-1 bg-slate-100 rounded"><?php echo esc_html($bed);  ?> Beds</span><?php endif; ?>
                                <?php if ($bath): ?><span class="px-2 py-1 bg-slate-100 rounded"><?php echo esc_html($bath); ?> Baths</span><?php endif; ?>
                                <?php if ($area > 0): ?><span class="px-2 py-1 bg-slate-100 rounded"><?php echo number_format($area); ?> sq.ft</span><?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if ($properties_query->max_num_pages > 1): ?>
            <div class="flex justify-center mt-12 gap-2 flex-wrap [&_a]:px-4 [&_a]:py-2 [&_a]:rounded-lg [&_a]:bg-white [&_a]:border [&_a]:border-slate-200 [&_a]:text-slate-700 [&_a]:hover:bg-blue-50 [&_a]:hover:text-blue-700 [&_a]:transition [&_span.current]:px-4 [&_span.current]:py-2 [&_span.current]:rounded-lg [&_span.current]:bg-blue-600 [&_span.current]:text-white [&_span.dots]:px-2 [&_span.dots]:py-2 [&_span.dots]:text-slate-400">
                <?php
                $pagination_base = trailingslashit(get_author_posts_url($author_id)) . '%_%';
                echo paginate_links(array(
                    'base'      => $pagination_base,
                    'format'    => 'page/%#%/',
                    'total'     => $properties_query->max_num_pages,
                    'current'   => $paged,
                    'prev_text' => '←',
                    'next_text' => '→',
                    'add_args'  => array_filter(array(
                        'orderby' => $orderby_param !== 'date' ? $orderby_param : false,
                    )),
                ));
                ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center py-16 bg-white border border-slate-200 rounded-xl">
                <svg class="w-16 h-16 mx-auto mb-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <h3 class="text-xl font-semibold text-slate-900 mb-2">No active listings</h3>
                <p class="text-slate-600">This realtor doesn't have any properties listed at the moment.</p>
                <a href="<?php echo esc_url(home_url('/properties')); ?>" class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Browse all properties</a>
            </div>
        <?php endif; wp_reset_postdata(); ?>
    </section>
</main>

<?php get_footer(); ?>
