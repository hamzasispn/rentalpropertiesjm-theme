<?php
/**
 * Resources Post Type
 * Registers the 'resource' custom post type for admin-uploadable PDF/doc resources.
 * Include this file from functions.php:
 *   require_once get_template_directory() . '/inc/resources-post-type.php';
 */

// ─── Register Post Type ───────────────────────────────────────────────────────
add_action('init', 'property_theme_register_resources_cpt', 5);

function property_theme_register_resources_cpt() {
    register_post_type('resource', array(
        'label'               => 'Resources',
        'labels'              => array(
            'name'               => 'Resources',
            'singular_name'      => 'Resource',
            'add_new'            => 'Add New Resource',
            'add_new_item'       => 'Add New Resource',
            'edit_item'          => 'Edit Resource',
            'view_item'          => 'View Resource',
            'all_items'          => 'All Resources',
            'search_items'       => 'Search Resources',
            'not_found'          => 'No resources found.',
            'not_found_in_trash' => 'No resources found in Trash.',
        ),
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_rest'        => true,
        'menu_icon'           => 'dashicons-media-document',
        'supports'            => array('title', 'editor', 'thumbnail', 'custom-fields', 'excerpt'),
        'has_archive'         => true,
        'rewrite'             => array('slug' => 'resources'),
        'rest_base'           => 'resources',
    ));

    // Resource Category taxonomy
    register_taxonomy('resource_category', 'resource', array(
        'label'             => 'Resource Category',
        'hierarchical'      => true,
        'show_in_rest'      => true,
        'show_ui'           => true,
        'rewrite'           => array('slug' => 'resource-category'),
    ));
}

// ─── Meta Box for PDF/Document Upload ────────────────────────────────────────
add_action('add_meta_boxes', 'resource_add_meta_boxes');

function resource_add_meta_boxes() {
    add_meta_box(
        'resource_document',
        'Resource Document (PDF / Doc)',
        'resource_document_meta_box_callback',
        'resource',
        'normal',
        'high'
    );
    add_meta_box(
        'resource_details',
        'Resource Details',
        'resource_details_meta_box_callback',
        'resource',
        'side',
        'default'
    );
}

function resource_document_meta_box_callback($post) {
    wp_nonce_field('resource_document_nonce', 'resource_document_nonce_field');
    $file_url  = get_post_meta($post->ID, '_resource_file_url', true);
    $file_name = get_post_meta($post->ID, '_resource_file_name', true);
    $file_type = get_post_meta($post->ID, '_resource_file_type', true);
    ?>
    <div style="padding: 10px 0;">
        <p style="margin-bottom:10px; color:#555;">Upload a PDF, Word document, or other file for this resource.</p>

        <div id="resource-file-preview" style="margin-bottom:12px; <?php echo $file_url ? '' : 'display:none;'; ?>">
            <div style="display:flex; align-items:center; gap:10px; padding:10px; background:#f0f6fc; border:1px solid #c3d9ef; border-radius:6px;">
                <span style="font-size:28px;"><?php echo ($file_type === 'pdf') ? '📄' : '📋'; ?></span>
                <div>
                    <strong id="resource-file-name"><?php echo esc_html($file_name); ?></strong><br>
                    <a href="<?php echo esc_url($file_url); ?>" target="_blank" style="color:#0073aa; font-size:12px;">View File</a>
                </div>
                <button type="button" id="resource-remove-file" style="margin-left:auto; color:#cc0000; background:none; border:none; cursor:pointer; font-size:12px;">✕ Remove</button>
            </div>
        </div>

        <input type="hidden" name="resource_file_url"  id="resource_file_url"  value="<?php echo esc_url($file_url); ?>">
        <input type="hidden" name="resource_file_name" id="resource_file_name" value="<?php echo esc_attr($file_name); ?>">
        <input type="hidden" name="resource_file_type" id="resource_file_type" value="<?php echo esc_attr($file_type); ?>">

        <button type="button" id="resource-upload-btn" class="button button-primary">
            <?php echo $file_url ? 'Replace File' : 'Upload / Select File'; ?>
        </button>
    </div>

    <script>
    (function($) {
        var mediaUploader;
        $('#resource-upload-btn').on('click', function(e) {
            e.preventDefault();
            if (mediaUploader) { mediaUploader.open(); return; }
            mediaUploader = wp.media({
                title: 'Select Resource File',
                button: { text: 'Use This File' },
                multiple: false,
                library: { type: ['application/pdf', 'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-powerpoint', 'application/zip', 'text/plain'] }
            });
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#resource_file_url').val(attachment.url);
                $('#resource_file_name').val(attachment.filename || attachment.title);
                var ext = attachment.filename ? attachment.filename.split('.').pop().toLowerCase() : '';
                $('#resource_file_type').val(ext);
                $('#resource-file-name').text(attachment.filename || attachment.title);
                $('#resource-file-preview').show();
                $('#resource-upload-btn').text('Replace File');
            });
            mediaUploader.open();
        });
        $('#resource-remove-file').on('click', function() {
            $('#resource_file_url').val('');
            $('#resource_file_name').val('');
            $('#resource_file_type').val('');
            $('#resource-file-preview').hide();
            $('#resource-upload-btn').text('Upload / Select File');
        });
    })(jQuery);
    </script>
    <?php
}

