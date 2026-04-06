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
<!-- Desktop Filter Bar -->
<div x-data="propertyFiltering(<?php echo htmlspecialchars(json_encode($filter_params)); ?>, <?php echo htmlspecialchars(json_encode($cities_data)); ?>, <?php echo htmlspecialchars(json_encode($property_type_hierarchy)); ?>)"
    class="absolute bottom-[-6%] left-1/2 transform -translate-x-1/2 md:w-[68.958vw] w-[90%] flex flex-col md:flex-row items-center bg-white shadow-lg rounded-[16px] z-10 md:h-[7vw] h-fit">

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

        <div class="md:w-[17.188vw] md:border-r border-slate-200 py-[0.99vw] px-0 md:pl-[1.8vw] md:pr-[1.458vw] relative">
            <label class="block text-[1.042vw] font-semibold text-slate-900 tracking-wide mb-[1.354vw]">Area</label>
            <input type="text" x-model="filters.location" @input="searchLocations($event)"
                @focus="showLocationSuggestions = true" @blur="setTimeout(() => showLocationSuggestions = false, 200)"
                placeholder="Search..."
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

        <div class="relative md:w-[17.188vw] md:border-r border-slate-200 py-[0.99vw] px-0 md:pl-[1.667vw] md:pr-[1.458vw]">
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

            <!-- ✅ Bedrooms — Plus/Minus Buttons -->
            <div class="border-r border-slate-300 py-[0.99vw] px-[1.458vw] col-span-1">
                <label class="block text-[1.042vw] font-semibold text-slate-900 tracking-wide mb-[1.354vw] font-inter">
                    Bedrooms (Min)
                </label>
                <div class="flex items-center gap-3">
                    <button type="button"
                        @click="filters.beds = Math.max(0, filters.beds - 1)"
                        class="w-8 h-8 rounded-full border border-slate-300 flex items-center justify-center text-slate-600 hover:bg-slate-100 text-xl leading-none select-none font-light">
                        −
                    </button>
                    <span class="font-inter text-[1vw] font-bold min-w-[1.5rem] text-center" x-text="filters.beds === 0 ? 'Any' : filters.beds + '+'"></span>
                    <button type="button"
                        @click="filters.beds = Math.min(10, filters.beds + 1)"
                        class="w-8 h-8 rounded-full border border-slate-300 flex items-center justify-center text-slate-600 hover:bg-slate-100 text-xl leading-none select-none font-light">
                        +
                    </button>
                </div>
            </div>

            <!-- ✅ Bathrooms — Plus/Minus Buttons (step 0.5) -->
            <div class="border-r border-slate-300 py-[0.99vw] px-[1.458vw] col-span-1">
                <label class="block text-[1.042vw] font-semibold text-slate-900 tracking-wide mb-[1.354vw] font-inter">
                    Bathrooms (Min)
                </label>
                <div class="flex items-center gap-3">
                    <button type="button"
                        @click="filters.baths = Math.max(0, parseFloat((filters.baths - 0.5).toFixed(1)))"
                        class="w-8 h-8 rounded-full border border-slate-300 flex items-center justify-center text-slate-600 hover:bg-slate-100 text-xl leading-none select-none font-light">
                        −
                    </button>
                    <span class="font-inter text-[1vw] font-bold min-w-[1.5rem] text-center" x-text="filters.baths === 0 ? 'Any' : filters.baths + '+'"></span>
                    <button type="button"
                        @click="filters.baths = Math.min(10, parseFloat((filters.baths + 0.5).toFixed(1)))"
                        class="w-8 h-8 rounded-full border border-slate-300 flex items-center justify-center text-slate-600 hover:bg-slate-100 text-xl leading-none select-none font-light">
                        +
                    </button>
                </div>
            </div>

            <!-- ✅ Price Slider + Synced Inputs -->
            <div class="border-r border-slate-300 py-[0.99vw] px-[1.458vw] col-span-1">
                <label class="block text-[1.042vw] font-semibold text-slate-900 tracking-wide mb-[1.354vw] font-inter">
                    Price Range
                </label>
                <div id="price-slider" class="h-2"></div>
                <div class="flex justify-between mt-3 gap-1">
                    <input type="number"
                        x-model.number="filters.priceMin"
                        @input="syncPriceSlider()"
                        class="w-1/2 text-[0.75vw] border border-slate-200 rounded px-1 py-0.5 font-inter text-center outline-none focus:border-[var(--primary-color)]"
                        min="0" max="5000000" step="50000" placeholder="Min">
                    <input type="number"
                        x-model.number="filters.priceMax"
                        @input="syncPriceSlider()"
                        class="w-1/2 text-[0.75vw] border border-slate-200 rounded px-1 py-0.5 font-inter text-center outline-none focus:border-[var(--primary-color)]"
                        min="0" max="5000000" step="50000" placeholder="Max">
                </div>
            </div>

            <!-- ✅ Area Slider + Synced Inputs -->
            <div class="py-[0.99vw] px-[1.458vw] col-span-1">
                <label class="block text-[1.042vw] font-semibold text-slate-900 tracking-wide mb-[1.354vw] font-inter">
                    Area Range (sq ft)
                </label>
                <div id="area-slider" class="h-2"></div>
                <div class="flex justify-between mt-3 gap-1">
                    <input type="number"
                        x-model.number="filters.areaMin"
                        @input="syncAreaSlider()"
                        class="w-1/2 text-[0.75vw] border border-slate-200 rounded px-1 py-0.5 font-inter text-center outline-none focus:border-[var(--primary-color)]"
                        min="0" max="100000" step="500" placeholder="Min">
                    <input type="number"
                        x-model.number="filters.areaMax"
                        @input="syncAreaSlider()"
                        class="w-1/2 text-[0.75vw] border border-slate-200 rounded px-1 py-0.5 font-inter text-center outline-none focus:border-[var(--primary-color)]"
                        min="0" max="100000" step="500" placeholder="Max">
                </div>
            </div>

            <!-- Featured Checkbox -->
            <div class="col-span-4 p-4 border-t border-slate-300 flex items-center">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" x-model="filters.featured" class="mr-2">
                    <span class="font-inter text-[0.833vw]">Featured Properties Only</span>
                </label>
            </div>
        </div>
    </div>

    <!-- ✅ Verified Badge -->
    <div class="hidden md:flex absolute -top-5 left-5 items-center gap-1.5 px-3 py-1 bg-white border border-green-200 rounded-full shadow-sm"
        style="white-space: nowrap;">
        <svg class="w-3.5 h-3.5 text-green-600 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-1 14l-3-3 1.41-1.41L11 12.17l4.59-4.58L17 9l-6 6z"/>
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
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAUPkXXwkGt0xC5ongE7-62nzz6l7D3Nf4&libraries=places,marker&v=beta"></script>

