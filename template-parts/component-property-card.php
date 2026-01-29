<div class="bg-white rounded-xl flex flex-col shadow-sm hover:shadow-xl border border-slate-200 overflow-hidden transition-all duration-300 hover:-translate-y-1 h-full"
    :class="{ '!flex-row !items-center': viewType === 'list' }">
    <!-- Image Container -->
    <div class="relative h-[50.059vw] md:h-[234px] bg-slate-200 overflow-hidden group" :class="{ '!w-[30%]': viewType === 'list' }">


        <div x-data="{
        images: [property.image, ...(property.gallery || [])].filter(Boolean),
        index: 0,
        hover(e) {
            const rect = e.currentTarget.getBoundingClientRect()
            const x = e.clientX - rect.left
            const percent = x / rect.width

            // total images ke hisaab se index calculate
            const newIndex = Math.floor(percent * this.images.length)

            if (newIndex !== this.index) {
                this.index = newIndex
            }
        }
    }" @mousemove="hover($event)" class="relative overflow-hidden w-full h-full group">
            <template x-for="(image, i) in images" :key="i">
                <img x-show="index === i" x-transition:enter="transition transform duration-500"
                    x-transition:enter-start="translate-x-full opacity-0"
                    x-transition:enter-end="translate-x-0 opacity-100"
                    x-transition:leave="transition transform duration-500"
                    x-transition:leave-start="translate-x-0 opacity-100"
                    x-transition:leave-end="-translate-x-full opacity-0"
                    :src="typeof image === 'string' ? image : image.media_url" :alt="property.title"
                    class="absolute inset-0 w-full h-full object-cover">
            </template>
        </div>


        <!-- Featured Badge -->
        <div x-show="property.featured"
            class="absolute top-4 right-4 bg-gradient-to-r from-red-700 to-red-500 text-white px-4 py-1.5 rounded-full text-[3.294vw] md:text-[0.729vw] font-bold shadow-lg flex gap-1 items-center font-inter">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M6 1L8.5 4.5L12 5L8.5 7.5L6 11L3.5 7.5L0 5L3.5 4.5L6 1Z" fill="white" />
            </svg>
            Super Hot
        </div>

        <div x-show="property.gallery.length > 1"
            class="absolute top-4 left-4 bg-black/50 text-white px-3 py-1.5 rounded-full text-sm font-bold shadow-lg flex gap-2 items-center font-inter">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8 6C6.916 6 6 6.916 6 8C6 9.084 6.916 10 8 10C9.084 10 10 9.084 10 8C10 6.916 9.084 6 8 6Z"
                    fill="white" />
                <path
                    d="M13.334 3.33337H11.61L9.80532 1.52871C9.68032 1.40367 9.51078 1.33341 9.33398 1.33337H6.66732C6.49052 1.33341 6.32098 1.40367 6.19598 1.52871L4.39132 3.33337H2.66732C1.93198 3.33337 1.33398 3.93137 1.33398 4.66671V12C1.33398 12.7354 1.93198 13.3334 2.66732 13.3334H13.334C14.0693 13.3334 14.6673 12.7354 14.6673 12V4.66671C14.6673 3.93137 14.0693 3.33337 13.334 3.33337ZM8.00065 11.3334C6.19398 11.3334 4.66732 9.80671 4.66732 8.00004C4.66732 6.19337 6.19398 4.66671 8.00065 4.66671C9.80732 4.66671 11.334 6.19337 11.334 8.00004C11.334 9.80671 9.80732 11.3334 8.00065 11.3334Z"
                    fill="white" />
            </svg>
            <span x-text="property.gallery.length" class="font-inter text-xs"></span>
        </div>

        <!-- Agent Profile Link -->
        <a :href="property.author_profile_url"
            class="absolute bottom-4 left-4 bg-[var(--secondary-color)] flex gap-4 items-center rounded-lg w-fit py-[4px] px-[8px] shadow-md hover:shadow-lg transition-all duration-300">
            <img :src="property.author_avatar" :alt="property.author_name"
                class="w-[30px] h-[30px] rounded-full object-cover">
            <div class="flex flex-col">
                <h4 class="text-[var(--primary-color)] font-semibold text-[12px] font-inter capitalize mb-0"
                    x-text="property.author_name">
                </h4>
                <span class="text-[10px] font-inter">Estate Agent</span>
            </div>
        </a>
    </div>

    <!-- Content -->
    <div class="px-[3.294vw] pt-[2.824vw] pb-[5.647vw] md:px-[1.458vw] md:pt-[0.625vw] md:pb-[1.25vw] grow flex flex-col justify-between">
        <div>
            <div class="flex justify-between items-start mb-[14px]">
                <h3
                    class="md:text-[1.389vw] text-[4.706vw] font-medium leading-[1em] text-slate-900 mb-2 line-clamp-2 hover:text-[var(--primary-color)] transition w-[70%]">
                    <a :href="property.permalink" x-text="property.title"></a>
                </h3>
                <p class="md:text-[1.042vw] text-[3.765vw] font-medium font-inter w-[25%] text-right"
                    x-text="`$${property.price >= 100000 ? (property.price / 1000).toFixed(property.price % 1000 === 0 ? 0 : 1) + 'K' : property.price.toLocaleString()}`">
                </p>
            </div>
        </div>

        <!-- Details -->
        <div class="flex gap-3 flex-wrap">
            <div class="w-full flex gap-2 items-center" x-show="property.address">
                <svg class="md:w-[0.833vw] md:h-[0.833vw] w-[3.765vw] h-[3.765vw]" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M9.66732 6.00004C9.66732 6.44207 9.49172 6.86599 9.17916 7.17855C8.8666 7.49111 8.44268 7.66671 8.00065 7.66671C7.55862 7.66671 7.1347 7.49111 6.82214 7.17855C6.50958 6.86599 6.33398 6.44207 6.33398 6.00004C6.33398 5.55801 6.50958 5.13409 6.82214 4.82153C7.1347 4.50897 7.55862 4.33337 8.00065 4.33337C8.44268 4.33337 8.8666 4.50897 9.17916 4.82153C9.49172 5.13409 9.66732 5.55801 9.66732 6.00004Z"
                        stroke="#132364" />
                    <path
                        d="M8.83897 11.6627C8.61379 11.8794 8.31345 12.0004 8.00097 12.0004C7.68849 12.0004 7.38815 11.8794 7.16297 11.6627C5.10364 9.66737 2.3443 7.43871 3.68964 4.20271C4.4183 2.45271 6.16497 1.33337 8.00097 1.33337C9.83697 1.33337 11.5843 2.45337 12.3123 4.20271C13.6563 7.43404 10.9036 9.67404 8.83897 11.6627Z"
                        stroke="#132364" />
                    <path d="M12 13.3334C12 14.07 10.2093 14.6667 8 14.6667C5.79067 14.6667 4 14.07 4 13.3334"
                        stroke="#132364" stroke-linecap="round" />
                </svg>
                <p class="text-[var(--primary-color)] text-[2.824vw] md:text-[0.833vw] line-clamp-1 font-inter" x-text="property.address">
                </p>
            </div>
            <div class="flex items-center gap-2 px-[2.824vw] py-[1.412vw] md:px-[0.625vw] md:py-[7px] bg-gray-100 rounded-lg">
                <svg class="md:h-[1.042vw] md:w-[1.042vw] w-[4.706vw] h-[4.706vw]" viewBox="0 0 16 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M11.5 2.96809e-08C12.1347 -9.77163e-05 12.7457 0.241233 13.209 0.67504C13.6724 1.10885 13.9534 1.70265 13.995 2.336L14 2.5V5.05C14.5359 5.15943 15.0211 5.4416 15.3813 5.85325C15.7415 6.2649 15.9567 6.7833 15.994 7.329L16 7.5V13.5C16.0002 13.6249 15.9537 13.7455 15.8695 13.8378C15.7853 13.9301 15.6696 13.9876 15.5452 13.9989C15.4207 14.0102 15.2966 13.9745 15.1972 13.8988C15.0977 13.8231 15.0303 13.7129 15.008 13.59L15 13.5V11H1V13.5C1.00023 13.6249 0.953671 13.7455 0.869492 13.8378C0.785312 13.9301 0.669613 13.9876 0.545178 13.9989C0.420743 14.0102 0.29659 13.9745 0.197168 13.8988C0.0977463 13.8231 0.0302602 13.7129 0.00799995 13.59L5.21142e-08 13.5V7.5C-0.000117575 6.92367 0.198892 6.36501 0.563347 5.91855C0.927802 5.47209 1.43532 5.16527 2 5.05V2.5C1.9999 1.86528 2.24123 1.25429 2.67504 0.790955C3.10885 0.327621 3.70265 0.0466377 4.336 0.00500014L4.5 2.96809e-08H11.5ZM13.5 6H2.5C2.12727 5.99999 1.7679 6.13876 1.49189 6.38925C1.21589 6.63974 1.04303 6.98402 1.007 7.355L1 7.5V10H15V7.5C15 7.12727 14.8612 6.7679 14.6108 6.49189C14.3603 6.21589 14.016 6.04303 13.645 6.007L13.5 6ZM11.5 1H4.5C4.12712 1.00002 3.76761 1.13892 3.49158 1.38962C3.21555 1.64032 3.0428 1.98484 3.007 2.356L3 2.5V5H4V4.5C4 4.36739 4.05268 4.24021 4.14645 4.14645C4.24021 4.05268 4.36739 4 4.5 4H7C7.13261 4 7.25979 4.05268 7.35355 4.14645C7.44732 4.24021 7.5 4.36739 7.5 4.5V5H8.5V4.5C8.5 4.36739 8.55268 4.24021 8.64645 4.14645C8.74021 4.05268 8.86739 4 9 4H11.5C11.6326 4 11.7598 4.05268 11.8536 4.14645C11.9473 4.24021 12 4.36739 12 4.5V5H13V2.5C13 2.12727 12.8612 1.7679 12.6108 1.49189C12.3603 1.21589 12.016 1.04303 11.645 1.007L11.5 1Z"
                        fill="#1A1A1A" />
                </svg>
                <span class="text-[3.059vw] md:text-[0.625vw] font-inter font-medium text-black"
                    x-text="`${property.bedrooms} Beds`"></span>
            </div>
            <div class="flex items-center gap-2 px-[2.824vw] py-[1.412vw] md:px-[0.625vw] md:py-[7px] bg-gray-100 rounded-lg">
                <svg class="md:h-[1.042vw] md:w-[1.042vw] w-[4.706vw] h-[4.706vw]" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_34125_1059)">
                        <path
                            d="M18.125 10.9375H3.125V3.90621C3.12428 3.64142 3.17609 3.37911 3.27742 3.13448C3.37875 2.88984 3.5276 2.66774 3.71535 2.48101L3.73098 2.46539C4.02522 2.17157 4.40299 1.97576 4.8127 1.90472C5.2224 1.83367 5.64405 1.89085 6.02004 2.06844C5.66495 2.65882 5.51736 3.35095 5.60072 4.03483C5.68408 4.71872 5.99361 5.35512 6.48012 5.84293L6.90789 6.2707L6.12051 7.05812L7.00434 7.94195L7.79172 7.15457L12.1546 2.7918L12.942 2.00441L12.0581 1.12054L11.2707 1.90793L10.8429 1.48015C10.3307 0.969424 9.6556 0.654591 8.93515 0.590519C8.2147 0.526446 7.4946 0.717197 6.90035 1.12953C6.27372 0.733792 5.53112 0.56295 4.79455 0.645067C4.05798 0.727184 3.37124 1.05738 2.84715 1.5814L2.83152 1.59703C2.52731 1.89956 2.28613 2.25943 2.12195 2.6558C1.95776 3.05218 1.87382 3.47718 1.875 3.90621V10.9375H0.625V12.1875H1.875V13.3867C1.87501 13.4874 1.89126 13.5875 1.92312 13.6831L3.08594 17.1714C3.148 17.3582 3.26732 17.5206 3.42696 17.6357C3.5866 17.7508 3.77844 17.8126 3.97523 17.8125H4.47914L4.02344 19.375H5.32551L5.78125 17.8125H13.9102L14.3789 19.375H15.6836L15.2148 17.8125H16.0246C16.2214 17.8126 16.4133 17.7508 16.573 17.6357C16.7326 17.5207 16.852 17.3582 16.9141 17.1714L18.0768 13.6831C18.1087 13.5875 18.125 13.4874 18.125 13.3867V12.1875H19.375V10.9375H18.125ZM7.36406 2.36402C7.70844 2.0204 8.17506 1.82741 8.66154 1.82741C9.14803 1.82741 9.61465 2.0204 9.95902 2.36402L10.3867 2.7918L7.79184 5.38668L7.36406 4.95898C7.02046 4.6146 6.82749 4.14798 6.82749 3.6615C6.82749 3.17502 7.02046 2.7084 7.36406 2.36402ZM16.875 13.3359L15.7995 16.5625H4.20047L3.125 13.3359V12.1875H16.875V13.3359Z"
                            fill="#1A1A1A" />
                    </g>
                    <defs>
                        <clipPath id="clip0_34125_1059">
                            <rect width="20" height="20" fill="white" />
                        </clipPath>
                    </defs>
                </svg>
                <span class="text-[3.059vw] md:text-[0.625vw] font-inter font-medium text-black"
                    x-text="`${property.bathrooms} Baths`"></span>
            </div>
            <div class="flex items-center gap-2 px-[2.824vw] py-[1.412vw] md:px-[0.625vw] md:py-[7px] bg-gray-100 rounded-lg">
                <svg class="md:h-[1.042vw] md:w-[1.042vw] w-[4.706vw] h-[4.706vw]" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_34125_1063)">
                        <path
                            d="M7.5 9.9983C7.5 10.6613 7.76339 11.2972 8.23223 11.7661C8.70107 12.2349 9.33696 12.4983 10 12.4983C10.663 12.4983 11.2989 12.2349 11.7678 11.7661C12.2366 11.2972 12.5 10.6613 12.5 9.9983C12.5 9.33526 12.2366 8.69938 11.7678 8.23054C11.2989 7.76169 10.663 7.4983 10 7.4983C9.33696 7.4983 8.70107 7.76169 8.23223 8.23054C7.76339 8.69938 7.5 9.33526 7.5 9.9983ZM0.854996 9.47414C0.782429 9.54072 0.724491 9.62166 0.684857 9.71182C0.645222 9.80199 0.624756 9.8994 0.624756 9.99789C0.624756 10.0964 0.645222 10.1938 0.684857 10.2839C0.724491 10.3741 0.782429 10.455 0.854996 10.5216L3.45333 12.9033C3.53188 12.9753 3.62967 13.023 3.7348 13.0404C3.83993 13.0579 3.94787 13.0445 4.0455 13.0018C4.14313 12.959 4.22625 12.8889 4.28474 12.7998C4.34324 12.7107 4.37459 12.6065 4.375 12.5V7.49997C4.37491 7.39326 4.34379 7.28887 4.28542 7.19954C4.22705 7.11021 4.14395 7.03979 4.04626 6.99686C3.94856 6.95393 3.84049 6.94034 3.73521 6.95777C3.62993 6.97519 3.53199 7.02286 3.45333 7.09497L0.854996 9.47414ZM19.1442 10.5216C19.2171 10.4553 19.2753 10.3744 19.3152 10.2842C19.355 10.194 19.3756 10.0965 19.3756 9.99789C19.3756 9.89929 19.355 9.80177 19.3152 9.71158C19.2753 9.62139 19.2171 9.54052 19.1442 9.47414L16.5467 7.09247C16.4678 7.02021 16.3697 6.9725 16.2642 6.95516C16.1587 6.93782 16.0504 6.95162 15.9526 6.99486C15.8548 7.03809 15.7717 7.1089 15.7135 7.19861C15.6554 7.28832 15.6246 7.39305 15.625 7.49997V12.5C15.6249 12.6067 15.6558 12.7111 15.7141 12.8005C15.7723 12.8899 15.8554 12.9604 15.9531 13.0034C16.0507 13.0463 16.1588 13.0599 16.2641 13.0424C16.3694 13.025 16.4673 12.9772 16.5458 12.905L19.1442 10.5216ZM9.47666 19.1425C9.54304 19.2152 9.62387 19.2734 9.71398 19.3131C9.80409 19.3529 9.9015 19.3735 10 19.3735C10.0985 19.3735 10.1959 19.3529 10.286 19.3131C10.3761 19.2734 10.457 19.2152 10.5233 19.1425L12.905 16.5441C12.9772 16.4656 13.025 16.3677 13.0425 16.2624C13.0599 16.1571 13.0464 16.0491 13.0034 15.9514C12.9604 15.8537 12.89 15.7706 12.8006 15.7124C12.7111 15.6541 12.6067 15.6232 12.5 15.6233H7.5C7.39337 15.6234 7.28907 15.6544 7.19978 15.7127C7.11049 15.771 7.04005 15.8539 6.99705 15.9515C6.95405 16.0491 6.94034 16.157 6.95758 16.2622C6.97482 16.3675 7.02227 16.4654 7.09416 16.5441L9.47666 19.1425ZM10.5233 0.853303C10.457 0.780531 10.3761 0.722402 10.286 0.682629C10.1959 0.642857 10.0985 0.622314 10 0.622314C9.9015 0.622314 9.80409 0.642857 9.71398 0.682629C9.62387 0.722402 9.54304 0.780531 9.47666 0.853303L7.095 3.45164C7.02288 3.5303 6.97521 3.62823 6.95779 3.73351C6.94037 3.83879 6.95395 3.94687 6.99688 4.04456C7.03981 4.14226 7.11024 4.22536 7.19957 4.28373C7.2889 4.3421 7.39328 4.37322 7.5 4.3733H12.5C12.6067 4.37322 12.7111 4.3421 12.8004 4.28373C12.8898 4.22536 12.9602 4.14226 13.0031 4.04456C13.046 3.94687 13.0596 3.83879 13.0422 3.73351C13.0248 3.62823 12.9771 3.5303 12.905 3.45164L10.5233 0.853303Z"
                            stroke="#1A1A1A" stroke-linecap="round" stroke-linejoin="round" />
                    </g>
                    <defs>
                        <clipPath id="clip0_34125_1063">
                            <rect width="20" height="20" fill="white" />
                        </clipPath>
                    </defs>
                </svg>
                <span class="text-[3.059vw] md:text-[0.625vw] font-inter font-medium text-black" x-text="`${property.area} sq.ft`"></span>
            </div>
        </div>
        <div x-show="!loading && viewType === 'list'" class="flex justify-end">
            <a :href="property.link"
                class="block mt-4 bg-[var(--primary-color)] text-white font-inter font-medium hover:text-blue-800 px-4 py-2 rounded-lg">View
                Details</a>
        </div>
    </div>
</div>