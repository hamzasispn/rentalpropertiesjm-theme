<?php
/**
 * User favorites & saved searches
 *
 * - Members can save/unsave properties from cards or the single page
 * - Members can save current search filters as a preset (Mary's use case)
 * - Weekly cron emails members about new listings matching their saved searches
 * - Exposes window.wpUser and window.ptToggleSavedProperty for the front-end
 * - Adds a lightweight login/register REST layer used by the auth modal
 */

if (!defined('ABSPATH')) exit;

if (!defined('PT_SAVED_PROPS_META'))  define('PT_SAVED_PROPS_META',  '_pt_saved_properties');
if (!defined('PT_SAVED_SEARCH_META')) define('PT_SAVED_SEARCH_META', '_pt_saved_searches');

/* ────────────────────────────────────────────────────────────
 *  Role split: members vs. agents
 *
 *  Members  = subscribers with no active subscription plan.
 *             Land on /my-account/ (saved-items dashboard).
 *  Agents   = users with an active subscription plan.
 *             Land on /dashboard/ (full realtor dashboard).
 *
 *  The auth-modal register form always creates a member; agents still
 *  register through /register/ where they pick a plan.
 * ──────────────────────────────────────────────────────────── */

function pt_user_is_agent($user_id) {
    $uid = (int) $user_id;
    if ($uid <= 0) return false;
    if (function_exists('property_theme_get_user_subscription')) {
        $sub = property_theme_get_user_subscription($uid);
        if (!empty($sub)) return true;
    }
    // Fallback for admins/editors so they still land somewhere useful.
    return user_can($uid, 'edit_others_posts');
}

function pt_get_member_dashboard_url() {
    $page = get_page_by_path('my-account');
    return $page ? get_permalink($page) : home_url('/my-account/');
}

function pt_get_agent_dashboard_url() {
    $page = get_page_by_path('dashboard');
    return $page ? get_permalink($page) : home_url('/dashboard/');
}

function pt_get_user_home_url($user_id) {
    if (pt_user_is_agent($user_id)) return pt_get_agent_dashboard_url();
    // Agents-in-waiting: they registered on the "I'm an Agent" tab but
    // haven't paid for a plan yet. Send them to /pricing/ so the funnel
    // completes; the flag clears itself once they subscribe.
    if ((int) $user_id > 0 && get_user_meta((int) $user_id, '_pt_wants_agent', true)) {
        return home_url('/pricing');
    }
    return pt_get_member_dashboard_url();
}

// When a user's subscription becomes active, the "wants agent" marker has
// done its job — drop it so future logins go straight to /dashboard/.
add_action('property_theme_subscription_activated', function ($user_id) {
    if ((int) $user_id > 0) delete_user_meta((int) $user_id, '_pt_wants_agent');
});

/**
 * Auto-create the /my-account/ page once, wired to the member dashboard
 * template. Runs at most once per install (guarded by an option flag).
 */
add_action('init', function () {
    if (wp_doing_ajax() || wp_doing_cron() || defined('REST_REQUEST')) return;
    if (get_option('pt_my_account_page_id')) return;

    $existing = get_page_by_path('my-account');
    if ($existing) {
        update_option('pt_my_account_page_id', (int) $existing->ID);
        update_post_meta($existing->ID, '_wp_page_template', 'page-my-account.php');
        return;
    }
    $page_id = wp_insert_post(array(
        'post_title'   => 'My Account',
        'post_name'    => 'my-account',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '',
    ));
    if ($page_id && !is_wp_error($page_id)) {
        update_post_meta($page_id, '_wp_page_template', 'page-my-account.php');
        update_option('pt_my_account_page_id', (int) $page_id);
    }
}, 20);

/* ────────────────────────────────────────────────────────────
 *  Helpers
 * ──────────────────────────────────────────────────────────── */

function pt_get_saved_properties($user_id) {
    $ids = get_user_meta((int)$user_id, PT_SAVED_PROPS_META, true);
    if (!is_array($ids)) return array();
    return array_values(array_unique(array_map('intval', $ids)));
}

function pt_set_saved_properties($user_id, $ids) {
    $ids = array_values(array_unique(array_map('intval', (array) $ids)));
    update_user_meta((int)$user_id, PT_SAVED_PROPS_META, $ids);
}

