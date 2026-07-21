<?php
/**
 * Archive template for properties with advanced filtering
 */
get_header();

$cities_data = get_jamaica_cities();

// How many search presets does the current user already have? Used to
// conditionally show a "View all your search presets" shortcut in the
// filter row.
$pt_saved_search_count = 0;
$pt_member_home_url    = '';
if (is_user_logged_in() && function_exists('pt_get_saved_searches')) {
    $pt_saved_search_count = count(pt_get_saved_searches(get_current_user_id()));
    $pt_member_home_url    = function_exists('pt_get_member_dashboard_url')
        ? pt_get_member_dashboard_url()
        : home_url('/my-account/');
}

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
            <div class="mb-6 sticky top-0 z-50 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80 rounded-2xl shadow-md border border-slate-200">

                <!-- Row 1: Search + Buy/Rent toggle + Sort/View -->
                <div class="px-4 md:px-5 pt-4 md:pt-5 pb-3 flex flex-col lg:flex-row lg:items-center gap-3">

                    <!-- Buy / Rent / All segmented toggle -->
                    <div class="inline-flex items-center bg-slate-100 rounded-full p-1 gap-1 self-start lg:self-auto">
                        <button type="button"
                            @click="filters.listingStatus = ''; buildPriceOptions(); applyFilters()"
                            :class="filters.listingStatus === ''
                                ? 'bg-white text-[var(--primary-color)] shadow-sm'
                                : 'text-slate-600 hover:text-slate-900'"
                            class="px-4 md:px-5 py-1.5 rounded-full text-sm font-semibold transition font-inter">
                            All
                        </button>
                        <template x-for="status in listingStatuses" :key="status.slug">
                            <button type="button"
                                @click="filters.listingStatus = status.slug; buildPriceOptions(); applyFilters()"
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
                        <!-- Parish select — needs enough width for parish name + TomSelect's
                             dropdown chevron; without min-width it collapses and the arrow
                             overlaps the "Any parish" label. -->
                        <div class="shrink-0 min-w-[210px] md:min-w-[220px]">
                            <select id="city-select" x-model="filters.city"
                                @change="resetLocationSuggestions(); applyFilters()"
                                class="bg-transparent border-0 text-sm font-medium text-slate-800 focus:outline-none focus:ring-0 px-0 py-1 w-full">
                                <option value="">Any parish</option>
                                <template x-for="city in citiesList" :key="city">
                                    <option :value="city" x-text="city"></option>
                                </template>
                            </select>
                        </div>
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

                    <!-- Sort only — layout is card+map Airbnb-style, no list toggle. -->
                    <div class="flex items-center gap-2 self-start lg:self-auto">
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

                    <!-- Beds chip (1-5 plain, 6+ meta) -->
                    <div class="relative" x-data="{ open: false, options: [1,2,3,4,5,6] }" @click.outside="open = false">
                        <button type="button" @click="open = !open"
                            :class="filters.beds ? 'border-[var(--primary-color)] text-[var(--primary-color)] bg-blue-50' : 'border-slate-200 text-slate-700 hover:border-slate-300'"
                            class="inline-flex items-center gap-2 px-4 py-2 border rounded-full text-sm font-medium bg-white transition">
                            <span x-text="filters.beds
                                ? (filters.beds >= 6 ? '6+ Beds' : (filters.beds + (filters.beds === 1 ? ' Bed' : ' Beds')))
                                : 'Beds'"></span>
                            <svg class="w-4 h-4" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-transition
                             class="absolute top-full left-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl z-30 w-56 p-2">
                            <button type="button"
                                @click="filters.beds = 0; applyFilters(); open = false"
                                :class="!filters.beds ? 'bg-blue-50 text-[var(--primary-color)] font-semibold' : 'text-slate-700'"
                                class="w-full text-left px-3 py-2 rounded-lg text-sm hover:bg-slate-50">Any</button>
                            <template x-for="n in options" :key="n">
                                <button type="button"
                                    @click="filters.beds = n; applyFilters(); open = false"
                                    :class="filters.beds === n ? 'bg-blue-50 text-[var(--primary-color)] font-semibold' : 'text-slate-700'"
                                    class="w-full text-left px-3 py-2 rounded-lg text-sm hover:bg-slate-50"
                                    x-text="n === 6 ? '6+ Beds' : (n + (n === 1 ? ' Bed' : ' Beds'))"></button>
                            </template>
                        </div>
                    </div>

                    <!-- Baths chip (1-5 plain, 6+ meta) -->
                    <div class="relative" x-data="{ open: false, options: [1,2,3,4,5,6] }" @click.outside="open = false">
                        <button type="button" @click="open = !open"
                            :class="filters.baths ? 'border-[var(--primary-color)] text-[var(--primary-color)] bg-blue-50' : 'border-slate-200 text-slate-700 hover:border-slate-300'"
                            class="inline-flex items-center gap-2 px-4 py-2 border rounded-full text-sm font-medium bg-white transition">
                            <span x-text="filters.baths
                                ? (filters.baths >= 6 ? '6+ Baths' : (filters.baths + (filters.baths === 1 ? ' Bath' : ' Baths')))
                                : 'Baths'"></span>
                            <svg class="w-4 h-4" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-transition
                             class="absolute top-full left-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl z-30 w-56 p-2">
                            <button type="button"
                                @click="filters.baths = 0; applyFilters(); open = false"
                                :class="!filters.baths ? 'bg-blue-50 text-[var(--primary-color)] font-semibold' : 'text-slate-700'"
                                class="w-full text-left px-3 py-2 rounded-lg text-sm hover:bg-slate-50">Any</button>
                            <template x-for="n in options" :key="n">
                                <button type="button"
                                    @click="filters.baths = n; applyFilters(); open = false"
                                    :class="filters.baths === n ? 'bg-blue-50 text-[var(--primary-color)] font-semibold' : 'text-slate-700'"
                                    class="w-full text-left px-3 py-2 rounded-lg text-sm hover:bg-slate-50"
                                    x-text="n === 6 ? '6+ Baths' : (n + (n === 1 ? ' Bath' : ' Baths'))"></button>
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

                        <?php if ($pt_saved_search_count > 0): ?>
                        <!-- Shortcut to the user's saved-search presets in their dashboard -->
                        <a href="<?= esc_url(trailingslashit($pt_member_home_url) . '#saved-searches'); ?>"
                           class="hidden md:inline-flex items-center gap-1.5 px-3 py-2 border border-slate-200 rounded-full text-sm font-medium bg-white text-slate-700 hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition group">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span>View your search presets</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold leading-none bg-slate-100 text-slate-700 group-hover:bg-[var(--primary-color)] group-hover:text-white transition">
                                <?= (int) $pt_saved_search_count; ?>
                            </span>
                        </a>
                        <?php endif; ?>

                        <!-- Save search button + inline naming popover -->
                        <div class="relative" @click.outside="showSaveSearch = false">
                            <button type="button"
                                @click="openSaveSearchDialog()"
                                class="inline-flex items-center gap-1.5 px-3 py-2 border border-slate-200 rounded-full text-sm font-medium bg-white text-slate-700 hover:border-[var(--primary-color)] hover:text-[var(--primary-color)] transition">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                </svg>
                                Save search
                            </button>

                            <div x-show="showSaveSearch" x-transition x-cloak
                                 class="absolute right-0 top-full mt-2 w-80 bg-white border border-slate-200 rounded-xl shadow-xl z-40 p-4">
                                <h4 class="font-semibold text-slate-900 mb-1">Save this search</h4>
                                <p class="text-xs text-slate-500 mb-3">We'll remember these filters so you can jump back to them next time.</p>

                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Name</label>
                                <input type="text" x-model="saveSearchLabel" placeholder="e.g. 3-bed houses in Kingston"
                                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[var(--primary-color)]/30 focus:border-[var(--primary-color)] outline-none mb-3">

                                <label class="flex items-start gap-2 mb-4 cursor-pointer select-none">
                                    <input type="checkbox" x-model="saveSearchWeekly" class="mt-0.5 rounded text-[var(--primary-color)]">
                                    <span class="text-sm text-slate-700">
                                        Email me weekly when new listings match
                                        <span class="block text-xs text-slate-400 mt-0.5">One digest per week per search — no spam.</span>
                                    </span>
                                </label>

                                <div class="flex items-center gap-2 justify-end">
                                    <button type="button" @click="showSaveSearch = false"
                                        class="text-sm text-slate-500 px-3 py-1.5 hover:text-slate-700">Cancel</button>
                                    <button type="button" @click="submitSaveSearch()" :disabled="savingSearch"
                                        class="text-sm text-white font-semibold px-4 py-2 rounded-lg disabled:opacity-60"
                                        style="background:var(--primary-color);"
                                        x-text="savingSearch ? 'Saving…' : 'Save search'"></button>
                                </div>

                                <p x-show="saveSearchError" x-text="saveSearchError" class="mt-2 text-xs text-red-600"></p>
                                <p x-show="saveSearchSuccess" class="mt-2 text-xs text-green-600">Saved — you can find it in your dashboard.</p>
                            </div>
                        </div>

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

            <!-- Airbnb-style split: cards left, sticky map right, no sponsors. -->
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,_1.15fr)_minmax(0,_1fr)] gap-6">

                <!-- Cards column -->
                <div>
                    <!-- Skeleton loading state -->
                    <div x-show="loading && allProperties.length === 0"
                        class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <template x-for="i in [1,2,3,4]" :key="i">
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden animate-pulse">
                                <div class="h-56 bg-gradient-to-r from-slate-200 to-slate-100"></div>
                                <div class="p-5">
                                    <div class="h-6 bg-slate-200 rounded w-3/4 mb-3"></div>
                                    <div class="h-4 bg-slate-200 rounded w-full mb-2"></div>
                                    <div class="h-4 bg-slate-200 rounded w-2/3"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Card grid (always cards — no list toggle in Airbnb-style layout) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5" x-show="!loading || allProperties.length > 0">
                        <template x-for="property in properties" :key="property.id">
                            <template x-if="property">
                                <div :id="'card-' + property.id"
                                     @mouseenter="hoverProperty(property.id)"
                                     @mouseleave="hoverProperty(null)"
                                     :class="highlightedId === property.id ? 'ring-2 ring-[var(--primary-color)] shadow-xl -translate-y-0.5' : ''"
                                     class="transition duration-150">
                                    <?php get_template_part('template-parts/component', 'property-card'); ?>
                                </div>
                            </template>
                        </template>
                    </div>

                    <!-- No results -->
                    <div x-show="!loading && allProperties.length === 0"
                        class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
                        <div class="mb-4 flex justify-center">
                            <svg class="w-14 h-14 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <p class="text-slate-600 text-lg mb-6 font-medium">No properties found matching your criteria.</p>
                        <button @click="clearFilters()"
                            class="bg-[var(--primary-color)] hover:opacity-90 text-white font-bold py-3 px-8 rounded-lg transition-all shadow-sm hover:shadow-md">Reset Filters</button>
                    </div>

                    <!-- Infinite scroll indicator -->
                    <div x-show="loading && allProperties.length > 0" class="flex justify-center mt-8">
                        <div class="inline-flex items-center gap-2 px-6 py-3 bg-white rounded-lg shadow-sm border border-slate-200">
                            <div class="w-4 h-4 border-2 border-[var(--primary-color)] border-t-transparent rounded-full animate-spin"></div>
                            <span class="text-slate-700 font-medium">Loading more…</span>
                        </div>
                    </div>

                    <div id="infinite-scroll-trigger" x-ref="infiniteScrollTrigger" class="py-6"></div>
                </div>

                <!-- Sticky map — right column on desktop, top on mobile via ordering -->
                <aside class="hidden lg:block">
                    <div class="sticky top-[200px]">
                        <div id="archive-map"
                             x-ref="archiveMap"
                             class="w-full h-[calc(100vh-220px)] min-h-[520px] rounded-2xl overflow-hidden bg-slate-100 shadow-md border border-slate-200"></div>
                        <p class="text-xs text-slate-500 mt-2 text-center">
                            <span x-text="mappableCount"></span> of <span x-text="properties.length"></span> visible listings shown on the map
                        </p>
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
            // Always grid — Airbnb-style layout has no list mode. Card component
            // reads this from the outer scope and flips to horizontal when 'list'.
            viewType: 'grid',
            allProperties: [],
            properties: [],
            loading: false,
            currentPage: 1,
            totalResults: 0,
            totalPages: 1,

            // Save search popover
            showSaveSearch: false,
            saveSearchLabel: '',
            saveSearchWeekly: true,
            savingSearch: false,
            saveSearchError: '',
            saveSearchSuccess: false,

            // Map state
            map: null,
            markers: {},        // { propertyId: google.maps.Marker }
            highlightedId: null,
            mappableCount: 0,

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
                this.initArchiveMap();
            },

            /**
             * Airbnb-style archive map. Plots one price-bubble marker per
             * property that has coords; refits bounds on every filter change;
             * hover on a card highlights the marker (and vice-versa).
             */
            initArchiveMap() {
                const mapEl = document.getElementById('archive-map');
                if (!mapEl || !window.google || !google.maps) return;

                this.map = new google.maps.Map(mapEl, {
                    zoom: 8,
                    center: { lat: 18.1096, lng: -77.2975 }, // Jamaica centroid
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false,
                    gestureHandling: 'greedy',
                    mapId: 'c484b19c4f8c16ebb3dcf3d1',
                });
            },

            hoverProperty(id) {
                this.highlightedId = id;
                this.updateMarkerStyles();
            },

            updateMarkerStyles() {
                const primary = getComputedStyle(document.documentElement)
                    .getPropertyValue('--primary-color').trim() || '#132364';
                Object.entries(this.markers).forEach(([pid, marker]) => {
                    const isActive = String(pid) === String(this.highlightedId);
                    marker.setIcon(this.priceBubbleIcon(marker.priceLabel, isActive ? '#111827' : primary, isActive));
                    marker.setZIndex(isActive ? 1000 : 1);
                });
            },

            priceBubbleIcon(label, bg, active) {
                const w = 8 + label.length * 7;
                const svg =
                    `<svg xmlns="http://www.w3.org/2000/svg" width="${w + 8}" height="34" viewBox="0 0 ${w + 8} 34">` +
                        `<rect x="1" y="1" rx="16" ry="16" width="${w + 6}" height="30" ` +
                            `fill="${bg}" stroke="#fff" stroke-width="${active ? 3 : 2}"/>` +
                        `<text x="${(w + 8) / 2}" y="20" text-anchor="middle" font-family="Inter,system-ui,sans-serif" ` +
                            `font-size="12" font-weight="700" fill="#fff">${label}</text>` +
                    `</svg>`;
                return {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
                    anchor: new google.maps.Point((w + 8) / 2, 34),
                };
            },

            /**
             * Re-plot markers after properties change (initial load or filter update).
             * Clears old markers, keeps only those with lat/lng, fits bounds to visible set.
             */
            renderMarkers() {
                if (!this.map) return;
                Object.values(this.markers).forEach(m => m.setMap(null));
                this.markers = {};

                const primary = getComputedStyle(document.documentElement)
                    .getPropertyValue('--primary-color').trim() || '#132364';

                const bounds = new google.maps.LatLngBounds();
                let count = 0;
                const infoWindow = new google.maps.InfoWindow();

                this.properties.forEach(p => {
                    if (!p || p.lat == null || p.lng == null) return;
                    const position = { lat: parseFloat(p.lat), lng: parseFloat(p.lng) };
                    const priceLabel = window.formatPrice ? window.formatPrice(p.price) : ('$' + p.price);

                    const marker = new google.maps.Marker({
                        position, map: this.map,
                        icon: this.priceBubbleIcon(priceLabel, primary, false),
                        title: p.title,
                    });
                    marker.priceLabel = priceLabel;

                    marker.addListener('mouseover', () => {
                        this.highlightedId = p.id;
                        this.updateMarkerStyles();
                    });
                    marker.addListener('mouseout', () => {
                        this.highlightedId = null;
                        this.updateMarkerStyles();
                    });
                    marker.addListener('click', () => {
                        // Popup card + scroll the matching card into view
                        infoWindow.setContent(
                            `<div style="font-family:Inter,system-ui,sans-serif;min-width:180px;max-width:220px;">
                                ${p.image ? `<img src="${p.image}" style="width:100%;height:110px;object-fit:cover;border-radius:8px;margin-bottom:8px;">` : ''}
                                <p style="margin:0;font-weight:700;font-size:14px;color:#0f172a;line-height:1.2;">${p.title.replace(/[<>]/g, '')}</p>
                                <p style="margin:4px 0 8px;font-size:13px;color:${primary};font-weight:600;">${priceLabel}</p>
                                <a href="${p.permalink}" style="display:inline-block;padding:6px 12px;background:${primary};color:#fff;text-decoration:none;border-radius:6px;font-size:12px;font-weight:600;">View details</a>
                            </div>`
                        );
                        infoWindow.open({ anchor: marker, map: this.map });
                        const card = document.getElementById('card-' + p.id);
                        if (card) card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    });

                    this.markers[p.id] = marker;
                    bounds.extend(position);
                    count++;
                });

                this.mappableCount = count;
                if (count === 1) {
                    this.map.setCenter(bounds.getCenter());
                    this.map.setZoom(14);
                } else if (count > 1) {
                    this.map.fitBounds(bounds, { top: 40, right: 40, bottom: 40, left: 40 });
                }
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
                const isJmd  = this.selectedCurrency === 'jmd';
                const isRent = this.filters.listingStatus === 'rent';

                // Rent is a monthly figure — always in JMD, with tighter
                // brackets matching realistic Jamaica rental prices.
                const rentRanges = [
                    { min: 0,      max: 50000,      label: 'J$0 – J$50K' },
                    { min: 50000,  max: 100000,     label: 'J$50K – J$100K' },
                    { min: 100000, max: 150000,     label: 'J$100K – J$150K' },
                    { min: 150000, max: 200000,     label: 'J$150K – J$200K' },
                    { min: 200000, max: 300000,     label: 'J$200K – J$300K' },
                    { min: 300000, max: 9999999999, label: 'J$300K+' },
                ];
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
                const ranges = isRent ? rentRanges : (isJmd ? jmdRanges : usdRanges);
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
                    const ts = new TomSelect('#city-select', {
                        placeholder: 'Any parish',
                        allowEmptyOption: true,
                        maxOptions: null,
                        onChange: (value) => {
                            // Keep Alpine's filter state in sync so any code
                            // reading filters.city (map, results, save-search)
                            // sees the current parish.
                            self.filters.city = value || '';
                            self.resetLocationSuggestions();
                            self.applyFilters();
                        }
                    });
                    // If we arrived from a footer link (?prop_city=Kingston),
                    // TomSelect ignores the underlying <select>'s x-model value —
                    // push it in so the pill shows "Kingston" instead of "Any parish".
                    if (self.filters.city) {
                        ts.setValue(self.filters.city, /* silent = */ true);
                    }
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
                        // Repaint map markers after each response so the pins
                        // always match the visible card set (initial load + infinite scroll + filter change).
                        this.$nextTick(() => this.renderMarkers());
                    })
                    .catch(error => {
                        console.error('Error fetching properties:', error);
                        this.loading = false;
                    });
            },

            // Called from the "Save search" button in the filter row.
            // Guests get bounced to the auth modal — we never silently drop the save.
            openSaveSearchDialog() {
                if (!window.wpUser || !window.wpUser.isLoggedIn) {
                    window.dispatchEvent(new CustomEvent('open-auth-modal', { detail: { mode: 'login' } }));
                    return;
                }
                this.saveSearchError = '';
                this.saveSearchSuccess = false;
                // Auto-fill a reasonable default label from active filters.
                if (!this.saveSearchLabel) {
                    const bits = [];
                    if (this.filters.listingStatus) bits.push(this.filters.listingStatus === 'rent' ? 'Rent' : 'Buy');
                    if (this.filters.beds)  bits.push(this.filters.beds + '+ bed');
                    if (this.filters.city)  bits.push(this.filters.city);
                    if (this.filters.location) bits.push(this.filters.location);
                    this.saveSearchLabel = bits.length ? bits.join(' · ') : 'My search';
                }
                this.showSaveSearch = true;
            },

            async submitSaveSearch() {
                if (!this.saveSearchLabel.trim()) {
                    this.saveSearchError = 'Give this search a name so you can find it later.';
                    return;
                }
                this.savingSearch = true;
                this.saveSearchError = '';
                const criteria = {
                    search:         this.filters.search || '',
                    property_type:  (this.filters.types || []).join(','),
                    listing_status: this.filters.listingStatus || '',
                    city:           this.filters.city || '',
                    location:       this.filters.location || '',
                    beds:           this.filters.beds || 0,
                    baths:          this.filters.baths || 0,
                    price_min:      this.filters.priceMin || 0,
                    price_max:      this.filters.priceMax || 0,
                    area_min:       this.filters.areaMin || 0,
                    area_max:       this.filters.areaMax || 0,
                    featured:       this.filters.featured ? '1' : '',
                    currency:       this.selectedCurrency || 'usd',
                    keyword:        this.filters.keyword || '',
                };
                try {
                    const res = await window.ptSaveSearch(this.saveSearchLabel.trim(), criteria, this.saveSearchWeekly);
                    if (res && res.success) {
                        this.saveSearchSuccess = true;
                        setTimeout(() => { this.showSaveSearch = false; this.saveSearchSuccess = false; }, 1600);
                    } else {
                        this.saveSearchError = 'Could not save. Please try again.';
                    }
                } catch (e) {
                    this.saveSearchError = 'Network error. Please try again.';
                }
                this.savingSearch = false;
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