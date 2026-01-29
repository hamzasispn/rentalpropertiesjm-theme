<?php
/**
 * Template Name: Add/Edit Property
 * A front-end property form for users to create, edit, and delete properties
 * UPDATED: Fixed amenities modal, added gallery video support with same box design
 * UPDATED: Added debugging, validation, and error handling for form submission
 */

// Redirect non-logged-in users to login
if (!is_user_logged_in()) {
    wp_safe_redirect(wp_login_url(get_permalink()));
    exit;
}

$current_user = wp_get_current_user();
$user_subscription = property_theme_get_user_subscription($current_user->ID);

if (!$user_subscription) {
    wp_die('You must have an active subscription to create properties. <a href="' . home_url('/pricing') . '">View pricing plans</a>', 'No Active Subscription', array('response' => 403));
}

$is_edit = isset($_GET['property_id']) && intval($_GET['property_id']) > 0;
$property_id = $is_edit ? intval($_GET['property_id']) : 0;
$property = $is_edit ? get_post($property_id) : null;

// Check if user has permission to edit this property
if ($is_edit && (!$property || $property->post_author != $current_user->ID || $property->post_type !== 'property')) {
    wp_die('You do not have permission to edit this property.');
}

$property_data = array(
    'title' => '',
    'description' => '',
    'price' => '',
    'area' => '',
    'bedrooms' => '',
    'bathrooms' => '',
    'city' => '',
    'address' => '',
    'latitude' => '',
    'longitude' => '',
    'featured' => 0,
    'property_type' => '',
    'amenities_data' => array(),
    'gallery' => array(),
    'featured_image' => '',
);

if ($is_edit && $property) {
    $property_terms = wp_get_post_terms($property_id, 'property_type');
    $property_bedrooms = wp_get_post_terms($property_id, 'bedroom');
    $property_bathrooms = wp_get_post_terms($property_id, 'bathroom');
    $property_data = array(
        'title' => $property->post_title,
        'description' => $property->post_content,
        'price' => get_post_meta($property_id, '_property_price', true),
        'area' => get_post_meta($property_id, '_property_area', true),
        'bedrooms' => !empty($property_bedrooms) ? $property_bedrooms[0]->slug : '',
        'bathrooms' => !empty($property_bathrooms) ? $property_bathrooms[0]->slug : '',
        'city' => get_post_meta($property_id, '_property_city', true),
        'address' => get_post_meta($property_id, '_property_address', true),
        'latitude' => get_post_meta($property_id, '_property_latitude', true),
        'longitude' => get_post_meta($property_id, '_property_longitude', true),
        'featured' => get_post_meta($property_id, '_property_featured', true),
        'property_type' => !empty($property_terms) ? $property_terms[0]->slug : '',
        'amenities_data' => get_post_meta($property_id, '_property_amenities_data', true) ?: array(),
        'gallery' => get_post_meta($property_id, '_property_gallery', true) ?: array(),
        'featured_image' => get_the_post_thumbnail_url($property_id),
    );
}

$parent_property_types = get_terms(array(
    'taxonomy' => 'property_type',
    'hide_empty' => false,
    'parent' => 0,
));

$property_type_tabs = [];

foreach ($parent_property_types as $parent) {
    $children = get_terms(array(
        'taxonomy' => 'property_type',
        'hide_empty' => false,
        'parent' => $parent->term_id,
    ));

    $property_type_tabs[] = [
        'parent' => $parent,
        'children' => $children,
    ];
}

// Determine active tab for edit mode
$active_tab_slug = $property_type_tabs[0]['parent']->slug ?? '';
if ($property_data['property_type']) {
    $term = get_term_by('slug', $property_data['property_type'], 'property_type');
    if ($term && $term->parent) {
        $parent_term = get_term($term->parent, 'property_type');
        $active_tab_slug = $parent_term->slug;
    }
}

function sort_terms_numerically($terms) {
    if (empty($terms) || is_wp_error($terms)) {
        return $terms;
    }

    usort($terms, function($a, $b) {
        return intval($a->name) - intval($b->name);
    });

    return $terms;
}

$bedrooms = get_terms(array(
    'taxonomy'   => 'bedroom',
    'hide_empty' => false,
));

$bedrooms = sort_terms_numerically($bedrooms);

$bathrooms = get_terms(array(
    'taxonomy'   => 'bathroom',
    'hide_empty' => false,
));

$bathrooms = sort_terms_numerically($bathrooms);

get_jamaica_cities();
global $cities;