function pt_get_saved_searches($user_id) {
    $searches = get_user_meta((int)$user_id, PT_SAVED_SEARCH_META, true);
    if (!is_array($searches)) return array();
    return $searches;
}

function pt_set_saved_searches($user_id, $searches) {
    update_user_meta((int)$user_id, PT_SAVED_SEARCH_META, (array) $searches);
}

function pt_sanitize_search_criteria($criteria) {
    $allowed = array(
        'search', 'property_type', 'listing_status', 'city', 'location',
        'beds', 'baths', 'price_min', 'price_max', 'area_min', 'area_max',
        'featured', 'currency', 'keyword',
    );
    $clean = array();
    foreach ($allowed as $k) {
        if (!isset($criteria[$k])) continue;
        $v = $criteria[$k];
        if (is_array($v)) $v = implode(',', array_map('sanitize_text_field', $v));
        if (is_numeric($v)) $clean[$k] = 0 + $v;
        else                $clean[$k] = sanitize_text_field((string) $v);
    }
    return $clean;
}

/* ────────────────────────────────────────────────────────────
 *  REST endpoints
 * ──────────────────────────────────────────────────────────── */

add_action('rest_api_init', function () {
    $logged_in = function () { return is_user_logged_in(); };
    $anyone    = function () { return true; };

    // Saved properties
    register_rest_route('property-theme/v1', '/user/toggle-saved-property', array(
        'methods'             => 'POST',
        'callback'            => 'pt_rest_toggle_saved_property',
        'permission_callback' => $logged_in,
    ));
    register_rest_route('property-theme/v1', '/user/saved-properties', array(
        'methods'             => 'GET',
        'callback'            => 'pt_rest_get_saved_properties',
        'permission_callback' => $logged_in,
    ));

    // Saved searches
    register_rest_route('property-theme/v1', '/user/saved-searches', array(
        array(
            'methods'             => 'GET',
            'callback'            => 'pt_rest_get_saved_searches',
            'permission_callback' => $logged_in,
        ),
        array(
            'methods'             => 'POST',
            'callback'            => 'pt_rest_add_saved_search',
            'permission_callback' => $logged_in,
        ),
    ));
    register_rest_route('property-theme/v1', '/user/saved-searches/(?P<id>[A-Za-z0-9_\-]+)', array(
        array(
            'methods'             => 'DELETE',
            'callback'            => 'pt_rest_delete_saved_search',
            'permission_callback' => $logged_in,
        ),
        array(
            'methods'             => 'PATCH',
            'callback'            => 'pt_rest_update_saved_search',
            'permission_callback' => $logged_in,
        ),
    ));

    // Lightweight auth for the popup modal (login + register)
    register_rest_route('property-theme/v1', '/auth/login', array(
        'methods'             => 'POST',
        'callback'            => 'pt_rest_login',
        'permission_callback' => $anyone,
    ));
    register_rest_route('property-theme/v1', '/auth/register', array(
        'methods'             => 'POST',
        'callback'            => 'pt_rest_register',
        'permission_callback' => $anyone,
    ));
});

function pt_rest_toggle_saved_property($request) {
    $uid = get_current_user_id();
    $pid = intval($request->get_param('property_id'));
    if (!$pid || get_post_type($pid) !== 'property') {
        return new WP_Error('invalid_property', 'Invalid property.', array('status' => 400));
    }
    $ids = pt_get_saved_properties($uid);
    if (in_array($pid, $ids, true)) {
        $ids = array_values(array_diff($ids, array($pid)));
        $state = 'removed';
    } else {
        $ids[] = $pid;
        $state = 'added';
    }
    pt_set_saved_properties($uid, $ids);
    return array('success' => true, 'state' => $state, 'saved_ids' => $ids);
}

