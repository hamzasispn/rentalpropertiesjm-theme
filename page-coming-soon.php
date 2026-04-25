<?php
/**
 * Template Name: Coming Soon
 */

if (current_user_can('manage_options')) {
    wp_redirect(home_url('/'));
    exit;
}

$logo = get_option('mytheme_logo');
$site_name = get_bloginfo('name');
$launch_date = get_option('mytheme_launch_date', '2025-09-01T00:00:00');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coming Soon — <?php bloginfo('name'); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        inter: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        navy: '#132364',
                    },
                    animation: {
                        drift1: 'drift1 8s ease-in-out infinite alternate',
                        drift2: 'drift2 10s ease-in-out infinite alternate-reverse',
                        rise: 'rise linear infinite',
                    },
                    keyframes: {
                        drift1: {
                            '0%': { transform: 'translate(0,0) scale(1)' },
                            '100%': { transform: 'translate(40px,30px) scale(1.1)' },
                        },
                        drift2: {
                            '0%': { transform: 'translate(0,0) scale(1)' },
                            '100%': { transform: 'translate(-40px,-30px) scale(1.1)' },
                        },
                        rise: {
                            '0%': { opacity: '0', transform: 'translateY(0) scale(1)' },
                            '10%': { opacity: '0.5' },
                            '90%': { opacity: '0.15' },
                            '100%': { opacity: '0', transform: 'translateY(-100vh) scale(0.5)' },
                        },
                    },
                },
            },
        }
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <?php wp_head(); ?>
</head>

<body class="font-inter bg-navy overflow-hidden">

    <div x-data="{
        email: '',
        submitted: false,
        days:  '00',
        hours: '00',
        mins:  '00',
        secs:  '00',
        particles: [],
        init() {
            const target = new Date('<?php echo esc_js($launch_date); ?>').getTime();
            const tick = () => {
                const diff = target - Date.now();
                if (diff <= 0) return;
                this.days  = String(Math.floor(diff / 86400000)).padStart(2,'0');
                this.hours = String(Math.floor((diff % 86400000) / 3600000)).padStart(2,'0');
                this.mins  = String(Math.floor((diff % 3600000)  / 60000)).padStart(2,'0');
                this.secs  = String(Math.floor((diff % 60000)    / 1000)).padStart(2,'0');
            };
            tick();
            setInterval(tick, 1000);

            this.particles = Array.from({ length: 30 }, () => ({
                left:     (Math.random() * 100).toFixed(2) + 'vw',
                bottom:   (Math.random() * 20).toFixed(2)  + 'vh',
                duration: (6  + Math.random() * 10).toFixed(2) + 's',
                delay:    (Math.random() * 8).toFixed(2)   + 's',
                size:     (1  + Math.random() * 3).toFixed(2)  + 'px',
            }));
        },
        submit(e) {
            e.preventDefault();
            if (!this.email) return;
            this.submitted = true;
            this.email = '';
        },
    }" x-init="init()" class="relative min-h-screen flex flex-col items-center justify-center px-6 py-16">

        {{-- Glow blob top-left --}}
        <div class="pointer-events-none absolute -top-[40%] -left-[20%] w-[80%] h-[80%] rounded-full animate-drift1"
            style="background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 70%);">
        </div>

        {{-- Glow blob bottom-right --}}
        <div class="pointer-events-none absolute -bottom-[40%] -right-[20%] w-[70%] h-[70%] rounded-full animate-drift2"
            style="background: radial-gradient(circle, rgba(255,255,255,0.04) 0%, transparent 70%);">
        </div>

        {{-- Floating particles --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <template x-for="(p, i) in particles" :key="i">
                <div class="absolute rounded-full bg-white opacity-0 animate-rise"
                    :style="`left:${p.left}; bottom:${p.bottom}; width:${p.size}; height:${p.size}; animation-duration:${p.duration}; animation-delay:${p.delay};`">
                </div>
            </template>
        </div>

        {{-- Content --}}
        <div class="relative z-10 w-full max-w-xl text-center">

            {{-- Logo --}}
            <div class="mb-10">
                <?php if ($logo): ?>
                    <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($site_name); ?>"
                        class="h-12 mx-auto object-contain brightness-0 invert">
                <?php else: ?>
                    <span class="text-2xl font-black tracking-tight text-white">
                        <?php echo esc_html($site_name); ?>
                    </span>
                <?php endif; ?>
            </div>

            {{-- Badge --}}
            <span
                class="inline-block border border-white/30 bg-white/10 text-white text-xs font-bold tracking-widest uppercase px-4 py-1 rounded-full mb-6">
                Coming Soon
            </span>

            {{-- Heading --}}
            <h1 class="text-5xl sm:text-6xl font-black leading-none tracking-tight text-white mb-5">
                Something <span class="text-white/60">Amazing</span><br>Is Coming
            </h1>

            {{-- Subtitle --}}
            <p class="text-base text-white/40 leading-relaxed mb-10 max-w-md mx-auto">
                We're putting the finishing touches on Jamaica's premier property rental platform.
                Be the first to know when we launch.
            </p>

            {{-- Countdown --}}
            <div class="flex justify-center gap-3 mb-10 flex-wrap">
                <template x-for="item in [
                { val: days,  label: 'Days' },
                { val: hours, label: 'Hours' },
                { val: mins,  label: 'Minutes' },
                { val: secs,  label: 'Seconds' },
            ]" :key="item.label">
                    <div class="bg-white/5 border border-white/10 rounded-xl px-5 py-4 min-w-[72px] text-center">
                        <span class="block text-4xl font-black text-white tabular-nums leading-none"
                            x-text="item.val"></span>
                        <span class="block text-[11px] font-semibold text-white/40 uppercase tracking-widest mt-2"
                            x-text="item.label"></span>
                    </div>
                </template>
            </div>

            {{-- Notify form --}}
            <form @submit="submit($event)" class="flex gap-2 max-w-md mx-auto mb-6">
                <input x-model="email" type="email" placeholder="Enter your email address" required
                    class="flex-1 px-4 py-3 rounded-xl border border-white/10 bg-white/5 text-sm text-white placeholder-white/30 outline-none focus:border-white/40 focus:bg-white/10 transition-colors">
                <button type="submit" :disabled="submitted"
                    :class="submitted ? 'opacity-60 cursor-not-allowed' : 'hover:bg-white/90 active:scale-95'"
                    x-text="submitted ? '✓ Subscribed!' : 'Notify Me'"
                    class="px-5 py-3 bg-white text-navy font-bold text-sm rounded-xl transition-all whitespace-nowrap"></button>
            </form>

            {{-- Footer --}}
            <p class="text-xs text-white/20">
                © <?php echo esc_html(date('Y')); ?> <?php echo esc_html($site_name); ?>. All rights reserved.
            </p>

        </div>
    </div>

    <?php wp_footer(); ?>
</body>

</html>