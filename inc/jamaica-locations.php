<?php
/**
 * Jamaica Location Data - Cities, Parishes, and Boundaries
 * Real-time Mapbox Geocoding for Jamaica Properties
 * Restricts property filtering to Jamaica only
 */

function property_theme_get_jamaica_parishes() {
    return array(
        'kingston' => array(
            'name' => 'Kingston',
            'lat' => 18.0432,
            'lng' => -76.8036,
            'cities' => array('Kingston', 'Liguanea', 'New Kingston', 'Trench Town', 'Denham Town'),
        ),
        'st-andrew' => array(
            'name' => 'Saint Andrew',
            'lat' => 18.0537,
            'lng' => -76.7597,
            'cities' => array('Half Way Tree', 'Mona', 'Hope Pastures', 'Norbrook', 'Stony River', 'Papine', 'Constant Spring', 'Cherry Gardens'),
        ),
        'st-thomas' => array(
            'name' => 'Saint Thomas',
            'lat' => 17.9537,
            'lng' => -76.2436,
            'cities' => array('Morant Bay', 'Sligoville', 'Bath', 'Stokes Valley', 'Whately'),
        ),
        'portland' => array(
            'name' => 'Portland',
            'lat' => 18.3667,
            'lng' => -76.3833,
            'cities' => array('Port Antonio', 'Buff Bay', 'Fairy Hill', 'Hope Bay', 'Boston', 'Charlestown'),
        ),
        'st-mary' => array(
            'name' => 'Saint Mary',
            'lat' => 18.3394,
            'lng' => -76.6667,
            'cities' => array('Port Maria', 'Oracabessa', 'Annotto Bay', 'Highgate', 'Islington'),
        ),
        'st-james' => array(
            'name' => 'Saint James',
            'lat' => 18.4089,
            'lng' => -77.9178,
            'cities' => array('Montego Bay', 'Falmouth', 'Cambridge', 'Grange Hill', 'Rose Hall', 'Greenside', 'Ironshore'),
        ),
        'hanover' => array(
            'name' => 'Hanover',
            'lat' => 18.3611,
            'lng' => -78.1667,
            'cities' => array('Lucea', 'Green Island', 'Cascade', 'Sandy Bay', 'Wakefield'),
        ),
        'westmoreland' => array(
            'name' => 'Westmoreland',
            'lat' => 18.2394,
            'lng' => -78.3364,
            'cities' => array('Savanna-la-Mar', 'Negril', 'Little Bay', 'Whitehouse', 'Grange Hill', 'Bluefields', 'Bethel Town'),
        ),
        'st-elizabeth' => array(
            'name' => 'Saint Elizabeth',
            'lat' => 18.0822,
            'lng' => -77.8297,
            'cities' => array('Black River', 'Santa Cruz', 'Junction', 'Lacovia', 'Malvern'),
        ),
        'manchester' => array(
            'name' => 'Manchester',
            'lat' => 18.1281,
            'lng' => -77.4986,
            'cities' => array('Mandeville', 'Christiana', 'Spalding', 'Porus', 'Harmony Hall'),
        ),
        'clarendon' => array(
            'name' => 'Clarendon',
            'lat' => 18.2544,
            'lng' => -77.2475,
            'cities' => array('May Pen', 'Chapelton', 'Four Paths', 'Lionel Town', 'Frankfield', 'Mineral Spring'),
        ),
        'st-catherine' => array(
            'name' => 'Saint Catherine',
            'lat' => 18.0758,
            'lng' => -77.0522,
            'cities' => array('Spanish Town', 'Port Royal', 'Linstead', 'Bog Walk', 'Old Harbour', 'Caymanas', 'Passage Fort'),
        ),
    );
}

function property_theme_get_jamaica_cities() {
    $parishes = property_theme_get_jamaica_parishes();
    $cities = array();
    
    foreach ($parishes as $parish_key => $parish_data) {
        foreach ($parish_data['cities'] as $city) {
            $city_slug = sanitize_title($city);
            $cities[$city_slug] = array(
                'name' => $city,
                'parish' => $parish_data['name'],
                'parish_key' => $parish_key,
                'lat' => $parish_data['lat'],
                'lng' => $parish_data['lng'],
            );
        }
    }
    
    ksort($cities);
    return $cities;
}

// Jamaica boundaries (approximate)
function property_theme_get_jamaica_bounds() {
    return array(
        'north' => 18.55,
        'south' => 17.70,
        'east' => -75.75,
        'west' => -78.35,
    );
}

// Check if coordinates are within Jamaica
function property_theme_is_in_jamaica($lat, $lng) {
    $bounds = property_theme_get_jamaica_bounds();
    
    return is_numeric($lat) && is_numeric($lng)
        && $lat >= $bounds['south'] 
        && $lat <= $bounds['north'] 
        && $lng >= $bounds['west'] 
        && $lng <= $bounds['east'];
}

// Validate Mapbox result is in Jamaica
function property_theme_validate_mapbox_result($feature) {
    if (empty($feature['geometry']['coordinates'])) {
        return false;
    }
    
    $lng = $feature['geometry']['coordinates'][0];
    $lat = $feature['geometry']['coordinates'][1];
    
    return property_theme_is_in_jamaica($lat, $lng);
}

