<?php
/**
 * Admin Panel REST API Endpoints
 * All routes require manage_options capability
 */

add_action('rest_api_init', function () {

    $admin_check = function () {
        return current_user_can('manage_options');
    };

    // Get pending properties
    register_rest_route('property-theme/v1', '/admin/pending-properties', [
        'methods'             => 'GET',
        'callback'            => 'admin_get_pending_properties',
        'permission_callback' => $admin_check,
    ]);

    // Approve property
    register_rest_route('property-theme/v1', '/admin/approve-property', [
        'methods'             => 'POST',
        'callback'            => 'admin_approve_property',
        'permission_callback' => $admin_check,
    ]);

    // Reject property
    register_rest_route('property-theme/v1', '/admin/reject-property', [
        'methods'             => 'POST',
        'callback'            => 'admin_reject_property',
        'permission_callback' => $admin_check,
    ]);

    // Add blog post
    register_rest_route('property-theme/v1', '/admin/add-blog', [
        'methods'             => 'POST',
        'callback'            => 'admin_add_blog_post',
        'permission_callback' => $admin_check,
    ]);

    // Add resource / PDF
    register_rest_route('property-theme/v1', '/admin/add-resource', [
        'methods'             => 'POST',
        'callback'            => 'admin_add_resource',
        'permission_callback' => $admin_check,
    ]);

    // Get site stats
    register_rest_route('property-theme/v1', '/admin/stats', [
        'methods'             => 'GET',
        'callback'            => 'admin_get_stats',
        'permission_callback' => $admin_check,
    ]);

    // Get all properties (published + pending) for admin list
    register_rest_route('property-theme/v1', '/admin/all-properties', [
        'methods'             => 'GET',
        'callback'            => 'admin_get_all_properties',
        'permission_callback' => $admin_check,
    ]);

    // Get all blog posts for admin list
    register_rest_route('property-theme/v1', '/admin/blogs', [
        'methods'             => 'GET',
        'callback'            => 'admin_get_blogs',
        'permission_callback' => $admin_check,
    ]);

    // Delete blog post
    register_rest_route('property-theme/v1', '/admin/delete-blog', [
        'methods'             => 'POST',
        'callback'            => 'admin_delete_blog',
        'permission_callback' => $admin_check,
    ]);

    // Get all resources for admin list
    register_rest_route('property-theme/v1', '/admin/resources', [
        'methods'             => 'GET',
        'callback'            => 'admin_get_resources',
        'permission_callback' => $admin_check,
    ]);

    // Delete resource
    register_rest_route('property-theme/v1', '/admin/delete-resource', [
        'methods'             => 'POST',
        'callback'            => 'admin_delete_resource',
        'permission_callback' => $admin_check,
    ]);

    // Upload media for admin (blog images, resource files)
    register_rest_route('property-theme/v1', '/admin/upload-file', [
        'methods'             => 'POST',
        'callback'            => 'admin_upload_file',
        'permission_callback' => $admin_check,
    ]);

    // Get all members with subscriptions
    register_rest_route('property-theme/v1', '/admin/members', [
        'methods'             => 'GET',
        'callback'            => 'admin_get_members',
        'permission_callback' => $admin_check,
    ]);

    // Get single member detail
    register_rest_route('property-theme/v1', '/admin/member/(?P<id>\d+)', [
        'methods'             => 'GET',
        'callback'            => 'admin_get_member_detail',
        'permission_callback' => $admin_check,
    ]);

    // Delete a member (and their properties + subscriptions)
    register_rest_route('property-theme/v1', '/admin/delete-member', [
        'methods'             => 'POST',
        'callback'            => 'admin_delete_member',
        'permission_callback' => $admin_check,
    ]);
});

function admin_get_pending_properties($request) {
    $paged    = max(1, intval($request->get_param('paged') ?? 1));
    $per_page = min(50, max(1, intval($request->get_param('per_page') ?? 20)));

    $query = new WP_Query([
        'post_type'      => 'property',
        'post_status'    => 'pending',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    $properties = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $pid = get_the_ID();
            $author = get_userdata(get_post_field('post_author', $pid));
            $properties[] = [
                'id'           => $pid,
                'title'        => get_the_title(),
                'price'        => get_post_meta($pid, '_property_price', true),
                'city'         => get_post_meta($pid, '_property_city', true),
                'address'      => get_post_meta($pid, '_property_address', true),
                'image'        => get_the_post_thumbnail_url($pid, 'medium') ?: null,
                'author_name'  => $author ? $author->display_name : 'Unknown',
                'author_email' => $author ? $author->user_email : '',
                'date'         => get_the_date('M j, Y'),
                'permalink'    => get_permalink(),
            ];
        }
    }
    wp_reset_postdata();

    return rest_ensure_response([
        'success'    => true,
        'properties' => $properties,
        'total'      => $query->found_posts,
        'pages'      => $query->max_num_pages,
    ]);
}

