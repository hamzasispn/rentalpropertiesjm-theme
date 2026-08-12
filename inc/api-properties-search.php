<?php
add_action('rest_api_init', function () {
    register_rest_route('property/v1', '/search', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'property_search_api',
        'permission_callback' => '__return_true',
    ));
});

function property_search_api(WP_REST_Request $request)
{
    // Hard dependency on the licence module — see inc/license.php.
    if (!pt_license_gate()) {
        return new WP_REST_Response(array('properties' => array(), 'total' => 0), 200);
    }

    // ── Disable caching (prevents 304 Not Modified stale results) ───────────
    nocache_headers();
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");

    // ── Raw param reads (keep null so we can detect "not provided") ──────────
    $price_min_raw  = $request->get_param('price_min');
    $price_max_raw  = $request->get_param('price_max');
    $area_min_raw   = $request->get_param('area_min');
    $area_max_raw   = $request->get_param('area_max');
    $beds_min_raw   = $request->get_param('beds_min');
    $beds_max_raw   = $request->get_param('beds_max');
    $baths_min_raw  = $request->get_param('baths_min');
    $baths_max_raw  = $request->get_param('baths_max');
    $featured_param = $request->get_param('featured'); // keep as string 'true'/'false'/null

    $filters = array(
        'property_type'  => sanitize_text_field($request->get_param('property_type') ?? ''),
        'price_min'      => $price_min_raw !== null ? intval($price_min_raw) : null,
        'price_max'      => $price_max_raw !== null ? intval($price_max_raw) : null,
        'area_min'       => $area_min_raw  !== null ? intval($area_min_raw)  : null,
        'area_max'       => $area_max_raw  !== null ? intval($area_max_raw)  : null,
        'beds_min'       => $beds_min_raw  !== null ? intval($beds_min_raw)  : null,
        'beds_max'       => $beds_max_raw  !== null ? intval($beds_max_raw)  : null,
        'baths_min'      => $baths_min_raw !== null ? intval($baths_min_raw) : null,
        'baths_max'      => $baths_max_raw !== null ? intval($baths_max_raw) : null,
        'city'           => sanitize_text_field($request->get_param('city')           ?? ''),
        'keyword'        => sanitize_text_field($request->get_param('keyword')        ?? ''),
        'listing_status' => sanitize_text_field($request->get_param('listing_status') ?? ''),
        'sort'           => sanitize_text_field($request->get_param('sort')           ?? 'newest'),
        'paged'          => max(1, intval($request->get_param('paged')    ?? 1)),
        'per_page'       => min(100, max(1, intval($request->get_param('per_page') ?? 12))),
    );

    // ── Base query args ───────────────────────────────────────────────────────
    $args = array(
        'post_type'      => 'property',
        'post_status'    => 'publish',
        'posts_per_page' => $filters['per_page'],
        'paged'          => $filters['paged'],
        'meta_query'     => array('relation' => 'AND'),
        'tax_query'      => array('relation' => 'AND'),
    );

    // ── Price range ───────────────────────────────────────────────────────────
    // IMPORTANT: We wrap every price/area filter in a relation=>OR that also
    // catches properties where the meta key is missing or stored as empty string.
    // Without this, MySQL's numeric comparison silently drops those rows.
    if ($filters['price_min'] !== null || $filters['price_max'] !== null) {
        $price_clause = array('relation' => 'OR');

        // Include properties with no price meta set yet (empty string or not exists)
        $price_clause[] = array(
            'key'     => '_property_price',
            'value'   => '',
            'compare' => '=',
        );
        $price_clause[] = array(
            'key'     => '_property_price',
            'compare' => 'NOT EXISTS',
        );

        // The actual numeric range
        if ($filters['price_min'] !== null && $filters['price_max'] !== null) {
            $price_clause[] = array(
                'key'     => '_property_price',
                'value'   => array($filters['price_min'], $filters['price_max']),
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC',
            );
        } elseif ($filters['price_min'] !== null) {
            $price_clause[] = array(
                'key'     => '_property_price',
                'value'   => $filters['price_min'],
                'compare' => '>=',
                'type'    => 'NUMERIC',
            );
        } else {
            $price_clause[] = array(
                'key'     => '_property_price',
                'value'   => $filters['price_max'],
                'compare' => '<=',
                'type'    => 'NUMERIC',
            );
        }

        $args['meta_query'][] = $price_clause;
    }

    // ── Area range ────────────────────────────────────────────────────────────
    // Same OR pattern: properties with empty/missing area meta are included
    // rather than silently dropped by the numeric comparison.
    if ($filters['area_min'] !== null || $filters['area_max'] !== null) {
        $area_clause = array('relation' => 'OR');

        $area_clause[] = array(
            'key'     => '_property_area',
            'value'   => '',
            'compare' => '=',
        );
        $area_clause[] = array(
            'key'     => '_property_area',
            'compare' => 'NOT EXISTS',
        );

        if ($filters['area_min'] !== null && $filters['area_max'] !== null) {
            $area_clause[] = array(
                'key'     => '_property_area',
                'value'   => array($filters['area_min'], $filters['area_max']),
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC',
            );
        } elseif ($filters['area_min'] !== null) {
            $area_clause[] = array(
                'key'     => '_property_area',
                'value'   => $filters['area_min'],
                'compare' => '>=',
                'type'    => 'NUMERIC',
            );
        } else {
            $area_clause[] = array(
                'key'     => '_property_area',
                'value'   => $filters['area_max'],
                'compare' => '<=',
                'type'    => 'NUMERIC',
            );
        }

        $args['meta_query'][] = $area_clause;
    }

    // ── Property type (comma-separated) ──────────────────────────────────────
    if (!empty($filters['property_type'])) {
        $types = array_filter(array_map('trim', explode(',', $filters['property_type'])));
        if (!empty($types)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'property_type',
                'field'    => 'slug',
                'terms'    => $types,
                'operator' => 'IN',
            );
        }
    }

    // ── Listing status (buy / rent) ───────────────────────────────────────────
    if (!empty($filters['listing_status'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'property_listing_status',
            'field'    => 'slug',
            'terms'    => $filters['listing_status'],
        );
    }

    // ── Keyword → address LIKE search ─────────────────────────────────────────
    if (!empty($filters['keyword'])) {
        $args['meta_query'][] = array(
            'key'     => '_property_address',
            'value'   => $filters['keyword'],
            'compare' => 'LIKE',
        );
    }

    // ── City LIKE search ──────────────────────────────────────────────────────
    if (!empty($filters['city'])) {
        $args['meta_query'][] = array(
            'key'     => '_property_city',
            'value'   => $filters['city'],
            'compare' => 'LIKE',
        );
    }

    // ── Bedrooms range ────────────────────────────────────────────────────────
    // OR pattern: properties with empty/missing meta are included, not silently dropped.
    if ($filters['beds_min'] !== null || $filters['beds_max'] !== null) {
        $beds_clause   = array('relation' => 'OR');
        $beds_clause[] = array('key' => '_property_bedrooms', 'value' => '', 'compare' => '=');
        $beds_clause[] = array('key' => '_property_bedrooms', 'compare' => 'NOT EXISTS');
        if ($filters['beds_min'] !== null && $filters['beds_max'] !== null) {
            $beds_clause[] = array('key' => '_property_bedrooms', 'value' => array($filters['beds_min'], $filters['beds_max']), 'compare' => 'BETWEEN', 'type' => 'NUMERIC');
        } elseif ($filters['beds_min'] !== null) {
            $beds_clause[] = array('key' => '_property_bedrooms', 'value' => $filters['beds_min'], 'compare' => '>=', 'type' => 'NUMERIC');
        } else {
            $beds_clause[] = array('key' => '_property_bedrooms', 'value' => $filters['beds_max'], 'compare' => '<=', 'type' => 'NUMERIC');
        }
        $args['meta_query'][] = $beds_clause;
    }

    // ── Bathrooms range ───────────────────────────────────────────────────────
    if ($filters['baths_min'] !== null || $filters['baths_max'] !== null) {
        $baths_clause   = array('relation' => 'OR');
        $baths_clause[] = array('key' => '_property_bathrooms', 'value' => '', 'compare' => '=');
        $baths_clause[] = array('key' => '_property_bathrooms', 'compare' => 'NOT EXISTS');
        if ($filters['baths_min'] !== null && $filters['baths_max'] !== null) {
            $baths_clause[] = array('key' => '_property_bathrooms', 'value' => array($filters['baths_min'], $filters['baths_max']), 'compare' => 'BETWEEN', 'type' => 'NUMERIC');
        } elseif ($filters['baths_min'] !== null) {
            $baths_clause[] = array('key' => '_property_bathrooms', 'value' => $filters['baths_min'], 'compare' => '>=', 'type' => 'NUMERIC');
        } else {
            $baths_clause[] = array('key' => '_property_bathrooms', 'value' => $filters['baths_max'], 'compare' => '<=', 'type' => 'NUMERIC');
        }
        $args['meta_query'][] = $baths_clause;
    }

    // ── Featured filter ───────────────────────────────────────────────────────
    // FIX: $filters['featured'] was cast to bool early, so the 'false' branch
    //      was never reachable. We now read $featured_param (raw string) here.
    if ($featured_param === 'true') {
        $args['meta_query'][] = array(
            'key'     => '_property_featured',
            'value'   => '1',
            'compare' => '=',
        );
    } elseif ($featured_param === 'false') {
        $args['meta_query'][] = array(
            'relation' => 'OR',
            array(
                'key'     => '_property_featured',
                'value'   => '1',
                'compare' => '!=',
            ),
            array(
                'key'     => '_property_featured',
                'compare' => 'NOT EXISTS',
            ),
        );
    }
    // if $featured_param is null / not passed → no filter applied (show all)

    // ── Sorting ───────────────────────────────────────────────────────────────
    // FIX: When sorting by price/featured, add the meta_key inside a named
    //      clause so it doesn't conflict with other meta_query entries.
    switch ($filters['sort']) {
        case 'price-low':
            $args['meta_query']['price_sort'] = array(
                'key'     => '_property_price',
                'type'    => 'NUMERIC',
                'compare' => 'EXISTS',
            );
            $args['orderby'] = array('price_sort' => 'ASC', 'date' => 'DESC');
            break;

        case 'price-high':
            $args['meta_query']['price_sort'] = array(
                'key'     => '_property_price',
                'type'    => 'NUMERIC',
                'compare' => 'EXISTS',
            );
            $args['orderby'] = array('price_sort' => 'DESC', 'date' => 'DESC');
            break;

        case 'featured':
            $args['meta_query']['featured_sort'] = array(
                'key'     => '_property_featured',
                'compare' => 'EXISTS',
            );
            $args['orderby'] = array('featured_sort' => 'DESC', 'date' => 'DESC');
            break;

        default: // newest
            $args['orderby'] = 'date';
            $args['order']   = 'DESC';
            break;
    }

    // ── Run query ─────────────────────────────────────────────────────────────
    $query      = new WP_Query($args);
    $properties = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            // Property type
            $property_type_terms = get_the_terms($post_id, 'property_type');
            $property_type = (!is_wp_error($property_type_terms) && !empty($property_type_terms))
                ? $property_type_terms[0]->name
                : '';
            $property_type_slug = (!is_wp_error($property_type_terms) && !empty($property_type_terms))
                ? $property_type_terms[0]->slug
                : '';
            $property_type_icon = (!is_wp_error($property_type_terms) && !empty($property_type_terms) && function_exists('get_field'))
                ? get_field('icons', 'property_type_' . $property_type_terms[0]->term_id)
                : '';

            // Gallery – ensure it is always an array of strings
            $gallery_raw = get_post_meta($post_id, '_property_gallery', true);
            $gallery     = is_array($gallery_raw) ? array_values(array_filter($gallery_raw)) : [];

            // Author
            $author_id = (int) get_post_field('post_author', $post_id);

            // Listing status
            $listing_status_terms = wp_get_post_terms($post_id, 'property_listing_status', array('fields' => 'slugs'));
            $listing_status = (!is_wp_error($listing_status_terms) && !empty($listing_status_terms))
                ? $listing_status_terms[0]
                : '';

            // Beds / baths – prefer meta, fall back to taxonomy term name (e.g. "3" → 3)
            $bedrooms  = (int) get_post_meta($post_id, '_property_bedrooms',  true);
            $bathrooms = (float) get_post_meta($post_id, '_property_bathrooms', true);

            if (!$bedrooms) {
                $bed_terms = wp_get_post_terms($post_id, 'bedroom');
                if (!is_wp_error($bed_terms) && !empty($bed_terms)) {
                    $bedrooms = (int) $bed_terms[0]->name;
                }
            }
            if (!$bathrooms) {
                $bath_terms = wp_get_post_terms($post_id, 'bathroom');
                if (!is_wp_error($bath_terms) && !empty($bath_terms)) {
                    $bathrooms = (float) $bath_terms[0]->name;
                }
            }

            // Thumbnail – return false as null so JSON stays clean
            $thumbnail = get_the_post_thumbnail_url($post_id, 'medium');

            // Coordinates for map markers on the archive page.
            $coords_out = function_exists('property_theme_get_property_coords')
                ? property_theme_get_property_coords($post_id)
                : array('lat' => null, 'lng' => null);

            $properties[] = array(
                'id'                 => $post_id,
                'title'              => get_the_title(),
                'price'              => (int) get_post_meta($post_id, '_property_price', true),
                'area'               => (int) get_post_meta($post_id, '_property_area',  true),
                'property_type'      => $property_type,
                'property_type_slug' => $property_type_slug,
                'property_type_icon' => $property_type_icon,
                'bedrooms'           => $bedrooms,
                'bathrooms'          => $bathrooms,
                'address'            => (string) (get_post_meta($post_id, '_property_address', true) ?: ''),
                'city'               => (string) (get_post_meta($post_id, '_property_city',    true) ?: ''),
                'featured'           => (bool)   get_post_meta($post_id, '_property_featured', true),
                'listing_status'     => $listing_status,
                'image'              => $thumbnail ?: null,
                'permalink'          => get_permalink(),
                'author_name'        => get_the_author_meta('display_name', $author_id),
                'author_profile_url' => get_author_posts_url($author_id),
                'author_avatar'      => get_avatar_url($author_id, array('size' => 96)),
                'gallery'            => $gallery,
                'lat'                => !empty($coords_out['lat']) ? floatval($coords_out['lat']) : null,
                'lng'                => !empty($coords_out['lng']) ? floatval($coords_out['lng']) : null,
            );
        }
    }

    wp_reset_postdata();

    return rest_ensure_response(array(
        'success'      => true,
        'properties'   => $properties,
        'total'        => (int) $query->found_posts,
        'pages'        => (int) $query->max_num_pages,
        'current_page' => $filters['paged'],
    ));
}