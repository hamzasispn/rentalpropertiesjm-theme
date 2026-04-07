<!-- Advanced Property Search Filter with Responsive Design + Mobile Sidebar -->
<?php

$cities_data = get_jamaica_cities();

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

$filter_params = $filter_params ?? [];

// Bedroom/bathroom taxonomy terms
if (!function_exists('sort_terms_numerically_local2')) {
    function sort_terms_numerically_local2($terms) {
        if (empty($terms) || is_wp_error($terms)) return [];
        usort($terms, function($a, $b) { return intval($a->name) - intval($b->name); });
        return $terms;
    }
}
$bedroom_terms   = sort_terms_numerically_local2(get_terms(array('taxonomy' => 'bedroom',  'hide_empty' => false)));
$bathroom_terms  = sort_terms_numerically_local2(get_terms(array('taxonomy' => 'bathroom', 'hide_empty' => false)));
$listing_statuses = get_terms(array('taxonomy' => 'property_listing_status', 'hide_empty' => false));

// Add city and location to filter params if coming from redirect
if (!isset($filter_params['city']) && isset($_GET['city'])) {
    $filter_params['city'] = sanitize_text_field($_GET['city']);
}
if (!isset($filter_params['location']) && isset($_GET['location'])) {
    $filter_params['location'] = sanitize_text_field($_GET['location']);
}

// Ensure we're using the correct WordPress properties page URL
$properties_page_url = get_post_type_archive_link('property');
if (empty($properties_page_url)) {
    $properties_page_url = home_url('/properties/');
}

?>

