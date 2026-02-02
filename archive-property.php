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
    'price_max' => intval($_GET['price_max'] ?? 5000000),
    'area_min' => intval($_GET['area_min'] ?? 0),
    'area_max' => intval($_GET['area_max'] ?? 100000),
    'beds' => intval($_GET['beds'] ?? 0),
    'baths' => intval($_GET['baths'] ?? 0),
    'city' => sanitize_text_field($_GET['city'] ?? ''),
    'location' => sanitize_text_field($_GET['location'] ?? ''),
    'keyword' => sanitize_text_field($_GET['keyword'] ?? ''),
    'sort' => sanitize_text_field($_GET['sort'] ?? 'newest'),
    'view' => sanitize_text_field($_GET['view'] ?? 'grid'),
    'featured' => sanitize_text_field($_GET['featured'] ?? ''),
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

<!-- Header -->
<div
    style="background: url('<?php echo get_template_directory_uri(); ?>/assets/archive-image.jpg') top/cover no-repeat;">
    <div
        class="flex pb-[7.229vw] md:pt-[16.229vw] items-start flex-col h-[40vh] justify-end md:h-[28.281vw] max-w-[90%] mx-auto">
        <h1 class="text-[8.471vw] leading-[1] md:text-[4.063vw] font-bold text-white mb-2">Find Homes and Spaces</h1>
        <p class="text-white font-bold text-[4.5vw] md:text-[2.917vw]">That Match Your Needs</p>
    </div>
