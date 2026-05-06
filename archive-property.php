<?php
/**
 * Archive template for properties with advanced filtering
 */
get_header();

$cities_data = get_jamaica_cities();

$filter_params = array(
    'search' => sanitize_text_field($_GET['search'] ?? ''),
    'property_type' => sanitize_text_field($_GET['property_type'] ?? ''),
    'price_min' => intval($_GET['price_min'] ?? 0),
    'price_max' => intval($_GET['price_max'] ?? 9999999999),
    'area_min' => intval($_GET['area_min'] ?? 0),
    'area_max' => intval($_GET['area_max'] ?? 100000),
    'beds' => intval($_GET['beds'] ?? 0),
    'baths' => intval($_GET['baths'] ?? 0),
    'city' => sanitize_text_field($_GET['prop_city'] ?? ''),
    'location' => sanitize_text_field($_GET['location'] ?? ''),
    'keyword' => sanitize_text_field($_GET['keyword'] ?? ''),
    'sort' => sanitize_text_field($_GET['sort'] ?? 'newest'),
    'view' => sanitize_text_field($_GET['view'] ?? 'list'),
    'featured' => sanitize_text_field($_GET['featured'] ?? ''),
    'listing_status' => sanitize_text_field($_GET['listing_status'] ?? ''),
    'currency' => sanitize_text_field($_GET['currency'] ?? 'usd'),
    'page' => max(1, intval($_GET['paged'] ?? 1)),
);

// Get property types with parent-child hierarchy
$parent_types = get_terms(array(
    'taxonomy' => 'property_type',
    'hide_empty' => false,
    'parent' => 0,
));

$property_type_hierarchy = [];

foreach ($parent_types as $parent) {
    $children = get_terms([
        'taxonomy' => 'property_type',
        'hide_empty' => false,
        'parent' => $parent->term_id,
    ]);

    $children_with_icons = array_map(function ($child) {
        return [
            'term_id' => $child->term_id,
            'name' => $child->name,
            'slug' => $child->slug,
            'icon' => get_field('icons', 'property_type_' . $child->term_id),
        ];
    }, $children);

    $property_type_hierarchy[] = [
        'parent' => [
            'term_id' => $parent->term_id,
            'name' => $parent->name,
        ],
        'children' => $children_with_icons,
    ];
}

// Get bedrooms and bathrooms for the single selector
$bedrooms = get_terms(array(
    'taxonomy' => 'bedroom',
    'hide_empty' => false,
));

$bathrooms = get_terms(array(
    'taxonomy' => 'bathroom',
    'hide_empty' => false,
));

function sort_terms_numerically($terms)
{
    if (empty($terms) || is_wp_error($terms)) {
        return $terms;
    }
    usort($terms, function ($a, $b) {
        return intval($a->name) - intval($b->name);
    });
    return $terms;
}

$bedrooms = sort_terms_numerically($bedrooms);
$bathrooms = sort_terms_numerically($bathrooms);
?>


<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <div class="max-w-[90%] mx-auto px-4 py-8 md:py-12">
        <!-- Main Content -->
        <div
            <?php
