<?php
/**
 * Single Property Template
 */
get_header();

while (have_posts()):
    the_post();
    $property_id = get_the_ID();

    // Get property data
    $price = get_post_meta($property_id, '_property_price', true);
    $area = get_post_meta($property_id, '_property_area', true);
    $featured = get_post_meta($property_id, '_property_featured', true);
    $gallery = get_post_meta($property_id, '_property_gallery', true);
    $amenities_data = get_post_meta($property_id, '_property_amenities_data', true);

    $status = get_post_meta($property_id, 'property_status', true);
    if (empty($status)) {
        $status = get_post_meta($property_id, '_property_status', true);
    }

    // Get full address details
    $full_address = property_theme_get_full_address($property_id);
    $coords = property_theme_get_property_coords($property_id);
    $lat = !empty($coords['lat']) ? $coords['lat'] : 25.2048;
    $lng = !empty($coords['lng']) ? $coords['lng'] : 55.2708;



    // Get property type
    $property_types = wp_get_post_terms($property_id, 'property_type');
    $property_type = !empty($property_types) ? $property_types[0]->name : '';
    $bedrooms = wp_get_post_terms($property_id, 'bedroom');
    $bathrooms = wp_get_post_terms($property_id, 'bathroom');
    $bedroom = !empty($bedrooms) ? $bedrooms[0]->name : '';
    $bathroom = !empty($bathrooms) ? $bathrooms[0]->name : '';

    $icons = get_field('icons', 'property_type_' . $property_types[0]->term_id);
    $publish_time = get_the_time('U');
    $current_time = current_time('timestamp');
    $days_diff = floor(($current_time - $publish_time) / (60 * 60 * 24));

    // Format price
    $formatted_price = number_format((float) $price);

    // Author Info 
    $author_id = get_post_field('post_author', $property_id);
    $author_name = get_the_author_meta('display_name', $author_id);
    $author_avatar = get_avatar_url($author_id, array('size' => 96));

    ?>
    <style>
        body {
            background-color: #F3F3F3;
        }
    </style>
    <script
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAUPkXXwkGt0xC5ongE7-62nzz6l7D3Nf4&libraries=places,marker&v=beta"
    async>
