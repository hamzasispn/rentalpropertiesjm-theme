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

// Fetch bedroom and bathroom terms from taxonomy
$bedroom_terms = get_terms(array('taxonomy' => 'bedroom', 'hide_empty' => false));
$bathroom_terms = get_terms(array('taxonomy' => 'bathroom', 'hide_empty' => false));
function sort_terms_numerically_local($terms)
{
    if (empty($terms) || is_wp_error($terms))
        return [];
    usort($terms, function ($a, $b) {
        return intval($a->name) - intval($b->name);
    });
    return $terms;
}
$bedroom_terms = sort_terms_numerically_local($bedroom_terms);
$bathroom_terms = sort_terms_numerically_local($bathroom_terms);
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
<!-- Desktop Filter Bar -->
<div x-data="propertyFiltering(<?php echo htmlspecialchars(json_encode($filter_params)); ?>, <?php echo htmlspecialchars(json_encode($cities_data)); ?>, <?php echo htmlspecialchars(json_encode($property_type_hierarchy)); ?>, <?php echo htmlspecialchars(json_encode($bedroom_terms)); ?>, <?php echo htmlspecialchars(json_encode($bathroom_terms)); ?>, <?php echo htmlspecialchars(json_encode($listing_statuses)); ?>)"
    class="absolute bottom-[-6%] left-1/2 transform -translate-x-1/2 md:w-[68.958vw] w-[90%] flex flex-col md:flex-row items-center bg-white shadow-lg rounded-[16px] z-10 md:h-[7vw] h-fit">

    <!-- Buy / Rent Toggle — top of desktop filter bar -->
    <div class="hidden md:flex absolute -top-[2.5vw] left-1/2 transform -translate-x-1/2 z-20">
        <template x-for="(status, index) in listingStatuses" :key="status.slug">
            <button type="button" @click="filters.listingStatus = status.slug" :class="{
            'bg-[var(--primary-color)] text-white': filters.listingStatus === status.slug,
            'bg-white text-slate-600 hover:bg-slate-50': filters.listingStatus !== status.slug,
            'rounded-tl-md': index === 0,
            'rounded-tr-md': index === listingStatuses.length - 1
        }" class="px-[2.5vw] py-[0.5vw] text-[1vw] font-semibold transition font-inter"
                x-text="status.name">
            </button>
        </template>
    </div>

    <!-- Desktop Filter UI -->
    <div class="hidden md:flex w-full items-stretch">
        <div class="md:w-[17.188vw] md:border-r border-slate-200 py-[1.2vw] px-0 md:pl-[1.8vw] md:pr-[1.458vw]">
            <label class="block text-[1.042vw] font-semibold text-slate-900 tracking-wide mb-[1.354vw]">Parish</label>
            <select id="city-select" x-model="filters.city" @change="resetLocationSuggestions();"
                class="w-full font-inter text-[0.833vw] text-slate-900 outline-none border-none bg-transparent">
                <option value="" class="font-inter">Select a parish...</option>
                <template x-for="city in citiesList" :key="city">
                    <option :value="city" x-text="city" class="font-inter"></option>
                </template>
            </select>
        </div>

        <div
            class="md:w-[17.188vw] md:border-r border-slate-200 py-[0.99vw] px-0 md:pl-[1.8vw] md:pr-[1.458vw] relative">
            <label class="block text-[1.042vw] font-semibold text-slate-900 tracking-wide mb-[1.354vw]">Area</label>
            <input type="text" x-model="filters.location" @input="searchLocations($event)"
                @focus="showLocationSuggestions = true" @blur="setTimeout(() => showLocationSuggestions = false, 200)"
                placeholder="Type to search areas..."
                class="w-full text-[0.833vw] text-slate-900 outline-none font-inter bg-transparent">

            <!-- Location Suggestions from Google API -->
            <div x-show="showLocationSuggestions && locationSuggestions.length"
                class="absolute top-full left-0 right-0 mt-2 bg-white border border-slate-300 rounded-lg shadow-lg z-20 max-h-56 overflow-y-auto">
                <template x-for="location in locationSuggestions" :key="location">
                    <button type="button" @click="filters.location = location; showLocationSuggestions = false;"
                        class="w-full text-left px-4 py-2.5 hover:bg-blue-50 text-slate-900 text-sm transition"
                        x-text="location"></button>
                </template>
            </div>
        </div>

        <div
            class="relative md:w-[17.188vw] md:border-r border-slate-200 py-[0.99vw] px-0 md:pl-[1.667vw] md:pr-[1.458vw]">
            <label class="block text-[1.042vw] font-semibold text-slate-900 tracking-wide mb-[1.354vw]">Type</label>
            <button @click="showTypeDropdown = !showTypeDropdown"
                class="w-full text-left text-[0.833vw] text-slate-900 outline-none font-inter flex justify-between items-center bg-transparent">
                <span x-text="selectedTypeName || 'Select type...'" class="font-inter truncate"></span>
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <!-- Custom Dropdown with Tabs -->
            <div x-show="showTypeDropdown" @click.outside="showTypeDropdown = false"
                class="absolute top-full left-0 w-full bg-white border border-slate-300 rounded-lg shadow-lg z-20 max-h-96 overflow-y-auto mt-2">
                <div class="flex border-b">
                    <template x-for="(typeGroup, index) in propertyTypeHierarchy" :key="typeGroup.parent.term_id">
                        <button @click="activeTab = index"
                            :class="{ 'bg-blue-100 text-[var(--primary-color)]': activeTab === index, 'text-slate-600': activeTab !== index }"
                            class="flex-1 py-2 px-4 text-sm font-medium border-r last:border-r-0 font-inter">
                            <span x-text="typeGroup.parent.name"></span>
                        </button>
                    </template>
                </div>
                <div class="p-4">
                    <template x-for="(typeGroup, index) in propertyTypeHierarchy" :key="index">
                        <div x-show="activeTab === index" class="grid grid-cols-2 gap-2">
                            <template x-for="child in typeGroup.children" :key="child.term_id">
                                <button @click="selectType(child.slug, child.name)"
                                    class="bg-blue-100 text-[var(--primary-color)] px-4 py-2 rounded-full text-sm flex items-center gap-2">
                                    <span x-html="child.icon" class="w-5 h-5 fill-[var(--primary-color)] block"></span>
                                    <span x-text="child.name" class="font-inter line-clamp-1 text-left"></span>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <button @click="showFilters = !showFilters"
            class="md:w-[6.146vw] flex justify-center items-center bg-[var(--primary-color)] text-white md:border-r border-white md:p-4">
            <svg class="md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
        </button>
        <button @click="searchProperties()"
            class="md:w-[20.207vw] md:p-4 bg-[var(--primary-color)] text-white md:text-[1.25vw] font-semibold md:rounded-r-[16px] font-inter">Search</button>
    </div>

    <!-- Additional Filters Dropdown (Desktop) -->
    <div x-show="showFilters" x-cloak
        class="hidden md:block absolute left-0 w-full bg-white shadow-lg rounded-[16px] z-10 mt-2"
        style="top: calc(100% + 0rem);">
        <div class="grid md:grid-cols-4">

            <!-- Bedrooms Dropdown -->
            <div class="border-r border-slate-300 py-[0.99vw] px-[1.458vw] col-span-1">
                <label
                    class="block text-[1.042vw] font-semibold text-slate-900 tracking-wide mb-[1.354vw] font-inter">Bedrooms
                    (Min)</label>
                <select x-model.number="filters.beds"
                    class="w-full font-inter text-[0.833vw] text-slate-900 outline-none border border-slate-200 rounded-lg px-2 py-1 bg-white">
                    <option value="0">Any</option>
                    <template x-for="term in bedroomTerms" :key="term.term_id">
                        <option :value="parseInt(term.name)" x-text="term.name + '+'"></option>
                    </template>
                </select>
            </div>

            <!-- Bathrooms Dropdown -->
            <div class="border-r border-slate-300 py-[0.99vw] px-[1.458vw] col-span-1">
                <label
                    class="block text-[1.042vw] font-semibold text-slate-900 tracking-wide mb-[1.354vw] font-inter">Bathrooms
                    (Min)</label>
                <select x-model.number="filters.baths"
                    class="w-full font-inter text-[0.833vw] text-slate-900 outline-none border border-slate-200 rounded-lg px-2 py-1 bg-white">
                    <option value="0">Any</option>
                    <template x-for="term in bathroomTerms" :key="term.term_id">
                        <option :value="parseFloat(term.name)" x-text="term.name + '+'"></option>
                    </template>
                </select>
            </div>

            <!-- Currency Selector -->
            <div class="border-r border-slate-300 py-[0.99vw] px-[1.458vw] col-span-1">
                <label
                    class="block text-[1.042vw] font-semibold text-slate-900 tracking-wide mb-[1.354vw] font-inter">Currency</label>
                <select x-model="selectedCurrency" @change="onCurrencyChange()"
                    class="w-full font-inter text-[0.833vw] text-slate-900 outline-none border border-slate-200 rounded-lg px-2 py-1 bg-white">
                    <option value="usd">USD ($)</option>
                    <option value="jmd">JMD (J$)</option>
                </select>
                <p class="text-[0.625vw] text-slate-500 mt-1 font-inter" x-show="selectedCurrency === 'jmd'"
                    x-text="`1 USD = ${usdToJmdRate.toFixed(2)} JMD`"></p>
            </div>

            <!-- Price Range Dropdown (single) -->
            <div class="py-[0.99vw] px-[1.458vw] col-span-1">
                <label class="block text-[1.042vw] font-semibold text-slate-900 tracking-wide mb-[1.354vw] font-inter">
                    Price Range
                    <span class="text-[0.7vw] text-slate-500"
                        x-text="selectedCurrency === 'jmd' ? '(JMD)' : '(USD)'"></span>
                </label>
                <select x-model="filters.priceRange" @change="applyPriceRange($event.target.value)"
                    class="w-full font-inter text-[0.7vw] text-slate-900 outline-none border border-slate-200 rounded px-1 py-0.5 bg-white">
                    <option value="">Any Price</option>
                    <template x-for="opt in priceRangeOptions" :key="opt.value">
                        <option :value="opt.value" x-text="opt.label"></option>
                    </template>
                </select>
            </div>

            <!-- Featured Checkbox -->
            <div class="col-span-4 p-4 border-t border-slate-300 flex items-center gap-6">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" x-model="filters.featured" class="mr-2">
                    <span class="font-inter text-[0.833vw]">Featured Properties Only</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Verified Badge -->
    <div class="hidden md:flex absolute -top-5 left-5 items-center gap-1.5 px-3 py-1 bg-white border border-green-200 rounded-full shadow-sm"
        style="white-space: nowrap;">
        <svg class="w-3.5 h-3.5 text-green-600 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
            <path
                d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-1 14l-3-3 1.41-1.41L11 12.17l4.59-4.58L17 9l-6 6z" />
        </svg>
        <span class="text-[0.75vw] font-semibold text-green-700 tracking-wide">100% Verified Listings</span>
    </div>