function admin_get_all_properties($request) {
    $paged    = max(1, intval($request->get_param('paged') ?? 1));
    $per_page = min(50, max(1, intval($request->get_param('per_page') ?? 20)));
    $status   = sanitize_text_field($request->get_param('status') ?? 'all');

    $statuses = ($status === 'all') ? ['publish', 'pending', 'draft'] : [$status];

    $query = new WP_Query([
        'post_type'      => 'property',
        'post_status'    => $statuses,
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    $properties = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $pid    = get_the_ID();
            $author = get_userdata(get_post_field('post_author', $pid));
            $properties[] = [
                'id'           => $pid,
                'title'        => get_the_title(),
                'status'       => get_post_status($pid),
                'price'        => get_post_meta($pid, '_property_price', true),
                'city'         => get_post_meta($pid, '_property_city', true),
                'image'        => get_the_post_thumbnail_url($pid, 'thumbnail') ?: null,
                'author_name'  => $author ? $author->display_name : 'Unknown',
                'date'         => get_the_date('M j, Y'),
                'permalink'    => get_permalink(),
                'rejection'    => get_post_meta($pid, '_property_rejection_reason', true),
            ];
        }
    }
    wp_reset_postdata();

    return rest_ensure_response([
        'success'    => true,
        'properties' => $properties,
        'total'      => $query->found_posts,
        'pages'      => $query->max_num_pages,
    ]);
}

function admin_approve_property($request) {
    $post_id = intval($request->get_param('property_id'));
    if (!$post_id) return new WP_Error('missing_id', 'Property ID required', ['status' => 400]);

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'property') {
        return new WP_Error('not_found', 'Property not found', ['status' => 404]);
    }

    $result = wp_update_post(['ID' => $post_id, 'post_status' => 'publish'], true);
    if (is_wp_error($result)) {
        return new WP_Error('update_failed', $result->get_error_message(), ['status' => 500]);
    }

    delete_post_meta($post_id, '_property_rejection_reason');

    // Notify user
    $author = get_userdata($post->post_author);
    if ($author && $author->user_email) {
        wp_mail(
            $author->user_email,
            'Your property listing has been approved!',
            'Great news! Your property "' . $post->post_title . '" has been approved and is now live on the site. View it here: ' . get_permalink($post_id),
            ['Content-Type: text/plain; charset=UTF-8']
        );
    }

    return rest_ensure_response(['success' => true, 'message' => 'Property approved and published.']);
}

function admin_reject_property($request) {
    $post_id = intval($request->get_param('property_id'));
    $reason  = sanitize_textarea_field($request->get_param('reason') ?? '');

    if (!$post_id) return new WP_Error('missing_id', 'Property ID required', ['status' => 400]);

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'property') {
        return new WP_Error('not_found', 'Property not found', ['status' => 404]);
    }

    wp_update_post(['ID' => $post_id, 'post_status' => 'draft']);
    update_post_meta($post_id, '_property_rejection_reason', $reason);

    // Notify user
    $author = get_userdata($post->post_author);
    if ($author && $author->user_email) {
        $msg = 'Your property "' . $post->post_title . '" was not approved.';
        if ($reason) $msg .= "\n\nReason: " . $reason;
        $msg .= "\n\nPlease update your listing and resubmit.";
        wp_mail(
            $author->user_email,
            'Property listing requires changes',
            $msg,
            ['Content-Type: text/plain; charset=UTF-8']
        );
    }

    return rest_ensure_response(['success' => true, 'message' => 'Property rejected.']);
}

