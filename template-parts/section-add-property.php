<?php
/**
 * Template for adding/editing a property
 * Updated: media upload limits from plan, WhatsApp toggle, Google Maps conditional display
 */

if (!is_user_logged_in()) {
    wp_safe_redirect(wp_login_url(get_permalink()));
    exit;
}

$current_user      = wp_get_current_user();
$user_subscription = property_theme_get_user_subscription($current_user->ID);

if ($user_subscription) {

$is_edit     = isset($_GET['property_id']) && intval($_GET['property_id']) > 0;
$property_id = $is_edit ? intval($_GET['property_id']) : 0;
$property    = $is_edit ? get_post($property_id) : null;

if ($is_edit && (!$property || $property->post_author != $current_user->ID || $property->post_type !== 'property')) {
    wp_die('You do not have permission to edit this property.');
}

// ── Pull plan limits & feature flags ──────────────────────────────────────
$plan             = property_theme_get_plan($user_subscription->package_id);
$plan_photo_limit = $plan['photo_limit']       ?? 10;
$plan_video_limit = $plan['video_limit']        ?? 2;
$plan_whatsapp    = $plan['enable_whatsapp']    ?? false;
$plan_google_map  = $plan['enable_google_map']  ?? false;

// ── Existing property data ────────────────────────────────────────────────
$property_data = array(
    'title'            => '',
    'description'      => '',
    'price'            => '',
    'area'             => '',
    'bedrooms'         => '',
    'bathrooms'        => '',
    'city'             => '',
    'address'          => '',
    'latitude'         => '',
    'longitude'        => '',
    'featured'         => 0,
    'property_type'    => '',
    'amenities_data'   => array(),
    'gallery'          => array(),
    'featured_image'   => '',
    'property_numbers' => array(),
);

if ($is_edit && $property) {
    $property_terms     = wp_get_post_terms($property_id, 'property_type');
    $property_bedrooms  = wp_get_post_terms($property_id, 'bedroom');
    $property_bathrooms = wp_get_post_terms($property_id, 'bathroom');
    $property_data = array(
        'title'            => $property->post_title,
        'description'      => $property->post_content,
        'price'            => get_post_meta($property_id, '_property_price', true),
        'area'             => get_post_meta($property_id, '_property_area', true),
        'bedrooms'         => !empty($property_bedrooms)  ? $property_bedrooms[0]->slug  : '',
        'bathrooms'        => !empty($property_bathrooms) ? $property_bathrooms[0]->slug : '',
        'city'             => get_post_meta($property_id, '_property_city', true),
        'address'          => get_post_meta($property_id, '_property_address', true),
        'latitude'         => get_post_meta($property_id, '_property_latitude', true),
        'longitude'        => get_post_meta($property_id, '_property_longitude', true),
        'featured'         => get_post_meta($property_id, '_property_featured', true),
        'property_type'    => !empty($property_terms) ? $property_terms[0]->slug : '',
        'amenities_data'   => get_post_meta($property_id, '_property_amenities_data', true) ?: array(),
        'gallery'          => get_post_meta($property_id, '_property_gallery', true) ?: array(),
        'featured_image'   => get_the_post_thumbnail_url($property_id),
        'property_numbers' => get_post_meta($property_id, '_property_numbers', true) ?: array(),
    );
}

// ── Taxonomy data ─────────────────────────────────────────────────────────
$parent_property_types = get_terms(array('taxonomy' => 'property_type', 'hide_empty' => false, 'parent' => 0));
$property_type_tabs    = array();
foreach ($parent_property_types as $parent) {
    $children              = get_terms(array('taxonomy' => 'property_type', 'hide_empty' => false, 'parent' => $parent->term_id));
    $property_type_tabs[]  = array('parent' => $parent, 'children' => $children);
}

$active_tab_slug = $property_type_tabs[0]['parent']->slug ?? '';
if ($property_data['property_type']) {
    $term = get_term_by('slug', $property_data['property_type'], 'property_type');
    if ($term && $term->parent) {
        $parent_term     = get_term($term->parent, 'property_type');
        $active_tab_slug = $parent_term->slug;
    }
}

function sort_terms_numerically($terms) {
    if (empty($terms) || is_wp_error($terms)) return $terms;
    usort($terms, function($a, $b) { return intval($a->name) - intval($b->name); });
    return $terms;
}

$bedrooms  = sort_terms_numerically(get_terms(array('taxonomy' => 'bedroom',  'hide_empty' => false)));
$bathrooms = sort_terms_numerically(get_terms(array('taxonomy' => 'bathroom', 'hide_empty' => false)));

// Listing status terms (Buy / Rent) + current value when editing
$listing_statuses_form  = get_terms(array('taxonomy' => 'property_listing_status', 'hide_empty' => false));
$current_listing_status = '';
if ($is_edit && $property_id) {
    $ls_terms = wp_get_post_terms($property_id, 'property_listing_status', array('fields' => 'slugs'));
    $current_listing_status = !empty($ls_terms) ? $ls_terms[0] : '';
}

get_jamaica_cities();
global $cities;
$cities_data = $cities;

// ── Count existing gallery by type ───────────────────────────────────────
$existing_photos = 0;
$existing_videos = 0;
foreach ($property_data['gallery'] as $item) {
    if (($item['type'] ?? 'image') === 'video') $existing_videos++;
    else $existing_photos++;
}

// ── Form submission ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_property'])) {
    if (!wp_verify_nonce($_POST['property_form_nonce'] ?? '', 'add_property_nonce')) {
        wp_die('Security check failed: Invalid nonce.');
    }

    // Server-side cap enforcement on NEW submissions.
    // (Edits to existing listings are exempt — they're updates, not new slots.)
    if (!$is_edit && function_exists('property_theme_can_publish_property')) {
        $under_cap = property_theme_can_publish_property($current_user->ID);
        if (!$under_cap && !current_user_can('manage_options')) {
            set_transient('property_form_errors_' . $current_user->ID,
                array('You have reached the property limit for your current plan. Please upgrade or delete an existing listing.'), 30);
            wp_safe_redirect(add_query_arg(['error' => 1]));
            exit;
        }
    }

    $errors = array();
    if (empty(sanitize_text_field($_POST['property_title'] ?? '')))   $errors[] = 'Property title is required.';
    if (empty(sanitize_text_field($_POST['property_price'] ?? '')))   $errors[] = 'Price is required.';
    if (empty(sanitize_text_field($_POST['property_city']  ?? '')))   $errors[] = 'City is required.';
    if (empty(sanitize_text_field($_POST['property_type']  ?? '')))   $errors[] = 'Property type is required.';

    if (!empty($errors)) {
        set_transient('property_form_errors_' . $current_user->ID, $errors, 30);
        $redirect_url = add_query_arg(['error' => 1]);
        if ($is_edit) $redirect_url = add_query_arg('property_id', $property_id, $redirect_url);
        wp_redirect($redirect_url);
        exit;
    }

    // New properties go to 'pending' for admin approval; edits keep their current status
    $existing_status = $is_edit ? get_post_status($property_id) : 'pending';
    // Admins can bypass approval
    $resolved_status = ($is_edit && $existing_status === 'publish') ? 'publish' : (current_user_can('manage_options') ? 'publish' : 'pending');

    $post_data = array(
        'post_type'    => 'property',
        'post_status'  => $resolved_status,
        'post_title'   => sanitize_text_field($_POST['property_title'] ?? 'Untitled Property'),
        'post_content' => sanitize_textarea_field($_POST['property_description'] ?? ''),
    );

    if ($is_edit) {
        $post_data['ID'] = $property_id;
        $post_id         = wp_update_post($post_data);
    } else {
        $post_data['post_author'] = $current_user->ID;
        $post_id                  = wp_insert_post($post_data);
    }

    if (is_wp_error($post_id) || !$post_id) {
        wp_die('Save failed. Check error logs for details.');
    }

    update_post_meta($post_id, '_property_price',     sanitize_text_field($_POST['property_price']    ?? ''));
    update_post_meta($post_id, '_property_area',      sanitize_text_field($_POST['property_area']     ?? ''));
    update_post_meta($post_id, '_property_city',      sanitize_text_field($_POST['property_city']     ?? ''));
    update_post_meta($post_id, '_property_address',   sanitize_text_field($_POST['property_address']  ?? ''));
    update_post_meta($post_id, '_property_latitude',  sanitize_text_field($_POST['property_latitude'] ?? ''));
    update_post_meta($post_id, '_property_longitude', sanitize_text_field($_POST['property_longitude']?? ''));
    update_post_meta($post_id, '_property_featured',  isset($_POST['property_featured']) ? 1 : 0);

    $property_type = sanitize_text_field($_POST['property_type'] ?? '');
    if ($property_type) {
        $term = get_term_by('slug', $property_type, 'property_type');
        if ($term) wp_set_post_terms($post_id, array($term->term_id), 'property_type');
    }

    $property_bedroom = sanitize_text_field($_POST['property_bedroom'] ?? '');
    if ($property_bedroom) wp_set_post_terms($post_id, array($property_bedroom), 'bedroom');

    $property_bathroom = sanitize_text_field($_POST['property_bathroom'] ?? '');
    if ($property_bathroom) wp_set_post_terms($post_id, array($property_bathroom), 'bathroom');

    // Listing Status (Buy / Rent)
    $property_listing_status = sanitize_text_field($_POST['property_listing_status'] ?? '');
    if ($property_listing_status) {
        $ls_term = get_term_by('slug', $property_listing_status, 'property_listing_status');
        if ($ls_term) wp_set_post_terms($post_id, array($ls_term->term_id), 'property_listing_status');
    } else {
        wp_set_object_terms($post_id, array(), 'property_listing_status');
    }

    // Amenities
    $amenities_data = array();
    if (isset($_POST['amenities_groups']) && is_array($_POST['amenities_groups'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

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

    // Contact numbers
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

    // Gallery — respect plan limits
    $gallery          = array();
    $existing_gallery = $is_edit ? get_post_meta($post_id, '_property_gallery', true) ?: array() : array();

    // Count existing media
    $ex_photos = 0; $ex_videos = 0;
    foreach ($existing_gallery as $item) {
        if (($item['type'] ?? 'image') === 'video') $ex_videos++; else $ex_photos++;
    }

    if (isset($_FILES['property_gallery_files']) && isset($_POST['property_gallery_types'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $files = $_FILES['property_gallery_files'];
        $types = $_POST['property_gallery_types'];

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== 0 || empty($files['name'][$i])) continue;

            $file_type   = sanitize_text_field($types[$i] ?? 'image');
            $over_photo  = ($file_type !== 'video' && $ex_photos >= $plan_photo_limit);
            $over_video  = ($file_type === 'video' && $ex_videos >= $plan_video_limit);
            // Skip videos entirely if plan doesn't allow
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

    // Featured image
    if (isset($_FILES['property_featured_image']) && $_FILES['property_featured_image']['size'] > 0) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        $attachment_id = media_handle_upload('property_featured_image', $post_id);
        if (!is_wp_error($attachment_id)) set_post_thumbnail($post_id, $attachment_id);
    }

    $saved_status = get_post_status($post_id);
    $redirect_url = add_query_arg(array(
        'property_id' => $post_id,
        'saved'       => 1,
        'review'      => $saved_status === 'pending' ? 1 : 0,
    ));
    wp_safe_redirect($redirect_url);
    exit;
}

// Property deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['property_id'])) {
    $property_id_to_delete = intval($_GET['property_id']);
    $property_to_delete    = get_post($property_id_to_delete);
    if ($property_to_delete && $property_to_delete->post_author == $current_user->ID && $property_to_delete->post_type === 'property') {
        if (wp_delete_post($property_id_to_delete, true)) {
            wp_redirect(get_permalink(get_option('page_on_front')) . '?deleted=1');
            exit;
        }
    }
}
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-12 px-4">
    <div class="max-w-6xl mx-auto">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-slate-900 mb-2 font-inter">
                <?php echo $is_edit ? 'Edit Property' : 'Add New Property'; ?>
            </h1>
            <p class="text-slate-600 font-inter">Fill in the details below to <?php echo $is_edit ? 'update' : 'create'; ?> your property listing</p>
        </div>

        <!-- ── Plan Media Limits Banner ───────────────────────────────────────── -->
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl flex flex-wrap gap-4 items-center">
            <div class="flex items-center gap-2 text-blue-800 text-sm font-semibold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Your <strong><?php echo esc_html($plan['name'] ?? 'Current'); ?></strong> plan allows:
            </div>
            <div class="flex flex-wrap gap-3">
                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                    📸 <?php echo $plan_photo_limit; ?> photos per listing
                    <?php if ($is_edit): ?>
                        <span class="text-blue-600">(<?php echo $existing_photos; ?> used)</span>
                    <?php endif; ?>
                </span>
                <?php if ($plan_video_limit > 0): ?>
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                        🎬 <?php echo $plan_video_limit; ?> videos per listing
                        <?php if ($is_edit): ?>
                            <span class="text-blue-600">(<?php echo $existing_videos; ?> used)</span>
                        <?php endif; ?>
                    </span>
                <?php else: ?>
                    <span class="px-3 py-1 bg-gray-100 text-gray-500 rounded-full text-sm font-medium line-through">🎬 No videos</span>
                <?php endif; ?>
                <?php if ($plan_whatsapp): ?>
                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">💬 WhatsApp</span>
                <?php endif; ?>
                <?php if ($plan_google_map): ?>
                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">📍 Map Location</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($_GET['saved'])): ?>
            <?php if (!empty($_GET['review'])): ?>
                <div class="mb-8 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <p class="text-amber-900 font-semibold">✓ Property submitted for review</p>
                    <p class="text-amber-800 text-sm mt-1">Your listing was saved and is now <strong>pending admin approval</strong>. We'll email you once it's approved and live on the site.</p>
                </div>
            <?php else: ?>
                <div class="mb-8 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-green-800 font-semibold">✓ Property saved successfully!</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Error Messages -->
        <?php
        $user_errors = get_transient('property_form_errors_' . $current_user->ID);
        delete_transient('property_form_errors_' . $current_user->ID);
        if (isset($_GET['error']) && $user_errors): ?>
        <div class="mb-8 p-4 bg-red-50 border border-red-200 rounded-lg">
            <ul class="text-red-800">
                <?php foreach ($user_errors as $error): ?>
                    <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Submission overlay (full-screen so user can't double-submit / think it froze) -->
        <div x-data="{ submitting: false }"
             x-on:submit.window="submitting = true"
             x-show="submitting"
             x-cloak
             class="fixed inset-0 z-[9999] bg-slate-900/60 flex flex-col items-center justify-center gap-4">
            <div class="w-16 h-16 border-[5px] border-white border-t-transparent rounded-full animate-spin"></div>
            <p class="text-white font-semibold text-base">
                <?php echo $is_edit ? 'Saving your changes…' : 'Creating your listing…'; ?>
            </p>
            <p class="text-slate-300 text-sm">Please don't close this tab. Uploading media may take a moment.</p>
        </div>

        <form method="POST" enctype="multipart/form-data"
              class="grid grid-cols-1 lg:grid-cols-3 gap-8"
              @submit="$el.querySelector('button[name=submit_property]')?.setAttribute('disabled','disabled')"
              x-data="propertyForm(
                  <?php echo htmlspecialchars(json_encode($property_data)); ?>,
                  <?php echo htmlspecialchars(json_encode($type_specific_fields ?? [])); ?>,
                  <?php echo htmlspecialchars(json_encode($cities_data)); ?>,
                  <?php echo (int)$plan_photo_limit; ?>,
                  <?php echo (int)$plan_video_limit; ?>,
                  <?php echo $plan_whatsapp   ? 'true' : 'false'; ?>,
                  <?php echo $plan_google_map ? 'true' : 'false'; ?>
              )">
            <?php wp_nonce_field('add_property_nonce', 'property_form_nonce'); ?>

            <!-- ════════════════════════════════ MAIN CONTENT ════════════════════════════════ -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Property Type Card -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h4 class="text-lg font-semibold font-inter mb-6">Select Property Type</h4>
                    <div x-data="{ activeTab: '<?php echo esc_js($active_tab_slug); ?>' }">
                        <div class="flex gap-2 border-b border-slate-200 mb-6">
                            <?php foreach ($property_type_tabs as $tab): ?>
                            <button type="button" @click="activeTab = '<?php echo esc_js($tab['parent']->slug); ?>'"
                                :class="activeTab === '<?php echo esc_js($tab['parent']->slug); ?>' ? 'border-[var(--primary-color)] text-[var(--primary-color)]' : 'border-transparent text-slate-600'"
                                class="px-4 py-2 font-semibold border-b-2 transition">
                                <?php echo esc_html($tab['parent']->name); ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <?php foreach ($property_type_tabs as $tab): ?>
                        <div x-show="activeTab === '<?php echo esc_js($tab['parent']->slug); ?>'" x-transition class="flex flex-wrap gap-3">
                            <?php foreach ($tab['children'] as $child):
                                $icon_svg = get_field('icons', 'property_type_' . $child->term_id); ?>
                            <label class="flex items-center gap-2 px-4 py-2 rounded-full cursor-pointer border transition"
                                :class="selectedPropertyType === '<?php echo esc_js($child->slug); ?>' ? 'shadow-sm' : ''"
                                :style="selectedPropertyType === '<?php echo esc_js($child->slug); ?>'
                                    ? 'background-color:var(--primary-color);border-color:var(--primary-color);color:#fff;'
                                    : 'background-color:#f8fafc;border-color:var(--secondary-color);color:var(--text-primary-color);'">
                                <input type="radio" name="property_type" value="<?php echo esc_attr($child->slug); ?>"
                                    x-model="selectedPropertyType" class="hidden">
                                <?php if ($icon_svg): ?>
                                <span class="w-5 h-5"
                                    :style="selectedPropertyType === '<?php echo esc_js($child->slug); ?>' ? 'fill:#fff' : 'fill:var(--primary-color)'">
                                    <?php echo $icon_svg; ?>
                                </span>
                                <?php endif; ?>
                                <span class="text-sm font-semibold whitespace-nowrap"><?php echo esc_html($child->name); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Property Purpose Card (Buy / Rent) -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h4 class="text-lg font-semibold font-inter mb-1">Property Purpose</h4>
                    <p class="text-sm text-slate-500 mb-4 font-inter">Is this property listed for sale or for rent?</p>

                    <!-- Tab-style buttons -->
                    <div class="flex rounded-xl overflow-hidden border border-slate-200 w-fit shadow-sm">
                        <?php if (!is_wp_error($listing_statuses_form) && !empty($listing_statuses_form)):
                            foreach ($listing_statuses_form as $ls): ?>
                        <button type="button"
                            @click="selectedListingStatus = '<?php echo esc_js($ls->slug); ?>'"
                            class="px-8 py-2.5 text-sm font-bold font-inter transition-all duration-200 focus:outline-none"
                            :class="selectedListingStatus === '<?php echo esc_js($ls->slug); ?>'
                                ? 'bg-[var(--primary-color)] text-white'
                                : 'bg-slate-50 text-slate-600 hover:bg-slate-100 hover:text-[var(--primary-color)]'">
                            <?php if ($ls->slug === 'buy'): ?>
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    For Sale
                                </span>
                            <?php else: ?>
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    For Rent
                                </span>
                            <?php endif; ?>
                        </button>
                        <?php endforeach; endif; ?>
                    </div>

                    <!-- Hidden input that submits with the form -->
                    <input type="hidden" name="property_listing_status" :value="selectedListingStatus">

                    <!-- Helper text -->
                    <p class="mt-3 text-xs text-slate-400 font-inter"
                        x-text="selectedListingStatus === 'buy' ? 'This property will appear in Buy / For Sale listings.' : selectedListingStatus === 'rent' ? 'This property will appear in Rent / For Rent listings.' : 'Please select a purpose above.'">
                    </p>
                </div>

                <!-- Basic Info Card -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-lg font-semibold mb-6">Basic Information</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Property Title *</label>
                            <input type="text" name="property_title" x-model="propertyData.title" required
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-slate-900">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Description</label>
                            <textarea name="property_description" rows="5" x-model="propertyData.description"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-slate-900"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">Price ($) *</label>
                                <input type="number" name="property_price" x-model="propertyData.price" step="0.01" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-slate-900">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">Area (sq ft)</label>
                                <input type="number" name="property_area" x-model="propertyData.area"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-slate-900">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Location Card — conditional Google Maps ──────────────────────────── -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-2">Location</h2>

                    <?php if (!$plan_google_map): ?>
                    <!-- Plan doesn't include Google Maps — show upgrade notice -->
                    <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-amber-800 font-semibold text-sm">Map location not included in your plan</p>
                            <p class="text-amber-700 text-xs mt-1">Upgrade your subscription to display a Google Maps pin on your listing.
                                <a href="<?php echo home_url('/dashboard/#billing'); ?>" class="underline font-semibold">Upgrade now →</a>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="space-y-4">
                        <!-- Parish dropdown always visible -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Parish *</label>
                            <select name="property_city" x-model="selectedCity"
                                @change="planGoogleMap && updateCityLocation()"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-slate-900" required>
                                <option value="">Select a parish…</option>
                                <template x-for="(coords, city) in citiesData" :key="city">
                                    <option :value="city" x-text="city"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Address input — always visible -->
                        <div class="relative">
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Address *</label>
                            <input type="text" id="address-input" name="property_address"
                                x-model="propertyData.address"
                                :placeholder="planGoogleMap ? 'Search address in selected city' : 'Enter your property address'"
                                :disabled="planGoogleMap && !selectedCity" required
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-slate-900 disabled:bg-gray-100">
                            <!-- Autocomplete dropdown (Google Maps plans only) -->
                            <div x-show="planGoogleMap"
                                id="autocomplete-dropdown"
                                class="absolute top-full left-0 right-0 bg-white border border-slate-300 rounded-lg shadow-lg mt-1 hidden max-h-48 overflow-y-auto z-50">
                            </div>
                            <p x-show="planGoogleMap" class="text-xs mt-1 text-slate-500 p-2">
                                Start typing to see address suggestions powered by Google Places.
                            </p>
                        </div>

                        <!-- Lat/Lng inputs — only shown for Google Maps plans -->
                        <template x-if="planGoogleMap">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-900 mb-2">Latitude</label>
                                    <input type="text" name="property_latitude" id="latitude-input"
                                        x-model="propertyData.latitude" readonly
                                        class="w-full px-4 py-2 border border-slate-300 rounded-lg bg-slate-50 text-slate-900">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-900 mb-2">Longitude</label>
                                    <input type="text" name="property_longitude" id="longitude-input"
                                        x-model="propertyData.longitude" readonly
                                        class="w-full px-4 py-2 border border-slate-300 rounded-lg bg-slate-50 text-slate-900">
                                </div>
                            </div>
                        </template>

                        <!-- Map — only rendered for Google Maps plans -->
                        <div x-show="planGoogleMap"
                            id="property-map"
                            class="w-full h-80 rounded-lg border border-slate-300 mt-4 bg-slate-100">
                        </div>
                    </div>
                </div>

                <!-- ── Contact Numbers — conditional WhatsApp ──────────────────────────── -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Contact Numbers</h2>
                            <p class="text-sm text-slate-600 mt-1">
                                Add contact numbers for inquiries
                                <?php if (!$plan_whatsapp): ?>
                                <span class="text-amber-600 font-medium">
                                    — <a href="<?php echo home_url('/dashboard/#billing'); ?>" class="underline">Upgrade</a> to add WhatsApp
                                </span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if ($plan_whatsapp): ?>
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">💬 WhatsApp enabled</span>
                        <?php endif; ?>
                    </div>

                    <div x-data="contactNumbers(
                            <?php echo htmlspecialchars(json_encode($property_data['property_numbers'] ?? [])); ?>,
                            <?php echo $plan_whatsapp ? 'true' : 'false'; ?>
                         )" class="space-y-4">

                        <div id="numbers-list" class="space-y-3">
                            <template x-for="(number, index) in numbers" :key="index">
                                <div class="flex gap-3 items-end p-4 bg-slate-50 rounded-lg border border-slate-200">
                                    <div class="flex-shrink-0">
                                        <label class="block text-xs font-semibold text-slate-700 mb-2">Type</label>
                                        <select x-model="number.type" :name="`property_numbers[${index}][type]`"
                                            class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-slate-900 bg-white">
                                            <option value="num">📱 Phone</option>
                                            <option value="whats" x-bind:disabled="!planWhatsapp">
                                                💬 WhatsApp<span x-show="!planWhatsapp"> (upgrade required)</span>
                                            </option>
                                        </select>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-xs font-semibold text-slate-700 mb-2">Number</label>
                                        <input type="tel" x-model="number.number" :name="`property_numbers[${index}][number]`"
                                            placeholder="e.g., +1 876 123 4567"
                                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-slate-900">
                                    </div>
                                    <button type="button" @click="removeNumber(index)"
                                        class="px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 font-semibold text-sm transition">
                                        Remove
                                    </button>
                                </div>
                            </template>
                        </div>

                        <button type="button" @click="addNumber()"
                            class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Number
                        </button>
                    </div>
                </div>

                <!-- Amenities Groups -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-lg font-semibold text-slate-900 mb-6">Features & Amenities</h2>

                    <!-- Bedrooms -->
                    <div class="flex flex-wrap gap-4 mb-6 items-start">
                        <div class="flex items-center p-2 justify-center rounded-lg bg-gray-200 text-gray-900">
                            <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1.2em" width="1.2em" xmlns="http://www.w3.org/2000/svg"><path fill="none" d="M0 0h24v24H0z"></path><path d="M12 3 1 11.4l1.21 1.59L4 11.62V21h16v-9.38l1.79 1.36L23 11.4 12 3zm6 16H6v-8.9l6-4.58 6 4.58V19zm-9-5c0 .55-.45 1-1 1s-1-.45-1-1 .45-1 1-1 1 .45 1 1zm3-1c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm3 1c0-.55.45-1 1-1s1 .45 1 1-.45 1-1 1-1-.45-1-1z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-md font-semibold text-slate-900 mb-2">Bedrooms</h3>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($bedrooms as $bedroom): ?>
                                <label class="flex items-center justify-center px-4 py-2 gap-2 text-sm rounded-full cursor-pointer border transition"
                                    :class="selectedBedroom === '<?php echo esc_js($bedroom->slug); ?>'
                                        ? 'shadow-sm bg-[var(--primary-color)] border-[var(--primary-color)] text-white'
                                        : 'bg-[#f8fafc] border-[var(--secondary-color)] text-[var(--text-primary-color)]'">
                                    <input type="radio" name="property_bedroom" value="<?php echo esc_attr($bedroom->slug); ?>"
                                        x-model="selectedBedroom" class="hidden">
                                    <span class="text-sm font-semibold whitespace-nowrap"><?php echo esc_html($bedroom->name); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Bathrooms -->
                    <div class="flex flex-wrap gap-4 mb-6 items-start">
                        <div class="flex items-center p-2 justify-center rounded-lg bg-gray-200 text-gray-900">
                            <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1.2em" width="1.2em" xmlns="http://www.w3.org/2000/svg"><path d="M21 10H7V7c0-1.103.897-2 2-2s2 .897 2 2h2c0-2.206-1.794-4-4-4S5 4.794 5 7v3H3a1 1 0 0 0-1 1v2c0 2.606 1.674 4.823 4 5.65V22h2v-3h8v3h2v-3.35c2.326-.827 4-3.044 4-5.65v-2a1 1 0 0 0-1-1zm-1 3c0 2.206-1.794 4-4 4H8c-2.206 0-4-1.794-4-4v-1h16v1z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-md font-semibold text-slate-900 mb-2">Bathrooms</h3>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($bathrooms as $bathroom): ?>
                                <label class="flex items-center gap-2 px-4 py-2 rounded-full cursor-pointer border transition"
                                    :class="selectedBathroom === '<?php echo esc_js($bathroom->slug); ?>'
                                        ? 'shadow-sm bg-[var(--primary-color)] border-[var(--primary-color)] text-white'
                                        : 'bg-[#f8fafc] border-[var(--secondary-color)] text-[var(--text-primary-color)]'">
                                    <input type="radio" name="property_bathroom" value="<?php echo esc_attr($bathroom->slug); ?>"
                                        x-model="selectedBathroom" class="hidden">
                                    <span class="text-sm font-semibold whitespace-nowrap"><?php echo esc_html($bathroom->name); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Feature & Amenities modal trigger -->
                    <div class="flex gap-4 mb-6 items-start">
                        <div class="flex items-center p-2 justify-center rounded-lg bg-gray-200 text-gray-900">
                            <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1.2em" width="1.2em" xmlns="http://www.w3.org/2000/svg"><path fill="none" d="M0 0h24v24H0z"></path><path d="M12 3 1 11.4l1.21 1.59L4 11.62V21h16v-9.38l1.79 1.36L23 11.4 12 3zm6 16H6v-8.9l6-4.58 6 4.58V19zm-9-5c0 .55-.45 1-1 1s-1-.45-1-1 .45-1 1-1 1 .45 1 1zm3-1c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm3 1c0-.55.45-1 1-1s1 .45 1 1-.45 1-1 1-1-.45-1-1z"></path></svg>
                        </div>
                        <div class="flex justify-between gap-6 items-end" x-data="{ openModal: false }">
                            <div>
                                <h3 class="text-md font-semibold text-slate-900 mb-2">Features and Amenities</h3>
                                <p class="text-sm text-[var(--text-primary-color)] mb-2">Add features e.g. parking, internet, etc.</p>
                            </div>
                            <button type="button" @click="openModal = !openModal"
                                class="px-4 py-2 bg-[var(--primary-color)] text-white rounded-lg font-semibold text-sm">+ Add Amenities</button>

                            <!-- AMENITIES MODAL -->
                            <div x-data="{
                                    open: true, activeTab: 0,
                                    groups: <?= esc_js(json_encode($property_data['amenities_data'] ?: [['title'=>'Main Features','amenities'=>[]]])) ?>,
                                    addGroup() { this.groups.push({title:'',amenities:[]}); this.activeTab=this.groups.length-1; },
                                    addAmenity() { this.groups[this.activeTab].amenities.push({title:'',icon:''}); },
                                    removeAmenity(i) { this.groups[this.activeTab].amenities.splice(i,1); },
                                    uploadIcon(g,a,e) { const f=e.target.files[0]; if(f) this.groups[g].amenities[a].icon=URL.createObjectURL(f); }
                                }" x-show="openModal" x-transition
                                class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
                                <div class="bg-white w-[70%] h-[85vh] rounded-xl shadow-xl flex flex-col overflow-hidden">
                                    <div class="flex items-center justify-between px-6 py-4 border-b">
                                        <h2 class="text-lg font-semibold">Feature and Amenities</h2>
                                        <button @click="openModal=false" class="text-slate-500 hover:text-slate-800 text-2xl leading-none">×</button>
                                    </div>
                                    <div class="flex flex-1 overflow-hidden">
                                        <div class="w-64 border-r bg-slate-50 overflow-y-auto">
                                            <template x-for="(group,gIndex) in groups" :key="gIndex">
                                                <button type="button" @click="activeTab=gIndex"
                                                    class="w-full text-left px-4 py-3 border-b text-sm font-medium transition"
                                                    :class="activeTab===gIndex?'bg-white text-[var(--primary-color)] border-l-4 border-[var(--primary-color)]':'text-slate-600 hover:bg-slate-100'">
                                                    <span x-text="group.title||'Untitled Group'"></span>
                                                </button>
                                            </template>
                                            <button type="button" @click="addGroup()" class="w-full px-4 py-3 text-left text-sm font-medium text-[var(--primary-color)] hover:bg-white border-t">+ Add Group</button>
                                        </div>
                                        <div class="flex-1 p-6 overflow-y-auto">
                                            <template x-if="groups[activeTab]">
                                                <div class="space-y-6">
                                                    <input type="text" x-model="groups[activeTab].title" :name="`amenities_groups[${activeTab}][title]`"
                                                        class="w-full text-lg font-semibold px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[var(--primary-color)] outline-none" placeholder="Group Title">
                                                    <div class="grid grid-cols-2 gap-4">
                                                        <template x-for="(amenity,aIndex) in groups[activeTab].amenities" :key="aIndex">
                                                            <div class="flex items-center gap-3 p-4 border rounded-lg bg-white">
                                                                <div class="w-10 h-10 bg-slate-100 rounded flex items-center justify-center relative">
                                                                    <template x-if="amenity.icon"><img :src="amenity.icon" class="w-8 h-8 object-contain"></template>
                                                                    <template x-if="!amenity.icon">📎</template>
                                                                    <input type="file" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer"
                                                                        :name="`amenities_groups[${activeTab}][amenities][${aIndex}][icon_file]`"
                                                                        @change="uploadIcon(activeTab,aIndex,$event)">
                                                                </div>
                                                                <input type="text" x-model="amenity.title"
                                                                    :name="`amenities_groups[${activeTab}][amenities][${aIndex}][title]`"
                                                                    class="flex-1 px-3 py-2 text-sm border rounded-md" placeholder="Amenity name">
                                                                <input type="hidden" x-model="amenity.icon" :name="`amenities_groups[${activeTab}][amenities][${aIndex}][icon]`">
                                                                <button type="button" @click="removeAmenity(aIndex)" class="text-red-500 hover:text-red-700 text-lg">×</button>
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <button type="button" @click="addAmenity()" class="px-4 py-2 rounded-lg text-sm font-medium text-white bg-[var(--secondary-color)] hover:opacity-90">+ Add Amenity</button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-3 px-6 py-4 border-t">
                                        <button type="button" @click="openModal=false" class="px-4 py-2 rounded-lg border text-sm">Cancel</button>
                                        <button type="button" @click="openModal=false" class="px-5 py-2 rounded-lg text-sm font-medium text-white bg-[var(--primary-color)]">Save Amenities</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Gallery Section with plan-based limits ───────────────────────────── -->
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                    <div class="flex items-start justify-between mb-4">
                        <h2 class="text-xl font-bold text-slate-900">Gallery</h2>
                        <!-- Live counter badges -->
                        <div class="flex gap-2 text-xs font-semibold">
                            <span class="px-2 py-1 rounded-full"
                                :class="photoCount >= planPhotoLimit ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
                                x-text="`📸 ${photoCount}/${planPhotoLimit} photos`">
                            </span>
                            <template x-if="planVideoLimit > 0">
                                <span class="px-2 py-1 rounded-full"
                                    :class="videoCount >= planVideoLimit ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
                                    x-text="`🎬 ${videoCount}/${planVideoLimit} videos`">
                                </span>
                            </template>
                            <template x-if="planVideoLimit === 0">
                                <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-500">🎬 No videos on your plan</span>
                            </template>
                        </div>
                    </div>

                    <!-- Limit warning -->
                    <div x-show="photoCount >= planPhotoLimit"
                        class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-sm font-medium">
                        ⚠️ You've reached the photo limit for your plan. Remove a photo to add another, or
                        <a href="<?php echo home_url('/dashboard/#billing'); ?>" class="underline">upgrade your plan</a>.
                    </div>

                    <div id="gallery-list" class="grid grid-cols-2 gap-4 mb-4">
                        <?php if (!empty($property_data['gallery'])): ?>
                            <?php foreach ($property_data['gallery'] as $index => $item): ?>
                            <div class="gallery-row relative p-4 bg-slate-50 rounded-lg border border-slate-300">
                                <?php if ($item['type'] === 'image'): ?>
                                <img src="<?php echo esc_url($item['media_url']); ?>" alt="Gallery" class="w-full h-48 object-cover rounded gallery-preview">
                                <?php else: ?>
                                <div class="w-full h-48 bg-black rounded flex items-center justify-center text-white">VIDEO</div>
                                <?php endif; ?>
                                <input type="file" name="property_gallery_files[]" accept="<?php echo $plan_video_limit > 0 ? 'image/*,video/*' : 'image/*'; ?>" class="gallery-file hidden">
                                <select name="property_gallery_types[]" class="gallery-type hidden">
                                    <option value="<?php echo esc_attr($item['type']); ?>" selected><?php echo esc_html(ucfirst($item['type'])); ?></option>
                                </select>
                                <div class="mt-2 flex gap-2">
                                    <button type="button" class="flex-1 px-2 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 upload-gallery-media">Replace Media</button>
                                </div>
                                <button type="button" class="absolute top-2 right-2 px-2 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700 remove-gallery">×</button>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <button type="button" id="add-gallery-btn"
                        class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold"
                        :disabled="photoCount >= planPhotoLimit"
                        :class="{'opacity-50 cursor-not-allowed': photoCount >= planPhotoLimit}">
                        + Add Media
                    </button>
                    <p class="text-xs text-slate-500 mt-2 text-center">
                        <?php if ($plan_video_limit > 0): ?>
                            Accepted: Images & Videos · Limits: <?php echo $plan_photo_limit; ?> photos, <?php echo $plan_video_limit; ?> videos
                        <?php else: ?>
                            Accepted: Images only · Limit: <?php echo $plan_photo_limit; ?> photos
                        <?php endif; ?>
                    </p>
                </div>
            </div><!-- /main content -->

            <!-- ════════════════════════════════ SIDEBAR ════════════════════════════════ -->
            <div class="lg:col-span-1">
                <!-- Featured Image -->
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-6 sticky top-24">
                    <h3 class="font-bold text-slate-900 mb-4">Featured Image</h3>
                    <div id="featured-image-preview" class="w-full bg-slate-100 rounded-lg mb-4 overflow-hidden">
                        <?php if ($property_data['featured_image']): ?>
                        <img src="<?php echo esc_url($property_data['featured_image']); ?>" alt="Featured" class="w-full h-48 object-cover">
                        <?php else: ?>
                        <div class="w-full h-48 flex items-center justify-center text-slate-400">No image</div>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="upload-featured-image"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Upload Image</button>
                    <input type="file" name="property_featured_image" id="featured-image-input" style="display:none;" accept="image/*">
                </div>

                <?php if (($plan['featured_limit'] ?? 0) > 0): ?>
                <!-- Featured Listing -->
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-6">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="property_featured" value="1"
                            <?php checked($property_data['featured'], 1); ?> class="w-5 h-5 rounded border-slate-300">
                        <span class="font-semibold text-slate-900">Featured Listing</span>
                    </label>
                    <p class="text-sm text-slate-600 mt-2">Highlight your property on homepage</p>
                </div>
                <?php endif; ?>

                <!-- Submit Buttons -->
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 space-y-3"
                     x-data="{ saving: false }"
                     x-on:submit.window="saving = true">
                    <button type="submit" name="submit_property"
                        :disabled="saving"
                        class="w-full px-4 py-3 bg-[var(--primary-color)] text-white rounded-lg hover:bg-blue-700 font-bold disabled:opacity-70 disabled:cursor-wait flex items-center justify-center gap-2">
                        <svg x-show="saving" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                            <path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4zm2 5.3A8 8 0 014 12H0c0 3 1.1 5.8 3 8l3-2.7z"></path>
                        </svg>
                        <span x-text="saving ? '<?php echo $is_edit ? 'Saving…' : 'Creating…'; ?>' : '<?php echo $is_edit ? 'Update Property' : 'Create Property'; ?>'"></span>
                    </button>
                    <a href="<?php echo home_url('/dashboard'); ?>"
                        class="block text-center px-4 py-3 bg-slate-200 text-slate-900 rounded-lg hover:bg-slate-300 font-semibold">Back to Dashboard</a>
                    <?php if ($is_edit): ?>
                    <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'delete', 'property_id' => $property_id)), 'delete_property', 'nonce'); ?>"
                        onclick="return confirm('Delete this property?');"
                        class="block text-center px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold">Delete Property</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════ SCRIPTS ═══════════════════════════════ -->
