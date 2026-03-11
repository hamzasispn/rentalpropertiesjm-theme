<section class="mx-auto w-[90%] overflow-hidden md:pt-[3.333vw] pt-[4.167vw] " x-data>
    <div class="flex items-center animate-marquee">
        <template x-for="t in 3" :key="t">
            <template x-for="i in 7" :key="i">
                <div
                    class="w-[32.471vw] h-[15.765vw] md:w-[7.187vw] md:h-[3.49vw] ml-[18.824vw] md:ml-[3.333vw] flex-shrink-0">
                    <img src="<?= get_template_directory_uri(); ?>/assets/LOGO.svg" alt="Logo"
                        class="w-full h-full object-contain" />
                </div>
            </template>
        </template>
    </div>
</section>