function admin_add_blog_post($request) {
    $title   = sanitize_text_field($request->get_param('title') ?? '');
    $content = wp_kses_post($request->get_param('content') ?? '');
    $excerpt = sanitize_textarea_field($request->get_param('excerpt') ?? '');
    $status  = in_array($request->get_param('status'), ['publish', 'draft']) ? $request->get_param('status') : 'publish';
    $image_id = intval($request->get_param('image_id') ?? 0);
    $category = intval($request->get_param('category_id') ?? 0);

    if (empty($title)) return new WP_Error('missing_title', 'Title required', ['status' => 400]);

    $post_id = wp_insert_post([
        'post_type'    => 'post',
        'post_title'   => $title,
        'post_content' => $content,
        'post_excerpt' => $excerpt,
        'post_status'  => $status,
        'post_author'  => get_current_user_id(),
    ], true);

    if (is_wp_error($post_id)) {
        return new WP_Error('insert_failed', $post_id->get_error_message(), ['status' => 500]);
    }

    if ($image_id) set_post_thumbnail($post_id, $image_id);
    if ($category) wp_set_post_categories($post_id, [$category]);

    return rest_ensure_response([
        'success'   => true,
        'message'   => 'Blog post created.',
        'post_id'   => $post_id,
        'permalink' => get_permalink($post_id),
    ]);
}

function admin_add_resource($request) {
    $title        = sanitize_text_field($request->get_param('title') ?? '');
    $description  = wp_kses_post($request->get_param('description') ?? '');
    $file_url     = esc_url_raw($request->get_param('file_url') ?? '');
    $file_name    = sanitize_file_name($request->get_param('file_name') ?? '');
    $file_type    = sanitize_text_field($request->get_param('file_type') ?? 'pdf');
    $category     = intval($request->get_param('category_id') ?? 0);
    $image_id     = intval($request->get_param('image_id') ?? 0);

    if (empty($title)) return new WP_Error('missing_title', 'Title required', ['status' => 400]);

    $post_id = wp_insert_post([
        'post_type'    => 'resource',
        'post_title'   => $title,
        'post_content' => $description,
        'post_status'  => 'publish',
        'post_author'  => get_current_user_id(),
    ], true);

    if (is_wp_error($post_id)) {
        return new WP_Error('insert_failed', $post_id->get_error_message(), ['status' => 500]);
    }

    update_post_meta($post_id, '_resource_file_url',  $file_url);
    update_post_meta($post_id, '_resource_file_name', $file_name);
    update_post_meta($post_id, '_resource_file_type', $file_type);

    if ($image_id) set_post_thumbnail($post_id, $image_id);
    if ($category) wp_set_post_terms($post_id, [$category], 'resource_category');

    return rest_ensure_response([
        'success'   => true,
        'message'   => 'Resource added.',
        'post_id'   => $post_id,
        'permalink' => get_permalink($post_id),
    ]);
}

function admin_get_stats($request) {
    global $wpdb;

    $total_properties   = wp_count_posts('property');
    $total_resources    = wp_count_posts('resource');
    $total_blogs        = wp_count_posts('post');
    $total_users        = count_users();
    $pending_properties = intval($total_properties->pending ?? 0);
    $live_properties    = intval($total_properties->publish ?? 0);

    $analytics = $wpdb->get_results(
        "SELECT event_type, COUNT(*) as count
         FROM {$wpdb->prefix}user_subscriptions
         WHERE status = 'active'",
        ARRAY_A
    );
    $active_subs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}user_subscriptions WHERE status = 'active'");

    $recent_signups = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users} WHERE user_registered > DATE_SUB(NOW(), INTERVAL 30 DAY)");

    $page_views = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}property_analytics WHERE event_type = 'page_view' AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");

    return rest_ensure_response([
        'success'             => true,
        'live_properties'     => $live_properties,
        'pending_properties'  => $pending_properties,
        'total_blogs'         => intval($total_blogs->publish ?? 0),
        'total_resources'     => intval($total_resources->publish ?? 0),
        'total_users'         => $total_users['total_users'],
        'active_subscriptions'=> intval($active_subs),
        'recent_signups'      => intval($recent_signups),
        'page_views_30d'      => intval($page_views),
    ]);
}