// REST API endpoints for Jamaica locations
add_action('rest_api_init', function() {
    register_rest_route('property-theme/v1', '/locations/jamaica/cities', array(
        'methods' => 'GET',
        'callback' => 'property_theme_get_jamaica_cities_rest',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('property-theme/v1', '/locations/jamaica/parishes', array(
        'methods' => 'GET',
        'callback' => 'property_theme_get_jamaica_parishes_rest',
        'permission_callback' => '__return_true',
    ));

    // Mapbox Geocoding Proxy Endpoint (Jamaica-only)
    register_rest_route('property-theme/v1', '/locations/geocode', array(
        'methods' => 'GET',
        'callback' => 'property_theme_geocode_address',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('property-theme/v1', '/locations/reverse-geocode', array(
        'methods' => 'GET',
        'callback' => 'property_theme_reverse_geocode',
        'permission_callback' => '__return_true',
    ));
});

function property_theme_get_jamaica_cities_rest() {
    return new WP_REST_Response(property_theme_get_jamaica_cities(), 200);
}

function property_theme_get_jamaica_parishes_rest() {
    return new WP_REST_Response(property_theme_get_jamaica_parishes(), 200);
}

/**
 * Geocode an address using Mapbox, restricted to Jamaica
 */
function property_theme_geocode_address($request) {
    $query = sanitize_text_field($request->get_param('query'));
    
    if (empty($query) || strlen($query) < 3) {
        return new WP_REST_Response(array('error' => 'Query too short'), 400);
    }

    $mapbox_token = defined('MAPBOX_PUBLIC_KEY') ? MAPBOX_PUBLIC_KEY : get_option('mapbox_public_key');
    if (!$mapbox_token) {
        return new WP_REST_Response(array('error' => 'Mapbox not configured'), 500);
    }

    // Restrict to Jamaica bounds
    $bounds = property_theme_get_jamaica_bounds();
    $bbox = "{$bounds['west']},{$bounds['south']},{$bounds['east']},{$bounds['north']}";
    $country = 'JM'; // Jamaica country code

    $url = add_query_arg(
        array(
            'access_token' => $mapbox_token,
            'country' => $country,
            'bbox' => $bbox,
            'limit' => 10,
        ),
        "https://api.mapbox.com/geocoding/v5/mapbox.places/{$query}.json"
    );

    $response = wp_remote_get($url, array(
        'timeout' => 10,
        'sslverify' => true,
    ));

    if (is_wp_error($response)) {
        return new WP_REST_Response(array('error' => 'Geocoding failed'), 500);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body['features'])) {
        return new WP_REST_Response(array('features' => array()), 200);
    }

    // Filter results to only Jamaica locations
    $jamaica_features = array_filter($body['features'], function($feature) {
        return property_theme_validate_mapbox_result($feature);
    });

    // Format response
    $formatted = array_map(function($feature) {
        $coords = $feature['geometry']['coordinates'];
        return array(
            'id' => $feature['id'],
            'place_name' => $feature['place_name'],
            'coordinates' => array(
                'longitude' => $coords[0],
                'latitude' => $coords[1],
            ),
            'center' => $coords,
            'bbox' => $feature['bbox'] ?? null,
            'place_type' => $feature['place_type'] ?? array(),
            'context' => $feature['context'] ?? array(),
        );
    }, array_values($jamaica_features));

    return new WP_REST_Response(array('features' => $formatted), 200);
}

/**
 * Reverse geocode coordinates to get address, restricted to Jamaica
 */
function property_theme_reverse_geocode($request) {
    $latitude = floatval($request->get_param('latitude'));
    $longitude = floatval($request->get_param('longitude'));

    if (empty($latitude) || empty($longitude)) {
        return new WP_REST_Response(array('error' => 'Invalid coordinates'), 400);
    }

    // Validate coordinates are in Jamaica
    if (!property_theme_is_in_jamaica($latitude, $longitude)) {
        return new WP_REST_Response(array('error' => 'Location must be in Jamaica'), 400);
    }

    $mapbox_token = defined('MAPBOX_PUBLIC_KEY') ? MAPBOX_PUBLIC_KEY : get_option('mapbox_public_key');
    if (!$mapbox_token) {
        return new WP_REST_Response(array('error' => 'Mapbox not configured'), 500);
    }

    $url = add_query_arg(
        array(
            'access_token' => $mapbox_token,
            'limit' => 1,
        ),
        "https://api.mapbox.com/geocoding/v5/mapbox.places/{$longitude},{$latitude}.json"
    );

    $response = wp_remote_get($url, array(
        'timeout' => 10,
        'sslverify' => true,
    ));

    if (is_wp_error($response)) {
        return new WP_REST_Response(array('error' => 'Reverse geocoding failed'), 500);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body['features'])) {
        return new WP_REST_Response(array(
            'address' => "{$latitude}, {$longitude}",
            'coordinates' => array(
                'latitude' => $latitude,
                'longitude' => $longitude,
            ),
        ), 200);
    }

    $feature = $body['features'][0];
    return new WP_REST_Response(array(
        'address' => $feature['place_name'],
        'coordinates' => array(
            'latitude' => $latitude,
            'longitude' => $longitude,
        ),
        'place_name' => $feature['place_name'],
        'context' => $feature['context'] ?? array(),
    ), 200);
}