$cities_data = $cities;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_property'])) {
    if (!wp_verify_nonce($_POST['property_form_nonce'], 'add_property_nonce')) {
        wp_die('Security check failed: Invalid nonce.');
    }

    // Basic validation (add more as needed)
    $errors = [];
    if (empty(sanitize_text_field($_POST['property_title'] ?? ''))) {
        $errors[] = 'Property title is required.';
    }
    if (empty(sanitize_text_field($_POST['property_price'] ?? ''))) {
        $errors[] = 'Price is required.';
    }
    if (empty(sanitize_text_field($_POST['property_city'] ?? ''))) {
        $errors[] = 'City is required.';
    }
    if (empty(sanitize_text_field($_POST['property_type'] ?? ''))) {
        $errors[] = 'Property type is required.';
    }
    if (!empty($errors)) {
        // Store errors in session or query arg for display
        set_transient('property_form_errors_' . $current_user->ID, $errors, 30);
        $redirect_url = add_query_arg(['error' => 1]);
        if ($is_edit) $redirect_url = add_query_arg('property_id', $property_id, $redirect_url);
        wp_redirect($redirect_url);
        exit;
    }

    $post_data = array(
        'post_type' => 'property',
        'post_status' => 'publish', // Change to 'draft' if users can't publish
        'post_title' => sanitize_text_field($_POST['property_title'] ?? 'Untitled Property'),
        'post_content' => sanitize_textarea_field($_POST['property_description'] ?? ''),
    );

    if ($is_edit) {
        $post_data['ID'] = $property_id;
        $post_id = wp_update_post($post_data);
    } else {
        $post_data['post_author'] = $current_user->ID;
        $post_id = wp_insert_post($post_data);
    }

    // DEBUG: Check for errors here
    if (is_wp_error($post_id)) {
        error_log('Property save error: ' . $post_id->get_error_message()); // Logs to debug.log
        wp_die('Save failed: ' . $post_id->get_error_message() . ' (Check error logs for details.)');
    }
    if (!$post_id || $post_id === 0) {
        error_log('Property save failed: Post ID is ' . var_export($post_id, true));
        wp_die('Save failed: Invalid post ID (0 or false). Check post type registration and user capabilities.');
    }

    // Rest of your meta/terms saving code
    update_post_meta($post_id, '_property_price', sanitize_text_field($_POST['property_price'] ?? ''));
    update_post_meta($post_id, '_property_area', sanitize_text_field($_POST['property_area'] ?? ''));
    update_post_meta($post_id, '_property_city', sanitize_text_field($_POST['property_city'] ?? ''));
    update_post_meta($post_id, '_property_address', sanitize_text_field($_POST['property_address'] ?? ''));
    update_post_meta($post_id, '_property_latitude', sanitize_text_field($_POST['property_latitude'] ?? ''));
    update_post_meta($post_id, '_property_longitude', sanitize_text_field($_POST['property_longitude'] ?? ''));
    update_post_meta($post_id, '_property_featured', isset($_POST['property_featured']) ? 1 : 0);

    $property_type = sanitize_text_field($_POST['property_type'] ?? '');
    if ($property_type) {
        $term = get_term_by('slug', $property_type, 'property_type');
        if ($term) {
            $set_terms = wp_set_post_terms($post_id, array($term->term_id), 'property_type');
            if (is_wp_error($set_terms)) {
                error_log('Property type term error: ' . $set_terms->get_error_message());
            }
        } else {
            error_log('Property type term not found: ' . $property_type);
        }
    }

    $property_bedroom = sanitize_text_field($_POST['property_bedroom'] ?? '');
    if ($property_bedroom) {
        wp_set_post_terms($post_id, array($property_bedroom), 'bedroom');
    }

    $property_bathroom = sanitize_text_field($_POST['property_bathroom'] ?? '');
    if ($property_bathroom) {
        wp_set_post_terms($post_id, array($property_bathroom), 'bathroom');
    }

    // Save property type specific fields
    if (isset($_POST['property_type_fields']) && is_array($_POST['property_type_fields'])) {
        foreach ($_POST['property_type_fields'] as $field_name => $field_value) {
            update_post_meta($post_id, '_property_' . sanitize_key($field_name), sanitize_text_field($field_value));
        }
    }

    // Save amenities groups
    $amenities_data = array();
    if (isset($_POST['amenities_groups']) && is_array($_POST['amenities_groups'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        foreach ($_POST['amenities_groups'] as $g => $group_data) {
            $group = array(
                'title' => sanitize_text_field($group_data['title'] ?? ''),
                'amenities' => array(),
            );

            if (isset($group_data['amenities']) && is_array($group_data['amenities'])) {
                foreach ($group_data['amenities'] as $a => $amenity) {
                    if (!empty($amenity['title'])) {
                        $icon = esc_url_raw($amenity['icon'] ?? '');

                        // Handle icon file upload if present
                        if (isset($_FILES['amenities_groups']['name'][$g]['amenities'][$a]['icon_file']) &&
                            $_FILES['amenities_groups']['error'][$g]['amenities'][$a]['icon_file'] === 0) {
                            $_FILES['temp_icon'] = array(
                                'name' => $_FILES['amenities_groups']['name'][$g]['amenities'][$a]['icon_file'],
                                'type' => $_FILES['amenities_groups']['type'][$g]['amenities'][$a]['icon_file'],
                                'tmp_name' => $_FILES['amenities_groups']['tmp_name'][$g]['amenities'][$a]['icon_file'],
                                'error' => $_FILES['amenities_groups']['error'][$g]['amenities'][$a]['icon_file'],
                                'size' => $_FILES['amenities_groups']['size'][$g]['amenities'][$a]['icon_file']
                            );
                            $attachment_id = media_handle_upload('temp_icon', $post_id);
                            if (!is_wp_error($attachment_id)) {
                                $icon = wp_get_attachment_url($attachment_id);
                            } else {
                                error_log('Amenity icon upload error: ' . $attachment_id->get_error_message());
                            }
                        }

                        $group['amenities'][] = array(
                            'title' => sanitize_text_field($amenity['title']),
                            'icon' => $icon,
                        );
                    }
                }
            }
            $amenities_data[] = $group;
        }
    }
    update_post_meta($post_id, '_property_amenities_data', $amenities_data);

    // Save gallery
    $gallery = array();
    $existing_gallery = $is_edit ? get_post_meta($post_id, '_property_gallery', true) ?: array() : array();
    if (isset($_FILES['property_gallery_files']) && isset($_POST['property_gallery_types'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $files = $_FILES['property_gallery_files'];
        $types = $_POST['property_gallery_types'];
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === 0 && !empty($files['name'][$i])) {
                $_FILES['temp_file'] = array(
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                );
                $attachment_id = media_handle_upload('temp_file', $post_id);
                if (!is_wp_error($attachment_id)) {
                    $media_url = wp_get_attachment_url($attachment_id);
                    $gallery[] = array(
                        'type' => sanitize_text_field($types[$i] ?? 'image'),
                        'media_url' => $media_url,
                    );
                } else {
                    error_log('Gallery upload error: ' . $attachment_id->get_error_message());
                }
            }
        }
    }
    // Merge new with existing (to keep old ones if no new uploads)
    $gallery = array_merge($existing_gallery, $gallery);
    update_post_meta($post_id, '_property_gallery', $gallery);
    error_log('Gallery saved: ' . print_r($gallery, true));

    // Handle featured image upload
    if (isset($_FILES['property_featured_image']) && $_FILES['property_featured_image']['size'] > 0) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('property_featured_image', $post_id);
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
        } else {
            error_log('Featured image upload error: ' . $attachment_id->get_error_message());
        }
    }

    // Success redirect
    $redirect_url = add_query_arg(array('property_id' => $post_id, 'saved' => 1));
    wp_redirect($redirect_url);
    exit;
}

