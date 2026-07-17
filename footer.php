<?php
/**
 * Footer template — Airbnb-inspired: parish grid, dense link columns,
 * social row, language/currency selectors, dark theme.
 */

$logo_url     = get_option('mytheme_logo');
$social_links = get_option('mytheme_social_links', array());
$archive_url  = get_post_type_archive_link('property') ?: home_url('/properties/');

// Parishes with a live property count. One SQL scan instead of 14 queries.
$parishes = get_jamaica_cities();
$parish_counts = array();
global $wpdb;
$rows = $wpdb->get_results(
    "SELECT pm.meta_value AS city, COUNT(*) AS c
     FROM {$wpdb->postmeta} pm
     INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
     WHERE pm.meta_key = '_property_city'
       AND p.post_type   = 'property'
       AND p.post_status = 'publish'
     GROUP BY pm.meta_value"
);
foreach ($rows as $r) $parish_counts[$r->city] = (int) $r->c;
?>

<footer class="bg-slate-950 text-slate-300 mt-16 md:mt-24">
    <div class="max-w-7xl mx-auto px-4 md:px-6 pt-12 pb-6">

        <!-- ─── Properties by parish (Airbnb "Inspiration" style) ─── -->
        <section class="pb-10 border-b border-slate-800/70">
            <div class="flex items-end justify-between flex-wrap gap-3 mb-6">
                <div>
                    <h2 class="text-white text-xl md:text-2xl font-bold">Discover properties by parish</h2>
                    <p class="text-slate-400 text-sm mt-1">Browse listings across every corner of Jamaica.</p>
                </div>
                <a href="<?= esc_url($archive_url); ?>"
                   class="text-sm text-white hover:text-slate-300 inline-flex items-center gap-1.5 group">
                    All properties
                    <svg class="w-4 h-4 group-hover:translate-x-0.5 transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-x-6 gap-y-3">
                <?php foreach ($parishes as $name => $_coords):
                    $count = $parish_counts[$name] ?? 0;
                    $url   = add_query_arg('prop_city', rawurlencode($name), $archive_url);
                    ?>
                    <a href="<?= esc_url($url); ?>"
                       class="group flex items-start justify-between gap-3 py-2 border-b border-slate-800/50 hover:border-slate-500 transition">
                        <div class="min-w-0">
                            <p class="text-slate-200 text-sm font-medium group-hover:text-white transition truncate">
                                Properties in <?= esc_html($name); ?>
                            </p>
                            <p class="text-slate-500 text-xs mt-0.5">
                                <?= $count > 0 ? esc_html($count) . ' ' . _n('home', 'homes', $count) : 'Explore'; ?>
                            </p>
                        </div>
                        <svg class="w-4 h-4 text-slate-600 shrink-0 mt-1 group-hover:text-white group-hover:translate-x-0.5 transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ─── Main link columns ─── -->
        <section class="grid grid-cols-2 md:grid-cols-4 gap-8 py-10">

            <div>
                <h3 class="text-white font-semibold mb-4 text-sm tracking-wide">Support</h3>
                <ul class="space-y-3 text-sm">
                    <li><a href="<?= esc_url(home_url('/contact')); ?>" class="hover:text-white transition">Help Centre</a></li>
                    <li><a href="<?= esc_url(home_url('/contact')); ?>" class="hover:text-white transition">Contact us</a></li>
                    <li><a href="<?= esc_url(home_url('/about')); ?>#faq" class="hover:text-white transition">Safety information</a></li>
                    <li><a href="<?= esc_url(home_url('/property/?listing_status=rent')); ?>" class="hover:text-white transition">Report a listing</a></li>
                    <li><a href="<?= esc_url(home_url('/contact')); ?>" class="hover:text-white transition">Neighbourhood support</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-white font-semibold mb-4 text-sm tracking-wide">Discover</h3>
                <ul class="space-y-3 text-sm">
                    <li><a href="<?= esc_url(add_query_arg('listing_status', 'rent', $archive_url)); ?>" class="hover:text-white transition">Homes for rent</a></li>
                    <li><a href="<?= esc_url(add_query_arg('listing_status', 'sale', $archive_url)); ?>" class="hover:text-white transition">Homes for sale</a></li>
                    <li><a href="<?= esc_url(add_query_arg('featured', 'true', $archive_url)); ?>" class="hover:text-white transition">Featured listings</a></li>
                    <li><a href="<?= esc_url(home_url('/resources')); ?>" class="hover:text-white transition">Blog &amp; Guides</a></li>
                    <li><a href="<?= esc_url(home_url('/pricing')); ?>" class="hover:text-white transition">Pricing plans</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-white font-semibold mb-4 text-sm tracking-wide">For Realtors</h3>
                <ul class="space-y-3 text-sm">
                    <li><a href="<?= esc_url(home_url('/register')); ?>" class="hover:text-white transition">List your property</a></li>
                    <li><a href="<?= esc_url(home_url('/dashboard')); ?>" class="hover:text-white transition">Agent dashboard</a></li>
                    <li><a href="<?= esc_url(home_url('/pricing')); ?>" class="hover:text-white transition">Realtor plans</a></li>
                    <li><a href="<?= esc_url(home_url('/resources')); ?>" class="hover:text-white transition">Marketing tips</a></li>
                    <li><a href="<?= esc_url(home_url('/contact')); ?>" class="hover:text-white transition">Partner with us</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-white font-semibold mb-4 text-sm tracking-wide">Company</h3>
                <ul class="space-y-3 text-sm">
                    <li><a href="<?= esc_url(home_url('/about')); ?>" class="hover:text-white transition">About us</a></li>
                    <li><a href="<?= esc_url(home_url('/resources')); ?>" class="hover:text-white transition">Newsroom</a></li>
                    <li><a href="<?= esc_url(home_url('/contact')); ?>" class="hover:text-white transition">Careers</a></li>
                    <li><a href="<?= esc_url(home_url('/contact')); ?>" class="hover:text-white transition">Investors</a></li>
                    <li><a href="<?= esc_url(home_url('/about')); ?>#trust" class="hover:text-white transition">Trust &amp; Safety</a></li>
                </ul>
            </div>
        </section>

        <!-- ─── Bottom bar ─── -->
        <section class="border-t border-slate-800/70 pt-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">

            <!-- Logo + copyright + legal -->
            <div class="flex items-center gap-4 flex-wrap">
                <?php if ($logo_url): ?>
                    <a href="<?= esc_url(home_url('/')); ?>" class="inline-flex">
                        <img src="<?= esc_url($logo_url); ?>" alt="<?= esc_attr(get_bloginfo('name')); ?>"
                             class="h-10 w-auto brightness-0 invert opacity-90">
                    </a>
                <?php else: ?>
                    <a href="<?= esc_url(home_url('/')); ?>" class="text-white font-bold text-lg">
                        <?= esc_html(get_bloginfo('name')); ?>
                    </a>
                <?php endif; ?>

                <span class="text-xs text-slate-500">
                    &copy; <?= date('Y'); ?> <?= esc_html(get_bloginfo('name')); ?>
                </span>
                <span class="text-slate-700 hidden md:inline">·</span>
                <a href="<?= esc_url(home_url('/privacy')); ?>" class="text-xs text-slate-400 hover:text-white transition">Privacy</a>
                <span class="text-slate-700">·</span>
                <a href="<?= esc_url(home_url('/terms')); ?>" class="text-xs text-slate-400 hover:text-white transition">Terms</a>
                <span class="text-slate-700">·</span>
                <a href="<?= esc_url(home_url('/sitemap.xml')); ?>" class="text-xs text-slate-400 hover:text-white transition">Sitemap</a>
            </div>

            <!-- Language / currency + social -->
            <div class="flex items-center gap-4 flex-wrap">
                <button type="button"
                        class="inline-flex items-center gap-1.5 text-xs text-white hover:bg-slate-800 px-3 py-1.5 rounded-full border border-slate-700 transition">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2 12h20M12 2a15.3 15.3 0 010 20M12 2a15.3 15.3 0 000 20"/>
                    </svg>
                    English (JM)
                </button>
                <button type="button"
                        class="inline-flex items-center gap-1.5 text-xs text-white hover:bg-slate-800 px-3 py-1.5 rounded-full border border-slate-700 transition">
                    <span class="font-semibold">USD</span>
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <?php if (!empty($social_links) && is_array($social_links)): ?>
                    <div class="flex items-center gap-2 md:ml-2">
                        <?php foreach ($social_links as $s):
                            if (empty($s['link']) || empty($s['image'])) continue; ?>
                            <a href="<?= esc_url($s['link']); ?>" target="_blank" rel="noopener"
                               class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 flex items-center justify-center transition">
                                <img src="<?= esc_url($s['image']); ?>" alt="" class="w-4 h-4 object-contain brightness-0 invert opacity-80">
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