<script>
function contactNumbers(initialNumbers, planWhatsapp) {
    let numbers = [];
    if (initialNumbers && typeof initialNumbers === 'object') {
        if (initialNumbers.whats && Array.isArray(initialNumbers.whats)) {
            initialNumbers.whats.forEach(num => { if (num) numbers.push({ type: 'whats', number: num }); });
        }
        if (initialNumbers.num && Array.isArray(initialNumbers.num)) {
            initialNumbers.num.forEach(num => { if (num) numbers.push({ type: 'num', number: num }); });
        }
    }
    if (numbers.length === 0) numbers.push({ type: 'num', number: '' });

    return {
        numbers,
        planWhatsapp,
        addNumber() { this.numbers.push({ type: 'num', number: '' }); },
        removeNumber(index) { if (this.numbers.length > 1) this.numbers.splice(index, 1); }
    };
}

function propertyForm(initialData, typeSpecificFieldsData, citiesData, planPhotoLimit, planVideoLimit, planWhatsapp, planGoogleMap) {
    return {
        propertyData:        initialData,
        typeSpecificFields:  [],
        propertyTypeFields:  typeSpecificFieldsData,
        citiesData,
        selectedPropertyType: initialData.property_type || '',
        selectedBedroom:      initialData.bedrooms  || '',
        selectedBathroom:     initialData.bathrooms || '',
        selectedListingStatus: initialData.listing_status || 'buy',
        selectedCity:         initialData.city      || '',

        // Plan feature flags
        planPhotoLimit,
        planVideoLimit,
        planWhatsapp,
        planGoogleMap,

        // Gallery counters (kept in sync by JS)
        photoCount: <?php echo $existing_photos; ?>,
        videoCount: <?php echo $existing_videos; ?>,

        map: null, marker: null, autocompleteListener: null,
        showAmenitiesModal: false,

        updateCityLocation() {
            if (!this.planGoogleMap) return;
            if (this.selectedCity && this.citiesData[this.selectedCity]) {
                const coords = this.citiesData[this.selectedCity];
                this.propertyData.latitude  = coords.lat;
                this.propertyData.longitude = coords.lng;
                this.initializeMap(coords.lat, coords.lng);
                this.setCoordinates(coords.lat, coords.lng, 'City Update');
            }
        },

        initializeMap(lat, lng) {
            if (!this.planGoogleMap) return;
            const mapElement = document.getElementById('property-map');
            if (!mapElement || !window.google || !window.google.maps) return;

            if (this.map === null) {
                mapElement.style.width  = '100%';
                mapElement.style.height = '400px';
                this.map = new google.maps.Map(mapElement, {
                    zoom: 14, center: { lat, lng },
                    mapTypeControl: false, zoomControl: true,
                    mapId: 'c484b19c4f8c16ebb3dcf3d1'
                });
                this.map.addListener('click', (e) => {
                    this.setCoordinates(e.latLng.lat(), e.latLng.lng(), 'Map Click');
                });
            } else {
                this.map.setCenter({ lat, lng });
                this.map.setZoom(14);
            }
            this.initializeAutocomplete();
        },

        setCoordinates(lat, lng, source) {
            if (!this.planGoogleMap) return;
            this.propertyData.latitude  = lat;
            this.propertyData.longitude = lng;

            const iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 60 60"><g><path fill="#95a5a5" d="M33 32.72v21.39a3.016 3.016 0 0 1-.43 1.55l-1.71 2.85a1 1 0 0 1-1.72 0l-1.71-2.85a3.016 3.016 0 0 1-.43-1.55V32.72z"/><path fill="#c03a2b" d="M46 17a15.98 15.98 0 1 1-6.44-12.84A16 16 0 0 1 46 17z"/></g></svg>`;

            if (this.marker) {
                this.marker.setPosition({ lat, lng });
            } else {
                this.marker = new google.maps.Marker({
                    position: { lat, lng }, map: this.map,
                    title: 'Property Location',
                    icon: {
                        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(iconSvg),
                        scaledSize: new google.maps.Size(32, 32),
                        anchor: new google.maps.Point(16, 16),
                    },
                    draggable: true,
                });
                this.marker.addListener('dragend', () => {
                    const pos = this.marker.getPosition();
                    this.propertyData.latitude  = pos.lat();
                    this.propertyData.longitude = pos.lng();
                });
            }
            if (this.map) this.map.panTo({ lat, lng });
        },

        initializeAutocomplete() {
            if (!this.planGoogleMap) return;
            const input = document.getElementById('address-input');
            if (!input || !this.selectedCity) return;

            if (this.autocompleteListener) {
                input.removeEventListener('input', this.autocompleteListener);
                this.autocompleteListener = null;
            }

            const geocoder = new google.maps.Geocoder();
            let debounceTimer = null;

            this.autocompleteListener = (e) => {
                clearTimeout(debounceTimer);
                if (input.value.length > 2) {
                    debounceTimer = setTimeout(() => {
                        geocoder.geocode(
                            { address: input.value + ', ' + this.selectedCity, componentRestrictions: { country: 'jm' } },
                            (results, status) => {
                                const dropdown = document.getElementById('autocomplete-dropdown');
                                dropdown.innerHTML = '';
                                dropdown.classList.add('hidden');
                                if (status === 'OK' && results && results.length > 0) {
                                    results.slice(0, 5).forEach(place => {
                                        const div = document.createElement('div');
                                        div.className = 'px-4 py-2 cursor-pointer hover:bg-slate-100';
                                        div.textContent = place.formatted_address;
                                        div.addEventListener('click', () => {
                                            this.propertyData.address = place.formatted_address;
                                            const lat = place.geometry.location.lat();
                                            const lng = place.geometry.location.lng();
                                            this.setCoordinates(lat, lng, 'Autocomplete');
                                            this.map.setZoom(16);
                                            dropdown.classList.add('hidden');
                                            input.value = place.formatted_address;
                                        });
                                        dropdown.appendChild(div);
                                    });
                                    dropdown.classList.remove('hidden');
                                }
                            }
                        );
                    }, 300);
                } else {
                    const dropdown = document.getElementById('autocomplete-dropdown');
                    if (dropdown) dropdown.classList.add('hidden');
                }
            };
            input.addEventListener('input', this.autocompleteListener);
        },

        init() {
            if (this.planGoogleMap) {
                if (this.selectedCity) {
                    this.updateCityLocation();
                } else if (this.propertyData.latitude && this.propertyData.longitude) {
                    const lat = parseFloat(this.propertyData.latitude);
                    const lng = parseFloat(this.propertyData.longitude);
                    this.initializeMap(lat, lng);
                    this.setCoordinates(lat, lng, 'Init');
                }
            }
        }
    };
}
</script>