</div>


<!-- Nouislider CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.css">
<!-- TomSelect CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAUPkXXwkGt0xC5ongE7-62nzz6l7D3Nf4&libraries=places,marker&v=beta"></script>

<script>
    function propertyFiltering(initialParams, citiesData, propertyTypeHierarchy, bedroomTermsData, bathroomTermsData, listingStatusesData) {
        initialParams = initialParams || {};
        return {
            filters: {
                type: initialParams.property_type || '',
                priceMin: initialParams.price_min || 0,
                priceMax: initialParams.price_max || 9999999999,
                areaMin: initialParams.area_min || 0,
                areaMax: initialParams.area_max || 100000,
                beds: initialParams.beds || 0,
                baths: initialParams.baths || 0,
                city: initialParams.city || '',
                location: initialParams.location || '',
                featured: initialParams.featured === 'true' ? true : false,
                listingStatus: initialParams.listing_status || 'buy',
                priceRange: initialParams.price_range || '',
            },
            citiesData: citiesData,
            citiesList: Object.keys(citiesData),
            propertyTypeHierarchy: propertyTypeHierarchy,
            bedroomTerms: bedroomTermsData || [],
            bathroomTerms: bathroomTermsData || [],
            listingStatuses: listingStatusesData || [],
            showFilters: false,
            showMobileSidebar: false,
            locationSuggestions: [],
            showLocationSuggestions: false,
            showTypeDropdown: false,
            activeTab: 0,
            selectedTypeName: '',
            selectedCurrency: 'usd',
            usdToJmdRate: 157,
            priceOptions: [],
            priceRangeOptions: [],

            priceSlider: null,
            areaSlider: null,
            priceSliderMobile: null,
            areaSliderMobile: null,
            geocoder: null,

            init() {
                this.setupTomSelect();
                this.geocoder = new google.maps.Geocoder();
                this.setSelectedTypeName();
                this.fetchExchangeRate();
                this.buildPriceOptions();
                // Default to first listing status (Buy) if not already set
                if (!this.filters.listingStatus && this.listingStatuses.length > 0) {
                    this.filters.listingStatus = this.listingStatuses[0].slug;
                }
            },

            async fetchExchangeRate() {
                try {
                    const res = await fetch('https://api.exchangerate-api.com/v4/latest/USD');
                    const data = await res.json();
                    if (data && data.rates && data.rates.JMD) {
                        this.usdToJmdRate = data.rates.JMD;
                        if (this.selectedCurrency === 'jmd') this.buildPriceOptions();
                    }
                } catch (e) {
                    this.usdToJmdRate = 157;
                }
            },

            buildPriceOptions() {
                const isJmd = this.selectedCurrency === 'jmd';
                // USD price range bands
                const usdRanges = [
                    { min: 0, max: 50000, label: '$0 – $50K' },
                    { min: 50000, max: 70000, label: '$50K – $70K' },
                    { min: 70000, max: 100000, label: '$70K – $100K' },
                    { min: 100000, max: 150000, label: '$100K – $150K' },
                    { min: 150000, max: 200000, label: '$150K – $200K' },
                    { min: 200000, max: 300000, label: '$200K – $300K' },
                    { min: 300000, max: 500000, label: '$300K – $500K' },
                    { min: 500000, max: 750000, label: '$500K – $750K' },
                    { min: 750000, max: 1000000, label: '$750K – $1M' },
                    { min: 1000000, max: 9999999999, label: '$1M+' },
                ];
                // JMD price range bands
                const jmdRanges = [
                    { min: 0, max: 5000000, label: 'J$0 – J$5M' },
                    { min: 5000000, max: 10000000, label: 'J$5M – J$10M' },
                    { min: 10000000, max: 20000000, label: 'J$10M – J$20M' },
                    { min: 20000000, max: 30000000, label: 'J$20M – J$30M' },
                    { min: 30000000, max: 50000000, label: 'J$30M – J$50M' },
                    { min: 50000000, max: 75000000, label: 'J$50M – J$75M' },
                    { min: 75000000, max: 100000000, label: 'J$75M – J$100M' },
                    { min: 100000000, max: 9999999999, label: 'J$100M+' },
                ];
                const ranges = isJmd ? jmdRanges : usdRanges;
                this.priceRangeOptions = ranges.map(r => ({
                    value: r.min + '_' + r.max,
                    label: r.label,
                    min: r.min,
                    max: r.max,
                }));
                // Reset selection when currency changes
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
            },

            formatPrice(usdPrice) {
                if (this.selectedCurrency === 'jmd') {
                    const jmd = Math.round(usdPrice * this.usdToJmdRate);
                    return 'J$' + (jmd >= 1000000 ? (jmd / 1000000).toFixed(2) + 'M' : jmd.toLocaleString());
                }
                return '$' + (usdPrice >= 100000 ? (usdPrice / 1000).toFixed(usdPrice % 1000 === 0 ? 0 : 1) + 'K' : usdPrice.toLocaleString());
            },

            setSelectedTypeName() {
                if (this.filters.type) {
                    for (let group of this.propertyTypeHierarchy) {
                        for (let child of group.children) {
                            if (child.term_id == this.filters.type) {
                                this.selectedTypeName = child.name;
                                return;
                            }
                        }
                    }
                }
            },

            selectType(slug, name) {
                this.filters.type = slug;
                this.selectedTypeName = name;
                this.showTypeDropdown = false;
            },

            setupTomSelect() {
                const self = this;
                setTimeout(() => {
                    const el = document.getElementById('city-select');
                    if (!el) return;
                    if (el.tomselect) {
                        el.tomselect.on('change', (value) => {
                            self.filters.city = value;
                            self.resetLocationSuggestions();
                        });
                        return;
                    }
                    new TomSelect('#city-select', {
                        placeholder: 'Select a city...',
                        allowEmptyOption: true,
                        maxOptions: null,
                        onChange: (value) => {
                            self.filters.city = value;
                            self.resetLocationSuggestions();
                        }
                    });
                }, 100);
            },

            initializeMobileSliders() {
                // Mobile sliders removed — using dropdowns now
            },

            syncPriceSlider() { /* no-op: using dropdowns */ },
            syncAreaSlider() { /* no-op: using dropdowns */ },

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
                if (!this.geocoder || !this.filters.city) return;
                const self = this;
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

            searchProperties() {
                const params = new URLSearchParams();
                if (this.filters.city) params.append('prop_city', this.filters.city);
                if (this.filters.location) params.append('keyword', this.filters.location);
                if (this.filters.type) params.append('property_type', this.filters.type);
                if (this.filters.beds > 0) params.append('beds', this.filters.beds);
                if (this.filters.baths > 0) params.append('baths', this.filters.baths);
                if (this.filters.priceMin > 0) params.append('price_min', this.filters.priceMin);
                if (this.filters.priceMax < 9999999999) params.append('price_max', this.filters.priceMax);
                if (this.filters.areaMin > 0) params.append('area_min', this.filters.areaMin);
                if (this.filters.areaMax < 100000) params.append('area_max', this.filters.areaMax);
                if (this.filters.featured) params.append('featured', 'true');
                if (this.filters.listingStatus) params.append('listing_status', this.filters.listingStatus);
                params.append('currency', this.selectedCurrency);

                const queryString = params.toString();
                let archiveUrl = '<?= $properties_page_url ?>';

                if (!archiveUrl || archiveUrl.includes('')) {
                    archiveUrl = '<?= home_url('/properties') ?>';
                }
                if (!archiveUrl.endsWith('/')) {
                    archiveUrl += '/';
                }

                window.location.href = archiveUrl + (queryString ? '?' + queryString : '');
            }
        };
    }
</script>