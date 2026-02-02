<!-- Advanced Property Search Filter with Responsive Design -->
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
        'taxonomy'   => 'property_type',
        'hide_empty' => false,
        'parent'     => $parent->term_id,
    ]);

    $children_with_icons = array_map(function ($child) {
        return [
            'term_id' => $child->term_id,
            'name'    => $child->name,
            'slug'    => $child->slug,
            'icon'    => get_field('icons', 'property_type_' . $child->term_id),
        ];
    }, $children);

    $property_type_hierarchy[] = [
        'parent'   => [
            'term_id' => $parent->term_id,
            'name'    => $parent->name,
        ],
        'children' => $children_with_icons,
    ];
}

$filter_params = $filter_params ?? [];

?>
<div x-data="propertyFiltering(<?php echo htmlspecialchars(json_encode($filter_params)); ?>, <?php echo htmlspecialchars(json_encode($cities_data)); ?>, <?php echo htmlspecialchars(json_encode($property_type_hierarchy)); ?>)"
    class="absolute bottom-[-6%] left-1/2 transform -translate-x-1/2 md:w-[68.958vw] w-[90%] flex flex-col md:flex-row items-center bg-white shadow-lg rounded-[16px] z-10 md:h-[7vw] h-fit">
    <div class="md:w-[17.188vw] w-full md:border-r border-b md:border-b-0 border-slate-200 py-[1.5vw] md:py-[1.2vw] px-[2.35vw] md:px-0 md:pl-[1.8vw] md:pr-[1.458vw]">
        <label class="block text-[2.35vw] md:text-[1.042vw] font-semibold text-slate-900 tracking-wide mb-[2vw] md:mb-[1.354vw]">City</label>
        <select id="city-select" x-model="filters.city" @change="resetLocationSuggestions();"
            class="w-full font-inter text-[2vw] md:text-[0.833vw] text-slate-900 outline-none border-none bg-transparent">
            <option value="" class="font-inter">Select a city...</option>
            <template x-for="city in citiesList" :key="city">
                <option :value="city" x-text="city" class="font-inter"></option>
            </template>
        </select>
    </div>

    <div class="md:w-[17.188vw] w-full md:border-r border-b md:border-b-0 border-slate-200 py-[1.5vw] md:py-[0.99vw] px-[2.35vw] md:px-0 md:pl-[1.8vw] md:pr-[1.458vw] relative">
        <label class="block text-[2.35vw] md:text-[1.042vw] font-semibold text-slate-900 tracking-wide mb-[2vw] md:mb-[1.354vw]">Location</label>
        <input type="text" x-model="filters.location" @input="searchLocations($event)"
            @focus="showLocationSuggestions = true" @blur="setTimeout(() => showLocationSuggestions = false, 200)"
            placeholder="Search..." class="w-full text-[2vw] md:text-[0.833vw] text-slate-900 outline-none font-inter bg-transparent">

        <!-- Location Suggestions from Google API -->
        <div x-show="showLocationSuggestions && locationSuggestions.length"
            class="absolute top-full left-0 right-0 mt-2 bg-white border border-slate-300 rounded-lg shadow-lg z-20 max-h-56 overflow-y-auto">
            <template x-for="location in locationSuggestions" :key="location">
                <button type="button"
                    @click="filters.location = location; showLocationSuggestions = false;"
                    class="w-full text-left px-4 py-2.5 hover:bg-blue-50 text-slate-900 text-sm transition"
                    x-text="location"></button>
            </template>
        </div>
    </div>

    <div class="relative md:w-[17.188vw] w-full md:border-r border-b md:border-b-0 border-slate-200 py-[1.5vw] md:py-[0.99vw] px-[2.35vw] md:px-0 md:pl-[1.667vw] md:pr-[1.458vw]">
        <label class="block text-[2.35vw] md:text-[1.042vw] font-semibold text-slate-900 tracking-wide mb-[2vw] md:mb-[1.354vw]">Type</label>
        <button @click="showTypeDropdown = !showTypeDropdown" class="w-full text-left text-[2vw] md:text-[0.833vw] text-slate-900 outline-none font-inter flex justify-between items-center bg-transparent">
            <span x-text="selectedTypeName || 'Select type...'" class="font-inter truncate"></span>
            <svg class="w-[2.35vw] md:w-4 h-[2.35vw] md:h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>

        <!-- Custom Dropdown with Tabs -->
        <div x-show="showTypeDropdown" @click.outside="showTypeDropdown = false"
            class="absolute top-full left-0 w-full bg-white border border-slate-300 rounded-lg shadow-lg z-20 max-h-96 overflow-y-auto mt-2">
            <!-- Tabs -->
            <div class="flex border-b">
                <template x-for="(typeGroup, index) in propertyTypeHierarchy" :key="typeGroup.parent.term_id">
                    <button @click="activeTab = index"
                        :class="{ 'bg-blue-100 text-[var(--primary-color)]': activeTab === index, 'text-slate-600': activeTab !== index }"
                        class="flex-1 py-2 px-4 text-sm font-medium border-r last:border-r-0 font-inter">
                        <span x-text="typeGroup.parent.name"></span>
                    </button>
                </template>
            </div>

            <!-- Tab Content -->
            <div class="p-4">
                <template x-for="(typeGroup, index) in propertyTypeHierarchy" :key="index">
                    <div x-show="activeTab === index" class="grid grid-cols-2 gap-2">
                        <template x-for="child in typeGroup.children" :key="child.term_id">
                            <button @click="selectType(child.term_id, child.name)"
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
        class="md:w-[6.146vw] w-full bg-[var(--primary-color)] flex items-center justify-center text-white md:border-r border-white md:p-4 p-[1.5vw] h-full">
        <svg class="md:w-5 md:h-5 w-[3vw] h-[3vw]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
            </path>
        </svg>
    </button>
    <button @click="searchProperties()"
        class="md:w-[14.063vw] w-full md:p-4 p-[1.5vw] bg-[var(--primary-color)] text-white h-full md:text-[1.25vw] text-[2vw] font-semibold md:rounded-r-[16px] rounded-b-[16px] md:rounded-b-none font-inter">Search</button>

    <!-- Additional Filters Dropdown -->
    <div x-show="showFilters" x-cloak class="absolute left-0 w-full bg-white shadow-lg rounded-[16px] z-10 mt-2" style="top: calc(100% + 0rem);">
        <div class="grid md:grid-cols-4">
            <!-- Bedrooms Slider -->
            <div class="border-r border-slate-300 py-[2.765vw] px-[5.765vw] md:py-[0.99vw] md:px-[1.458vw] col-span-2 md:col-span-1">
                <label class="block text-[3.765vw] md:text-[1.042vw] font-semibold text-slate-900 tracking-wide mb-[1.354vw] font-inter">Bedrooms (Min)</label>
                <div id="bedroom-slider" class="h-2"></div>
                <div class="text-center mt-2 text-[3.765vw] md:text-[0.833vw]" x-text="filters.beds" class="font-inter text-[0.833vw]"></div>
            </div>

            <!-- Bathrooms Slider -->
            <div class="border-r border-slate-300 py-[2.765vw] px-[5.765vw] md:py-[0.99vw] md:px-[1.458vw]  col-span-2 md:col-span-1">
                <label class="block text-[3.765vw] md:text-[1.042vw] font-semibold text-slate-900 tracking-wide mb-[1.354vw] font-inter">Bathrooms (Min)</label>
                <div id="bathroom-slider" class="h-2"></div>
                <div class="text-center mt-2 text-[3.765vw] font-inter md:text-[0.833vw]" x-text="filters.baths"></div>
            </div>

            <!-- Price Slider -->
            <div class=" border-t md:border-t-[0] border-r border-slate-300 py-[2.765vw] px-[5.765vw] md:py-[0.99vw] md:px-[1.458vw]  col-span-4 md:col-span-1">
                <label class="block text-[3.765vw] md:text-[1.042vw] font-semibold text-slate-900 tracking-wide mb-[1.354vw] font-inter">Price Range</label>
                <div id="price-slider" class="h-2"></div>
                <div class="flex justify-between mt-2">
                    <span x-text="'$' + filters.priceMin.toLocaleString()" class="font-inter text-[3.765vw] md:text-[0.833vw]"></span>
                    <span x-text="'$' + filters.priceMax.toLocaleString()" class="font-inter text-[3.765vw] md:text-[0.833vw]"></span>
                </div>
            </div>

            <!-- Area Slider -->
            <div class="border-r border-slate-300 py-[2.765vw] px-[5.765vw] md:py-[0.99vw] md:px-[1.458vw]  col-span-4 md:col-span-1">
                <label class="block text-[3.765vw] md:text-[1.042vw] font-semibold text-slate-900 tracking-wide mb-[1.354vw] font-inter">Area Range (sq ft)</label>
                <div id="area-slider" class="h-2"></div>
                <div class="flex justify-between mt-2">
                    <span x-text="filters.areaMin.toLocaleString()" class="font-inter text-[3.765vw] md:text-[0.833vw]"></span>
                    <span x-text="filters.areaMax.toLocaleString()" class="font-inter text-[3.765vw] md:text-[0.833vw]"></span>
                </div>
            </div>

            <!-- Featured Checkbox -->
            <div class="col-span-4 p-4 border-t border-slate-300 flex items-center">
                <label class="flex items-center">
                    <input type="checkbox" x-model="filters.featured" class="mr-2">
                    <span class="font-inter text-[3.765vw] md:text-[0.833vw]">Featured Properties Only</span>
                </label>
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