<div
    class="md:hidden block w-full"
    x-data="propertyFiltering(<?php echo htmlspecialchars(json_encode($filter_params)); ?>, <?php echo htmlspecialchars(json_encode($cities_data)); ?>, <?php echo htmlspecialchars(json_encode($property_type_hierarchy)); ?>, <?php echo htmlspecialchars(json_encode($bedroom_terms)); ?>, <?php echo htmlspecialchars(json_encode($bathroom_terms)); ?>, <?php echo htmlspecialchars(json_encode($listing_statuses)); ?>)">

    <!-- Mobile Filter Button -->
    <button
        @click="showMobileSidebar = !showMobileSidebar; $nextTick(() => { if (showMobileSidebar) initializeMobileSliders() })"
        class="btn-secondary flex items-center justify-center w-full gap-2 text-center">
        <svg class="w-[4vw] h-[4vw]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
            </path>
        </svg>
        Browse Properties
    </button>

    <!-- ✅ Verified Badge -->
    <div class="flex items-center justify-center gap-1.5 mt-2">
        <div class="flex items-center gap-1.5 px-3 py-1 bg-white border border-green-200 rounded-full shadow-sm">
            <svg class="w-3.5 h-3.5 text-green-600 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-1 14l-3-3 1.41-1.41L11 12.17l4.59-4.58L17 9l-6 6z"/>
            </svg>
            <span class="text-xs font-semibold text-green-700 tracking-wide">100% Verified Listings</span>
        </div>
    </div>

    <!-- Mobile Sidebar -->
    <div x-show="showMobileSidebar" x-transition class="fixed inset-0 z-50 md:hidden" x-cloak>
        <!-- Overlay -->
        <div @click="showMobileSidebar = false" class="fixed inset-0 bg-black/30 backdrop-blur-sm"></div>

        <!-- Sidebar Panel -->
        <div class="fixed left-0 top-0 bottom-0 w-4/5 bg-white shadow-2xl overflow-y-auto" style="max-width: 320px;">

            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b border-slate-200 sticky top-0 bg-white z-10">
                <h2 class="text-lg font-bold text-slate-900">Filters</h2>
                <button @click="showMobileSidebar = false" class="text-slate-600 hover:text-slate-900 text-2xl">✕</button>
            </div>

            <!-- Filter Content -->
            <div class="p-4 space-y-6">

                <!-- Parish Filter -->
                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">Parish</label>
                    <select x-model="filters.city" @change="resetLocationSuggestions()"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent">
                        <option value="">Select a parish...</option>
                        <template x-for="city in citiesList" :key="city">
                            <option :value="city" x-text="city"></option>
                        </template>
                    </select>
                </div>

                <!-- Area Filter -->
                <div x-show="filters.city" class="relative">
                    <label class="block text-sm font-semibold text-slate-900 mb-2">Area</label>
                    <input
                        type="text"
                        x-model="filters.location"
                        @input="searchLocations($event)"
                        @focus="showLocationSuggestions = locationSuggestions.length > 0"
                        @click.outside="showLocationSuggestions = false"
                        placeholder="Search area..."
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent">
                    <div
                        x-show="showLocationSuggestions && locationSuggestions.length > 0"
                        x-transition
                        class="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg z-50 overflow-hidden">
                        <template x-for="(suggestion, index) in locationSuggestions" :key="index">
                            <button
                                type="button"
                                @click="filters.location = suggestion; showLocationSuggestions = false"
                                class="w-full text-left px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 border-b border-slate-100 last:border-0 transition">
                                <span class="flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span x-text="suggestion"></span>
                                </span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Property Type Filter -->
                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-3">Property Type</label>
                    <div class="space-y-2">
                        <template x-for="group in propertyTypeHierarchy" :key="group.parent.term_id">
                            <div>
                                <p class="text-xs font-semibold text-slate-700 uppercase mb-2" x-text="group.parent.name"></p>
                                <div class="space-y-1 ml-2">
                                    <template x-for="child in group.children" :key="child.slug">
                                        <button type="button"
                                            @click="filters.type = child.term_id; selectedTypeName = child.name"
                                            :class="filters.type == child.term_id ? 'bg-[var(--primary-color)] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                            class="w-full text-left px-3 py-2 rounded text-sm font-medium transition"
                                            x-text="child.name">
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Currency -->
                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">Currency</label>
                    <select x-model="selectedCurrency" @change="onCurrencyChange()"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent">
                        <option value="usd">USD ($)</option>
                        <option value="jmd">JMD (J$)</option>
                    </select>
                    <p class="text-xs text-slate-500 mt-1" x-show="selectedCurrency === 'jmd'" x-text="`1 USD = ${usdToJmdRate.toFixed(2)} JMD`"></p>
                </div>

                <!-- Buy / Rent -->
                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">Listing Type</label>
                    <div class="flex gap-2">
                        <template x-for="status in listingStatuses" :key="status.slug">
                            <button type="button"
                                @click="filters.listingStatus = filters.listingStatus === status.slug ? '' : status.slug"
                                :class="filters.listingStatus === status.slug ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)]' : 'bg-white text-slate-700 border-slate-300'"
                                class="flex-1 py-2 rounded-lg border text-sm font-semibold transition"
                                x-text="status.name">
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Bedrooms Dropdown -->
                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">Bedrooms (Min)</label>
                    <select x-model.number="filters.beds"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent">
                        <option value="0">Any</option>
                        <template x-for="term in bedroomTerms" :key="term.term_id">
                            <option :value="parseInt(term.name)" x-text="term.name + '+'"></option>
                        </template>
                    </select>
                </div>

                <!-- Bathrooms Dropdown -->
                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">Bathrooms (Min)</label>
                    <select x-model.number="filters.baths"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent">
                        <option value="0">Any</option>
                        <template x-for="term in bathroomTerms" :key="term.term_id">
                            <option :value="parseFloat(term.name)" x-text="term.name + '+'"></option>
                        </template>
                    </select>
                </div>

                <!-- Price Range Dropdowns -->
                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">
                        Price Range
                        <span class="text-xs text-slate-500" x-text="selectedCurrency === 'jmd' ? '(JMD)' : '(USD)'"></span>
                    </label>
                    <div class="flex gap-2 items-center">
                        <select x-model.number="filters.priceMin"
                            class="w-1/2 px-2 py-2 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent">
                            <template x-for="opt in priceOptions" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                        <span class="text-slate-400">—</span>
                        <select x-model.number="filters.priceMax"
                            class="w-1/2 px-2 py-2 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent">
                            <template x-for="opt in priceOptions" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <!-- Area commented out as per requirements -->
                <!-- Area Range (sq ft) filter removed -->

                <!-- Featured -->
                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="filters.featured" class="w-4 h-4 rounded">
                        <span class="text-sm font-medium text-slate-900">Featured Only</span>
                    </label>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-2 pt-4 border-t border-slate-200">
                    <button
                        @click="searchProperties(); showMobileSidebar = false"
                        class="w-full bg-[var(--primary-color)] text-white font-semibold py-3 rounded-lg hover:opacity-90 transition">
                        Apply Filters
                    </button>
                    <button
                        @click="filters = { type: '', priceMin: 0, priceMax: 500000, areaMin: 0, areaMax: 100000, beds: 0, baths: 0, city: '', location: '', featured: false, listingStatus: '' }; selectedTypeName = ''; selectedCurrency = 'usd'; buildPriceOptions();"
                        class="w-full border border-slate-300 text-slate-700 font-semibold py-3 rounded-lg hover:bg-slate-50 transition">
                        Clear All
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>