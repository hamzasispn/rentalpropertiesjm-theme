<?php
/**
 * 404 Error Page
 */
get_header();
?>

<main class="w-[90%] mx-auto flex flex-col items-center justify-center text-center md:py-[5vw] py-[20vw]">

    <!-- Illustration -->
    <div class="relative mb-[4vw] md:mb-[2.5vw]">
        <svg class="md:w-[18vw] md:h-[18vw] w-[55vw] h-[55vw] mx-auto" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- House outline -->
            <path d="M100 30L160 80V170H40V80L100 30Z" fill="color-mix(in srgb, var(--primary-color) 8%, transparent)" stroke="var(--primary-color)" stroke-width="3"/>
            <!-- Door -->
            <rect x="83" y="120" width="34" height="50" rx="4" fill="color-mix(in srgb, var(--primary-color) 15%, transparent)" stroke="var(--primary-color)" stroke-width="2"/>
            <!-- Window left -->
            <rect x="52" y="100" width="30" height="28" rx="3" fill="color-mix(in srgb, var(--primary-color) 12%, transparent)" stroke="var(--primary-color)" stroke-width="2"/>
            <line x1="67" y1="100" x2="67" y2="128" stroke="var(--primary-color)" stroke-width="1.5"/>
            <line x1="52" y1="114" x2="82" y2="114" stroke="var(--primary-color)" stroke-width="1.5"/>
            <!-- Window right -->
            <rect x="118" y="100" width="30" height="28" rx="3" fill="color-mix(in srgb, var(--primary-color) 12%, transparent)" stroke="var(--primary-color)" stroke-width="2"/>
            <line x1="133" y1="100" x2="133" y2="128" stroke="var(--primary-color)" stroke-width="1.5"/>
            <line x1="118" y1="114" x2="148" y2="114" stroke="var(--primary-color)" stroke-width="1.5"/>
            <!-- Question mark -->
            <text x="100" y="82" text-anchor="middle" font-size="28" font-weight="bold" fill="var(--primary-color)" font-family="Inter, sans-serif">?</text>
            <!-- Ground line -->
            <line x1="20" y1="170" x2="180" y2="170" stroke="var(--primary-color)" stroke-width="2" stroke-linecap="round"/>
        </svg>

        <!-- 404 badge -->
        <div class="absolute -top-2 -right-2 md:-top-[0.5vw] md:-right-[0.5vw] bg-white border-2 rounded-full md:w-[5vw] md:h-[5vw] w-[16vw] h-[16vw] flex items-center justify-center shadow-lg" style="border-color: var(--primary-color);">
            <span class="font-bold font-inter md:text-[1.2vw] text-[4vw] leading-none" style="color: var(--primary-color);">404</span>
        </div>
    </div>

    <!-- Heading -->
    <h1 class="text-[#1A1A1A] text-[8.471vw] md:text-[3vw] font-bold leading-[1.1] mb-[3vw] md:mb-[1vw]">
        This page has moved out
    </h1>

    <!-- Subtext -->
    <p class="text-gray-500 font-inter text-[3.765vw] md:text-[1.042vw] max-w-md mx-auto mb-[8vw] md:mb-[2.5vw] leading-relaxed">
        Looks like the page you are looking for no longer exists or has been moved to a new address.
        Let&rsquo;s get you back on track.
    </p>

    <!-- Search -->
    <form role="search" method="get" action="<?= esc_url(home_url('/')); ?>" class="w-full max-w-md mb-[6vw] md:mb-[2vw]">
        <div class="flex items-center border border-black/10 rounded-full bg-white overflow-hidden shadow-sm focus-within:ring-2 transition-all" style="--tw-ring-color: var(--primary-color);">
            <input
                type="search"
                name="s"
                placeholder="Search properties&hellip;"
                class="flex-1 outline-none bg-transparent font-inter text-[3.5vw] md:text-[0.9vw] text-[#1A1A1A] md:px-[1.2vw] md:py-[0.7vw] px-[5vw] py-[3.5vw]"
                value="<?= esc_attr(get_search_query()); ?>"
            >
            <button
                type="submit"
                class="flex-shrink-0 md:px-[1.5vw] md:py-[0.7vw] px-[5vw] py-[3.5vw] font-semibold font-inter text-white text-[3.5vw] md:text-[0.9vw] rounded-full transition-opacity hover:opacity-90"
                style="background: var(--primary-color);"
            >
                Search
            </button>
        </div>
    </form>

    <!-- Quick links -->
    <div class="flex flex-wrap items-center justify-center gap-3">
        <a href="<?= esc_url(home_url('/')); ?>"
           class="inline-flex items-center gap-2 font-semibold font-inter rounded-full text-[3.5vw] md:text-[0.9vw] md:px-[1.8vw] md:py-[0.6vw] px-[6vw] py-[3vw] transition-opacity hover:opacity-90 text-white"
           style="background: var(--primary-color);">
            <svg class="md:w-[0.9vw] md:h-[0.9vw] w-[4vw] h-[4vw]" viewBox="0 0 24 24" fill="none">
                <path d="M10 19L3 12M3 12L10 5M3 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Back to Home
        </a>

        <a href="<?= esc_url(home_url('/properties')); ?>"
           class="inline-flex items-center gap-2 font-semibold font-inter rounded-full text-[3.5vw] md:text-[0.9vw] md:px-[1.8vw] md:py-[0.6vw] px-[6vw] py-[3vw] border transition-colors hover:bg-gray-50"
           style="border-color: var(--primary-color); color: var(--primary-color);">
            Browse Properties
        </a>

        <a href="<?= esc_url(home_url('/pricing')); ?>"
           class="inline-flex items-center gap-2 font-semibold font-inter rounded-full text-[3.5vw] md:text-[0.9vw] md:px-[1.8vw] md:py-[0.6vw] px-[6vw] py-[3vw] border transition-colors hover:bg-gray-50"
           style="border-color: var(--primary-color); color: var(--primary-color);">
            View Pricing
        </a>
    </div>

</main>

<?php get_footer(); ?>
