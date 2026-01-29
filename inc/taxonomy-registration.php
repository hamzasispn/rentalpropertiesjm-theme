<?php
/**
 * Property Listing Taxonomies - Purpose, Bedrooms, Bathrooms, Amenities
 * Replaces hardcoded bed/bath meta fields with flexible taxonomy system
 */

// Register Property Purpose Taxonomy (For Rent / For Sale)
function property_theme_register_purpose_taxonomy() {
    $labels = array(
        'name'              => 'Property Purpose',
        'singular_name'     => 'Purpose',
        'menu_name'         => 'Property Purpose',
        'all_items'         => 'All Purposes',
        'edit_item'         => 'Edit Purpose',
        'view_item'         => 'View Purpose',
        'update_item'       => 'Update Purpose',
        'add_new_item'      => 'Add New Purpose',
        'new_item_name'     => 'New Purpose',
        'search_items'      => 'Search Purposes',
    );

    $args = array(
        'label'             => 'Property Purpose',
        'labels'            => $labels,
        'public'            => true,
        'publicly_queryable'=> true,
        'hierarchical'      => false,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_nav_menus' => true,
        'show_tagcloud'     => false,
        'show_in_rest'      => true,
        'rest_base'         => 'property_purpose',
    );

    register_taxonomy('property_purpose', 'property', $args);

    // Create default terms
    if (!term_exists('rent', 'property_purpose')) {
        wp_insert_term('For Rent', 'property_purpose', array('slug' => 'rent'));
    }
    if (!term_exists('sale', 'property_purpose')) {
        wp_insert_term('For Sale', 'property_purpose', array('slug' => 'sale'));
    }
}
add_action('init', 'property_theme_register_purpose_taxonomy', 0);

// Register Bedrooms Taxonomy (1, 2, 3... 10+)
function property_theme_register_bedrooms_taxonomy() {
    $labels = array(
        'name'              => 'Bedrooms',
        'singular_name'     => 'Bedroom Count',
        'menu_name'         => 'Bedrooms',
        'all_items'         => 'All Bedroom Counts',
        'edit_item'         => 'Edit Bedroom Count',
        'view_item'         => 'View Bedroom Count',
        'update_item'       => 'Update Bedroom Count',
        'add_new_item'      => 'Add New Bedroom Count',
        'new_item_name'     => 'New Bedroom Count',
        'search_items'      => 'Search Bedroom Counts',
    );

    $args = array(
        'label'             => 'Bedrooms',
        'labels'            => $labels,
        'public'            => true,
        'publicly_queryable'=> true,
        'hierarchical'      => false,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_nav_menus' => true,
        'show_tagcloud'     => false,
        'show_in_rest'      => true,
        'rest_base'         => 'bedrooms',
    );

    register_taxonomy('bedrooms', 'property', $args);

    // Create default terms
    $bedroom_counts = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '10+');
    foreach ($bedroom_counts as $count) {
        if (!term_exists('bed-' . $count, 'bedrooms')) {
            wp_insert_term($count, 'bedrooms', array('slug' => 'bed-' . $count));
        }
    }
}
add_action('init', 'property_theme_register_bedrooms_taxonomy', 0);

// Register Bathrooms Taxonomy (1, 2, 3... 10+)
function property_theme_register_bathrooms_taxonomy() {
    $labels = array(
        'name'              => 'Bathrooms',
        'singular_name'     => 'Bathroom Count',
        'menu_name'         => 'Bathrooms',
        'all_items'         => 'All Bathroom Counts',
        'edit_item'         => 'Edit Bathroom Count',
        'view_item'         => 'View Bathroom Count',
        'update_item'       => 'Update Bathroom Count',
        'add_new_item'      => 'Add New Bathroom Count',
        'new_item_name'     => 'New Bathroom Count',
        'search_items'      => 'Search Bathroom Counts',
    );

    $args = array(
        'label'             => 'Bathrooms',
        'labels'            => $labels,
        'public'            => true,
        'publicly_queryable'=> true,
        'hierarchical'      => false,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_nav_menus' => true,
        'show_tagcloud'     => false,
        'show_in_rest'      => true,
        'rest_base'         => 'bathrooms',
    );

    register_taxonomy('bathrooms', 'property', $args);

    // Create default terms
    $bathroom_counts = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '10+');
    foreach ($bathroom_counts as $count) {
        if (!term_exists('bath-' . $count, 'bathrooms')) {
            wp_insert_term($count, 'bathrooms', array('slug' => 'bath-' . $count));
        }
    }
}
add_action('init', 'property_theme_register_bathrooms_taxonomy', 0);