function admin_get_blogs($request) {
    $paged    = max(1, intval($request->get_param('paged') ?? 1));
    $per_page = min(50, max(1, intval($request->get_param('per_page') ?? 20)));

    $query = new WP_Query([
        'post_type'      => 'post',
        'post_status'    => ['publish', 'draft'],
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    $posts = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $pid = get_the_ID();
            $posts[] = [
                'id'        => $pid,
                'title'     => get_the_title(),
                'status'    => get_post_status($pid),
                'image'     => get_the_post_thumbnail_url($pid, 'thumbnail') ?: null,
                'date'      => get_the_date('M j, Y'),
                'permalink' => get_permalink(),
                'excerpt'   => wp_trim_words(get_the_excerpt(), 15),
            ];
        }
    }
    wp_reset_postdata();

    return rest_ensure_response([
        'success' => true,
        'posts'   => $posts,
        'total'   => $query->found_posts,
        'pages'   => $query->max_num_pages,
    ]);
}

function admin_delete_blog($request) {
    $post_id = intval($request->get_param('post_id'));
    if (!$post_id) return new WP_Error('missing_id', 'Post ID required', ['status' => 400]);
    $result = wp_trash_post($post_id);
    if (!$result) return new WP_Error('delete_failed', 'Could not delete post', ['status' => 500]);
    return rest_ensure_response(['success' => true, 'message' => 'Post moved to trash.']);
}

function admin_get_resources($request) {
    $paged    = max(1, intval($request->get_param('paged') ?? 1));
    $per_page = min(50, max(1, intval($request->get_param('per_page') ?? 20)));

    $query = new WP_Query([
        'post_type'      => 'resource',
        'post_status'    => ['publish', 'draft'],
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    $resources = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $pid = get_the_ID();
            $resources[] = [
                'id'        => $pid,
                'title'     => get_the_title(),
                'status'    => get_post_status($pid),
                'file_url'  => get_post_meta($pid, '_resource_file_url', true),
                'file_type' => get_post_meta($pid, '_resource_file_type', true),
                'date'      => get_the_date('M j, Y'),
                'permalink' => get_permalink(),
            ];
        }
    }
    wp_reset_postdata();

    return rest_ensure_response([
        'success'   => true,
        'resources' => $resources,
        'total'     => $query->found_posts,
        'pages'     => $query->max_num_pages,
    ]);
}

function admin_delete_resource($request) {
    $post_id = intval($request->get_param('post_id'));
    if (!$post_id) return new WP_Error('missing_id', 'Post ID required', ['status' => 400]);
    $result = wp_trash_post($post_id);
    if (!$result) return new WP_Error('delete_failed', 'Could not delete resource', ['status' => 500]);
    return rest_ensure_response(['success' => true, 'message' => 'Resource moved to trash.']);
}

function admin_upload_file($request) {
    if (empty($_FILES['file'])) {
        return new WP_Error('no_file', 'No file uploaded', ['status' => 400]);
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $attachment_id = media_handle_upload('file', 0);
    if (is_wp_error($attachment_id)) {
        return new WP_Error('upload_failed', $attachment_id->get_error_message(), ['status' => 500]);
    }

    return rest_ensure_response([
        'success' => true,
        'id'      => $attachment_id,
        'url'     => wp_get_attachment_url($attachment_id),
        'name'    => basename(get_attached_file($attachment_id)),
    ]);
}

function admin_get_members($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'user_subscriptions';

    $users = get_users(['number' => -1, 'role__not_in' => ['administrator']]);
    $members = [];

    foreach ($users as $user) {
        $sub = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, p.post_title as plan_name
             FROM $table s
             LEFT JOIN {$wpdb->posts} p ON p.ID = s.plan_id
             WHERE s.user_id = %d
             ORDER BY s.id DESC LIMIT 1",
            $user->ID
        ));

        $prop_count = count(get_posts([
            'post_type'      => 'property',
            'author'         => $user->ID,
            'post_status'    => ['publish', 'pending', 'draft'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]));

        $members[] = [
            'user_id'        => $user->ID,
            'name'           => $user->display_name,
            'email'          => $user->user_email,
            'sub_status'     => $sub ? $sub->status : null,
            'plan_name'      => $sub ? $sub->plan_name : null,
            'expires_at'     => ($sub && $sub->expires_at) ? date('M j, Y', strtotime($sub->expires_at)) : null,
            'property_count' => $prop_count,
        ];
    }

    return rest_ensure_response(['success' => true, 'members' => $members]);
}

/**
 * Delete a member: their properties, leads on those properties, subscription rows,
 * then the user account. Refuses to delete administrators or the current user.
 */