function pt_rest_get_saved_properties($request) {
    $uid = get_current_user_id();
    $ids = pt_get_saved_properties($uid);
    if (empty($ids)) return array('items' => array());

    $posts = get_posts(array(
        'post_type'   => 'property',
        'post_status' => 'publish',
        'include'     => $ids,
        'orderby'     => 'post__in',
        'numberposts' => -1,
    ));

    $items = array();
    foreach ($posts as $p) {
        $addr = function_exists('property_theme_get_full_address')
            ? (property_theme_get_full_address($p->ID)['address'] ?? '')
            : '';
        $listing_terms = wp_get_post_terms($p->ID, 'property_listing_status');
        $bed_terms     = wp_get_post_terms($p->ID, 'bedroom');
        $bath_terms    = wp_get_post_terms($p->ID, 'bathroom');
        $items[] = array(
            'id'        => $p->ID,
            'title'     => get_the_title($p),
            'permalink' => get_permalink($p),
            'thumbnail' => get_the_post_thumbnail_url($p, 'medium_large') ?: '',
            'price'     => (float) get_post_meta($p->ID, '_property_price', true),
            'area'      => get_post_meta($p->ID, '_property_area', true),
            'address'   => $addr,
            'bedrooms'  => (!is_wp_error($bed_terms) && !empty($bed_terms))   ? $bed_terms[0]->name  : '',
            'bathrooms' => (!is_wp_error($bath_terms) && !empty($bath_terms)) ? $bath_terms[0]->name : '',
            'listing_status' => (!is_wp_error($listing_terms) && !empty($listing_terms)) ? $listing_terms[0]->slug : '',
        );
    }
    return array('items' => $items);
}

function pt_rest_get_saved_searches($request) {
    return array('items' => array_values(pt_get_saved_searches(get_current_user_id())));
}

function pt_rest_add_saved_search($request) {
    $uid      = get_current_user_id();
    $label    = sanitize_text_field($request->get_param('label') ?: 'My Search');
    $criteria = pt_sanitize_search_criteria((array) $request->get_param('criteria'));
    $weekly   = !empty($request->get_param('weekly_email'));

    $searches = pt_get_saved_searches($uid);
    $id = uniqid('sr_');
    $searches[$id] = array(
        'id'                => $id,
        'label'             => $label,
        'criteria'          => $criteria,
        'weekly_email'      => $weekly,
        'created_at'        => time(),
        'last_notified_at'  => 0,
    );
    pt_set_saved_searches($uid, $searches);
    return array('success' => true, 'item' => $searches[$id]);
}

function pt_rest_delete_saved_search($request) {
    $uid = get_current_user_id();
    $id  = sanitize_text_field($request['id']);
    $searches = pt_get_saved_searches($uid);
    if (isset($searches[$id])) unset($searches[$id]);
    pt_set_saved_searches($uid, $searches);
    return array('success' => true);
}

function pt_rest_update_saved_search($request) {
    $uid = get_current_user_id();
    $id  = sanitize_text_field($request['id']);
    $searches = pt_get_saved_searches($uid);
    if (!isset($searches[$id])) {
        return new WP_Error('not_found', 'Search not found.', array('status' => 404));
    }
    if ($request->get_param('label') !== null) {
        $searches[$id]['label'] = sanitize_text_field((string) $request->get_param('label'));
    }
    if ($request->get_param('weekly_email') !== null) {
        $searches[$id]['weekly_email'] = !empty($request->get_param('weekly_email'));
    }
    pt_set_saved_searches($uid, $searches);
    return array('success' => true, 'item' => $searches[$id]);
}

function pt_rest_login($request) {
    $email    = sanitize_email($request->get_param('email'));
    $password = (string) $request->get_param('password');
    if (!$email || !$password) {
        return new WP_REST_Response(array('success' => false, 'message' => 'Email and password required.'), 400);
    }
    $creds = array(
        'user_login'    => $email,
        'user_password' => $password,
        'remember'      => true,
    );
    $user = wp_signon($creds, is_ssl());
    if (is_wp_error($user)) {
        return new WP_REST_Response(array('success' => false, 'message' => 'Invalid email or password.'), 401);
    }
    return array(
        'success'  => true,
        'redirect' => pt_get_user_home_url($user->ID),
    );
}