$listing_statuses_archive = get_terms(array('taxonomy' => 'property_listing_status', 'hide_empty' => false));
?>
            x-data="propertyArchiveFiltering(<?php echo htmlspecialchars(json_encode($filter_params)); ?>, <?php echo htmlspecialchars(json_encode($cities_data)); ?>, <?php echo htmlspecialchars(json_encode($property_type_hierarchy)); ?>, <?php echo htmlspecialchars(json_encode($bedrooms)); ?>, <?php echo htmlspecialchars(json_encode($bathrooms)); ?>, <?php echo htmlspecialchars(json_encode($listing_statuses_archive)); ?>)">

            <!-- ─────────────────────────────────────────────────────────────────
                 REAL-ESTATE FILTER BAR (Zillow / Realtor.com style)
                 - Big city/location search
                 - Inline pill toggles for Buy/Rent/All
                 - Inline dropdown chips for Price, Beds, Baths, Type
                 - "More filters" expander for everything else
                 ───────────────────────────────────────────────────────────────── -->
            <div class="mb-6 sticky top-0 z-50 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80 rounded-2xl shadow-md border border-slate-200">

                <!-- Row 1: Search + Buy/Rent toggle + Sort/View -->
                <div class="px-4 md:px-5 pt-4 md:pt-5 pb-3 flex flex-col lg:flex-row lg:items-center gap-3">

                    <!-- Buy / Rent / All segmented toggle -->
                    <div class="inline-flex items-center bg-slate-100 rounded-full p-1 gap-1 self-start lg:self-auto">
                        <button type="button"
                            @click="filters.listingStatus = ''; applyFilters()"
                            :class="filters.listingStatus === ''
                                ? 'bg-white text-[var(--primary-color)] shadow-sm'
                                : 'text-slate-600 hover:text-slate-900'"
                            class="px-4 md:px-5 py-1.5 rounded-full text-sm font-semibold transition font-inter">
                            All
                        </button>
                        <template x-for="status in listingStatuses" :key="status.slug">
                            <button type="button"
                                @click="filters.listingStatus = status.slug; applyFilters()"
                                :class="filters.listingStatus === status.slug
                                    ? 'bg-white text-[var(--primary-color)] shadow-sm'
                                    : 'text-slate-600 hover:text-slate-900'"
                                class="px-4 md:px-5 py-1.5 rounded-full text-sm font-semibold transition font-inter"
                                x-text="status.name">
                            </button>
                        </template>
                    </div>

                    <!-- Big search: Parish dropdown + free-text location -->
                    <div class="flex-1 flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-full pl-4 pr-2 py-1.5 focus-within:ring-2 focus-within:ring-[var(--primary-color)]/30 focus-within:border-[var(--primary-color)] transition">
                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <select id="city-select" x-model="filters.city"
                            @change="resetLocationSuggestions(); applyFilters()"
                            class="bg-transparent border-0 text-sm font-medium text-slate-800 focus:outline-none focus:ring-0 px-0 py-1 max-w-[160px]">
                            <option value="">Any parish</option>
                            <template x-for="city in citiesList" :key="city">
                                <option :value="city" x-text="city"></option>
                            </template>
                        </select>
                        <span class="w-px h-5 bg-slate-300 hidden md:block"></span>
                        <input type="text" x-model="filters.location" @input="searchLocations($event)"
                            @focus="showLocationSuggestions = true"
                            @blur="setTimeout(() => showLocationSuggestions = false, 200)"
                            @keydown.enter.prevent="applyFilters()"
                            placeholder="Address, neighbourhood…"
                            class="flex-1 bg-transparent border-0 text-sm placeholder-slate-400 text-slate-800 focus:outline-none focus:ring-0 px-1 py-1 min-w-0">
                        <button type="button" @click="applyFilters()"
                            class="bg-[var(--primary-color)] hover:bg-blue-700 text-white text-sm font-semibold px-4 md:px-5 py-2 rounded-full transition shrink-0">
                            Search
                        </button>

                        <div x-show="showLocationSuggestions && locationSuggestions.length"
                             class="absolute top-full left-4 right-4 md:left-auto md:right-auto md:w-[420px] mt-1 bg-white border border-slate-200 rounded-xl shadow-xl z-30 max-h-72 overflow-y-auto">
                            <template x-for="location in locationSuggestions" :key="location">
                                <button type="button"
                                    @click="filters.location = location; showLocationSuggestions = false; applyFilters()"
                                    class="w-full text-left px-4 py-2.5 hover:bg-blue-50 text-slate-800 text-sm flex items-center gap-2 font-inter"
                                    >
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span x-text="location"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- View toggle + Sort -->
                    <div class="flex items-center gap-2 self-start lg:self-auto">
                        <div class="hidden md:inline-flex bg-slate-100 rounded-lg p-1">
                            <button type="button" @click="viewType = 'list'"
                                :class="viewType === 'list' ? 'bg-white text-[var(--primary-color)] shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                                class="p-2 rounded-md transition" title="List view">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/></svg>
                            </button>
                            <button type="button" @click="viewType = 'grid'"
                                :class="viewType === 'grid' ? 'bg-white text-[var(--primary-color)] shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                                class="p-2 rounded-md transition" title="Grid view">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M3 3h7v7H3zm11 0h7v7h-7zm-11 11h7v7H3zm11 0h7v7h-7z"/></svg>
                            </button>
                        </div>
                        <select x-model="sortBy" @change="applyFilters()"
                            class="px-3 py-2 border border-slate-200 bg-white rounded-lg focus:ring-2 focus:ring-[var(--primary-color)]/30 focus:border-[var(--primary-color)] text-slate-900 text-sm font-medium">
                            <option value="newest">Newest</option>
                            <option value="featured">Featured</option>
                            <option value="price-low">Price: Low → High</option>
                            <option value="price-high">Price: High → Low</option>
                        </select>
                    </div>
                </div>

                <!-- Row 2: Inline filter chips (Price / Beds / Baths / Home Type / More) -->
                <div class="px-4 md:px-5 pb-4 flex items-center gap-2 flex-wrap border-t border-slate-100 pt-3">

                    <!-- Price chip -->
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button" @click="open = !open"
                            :class="filters.priceRange ? 'border-[var(--primary-color)] text-[var(--primary-color)] bg-blue-50' : 'border-slate-200 text-slate-700 hover:border-slate-300'"
                            class="inline-flex items-center gap-2 px-4 py-2 border rounded-full text-sm font-medium bg-white transition">
                            <span x-text="filters.priceRange ? (priceRangeOptions.find(o => o.value === filters.priceRange)?.label || 'Price') : 'Price'"></span>
                            <svg class="w-4 h-4" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-transition
                             class="absolute top-full left-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl z-30 w-72 p-2">
                            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide px-3 pt-2 pb-1 flex items-center justify-between">
                                <span>Price (<span x-text="selectedCurrency === 'jmd' ? 'JMD' : 'USD'"></span>)</span>
                                <select x-model="selectedCurrency" @change="onCurrencyChange()"
                                    class="text-xs border border-slate-200 rounded px-2 py-0.5 normal-case font-medium">
                                    <option value="usd">USD</option>
                                    <option value="jmd">JMD</option>
                                </select>
                            </div>
                            <button type="button"
                                @click="filters.priceRange = ''; applyPriceRange(''); applyFilters(); open = false"
                                :class="!filters.priceRange ? 'bg-blue-50 text-[var(--primary-color)] font-semibold' : 'text-slate-700'"
                                class="w-full text-left px-3 py-2 rounded-lg text-sm hover:bg-slate-50">Any Price</button>
                            <template x-for="opt in priceRangeOptions" :key="opt.value">
                                <button type="button"
                                    @click="filters.priceRange = opt.value; applyPriceRange(opt.value); applyFilters(); open = false"
                                    :class="filters.priceRange === opt.value ? 'bg-blue-50 text-[var(--primary-color)] font-semibold' : 'text-slate-700'"
                                    class="w-full text-left px-3 py-2 rounded-lg text-sm hover:bg-slate-50"
                                    x-text="opt.label"></button>
                            </template>
                        </div>
                    </div>

                    <!-- Beds chip -->
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button" @click="open = !open"
                            :class="filters.beds ? 'border-[var(--primary-color)] text-[var(--primary-color)] bg-blue-50' : 'border-slate-200 text-slate-700 hover:border-slate-300'"
                            class="inline-flex items-center gap-2 px-4 py-2 border rounded-full text-sm font-medium bg-white transition">
                            <span x-text="filters.beds ? (filters.beds + '+ Beds') : 'Beds'"></span>
                            <svg class="w-4 h-4" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-transition
                             class="absolute top-full left-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl z-30 w-56 p-2">
                            <button type="button"
                                @click="filters.beds = 0; applyFilters(); open = false"
                                :class="!filters.beds ? 'bg-blue-50 text-[var(--primary-color)] font-semibold' : 'text-slate-700'"
                                class="w-full text-left px-3 py-2 rounded-lg text-sm hover:bg-slate-50">Any</button>
                            <template x-for="term in bedroomTerms" :key="term.term_id">
                                <button type="button"
                                    @click="filters.beds = parseInt(term.name); applyFilters(); open = false"
                                    :class="filters.beds === parseInt(term.name) ? 'bg-blue-50 text-[var(--primary-color)] font-semibold' : 'text-slate-700'"
                                    class="w-full text-left px-3 py-2 rounded-lg text-sm hover:bg-slate-50"
                                    x-text="term.name + '+ Beds'"></button>
                            </template>
                        </div>
                    </div>

                    <!-- Baths chip -->
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button" @click="open = !open"
                            :class="filters.baths ? 'border-[var(--primary-color)] text-[var(--primary-color)] bg-blue-50' : 'border-slate-200 text-slate-700 hover:border-slate-300'"
                            class="inline-flex items-center gap-2 px-4 py-2 border rounded-full text-sm font-medium bg-white transition">
                            <span x-text="filters.baths ? (filters.baths + '+ Baths') : 'Baths'"></span>
                            <svg class="w-4 h-4" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-transition
                             class="absolute top-full left-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl z-30 w-56 p-2">
                            <button type="button"
                                @click="filters.baths = 0; applyFilters(); open = false"
                                :class="!filters.baths ? 'bg-blue-50 text-[var(--primary-color)] font-semibold' : 'text-slate-700'"
                                class="w-full text-left px-3 py-2 rounded-lg text-sm hover:bg-slate-50">Any</button>
                            <template x-for="term in bathroomTerms" :key="term.term_id">
                                <button type="button"
                                    @click="filters.baths = parseFloat(term.name); applyFilters(); open = false"
                                    :class="filters.baths === parseFloat(term.name) ? 'bg-blue-50 text-[var(--primary-color)] font-semibold' : 'text-slate-700'"
                                    class="w-full text-left px-3 py-2 rounded-lg text-sm hover:bg-slate-50"
                                    x-text="term.name + '+ Baths'"></button>
                            </template>
                        </div>
                    </div>

                    <!-- Home type chip (opens the property-type panel) -->
                    <button type="button" @click="showFilters = !showFilters"
                        :class="filters.types.length ? 'border-[var(--primary-color)] text-[var(--primary-color)] bg-blue-50' : 'border-slate-200 text-slate-700 hover:border-slate-300'"
                        class="inline-flex items-center gap-2 px-4 py-2 border rounded-full text-sm font-medium bg-white transition">
                        <span x-text="filters.types.length ? (filters.types.length + ' Home types') : 'Home Type'"></span>
                        <svg class="w-4 h-4" :class="showFilters && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <!-- Featured -->
                    <button type="button" @click="filters.featured = !filters.featured; applyFilters()"
                        :class="filters.featured ? 'border-amber-500 text-amber-600 bg-amber-50' : 'border-slate-200 text-slate-700 hover:border-slate-300'"
                        class="inline-flex items-center gap-1.5 px-4 py-2 border rounded-full text-sm font-medium bg-white transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.39 7.36H22l-6.18 4.49L18.21 22 12 17.27 5.79 22l2.39-8.15L2 9.36h7.61L12 2z"/></svg>
                        Featured
                    </button>

                    <div class="ml-auto flex items-center gap-3">
                        <span class="text-slate-500 text-sm hidden md:inline-flex items-center gap-1">
                            <span x-text="totalResults" class="font-bold text-slate-900"></span>
                            <span>homes</span>
                        </span>
                        <button type="button" @click="clearFilters()"
                            class="text-sm text-slate-500 hover:text-red-600 underline-offset-2 hover:underline transition">
                            Clear all
                        </button>
                    </div>
                </div>

                <!-- Row 3: expandable property-type panel (kept) -->
                <div x-show="showFilters" x-transition
                     class="border-t border-slate-100 px-4 md:px-5 py-4 bg-slate-50/50 rounded-b-2xl">
                    <div x-show="propertyTypeHierarchy.length > 0">
                        <div x-data="{
                            propertyTypeTab: propertyTypeHierarchy.length > 0 ? propertyTypeHierarchy[0].parent.term_id : '',
                        }">
                            <div class="flex gap-1 border-b border-slate-200 mb-4 flex-wrap">
                                <template x-for="group in propertyTypeHierarchy" :key="group.parent.term_id">
                                    <button type="button" @click="propertyTypeTab = group.parent.term_id"
                                        :class="propertyTypeTab === group.parent.term_id ? 'border-[var(--primary-color)] text-[var(--primary-color)]' : 'border-transparent text-slate-600 hover:text-slate-900'"
                                        class="px-3 py-2 font-semibold text-sm border-b-2 transition font-inter">
                                        <span x-text="group.parent.name"></span>
                                    </button>
                                </template>
                            </div>
                            <template x-for="group in propertyTypeHierarchy" :key="group.parent.term_id">
                                <div x-show="propertyTypeTab === group.parent.term_id" x-transition
                                    class="flex flex-wrap gap-2">
                                    <template x-for="child in group.children" :key="child.slug">
                                        <button type="button"
                                            @click="filters.types.includes(child.slug) ? filters.types.splice(filters.types.indexOf(child.slug), 1) : filters.types.push(child.slug); applyFilters();"
                                            :class="filters.types.includes(child.slug) ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)]' : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300'"
                                            class="px-3 py-1.5 rounded-full border text-sm font-medium transition flex items-center gap-2">
                                            <span x-html="child.icon" class="w-4 h-4 fill-current block"></span>
                                            <span x-text="child.name"></span>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results + Ad Sidebar Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-6">

                <!-- Results column -->
                <div>
                    <!-- Top leaderboard ad -->
                    <div class="mb-6">
                        <?php get_template_part('template-parts/component', 'ad-space', ['slot' => 'leaderboard', 'label' => 'Sponsored']); ?>
                    </div>

                    <!-- Skeleton Cards Loading State -->
                    <div x-show="loading" :class="viewType === 'list' ? 'space-y-4' : 'grid grid-cols-1 md:grid-cols-3 gap-6'">
                        <template x-for="i in [1,2,3,4,5,6]" :key="i">
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden animate-pulse"
                                 :class="viewType === 'list' && 'flex items-center'">
                                <div :class="viewType === 'list' ? 'h-40 w-1/3 shrink-0' : 'h-56'" class="bg-gradient-to-r from-slate-200 to-slate-100"></div>
                                <div class="p-6 flex-1">
                                    <div class="h-6 bg-slate-200 rounded w-3/4 mb-4"></div>
                                    <div class="h-4 bg-slate-200 rounded w-full mb-3"></div>
                                    <div class="h-4 bg-slate-200 rounded w-2/3 mb-6"></div>
                                    <div class="flex gap-2 mb-4">
                                        <div class="h-8 bg-slate-200 rounded-full w-20"></div>
                                        <div class="h-8 bg-slate-200 rounded-full w-20"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Grid View -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6" x-show="!loading && viewType === 'grid'">
                        <template x-for="property in properties" :key="property.id">
                            <template x-if="property">
                                <?php get_template_part('template-parts/component', 'property-card'); ?>
                            </template>
                        </template>
                    </div>

                    <!-- List View (default) -->
                    <div class="space-y-4" x-show="!loading && viewType === 'list'">
                        <template x-for="property in properties" :key="property.id">
                            <template x-if="property">
                                <?php get_template_part('template-parts/component', 'property-card'); ?>
                            </template>
                        </template>
                    </div>

                    <!-- No Results State -->
                    <div x-show="!loading && allProperties.length === 0"
                        class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
                        <div class="mb-4 text-5xl">🔍</div>
                        <p class="text-slate-600 text-lg mb-6 font-medium">No properties found matching your criteria.</p>
                        <button @click="clearFilters()"
                            class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-3 px-8 rounded-lg transition-all shadow-sm hover:shadow-md">Reset
                            Filters</button>
                    </div>

                    <!-- Infinite Scroll Loading Indicator -->
                    <div x-show="loading && allProperties.length > 0" class="flex justify-center mt-10">
                        <div class="inline-flex items-center gap-2 px-6 py-3 bg-white rounded-lg shadow-sm border border-slate-200">
                            <div class="w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                            <span class="text-slate-700 font-medium">Loading more...</span>
                        </div>
                    </div>

                    <!-- Infinite Scroll Trigger Element -->
                    <div id="infinite-scroll-trigger" x-ref="infiniteScrollTrigger" class="py-8"></div>
                </div>

                <!-- Ads sidebar — sticky on desktop, hidden on small screens -->
                <aside class="hidden lg:block">
                    <div class="sticky top-[180px] flex flex-col gap-6">
                        <?php get_template_part('template-parts/component', 'ad-space', ['slot' => 'sidebar', 'label' => 'Sponsored']); ?>
                        <?php get_template_part('template-parts/component', 'ad-space', ['slot' => 'sidebar', 'label' => 'Sponsored']); ?>

                        <!-- Newsletter / promo card -->
                        <div class="bg-gradient-to-br from-blue-700 to-indigo-700 text-white rounded-xl p-5 shadow-lg">
                            <p class="text-xs font-bold uppercase tracking-wider text-blue-200 mb-2">List your property</p>
                            <h4 class="text-xl font-bold mb-2 leading-tight">Reach buyers across Jamaica</h4>
                            <p class="text-sm text-blue-50 mb-4">Get your listing in front of thousands of property seekers every month.</p>
                            <a href="<?php echo esc_url(home_url('/pricing')); ?>"
                               class="inline-flex items-center gap-1.5 bg-white text-blue-700 hover:bg-blue-50 font-semibold text-sm px-4 py-2 rounded-lg transition">
                                See pricing
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </a>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<!-- Nouislider CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.css">
<!-- TomSelect CSS (jQuery-free) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAUPkXXwkGt0xC5ongE7-62nzz6l7D3Nf4&libraries=places,marker&v=beta"></script>

