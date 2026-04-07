<?php
/**
 * Property Status Taxonomy — Buy / Rent
 * Registers a hierarchical "status" taxonomy for filtering properties.
 */

add_action('init', 'property_theme_register_status_taxonomy', 5);

function property_theme_register_status_taxonomy() {
    register_taxonomy('property_listing_status', 'property', array(
        'label'             => 'Listing Status',
        'labels'            => array(
            'name'          => 'Listing Status',
            'singular_name' => 'Status',
            'all_items'     => 'All Statuses',
            'edit_item'     => 'Edit Status',
            'add_new_item'  => 'Add New Status',
            'new_item_name' => 'New Status',
            'menu_name'     => 'Listing Status',
        ),
        'public'            => true,
        'publicly_queryable'=> true,
        'hierarchical'      => false,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_nav_menus' => true,
        'show_tagcloud'     => false,
        'show_in_rest'      => true,
        'rest_base'         => 'listing_status',
        'rewrite'           => array('slug' => 'listing-status'),
    ));

    // Seed default terms only once
    if (!term_exists('buy', 'property_listing_status')) {
        wp_insert_term('Buy',  'property_listing_status', array('slug' => 'buy'));
    }
    if (!term_exists('rent', 'property_listing_status')) {
        wp_insert_term('Rent', 'property_listing_status', array('slug' => 'rent'));
    }
}

/**
 * REST endpoint: get listing statuses
 */
add_action('rest_api_init', function () {
    register_rest_route('property-theme/v1', '/listing-statuses', array(
        'methods'             => 'GET',
        'callback'            => function () {
            $terms = get_terms(array('taxonomy' => 'property_listing_status', 'hide_empty' => false));
            return is_wp_error($terms) ? array() : array_map(function ($t) {
                return array('id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug);
            }, $terms);
        },
        'permission_callback' => '__return_true',
    ));
});
