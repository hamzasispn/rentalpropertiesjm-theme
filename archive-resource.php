<?php
/**
 * Archive template for Resources
 * Place in theme root as: archive-resource.php
 */
get_header();

$resource_categories = get_terms(array(
    'taxonomy'   => 'resource_category',
    'hide_empty' => false,
));

$home_url = get_home_url();
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">

    <!-- Hero Banner -->
    <div class="bg-gradient-to-r from-[var(--primary-color)] to-blue-700 py-16 px-4">
        <div class="max-w-5xl mx-auto text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4 font-inter">Resources</h1>
            <p class="text-blue-100 text-lg md:text-xl max-w-2xl mx-auto">Guides, reports, and documents to help you make informed property decisions.</p>
        </div>
    </div>

    <!-- Banner Ad -->
    <div class="max-w-5xl mx-auto px-4 py-6">
        <?php get_template_part('template-parts/component', 'ad-space', ['slot' => 'leaderboard', 'label' => 'Advertisement']); ?>
    </div>

    <!-- Main Content -->
    <div class="max-w-[90%] mx-auto px-4 py-10"
        x-data="resourceArchive('<?php echo esc_js($home_url); ?>')">

        <!-- Search + Category Filter Bar -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 md:p-6 mb-8 flex flex-col md:flex-row gap-4 items-start md:items-center">

            <!-- Search -->
            <div class="relative flex-1 w-full">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" x-model="searchQuery" @input.debounce.400ms="fetchResources()"
                    placeholder="Search resources..."
                    class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent font-inter">
            </div>

            <!-- Category Pills -->
            <div class="flex flex-wrap gap-2">
                <button @click="activeCategory = ''; fetchResources()"
                    :class="activeCategory === '' ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)]' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'"
                    class="px-4 py-2 rounded-full border text-sm font-semibold transition font-inter">All</button>
                <?php if (!is_wp_error($resource_categories) && $resource_categories): foreach ($resource_categories as $cat): ?>
                <button @click="activeCategory = '<?php echo esc_js($cat->slug); ?>'; fetchResources()"
                    :class="activeCategory === '<?php echo esc_js($cat->slug); ?>' ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)]' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'"
                    class="px-4 py-2 rounded-full border text-sm font-semibold transition font-inter">
                    <?php echo esc_html($cat->name); ?>
                </button>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Results Count -->
        <p class="text-slate-600 text-sm mb-6 font-inter">
            Showing <span class="font-bold text-slate-900" x-text="totalResults"></span> resource<span x-show="totalResults !== 1">s</span>
        </p>

        <!-- Loading Skeleton -->
        <div x-show="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="i in [1,2,3,4,5,6]" :key="i">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden animate-pulse">
                    <div class="h-40 bg-gradient-to-r from-slate-200 to-slate-100"></div>
                    <div class="p-5">
                        <div class="h-5 bg-slate-200 rounded w-3/4 mb-3"></div>
                        <div class="h-4 bg-slate-200 rounded w-full mb-2"></div>
                        <div class="h-4 bg-slate-200 rounded w-2/3 mb-5"></div>
                        <div class="h-10 bg-slate-200 rounded w-full"></div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Resources Grid -->
        <div x-show="!loading && resources.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="resource in resources" :key="resource.id">
                <div class="bg-white rounded-xl shadow-sm hover:shadow-xl border border-slate-200 overflow-hidden transition-all duration-300 hover:-translate-y-1 flex flex-col">

                    <!-- Thumbnail / File Type Banner -->
                    <div class="relative h-40 bg-gradient-to-br from-slate-100 to-slate-200 overflow-hidden flex items-center justify-center">
                        <img x-show="resource.thumbnail" :src="resource.thumbnail" :alt="resource.title"
                            class="absolute inset-0 w-full h-full object-cover">
                        <div x-show="!resource.thumbnail" class="flex flex-col items-center gap-2">
                            <span class="text-5xl" x-text="fileIcon(resource.file_type)"></span>
                            <span class="text-xs font-semibold text-slate-500 uppercase font-inter" x-text="resource.file_type || 'doc'"></span>
                        </div>

                        <!-- File type badge -->
                        <div class="absolute top-3 right-3 px-2 py-1 rounded text-xs font-bold uppercase font-inter shadow"
                            :class="resource.file_type === 'pdf' ? 'bg-red-500 text-white' : 'bg-blue-600 text-white'"
                            x-text="resource.file_type || 'doc'">
                        </div>

                        <!-- Free / Members badge -->
                        <div class="absolute top-3 left-3 px-2 py-1 rounded text-xs font-bold font-inter shadow"
                            :class="resource.is_free ? 'bg-green-500 text-white' : 'bg-amber-500 text-white'"
                            x-text="resource.is_free ? 'Free' : 'Members'">
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-5 flex flex-col flex-1">
                        <!-- Categories -->
                        <div class="flex flex-wrap gap-1 mb-2" x-show="resource.categories.length">
                            <template x-for="cat in resource.categories" :key="cat">
                                <span class="text-[10px] font-semibold uppercase tracking-wide px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full font-inter" x-text="cat"></span>
                            </template>
                        </div>

                        <!-- Title -->
                        <h3 class="text-base font-semibold text-slate-900 mb-2 line-clamp-2 font-inter leading-snug">
                            <a :href="resource.permalink" x-text="resource.title" class="hover:text-[var(--primary-color)] transition"></a>
                        </h3>

                        <!-- Excerpt -->
                        <p class="text-sm text-slate-600 line-clamp-3 mb-4 font-inter flex-1" x-text="resource.excerpt || 'No description available.'"></p>

                        <!-- Meta -->
                        <div class="flex items-center gap-3 text-xs text-slate-500 font-inter mb-4">
                            <span x-text="resource.date"></span>
                            <span x-show="resource.download_count > 0">·</span>
                            <span x-show="resource.download_count > 0" x-text="resource.download_count + ' downloads'"></span>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <a :href="resource.permalink"
                                class="flex-1 text-center py-2.5 px-4 border border-[var(--primary-color)] text-[var(--primary-color)] rounded-lg text-sm font-semibold hover:bg-blue-50 transition font-inter">
                                View
                            </a>
                            <button x-show="resource.file_url"
                                @click="downloadResource(resource)"
                                class="flex-1 py-2.5 px-4 bg-[var(--primary-color)] text-white rounded-lg text-sm font-semibold hover:opacity-90 transition flex items-center justify-center gap-1.5 font-inter">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Download
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && resources.length === 0"
            class="bg-white rounded-xl shadow-sm border border-slate-200 p-16 text-center">
            <div class="text-5xl mb-4">📂</div>
            <p class="text-slate-600 text-lg font-medium mb-2">No resources found</p>
            <p class="text-slate-500 text-sm">Try adjusting your search or category filter.</p>
        </div>

        <!-- Load More -->
        <div x-show="!loading && currentPage < totalPages" class="text-center mt-10">
            <button @click="loadMore()"
                class="px-8 py-3 bg-white border border-slate-300 text-slate-700 font-semibold rounded-lg hover:bg-slate-50 transition shadow-sm font-inter">
                Load More Resources
            </button>
        </div>
    </div>
