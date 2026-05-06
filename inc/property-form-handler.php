<?php
/**
 * Property Add/Edit form handler.
 *
 * Why this file exists:
 *   The handler used to live at the top of `template-parts/section-add-property.php`,
 *   which is included from inside `dashboard.php`'s body — i.e. AFTER `get_header()`
 *   has already written HTML. By the time `wp_safe_redirect()` was called, headers
 *   were already sent and the redirect silently no-op'd. Properties were saved to the
 *   database, but the user landed on a half-rendered page with no success banner and
 *   assumed nothing happened.
 *
 *   Hooking into `template_redirect` runs the handler BEFORE any output, so redirects
 *   work correctly and the user is bounced to the success state.
 */

if (!defined('ABSPATH')) exit;

add_action('template_redirect', 'property_theme_handle_property_form_submission');

function property_theme_handle_property_form_submission() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')      return;
    if (!isset($_POST['submit_property']))          return;

    // Diagnostic tracing — remove once submission is confirmed working.
    error_log('[property_listing][handler] reached. user_logged_in=' . (is_user_logged_in() ? 'yes' : 'NO') .
              ' uri=' . ($_SERVER['REQUEST_URI'] ?? '?') .
              ' has_nonce=' . (!empty($_POST['property_form_nonce']) ? 'yes' : 'NO'));

    if (!is_user_logged_in()) {
        error_log('[property_listing][handler] aborted: user not logged in');
        return;
    }

    $current_user = wp_get_current_user();
    $user_id      = (int) $current_user->ID;

    if (!wp_verify_nonce($_POST['property_form_nonce'] ?? '', 'add_property_nonce')) {
        error_log('[property_listing][handler] nonce verification FAILED for user ' . $user_id);
        wp_die('Security check failed: Invalid nonce.');
    }

    error_log('[property_listing][handler] nonce ok, processing for user ' . $user_id);

    $is_edit     = isset($_POST['property_id_edit']) && intval($_POST['property_id_edit']) > 0;
    $property_id = $is_edit ? intval($_POST['property_id_edit']) : 0;

    if ($is_edit) {
        $existing = get_post($property_id);
        if (!$existing || (int) $existing->post_author !== $user_id || $existing->post_type !== 'property') {
            wp_die('You do not have permission to edit this property.');
        }
    }

    // Plan-driven media limits (used by the gallery uploader below)
    $plan_photo_limit = 10;
    $plan_video_limit = 2;
    if (function_exists('property_theme_get_user_subscription') && function_exists('property_theme_get_plan')) {
        $sub = property_theme_get_user_subscription($user_id);
        if ($sub) {
            $plan = property_theme_get_plan($sub->package_id);
            if ($plan) {
                $plan_photo_limit = (int) ($plan['photo_limit'] ?? 10);
                $plan_video_limit = (int) ($plan['video_limit'] ?? 2);
            }
        }
    }

    // ── Required-field validation ─────────────────────────────────────────
    $errors = array();
    if (empty(sanitize_text_field($_POST['property_title'] ?? ''))) $errors[] = 'Property title is required.';
    if (empty(sanitize_text_field($_POST['property_price'] ?? ''))) $errors[] = 'Price is required.';
    if (empty(sanitize_text_field($_POST['property_city']  ?? ''))) $errors[] = 'City is required.';
    if (empty(sanitize_text_field($_POST['property_type']  ?? ''))) $errors[] = 'Property type is required.';

    if (!empty($errors)) {
        set_transient('property_form_errors_' . $user_id, $errors, 30);
        $back = wp_get_referer() ?: home_url('/dashboard/#add-property');
        $back = add_query_arg(['error' => 1], $back);
        if ($is_edit) $back = add_query_arg('property_id', $property_id, $back);
        wp_safe_redirect($back);
        exit;
    }

    // ── Status resolution ─────────────────────────────────────────────────
    // New listings always go through as `pending` for admin review. Admins bypass
    // review. Edits keep their existing status (so an approved listing stays live).
    $existing_status = $is_edit ? get_post_status($property_id) : 'pending';
    $resolved_status = ($is_edit && $existing_status === 'publish')
        ? 'publish'
        : (current_user_can('manage_options') ? 'publish' : 'pending');

    $post_data = array(
        'post_type'    => 'property',
        'post_status'  => $resolved_status,
        'post_title'   => sanitize_text_field($_POST['property_title'] ?? 'Untitled Property'),
        'post_content' => sanitize_textarea_field($_POST['property_description'] ?? ''),
    );

    if ($is_edit) {
        $post_data['ID'] = $property_id;
        $post_id         = wp_update_post($post_data, true);
    } else {
        $post_data['post_author'] = $user_id;
        $post_id                  = wp_insert_post($post_data, true);
    }

    if (is_wp_error($post_id) || !$post_id) {
        $msg = is_wp_error($post_id) ? $post_id->get_error_message() : 'Unknown error';
        error_log('[property_listing] property save failed for user ' . $user_id . ': ' . $msg);
        set_transient('property_form_errors_' . $user_id,
            array('Could not save property: ' . $msg), 30);
        $back = add_query_arg(['error' => 1], wp_get_referer() ?: home_url('/dashboard/#add-property'));
        wp_safe_redirect($back);
        exit;
    }

    // ── Meta ──────────────────────────────────────────────────────────────
    update_post_meta($post_id, '_property_price',     sanitize_text_field($_POST['property_price']    ?? ''));
    update_post_meta($post_id, '_property_area',      sanitize_text_field($_POST['property_area']     ?? ''));
    update_post_meta($post_id, '_property_city',      sanitize_text_field($_POST['property_city']     ?? ''));
    update_post_meta($post_id, '_property_address',   sanitize_text_field($_POST['property_address']  ?? ''));
    update_post_meta($post_id, '_property_latitude',  sanitize_text_field($_POST['property_latitude'] ?? ''));
    update_post_meta($post_id, '_property_longitude', sanitize_text_field($_POST['property_longitude']?? ''));
    update_post_meta($post_id, '_property_featured',  isset($_POST['property_featured']) ? 1 : 0);

    // ── Taxonomies ────────────────────────────────────────────────────────
    $property_type = sanitize_text_field($_POST['property_type'] ?? '');
    if ($property_type) {
        $term = get_term_by('slug', $property_type, 'property_type');
        if ($term) wp_set_post_terms($post_id, array($term->term_id), 'property_type');
    }

    $property_bedroom = sanitize_text_field($_POST['property_bedroom'] ?? '');
    if ($property_bedroom) {
        $bed_term = get_term_by('slug', $property_bedroom, 'bedroom');
        if ($bed_term) {
            wp_set_post_terms($post_id, array((int) $bed_term->term_id), 'bedroom');
            update_post_meta($post_id, '_property_bedrooms', (int) $bed_term->name);
        }
    } else {
        wp_set_object_terms($post_id, array(), 'bedroom');
        delete_post_meta($post_id, '_property_bedrooms');
    }

    $property_bathroom = sanitize_text_field($_POST['property_bathroom'] ?? '');
    if ($property_bathroom) {
        $bath_term = get_term_by('slug', $property_bathroom, 'bathroom');
        if ($bath_term) {
            wp_set_post_terms($post_id, array((int) $bath_term->term_id), 'bathroom');
            update_post_meta($post_id, '_property_bathrooms', (float) $bath_term->name);
        }
    } else {
        wp_set_object_terms($post_id, array(), 'bathroom');
        delete_post_meta($post_id, '_property_bathrooms');
    }

    $property_listing_status = sanitize_text_field($_POST['property_listing_status'] ?? '');
    if ($property_listing_status) {
        $ls_term = get_term_by('slug', $property_listing_status, 'property_listing_status');
        if ($ls_term) wp_set_post_terms($post_id, array($ls_term->term_id), 'property_listing_status');
    } else {
        wp_set_object_terms($post_id, array(), 'property_listing_status');
    }

    // ── Amenities groups ──────────────────────────────────────────────────
    $amenities_data = array();
    if (isset($_POST['amenities_groups']) && is_array($_POST['amenities_groups'])) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        foreach ($_POST['amenities_groups'] as $g => $group_data) {
            $group = array('title' => sanitize_text_field($group_data['title'] ?? ''), 'amenities' => array());
            if (isset($group_data['amenities']) && is_array($group_data['amenities'])) {
                foreach ($group_data['amenities'] as $a => $amenity) {
                    if (!empty($amenity['title'])) {
                        $icon = esc_url_raw($amenity['icon'] ?? '');
                        if (isset($_FILES['amenities_groups']['name'][$g]['amenities'][$a]['icon_file']) &&
                            $_FILES['amenities_groups']['error'][$g]['amenities'][$a]['icon_file'] === 0) {
                            $_FILES['temp_icon'] = array(
                                'name'     => $_FILES['amenities_groups']['name'][$g]['amenities'][$a]['icon_file'],
                                'type'     => $_FILES['amenities_groups']['type'][$g]['amenities'][$a]['icon_file'],
                                'tmp_name' => $_FILES['amenities_groups']['tmp_name'][$g]['amenities'][$a]['icon_file'],
                                'error'    => $_FILES['amenities_groups']['error'][$g]['amenities'][$a]['icon_file'],
                                'size'     => $_FILES['amenities_groups']['size'][$g]['amenities'][$a]['icon_file'],
                            );
                            $attachment_id = media_handle_upload('temp_icon', $post_id);
                            if (!is_wp_error($attachment_id)) $icon = wp_get_attachment_url($attachment_id);
                        }
                        $group['amenities'][] = array('title' => sanitize_text_field($amenity['title']), 'icon' => $icon);
                    }
                }
            }
            $amenities_data[] = $group;
        }
    }
    update_post_meta($post_id, '_property_amenities_data', $amenities_data);

    // ── Contact numbers ───────────────────────────────────────────────────
    $property_numbers = array('whats' => array(), 'num' => array());
    if (isset($_POST['property_numbers']) && is_array($_POST['property_numbers'])) {
        foreach ($_POST['property_numbers'] as $number_data) {
            $number = sanitize_text_field($number_data['number'] ?? '');
            $type   = sanitize_text_field($number_data['type']   ?? 'num');
            if (!empty($number)) {
                if ($type === 'whats') $property_numbers['whats'][] = $number;
                else                   $property_numbers['num'][]   = $number;
            }
        }
    }
    update_post_meta($post_id, '_property_numbers', $property_numbers);

    // ── Gallery ───────────────────────────────────────────────────────────
    $gallery          = array();
    $existing_gallery = $is_edit ? (get_post_meta($post_id, '_property_gallery', true) ?: array()) : array();

    $ex_photos = 0; $ex_videos = 0;
    foreach ($existing_gallery as $item) {
        if (($item['type'] ?? 'image') === 'video') $ex_videos++; else $ex_photos++;
    }

    if (isset($_FILES['property_gallery_files']) && isset($_POST['property_gallery_types'])) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $files = $_FILES['property_gallery_files'];
        $types = $_POST['property_gallery_types'];

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== 0 || empty($files['name'][$i])) continue;

            $file_type   = sanitize_text_field($types[$i] ?? 'image');
            $over_photo  = ($file_type !== 'video' && $ex_photos >= $plan_photo_limit);
            $over_video  = ($file_type === 'video' && $ex_videos >= $plan_video_limit);
            $no_video    = ($file_type === 'video' && $plan_video_limit === 0);

            if ($over_photo || $over_video || $no_video) continue;

            $_FILES['temp_file'] = array(
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            );
            $attachment_id = media_handle_upload('temp_file', $post_id);
            if (!is_wp_error($attachment_id)) {
                $media_url = wp_get_attachment_url($attachment_id);
                $gallery[] = array('type' => $file_type, 'media_url' => $media_url);
                if ($file_type === 'video') $ex_videos++; else $ex_photos++;
            }
        }
    }
    $gallery = array_merge($existing_gallery, $gallery);
    update_post_meta($post_id, '_property_gallery', $gallery);

    // ── Featured image ────────────────────────────────────────────────────
    if (isset($_FILES['property_featured_image']) && $_FILES['property_featured_image']['size'] > 0) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        $attachment_id = media_handle_upload('property_featured_image', $post_id);
        if (!is_wp_error($attachment_id)) set_post_thumbnail($post_id, $attachment_id);
    }

    // ── Redirect with success state ───────────────────────────────────────
    $saved_status = get_post_status($post_id);
    $redirect_url = home_url('/dashboard/');
    $redirect_url = add_query_arg(array(
        'property_id' => $post_id,
        'saved'       => 1,
        'review'      => $saved_status === 'pending' ? 1 : 0,
    ), $redirect_url);
    $redirect_url .= '#add-property';

    wp_safe_redirect($redirect_url);
    exit;
}

/**
 * Property delete: also handled at template_redirect so headers can still be sent.
 */
add_action('template_redirect', function () {
    if (empty($_GET['action']) || $_GET['action'] !== 'delete' || empty($_GET['property_id'])) return;
    if (!is_user_logged_in()) return;

    $user_id = get_current_user_id();
    $pid     = intval($_GET['property_id']);
    $post    = get_post($pid);

    if (!$post || (int) $post->post_author !== (int) $user_id || $post->post_type !== 'property') return;

    if (wp_delete_post($pid, true)) {
        wp_safe_redirect(home_url('/dashboard/?deleted=1#properties'));
        exit;
    }
});