function resource_details_meta_box_callback($post) {
    $download_count = get_post_meta($post->ID, '_resource_download_count', true) ?: 0;
    $is_free        = get_post_meta($post->ID, '_resource_is_free', true);
    $is_free        = ($is_free === '') ? '1' : $is_free; // default free
    ?>
    <p>
        <label style="font-weight:600;">Access</label><br>
        <label style="display:block; margin-top:6px;">
            <input type="radio" name="resource_is_free" value="1" <?php checked($is_free, '1'); ?>>
            Free (anyone can download)
        </label>
        <label style="display:block; margin-top:4px;">
            <input type="radio" name="resource_is_free" value="0" <?php checked($is_free, '0'); ?>>
            Members only
        </label>
    </p>
    <p style="margin-top:12px; color:#555; font-size:12px;">
        Downloads: <strong><?php echo intval($download_count); ?></strong>
    </p>
    <?php
}

// ─── Save Meta Box Data ───────────────────────────────────────────────────────
add_action('save_post_resource', 'resource_save_meta_box_data');

function resource_save_meta_box_data($post_id) {
    if (!isset($_POST['resource_document_nonce_field'])) return;
    if (!wp_verify_nonce($_POST['resource_document_nonce_field'], 'resource_document_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, '_resource_file_url',  esc_url_raw($_POST['resource_file_url']  ?? ''));
    update_post_meta($post_id, '_resource_file_name', sanitize_text_field($_POST['resource_file_name'] ?? ''));
    update_post_meta($post_id, '_resource_file_type', sanitize_text_field($_POST['resource_file_type'] ?? ''));
    update_post_meta($post_id, '_resource_is_free',   sanitize_text_field($_POST['resource_is_free']   ?? '1'));
}

// ─── REST API: Download endpoint (tracks downloads) ──────────────────────────
add_action('rest_api_init', function () {
    register_rest_route('property/v1', '/resource/(?P<id>\d+)/download', array(
        'methods'             => 'GET',
        'callback'            => 'resource_download_callback',
        'permission_callback' => '__return_true',
        'args'                => array(
            'id' => array('validate_callback' => function($p) { return is_numeric($p); }),
        ),
    ));
});

function resource_download_callback(WP_REST_Request $request) {
    $post_id = intval($request['id']);
    $post    = get_post($post_id);

    if (!$post || $post->post_type !== 'resource' || $post->post_status !== 'publish') {
        return new WP_Error('not_found', 'Resource not found', array('status' => 404));
    }

    $is_free = get_post_meta($post_id, '_resource_is_free', true);
    if ($is_free === '0' && !is_user_logged_in()) {
        return new WP_Error('forbidden', 'Login required to download this resource', array('status' => 403));
    }

    $file_url = get_post_meta($post_id, '_resource_file_url', true);
    if (empty($file_url)) {
        return new WP_Error('no_file', 'No file attached to this resource', array('status' => 404));
    }

    // Increment download count
    $count = (int) get_post_meta($post_id, '_resource_download_count', true);
    update_post_meta($post_id, '_resource_download_count', $count + 1);

    return rest_ensure_response(array(
        'success'  => true,
        'file_url' => $file_url,
    ));
}

// ─── REST API: Resources search endpoint ─────────────────────────────────────
add_action('rest_api_init', function () {
    register_rest_route('property/v1', '/resources', array(
        'methods'             => 'GET',
        'callback'            => 'resources_list_api',
        'permission_callback' => '__return_true',
    ));
});

function resources_list_api(WP_REST_Request $request) {
    $category = sanitize_text_field($request->get_param('category') ?? '');
    $search   = sanitize_text_field($request->get_param('search')   ?? '');
    $paged    = max(1, intval($request->get_param('paged') ?? 1));
    $per_page = min(24, intval($request->get_param('per_page') ?? 12));

    $args = array(
        'post_type'      => 'resource',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if ($search) $args['s'] = $search;

    if ($category) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'resource_category',
                'field'    => 'slug',
                'terms'    => $category,
            ),
        );
    }

    $query     = new WP_Query($args);
    $resources = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $pid = get_the_ID();

            $cats = get_the_terms($pid, 'resource_category');
            $category_names = (!is_wp_error($cats) && $cats) ? array_map(fn($c) => $c->name, $cats) : array();

            $resources[] = array(
                'id'             => $pid,
                'title'          => get_the_title(),
                'excerpt'        => get_the_excerpt(),
                'permalink'      => get_permalink(),
                'thumbnail'      => get_the_post_thumbnail_url($pid, 'medium') ?: '',
                'file_url'       => get_post_meta($pid, '_resource_file_url', true),
                'file_name'      => get_post_meta($pid, '_resource_file_name', true),
                'file_type'      => get_post_meta($pid, '_resource_file_type', true),
                'is_free'        => get_post_meta($pid, '_resource_is_free', true) !== '0',
                'download_count' => (int) get_post_meta($pid, '_resource_download_count', true),
                'categories'     => $category_names,
                'date'           => get_the_date('F j, Y'),
            );
        }
    }
    wp_reset_postdata();

    return rest_ensure_response(array(
        'success'   => true,
        'resources' => $resources,
        'total'     => $query->found_posts,
        'pages'     => $query->max_num_pages,
    ));
}