<style>
    [x-cloak] { display: none !important; }
</style>

<script>
    function propertyFiltering(initialParams, citiesData, propertyTypeHierarchy, bedrooms, bathrooms) {
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
            locationSuggestions: [],
            showLocationSuggestions: false,
            showTypeDropdown: false,
            activeTab: 0,
            selectedTypeName: '',

            bedroomSlider: null,
            bathroomSlider: null,
            priceSlider: null,
            areaSlider: null,
            geocoder: null,
            autocompleteListener: null,

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

            selectType(id, name) {
                this.filters.type = id;
                this.selectedTypeName = name;
                this.showTypeDropdown = false;
            },

            setupTomSelect() {
                const self = this;
                setTimeout(() => {
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
                    });
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
                if (!this.geocoder || !this.filters.city) {
                    console.warn('Geocoder not ready or city not selected');
                    return;
                }

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
                if (this.filters.city) params.append('city', encodeURIComponent(this.filters.city));
                if (this.filters.location) params.append('location', encodeURIComponent(this.filters.location));
                if (this.filters.type) params.append('property_type', this.filters.type);
                if (this.filters.beds > 0) params.append('beds', this.filters.beds);
                if (this.filters.baths > 0) params.append('baths', this.filters.baths);
                if (this.filters.priceMin > 0) params.append('price_min', this.filters.priceMin);
                if (this.filters.priceMax < 5000000) params.append('price_max', this.filters.priceMax);
                if (this.filters.areaMin > 0) params.append('area_min', this.filters.areaMin);
                if (this.filters.areaMax < 100000) params.append('area_max', this.filters.areaMax);
                if (this.filters.featured) params.append('featured', 'true');

                const queryString = params.toString();
                const archiveUrl = '<?= home_url() ?>/properties';
                window.location.href = archiveUrl + (queryString ? '?' + queryString : '');
            }
        };
    }
</script>
