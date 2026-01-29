<?php
/**
 * API Endpoints for Property Theme
 * REMOVED location hierarchy endpoints (countries, states, cities, localities)
 * All location selection now handled via Mapbox Geocoding API directly in JavaScript
 * NO static data, NO PHP fallbacks - Pure Mapbox API with Axios + Alpine
 */

// Register REST API routes
add_action('rest_api_init', function () {
    // Property search with filters (KEPT - this uses city/locality from Mapbox input)
    register_rest_route('property-theme/v1', '/properties/search', array(
        'methods' => 'GET',
        'callback' => 'property_theme_search_properties',
        'permission_callback' => '__return_true',
    ));
    
    // Filter options (property types & status only - no location data)
    register_rest_route('property-theme/v1', '/filter-options', array(
        'methods' => 'GET',
        'callback' => 'property_theme_get_filter_options',
        'permission_callback' => '__return_true',
    ));
    
    // Amenities list with counts
    register_rest_route('property-theme/v1', '/amenities', array(
        'methods' => 'GET',
        'callback' => 'property_theme_get_amenities_list',
        'permission_callback' => '__return_true',
    ));
    
    // Save property location (AJAX)
    register_rest_route('property-theme/v1', '/save-location', array(
        'methods' => 'POST',
        'callback' => 'property_theme_save_location',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        },
    ));
    
    // Upload property media (webp/webm only)
    register_rest_route('property-theme/v1', '/upload-media', array(
        'methods' => 'POST',
        'callback' => 'property_theme_upload_media',
        'permission_callback' => function() {
            return current_user_can('upload_files');
        },
    ));
});

/**
 * Search properties with advanced filtering
 * Filters by city, locality, coords + radius, price, area, beds, baths, property type, status
 */
