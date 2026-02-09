
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
    x-data="propertyFiltering(<?php echo htmlspecialchars(json_encode($filter_params)); ?>, <?php echo htmlspecialchars(json_encode($cities_data)); ?>, <?php echo htmlspecialchars(json_encode($property_type_hierarchy)); ?>)">

    <!-- Mobile Filter Button (visible on mobile) -->
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

    <!-- Mobile Sidebar Filter (Canvas/Menu) -->
    <div x-show="showMobileSidebar" x-transition class="fixed inset-0 z-50 md:hidden" x-cloak>
        <!-- Overlay -->
        <div @click="showMobileSidebar = false" class="fixed inset-0 bg-black/30 backdrop-blur-sm"></div>

        <!-- Sidebar Panel -->
        <div class="fixed left-0 top-0 bottom-0 w-4/5 bg-white shadow-2xl overflow-y-auto" style="max-width: 320px;">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b border-slate-200 sticky top-0 bg-white">
                <h2 class="text-lg font-bold text-slate-900">Filters</h2>
                <button @click="showMobileSidebar = false" class="text-slate-600 hover:text-slate-900 text-2xl">
                    ✕
                </button>
            </div>

            <!-- Filter Content -->
            <div class="p-4 space-y-6">
                <!-- City Filter -->
                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">City</label>
                    <select x-model="filters.city" @change="resetLocationSuggestions()"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent">
                        <option value="">Select a city...</option>
                        <template x-for="city in citiesList" :key="city">
                            <option :value="city" x-text="city"></option>
                        </template>
                    </select>
                </div>

                <!-- Location Filter -->
                <div x-show="filters.city" class="relative">
                    <label class="block text-sm font-semibold text-slate-900 mb-2">Area</label>
                    <input type="text" x-model="filters.location" @input="searchLocations($event)"
                        placeholder="Search..."
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent">
                </div>

                <!-- Property Type Filter -->
                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-3">Property Type</label>
                    <div class="space-y-2">
                        <template x-for="group in propertyTypeHierarchy" :key="group.parent.term_id">
                            <div>
                                <p class="text-xs font-semibold text-slate-700 uppercase mb-2"
                                    x-text="group.parent.name"></p>
                                <div class="space-y-1 ml-2">
                                    <template x-for="child in group.children" :key="child.slug">
                                        <button type="button"
                                            @click="filters.type = child.term_id; filters.selectedTypeName = child.name"
                                            :class="filters.type == child.term_id ? 'bg-[var(--primary-color)] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                            class="w-full text-left px-3 py-2 rounded text-sm font-medium transition"
                                            x-text="child.name"></button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Bedrooms -->
                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">
                        Bedrooms: <span x-text="filters.beds > 0 ? filters.beds + '+' : 'Any'"
                            class="text-[var(--primary-color)]"></span>
                    </label>
                    <div id="bedroom-slider-mobile" class="mt-3"></div>
                </div>

                <!-- Bathrooms -->
                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">
                        Bathrooms: <span x-text="filters.baths > 0 ? filters.baths + '+' : 'Any'"
                            class="text-[var(--primary-color)]"></span>
                    </label>
                    <div id="bathroom-slider-mobile" class="mt-3"></div>
                </div>

                <!-- Price -->
                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">
                        Price: <span
                            x-text="`$${(filters.priceMin/1000).toFixed(0)}K - $${(filters.priceMax/1000).toFixed(0)}K`"
                            class="text-[var(--primary-color)]"></span>
                    </label>
                    <div id="price-slider-mobile" class="mt-3"></div>
                </div>

                <!-- Area -->
                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">
                        Area: <span
                            x-text="`${filters.areaMin.toLocaleString()} - ${filters.areaMax.toLocaleString()} sqft`"
                            class="text-[var(--primary-color)]"></span>
                    </label>
                    <div id="area-slider-mobile" class="mt-3"></div>
                </div>

                <!-- Featured -->
                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="filters.featured" class="w-4 h-4 rounded">
                        <span class="text-sm font-medium text-slate-900">Featured Only</span>
                    </label>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-2 pt-4 border-t border-slate-200">
                    <button @click="searchProperties(); showMobileSidebar = false"
                        class="w-full bg-[var(--primary-color)] text-white font-semibold py-3 rounded-lg hover:opacity-90 transition">
                        Apply Filters
                    </button>
                    <button @click="filters = {}; selectedTypeName = ''"
                        class="w-full border border-slate-300 text-slate-700 font-semibold py-3 rounded-lg hover:bg-slate-50 transition">
                        Clear All
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>