</div>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <div class="max-w-[90%] mx-auto px-4 py-8 md:py-12">
        <!-- Main Content -->
        <div
            x-data="propertyArchiveFiltering(<?php echo htmlspecialchars(json_encode($filter_params)); ?>, <?php echo htmlspecialchars(json_encode($cities_data)); ?>, <?php echo htmlspecialchars(json_encode($property_type_hierarchy)); ?>, <?php echo htmlspecialchars(json_encode($bedrooms)); ?>, <?php echo htmlspecialchars(json_encode($bathrooms)); ?>)">

            <!-- Top Filter Bar - Sticky -->
            <div
                class="bg-white rounded-xl shadow-sm p-[1.765vw] md:p-4 border border-slate-200 mb-6 sticky top-0 z-50">
                <div class="flex items-center justify-between md:justify-normal md:gap-3 flex-wrap">
                    <!-- Plus Button to Toggle Filters -->
                    <button type="button" @click="showFilters = !showFilters"
                        class="flex items-center gap-2 md:px-4 md:py-2.5 px-[4.235vw] py-[2.353vw] bg-gradient-to-r from-[var(--primary-color)] to-blue-700 hover:from-blue-700 hover:to-[var(--primary-color)] text-white font-semibold rounded-lg transition-all shadow-sm hover:shadow-md">
                        <svg class="md:w-5 md:h-5 w-[2.353vw] h-[2.353vw]" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        <span x-text="showFilters ? 'Hide Filters' : 'Show Filters'"
                            class="text-[3vw] md:text-[0.938vw]"></span>
                    </button>

                    <!-- Quick Stats -->
                    <div class="text-slate-700 font-medium ml-auto hidden md:block font-inter">
                        Found <span x-text="totalResults" class="text-[var(--primary-color)] font-bold"></span>
                        properties
                    </div>

                    <!-- View Toggle -->
                    <div class="flex gap-2 border-l pl-4 hidden md:block">
                        <button type="button" @click="viewType = 'grid'"
                            :class="viewType === 'grid' ? 'bg-[var(--primary-color)] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="p-2.5 rounded-lg transition-all" title="Grid view">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M3 3h7v7H3zm11 0h7v7h-7zm-11 11h7v7H3zm11 0h7v7h-7z"></path>
                            </svg>
                        </button>
                        <button type="button" @click="viewType = 'list'"
                            :class="viewType === 'list' ? 'bg-[var(--primary-color)] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="p-2.5 rounded-lg transition-all" title="List view">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Sort Dropdown -->
                    <select x-model="sortBy" @change="applyFilters()"
                        class="md:px-4 md:py-2.5 px-[4.235vw] py-[2.353vw] border border-slate-300 rounded-lg focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent text-slate-900 text-[2.824vw] md:text-sm font-medium bg-white">
                        <option value="newest">Newest First</option>
                        <option value="featured">Featured First</option>
                        <option value="price-low">Price: Low to High</option>
                        <option value="price-high">Price: High to Low</option>
                    </select>
                </div>

                <!-- Expandable Filters Panel -->
                <div x-show="showFilters" x-transition class="mt-6 pt-6 border-t border-slate-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- City Filter with TomSelect Dropdown -->
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2 font-inter">City</label>
                            <select id="city-select" x-model="filters.city"
                                @change="resetLocationSuggestions(); applyFilters()"
                                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm text-slate-900 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent transition">
                                <option value="">Select a city...</option>
                                <template x-for="city in citiesList" :key="city">
                                    <option :value="city" x-text="city"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Location Filter with Google API Autocomplete (appears after city selection) -->
                        <div class="relative" x-show="filters.city">
                            <label
                                class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2 font-inter">Location</label>
                            <input type="text" x-model="filters.location" @input="searchLocations($event)"
                                @focus="showLocationSuggestions = true"
                                @blur="setTimeout(() => showLocationSuggestions = false, 200)"
                                placeholder="Search location..."
                                class="w-full px-4 py-2.5 border font-inter border-slate-300 rounded-lg text-sm text-slate-900 placeholder-slate-500 focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent transition">

                            <!-- Location Suggestions from Google API -->
                            <div x-show="showLocationSuggestions && locationSuggestions.length"
                                class="absolute top-full left-0 right-0 mt-2 bg-white border border-slate-300 rounded-lg shadow-lg z-20 max-h-56 overflow-y-auto">
                                <template x-for="location in locationSuggestions" :key="location">
                                    <button type="button"
                                        @click="filters.location = location; showLocationSuggestions = false; applyFilters()"
                                        class="w-full text-left px-4 py-2.5 hover:bg-blue-50 text-slate-900 text-sm transition font-inter"
                                        x-text="location"></button>
                                </template>
                            </div>
                        </div>

                        <!-- Property Type Filter with Tabs -->
                        <div x-show="propertyTypeHierarchy.length > 0" class="col-span-full">
                            <label
                                class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-3">Property
                                Type</label>
                            <div x-data="{
                                propertyTypeTab: propertyTypeHierarchy.length > 0 ? propertyTypeHierarchy[0].parent.term_id : '',
                            }" class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                                <!-- Tab Headers -->
                                <div class="flex gap-2 border-b border-slate-300 mb-4 flex-wrap">
                                    <template x-for="group in propertyTypeHierarchy" :key="group.parent.term_id">
                                        <button type="button" @click="propertyTypeTab = group.parent.term_id"
                                            :class="propertyTypeTab === group.parent.term_id ? 'border-[var(--primary-color)] text-[var(--primary-color)] bg-white' : 'border-transparent text-slate-600 hover:text-slate-900'"
                                            class="px-4 py-2.5 font-semibold text-sm border-b-2 transition rounded-t font-inter">
                                            <span x-text="group.parent.name"></span>
                                        </button>
                                    </template>
                                </div>

                                <!-- Tab Content - Child Filters -->
                                <template x-for="group in propertyTypeHierarchy" :key="group.parent.term_id">
                                    <div x-show="propertyTypeTab === group.parent.term_id" x-transition
                                        class="flex flex-wrap gap-2">
                                        <template x-for="child in group.children" :key="child.slug">
                                            <button type="button"
                                                @click="filters.types.includes(child.slug) ? filters.types.splice(filters.types.indexOf(child.slug), 1) : filters.types.push(child.slug); applyFilters();"
                                                :class="filters.types.includes(child.slug) ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)]' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'"
                                                class="px-4 py-2 rounded-full border text-sm font-medium transition font-inter flex items-center gap-2">
                                                <span x-html="child.icon"
                                                    class="w-5 h-5 fill-[var(--primary-color)] block"></span>
                                                <span x-text="child.name"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div
                            class="grid-cols-2 grid md:grid-cols-5 gap-8 lg:col-span-5 md:col-span-2 col-span-1 px-2 py-2">
                            <!-- Bedrooms Single Slider (nouislider) -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">
                                    Bedrooms: <span x-text="filters.beds > 0 ? filters.beds + '+' : 'Any'"
                                        class="text-blue-600"></span>
                                </label>
                                <div id="bedroom-slider" class="mt-3"></div>
                                <input type="hidden" x-model="filters.beds" id="bedroom-value" @change="applyFilters()">
                            </div>

                            <!-- Bathrooms Single Slider (nouislider) -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">
                                    Bathrooms: <span x-text="filters.baths > 0 ? filters.baths + '+' : 'Any'"
                                        class="text-blue-600"></span>
                                </label>
                                <div id="bathroom-slider" class="mt-3"></div>
                                <input type="hidden" x-model="filters.baths" id="bathroom-value"
                                    @change="applyFilters()">
                            </div>

                            <!-- Price Range Slider (nouislider) -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">
                                    Price: <span
                                        x-text="`$${(filters.priceMin/1000).toFixed(0)}K - $${(filters.priceMax/1000).toFixed(0)}K`"
                                        class="text-blue-600"></span>
                                </label>
                                <div id="price-slider" class="mt-3"></div>
                            </div>

                            <!-- Area Range Slider (nouislider) -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">
                                    Area: <span
                                        x-text="`${filters.areaMin.toLocaleString()} - ${filters.areaMax.toLocaleString()} sqft`"
                                        class="text-blue-600"></span>
                                </label>
                                <div id="area-slider" class="mt-3"></div>
                            </div>
                        </div>

                        <!-- Featured Filter -->
                        <div class="flex items-end">
                            <label class="flex items-center cursor-pointer group gap-2">
                                <input type="checkbox" x-model="filters.featured" @change="applyFilters()"
                                    class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 cursor-pointer">
                                <span
                                    class="text-sm font-medium text-slate-900 group-hover:text-blue-600 transition">Featured
                                    Only</span>
                            </label>
                        </div>

                        <!-- Clear Filters Button -->
                        <div class="flex items-end">
                            <button @click="clearFilters()"
                                class="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-semibold py-2.5 px-4 rounded-lg transition-all shadow-sm hover:shadow-md text-sm">
                                Clear All
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Section -->
            <div>


                <!-- Skeleton Cards Loading State - Dynamic placeholder instead of spinner -->
                <div x-show="loading" class="space-y-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <template x-for="i in [1,2,3,4,5,6]" :key="i">
                        <div
                            class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden animate-pulse">
                            <div class="h-56 bg-gradient-to-r from-slate-200 to-slate-100"></div>
                            <div class="p-6">
                                <div class="h-6 bg-slate-200 rounded w-3/4 mb-4"></div>
                                <div class="h-4 bg-slate-200 rounded w-full mb-3"></div>
                                <div class="h-4 bg-slate-200 rounded w-2/3 mb-6"></div>
                                <div class="flex gap-2 mb-4">
                                    <div class="h-8 bg-slate-200 rounded-full w-20"></div>
                                    <div class="h-8 bg-slate-200 rounded-full w-20"></div>
                                </div>
                                <div class="h-10 bg-slate-200 rounded w-full"></div>
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

                <!-- List View -->
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
                    <div
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white rounded-lg shadow-sm border border-slate-200">
                        <div class="w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin">
                        </div>
                        <span class="text-slate-700 font-medium">Loading more...</span>
                    </div>
                </div>

                <!-- Infinite Scroll Trigger Element -->
                <div id="infinite-scroll-trigger" x-ref="infiniteScrollTrigger" class="py-8"></div>
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
    function propertyArchiveFiltering(initialParams, citiesData, propertyTypeHierarchy, bedrooms, bathrooms) {
        return {
            filters: {
                search: initialParams.search || '',
                types: initialParams.property_type ? initialParams.property_type.split(',') : [],
                priceMin: initialParams.price_min || 0,
                priceMax: initialParams.price_max || 5000000,
                areaMin: initialParams.area_min || 0,
                areaMax: initialParams.area_max || 100000,
                beds: initialParams.beds || 0,
                baths: initialParams.baths || 0,
                city: initialParams.city || '',
                location: initialParams.location || '',
                keyword: initialParams.keyword || '',
                featured: initialParams.featured === 'true' ? true : false,
            },
            sortBy: initialParams.sort || 'newest',
            viewType: initialParams.view || 'grid',
            allProperties: [],
            properties: [],
            loading: false,
            currentPage: 1,
            totalResults: 0,
            totalPages: 1,

            citiesData: citiesData,
            citiesList: Object.keys(citiesData),
            propertyTypeHierarchy: propertyTypeHierarchy,

            showFilters: false,
            locationSuggestions: [],
            showLocationSuggestions: false,

            bedroomSlider: null,
            bathroomSlider: null,
            geocoder: null,
            autocompleteListener: null,

            init() {
                this.setupTomSelect();
                this.initializeNouiSliders();
                this.geocoder = new google.maps.Geocoder();
                this.applyFilters();
                this.setupInfiniteScroll();
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

            initializeNouiSliders() {
                const bedroomElement = document.getElementById('bedroom-slider');
                const bathroomElement = document.getElementById('bathroom-slider');
                const priceElement = document.getElementById('price-slider');
                const areaElement = document.getElementById('area-slider');

                if (bedroomElement && !this.bedroomSlider) {
                    this.bedroomSlider = noUiSlider.create(bedroomElement, {
                        start: [this.filters.beds],
                        range: { min: 0, max: 10 },
                        step: 1,
                        tooltips: false,
                        connect: 'lower',
                        pips: false
                    });

                    this.bedroomSlider.on('change', (values) => {
                        this.filters.beds = parseInt(values[0]);
                        this.$nextTick(() => this.applyFilters());
                    });
                }

                if (bathroomElement && !this.bathroomSlider) {
                    this.bathroomSlider = noUiSlider.create(bathroomElement, {
                        start: [this.filters.baths],
                        range: { min: 0, max: 10 },
                        step: 1,
                        tooltips: false,
                        connect: 'lower',
                        pips: false
                    });

                    this.bathroomSlider.on('change', (values) => {
                        this.filters.baths = parseInt(values[0]);
                        this.$nextTick(() => this.applyFilters());
                    });
                }

                if (priceElement && !this.priceSlider) {
                    this.priceSlider = noUiSlider.create(priceElement, {
                        start: [this.filters.priceMin, this.filters.priceMax],
                        range: { min: 0, max: 5000000 },
                        step: 50000,
                        tooltips: false,
                        connect: true,
                        pips: false
                    });

                    this.priceSlider.on('change', (values) => {
                        this.filters.priceMin = parseInt(values[0]);
                        this.filters.priceMax = parseInt(values[1]);
                        this.$nextTick(() => this.applyFilters());
                    });
                }

                if (areaElement && !this.areaSlider) {
                    this.areaSlider = noUiSlider.create(areaElement, {
                        start: [this.filters.areaMin, this.filters.areaMax],
                        range: { min: 0, max: 100000 },
                        step: 500,
                        tooltips: false,
                        connect: true,
                        pips: false
                    });

                    this.areaSlider.on('change', (values) => {
                        this.filters.areaMin = parseInt(values[0]);
                        this.filters.areaMax = parseInt(values[1]);
                        this.$nextTick(() => this.applyFilters());
                    });
                }
            },

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
                if (this.filters.priceMax) params.append('price_max', this.filters.priceMax);
                if (this.filters.areaMin) params.append('area_min', this.filters.areaMin);
                if (this.filters.areaMax) params.append('area_max', this.filters.areaMax);
                if (this.filters.beds) params.append('beds_min', this.filters.beds);
                if (this.filters.baths) params.append('baths_min', this.filters.baths);
                if (this.filters.city) params.append('city', this.filters.city);
                if (this.filters.location) params.append('keyword', this.filters.location);
                if (this.filters.keyword) params.append('keyword', this.filters.keyword);
                // Always sort featured first if filter is not applied, otherwise use selected sort
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
                    priceMax: 5000000,
                    areaMin: 0,
                    areaMax: 100000,
                    beds: 0,
                    baths: 0,
                    city: '',
                    location: '',
                    keyword: '',
                    featured: false,
                };
                this.sortBy = 'newest';

                // Reset nouisliders
                if (this.bedroomSlider) this.bedroomSlider.set([0]);
                if (this.bathroomSlider) this.bathroomSlider.set([0]);
                if (this.priceSlider) this.priceSlider.set([0, 5000000]);
                if (this.areaSlider) this.areaSlider.set([0, 100000]);

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