function property_theme_search_properties($request) {
    $args = array(
        'post_type' => 'property',
        'post_status' => 'publish',
        'posts_per_page' => intval($request->get_param('per_page')) ?: 12,
        'paged' => intval($request->get_param('page')) ?: 1,
    );
    
    $meta_query = array('relation' => 'AND');
    $tax_query = array();
    
    // Search term
    if ($search = sanitize_text_field($request->get_param('search'))) {
        $args['s'] = $search;
    }
    
    // Property type (taxonomy)
    if ($property_type = sanitize_text_field($request->get_param('property_type'))) {
        $types = explode(',', $property_type);
        $tax_query[] = array(
            'taxonomy' => 'property_type',
            'field' => 'slug',
            'terms' => array_map('sanitize_text_field', $types),
        );
    }
    
    // Property status (rent or for_sale only)
    if ($status = sanitize_text_field($request->get_param('property_status'))) {
        if (in_array($status, array('rent', 'for_sale'))) {
            $meta_query[] = array(
                'key' => 'property_status',
                'value' => $status,
                'compare' => '=',
            );
        }
    }
    
    // Price range
    $min_price = intval($request->get_param('min_price'));
    $max_price = intval($request->get_param('max_price'));
    if ($min_price > 0 || $max_price > 0) {
        if ($min_price > 0 && $max_price > 0) {
            $meta_query[] = array(
                'key' => '_property_price',
                'value' => array($min_price, $max_price),
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN',
            );
        } elseif ($min_price > 0) {
            $meta_query[] = array(
                'key' => '_property_price',
                'value' => $min_price,
                'type' => 'NUMERIC',
                'compare' => '>=',
            );
        } elseif ($max_price > 0) {
            $meta_query[] = array(
                'key' => '_property_price',
                'value' => $max_price,
                'type' => 'NUMERIC',
                'compare' => '<=',
            );
        }
    }
    
    // Area range
    $min_area = intval($request->get_param('min_area'));
    $max_area = intval($request->get_param('max_area'));
    if ($min_area > 0 || ($max_area > 0 && $max_area < 100000)) {
        if ($min_area > 0 && $max_area > 0) {
            $meta_query[] = array(
                'key' => '_property_area',
                'value' => array($min_area, $max_area),
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN',
            );
        } elseif ($min_area > 0) {
            $meta_query[] = array(
                'key' => '_property_area',
                'value' => $min_area,
                'type' => 'NUMERIC',
                'compare' => '>=',
            );
        } elseif ($max_area > 0) {
            $meta_query[] = array(
                'key' => '_property_area',
                'value' => $max_area,
                'type' => 'NUMERIC',
                'compare' => '<=',
            );
        }
    }
    
    // Bedrooms range
    $beds_min = $request->get_param('beds_min');
    $beds_max = $request->get_param('beds_max');
    if ($beds_min !== '' && $beds_min !== null) {
        $meta_query[] = array(
            'key' => '_property_bedrooms',
            'value' => intval($beds_min),
            'type' => 'NUMERIC',
            'compare' => '>=',
        );
    }
    if ($beds_max !== '' && $beds_max !== null) {
        $meta_query[] = array(
            'key' => '_property_bedrooms',
            'value' => intval($beds_max),
            'type' => 'NUMERIC',
            'compare' => '<=',
        );
    }
    
    // City filter (from Mapbox input)
    if ($city = sanitize_text_field($request->get_param('city'))) {
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key' => 'property_city',
                'value' => $city,
                'compare' => 'LIKE',
            ),
            array(
                'key' => '_property_city',
                'value' => $city,
                'compare' => 'LIKE',
            ),
        );
    }
    
    // Locality filter (from Mapbox input)
    if ($locality = sanitize_text_field($request->get_param('locality'))) {
        $meta_query[] = array(
            'key' => 'property_locality',
            'value' => $locality,
            'compare' => 'LIKE',
        );
    }
    
    // Featured filter
    if ($request->get_param('featured') === 'true') {
        $meta_query[] = array(
            'key' => '_property_featured',
            'value' => '1',
            'compare' => '=',
        );
    }
    
    // Apply meta query
    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }
    
    // Apply tax query
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }
    
    // Sorting
    $sort = sanitize_text_field($request->get_param('sort'));
    switch ($sort) {
        case 'price-low':
            $args['meta_key'] = '_property_price';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'ASC';
            break;
        case 'price-high':
            $args['meta_key'] = '_property_price';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        case 'featured':
            $args['meta_key'] = '_property_featured';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        default:
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
    }
    
    $query = new WP_Query($args);
    $properties = array();
    
    // Coords-based filtering (radius search)
    $coords_param = sanitize_text_field($request->get_param('coords'));
    $radius = intval($request->get_param('radius')) ?: 10; // Default 10km
    $user_lat = null;
    $user_lng = null;
    
    if ($coords_param && strpos($coords_param, ',') !== false) {
        list($user_lat, $user_lng) = array_map('floatval', explode(',', $coords_param));
    }
    
    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        
        // Get coordinates for distance filtering
        $coords = property_theme_get_property_coords($post_id);
        
        // If radius search is active, filter by distance
        if ($user_lat !== null && $user_lng !== null && !empty($coords['lat']) && !empty($coords['lng'])) {
            $distance = property_theme_haversine_distance($user_lat, $user_lng, $coords['lat'], $coords['lng']);
            if ($distance > $radius) {
                continue; // Skip properties outside radius
            }
        }
        
        $status = get_post_meta($post_id, 'property_status', true);
        if (empty($status)) {
            $status = get_post_meta($post_id, '_property_status', true);
        }
        
        $properties[] = array(
            'id' => $post_id,
            'title' => get_the_title(),
            'permalink' => get_permalink(),
            'image' => get_the_post_thumbnail_url($post_id, 'medium_large') ?: '',
            'price' => floatval(get_post_meta($post_id, '_property_price', true)),
            'area' => get_post_meta($post_id, '_property_area', true),
            'bedrooms' => intval(get_post_meta($post_id, '_property_bedrooms', true)),
            'bathrooms' => intval(get_post_meta($post_id, '_property_bathrooms', true)),
            'address' => get_post_meta($post_id, '_property_address', true),
            'status' => $status,
            'featured' => (bool) get_post_meta($post_id, '_property_featured', true),
            'coords' => $coords,
        );
    }
    
    wp_reset_postdata();
    
    return new WP_REST_Response(array(
        'properties' => $properties,
        'total' => $query->found_posts,
        'pages' => $query->max_num_pages,
        'current_page' => $args['paged'],
    ), 200);
}

/**
 * Haversine distance calculation (in km)
 */
function property_theme_haversine_distance($lat1, $lng1, $lat2, $lng2) {
    $earth_radius = 6371; // km
    
    $lat1 = deg2rad($lat1);
    $lng1 = deg2rad($lng1);
    $lat2 = deg2rad($lat2);
    $lng2 = deg2rad($lng2);
    
    $dlat = $lat2 - $lat1;
    $dlng = $lng2 - $lng1;
    
    $a = sin($dlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dlng / 2) ** 2;
    $c = 2 * asin(sqrt($a));
    
    return $earth_radius * $c;
}

