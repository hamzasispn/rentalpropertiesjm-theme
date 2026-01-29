<?php
/**
 * Custom Fields and Meta Registration for Properties
 * Updated with new location hierarchy and property_status meta
 */

// Register meta fields for properties
function property_theme_register_meta_fields() {
    register_post_meta('property', '_property_price', array(
        'show_in_rest' => true,
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ));

    register_post_meta('property', '_property_area', array(
        'show_in_rest' => true,
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ));

    register_post_meta('property', '_property_bedrooms', array(
        'show_in_rest' => true,
        'type' => 'integer',
        'single' => true,
        'sanitize_callback' => 'absint',
    ));

    register_post_meta('property', '_property_bathrooms', array(
        'show_in_rest' => true,
        'type' => 'integer',
        'single' => true,
        'sanitize_callback' => 'absint',
    ));

    register_post_meta('property', '_property_address', array(
        'show_in_rest' => true,
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ));

    register_post_meta('property', '_property_latitude', array(
        'show_in_rest' => true,
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ));

    register_post_meta('property', '_property_longitude', array(
        'show_in_rest' => true,
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ));

    register_post_meta('property', 'property_coords', array(
        'show_in_rest' => array(
            'schema' => array(
                'type' => 'object',
                'properties' => array(
                    'lat' => array('type' => 'number'),
                    'lng' => array('type' => 'number'),
                ),
            ),
        ),
        'type' => 'object',
        'single' => true,
        'sanitize_callback' => 'property_theme_sanitize_coords',
    ));

    register_post_meta('property', '_property_featured', array(
        'show_in_rest' => true,
        'type' => 'boolean',
        'single' => true,
    ));

    register_post_meta('property', '_property_amenities_data', array(
        'show_in_rest' => true,
        'type' => 'object',
        'single' => true,
    ));

    register_post_meta('property', '_property_gallery', array(
        'show_in_rest' => true,
        'type' => 'array',
        'single' => true,
    ));

    register_post_meta('property', 'property_status', array(
        'show_in_rest' => true,
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'property_theme_sanitize_status',
    ));

    // Keep old _property_status for backward compatibility
    register_post_meta('property', '_property_status', array(
        'show_in_rest' => true,
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ));

    register_post_meta('property', '_property_plot_number', array(
        'show_in_rest' => true,
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ));

    register_post_meta('property', 'property_country', array(
        'show_in_rest' => true,
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ));

    register_post_meta('property', 'property_state', array(
        'show_in_rest' => true,
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ));

    register_post_meta('property', 'property_city', array(
        'show_in_rest' => true,
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ));

    register_post_meta('property', 'property_locality', array(
        'show_in_rest' => true,
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ));

    register_post_meta('property', 'property_block', array(
        'show_in_rest' => true,
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ));

    // Keep old city/country fields for backward compatibility
    register_post_meta('property', '_property_city', array(
        'show_in_rest' => true,
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ));

    register_post_meta('property', '_property_country', array(
        'show_in_rest' => true,
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ));

    register_post_meta('property', '_property_detailed_address', array(
        'show_in_rest' => true,
        'type' => 'string',
        'single' => true,
        'sanitize_callback' => 'sanitize_textarea_field',
    ));
}
add_action('init', 'property_theme_register_meta_fields');

/**
 * Sanitize property_status to only allow rent or for_sale
 */
function property_theme_sanitize_status($value) {
    $allowed = array('rent', 'for_sale');
    return in_array($value, $allowed) ? $value : '';
}

/**
 * Sanitize property_coords JSON
 */
function property_theme_sanitize_coords($value) {
    if (is_string($value)) {
        $value = json_decode($value, true);
    }
    
    if (!is_array($value)) {
        return array('lat' => null, 'lng' => null);
    }
    
    return array(
        'lat' => isset($value['lat']) ? floatval($value['lat']) : null,
        'lng' => isset($value['lng']) ? floatval($value['lng']) : null,
    );
}

/**
 * Get user subscription plan
 */
function property_theme_get_user_plan($user_id) {
    $subscription = property_theme_get_user_subscription($user_id);
    if (!$subscription) {
        return null;
    }
    
    return get_post($subscription->package_id);
}

/**
 * Get locations autocomplete data
 */
function property_theme_get_locations($search = '') {
    global $wpdb;
    
    $query = "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_property_address'";
    
    if (!empty($search)) {
        $query .= $wpdb->prepare(" AND meta_value LIKE %s", '%' . $wpdb->esc_like($search) . '%');
    }
    
    $results = $wpdb->get_col($query . " ORDER BY meta_value LIMIT 20");
    return array_filter(array_unique($results));
}

/**
 * Get analytics data for property
 */
function property_theme_get_property_analytics($property_id) {
    global $wpdb;
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT event_type, COUNT(*) as count FROM {$wpdb->prefix}property_analytics WHERE property_id = %d GROUP BY event_type",
        $property_id
    ));
}

/**
 * Get leads for property
 */
function property_theme_get_property_leads($property_id) {
    global $wpdb;
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}property_leads WHERE property_id = %d ORDER BY created_at DESC LIMIT 50",
        $property_id
    ));
}

/**
 * Get property coordinates (with migration from old lat/long)
 */
function property_theme_get_property_coords($property_id) {
    $coords = get_post_meta($property_id, 'property_coords', true);
    
    // If new coords exist and are valid, return them
    if (!empty($coords) && !empty($coords['lat']) && !empty($coords['lng'])) {
        return $coords;
    }
    
    // Migration: check old lat/long fields
    $old_lat = get_post_meta($property_id, '_property_latitude', true);
    $old_lng = get_post_meta($property_id, '_property_longitude', true);
    
    if (!empty($old_lat) && !empty($old_lng)) {
        return array(
            'lat' => floatval($old_lat),
            'lng' => floatval($old_lng),
        );
    }
    
    return array('lat' => null, 'lng' => null);
}

/**
 * Get full address details for a property
 */
function property_theme_get_full_address($property_id) {
    return array(
        'address' => get_post_meta($property_id, '_property_address', true),
    );
}