function pt_rest_register($request) {
    if (!get_option('users_can_register')) {
        // Register anyway — this is a membership site.
    }
    $email    = sanitize_email($request->get_param('email'));
    $password = (string) $request->get_param('password');
    $name     = sanitize_text_field($request->get_param('name') ?: '');

    if (!$email || !is_email($email)) {
        return new WP_REST_Response(array('success' => false, 'message' => 'A valid email is required.'), 400);
    }
    if (strlen($password) < 6) {
        return new WP_REST_Response(array('success' => false, 'message' => 'Password must be at least 6 characters.'), 400);
    }
    if (email_exists($email) || username_exists($email)) {
        return new WP_REST_Response(array('success' => false, 'message' => 'An account with that email already exists.'), 400);
    }
    $uid = wp_create_user($email, $password, $email);
    if (is_wp_error($uid)) {
        return new WP_REST_Response(array('success' => false, 'message' => 'Could not create account.'), 400);
    }
    // Popup signup always creates a plain member (subscriber). Agents go
    // through /register/ where they also pick a plan.
    $u = new WP_User($uid);
    $u->set_role('subscriber');

    if ($name) {
        wp_update_user(array('ID' => $uid, 'display_name' => $name, 'first_name' => $name));
    }
    wp_set_current_user($uid);
    wp_set_auth_cookie($uid, true, is_ssl());
    return array(
        'success'  => true,
        'redirect' => pt_get_member_dashboard_url(),
    );
}

/* ────────────────────────────────────────────────────────────
 *  Expose user state to JS + inject auth modal
 * ──────────────────────────────────────────────────────────── */

add_action('wp_head', function () {
    $uid = get_current_user_id();
    $state = array(
        'isLoggedIn'      => is_user_logged_in(),
        'isAgent'         => $uid ? pt_user_is_agent($uid) : false,
        'nonce'           => wp_create_nonce('wp_rest'),
        'restRoot'        => esc_url_raw(rest_url('property-theme/v1')),
        'loginUrl'        => wp_login_url(),
        'registerUrl'     => wp_registration_url(),
        'dashboardUrl'    => $uid ? pt_get_user_home_url($uid) : pt_get_member_dashboard_url(),
        'memberHomeUrl'   => pt_get_member_dashboard_url(),
        'agentHomeUrl'    => pt_get_agent_dashboard_url(),
        'savedProperties' => $uid ? pt_get_saved_properties($uid) : array(),
    );
    echo '<script id="pt-user-state">window.wpUser = ' . wp_json_encode($state) . ';</script>' . "\n";
}, 5);

add_action('wp_footer', function () {
    if (is_admin()) return;
    get_template_part('template-parts/component', 'auth-modal');
});

/* ────────────────────────────────────────────────────────────
 *  Weekly saved-search email cron
 *  Runs hourly; sends per-user email only when a search hasn't
 *  been notified in the past week AND new matches exist.
 * ──────────────────────────────────────────────────────────── */

add_action('init', function () {
    if (wp_doing_ajax() || wp_doing_cron() || defined('REST_REQUEST')) return;
    if (!wp_next_scheduled('pt_saved_search_weekly_cron')) {
        wp_schedule_event(time() + 300, 'hourly', 'pt_saved_search_weekly_cron');
    }
});

add_action('pt_saved_search_weekly_cron', 'pt_saved_search_weekly_handler');

function pt_saved_search_weekly_handler() {
    global $wpdb;
    $key  = PT_SAVED_SEARCH_META;
    $rows = $wpdb->get_results(
        $wpdb->prepare("SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s", $key)
    );
    if (!$rows) return;

    $week = 7 * DAY_IN_SECONDS;
    $now  = time();

    foreach ($rows as $row) {
        $uid = intval($row->user_id);
        $searches = maybe_unserialize($row->meta_value);
        if (!is_array($searches)) continue;

        $touched  = false;
        $bundles  = array();

        foreach ($searches as $sid => $search) {
            if (empty($search['weekly_email'])) continue;
            $last = intval($search['last_notified_at'] ?? 0);
            if ($last && ($now - $last) < $week) continue;

            $matches = pt_query_saved_search_matches(
                (array) ($search['criteria'] ?? array()),
                $last ?: ($now - $week)
            );
            if (!empty($matches)) {
                $bundles[$sid] = array('search' => $search, 'matches' => $matches);
            }
            $searches[$sid]['last_notified_at'] = $now;
            $touched = true;
        }

        if (!empty($bundles)) {
            pt_send_saved_search_email($uid, $bundles);
        }
        if ($touched) {
            update_user_meta($uid, PT_SAVED_SEARCH_META, $searches);
        }
    }
}

