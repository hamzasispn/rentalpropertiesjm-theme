<?php
/**
 * Ad Space Component
 * Usage: get_template_part('template-parts/component', 'ad-space', ['slot' => 'sidebar', 'label' => 'Advertisement']);
 *
 * Slots: 'banner' (728×90), 'sidebar' (300×250), 'mobile' (320×50), 'leaderboard' (970×90)
 */

$slot  = $args['slot']  ?? 'banner';
$label = $args['label'] ?? 'Advertisement';
$class = $args['class'] ?? '';

$dimensions = [
    'banner'      => ['w' => 728, 'h' => 90,  'mobile_h' => 60],
    'sidebar'     => ['w' => 300, 'h' => 250, 'mobile_h' => 250],
    'mobile'      => ['w' => 320, 'h' => 50,  'mobile_h' => 50],
    'leaderboard' => ['w' => 970, 'h' => 90,  'mobile_h' => 60],
];

$dim = $dimensions[$slot] ?? $dimensions['banner'];
?>
<div class="ad-space ad-space--<?php echo esc_attr($slot); ?> <?php echo esc_attr($class); ?>"
     data-ad-slot="<?php echo esc_attr($slot); ?>"
     style="width:100%;max-width:<?php echo $dim['w']; ?>px;margin:0 auto;">
    <div style="
        width:100%;
        min-height:<?php echo $dim['h']; ?>px;
        background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%);
        border:1px dashed #cbd5e1;
        border-radius:8px;
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        gap:6px;
        position:relative;
        overflow:hidden;
    ">
        <!-- Replace everything inside this div with your ad code (Google AdSense, etc.) -->
        <span style="font-size:10px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#94a3b8;"><?php echo esc_html($label); ?></span>
        <span style="font-size:11px;color:#cbd5e1;"><?php echo $dim['w']; ?> × <?php echo $dim['h']; ?></span>
    </div>
</div>
