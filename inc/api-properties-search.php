<?php
add_action('rest_api_init', function () {
    register_rest_route('property/v1', '/search', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'property_search_api',
        'permission_callback' => '__return_true',
    ));
});

function property_search_api(WP_REST_Request $request)
{
    $filters = array(
        'property_type' => sanitize_text_field($request->get_param('property_type') ?? ''),
        'price_min' => intval($request->get_param('price_min') ?? 0),
        'price_max' => intval($request->get_param('price_max') ?? 999999999),
        'area_min' => intval($request->get_param('area_min') ?? 0),
        'area_max' => intval($request->get_param('area_max') ?? 999999999),
        'beds_min' => intval($request->get_param('beds_min') ?? 0),
        'beds_max' => intval($request->get_param('beds_max') ?? 10),
        'baths_min' => intval($request->get_param('baths_min') ?? 0),
        'baths_max' => intval($request->get_param('baths_max') ?? 10),
        'city' => sanitize_text_field($request->get_param('city') ?? ''),
        'keyword' => sanitize_text_field($request->get_param('keyword') ?? ''),
        'featured' => $request->get_param('featured') === 'true',
        'sort' => sanitize_text_field($request->get_param('sort') ?? 'newest'),
        'paged' => intval($request->get_param('paged') ?? 1),
        'per_page' => intval($request->get_param('per_page') ?? 12),
    );

    $args = array(
        'post_type' => 'property',
        'posts_per_page' => $filters['per_page'],
        'paged' => $filters['paged'],
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => '_property_price',
                'value' => array($filters['price_min'], $filters['price_max']),
                'compare' => 'BETWEEN',
                'type' => 'NUMERIC',
            ),
            array(
                'key' => '_property_area',
                'value' => array($filters['area_min'], $filters['area_max']),
                'compare' => 'BETWEEN',
                'type' => 'NUMERIC',
            ),
        ),
        'tax_query' => array(
            'relation' => 'AND',
        ),
    );

    // Property Type (support multiple comma-separated)
    if (!empty($filters['property_type'])) {
        $types = array_map('trim', explode(',', $filters['property_type']));
        $args['tax_query'][] = array(
            'taxonomy' => 'property_type',
            'field' => 'slug',
            'terms' => $types,
        );
    }

    if (!empty($filters['keyword'])) {
        $args['meta_query'][] = array(
            'key' => '_property_address',
            'value' => $filters['keyword'],
            'compare' => 'LIKE',
        );
    }

    if (!empty($filters['city'])) {
        $args['meta_query'][] = array(
            'key' => '_property_city',
            'value' => $filters['city'],
            'compare' => 'LIKE',
        );
    }


    if ($filters['beds_min'] > 0 || $filters['beds_max'] < 10) {
        $bed_terms = [];
        for ($i = $filters['beds_min']; $i <= $filters['beds_max']; $i++) {
            $bed_terms[] = strval($i);
        }
        if (!empty($bed_terms)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'bedroom',
                'field' => 'slug',
                'terms' => $bed_terms,
                'operator' => 'IN',
            );
        }
    }

    // Bathrooms Range (similar assumption)
    if ($filters['baths_min'] > 0 || $filters['baths_max'] < 10) {
        $bath_terms = [];
        for ($i = $filters['baths_min']; $i <= $filters['baths_max']; $i++) {
            $bath_terms[] = strval($i);
        }
        if (!empty($bath_terms)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'bathroom',
                'field' => 'slug',
                'terms' => $bath_terms,
                'operator' => 'IN',
            );
        }
    }

    // Featured
    $featured_param = $request->get_param('featured');
    if ($featured_param === 'true') {
        $args['meta_query'][] = array(
            'key' => '_property_featured',
            'value' => '1',
            'compare' => '=',
        );
    } elseif ($featured_param === 'false') {
        $args['meta_query'][] = array(
            'relation' => 'OR',
            array(
                'key' => '_property_featured',
                'value' => '1',
                'compare' => '!=',
            ),
            array(
                'key' => '_property_featured',
                'compare' => 'NOT EXISTS',
            ),
        );
    }

    // Sorting
    switch ($filters['sort']) {
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
            $args['orderby'] = array(
                'meta_value_num' => 'DESC',
                'date' => 'DESC'
            );
            break;
        default: // newest
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
    }

    $query = new WP_Query($args);
    $properties = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            $property_type_terms = get_the_terms($post_id, 'property_type');
            $property_type = (!is_wp_error($property_type_terms) && !empty($property_type_terms))
                ? $property_type_terms[0]->name
                : 'N/A';

            $gallery = get_post_meta($post_id, '_property_gallery', true);
            $bed_terms = wp_get_post_terms($post_id, 'bedroom', array('fields' => 'names'));
            $bath_terms = wp_get_post_terms($post_id, 'bathroom', array('fields' => 'names'));
            $author_id = get_post_field('post_author', $post_id);

            $properties[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'price' => (int) get_post_meta($post_id, '_property_price', true),
                'area' => (int) get_post_meta($post_id, '_property_area', true),
                'property_type' => $property_type,
                'bedrooms' => !empty($bed_terms) ? (int) $bed_terms[0] : 0,
                'bathrooms' => !empty($bath_terms) ? (int) $bath_terms[0] : 0,
                'address' => get_post_meta($post_id, '_property_address', true) ?: '',
                'featured' => (bool) get_post_meta($post_id, '_property_featured', true),
                'image' => get_the_post_thumbnail_url($post_id, 'medium'),
                'permalink' => get_permalink(),
                'author_name' => get_the_author_meta('display_name', $author_id),
                'author_profile_url' => get_author_posts_url($author_id),
                'author_avatar' => get_avatar_url($author_id, array('size' => 96)),
                'gallery' => is_array($gallery) ? $gallery : [],
            );
        }
    }

    wp_reset_postdata();

    return rest_ensure_response(array(
        'success' => true,
        'properties' => $properties,
        'total' => $query->found_posts,
        'pages' => $query->max_num_pages,
        'current_page' => $filters['paged'],
    ));
}