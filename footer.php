<?php
/**
 * Footer template — trimmed per feedback log.
 * Kept: 4 short link columns + bottom bar (logo, copyright, legal,
 * language/currency, social). Removed: full parish grid.
 */

$logo_url     = get_option('mytheme_logo');
$social_links = get_option('mytheme_social_links', array());
$archive_url  = get_post_type_archive_link('property') ?: home_url('/properties/');
?>

<footer class="bg-slate-950 text-slate-300 mt-16 md:mt-24">
    <div class="max-w-7xl mx-auto px-4 md:px-6 pt-12 pb-6">

        <!-- ─── Link columns (trimmed per client) ─── -->
        <section class="grid grid-cols-2 md:grid-cols-4 gap-8 pb-10">

            <div>
                <h3 class="text-white font-semibold mb-4 text-sm tracking-wide">Support</h3>
                <ul class="space-y-3 text-sm">
                    <li><a href="<?= esc_url(home_url('/contact')); ?>" class="hover:text-white transition">Contact us</a></li>
                    <li><a href="<?= esc_url(home_url('/about')); ?>#faq" class="hover:text-white transition">Safety information</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-white font-semibold mb-4 text-sm tracking-wide">Discover</h3>
                <ul class="space-y-3 text-sm">
                    <li><a href="<?= esc_url(add_query_arg('listing_status', 'rent', $archive_url)); ?>" class="hover:text-white transition">Homes for rent</a></li>
                    <li><a href="<?= esc_url(add_query_arg('listing_status', 'sale', $archive_url)); ?>" class="hover:text-white transition">Homes for sale</a></li>
                    <li><a href="<?= esc_url(home_url('/pricing')); ?>" class="hover:text-white transition">Pricing plans</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-white font-semibold mb-4 text-sm tracking-wide">For Realtors</h3>
                <ul class="space-y-3 text-sm">
                    <li><a href="<?= esc_url(home_url('/register?as=agent')); ?>" class="hover:text-white transition">List your property</a></li>
                    <li><a href="<?= esc_url(home_url('/pricing')); ?>" class="hover:text-white transition">Realtor plans</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-white font-semibold mb-4 text-sm tracking-wide">Company</h3>
                <ul class="space-y-3 text-sm">
                    <li><a href="<?= esc_url(home_url('/about')); ?>" class="hover:text-white transition">About us</a></li>
                </ul>
            </div>
        </section>

        <!-- ─── Bottom bar ─── -->
        <section class="border-t border-slate-800/70 pt-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">

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
            </div>

            <div class="flex items-center gap-4 flex-wrap"
                 x-data="footerPrefs()" x-init="init()">

                <button type="button"
                        class="inline-flex items-center gap-1.5 text-xs text-white hover:bg-slate-800 px-3 py-1.5 rounded-full border border-slate-700 transition">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2 12h20M12 2a15.3 15.3 0 010 20M12 2a15.3 15.3 0 000 20"/>
                    </svg>
                    English (JM)
                </button>

                <div class="relative" @click.outside="showCurrency = false">
                    <button type="button" @click="showCurrency = !showCurrency"
                            class="inline-flex items-center gap-1.5 text-xs text-white hover:bg-slate-800 px-3 py-1.5 rounded-full border border-slate-700 transition">
                        <span class="font-semibold" x-text="currency.toUpperCase()"></span>
                        <svg class="w-3 h-3 transition" :class="showCurrency && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="showCurrency" x-transition x-cloak
                         class="absolute bottom-full right-0 mb-2 w-40 bg-slate-800 border border-slate-700 rounded-xl shadow-xl p-1 z-30">
                        <template x-for="opt in currencyOptions" :key="opt.code">
                            <button type="button" @click="selectCurrency(opt.code)"
                                    :class="currency === opt.code ? 'bg-slate-700 text-white' : 'text-slate-300 hover:bg-slate-700/60'"
                                    class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs">
                                <span x-text="opt.label"></span>
                                <svg x-show="currency === opt.code" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>

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

<script>
function footerPrefs() {
    return {
        showCurrency: false,
        currency: 'usd',
        currencyOptions: [
            { code: 'usd', label: 'USD — US Dollar' },
            { code: 'jmd', label: 'JMD — Jamaican Dollar' },
        ],
        init() {
            const url = new URL(window.location.href);
            const fromUrl = (url.searchParams.get('currency') || '').toLowerCase();
            const fromLs  = (localStorage.getItem('pt_currency') || '').toLowerCase();
            const valid   = c => c === 'usd' || c === 'jmd';
            this.currency = valid(fromUrl) ? fromUrl : (valid(fromLs) ? fromLs : 'usd');
            if (this.currency !== fromLs) localStorage.setItem('pt_currency', this.currency);
        },
        selectCurrency(code) {
            if (code === this.currency) { this.showCurrency = false; return; }
            this.currency = code;
            this.showCurrency = false;
            localStorage.setItem('pt_currency', code);
            window.dispatchEvent(new CustomEvent('pt-currency-changed', { detail: { currency: code } }));
            const url = new URL(window.location.href);
            url.searchParams.set('currency', code);
            window.location.href = url.toString();
        },
    };
}
</script>

<?php wp_footer(); ?>
</body>
</html>
