<?php
/**
 * Template Name: Coming Soon
 */

// Admins can still see the site normally
if (current_user_can('manage_options')) {
    wp_redirect(home_url('/'));
    exit;
}

$logo       = get_option('mytheme_logo');
$site_name  = get_bloginfo('name');
$launch_date = get_option('mytheme_launch_date', '');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coming Soon — <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #080d1a;
            color: #f1f5f9;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
        }

        /* Animated background */
        .bg-glow {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }
        .bg-glow::before {
            content: '';
            position: absolute;
            top: -40%;
            left: -20%;
            width: 80%;
            height: 80%;
            background: radial-gradient(circle, rgba(234,179,8,0.12) 0%, transparent 70%);
            animation: drift 8s ease-in-out infinite alternate;
        }
        .bg-glow::after {
            content: '';
            position: absolute;
            bottom: -40%;
            right: -20%;
            width: 70%;
            height: 70%;
            background: radial-gradient(circle, rgba(59,130,246,0.08) 0%, transparent 70%);
            animation: drift 10s ease-in-out infinite alternate-reverse;
        }
        @keyframes drift {
            from { transform: translate(0, 0) scale(1); }
            to   { transform: translate(40px, 30px) scale(1.1); }
        }

        .wrapper {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 40px 24px;
            max-width: 640px;
            width: 100%;
        }

        .logo-wrap { margin-bottom: 40px; }
        .logo-wrap img { height: 48px; object-fit: contain; filter: brightness(0) invert(1); }
        .logo-text {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #f1f5f9;
        }

        .badge {
            display: inline-block;
            background: rgba(234,179,8,0.15);
            border: 1px solid rgba(234,179,8,0.3);
            color: #eab308;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 4px 14px;
            border-radius: 99px;
            margin-bottom: 24px;
        }

        h1 {
            font-size: clamp(36px, 8vw, 64px);
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: -0.03em;
            background: linear-gradient(135deg, #f1f5f9 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
        }
        h1 span {
            background: linear-gradient(135deg, #eab308 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            font-size: 17px;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 40px;
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Countdown */
        <?php if ($launch_date): ?>
        .countdown {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        .countdown-item {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 18px 22px;
            min-width: 80px;
        }
        .countdown-num {
            font-size: 36px;
            font-weight: 800;
            color: #eab308;
            line-height: 1;
            display: block;
        }
        .countdown-label {
            font-size: 11px;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-top: 6px;
        }
        <?php endif; ?>

        /* Notify form */
        .notify-form {
            display: flex;
            gap: 8px;
            max-width: 420px;
            margin: 0 auto 32px;
        }
        .notify-form input {
            flex: 1;
            padding: 14px 18px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #f1f5f9;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        .notify-form input::placeholder { color: #475569; }
        .notify-form input:focus { border-color: #eab308; }
        .notify-form button {
            padding: 14px 22px;
            background: #eab308;
            color: #000;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: opacity 0.2s;
        }
        .notify-form button:hover { opacity: 0.9; }

        .footer-note {
            font-size: 13px;
            color: #334155;
        }

        /* Floating particles */
        .particles { position: fixed; inset: 0; pointer-events: none; z-index: 0; }
        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: #eab308;
            border-radius: 50%;
            opacity: 0;
            animation: rise linear infinite;
        }
        @keyframes rise {
            0%   { opacity: 0; transform: translateY(0) scale(1); }
            10%  { opacity: 0.6; }
            90%  { opacity: 0.2; }
            100% { opacity: 0; transform: translateY(-100vh) scale(0.5); }
        }
    </style>
</head>
<body>

<div class="bg-glow"></div>

<!-- Particles -->
<div class="particles" id="particles"></div>

<div class="wrapper">

    <div class="logo-wrap">
        <?php if ($logo): ?>
            <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($site_name); ?>">
        <?php else: ?>
            <div class="logo-text"><?php echo esc_html($site_name); ?></div>
        <?php endif; ?>
    </div>

    <div class="badge">Coming Soon</div>

    <h1>Something <span>Amazing</span><br>Is Coming</h1>

    <p class="subtitle">
        We're putting the finishing touches on Jamaica's premier property rental platform.
        Be the first to know when we launch.
    </p>

    <?php if ($launch_date): ?>
    <div class="countdown" id="countdown" data-target="<?php echo esc_attr($launch_date); ?>">
        <div class="countdown-item"><span class="countdown-num" id="cd-days">00</span><div class="countdown-label">Days</div></div>
        <div class="countdown-item"><span class="countdown-num" id="cd-hours">00</span><div class="countdown-label">Hours</div></div>
        <div class="countdown-item"><span class="countdown-num" id="cd-mins">00</span><div class="countdown-label">Minutes</div></div>
        <div class="countdown-item"><span class="countdown-num" id="cd-secs">00</span><div class="countdown-label">Seconds</div></div>
    </div>
    <?php endif; ?>

    <form class="notify-form" onsubmit="handleNotify(event)">
        <input type="email" placeholder="Enter your email address" required id="notifyEmail">
        <button type="submit" id="notifyBtn">Notify Me</button>
    </form>

    <p class="footer-note">© <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. All rights reserved.</p>

</div>

<script>
// Countdown timer
<?php if ($launch_date): ?>
(function() {
    const target = new Date('<?php echo esc_js($launch_date); ?>').getTime();
    function update() {
        const diff = target - Date.now();
        if (diff <= 0) return;
        const d = Math.floor(diff / 86400000);
        const h = Math.floor((diff % 86400000) / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        const s = Math.floor((diff % 60000) / 1000);
        document.getElementById('cd-days').textContent  = String(d).padStart(2,'0');
        document.getElementById('cd-hours').textContent = String(h).padStart(2,'0');
        document.getElementById('cd-mins').textContent  = String(m).padStart(2,'0');
        document.getElementById('cd-secs').textContent  = String(s).padStart(2,'0');
    }
    update();
    setInterval(update, 1000);
})();
<?php endif; ?>

// Notify form (client-side only — wire to your email service as needed)
function handleNotify(e) {
    e.preventDefault();
    const btn = document.getElementById('notifyBtn');
    btn.textContent = '✓ Subscribed!';
    btn.disabled = true;
    document.getElementById('notifyEmail').value = '';
}

// Particles
(function() {
    const container = document.getElementById('particles');
    for (let i = 0; i < 30; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.left     = Math.random() * 100 + 'vw';
        p.style.bottom   = Math.random() * 20 + 'vh';
        p.style.animationDuration  = (6 + Math.random() * 10) + 's';
        p.style.animationDelay     = (Math.random() * 8) + 's';
        p.style.width = p.style.height = (1 + Math.random() * 3) + 'px';
        container.appendChild(p);
    }
})();
</script>

<?php wp_footer(); ?>
</body>
</html>