// Register Amenities Taxonomy with parent-child structure
function property_theme_register_amenities_taxonomy() {
    $labels = array(
        'name'              => 'Amenities',
        'singular_name'     => 'Amenity',
        'menu_name'         => 'Amenities',
        'all_items'         => 'All Amenities',
        'edit_item'         => 'Edit Amenity',
        'view_item'         => 'View Amenity',
        'update_item'       => 'Update Amenity',
        'add_new_item'      => 'Add New Amenity',
        'new_item_name'     => 'New Amenity',
        'parent_item'       => 'Parent Amenity',
        'parent_item_colon' => 'Parent Amenity:',
        'search_items'      => 'Search Amenities',
    );

    $args = array(
        'label'             => 'Amenities',
        'labels'            => $labels,
        'public'            => true,
        'publicly_queryable'=> true,
        'hierarchical'      => true, // allows parent-child structure
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_nav_menus' => true,
        'show_tagcloud'     => false,
        'show_in_rest'      => true,
        'rest_base'         => 'amenities',
    );

    register_taxonomy('amenities', 'property', $args);
}
add_action('init', 'property_theme_register_amenities_taxonomy', 0);

function property_theme_register_amenity_meta_fields() {
    register_term_meta('amenities', '_amenity_field_type', array(
        'type'              => 'string',
        'single'            => true,
        'show_in_rest'      => true,
        'sanitize_callback' => function($value) {
            return in_array($value, array('checkbox', 'text', 'select')) ? $value : 'checkbox';
        },
    ));

    register_term_meta('amenities', '_amenity_options', array(
        'type'              => 'string',
        'single'            => true,
        'show_in_rest'      => true,
        'sanitize_callback' => 'sanitize_textarea_field',
    ));

    register_term_meta('amenities', '_amenity_icon', array(
        'type'              => 'string',
        'single'            => true,
        'show_in_rest'      => true,
        'sanitize_callback' => 'sanitize_text_field',
    ));
}
add_action('init', 'property_theme_register_amenity_meta_fields', 5);

function property_theme_amenity_meta_box() {
    add_meta_box(
        'amenity_configuration',
        'Amenity Configuration',
        'property_theme_amenity_meta_box_callback',
        'edit-amenities',
        'normal',
        'high'
    );
}
add_action('admin_init', 'property_theme_amenity_meta_box');

function property_theme_amenity_meta_box_callback($term) {
    wp_nonce_field('amenity_nonce', 'amenity_nonce');
    
    $field_type = get_term_meta($term->term_id, '_amenity_field_type', true) ?: 'checkbox';
    $options = get_term_meta($term->term_id, '_amenity_options', true) ?: '';
    $icon = get_term_meta($term->term_id, '_amenity_icon', true) ?: '';
    ?>
    <div style="padding: 15px;">
        <div style="margin-bottom: 15px;">
            <label for="amenity_field_type" style="display: block; margin-bottom: 5px; font-weight: bold;">Field Type</label>
            <select id="amenity_field_type" name="amenity_field_type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="checkbox" <?php selected($field_type, 'checkbox'); ?>>Checkbox (Yes/No)</option>
                <option value="text" <?php selected($field_type, 'text'); ?>>Text Input</option>
                <option value="select" <?php selected($field_type, 'select'); ?>>Dropdown Select</option>
            </select>
            <p style="font-size: 12px; color: #666; margin-top: 5px;">Choose how this amenity is displayed in add property form</p>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="amenity_options" style="display: block; margin-bottom: 5px; font-weight: bold;">Options (for Select/Dropdown)</label>
            <textarea id="amenity_options" name="amenity_options" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="One option per line. Only used if field type is Select."><?php echo esc_textarea($options); ?></textarea>
            <p style="font-size: 12px; color: #666; margin-top: 5px;">Enter options one per line (e.g., "Option 1", "Option 2", "Option 3")</p>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="amenity_icon" style="display: block; margin-bottom: 5px; font-weight: bold;">Icon Class (Font Awesome or custom)</label>
            <input type="text" id="amenity_icon" name="amenity_icon" value="<?php echo esc_attr($icon); ?>" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="e.g., fa-wifi, fa-swimmingpool">
        </div>
    </div>
    <?php
}

function property_theme_save_amenity_meta($term_id) {
    if (!isset($_POST['amenity_nonce']) || !wp_verify_nonce($_POST['amenity_nonce'], 'amenity_nonce')) {
        return;
    }

    if (isset($_POST['amenity_field_type'])) {
        update_term_meta($term_id, '_amenity_field_type', sanitize_text_field($_POST['amenity_field_type']));
    }
    if (isset($_POST['amenity_options'])) {
        update_term_meta($term_id, '_amenity_options', sanitize_textarea_field($_POST['amenity_options']));
    }
    if (isset($_POST['amenity_icon'])) {
        update_term_meta($term_id, '_amenity_icon', sanitize_text_field($_POST['amenity_icon']));
    }
}
add_action('edit_amenities', 'property_theme_save_amenity_meta', 10, 1);
add_action('create_amenities', 'property_theme_save_amenity_meta', 10, 1);

// Helper function to get amenity configuration
function property_theme_get_amenity_config($amenity_id) {
    return array(
        'id'         => $amenity_id,
        'field_type' => get_term_meta($amenity_id, '_amenity_field_type', true) ?: 'checkbox',
        'options'    => array_filter(array_map('trim', explode("\n", get_term_meta($amenity_id, '_amenity_options', true) ?: ''))),
        'icon'       => get_term_meta($amenity_id, '_amenity_icon', true) ?: '',
    );
}