<!-- Alpine.js Script with Google Geocoder & nouislider -->
<script>
    function propertyArchiveFiltering(initialParams, citiesData, propertyTypeHierarchy, bedrooms, bathrooms, listingStatusesData) {
        return {
            filters: {
                search: initialParams.search || '',
                types: initialParams.property_type ? initialParams.property_type.split(',') : [],
                priceMin: initialParams.price_min || 0,
                priceMax: initialParams.price_max || 9999999999,
                priceRange: '',
                areaMin: initialParams.area_min || 0,
                areaMax: initialParams.area_max || 100000,
                beds: initialParams.beds || 0,
                baths: initialParams.baths || 0,
                city: initialParams.city || '',
                location: initialParams.location || '',
                keyword: initialParams.keyword || '',
                featured: initialParams.featured === 'true' ? true : false,
                listingStatus: initialParams.listing_status || '',
            },
            sortBy: initialParams.sort || 'newest',
            viewType: initialParams.view || 'list',
            allProperties: [],
            properties: [],
            loading: false,
            currentPage: 1,
            totalResults: 0,
            totalPages: 1,

            citiesData: citiesData,
            citiesList: Object.keys(citiesData),
            propertyTypeHierarchy: propertyTypeHierarchy,
            bedroomTerms: bedrooms || [],
            bathroomTerms: bathrooms || [],
            listingStatuses: listingStatusesData || [],
            selectedCurrency: initialParams.currency || 'usd',
            usdToJmdRate: 157,
            priceOptions: [],
            priceRangeOptions: [],

            showFilters: false,
            locationSuggestions: [],
            showLocationSuggestions: false,

            bedroomSlider: null,
            bathroomSlider: null,
            geocoder: null,
            autocompleteListener: null,

            init() {
                // Expose formatPrice globally so property-card component can use it
                window.formatPrice = (price) => this.formatPrice(price);
                this.setupTomSelect();
                this.geocoder = new google.maps.Geocoder();
                this.fetchExchangeRate();
                this.buildPriceOptions();
                // No default listing status — show all properties
                this.applyFilters();
                this.setupInfiniteScroll();
            },

            async fetchExchangeRate() {
                try {
                    const res = await fetch('https://api.exchangerate-api.com/v4/latest/USD');
                    const data = await res.json();
                    if (data && data.rates && data.rates.JMD) {
                        this.usdToJmdRate = data.rates.JMD;
                        this.buildPriceOptions();
                    }
                } catch(e) {
                    this.usdToJmdRate = 157;
                }
            },

            buildPriceOptions() {
                const isJmd = this.selectedCurrency === 'jmd';
                const usdRanges = [
                    { min: 0,       max: 50000,      label: '$0 – $50K' },
                    { min: 50000,   max: 70000,      label: '$50K – $70K' },
                    { min: 70000,   max: 100000,     label: '$70K – $100K' },
                    { min: 100000,  max: 150000,     label: '$100K – $150K' },
                    { min: 150000,  max: 200000,     label: '$150K – $200K' },
                    { min: 200000,  max: 300000,     label: '$200K – $300K' },
                    { min: 300000,  max: 500000,     label: '$300K – $500K' },
                    { min: 500000,  max: 750000,     label: '$500K – $750K' },
                    { min: 750000,  max: 1000000,    label: '$750K – $1M' },
                    { min: 1000000, max: 9999999999, label: '$1M+' },
                ];
                const jmdRanges = [
                    { min: 0,         max: 5000000,    label: 'J$0 – J$5M' },
                    { min: 5000000,   max: 10000000,   label: 'J$5M – J$10M' },
                    { min: 10000000,  max: 20000000,   label: 'J$10M – J$20M' },
                    { min: 20000000,  max: 30000000,   label: 'J$20M – J$30M' },
                    { min: 30000000,  max: 50000000,   label: 'J$30M – J$50M' },
                    { min: 50000000,  max: 75000000,   label: 'J$50M – J$75M' },
                    { min: 75000000,  max: 100000000,  label: 'J$75M – J$100M' },
                    { min: 100000000, max: 9999999999, label: 'J$100M+' },
                ];
                const ranges = isJmd ? jmdRanges : usdRanges;
                this.priceRangeOptions = ranges.map(r => ({
                    value: r.min + '_' + r.max,
                    label: r.label,
                    min: r.min,
                    max: r.max,
                }));
                // Reset price selection when currency changes
                this.filters.priceRange = '';
                this.filters.priceMin = 0;
                this.filters.priceMax = 9999999999;
            },

            applyPriceRange(value) {
                if (!value) {
                    this.filters.priceMin = 0;
                    this.filters.priceMax = 9999999999;
                    return;
                }
                const opt = this.priceRangeOptions.find(o => o.value === value);
                if (opt) {
                    this.filters.priceMin = opt.min;
                    this.filters.priceMax = opt.max;
                }
            },

            onCurrencyChange() {
                this.buildPriceOptions();
                // Re-expose so card re-renders with new currency
                window.formatPrice = (price) => this.formatPrice(price);
                this.applyFilters();
            },

            formatPrice(usdPrice) {
                if (this.selectedCurrency === 'jmd') {
                    const jmd = Math.round(usdPrice * this.usdToJmdRate);
                    return 'J$' + (jmd >= 1000000 ? (jmd/1000000).toFixed(2)+'M' : jmd.toLocaleString());
                }
                return '$' + (usdPrice >= 100000 ? (usdPrice/1000).toFixed(usdPrice%1000===0?0:1)+'K' : usdPrice.toLocaleString());
            },

            setupTomSelect() {
                const self = this;
                setTimeout(() => {
                    new TomSelect('#city-select', {
                        placeholder: 'Select a city...',
                        allowEmptyOption: true,
                        maxOptions: null,
                        onChange: (value) => {
                            self.resetLocationSuggestions();
                            self.applyFilters();
                        }
                    });
                }, 100);
            },

            priceSlider: null,
            areaSlider: null,

            setupInfiniteScroll() {
                const observer = new IntersectionObserver((entries) => {
                    if (entries[0].isIntersecting && !this.loading && this.currentPage < this.totalPages) {
                        this.loadMoreProperties();
                    }
                }, { threshold: 0.5 });

                const trigger = this.$refs.infiniteScrollTrigger;
                if (trigger) observer.observe(trigger);
            },

            resetLocationSuggestions() {
                this.filters.location = '';
                this.locationSuggestions = [];
                this.showLocationSuggestions = false;
            },

            searchLocations(event) {
                const query = event.target.value;
                if (!query || !this.filters.city) {
                    this.locationSuggestions = [];
                    this.showLocationSuggestions = false;
                    return;
                }

                this.fetchLocationSuggestions(query);
            },

            fetchLocationSuggestions(query) {
                if (!this.geocoder || !this.filters.city) {
                    console.warn('Geocoder not ready or city not selected');
                    return;
                }

                const self = this;
                const debounceTimer = null;

                this.geocoder.geocode({
                    address: query + ', ' + this.filters.city,
                    componentRestrictions: { country: 'jm' }
                }, (results, status) => {
                    if (status === 'OK' && results && results.length > 0) {
                        self.locationSuggestions = results.slice(0, 5).map(place => place.formatted_address);
                        self.showLocationSuggestions = true;
                    } else {
                        self.locationSuggestions = [];
                        self.showLocationSuggestions = false;
                    }
                });
            },

            applyFilters() {
                this.currentPage = 1;
                this.allProperties = [];
                this.loadMoreProperties();
            },

            loadMoreProperties() {
                this.loading = true;
                const params = new URLSearchParams();

                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.types.length) params.append('property_type', this.filters.types.join(','));
                if (this.filters.priceMin) params.append('price_min', this.filters.priceMin);
                if (this.filters.priceMax && this.filters.priceMax < 9999999999) params.append('price_max', this.filters.priceMax);
                if (this.filters.areaMin) params.append('area_min', this.filters.areaMin);
                if (this.filters.areaMax) params.append('area_max', this.filters.areaMax);
                if (this.filters.beds) params.append('beds_min', this.filters.beds);
                if (this.filters.baths) params.append('baths_min', this.filters.baths);
                if (this.filters.city) params.append('city', this.filters.city);
                if (this.filters.location) params.append('location', this.filters.location);
                if (this.filters.keyword) params.append('keyword', this.filters.keyword);
                if (this.filters.listingStatus) params.append('listing_status', this.filters.listingStatus);
                const sort = this.filters.featured ? 'featured' : this.sortBy;
                params.append('sort', sort);
                if (this.filters.featured) params.append('featured', 'true');
                params.append('paged', this.currentPage);
                params.append('per_page', 12);

                fetch(`<?php echo get_home_url(); ?>/wp-json/property/v1/search?${params.toString()}`)
                    .then(response => response.json())
                    .then(data => {
                        // Sort featured to top
                        let properties = data.properties || [];
                        const featured = properties.filter(p => p.featured);
                        const notFeatured = properties.filter(p => !p.featured);
                        properties = [...featured, ...notFeatured];

                        if (this.currentPage === 1) {
                            this.allProperties = properties;
                        } else {
                            this.allProperties = [...this.allProperties, ...properties];
                        }
                        this.totalResults = data.total;
                        this.totalPages = data.pages;
                        this.currentPage = data.current_page;
                        this.properties = this.allProperties;
                        this.loading = false;
                    })
                    .catch(error => {
                        console.error('Error fetching properties:', error);
                        this.loading = false;
                    });
            },

            clearFilters() {
                this.filters = {
                    search: '',
                    types: [],
                    priceMin: 0,
                    priceMax: 9999999999,
                    priceRange: '',
                    areaMin: 0,
                    areaMax: 100000,
                    beds: 0,
                    baths: 0,
                    city: '',
                    location: '',
                    keyword: '',
                    featured: false,
                    listingStatus: '',
                };
                this.sortBy = 'newest';
                this.selectedCurrency = 'usd';
                this.buildPriceOptions();

                // Reset TomSelect
                const select = document.getElementById('city-select');
                if (select?.tomselect) {
                    select.tomselect.clear();
                }



                this.applyFilters();
            },
        };
    }
</script>

<?php get_footer(); ?>