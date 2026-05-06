<?php
/**
 * Email Verification System
 * - Issues a hashed token on registration and emails a verification link
 * - Blocks unverified users from logging in
 * - Resends verification email on demand
 */

if (!defined('ABSPATH')) exit;

const PROPERTY_THEME_EMAIL_VERIFY_META   = '_property_email_verified';
const PROPERTY_THEME_EMAIL_TOKEN_META    = '_property_email_verify_token';
const PROPERTY_THEME_EMAIL_TOKEN_EXP     = '_property_email_verify_expires';
const PROPERTY_THEME_EMAIL_TOKEN_TTL     = 3 * DAY_IN_SECONDS;

/**
 * Generate a verification token, store its hash + expiry, and return the raw token.
 */
function property_theme_issue_email_verification_token($user_id) {
    $user_id = intval($user_id);
    if ($user_id <= 0) return '';

    $token = bin2hex(random_bytes(24));
    update_user_meta($user_id, PROPERTY_THEME_EMAIL_TOKEN_META, wp_hash($token));
    update_user_meta($user_id, PROPERTY_THEME_EMAIL_TOKEN_EXP, time() + PROPERTY_THEME_EMAIL_TOKEN_TTL);
    update_user_meta($user_id, PROPERTY_THEME_EMAIL_VERIFY_META, '0');
    return $token;
}

/**
 * Build the verification URL for a freshly issued raw token.
 */
function property_theme_build_email_verification_url($user_id, $token) {
    return add_query_arg(array(
        'rpjm_verify' => '1',
        'uid'         => intval($user_id),
        'token'       => rawurlencode($token),
    ), home_url('/'));
}

/**
 * Send the verification email.
 */
function property_theme_send_verification_email($user_id) {
    $user = get_userdata($user_id);
    if (!$user) return false;

    $token = property_theme_issue_email_verification_token($user_id);
    if (!$token) return false;

    $verify_url = property_theme_build_email_verification_url($user_id, $token);
    $site_name  = get_bloginfo('name');

    $subject = sprintf('Verify your email for %s', $site_name);
    $body    = "<p>Hi " . esc_html($user->display_name ?: $user->user_login) . ",</p>";
    $body   .= "<p>Thanks for joining <strong>" . esc_html($site_name) . "</strong>. Please confirm your email address to activate your account:</p>";
    $body   .= "<p><a href='" . esc_url($verify_url) . "' style='display:inline-block;padding:10px 20px;background:#132364;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;'>Verify Email</a></p>";
    $body   .= "<p style='font-size:12px;color:#64748b;'>If the button doesn't work, copy this link into your browser:<br>" . esc_url($verify_url) . "</p>";
    $body   .= "<p style='font-size:12px;color:#64748b;'>This link expires in 3 days. If you didn't sign up, you can ignore this message.</p>";

    if (function_exists('property_theme_send_html_email')) {
        return property_theme_send_html_email(
            $user->user_email,
            $subject,
            'Verify your email',
            $body,
            array('text' => 'Verify Email', 'url' => $verify_url)
        );
    }

    return wp_mail(
        $user->user_email,
        $subject,
        $body,
        array('Content-Type: text/html; charset=UTF-8')
    );
}

/**
 * Check whether a user's email has been verified.
 * Pre-existing users (registered before this feature shipped) are grandfathered.
 */
function property_theme_is_email_verified($user_id) {
    $user_id = intval($user_id);
    if ($user_id <= 0) return false;
    $flag = get_user_meta($user_id, PROPERTY_THEME_EMAIL_VERIFY_META, true);
    if ($flag === '1') return true;
    if ($flag === '0') return false;
    // Never set: legacy account — treat as verified to avoid locking out existing users.
    return true;
}

/**
 * Verify a token; on success mark verified and clear stored token.
 * @return true|WP_Error
 */
function property_theme_verify_email_token($user_id, $token) {
    $user_id = intval($user_id);
    $token   = is_string($token) ? trim($token) : '';
    if ($user_id <= 0 || $token === '') {
        return new WP_Error('invalid', 'Missing user or token.');
    }

    $stored_hash = get_user_meta($user_id, PROPERTY_THEME_EMAIL_TOKEN_META, true);
    $expires     = intval(get_user_meta($user_id, PROPERTY_THEME_EMAIL_TOKEN_EXP, true));

    if (!$stored_hash) {
        return new WP_Error('used_or_missing', 'This link has already been used or never existed.');
    }
    if ($expires && $expires < time()) {
        return new WP_Error('expired', 'This verification link has expired. Please request a new one.');
    }
    if (!hash_equals($stored_hash, wp_hash($token))) {
        return new WP_Error('mismatch', 'Invalid verification token.');
    }

    update_user_meta($user_id, PROPERTY_THEME_EMAIL_VERIFY_META, '1');
    delete_user_meta($user_id, PROPERTY_THEME_EMAIL_TOKEN_META);
    delete_user_meta($user_id, PROPERTY_THEME_EMAIL_TOKEN_EXP);
    return true;
}

/**
 * Front-end handler: process verification links of the form
 *   /?rpjm_verify=1&uid=XX&token=YY
 */
add_action('template_redirect', function () {
    if (empty($_GET['rpjm_verify'])) return;

    $uid   = intval($_GET['uid'] ?? 0);
    $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
    $result = property_theme_verify_email_token($uid, $token);

    $login_url = home_url('/login');
    if (is_wp_error($result)) {
        wp_safe_redirect(add_query_arg('verify_error', rawurlencode($result->get_error_code()), $login_url));
        exit;
    }
    wp_safe_redirect(add_query_arg('verified', '1', $login_url));
    exit;
});

/**
 * Block login for unverified users — works for both wp_signon() and wp-login.php.
 */
add_filter('wp_authenticate_user', function ($user) {
    if (is_wp_error($user) || !$user instanceof WP_User) return $user;
    if (user_can($user, 'manage_options')) return $user; // never lock out admins
    if (!property_theme_is_email_verified($user->ID)) {
        return new WP_Error(
            'email_not_verified',
            'Please verify your email before signing in. <a href="' . esc_url(add_query_arg('resend', $user->ID, home_url('/login'))) . '">Resend verification email</a>.'
        );
    }
    return $user;
}, 30);

/**
 * Resend handler: /login?resend=<uid>
 */
add_action('template_redirect', function () {
    if (empty($_GET['resend'])) return;
    $uid = intval($_GET['resend']);
    if (!$uid || !get_userdata($uid)) return;

    // Throttle: 1 resend per 60s
    $last = intval(get_user_meta($uid, '_property_email_verify_last_sent', true));
    if ($last && (time() - $last) < 60) {
        wp_safe_redirect(add_query_arg('resend_status', 'wait', home_url('/login')));
        exit;
    }
    update_user_meta($uid, '_property_email_verify_last_sent', time());

    property_theme_send_verification_email($uid);
    wp_safe_redirect(add_query_arg('resend_status', 'sent', home_url('/login')));
    exit;
});