/**
 * Get filter options (property types, status values - no location data)
 */
function property_theme_get_filter_options($request) {
    $types = get_terms(array(
        'taxonomy' => 'property_type',
        'hide_empty' => false,
    ));
    
    $property_types = array();
    foreach ($types as $type) {
        $property_types[] = array(
            'slug' => $type->slug,
            'name' => $type->name,
            'count' => $type->count,
        );
    }
    
    // Status options (only rent and for_sale)
    $status_options = array(
        array('value' => 'rent', 'label' => 'For Rent'),
        array('value' => 'for_sale', 'label' => 'For Sale'),
    );
    
    return new WP_REST_Response(array(
        'property_types' => $property_types,
        'status_options' => $status_options,
    ), 200);
}

/**
 * Get amenities list with counts
 */
function property_theme_get_amenities_list($request) {
    global $wpdb;
    
    // Get all amenities from property meta
    $results = $wpdb->get_col(
        "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_property_amenities_data'"
    );
    
    $amenity_counts = array();
    
    foreach ($results as $result) {
        $data = maybe_unserialize($result);
        if (is_array($data)) {
            foreach ($data as $group) {
                if (!empty($group['amenities']) && is_array($group['amenities'])) {
                    foreach ($group['amenities'] as $amenity) {
                        if (!empty($amenity['title'])) {
                            $key = sanitize_title($amenity['title']);
                            if (!isset($amenity_counts[$key])) {
                                $amenity_counts[$key] = array(
                                    'key' => $key,
                                    'label' => $amenity['title'],
                                    'count' => 0,
                                );
                            }
                            $amenity_counts[$key]['count']++;
                        }
                    }
                }
            }
        }
    }
    
    // Sort by count descending
    usort($amenity_counts, function($a, $b) {
        return $b['count'] - $a['count'];
    });
    
    return new WP_REST_Response(array_values($amenity_counts), 200);
}

/**
 * Save property location via AJAX
 * Stores the location data (country, state, city, locality, coords) from Mapbox
 */
function property_theme_save_location($request) {
    $property_id = intval($request->get_param('property_id'));
    
    if (!$property_id || !current_user_can('edit_post', $property_id)) {
        return new WP_Error('unauthorized', 'You cannot edit this property', array('status' => 403));
    }
    
    $coords = $request->get_param('coords');
    $address_data = $request->get_param('address_data');
    
    if (!empty($coords['lat']) && !empty($coords['lng'])) {
        $coords_value = array(
            'lat' => floatval($coords['lat']),
            'lng' => floatval($coords['lng']),
        );
        update_post_meta($property_id, 'property_coords', $coords_value);
        
        // Also update old fields for backward compatibility
        update_post_meta($property_id, '_property_latitude', strval($coords_value['lat']));
        update_post_meta($property_id, '_property_longitude', strval($coords_value['lng']));
    }
    
    if (!empty($address_data)) {
        if (!empty($address_data['country'])) {
            update_post_meta($property_id, 'property_country', sanitize_text_field($address_data['country']));
        }
        if (!empty($address_data['state'])) {
            update_post_meta($property_id, 'property_state', sanitize_text_field($address_data['state']));
        }
        if (!empty($address_data['city'])) {
            update_post_meta($property_id, 'property_city', sanitize_text_field($address_data['city']));
        }
        if (!empty($address_data['locality'])) {
            update_post_meta($property_id, 'property_locality', sanitize_text_field($address_data['locality']));
        }
    }
    
    return new WP_REST_Response(array('success' => true), 200);
}

/**
 * Upload property media (webp/webm only)
 */
function property_theme_upload_media($request) {
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }
    
    if (empty($_FILES['media'])) {
        return new WP_Error('no_file', 'No file provided', array('status' => 400));
    }
    
    $file = $_FILES['media'];
    
    // Validate file type
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, array('webp', 'webm'))) {
        return new WP_Error('invalid_type', 'Only WEBP and WEBM files are allowed', array('status' => 400));
    }
    
    $upload = wp_handle_upload($file, array('test_form' => false));
    
    if (!empty($upload['error'])) {
        return new WP_Error('upload_error', $upload['error'], array('status' => 400));
    }
    
    return new WP_REST_Response(array(
        'url' => $upload['url'],
        'type' => in_array($ext, array('webp')) ? 'image' : 'video',
    ), 200);
}