// Handle property deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['property_id'])) {
    $property_id_to_delete = intval($_GET['property_id']);
    $property_to_delete = get_post($property_id_to_delete);

    if ($property_to_delete && $property_to_delete->post_author == $current_user->ID && $property_to_delete->post_type === 'property') {
        if (wp_delete_post($property_id_to_delete, true)) {
            wp_redirect(get_permalink(get_option('page_on_front')) . '?deleted=1');
            exit;
        }
    }
}


get_header();
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-12 px-4">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-slate-900 mb-2 font-inter">
                <?php echo $is_edit ? 'Edit Property' : 'Add New Property'; ?></h1>
            <p class="text-slate-600 font-inter">Fill in the details below to
                <?php echo $is_edit ? 'update' : 'create'; ?> your
                property listing</p>
        </div>

        <!-- Success Message -->
        <?php if (isset($_GET['saved'])) : ?>
        <div class="mb-8 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-green-800 font-semibold">Property saved successfully!</p>
        </div>
        <?php endif; ?>

        <!-- Error Messages -->
        <?php
        $user_errors = get_transient('property_form_errors_' . $current_user->ID);
        delete_transient('property_form_errors_' . $current_user->ID); // Clean up
        if (isset($_GET['error']) && $user_errors) : ?>
        <div class="mb-8 p-4 bg-red-50 border border-red-200 rounded-lg">
            <ul class="text-red-800">
                <?php foreach ($user_errors as $error) : ?>
                <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8"
            x-data="propertyForm(<?php echo htmlspecialchars(json_encode($property_data)); ?>, <?php echo htmlspecialchars(json_encode($type_specific_fields ?? [])); ?>, <?php echo htmlspecialchars(json_encode($cities_data)); ?>)">
            <?php wp_nonce_field('add_property_nonce', 'property_form_nonce'); ?>

            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Property Type Selection Card -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h4 class="text-lg font-semibold font-inter mb-6">Select Property Type</h4>
                    <div x-data="{ activeTab: '<?php echo esc_js($active_tab_slug); ?>' }">
                        <div class="flex gap-2 border-b border-slate-200 mb-6">
                            <?php foreach ($property_type_tabs as $tab) : ?>
                            <button type="button" @click="activeTab = '<?php echo esc_js($tab['parent']->slug); ?>'"
                                :class="activeTab === '<?php echo esc_js($tab['parent']->slug); ?>' ? 'border-[var(--primary-color)] text-[var(--primary-color)]' : 'border-transparent text-slate-600'"
                                class="px-4 py-2 font-semibold border-b-2 transition">
                                <?php echo esc_html($tab['parent']->name); ?>
                            </button>
                            <?php endforeach; ?>
                        </div>

                        <?php foreach ($property_type_tabs as $tab) : ?>
                        <div x-show="activeTab === '<?php echo esc_js($tab['parent']->slug); ?>'" x-transition
                            class="flex flex-wrap gap-3">
                            <?php foreach ($tab['children'] as $child) : ?>

                            <?php
                                // ACF SVG icon field
                                $icon_svg = get_field('icons', 'property_type_' . $child->term_id);
                                ?>

                            <label
                                class="flex items-center gap-2 px-4 py-2 rounded-full cursor-pointer border transition"
                                :class="selectedPropertyType === '<?php echo esc_js($child->slug); ?>' ? 'shadow-sm' : ''"
                                :style="selectedPropertyType === '<?php echo esc_js($child->slug); ?>' ? 'background-color: var(--primary-color); border-color: var(--primary-color); color: #fff;' : 'background-color: #f8fafc; border-color: var(--secondary-color); color: var(--text-primary-color);'">

                                <input type="radio" name="property_type" value="<?php echo esc_attr($child->slug); ?>"
                                    x-model="selectedPropertyType" class="hidden">

                                <!-- SVG Icon -->
                                <?php if ($icon_svg) : ?>
                                <span class="w-5 h-5"
                                    :style="selectedPropertyType === '<?php echo esc_js($child->slug); ?>' ? 'fill: #fff' : 'fill: var(--primary-color)'">
                                    <?php echo $icon_svg; ?>
                                </span>
                                <?php endif; ?>

                                <span class="text-sm font-semibold whitespace-nowrap">
                                    <?php echo esc_html($child->name); ?>
                                </span>

                            </label>

                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>


                <!-- Basic Info Card -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-lg font-semibold mb-6">Basic Information</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Property Title *</label>
                            <input type="text" name="property_title" x-model="propertyData.title" required
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Description</label>
                            <textarea name="property_description" rows="5" x-model="propertyData.description"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">Price ($) *</label>
                                <input type="number" name="property_price" x-model="propertyData.price" step="0.01"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900"
                                    required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">Area (sq ft)</label>
                                <input type="number" name="property_area" x-model="propertyData.area"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location Card with City Selection & Google Maps -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-6">Location & Map</h2>

                    <div class="space-y-4">
                        <!-- City Selection Dropdown -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">City *</label>
                            <select name="property_city" x-model="selectedCity" @change="updateCityLocation()"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900"
                                required>
                                <option value="">Select a city...</option>
                                <template x-for="(coords, city) in citiesData" :key="city">
                                    <option :value="city" x-text="city"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Address Input with Google Places -->
                        <div class="relative">
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Address *</label>
                            <input type="text" id="address-input" name="property_address" x-model="propertyData.address"
                                :disabled="!selectedCity" placeholder="Search address in selected city" required
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900 disabled:bg-gray-100">
                            <div id="autocomplete-dropdown"
                                class="absolute top-full left-0 right-0 bg-white border border-slate-300 rounded-lg shadow-lg mt-1 hidden max-h-48 overflow-y-auto z-50">
                                <!-- Autocomplete suggestions -->
                            </div>
                            <p class="text-xs mt-1 text-slate-500 p-2">
                                Start typing your location in detail to see address suggestions powered by Google Places API.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">Latitude</label>
                                <input type="text" name="property_latitude" id="latitude-input"
                                    x-model="propertyData.latitude"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900"
                                    readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">Longitude</label>
                                <input type="text" name="property_longitude" id="longitude-input"
                                    x-model="propertyData.longitude"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900"
                                    readonly>
                            </div>
                        </div>

                        <!-- Map Display with Google Maps -->
                        <div id="property-map" class="w-full h-80 rounded-lg border border-slate-300 mt-4 bg-slate-100">
                        </div>
                    </div>
                </div>

                <!-- Amenities Groups Section -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-lg font-semibold text-slate-900 mb-6">Features & Amenities</h2>

                    <div class="flex flex-wrap gap-4 mb-6 items-start">

                        <div class="flex items-center p-2 justify-center rounded-lg bg-gray-200 text-gray-900">
                            <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24"
                                height="1.2em" width="1.2em" xmlns="http://www.w3.org/2000/svg">
                                <path fill="none" d="M0 0h24v24H0z"></path>
                                <path
                                    d="M12 3 1 11.4l1.21 1.59L4 11.62V21h16v-9.38l1.79 1.36L23 11.4 12 3zm6 16H6v-8.9l6-4.58 6 4.58V19zm-9-5c0 .55-.45 1-1 1s-1-.45-1-1 .45-1 1-1 1 .45 1 1zm3-1c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm3 1c0-.55.45-1 1-1s1 .45 1 1-.45 1-1 1-1-.45-1-1z">
                                </path>
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-md font-semibold text-slate-900 mb-2">Bedrooms</h3>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($bedrooms as $bedroom) : ?>

                                <label
                                    class="flex items-center justify-center px-4 py-2 gap-2 text-sm rounded-full cursor-pointer border transition"
                                    :class="selectedBedroom === '<?php echo esc_js($bedroom->slug); ?>' ? 'shadow-sm bg-[var(--primary-color)] border-[var(--primary-color)] text-white' : 'bg-[#f8fafc] border-[var(--secondary-color)] text-[var(--text-primary-color)]'">

                                    <input type="radio" name="property_bedroom"
                                        value="<?php echo esc_attr($bedroom->slug); ?>" x-model="selectedBedroom"
                                        class="hidden">

                                    <span class="text-sm font-semibold whitespace-nowrap">
                                        <?php echo esc_html($bedroom->name); ?>
                                    </span>

                                </label>

                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>


                    <div class="flex flex-wrap gap-4 mb-6 items-start">
                        <div class="flex items-center p-2 justify-center rounded-lg bg-gray-200 text-gray-900">
                            <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24"
                                height="1.2em" width="1.2em" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M21 10H7V7c0-1.103.897-2 2-2s2 .897 2 2h2c0-2.206-1.794-4-4-4S5 4.794 5 7v3H3a1 1 0 0 0-1 1v2c0 2.606 1.674 4.823 4 5.65V22h2v-3h8v3h2v-3.35c2.326-.827 4-3.044 4-5.65v-2a1 1 0 0 0-1-1zm-1 3c0 2.206-1.794 4-4 4H8c-2.206 0-4-1.794-4-4v-1h16v1z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-md font-semibold text-slate-900 mb-2">Bathrooms</h3>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($bathrooms as $bathroom) : ?>

                                <label
                                    class="flex items-center gap-2 px-4 py-2 rounded-full cursor-pointer border transition"
                                    :class="selectedBathroom === '<?php echo esc_js($bathroom->slug); ?>' ? 'shadow-sm bg-[var(--primary-color)] border-[var(--primary-color)] text-white' : 'bg-[#f8fafc] border-[var(--secondary-color)] text-[var(--text-primary-color)]'">

                                    <input type="radio" name="property_bathroom"
                                        value="<?php echo esc_attr($bathroom->slug); ?>" x-model="selectedBathroom"
                                        class="hidden">
                                    <span class="text-sm font-semibold whitespace-nowrap">
                                        <?php echo esc_html($bathroom->name); ?>
                                    </span>

                                </label>

                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 mb-6 items-start">
                        <div class="flex items-center p-2 justify-center rounded-lg bg-gray-200 text-gray-900">
                            <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24"
                                height="1.2em" width="1.2em" xmlns="http://www.w3.org/2000/svg">
                                <path fill="none" d="M0 0h24v24H0z"></path>
                                <path
                                    d="M12 3 1 11.4l1.21 1.59L4 11.62V21h16v-9.38l1.79 1.36L23 11.4 12 3zm6 16H6v-8.9l6-4.58 6 4.58V19zm-9-5c0 .55-.45 1-1 1s-1-.45-1-1 .45-1 1-1 1 .45 1 1zm3-1c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm3 1c0-.55.45-1 1-1s1 .45 1 1-.45 1-1 1-1-.45-1-1z">
                                </path>
                            </svg>
                        </div>
                        <div class="flex justify-between gap-6 items-end" x-data="{ openModal: false }">
                            <div>
                                <h3 class="text-md font-semibold text-slate-900 mb-2">Feature and Amenities</h3>
                                <p class="text-sm text-[var(--text-primary-color)] mb-2">Add additional features e.g.
                                    parking spaces, waste disposal, internet etc.</p>
                            </div>
                            <button type="button" @click="openModal = ! openModal"
                                class="px-4 py-2 bg-[var(--primary-color)] text-white rounded-lg font-semibold text-sm">
                                + Add Amenities
                            </button>

                            <!-- AMENITIES MODAL -->
                            <div x-data="{
                                    open: true,
                                    activeTab: 0,
                                    groups: <?= esc_js(json_encode($property_data['amenities_data'] ?? [
                                        ['title' => 'Main Features', 'amenities' => []]
                                    ])) ?>,

                                    addGroup() {
                                        this.groups.push({ title: '', amenities: [] });
                                        this.activeTab = this.groups.length - 1;
                                    },

                                    addAmenity() {
                                        this.groups[this.activeTab].amenities.push({ title: '', icon: '' });
                                    },

                                    removeAmenity(index) {
                                        this.groups[this.activeTab].amenities.splice(index, 1);
                                    },

                                    uploadIcon(gIndex, aIndex, event) {
                                        const file = event.target.files[0];
                                        if (file) {
                                            this.groups[gIndex].amenities[aIndex].icon = URL.createObjectURL(file);
                                        }
                                    }
                                }" x-show="openModal" x-transition
                                class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
                                <!-- MODAL BOX -->
                                <div
                                    class="bg-white w-[70%] h-[85vh] rounded-xl shadow-xl flex flex-col overflow-hidden">

                                    <!-- HEADER -->
                                    <div class="flex items-center justify-between px-6 py-4 border-b">
                                        <h2 class="text-lg font-semibold text-[var(--text-primary-color)]">
                                            Feature and Amenities
                                        </h2>
                                        <button @click="openModal=false"
                                            class="text-slate-500 hover:text-slate-800 text-2xl leading-none">
                                            ×
                                        </button>
                                    </div>

                                    <!-- BODY -->
                                    <div class="flex flex-1 overflow-hidden">

                                        <!-- LEFT TABS -->
                                        <div class="w-64 border-r bg-slate-50 overflow-y-auto">

                                            <template x-for="(group, gIndex) in groups" :key="gIndex">
                                                <button type="button" @click="activeTab = gIndex"
                                                    class="w-full text-left px-4 py-3 border-b text-sm font-medium transition"
                                                    :class="activeTab === gIndex ? 'bg-white text-[var(--primary-color)] border-l-4 border-[var(--primary-color)]' : 'text-slate-600 hover:bg-slate-100'">
                                                    <span x-text="group.title || 'Untitled Group'"></span>
                                                </button>
                                            </template>

                                            <!-- ADD GROUP -->
                                            <button type="button" @click="addGroup()" class="w-full px-4 py-3 text-left text-sm font-medium
                    text-[var(--primary-color)] hover:bg-white border-t">
                                                + Add Amenities Group
                                            </button>
                                        </div>

                                        <!-- RIGHT CONTENT -->
                                        <div class="flex-1 p-6 overflow-y-auto">

                                            <template x-if="groups[activeTab]">
                                                <div class="space-y-6">

                                                    <!-- GROUP TITLE -->
                                                    <input type="text" x-model="groups[activeTab].title"
                                                        :name="`amenities_groups[${activeTab}][title]`" class="w-full text-lg font-semibold px-4 py-2 border rounded-lg
                            focus:ring-2 focus:ring-[var(--primary-color)] outline-none" placeholder="Group Title">

                                                    <!-- AMENITIES LIST -->
                                                    <div class="grid grid-cols-2 gap-4">

                                                        <template
                                                            x-for="(amenity, aIndex) in groups[activeTab].amenities"
                                                            :key="aIndex">
                                                            <div
                                                                class="flex items-center gap-3 p-4 border rounded-lg bg-white">

                                                                <div
                                                                    class="w-10 h-10 bg-slate-100 rounded flex items-center justify-center relative">
                                                                    <template x-if="amenity.icon">
                                                                        <img :src="amenity.icon"
                                                                            class="w-8 h-8 object-contain">
                                                                    </template>
                                                                    <template x-if="!amenity.icon">📎</template>
                                                                    <input type="file" accept="image/*"
                                                                        class="absolute inset-0 opacity-0 cursor-pointer"
                                                                        :name="`amenities_groups[${activeTab}][amenities][${aIndex}][icon_file]`"
                                                                        @change="uploadIcon(activeTab, aIndex, $event)">
                                                                </div>

                                                                <input type="text" x-model="amenity.title"
                                                                    :name="`amenities_groups[${activeTab}][amenities][${aIndex}][title]`"
                                                                    class="flex-1 px-3 py-2 text-sm border rounded-md"
                                                                    placeholder="Amenity name">

                                                                <input type="hidden" x-model="amenity.icon"
                                                                    :name="`amenities_groups[${activeTab}][amenities][${aIndex}][icon]`">

                                                                <button type="button" @click="removeAmenity(aIndex)"
                                                                    class="text-red-500 hover:text-red-700 text-lg">
                                                                    ×
                                                                </button>

                                                            </div>
                                                        </template>

                                                    </div>

                                                    <!-- ADD AMENITY -->
                                                    <button type="button" @click="addAmenity()" class="px-4 py-2 rounded-lg text-sm font-medium text-white
                            bg-[var(--secondary-color)] hover:opacity-90">
                                                        + Add Amenity
                                                    </button>

                                                </div>
                                            </template>

                                        </div>
                                    </div>

                                    <!-- FOOTER -->
                                    <div class="flex justify-end gap-3 px-6 py-4 border-t">
                                        <button type="button" @click="openModal=false"
                                            class="px-4 py-2 rounded-lg border text-sm">
                                            Cancel
                                        </button>
                                        <button type="button" @click="openModal=false" class="px-5 py-2 rounded-lg text-sm font-medium text-white
                bg-[var(--primary-color)]">
                                            Save Amenities
                                        </button>
                                    </div>

                                </div>
                            </div>

                        </div>

                    </div>


                </div>

                <!-- Gallery Section -->
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-6">Gallery (Images & Videos)</h2>
                    <div id="gallery-list" class="grid grid-cols-2 gap-4 mb-4">
                        <?php if (!empty($property_data['gallery'])) : ?>
                        <?php foreach ($property_data['gallery'] as $index => $item) : ?>
                        <div class="gallery-row relative p-4 bg-slate-50 rounded-lg border border-slate-300">
                            <?php if ($item['type'] === 'image') : ?>
                            <img src="<?php echo esc_url($item['media_url']); ?>" alt="Gallery"
                                class="w-full h-48 object-cover rounded gallery-preview">
                            <?php else : ?>
                            <div class="w-full h-48 bg-black rounded flex items-center justify-center text-white">
                                VIDEO
                            </div>
                            <?php endif; ?>
                            <input type="file" name="property_gallery_files[]" accept="image/*,video/*"
                                class="gallery-file hidden">
                            <select name="property_gallery_types[]" class="gallery-type hidden">
                                <option value="<?php echo esc_attr($item['type']); ?>" selected>
                                    <?php echo esc_html(ucfirst($item['type'])); ?></option>
                            </select>
                            <div class="mt-2 flex gap-2">
                                <button type="button"
                                    class="flex-1 px-2 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 upload-gallery-media">
                                    Replace Media
                                </button>
                            </div>
                            <button type="button"
                                class="absolute top-2 right-2 px-2 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700 remove-gallery">
                                ×
                            </button>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="add-gallery-btn"
                        class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                        + Add Media
                    </button>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Featured Image -->
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-6 sticky top-24">
                    <h3 class="font-bold text-slate-900 mb-4">Featured Image</h3>
                    <div id="featured-image-preview" class="w-full bg-slate-100 rounded-lg mb-4 overflow-hidden">
                        <?php if ($property_data['featured_image']) : ?>
                        <img src="<?php echo esc_url($property_data['featured_image']); ?>" alt="Featured"
                            class="w-full h-48 object-cover">
                        <?php else : ?>
                        <div class="w-full h-48 flex items-center justify-center text-slate-400">No image</div>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="upload-featured-image"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                        Upload Image
                    </button>
                    <input type="file" name="property_featured_image" id="featured-image-input" style="display: none;"
                        accept="image/*">
                </div>

                <!-- Featured Listing -->
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-6">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="property_featured" value="1"
                            <?php checked($property_data['featured'], 1); ?> class="w-5 h-5 rounded border-slate-300">
                        <span class="font-semibold text-slate-900">Featured Listing</span>
                    </label>
                    <p class="text-sm text-slate-600 mt-2">Highlight your property on homepage</p>
                </div>

                <!-- Submit Buttons -->
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 space-y-3">
                    <button type="submit" name="submit_property"
                        class="w-full px-4 py-3 bg-[var(--primary-color)] text-white rounded-lg hover:bg-blue-700 font-bold">
                        <?php echo $is_edit ? 'Update Property' : 'Create Property'; ?>
                    </button>

                    <a href="<?php echo home_url('/dashboard'); ?>"
                        class="block text-center px-4 py-3 bg-slate-200 text-slate-900 rounded-lg hover:bg-slate-300 font-semibold">
                        Back to Dashboard
                    </a>

                    <?php if ($is_edit) : ?>
                    <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'delete', 'property_id' => $property_id)), 'delete_property', 'nonce'); ?>"
                        onclick="return confirm('Delete this property?');"
                        class="block text-center px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold">
                        Delete Property
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Optimized Alpine.js Script - No Vanilla JS Functions -->
<script>
function propertyForm(initialData, typeSpecificFieldsData, citiesData) {
    return {
        propertyData: initialData,
        typeSpecificFields: [],
        propertyTypeFields: typeSpecificFieldsData,
        citiesData: citiesData,
        selectedPropertyType: initialData.property_type || '',
        selectedBedroom: initialData.bedrooms || '',
        selectedBathroom: initialData.bathrooms || '',
        selectedCity: initialData.city || '',
        map: null,
        marker: null,
        autocompleteListener: null,
        showAmenitiesModal: false,
        amenitiesGroups: <?php echo json_encode($property_data['amenities_data'] ?? [['title' => 'Main Features', 'amenities' => []]]); ?>,
        activeAmenitiesTab: 0,

        addAmenitiesGroup() {
            this.amenitiesGroups.push({
                title: '',
                amenities: []
            });
            this.activeAmenitiesTab = this.amenitiesGroups.length - 1;
        },

        addAmenity() {
            if (!this.amenitiesGroups[this.activeAmenitiesTab]) return;
            this.amenitiesGroups[this.activeAmenitiesTab].amenities.push({
                title: '',
                icon: ''
            });
        },

        removeAmenity(index) {
            if (!this.amenitiesGroups[this.activeAmenitiesTab]) return;
            this.amenitiesGroups[this.activeAmenitiesTab].amenities.splice(index, 1);
        },

        updateCityLocation() {
            if (this.selectedCity && this.citiesData[this.selectedCity]) {
                const coords = this.citiesData[this.selectedCity];
                this.propertyData.latitude = coords.lat;
                this.propertyData.longitude = coords.lng;
                this.initializeMap(coords.lat, coords.lng);
                this.setCoordinates(coords.lat, coords.lng, 'City Update');
            }
        },

        updatePropertyTypeFields() {
            this.typeSpecificFields.forEach(field => {
                if (!(field.name in this.propertyData)) {
                    this.propertyData[field.name] = '';
                }
            });
        },

        getPropertyTypeFieldValue(fieldName) {
            return this.propertyData[fieldName] || '';
        },

        initializeMap(lat, lng) {
            const mapElement = document.getElementById('property-map');
            if (!mapElement) {
                console.log('[v0] Map element not found');
                return;
            }

            if (window.google && window.google.maps) {
                if (this.map === null) {
                    mapElement.style.width = '100%';
                    mapElement.style.height = '400px';
                    this.map = new google.maps.Map(mapElement, {
                        zoom: 14,
                        center: {
                            lat,
                            lng
                        },
                        mapTypeControl: true,
                        zoomControl: true,
                        mapId: 'c484b19c4f8c16ebb3dcf3d1'
                    });

                    this.map.addListener('click', (e) => {
                        this.setCoordinates(e.latLng.lat(), e.latLng.lng(), 'Map Click');
                    });
                } else {
                    this.map.setCenter({
                        lat,
                        lng
                    });
                    this.map.setZoom(14);
                }
                this.initializeAutocomplete();
            } else {
                console.log('[v0] Google Maps API not loaded');
            }
        },

        setCoordinates(lat, lng, source = '') {
            console.log('[v0] Setting coordinates:', lat, lng, source);

            this.propertyData.latitude = lat;
            this.propertyData.longitude = lng;

            if (this.marker) {
                this.marker.setPosition({
                    lat,
                    lng
                });
            } else {
                const icon = {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                    <svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 60 60">
                        <g>
                            <path fill="#95a5a5" d="M33 32.72v21.39a3.016 3.016 0 0 1-.43 1.55l-1.71 2.85a1 1 0 0 1-1.72 0l-1.71-2.85a3.016 3.016 0 0 1-.43-1.55V32.72z" opacity="1" data-original="#95a5a5"></path>
                            <path fill="#c03a2b" d="M46 17a15.98 15.98 0 1 1-6.44-12.84A16 16 0 0 1 46 17z" opacity="1" data-original="#c03a2b" class=""></path>
                            <path fill="#e64c3c" d="M40 8a17 17 0 0 1-17 17 16.853 16.853 0 0 1-7.79-1.89A16.009 16.009 0 0 1 39.56 4.16 16.744 16.744 0 0 1 40 8z" opacity="1" data-original="#e64c3c" class=""></path>
                        </g>
                    </svg>
                    `),
                    scaledSize: new google.maps.Size(32, 32),
                    anchor: new google.maps.Point(16, 16)
                };

                this.marker = new google.maps.Marker({
                    position: {
                        lat,
                        lng
                    },
                    map: this.map,
                    title: 'Property Location',
                    icon: icon,
                    draggable: true
                });

                this.marker.addListener('dragend', () => {
                    const pos = this.marker.getPosition();
                    this.propertyData.latitude = pos.lat();
                    this.propertyData.longitude = pos.lng();
                    console.log('[v0] Marker dragged to:', pos.lat(), pos.lng());
                });
            }

            // Pan map to marker
            if (this.map) {
                this.map.panTo({
                    lat,
                    lng
                });
            }
        },

initializeAutocomplete() {
            const input = document.getElementById('address-input');
            if (!input || !this.selectedCity) return;

            if (this.autocompleteListener) {
                // Remove previous listener if it exists
                const tempListener = this.autocompleteListener;
                input.removeEventListener('input', tempListener);
                this.autocompleteListener = null;
            }

            const geocoder = new google.maps.Geocoder();
            let debounceTimer = null;

            this.autocompleteListener = (e) => {
                clearTimeout(debounceTimer);

                if (input.value.length > 2) {
                    debounceTimer = setTimeout(() => {
                        geocoder.geocode({
                                address: input.value + ', ' + this.selectedCity,
                                componentRestrictions: {
                                    country: 'jm'
                                }
                            },
                            (results, status) => {
                                const dropdown = document.getElementById('autocomplete-dropdown');
                                dropdown.innerHTML = '';
                                dropdown.classList.add('hidden');

                                if (status === 'OK' && results && results.length > 0) {
                                    results.slice(0, 5).forEach(place => {
                                        const div = document.createElement('div');
                                        div.className =
                                            'px-4 py-2 cursor-pointer hover:bg-slate-100';
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
                    }, 300); // 300ms debounce
                } else {
                    const dropdown = document.getElementById('autocomplete-dropdown');
                    dropdown.classList.add('hidden');
                }
            };

            input.addEventListener('input', this.autocompleteListener);
        },

        init() {
            this.updatePropertyTypeFields();
            if (this.selectedCity) {
                this.updateCityLocation();
                if (!this.map) {
                    this.initializeMap(this.citiesData[this.selectedCity].lat, this.citiesData[this.selectedCity].lng);
                }
            } else if (this.propertyData.latitude && this.propertyData.longitude) {
                const lat = parseFloat(this.propertyData.latitude);
                const lng = parseFloat(this.propertyData.longitude);
                this.initializeMap(lat, lng);
                this.setCoordinates(lat, lng, 'Init');
            }
        }
    }
}
</script>
<!-- Direct upload handlers with Alpine (no wp.media) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadBtn = document.getElementById('upload-featured-image');
    const fileInput = document.getElementById('featured-image-input');

    if (uploadBtn && fileInput) {
        uploadBtn.addEventListener('click', (e) => {
            e.preventDefault();
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            if (!this.files || !this.files[0]) return;
            const file = this.files[0];
            if (!file.type.startsWith('image/')) {
                alert('Please select a valid image file.');
                this.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => {
                document.getElementById('featured-image-preview').innerHTML =
                    `<img src="${e.target.result}" alt="Preview" class="w-full h-48 object-cover">`;
            };
            reader.readAsDataURL(file);
        });
    }

    const addGalleryBtn = document.getElementById('add-gallery-btn');
    const galleryList = document.getElementById('gallery-list');

    if (addGalleryBtn) {
        addGalleryBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const index = galleryList.children.length;
            const galleryHTML = `
                <div class="gallery-row relative p-4 bg-slate-50 rounded-lg border border-slate-300 overflow-hidden">
                    <div class="w-full h-48 bg-slate-200 rounded flex items-center justify-center text-slate-400 gallery-preview">No media</div>
                    <input type="file" name="property_gallery_files[]" accept="image/*,video/*" class="gallery-file hidden">
                    <select name="property_gallery_types[]" class="gallery-type hidden">
                        <option value="image">Image</option>
                        <option value="video">Video</option>
                    </select>
                    <div class="mt-2 flex gap-2">
                        <button type="button" class="flex-1 px-2 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 upload-gallery-media">Upload Media</button>
                    </div>
                    <button type="button" class="absolute top-2 right-2 px-2 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700 remove-gallery">×</button>
                </div>
            `;
            galleryList.insertAdjacentHTML('beforeend', galleryHTML);
            attachGalleryListeners();
        });
    }

    function attachGalleryListeners() {
        document.querySelectorAll('.upload-gallery-media').forEach(btn => {
            btn.removeEventListener('click', uploadMedia);
            btn.addEventListener('click', uploadMedia);
        });

        document.querySelectorAll('.remove-gallery').forEach(btn => {
            btn.removeEventListener('click', removeGallery);
            btn.addEventListener('click', removeGallery);
        });
    }

    function uploadMedia(e) {
        e.preventDefault();
        const row = e.target.closest('.gallery-row');
        const fileInput = row.querySelector('.gallery-file');
        const typeSelect = row.querySelector('.gallery-type');
        fileInput.click();

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const type = file.type.startsWith('image/') ? 'image' : (file.type.startsWith(
                    'video/') ? 'video' : '');
                if (!type) {
                    alert('Invalid file type. Only images and videos allowed.');
                    this.value = '';
                    return;
                }
                typeSelect.value = type;

                const preview = row.querySelector('.gallery-preview');
                const objectURL = URL.createObjectURL(file);

                if (type === 'image') {
                    preview.innerHTML =
                        `<img src="${objectURL}" alt="Gallery" class="w-full h-48 object-cover rounded">`;
                } else {
                    preview.innerHTML = `
                        <div class="w-full h-48 bg-black rounded flex items-center justify-center relative">
                            <video class="w-full h-full object-cover" src="${objectURL}"></video>
                            <div class="absolute inset-0 flex items-center justify-center bg-black/30">
                                <svg class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"></path>
                                </svg>
                            </div>
                        </div>
                    `;
                }
            }
        }, {
            once: true
        });
    }

    function removeGallery(e) {
        e.preventDefault();
        e.target.closest('.gallery-row').remove();
    }

    attachGalleryListeners();
});
</script>

<script
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAUPkXXwkGt0xC5ongE7-62nzz6l7D3Nf4&libraries=places,marker&v=beta"
    async>
</script>

<?php get_footer(); ?>