</script>
    <section class="propertyBanner">
        <div class="md:w-[80%] w-[90%] mx-auto md:pt-[9.938vw] pt-[34.824vw] flex md:flex-col flex-col-reverse">

            <!-- Property Details Here  -->
            <div
                class="propertyDetails flex items-start justify-between md:flex-row flex-col gap-[4.706vw] md:gap-0 md:mb-[1.979vw] mb-[8.941vw]">
                <div>
                    <h1
                        class="text-[8.412vw] md:text-[2.083vw] font-semibold leading-none md:mb-[0.833vw] mb-[3.765vw] text-black/90">
                        <?= get_the_title(); ?>
                    </h1>
                    <div class="flex flex-col md:flex-row md:items-end gap-[2.765vw] md:gap-[0.833vw]">
                        <p
                            class="text-[3.765vw] md:text-[0.833vw] font-inter text-[var(--primary-color)] flex items-center gap-[2.824vw] md:gap-[0.625vw]">
                            <svg class="md:w-[0.833vw] md:h-[0.833vw] w-[3.765vw] h-[3.765vw]" viewBox="0 0 16 16"
                                xmlns="http://www.w3.org/2000/svg" fill="none">
                                <path
                                    d="M9.66732 6.00004C9.66732 6.44207 9.49172 6.86599 9.17916 7.17855C8.8666 7.49111 8.44268 7.66671 8.00065 7.66671C7.55862 7.66671 7.1347 7.49111 6.82214 7.17855C6.50958 6.86599 6.33398 6.44207 6.33398 6.00004C6.33398 5.55801 6.50958 5.13409 6.82214 4.82153C7.1347 4.50897 7.55862 4.33337 8.00065 4.33337C8.44268 4.33337 8.8666 4.50897 9.17916 4.82153C9.49172 5.13409 9.66732 5.55801 9.66732 6.00004Z"
                                    stroke="#132364" />
                                <path
                                    d="M8.83897 11.6627C8.61379 11.8794 8.31345 12.0004 8.00097 12.0004C7.68849 12.0004 7.38815 11.8794 7.16297 11.6627C5.10364 9.66737 2.3443 7.43871 3.68964 4.20271C4.4183 2.45271 6.16497 1.33337 8.00097 1.33337C9.83697 1.33337 11.5843 2.45337 12.3123 4.20271C13.6563 7.43404 10.9036 9.67404 8.83897 11.6627Z"
                                    stroke="#132364" />
                                <path d="M12 13.3334C12 14.07 10.2093 14.6667 8 14.6667C5.79067 14.6667 4 14.07 4 13.3334"
                                    stroke="#132364" stroke-linecap="round" />
                            </svg>
                            <?= esc_html($full_address['address']); ?>
                        </p>
                        <div class="text-gray-800 font-inter md:text-[0.729vw] text-[3.294vw] font-medium capitalize">See on
                            the map</div>
                    </div>
                </div>
                <div
                    class="priceDetails text-right w-full md:w-auto md:bg-transparent bg-[var(--primary-color)] md:p-0 p-[3.765vw] rounded-[16px]">
                    <h4
                        class="md:text-[1.875vw] text-[11.471vw] md:text-black/90 text-white md:mb-[0.833vw] mb-none font-inter font-bold">
                        $ <?= esc_html($formatted_price); ?></h4>
                    <h6
                        class="md:text-[0.833vw] text-[3.765vw] md:text-black/90 text-white font-inter text-right font-bold">
                        <?= esc_html($area) ?> <span class="font-light md:text-gray-800 text-gray-300">/ sqft</span>
                    </h6>
                </div>
            </div>

            <!-- Property Gallery -->
            <div x-data="{            
                    mainSwiper: null,
                    active: 0,
                    images: [
                        '<?= get_the_post_thumbnail_url(); ?>',
                    <?php if (!empty($gallery)):
                        foreach ($gallery as $img): ?>
                            '<?= esc_url($img["media_url"]); ?>',
                        <?php endforeach; endif; ?>
                    ],

                    init() {
                        this.mainSwiper = new Swiper(this.$refs.mainSwiper, {
                            spaceBetween: 10,
                            on: {
                                slideChange: () => {
                                    this.active = this.mainSwiper.activeIndex
                                }
                            }
                        })
                    },

                    goTo(index) {
                        this.active = index
                        this.mainSwiper.slideTo(index)
                    }
                }" x-init="init()">
                <div class="grid grid-cols-12 md:gap-[0.833vw] gap-[3.765vw] mb-[30px]">

                    <!-- LEFT: MAIN SLIDER -->
                    <div
                        class="col-span-12 lg:col-span-8 md:h-[27.083vw] h-[122.353vw] rounded-[16px] overflow-hidden relative">
                        <div class="absolute top-5 left-5 bg-black/50 rounded-full flex gap-2 p-2 z-10">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M8 6C6.916 6 6 6.916 6 8C6 9.084 6.916 10 8 10C9.084 10 10 9.084 10 8C10 6.916 9.084 6 8 6Z"
                                    fill="white" />
                                <path
                                    d="M13.334 3.33337H11.61L9.80532 1.52871C9.68032 1.40367 9.51078 1.33341 9.33398 1.33337H6.66732C6.49052 1.33341 6.32098 1.40367 6.19598 1.52871L4.39132 3.33337H2.66732C1.93198 3.33337 1.33398 3.93137 1.33398 4.66671V12C1.33398 12.7354 1.93198 13.3334 2.66732 13.3334H13.334C14.0693 13.3334 14.6673 12.7354 14.6673 12V4.66671C14.6673 3.93137 14.0693 3.33337 13.334 3.33337ZM8.00065 11.3334C6.19398 11.3334 4.66732 9.80671 4.66732 8.00004C4.66732 6.19337 6.19398 4.66671 8.00065 4.66671C9.80732 4.66671 11.334 6.19337 11.334 8.00004C11.334 9.80671 9.80732 11.3334 8.00065 11.3334Z"
                                    fill="white" />
                            </svg>
                            <span class="text-xs text-white" x-text="images.length + ' Photos'"></span>
                        </div>
                        <div class="swiper h-full" x-ref="mainSwiper">
                            <div class="swiper-wrapper">

                                <template x-for="(img, index) in images" :key="index">
                                    <div class="swiper-slide">
                                        <img :src="img" class="w-full h-full object-cover" />
                                    </div>
                                </template>

                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: STATIC 2x2 GRID -->
                    <div class="col-span-12 lg:col-span-4 grid md:grid-cols-2 grid-cols-4 gap-[1.765vw] md:gap-[0.833vw]">

                        <template x-for="(img, index) in images.slice(1,5)" :key="index">
                            <div class="h-[20.824vw] md:h-[13.021vw] rounded-[16px] overflow-hidden cursor-pointer border-2"
                                :class="active === index + 1 ? 'border-black' : 'border-transparent'"
                                @click="goTo(index + 1)">
                                <img :src="img" class="w-full h-full object-cover" />
                            </div>
                        </template>

                    </div>

                </div>
            </div>





        </div>
    </section>

    <section class="propertyAmentities w-[90%] md:w-[80%] mx-auto md:pt-[1.875vw] pt-[8.471vw]">
        <div class="grid grid-cols-12 gap-[1.765vw] md:gap-[0.833vw]">
            <!-- Left Content -->
            <div class="col-span-12 lg:col-span-8">
                <!-- Property -- Details -- Here -->
                <div class="grid-cols-2 md:grid-cols-3 grid gap-[1.765vw] md:gap-[0.833vw]">

                    <div
                        class="bg-white rounded-[8px] shadow-lg flex items-center gap-[3.294vw] md:gap-[0.729vw] md:p-[0.521vw] p-[2.353vw]">
                        <div
                            class="icon p-[0.941vw] md:p-[0.208vw] rounded-[8px] fill-[var(--primary-color)] stroke-1 flex items-center justify-center bg-[#F3F3F3] w-[10.353vw] h-[10.353vw] md:w-[2.292vw] md:h-[2.292vw]">
                            <?= $icons ?>
                        </div>
                        <div>
                            <h4 class="text-[3.500vw] md:text-[1.042vw] font-medium text-black/90">
                                <?= esc_html($property_type); ?>
                            </h4>
                            <p class="text-[2.824vw] md:text-[0.625vw] font-medium text-black/90 capitalize font-inter">
                                Property Type</p>
                        </div>
                    </div>

                    <div
                        class="bg-white rounded-[8px] shadow-lg flex items-center gap-[3.294vw] md:gap-[0.729vw] md:p-[0.521vw] p-[2.353vw]">
                        <div
                            class="icon p-[0.941vw] md:p-[0.208vw] rounded-[8px] flex items-center justify-center bg-[#F3F3F3] w-[10.353vw] h-[10.353vw] md:w-[2.292vw] md:h-[2.292vw]">
                            <svg class="md:w-[1.667vw] md:h-[1.667vw] w-[7.529vw] h-[7.529vw]" viewBox="0 0 32 32"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M21.6 4.80005C22.6155 4.79989 23.5931 5.18602 24.3345 5.88011C25.0758 6.5742 25.5254 7.52429 25.592 8.53765L25.6 8.80005V12.88C26.4575 13.0551 27.2338 13.5066 27.8101 14.1652C28.3864 14.8239 28.7307 15.6533 28.7904 16.5265L28.8 16.8V26.4C28.8004 26.6 28.7259 26.7928 28.5912 26.9405C28.4565 27.0882 28.2714 27.1802 28.0723 27.1982C27.8732 27.2163 27.6745 27.1592 27.5155 27.0381C27.3564 26.917 27.2484 26.7408 27.2128 26.5441L27.2 26.4V22.4H4.8V26.4C4.80036 26.6 4.72587 26.7928 4.59118 26.9405C4.4565 27.0882 4.27138 27.1802 4.07228 27.1982C3.87319 27.2163 3.67454 27.1592 3.51547 27.0381C3.35639 26.917 3.24841 26.7408 3.2128 26.5441L3.2 26.4V16.8C3.19981 15.8779 3.51822 14.9841 4.10135 14.2697C4.68448 13.5554 5.49651 13.0645 6.4 12.88V8.80005C6.39984 7.7845 6.78597 6.80691 7.48006 6.06558C8.17415 5.32424 9.12424 4.87467 10.1376 4.80805L10.4 4.80005H21.6ZM24.8 14.4H7.2C6.60364 14.4 6.02863 14.6221 5.58702 15.0228C5.14542 15.4236 4.86885 15.9745 4.8112 16.568L4.8 16.8V20.8H27.2V16.8C27.2 16.2037 26.978 15.6287 26.5772 15.1871C26.1764 14.7455 25.6256 14.4689 25.032 14.4112L24.8 14.4ZM21.6 6.40005H10.4C9.80338 6.40008 9.22817 6.62232 8.78653 7.02344C8.34488 7.42456 8.06847 7.97579 8.0112 8.56965L8 8.80005V12.8H9.6V12C9.6 11.7879 9.68428 11.5844 9.83431 11.4344C9.98434 11.2843 10.1878 11.2 10.4 11.2H14.4C14.6122 11.2 14.8157 11.2843 14.9657 11.4344C15.1157 11.5844 15.2 11.7879 15.2 12V12.8H16.8V12C16.8 11.7879 16.8843 11.5844 17.0343 11.4344C17.1843 11.2843 17.3878 11.2 17.6 11.2H21.6C21.8122 11.2 22.0157 11.2843 22.1657 11.4344C22.3157 11.5844 22.4 11.7879 22.4 12V12.8H24V8.80005C24 8.20369 23.778 7.62868 23.3772 7.18708C22.9764 6.74547 22.4256 6.4689 21.832 6.41125L21.6 6.40005Z"
                                    fill="#1A1A1A" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-[4.706vw] md:text-[1.042vw] font-medium text-black/90">
                                <?= esc_html($bedroom); ?>
                            </h4>
                            <p class="text-[2.824vw] md:text-[0.625vw] font-medium text-black/90 capitalize font-inter">
                                Bedrooms</p>
                        </div>
                    </div>

                    <div
                        class="bg-white rounded-[8px] shadow-lg flex items-center gap-[3.294vw] md:gap-[0.729vw] md:p-[0.521vw] p-[2.353vw]">
                        <div
                            class="icon p-[0.941vw] md:p-[0.208vw] rounded-[8px] flex items-center justify-center bg-[#F3F3F3] w-[10.353vw] h-[10.353vw] md:w-[2.292vw] md:h-[2.292vw]">

                            <svg class="md:w-[1.667vw] md:h-[1.667vw] w-[7.529vw] h-[7.529vw]" viewBox="0 0 44 44"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect width="44" height="44" rx="8" fill="#F3F3F3" />
                                <path
                                    d="M35 23.5H11V12.25C10.9988 11.8263 11.0817 11.4066 11.2439 11.0152C11.406 10.6238 11.6442 10.2684 11.9446 9.96965L11.9696 9.94465C12.4403 9.47454 13.0448 9.16125 13.7003 9.04757C14.3558 8.93389 15.0305 9.02538 15.6321 9.30952C15.0639 10.2541 14.8278 11.3615 14.9611 12.4558C15.0945 13.55 15.5898 14.5682 16.3682 15.3487L17.0526 16.0331L15.7928 17.293L17.2069 18.7071L18.4668 17.4473L25.4473 10.4669L26.7071 9.20708L25.2929 7.7929L24.0331 9.05271L23.3486 8.36827C22.5292 7.5511 21.449 7.04737 20.2962 6.94485C19.1435 6.84234 17.9914 7.14754 17.0406 7.80727C16.0379 7.17409 14.8498 6.90074 13.6713 7.03213C12.4928 7.16352 11.394 7.69183 10.5554 8.53027L10.5304 8.55527C10.0437 9.03932 9.65782 9.61511 9.39512 10.2493C9.13241 10.8835 8.99812 11.5635 9 12.25V23.5H7V25.5H9V27.4187C9.00001 27.5799 9.02601 27.7401 9.077 27.893L10.9375 33.4743C11.0368 33.7731 11.2277 34.033 11.4831 34.2171C11.7386 34.4012 12.0455 34.5002 12.3604 34.5H13.1666L12.4375 37H14.5208L15.25 34.5H28.2563L29.0063 37H31.0938L30.3438 34.5H31.6394C31.9543 34.5003 32.2613 34.4013 32.5168 34.2172C32.7722 34.0331 32.9632 33.7731 33.0625 33.4743L34.9229 27.893C34.9739 27.7401 34.9999 27.5799 35 27.4187V25.5H37V23.5H35ZM17.7825 9.78246C18.3335 9.23266 19.0801 8.92389 19.8585 8.92389C20.6368 8.92389 21.3834 9.23266 21.9344 9.78246L22.6187 10.4669L18.4669 14.6187L17.7825 13.9344C17.2327 13.3834 16.924 12.6368 16.924 11.8584C16.924 11.0801 17.2327 10.3335 17.7825 9.78246ZM33 27.3375L31.2792 32.5H12.7207L11 27.3375V25.5H33V27.3375Z"
                                    fill="#1A1A1A" />
                            </svg>

                        </div>
                        <div>
                            <h4 class="text-[4.706vw] md:text-[1.042vw] font-medium text-black/90">
                                <?= esc_html($bathroom); ?>
                            </h4>
                            <p class="text-[2.824vw] md:text-[0.625vw] font-medium text-black/90 capitalize font-inter">
                                Bathrooms</p>
                        </div>
                    </div>

                    <div
                        class="bg-white rounded-[8px] shadow-lg flex items-center gap-[3.294vw] md:gap-[0.729vw] md:p-[0.521vw] p-[2.353vw]">
                        <div
                            class="icon p-[0.941vw] md:p-[0.208vw] rounded-[8px] flex items-center justify-center bg-[#F3F3F3] w-[10.353vw] h-[10.353vw] md:w-[2.292vw] md:h-[2.292vw]">

                            <svg class="md:w-[1.667vw] md:h-[1.667vw] w-[7.529vw] h-[7.529vw]" viewBox="0 0 32 32"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_34173_2765)">
                                    <path
                                        d="M12 15.9973C12 17.0582 12.4214 18.0756 13.1716 18.8257C13.9217 19.5759 14.9392 19.9973 16 19.9973C17.0609 19.9973 18.0783 19.5759 18.8284 18.8257C19.5786 18.0756 20 17.0582 20 15.9973C20 14.9364 19.5786 13.919 18.8284 13.1689C18.0783 12.4187 17.0609 11.9973 16 11.9973C14.9392 11.9973 13.9217 12.4187 13.1716 13.1689C12.4214 13.919 12 14.9364 12 15.9973ZM1.36802 15.1586C1.25191 15.2652 1.15921 15.3947 1.09579 15.5389C1.03238 15.6832 0.999634 15.8391 0.999634 15.9966C0.999634 16.1542 1.03238 16.3101 1.09579 16.4543C1.15921 16.5986 1.25191 16.7281 1.36802 16.8346L5.52535 20.6453C5.65104 20.7605 5.8075 20.8368 5.9757 20.8647C6.1439 20.8927 6.31661 20.8712 6.47282 20.8028C6.62903 20.7345 6.76202 20.6222 6.85561 20.4797C6.9492 20.3371 6.99937 20.1705 7.00002 20V12C6.99989 11.8292 6.95009 11.6622 6.8567 11.5193C6.76331 11.3764 6.63035 11.2637 6.47404 11.195C6.31772 11.1263 6.14481 11.1046 5.97636 11.1324C5.80791 11.1603 5.65121 11.2366 5.52535 11.352L1.36802 15.1586ZM30.6307 16.8346C30.7473 16.7284 30.8405 16.599 30.9043 16.4547C30.9681 16.3104 31.001 16.1544 31.001 15.9966C31.001 15.8389 30.9681 15.6829 30.9043 15.5386C30.8405 15.3943 30.7473 15.2649 30.6307 15.1586L26.4747 11.348C26.3486 11.2324 26.1915 11.156 26.0227 11.1283C25.8539 11.1005 25.6806 11.1226 25.5242 11.1918C25.3677 11.261 25.2348 11.3743 25.1417 11.5178C25.0486 11.6613 24.9994 11.8289 25 12V20C24.9998 20.1707 25.0493 20.3378 25.1425 20.4809C25.2358 20.6239 25.3686 20.7367 25.5249 20.8054C25.6812 20.8742 25.8542 20.8959 26.0226 20.8679C26.191 20.84 26.3477 20.7635 26.4734 20.648L30.6307 16.8346ZM15.1627 30.628C15.2689 30.7444 15.3982 30.8374 15.5424 30.9011C15.6866 30.9647 15.8424 30.9976 16 30.9976C16.1576 30.9976 16.3135 30.9647 16.4577 30.9011C16.6018 30.8374 16.7311 30.7444 16.8374 30.628L20.648 26.4706C20.7636 26.3449 20.84 26.1883 20.868 26.0199C20.8959 25.8514 20.8742 25.6785 20.8055 25.5222C20.7367 25.3659 20.6239 25.2331 20.4809 25.1398C20.3379 25.0466 20.1708 24.9971 20 24.9973H12C11.8294 24.9974 11.6625 25.0471 11.5197 25.1403C11.3768 25.2336 11.2641 25.3663 11.1953 25.5224C11.1265 25.6785 11.1046 25.8513 11.1322 26.0196C11.1597 26.188 11.2357 26.3447 11.3507 26.4706L15.1627 30.628ZM16.8374 1.36531C16.7311 1.24887 16.6018 1.15587 16.4577 1.09223C16.3135 1.0286 16.1576 0.995728 16 0.995728C15.8424 0.995728 15.6866 1.0286 15.5424 1.09223C15.3982 1.15587 15.2689 1.24887 15.1627 1.36531L11.352 5.52264C11.2366 5.6485 11.1604 5.8052 11.1325 5.97365C11.1046 6.1421 11.1264 6.31501 11.195 6.47133C11.2637 6.62764 11.3764 6.7606 11.5193 6.85399C11.6623 6.94738 11.8293 6.99718 12 6.99731H20C20.1708 6.99718 20.3378 6.94738 20.4807 6.85399C20.6236 6.7606 20.7363 6.62764 20.805 6.47133C20.8737 6.31501 20.8954 6.1421 20.8675 5.97365C20.8397 5.8052 20.7634 5.6485 20.648 5.52264L16.8374 1.36531Z"
                                        stroke="#1A1A1A" stroke-linecap="round" stroke-linejoin="round" />
                                </g>
                                <defs>
                                    <clipPath id="clip0_34173_2765">
                                        <rect width="32" height="32" fill="white" />
                                    </clipPath>
                                </defs>
                            </svg>

                        </div>
                        <div>
                            <h4 class="text-[4.706vw] md:text-[1.042vw] font-medium text-black/90">
                                <?= esc_html($area); ?> <span class="text-gray-500 text-xs font-inter">/ sqft</span>
                            </h4>
                            <p class="text-[2.824vw] md:text-[0.625vw] font-medium text-black/90 capitalize font-inter">
                                Property Size</p>
                        </div>
                    </div>

                    <div
                        class="bg-white rounded-[8px] shadow-lg flex items-center gap-[3.294vw] md:gap-[0.729vw] md:p-[0.521vw] p-[2.353vw]">
                        <div
                            class="icon p-[0.941vw] md:p-[0.208vw] rounded-[8px] flex items-center justify-center bg-[#F3F3F3] w-[10.353vw] h-[10.353vw] md:w-[2.292vw] md:h-[2.292vw]">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="md:w-[1.667vw] md:h-[1.667vw] w-[7.529vw] h-[7.529vw] fill-[var(--primary-color)]"
                                viewBox="0 0 64 64">
                                <g>
                                    <path
                                        d="M55 8h-4V6a4 4 0 0 0-8 0v2h-8V6a4 4 0 0 0-8 0v2h-8V6a4 4 0 0 0-8 0v2H7a5.006 5.006 0 0 0-5 5v42a5.006 5.006 0 0 0 5 5h30a1 1 0 0 0 0-2H7a3 3 0 0 1-3-3V22h54v14a1 1 0 0 0 2 0V13a5.006 5.006 0 0 0-5-5ZM45 6a2 2 0 0 1 4 0v6a2 2 0 0 1-4 0ZM29 6a2 2 0 0 1 4 0v6a2 2 0 0 1-4 0ZM13 6a2 2 0 0 1 4 0v6a2 2 0 0 1-4 0ZM4 20v-7a3 3 0 0 1 3-3h4v2a4 4 0 0 0 8 0v-2h8v2a4 4 0 0 0 8 0v-2h8v2a4 4 0 0 0 8 0v-2h4a3 3 0 0 1 3 3v7Z"
                                        fill="#000000" opacity="1" data-original="#000000" class=""></path>
                                    <path
                                        d="M61 41a1 1 0 0 0 0-2h-4a1 1 0 0 0-1 1v4a1 1 0 0 0 2 0v-1.307a10.975 10.975 0 1 1-5.162-4 1 1 0 1 0 .7-1.875A13.018 13.018 0 1 0 59.235 41Z"
                                        fill="#000000" opacity="1" data-original="#000000" class=""></path>
                                    <path
                                        d="M50 46.184V41a1 1 0 0 0-2 0v5.184A3 3 0 0 0 46.184 48H43a1 1 0 0 0 0 2h3.184A2.993 2.993 0 1 0 50 46.184ZM49 50a1 1 0 1 1 1-1 1 1 0 0 1-1 1ZM27 33h-4a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2Zm-4-5v3h4v-3ZM39 33h-4a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2Zm-4-5v3h4v-3ZM15 33h-4a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2Zm-4-5v3h4v-3ZM51 33h-4a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2Zm-4-5v3h4v-3ZM27 44h-4a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2Zm-4-5v3h4v-3ZM35 44a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3a1 1 0 0 1 0 2h-3v3a1 1 0 0 1 0 2ZM15 44h-4a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2Zm-4-5v3h4v-3ZM27 55h-4a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2Zm-4-5v3h4v-3ZM15 55h-4a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2Zm-4-5v3h4v-3Z"
                                        fill="#000000" opacity="1" data-original="#000000" class=""></path>
                                </g>
                            </svg>

                        </div>
                        <div>
                            <h4 class="text-[4.706vw] md:text-[1.042vw] font-medium text-black/90">
                                Added</span>
                            </h4>
                            <p class="text-[2.824vw] md:text-[0.625vw] font-medium text-black/90 capitalize font-inter">
                                <?php
                                if ($days_diff == 0) {
                                    echo "today";
                                } elseif ($days_diff == 1) {
                                    echo "1 day ago";
                                } else {
                                    echo "$days_diff days ago";
                                }
                                ?>
                            </p>
                        </div>
                    </div>

                </div>

                <!-- Property -- Descriptions -- Here -->
                <div
                    class="propertyDescription md:mt-[1.25vw] mt-[5.647vw] md:p-[0.833vw] p-[3.765vw] bg-white rounded-[8px] shadow-lg">
                    <h2 class="md:text-[1.25vw] text-[5.647vw] font-semibold md:mb-[0.833vw] mb-[3.765vw]">Description</h2>
                    <p class="text-gray-500 text-[3.765vw] md:text-[0.833vw]"><?= get_the_content(); ?></p>
                </div>

                <!-- Property -- Amenities -- Here -->
                <div
                    class="propertyAmenities md:mt-[1.25vw] mt-[5.647vw] md:p-[0.833vw] p-[3.765vw] bg-white rounded-[8px] shadow-lg">
                    <h2 class="md:text-[1.25vw] text-[5.647vw] font-semibold md:mb-[0.833vw] mb-[3.765vw]">Features &
                        Amenities</h2>
                    <div x-data="{ showAll: false }" class="w-full">

                        <?php foreach ($amenities_data as $index => $group): ?>

                            <!-- Group Wrapper -->
                            <div x-show="showAll || <?= $index ?> === 0" class="flex flex-col md:gap-[0.833vw] gap-[1.687vw]"
                                x-transition>

                                <div
                                    class="bg-[#f3f3f3] rounded-[16px] md:p-[1.25vw] p-[4.706vw] grid grid-cols-12 gap-[2.5vw] md:gap-[1.25vw] mb-[4vw] md:mb-[2vw] items-center">
                                    <!-- Group Title -->
                                    <div
                                        class="text-black font-medium text-[3.294vw] md:text-[0.833vw] font-inter col-span-12 md:col-span-3 h-full border-b md:border-b-0 md:border-r flex justify-center items-center border-black/80">
                                        <?= esc_html($group['title']); ?>
                                    </div>

                                    <!-- Amenities -->
                                    <div
                                        class="grid grid-cols-2 md:grid-cols-3 gap-y-[3vw] md:gap-y-[1vw]  col-span-12 md:col-span-9">
                                        <?php foreach ($group['amenities'] as $amenity): ?>
                                            <div
                                                class="flex items-center justify-center md:justify-normal gap-[2vw] md:gap-[0.625vw]">

                                                <!-- Icon -->
                                                <?php if (!empty($amenity['icon'])): ?>
                                                    <img src="<?= esc_url($amenity['icon']); ?>"
                                                        alt="<?= esc_attr($amenity['title']); ?>"
                                                        class="w-[5vw] h-[5vw] md:w-[1.25vw] md:h-[1.25vw] object-contain">
                                                <?php endif; ?>

                                                <!-- Text -->
                                                <span class="text-black/80 font-medium text-[2.824vw] md:text-[0.729vw] font-inter">
                                                    <?= esc_html($amenity['title']); ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                            </div>

                        <?php endforeach; ?>

                        <!-- View More Button (only if more than 1 group) -->
                        <?php if (count($amenities_data) > 1): ?>
                            <div class="flex justify-end">
                                <button @click="showAll = !showAll" class="flex items-center gap-[1vw] md:gap-[0.417vw] 
                       text-green-600 font-medium 
                       text-[2.824vw] md:text-[0.729vw]">

                                    <span x-text="showAll ? 'View Less' : 'View More'"></span>

                                    <svg :class="showAll ? 'rotate-180' : ''"
                                        class="transition-transform w-[3vw] h-[3vw] md:w-[0.833vw] md:h-[0.833vw]" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </div>
                        <?php endif; ?>

                    </div>

                </div>

            </div>

            <!-- Sidebar -- Here -->
            <div class="col-span-12 lg:col-span-4">
                <!-- Agent -- Information -- Here -->
                <?php if ($author_name && $author_avatar): ?>
                    <div class="bg-white rounded-[16px] p-[3.765vw] md:p-[0.833vw] w-full">
                        <div class="flex justify-between">
                            <div class="agentInfo flex gap-[3.765vw] md:gap-[0.833vw] items-stretch">

                                <div class="agentImg">
                                    <img src="<?= esc_url($author_avatar); ?>" alt="<?= esc_attr($author_name); ?>"
                                        class="w-[26.353vw] h-[23.294vw] md:w-[5.833vw] md:h-[5.156vw] object-cover rounded-[16px]">
                                </div>
                                <div class="agentDetails">
                                    <h4
                                        class="md:text-[1.667vw] text-[7.529vw] font-semibold text-black/90 capitalize leading-[1] md:mb-[0.208vw] mb-[0.941vw]">
                                        <?= esc_html($author_name); ?>
                                    </h4>
                                    <span class="text-[#616161] font-inter capitalize text-[3.765vw] md:text-[0.833vw]">Estate
                                        Agent</span>
                                    <ul class="flex items-center gap-[3.765vw] md:gap-[0.833vw] mt-[0.625vw] md:mt-[0.625vw]">
                                        <li>
                                            <a href="">
                                                <svg class="md:w-[1.25vw] md:h-[1.25vw] w-[5.647vw] h-[5.647vw]"
                                                    viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <g clip-path="url(#clip0_34202_47)">
                                                        <path
                                                            d="M4.38226 0C1.95468 0 0 1.95468 0 4.38226V19.6178C0 22.0453 1.95468 24 4.38226 24H12.6398V14.6175H10.1588V11.2395H12.6398V8.35351C12.6398 6.08611 14.1057 4.00426 17.4825 4.00426C18.8497 4.00426 19.8608 4.13551 19.8608 4.13551L19.7813 7.29002C19.7813 7.29002 18.7501 7.28028 17.625 7.28028C16.4073 7.28028 16.212 7.84135 16.212 8.77279V11.2395H19.878L19.7183 14.6175H16.212V24H19.6177C22.0453 24 24 22.0454 24 19.6178V4.38228C24 1.9547 22.0453 2.4e-05 19.6177 2.4e-05H4.38223L4.38226 0Z"
                                                            fill="#132364" />
                                                    </g>
                                                    <defs>
                                                        <clipPath id="clip0_34202_47">
                                                            <rect width="24" height="24" fill="white" />
                                                        </clipPath>
                                                    </defs>
                                                </svg>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="">
                                                <svg class="md:w-[1.25vw] md:h-[1.25vw] w-[5.647vw] h-[5.647vw]"
                                                    viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <g clip-path="url(#clip0_34202_53)">
                                                        <path
                                                            d="M4.70556 0.00598145C2.12036 0.00598145 0.0045166 2.12177 0.0045166 4.70703V19.2952C0.0045166 21.8804 2.12031 23.9955 4.70556 23.9955H19.2937C21.879 23.9955 23.994 21.8804 23.994 19.2952V4.70703C23.994 2.12182 21.879 0.00598145 19.2937 0.00598145H4.70556ZM5.88795 3.96473C7.1275 3.96473 7.89101 4.77848 7.91458 5.84813C7.91458 6.89417 7.12745 7.73079 5.86397 7.73079H5.84072C4.62476 7.73079 3.83883 6.89422 3.83883 5.84813C3.83883 4.7785 4.64854 3.96473 5.88792 3.96473H5.88795ZM16.5699 8.96417C18.9538 8.96417 20.7408 10.5223 20.7408 13.8706V20.1214H17.118V14.2897C17.118 12.8243 16.5936 11.8245 15.2825 11.8245C14.2816 11.8245 13.685 12.4984 13.4231 13.1493C13.3274 13.3822 13.3039 13.7075 13.3039 14.0333V20.1214H9.68103C9.68103 20.1214 9.72857 10.2427 9.68103 9.21982H13.3046V10.7636C13.7861 10.0208 14.6473 8.96415 16.5699 8.96415V8.96417ZM4.05253 9.22061H7.6754V20.1215H4.05253V9.22061Z"
                                                            fill="#132364" />
                                                    </g>
                                                    <defs>
                                                        <clipPath id="clip0_34202_53">
                                                            <rect width="24" height="24" fill="white" />
                                                        </clipPath>
                                                    </defs>
                                                </svg>

                                            </a>
                                        </li>
                                        <li>
                                            <a href="">
                                                <svg class="md:w-[1.25vw] md:h-[1.25vw] w-[5.647vw] h-[5.647vw]"
                                                    viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                        d="M5.41671 1.08337C4.26744 1.08337 3.16524 1.53992 2.35258 2.35258C1.53992 3.16524 1.08337 4.26744 1.08337 5.41671V20.5834C1.08337 21.7326 1.53992 22.8348 2.35258 23.6475C3.16524 24.4602 4.26744 24.9167 5.41671 24.9167H20.5834C21.7326 24.9167 22.8348 24.4602 23.6475 23.6475C24.4602 22.8348 24.9167 21.7326 24.9167 20.5834V5.41671C24.9167 4.26744 24.4602 3.16524 23.6475 2.35258C22.8348 1.53992 21.7326 1.08337 20.5834 1.08337H5.41671ZM5.05487 4.87504C4.93628 4.91911 4.82962 4.99027 4.74339 5.08284C4.65715 5.17542 4.59373 5.28685 4.55817 5.40827C4.52261 5.52968 4.51589 5.65772 4.53855 5.78219C4.56122 5.90666 4.61263 6.02412 4.68871 6.12521L10.7705 14.196L4.36262 21.0698L4.31496 21.125H6.53254L11.765 15.5145L15.7864 20.8531C15.8797 20.9767 16.0063 21.071 16.1515 21.125H20.942C21.0604 21.0807 21.1668 21.0094 21.2528 20.9167C21.3388 20.8241 21.402 20.7126 21.4373 20.5912C21.4727 20.4698 21.4792 20.3418 21.4564 20.2175C21.4336 20.0931 21.3821 19.9758 21.306 19.8749L15.2241 11.804L21.6851 4.87504H19.4643L14.2318 10.4867L10.2083 5.14804C10.1151 5.02407 9.98846 4.92934 9.84321 4.87504H5.05487ZM16.8415 19.552L6.96696 6.44804H9.15421L19.0277 19.551L16.8415 19.552Z"
                                                        fill="#132364" />
                                                </svg>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="">
                                                <svg class="md:w-[1.25vw] md:h-[1.25vw] w-[5.647vw] h-[5.647vw]"
                                                    viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                        d="M6.78002 1.57397C5.39912 1.57397 4.07475 2.1224 3.09812 3.09866C2.12148 4.07491 1.57255 5.39907 1.57202 6.77997V20.668C1.57202 22.0492 2.12072 23.3739 3.09741 24.3506C4.0741 25.3273 5.39877 25.876 6.78002 25.876H20.668C22.0489 25.8754 23.3731 25.3265 24.3493 24.3499C25.3256 23.3732 25.874 22.0489 25.874 20.668V6.77997C25.8735 5.39942 25.3248 4.07556 24.3486 3.09936C23.3724 2.12316 22.0486 1.5745 20.668 1.57397H6.78002ZM22.166 6.78797C22.166 7.1858 22.008 7.56733 21.7267 7.84863C21.4454 8.12994 21.0638 8.28797 20.666 8.28797C20.2682 8.28797 19.8867 8.12994 19.6054 7.84863C19.3241 7.56733 19.166 7.1858 19.166 6.78797C19.166 6.39015 19.3241 6.00862 19.6054 5.72731C19.8867 5.44601 20.2682 5.28797 20.666 5.28797C21.0638 5.28797 21.4454 5.44601 21.7267 5.72731C22.008 6.00862 22.166 6.39015 22.166 6.78797ZM13.726 9.56397C12.6227 9.56397 11.5646 10.0023 10.7845 10.7824C10.0043 11.5626 9.56602 12.6207 9.56602 13.724C9.56602 14.8273 10.0043 15.8854 10.7845 16.6655C11.5646 17.4457 12.6227 17.884 13.726 17.884C14.8293 17.884 15.8874 17.4457 16.6676 16.6655C17.4477 15.8854 17.886 14.8273 17.886 13.724C17.886 12.6207 17.4477 11.5626 16.6676 10.7824C15.8874 10.0023 14.8293 9.56397 13.726 9.56397ZM7.56402 13.724C7.56402 12.0902 8.21302 10.5234 9.36824 9.3682C10.5235 8.21297 12.0903 7.56397 13.724 7.56397C15.3578 7.56397 16.9246 8.21297 18.0798 9.3682C19.235 10.5234 19.884 12.0902 19.884 13.724C19.884 15.3577 19.235 16.9245 18.0798 18.0798C16.9246 19.235 15.3578 19.884 13.724 19.884C12.0903 19.884 10.5235 19.235 9.36824 18.0798C8.21302 16.9245 7.56402 15.3577 7.56402 13.724Z"
                                                        fill="#132364" />
                                                </svg>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="hidden md:block">
                                <ul>
                                    <li class="mb-[3.294vw] md:mb-[0.729vw]">
                                        <a href="" class="flex gap-[3.765vw] md:gap-[0.833vw] items-center">
                                            <svg class="w-[4.235vw] h-[4.235vw] md:w-[0.938vw] md:h-[0.938vw]"
                                                viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M4.965 8.0925C6.045 10.215 7.785 11.955 9.9075 13.035L11.5575 11.385C11.7675 11.175 12.06 11.115 12.3225 11.1975C13.1625 11.475 14.0625 11.625 15 11.625C15.1989 11.625 15.3897 11.704 15.5303 11.8447C15.671 11.9853 15.75 12.1761 15.75 12.375V15C15.75 15.1989 15.671 15.3897 15.5303 15.5303C15.3897 15.671 15.1989 15.75 15 15.75C11.6185 15.75 8.37548 14.4067 5.98439 12.0156C3.5933 9.62452 2.25 6.38151 2.25 3C2.25 2.80109 2.32902 2.61032 2.46967 2.46967C2.61032 2.32902 2.80109 2.25 3 2.25H5.625C5.82391 2.25 6.01468 2.32902 6.15533 2.46967C6.29598 2.61032 6.375 2.80109 6.375 3C6.375 3.9375 6.525 4.8375 6.8025 5.6775C6.885 5.94 6.825 6.2325 6.615 6.4425L4.965 8.0925Z"
                                                    fill="#132364" />
                                            </svg>
                                            <span
                                                class="text-black/90 font-medium font-inter text-[3.294vw] md:text-[0.729vw]">(305)
                                                555-5555</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="" class="flex gap-[3.765vw] md:gap-[0.833vw] items-center">
                                            <svg class="w-[4.235vw] h-[4.235vw] md:w-[0.938vw] md:h-[0.938vw]"
                                                viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <mask id="mask0_34295_3723" style="mask-type:luminance"
                                                    maskUnits="userSpaceOnUse" x="1" y="1" width="16" height="16">
                                                    <path d="M1.875 1.875H16.125V16.125H1.875V1.875Z" fill="white" />
                                                </mask>
                                                <g mask="url(#mask0_34295_3723)">
                                                    <path
                                                        d="M10.3425 1.98001L9.915 1.92001C8.6302 1.73451 7.31913 1.91803 6.13465 2.44919C4.95017 2.98035 3.94107 3.83725 3.225 4.92001C2.46312 5.955 2.00894 7.18407 1.91473 8.46578C1.82053 9.74749 2.09013 11.0298 2.6925 12.165C2.75415 12.2788 2.79253 12.4037 2.80541 12.5325C2.81828 12.6612 2.8054 12.7913 2.7675 12.915C2.46 13.9725 2.175 15.0375 1.875 16.155L2.25 16.0425C3.2625 15.7725 4.275 15.5025 5.2875 15.255C5.50121 15.2106 5.72334 15.2315 5.925 15.315C6.8334 15.7584 7.82612 16.0023 8.83654 16.0304C9.84696 16.0585 10.8517 15.8701 11.7833 15.4779C12.715 15.0858 13.5519 14.4989 14.2381 13.7566C14.9242 13.0143 15.4436 12.1339 15.7615 11.1744C16.0794 10.2148 16.1883 9.19842 16.081 8.19332C15.9737 7.18822 15.6527 6.21769 15.1395 5.34688C14.6262 4.47607 13.9326 3.72511 13.1053 3.14441C12.2779 2.5637 11.3359 2.16669 10.3425 1.98001ZM12.2325 11.82C11.9599 12.0641 11.6275 12.2315 11.2692 12.3053C10.9108 12.3791 10.5393 12.3565 10.1925 12.24C8.62121 11.7969 7.25794 10.811 6.345 9.45751C5.99638 8.97871 5.71619 8.45366 5.5125 7.89751C5.40214 7.57484 5.38224 7.22812 5.45496 6.89494C5.52767 6.56176 5.69023 6.25486 5.925 6.00751C6.03929 5.86165 6.19486 5.75359 6.37144 5.6974C6.54802 5.64122 6.73744 5.63951 6.915 5.69251C7.065 5.73001 7.17 5.94751 7.305 6.11251C7.415 6.42251 7.5425 6.72501 7.6875 7.02001C7.79732 7.17039 7.84318 7.35811 7.81508 7.54219C7.78698 7.72627 7.68719 7.89175 7.5375 8.00251C7.2 8.30251 7.2525 8.55001 7.4925 8.88751C8.02279 9.65215 8.75499 10.2545 9.6075 10.6275C9.8475 10.7325 10.0275 10.755 10.185 10.5075C10.2525 10.41 10.3425 10.3275 10.4175 10.2375C10.8525 9.69001 10.7175 9.69751 11.4075 9.99751C11.6275 10.09 11.84 10.1975 12.045 10.32C12.2475 10.44 12.555 10.5675 12.6 10.7475C12.6433 10.9428 12.6319 11.1462 12.5671 11.3354C12.5022 11.5247 12.3865 11.6923 12.2325 11.82Z"
                                                        fill="#132364" />
                                                </g>
                                            </svg>
                                            <span
                                                class="text-black/90 font-medium font-inter text-[3.294vw] md:text-[0.729vw]">(305)
                                                555-5555</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div x-data="{ openShare:false }"
                            class="mt-[4.706vw] md:mt-[1.25vw] space-y-[3.765vw] md:space-y-[0.833vw]">

                            <!-- Action Buttons -->
                            <div class="flex gap-[3.765vw] md:gap-[0.833vw]">

                                <!-- WhatsApp -->
                                <a href="https://wa.me/3055555555" target="_blank" class="flex-1 flex items-center justify-center gap-[2.353vw] md:gap-[0.521vw]
                                    h-[12.706vw] md:h-[3.125vw]
                                    rounded-[3.765vw] md:rounded-[0.833vw]
                                    bg-[#25D366] text-white font-medium
                                    text-[3.294vw] md:text-[0.833vw]">
                                    <svg class="w-[4.706vw] h-[4.706vw] md:w-[1.042vw] md:h-[1.042vw]" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <path
                                            d="M20.52 3.48A11.91 11.91 0 0012.01 0C5.39 0 .01 5.38.01 12c0 2.12.55 4.19 1.6 6.02L0 24l6.15-1.61A11.94 11.94 0 0012 24c6.62 0 12-5.38 12-12 0-3.2-1.25-6.2-3.48-8.52z" />
                                    </svg>
                                    WhatsApp
                                </a>

                                <!-- Call -->
                                <a href="tel:3055555555" class="flex-1 flex items-center justify-center gap-[2.353vw] md:gap-[0.521vw]
                                    h-[12.706vw] md:h-[3.125vw]
                                    rounded-[3.765vw] md:rounded-[0.833vw]
                                    bg-[#132364] text-white font-medium
                                    text-[3.294vw] md:text-[0.833vw]">
                                    <svg class="w-[4.706vw] h-[4.706vw] md:w-[1.042vw] md:h-[1.042vw]" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <path
                                            d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 011 1V21a1 1 0 01-1 1C10.85 22 2 13.15 2 2a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.46.57 3.59a1 1 0 01-.25 1.01l-2.2 2.19z" />
                                    </svg>
                                    Call
                                </a>

                            </div>

                            <!-- Share Toggle -->
                            <button @click="openShare=!openShare" class="w-full flex items-center justify-between
                                bg-white rounded-[3.765vw] md:rounded-[0.833vw]
                                p-[3.765vw] md:p-[0.833vw]">

                                <span class="font-medium text-black/90 text-[3.294vw] md:text-[0.729vw]">
                                    Share this page
                                </span>

                                <svg :class="openShare ? 'rotate-180' : ''"
                                    class="transition-transform w-[4.706vw] h-[4.706vw] md:w-[1.042vw] md:h-[1.042vw]"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Share Icons -->
                            <div x-show="openShare" x-transition class="flex items-center gap-[3.765vw] md:gap-[0.833vw]">

                                <!-- WhatsApp -->
                                <a target="_blank" href="https://wa.me/?text=<?= urlencode(get_permalink()); ?>" class="flex items-center justify-center
                                    w-[10.588vw] h-[10.588vw] md:w-[2.5vw] md:h-[2.5vw]
                                    rounded-full bg-[#25D366] text-white
                                    text-[3.294vw] md:text-[0.729vw]">
                                    WA
                                </a>

                                <!-- Facebook -->
                                <a target="_blank"
                                    href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(get_permalink()); ?>"
                                    class="flex items-center justify-center
                                    w-[10.588vw] h-[10.588vw] md:w-[2.5vw] md:h-[2.5vw]
                                    rounded-full bg-[#1877F2] text-white">
                                    FB
                                </a>

                                <!-- X -->
                                <a target="_blank"
                                    href="https://twitter.com/intent/tweet?url=<?= urlencode(get_permalink()); ?>" class="flex items-center justify-center
                                    w-[10.588vw] h-[10.588vw] md:w-[2.5vw] md:h-[2.5vw]
                                    rounded-full bg-black text-white">
                                    X
                                </a>

                                <!-- Instagram (copy link) -->
                                <button @click="navigator.clipboard.writeText('<?= get_permalink(); ?>')" class="flex items-center justify-center
                                    w-[10.588vw] h-[10.588vw] md:w-[2.5vw] md:h-[2.5vw]
                                    rounded-full bg-gradient-to-tr from-pink-500 to-yellow-500 text-white">
                                    IG
                                </button>

                                <!-- More -->
                                <button
                                    @click="navigator.share ? navigator.share({ url:'<?= get_permalink(); ?>' }) : navigator.clipboard.writeText('<?= get_permalink(); ?>')"
                                    class="flex items-center justify-center
                                    w-[10.588vw] h-[10.588vw] md:w-[2.5vw] md:h-[2.5vw]
                                    rounded-full bg-gray-200 text-black">
                                    +
                                </button>

                            </div>

                        </div>

                    </div>
                <?php endif; ?>

                <div class="mt-[3.765vw] md:mt-[0.833vw] overflow-hidden rounded-[16px]">
                    <div
                        id="property-map"
                        class="w-full h-[122.353vw] md:h-[27.083vw] rounded-xl overflow-hidden"
                    ></div>
                </div>
            </div>
    </section>

<?php endwhile; ?>


<script>
document.addEventListener('DOMContentLoaded', function () {

    if (!window.google || !window.google.maps) {
        console.log('Google Maps not loaded');
        return;
    }

    const lat = <?= esc_js($lat); ?>;
    const lng = <?= esc_js($lng); ?>;

    const mapEl = document.getElementById('property-map');
    if (!mapEl) return;

    const map = new google.maps.Map(mapEl, {
        zoom: 15,
        center: { lat: parseFloat(lat), lng: parseFloat(lng) },
        mapTypeControl: false,
        zoomControl: true,
        mapId: 'c484b19c4f8c16ebb3dcf3d1'
    });

    const customIcon = {
        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
            <svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 60 60">
                <g>
                    <path fill="#95a5a5" d="M33 32.72v21.39a3.016 3.016 0 0 1-.43 1.55l-1.71 2.85a1 1 0 0 1-1.72 0l-1.71-2.85a3.016 3.016 0 0 1-.43-1.55V32.72z" opacity="1" data-original="#95a5a5"></path>
                    <path fill="#c03a2b" d="M46 17a15.98 15.98 0 1 1-6.44-12.84A16 16 0 0 1 46 17z" opacity="1" data-original="#c03a2b" class=""></path>
                    <path fill="#e64c3c" d="M40 8a17 17 0 0 1-17 17 16.853 16.853 0 0 1-7.79-1.89A16.009 16.009 0 0 1 39.56 4.16 16.744 16.744 0 0 1 40 8z" opacity="1" data-original="#e64c3c" class=""></path>
                </g>
            </svg>
        `),
        scaledSize: new google.maps.Size(40, 40),
        anchor: new google.maps.Point(20, 40)
    };

    new google.maps.Marker({
        position: { lat: parseFloat(lat), lng: parseFloat(lng) },
        map: map,
        title: 'Property Location',
        icon: customIcon
    });

});
</script>


<?php get_footer(); ?>