</div>

<script>
function resourceArchive(homeUrl) {
    return {
        resources: [],
        loading: true,
        searchQuery: '',
        activeCategory: '',
        currentPage: 1,
        totalPages: 1,
        totalResults: 0,

        init() {
            this.fetchResources();
        },

        async fetchResources() {
            this.loading = true;
            this.currentPage = 1;
            const params = new URLSearchParams({ paged: 1, per_page: 12 });
            if (this.searchQuery)   params.append('search',   this.searchQuery);
            if (this.activeCategory) params.append('category', this.activeCategory);

            try {
                const res  = await fetch(`${homeUrl}/wp-json/property/v1/resources?${params}`);
                const data = await res.json();
                this.resources     = data.resources || [];
                this.totalResults  = data.total     || 0;
                this.totalPages    = data.pages      || 1;
            } catch(e) {
                console.error('Error fetching resources:', e);
            }
            this.loading = false;
        },

        async loadMore() {
            this.currentPage++;
            const params = new URLSearchParams({ paged: this.currentPage, per_page: 12 });
            if (this.searchQuery)    params.append('search',   this.searchQuery);
            if (this.activeCategory) params.append('category', this.activeCategory);

            try {
                const res  = await fetch(`${homeUrl}/wp-json/property/v1/resources?${params}`);
                const data = await res.json();
                this.resources    = [...this.resources, ...(data.resources || [])];
                this.totalResults = data.total || 0;
                this.totalPages   = data.pages  || 1;
            } catch(e) {
                console.error('Error loading more resources:', e);
            }
        },

        async downloadResource(resource) {
            if (!resource.is_free && !this.isLoggedIn()) {
                window.location.href = '<?php echo wp_login_url(get_permalink()); ?>';
                return;
            }
            try {
                const res  = await fetch(`${homeUrl}/wp-json/property/v1/resource/${resource.id}/download`);
                const data = await res.json();
                if (data.success && data.file_url) {
                    const a = document.createElement('a');
                    a.href     = data.file_url;
                    a.download = resource.file_name || 'resource';
                    a.target   = '_blank';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    resource.download_count++;
                } else {
                    alert(data.message || 'Unable to download this file.');
                }
            } catch(e) {
                console.error('Download error:', e);
            }
        },

        isLoggedIn() {
            return <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;
        },

        fileIcon(type) {
            const icons = { pdf: '📄', doc: '📝', docx: '📝', ppt: '📊', pptx: '📊', xls: '📈', xlsx: '📈', zip: '🗜️', txt: '📃' };
            return icons[type] || '📋';
        },
    };
}
</script>

<?php get_footer(); ?>