function admin_delete_member($request) {
    global $wpdb;

    $user_id = intval($request->get_param('user_id'));
    if (!$user_id) {
        return new WP_Error('missing_id', 'User ID required', ['status' => 400]);
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return new WP_Error('not_found', 'User not found', ['status' => 404]);
    }

    if (in_array('administrator', (array) $user->roles, true)) {
        return new WP_Error('forbidden', 'Cannot delete an administrator account.', ['status' => 403]);
    }

    if ($user_id === get_current_user_id()) {
        return new WP_Error('forbidden', 'You cannot delete your own account here.', ['status' => 403]);
    }

    // Force-delete the user's properties (and the leads attached to them)
    $property_ids = get_posts([
        'post_type'      => 'property',
        'author'         => $user_id,
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    $deleted_properties = 0;
    foreach ($property_ids as $pid) {
        $wpdb->delete($wpdb->prefix . 'property_leads',     ['property_id' => $pid], ['%d']);
        $wpdb->delete($wpdb->prefix . 'property_analytics', ['property_id' => $pid], ['%d']);
        if (wp_delete_post($pid, true)) $deleted_properties++;
    }

    // Drop subscription rows
    $wpdb->delete($wpdb->prefix . 'user_subscriptions', ['user_id' => $user_id], ['%d']);

    // Delete the user account itself
    require_once ABSPATH . 'wp-admin/includes/user.php';
    $deleted = wp_delete_user($user_id);

    if (!$deleted) {
        return new WP_Error('delete_failed', 'Could not delete user account.', ['status' => 500]);
    }

    return rest_ensure_response([
        'success'            => true,
        'message'            => 'Member and all related data deleted.',
        'deleted_properties' => $deleted_properties,
    ]);
}

function admin_get_member_detail($request) {
    global $wpdb;
    $user_id = intval($request['id']);
    $user    = get_userdata($user_id);
    if (!$user) return new WP_Error('not_found', 'User not found', ['status' => 404]);

    $table = $wpdb->prefix . 'user_subscriptions';
    $sub   = $wpdb->get_row($wpdb->prepare(
        "SELECT s.*, p.post_title as plan_name
         FROM $table s
         LEFT JOIN {$wpdb->posts} p ON p.ID = s.plan_id
         WHERE s.user_id = %d AND s.status = 'active'
         ORDER BY s.id DESC LIMIT 1",
        $user_id
    ));

    $subscription = null;
    if ($sub) {
        $price         = (float) get_post_meta($sub->plan_id, '_plan_price', true);
        $billing_cycle = get_post_meta($sub->plan_id, '_plan_billing_cycle', true) ?: 'monthly';
        $started       = strtotime($sub->started_at);
        $expires       = $sub->expires_at ? strtotime($sub->expires_at) : null;
        $now           = time();
        $days_left     = 0;
        $progress_pct  = 0;

        if ($expires) {
            $total        = $expires - $started;
            $elapsed      = $now - $started;
            $days_left    = max(0, (int) ceil(($expires - $now) / 86400));
            $progress_pct = $total > 0 ? min(100, (int) round(($elapsed / $total) * 100)) : 100;
        }

        $subscription = [
            'plan_name'    => $sub->plan_name,
            'status'       => $sub->status,
            'price'        => $price,
            'billing_cycle'=> $billing_cycle,
            'started_at'   => date('M j, Y', $started),
            'expires_at'   => $expires ? date('M j, Y', $expires) : 'No expiry',
            'progress_pct' => 100 - $progress_pct,
            'days_left'    => $days_left,
        ];
    }

    $prop_ids   = get_posts([
        'post_type'      => 'property',
        'author'         => $user_id,
        'post_status'    => ['publish', 'pending', 'draft'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);
    $properties = [];
    foreach ($prop_ids as $pid) {
        $properties[] = [
            'id'        => $pid,
            'title'     => get_the_title($pid),
            'status'    => get_post_status($pid),
            'price'     => get_post_meta($pid, '_property_price', true),
            'city'      => get_post_meta($pid, '_property_city', true),
            'date'      => get_the_date('M j, Y', $pid),
            'permalink' => get_permalink($pid),
        ];
    }

    return rest_ensure_response([
        'success' => true,
        'member'  => [
            'user_id'        => $user->ID,
            'name'           => $user->display_name,
            'email'          => $user->user_email,
            'registered'     => date('M j, Y', strtotime($user->user_registered)),
            'property_count' => count($properties),
            'subscription'   => $subscription,
            'properties'     => $properties,
        ],
    ]);
}