<!-- Gallery & Featured Image handlers -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Featured image upload ─────────────────────────────────────────
    const uploadBtn = document.getElementById('upload-featured-image');
    const fileInput = document.getElementById('featured-image-input');
    if (uploadBtn && fileInput) {
        uploadBtn.addEventListener('click', e => { e.preventDefault(); fileInput.click(); });
        fileInput.addEventListener('change', function () {
            if (!this.files || !this.files[0]) return;
            const file = this.files[0];
            if (!file.type.startsWith('image/')) { alert('Please select a valid image file.'); this.value = ''; return; }
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('featured-image-preview').innerHTML =
                    `<img src="${e.target.result}" alt="Preview" class="w-full h-48 object-cover">`;
            };
            reader.readAsDataURL(file);
        });
    }

    // ── Gallery helpers ───────────────────────────────────────────────
    const planPhotoLimit = <?php echo (int)$plan_photo_limit; ?>;
    const planVideoLimit = <?php echo (int)$plan_video_limit; ?>;

    function getGalleryCounts() {
        const rows = document.querySelectorAll('#gallery-list .gallery-row');
        let photos = 0, videos = 0;
        rows.forEach(row => {
            const typeSelect = row.querySelector('.gallery-type');
            if (typeSelect && typeSelect.value === 'video') videos++;
            else photos++;
        });
        return { photos, videos };
    }

    function updateAlpineCounts() {
        const { photos, videos } = getGalleryCounts();
        // Update Alpine reactive data via dispatch
        const form = document.querySelector('form[x-data*="propertyForm"]');
        if (form && form._x_dataStack) {
            const data = form._x_dataStack[0];
            if (data) { data.photoCount = photos; data.videoCount = videos; }
        }
    }

    const addGalleryBtn = document.getElementById('add-gallery-btn');
    const galleryList   = document.getElementById('gallery-list');

    if (addGalleryBtn) {
        addGalleryBtn.addEventListener('click', e => {
            e.preventDefault();
            const { photos } = getGalleryCounts();
            if (photos >= planPhotoLimit) {
                alert(`You've reached your plan's limit of ${planPhotoLimit} photos. Please remove a photo first or upgrade your plan.`);
                return;
            }
            const accept = planVideoLimit > 0 ? 'image/*,video/*' : 'image/*';
            const html = `
                <div class="gallery-row relative p-4 bg-slate-50 rounded-lg border border-slate-300 overflow-hidden">
                    <div class="w-full h-48 bg-slate-200 rounded flex items-center justify-center text-slate-400 gallery-preview">No media</div>
                    <input type="file" name="property_gallery_files[]" accept="${accept}" class="gallery-file hidden">
                    <select name="property_gallery_types[]" class="gallery-type hidden">
                        <option value="image">Image</option>
                        ${planVideoLimit > 0 ? '<option value="video">Video</option>' : ''}
                    </select>
                    <div class="mt-2 flex gap-2">
                        <button type="button" class="flex-1 px-2 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 upload-gallery-media">Upload Media</button>
                    </div>
                    <button type="button" class="absolute top-2 right-2 px-2 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700 remove-gallery">×</button>
                </div>`;
            galleryList.insertAdjacentHTML('beforeend', html);
            attachGalleryListeners();
            updateAlpineCounts();
        });
    }

    function attachGalleryListeners() {
        document.querySelectorAll('.upload-gallery-media').forEach(btn => {
            btn.replaceWith(btn.cloneNode(true)); // remove old listeners
        });
        document.querySelectorAll('.remove-gallery').forEach(btn => {
            btn.replaceWith(btn.cloneNode(true));
        });
        document.querySelectorAll('.upload-gallery-media').forEach(btn => {
            btn.addEventListener('click', uploadMedia);
        });
        document.querySelectorAll('.remove-gallery').forEach(btn => {
            btn.addEventListener('click', removeGallery);
        });
    }

    function uploadMedia(e) {
        e.preventDefault();
        const row        = e.target.closest('.gallery-row');
        const fileInput  = row.querySelector('.gallery-file');
        const typeSelect = row.querySelector('.gallery-type');
        fileInput.click();
        fileInput.addEventListener('change', function () {
            if (!this.files || !this.files[0]) return;
            const file = this.files[0];
            const type = file.type.startsWith('image/') ? 'image' : (file.type.startsWith('video/') ? 'video' : '');
            if (!type) { alert('Invalid file type.'); this.value = ''; return; }

            // Check video limit
            if (type === 'video') {
                if (planVideoLimit === 0) { alert('Your plan does not include video uploads.'); this.value = ''; return; }
                const { videos } = getGalleryCounts();
                if (videos >= planVideoLimit) { alert(`Video limit reached (${planVideoLimit}). Upgrade your plan to add more.`); this.value = ''; return; }
            }

            typeSelect.value = type;
            const objectURL  = URL.createObjectURL(file);
            const preview    = row.querySelector('.gallery-preview');
            if (type === 'image') {
                preview.innerHTML = `<img src="${objectURL}" alt="Gallery" class="w-full h-48 object-cover rounded">`;
            } else {
                preview.innerHTML = `<div class="w-full h-48 bg-black rounded flex items-center justify-center relative"><video class="w-full h-full object-cover" src="${objectURL}"></video><div class="absolute inset-0 flex items-center justify-center bg-black/30"><svg class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"></path></svg></div></div>`;
            }
            updateAlpineCounts();
        }, { once: true });
    }

    function removeGallery(e) {
        e.preventDefault();
        e.target.closest('.gallery-row').remove();
        updateAlpineCounts();
    }

    attachGalleryListeners();
});
</script>

<?php if ($plan_google_map): ?>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAUPkXXwkGt0xC5ongE7-62nzz6l7D3Nf4&libraries=places,marker&v=beta" async></script>
<?php endif; ?>

<?php
} else {
?>
<div class="max-w-3xl mx-auto my-12 p-6 bg-white rounded-lg shadow text-center">
    <h2 class="text-2xl font-bold mb-4">No Active Subscription</h2>
    <p class="mb-6">You must have an active subscription to create properties.</p>
    <a href="<?php echo home_url('/pricing'); ?>"
       class="inline-block px-6 py-3 bg-[var(--primary-color)] text-white rounded-lg hover:bg-blue-700 font-semibold">
        View Pricing Plans
    </a>
</div>
<?php
}