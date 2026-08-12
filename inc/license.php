<?php


if (!defined('ABSPATH')) exit;

define('PT_LICENSE_SERVER', 'https://firebrick-sandpiper-953468.hostingersite.com/license.php');
define('PT_LICENSE_SECRET', 'change-this-to-a-long-random-string');

/** Is this install licensed? Defaults to yes until told otherwise. */
function pt_license_ok() {
    return get_option('pt_license_active', '1') === '1';
}

/* ── PULL: daily check ─────────────────────────────────────────────── */

add_action('init', function () {
    if (!wp_next_scheduled('pt_license_check')) {
        wp_schedule_event(time() + 300, 'daily', 'pt_license_check');
    }
});

add_action('pt_license_check', 'pt_license_check_now');

function pt_license_check_now() {
    $res = wp_remote_get(add_query_arg('site', home_url(), PT_LICENSE_SERVER), array('timeout' => 10));

    // Unreachable / errored → leave the current state alone (fail open).
    if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) return;

    $body = json_decode(wp_remote_retrieve_body($res), true);
    if (!isset($body['active'])) return;

    update_option('pt_license_active', $body['active'] ? '1' : '0');
}

/* ── PUSH: instant kill via curl ───────────────────────────────────── */

add_action('rest_api_init', function () {
    register_rest_route('property-theme/v1', '/license', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true', // secret is checked in the callback
        'callback'            => function ($req) {
            if (!hash_equals(PT_LICENSE_SECRET, (string) $req->get_param('secret'))) {
                return new WP_Error('forbidden', 'Bad secret', array('status' => 403));
            }
            update_option('pt_license_active', $req->get_param('active') ? '1' : '0');
            return array('active' => pt_license_ok());
        },
    ));
});

/* ── Enforcement ───────────────────────────────────────────────────── */

/**
 * Called from the theme's hot paths (property search, property queries).
 *
 * This is deliberately NOT a self-contained hook: the callers depend on this
 * function existing. Delete this file and remove the require, and those
 * callers fatal — the site breaks instead of quietly going unlicensed.
 * Encode this one file with ionCube and there is nothing left to grep for.
 */
function pt_license_gate() {
    if (pt_license_ok()) return true;
    if (is_user_logged_in() && current_user_can('manage_options')) return true;
    return false;
}

// No properties for unlicensed installs — archive, search and single all go
// through this, so the site is visibly dead without touching any template.
add_action('pre_get_posts', function ($q) {
    if (is_admin() || pt_license_gate()) return;
    if ($q->get('post_type') === 'property' || is_post_type_archive('property') || is_singular('property')) {
        $q->set('post__in', array(0));
    }
});

// Front-end: replace the site with a notice. Admin stays reachable so the
// client can still log in, see why, and pay.
add_action('template_redirect', function () {
    if (pt_license_gate()) return;
    wp_die(
        '<h1>Site temporarily unavailable</h1><p>This theme licence is inactive. '
        . 'Please contact the developer to restore service.</p>',
        'Licence inactive',
        array('response' => 503)
    );
}, 0);

add_action('admin_notices', function () {
    if (pt_license_ok()) return;
    echo '<div class="notice notice-error"><p><strong>Theme licence inactive.</strong> '
       . 'The public site is disabled until the licence is renewed.</p></div>';
});