function pt_query_saved_search_matches($criteria, $since_ts) {
    $args = array(
        'post_type'   => 'property',
        'post_status' => 'publish',
        'numberposts' => 20,
        'date_query'  => array(
            array('after' => date('Y-m-d H:i:s', (int) $since_ts)),
        ),
    );

    $meta = array();
    if (!empty($criteria['price_min']) || !empty($criteria['price_max'])) {
        $mn = intval($criteria['price_min'] ?? 0);
        $mx = intval($criteria['price_max'] ?? 999999999);
        if ($mx > 0) {
            $meta[] = array(
                'key'     => '_property_price',
                'value'   => array($mn, $mx),
                'type'    => 'NUMERIC',
                'compare' => 'BETWEEN',
            );
        }
    }
    if (!empty($meta)) $args['meta_query'] = $meta;

    $tax = array();
    if (!empty($criteria['property_type'])) {
        $slugs = is_array($criteria['property_type'])
            ? $criteria['property_type']
            : array_filter(array_map('trim', explode(',', (string) $criteria['property_type'])));
        if (!empty($slugs)) {
            $tax[] = array('taxonomy' => 'property_type', 'field' => 'slug', 'terms' => $slugs);
        }
    }
    if (!empty($criteria['listing_status'])) {
        $tax[] = array('taxonomy' => 'property_listing_status', 'field' => 'slug', 'terms' => $criteria['listing_status']);
    }
    if (!empty($criteria['beds'])) {
        $tax[] = array('taxonomy' => 'bedroom', 'field' => 'name', 'terms' => (string) intval($criteria['beds']));
    }
    if (!empty($tax)) {
        if (count($tax) > 1) $tax['relation'] = 'AND';
        $args['tax_query'] = $tax;
    }
    if (!empty($criteria['search'])) {
        $args['s'] = (string) $criteria['search'];
    }

    return get_posts($args);
}

function pt_send_saved_search_email($uid, $bundles) {
    $user = get_userdata((int) $uid);
    if (!$user || empty($user->user_email)) return;

    $site    = get_bloginfo('name');
    $dash    = get_page_by_path('dashboard');
    $dashurl = $dash ? get_permalink($dash) : home_url('/dashboard/');
    $subject = 'New listings for your saved searches on ' . $site;

    $html  = '<div style="font-family:Inter,Arial,sans-serif;color:#0f172a;max-width:640px;margin:0 auto;padding:24px;">';
    $html .= '<h2 style="color:#132364;margin:0 0 8px;">Hi ' . esc_html($user->display_name) . ',</h2>';
    $html .= '<p style="margin:0 0 20px;color:#475569;">Here are the newest listings matching your saved searches this week.</p>';

    foreach ($bundles as $bundle) {
        $s = $bundle['search'];
        $html .= '<h3 style="margin:24px 0 12px;color:#132364;border-bottom:1px solid #e2e8f0;padding-bottom:6px;">'
              .  esc_html($s['label']) . '</h3>';

        foreach ($bundle['matches'] as $p) {
            $price = number_format((float) get_post_meta($p->ID, '_property_price', true));
            $addr  = function_exists('property_theme_get_full_address')
                ? (property_theme_get_full_address($p->ID)['address'] ?? '')
                : '';
            $url   = get_permalink($p->ID);
            $thumb = get_the_post_thumbnail_url($p->ID, 'medium_large');

            $html .= '<a href="' . esc_url($url) . '" style="display:block;text-decoration:none;color:inherit;padding:12px;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:10px;background:#fff;">';
            if ($thumb) {
                $html .= '<img src="' . esc_url($thumb) . '" style="max-width:100%;width:100%;max-height:180px;object-fit:cover;border-radius:8px;display:block;margin-bottom:10px;">';
            }
            $html .= '<div style="font-size:16px;font-weight:600;color:#0f172a;">' . esc_html(get_the_title($p)) . '</div>';
            if ($addr) $html .= '<div style="color:#64748b;font-size:14px;margin-top:2px;">' . esc_html($addr) . '</div>';
            $html .= '<div style="color:#132364;font-weight:700;margin-top:6px;">$' . $price . '</div>';
            $html .= '</a>';
        }
    }

    $html .= '<p style="margin-top:32px;font-size:12px;color:#94a3b8;">You are receiving this because you enabled weekly emails on a saved search. '
          .  'Manage your saved searches in your <a href="' . esc_url($dashurl) . '" style="color:#132364;">dashboard</a>.</p>';
    $html .= '</div>';

    wp_mail($user->user_email, $subject, $html, array('Content-Type: text/html; charset=UTF-8'));
}