<script>
    function propertyFiltering(initialParams, citiesData, propertyTypeHierarchy) {
        initialParams = initialParams || {};
        return {
            filters: {
                type: initialParams.property_type || '',
                priceMin: initialParams.price_min || 0,
                priceMax: initialParams.price_max || 5000000,
                areaMin: initialParams.area_min || 0,
                areaMax: initialParams.area_max || 100000,
                beds: initialParams.beds || 0,
                baths: initialParams.baths || 0,
                city: initialParams.city || '',
                location: initialParams.location || '',
                featured: initialParams.featured === 'true' ? true : false,
            },
            citiesData: citiesData,
            citiesList: Object.keys(citiesData),
            propertyTypeHierarchy: propertyTypeHierarchy,
            showFilters: false,
            showMobileSidebar: false,
            locationSuggestions: [],
            showLocationSuggestions: false,
            showTypeDropdown: false,
            activeTab: 0,
            selectedTypeName: '',

            priceSlider: null,
            areaSlider: null,
            priceSliderMobile: null,
            areaSliderMobile: null,
            geocoder: null,

            init() {
                this.setupTomSelect();
                this.initializeNouiSliders();
                this.geocoder = new google.maps.Geocoder();
                this.setSelectedTypeName();
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

            initializeNouiSliders() {
                const priceElement = document.getElementById('price-slider');
                const areaElement = document.getElementById('area-slider');

                // Price Slider — updates input fields live while dragging
                if (priceElement && !priceElement.noUiSlider) {
                    this.priceSlider = noUiSlider.create(priceElement, {
                        start: [this.filters.priceMin, this.filters.priceMax],
                        range: { min: 0, max: 5000000 },
                        step: 50000,
                        tooltips: false,
                        connect: true,
                    });
                    this.priceSlider.on('update', (values) => {
                        this.filters.priceMin = parseInt(values[0]);
                        this.filters.priceMax = parseInt(values[1]);
                    });
                } else if (priceElement) {
                    this.priceSlider = priceElement.noUiSlider;
                }

                // Area Slider — updates input fields live while dragging
                if (areaElement && !areaElement.noUiSlider) {
                    this.areaSlider = noUiSlider.create(areaElement, {
                        start: [this.filters.areaMin, this.filters.areaMax],
                        range: { min: 0, max: 100000 },
                        step: 500,
                        tooltips: false,
                        connect: true,
                    });
                    this.areaSlider.on('update', (values) => {
                        this.filters.areaMin = parseInt(values[0]);
                        this.filters.areaMax = parseInt(values[1]);
                    });
                } else if (areaElement) {
                    this.areaSlider = areaElement.noUiSlider;
                }
            },

            initializeMobileSliders() {
                const priceMobile = document.getElementById('price-slider-mobile');
                const areaMobile = document.getElementById('area-slider-mobile');

                if (priceMobile && !priceMobile.noUiSlider) {
                    this.priceSliderMobile = noUiSlider.create(priceMobile, {
                        start: [this.filters.priceMin, this.filters.priceMax],
                        range: { min: 0, max: 5000000 },
                        step: 50000,
                        tooltips: false,
                        connect: true,
                    });
                    this.priceSliderMobile.on('update', (values) => {
                        this.filters.priceMin = parseInt(values[0]);
                        this.filters.priceMax = parseInt(values[1]);
                    });
                } else if (priceMobile) {
                    this.priceSliderMobile = priceMobile.noUiSlider;
                }

                if (areaMobile && !areaMobile.noUiSlider) {
                    this.areaSliderMobile = noUiSlider.create(areaMobile, {
                        start: [this.filters.areaMin, this.filters.areaMax],
                        range: { min: 0, max: 100000 },
                        step: 500,
                        tooltips: false,
                        connect: true,
                    });
                    this.areaSliderMobile.on('update', (values) => {
                        this.filters.areaMin = parseInt(values[0]);
                        this.filters.areaMax = parseInt(values[1]);
                    });
                } else if (areaMobile) {
                    this.areaSliderMobile = areaMobile.noUiSlider;
                }
            },

            // Sync price inputs → slider
            syncPriceSlider() {
                if (this.priceSlider) {
                    this.priceSlider.set([this.filters.priceMin, this.filters.priceMax]);
                }
                if (this.priceSliderMobile) {
                    this.priceSliderMobile.set([this.filters.priceMin, this.filters.priceMax]);
                }
            },

            // Sync area inputs → slider
            syncAreaSlider() {
                if (this.areaSlider) {
                    this.areaSlider.set([this.filters.areaMin, this.filters.areaMax]);
                }
                if (this.areaSliderMobile) {
                    this.areaSliderMobile.set([this.filters.areaMin, this.filters.areaMax]);
                }
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
                if (this.filters.priceMax < 5000000) params.append('price_max', this.filters.priceMax);
                if (this.filters.areaMin > 0) params.append('area_min', this.filters.areaMin);
                if (this.filters.areaMax < 100000) params.append('area_max', this.filters.areaMax);
                if (this.filters.featured) params.append('featured', 'true');

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