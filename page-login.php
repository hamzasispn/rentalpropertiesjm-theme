<?php
/**
 * Template Name: Login
 * Login page template
 */

// Role-based home: agents (paid subscription) → /dashboard/,
// members → /my-account/. Falls back to a hard-coded URL if the helper
// isn't loaded yet during theme upgrade.
$default_redirect = function ($uid = 0) {
    if (function_exists('pt_get_user_home_url')) return pt_get_user_home_url($uid);
    if ($uid && function_exists('pt_user_is_agent') && pt_user_is_agent($uid)) return home_url('/dashboard/');
    return home_url('/my-account/');
};

if (is_user_logged_in()) {
    wp_redirect($default_redirect(get_current_user_id()));
    exit;
}

// Only honor a redirect_to that lives on this site to prevent open-redirect abuse.
$redirect_to = $default_redirect();
if (!empty($_GET['redirect_to'])) {
    $candidate = esc_url_raw(wp_unslash($_GET['redirect_to']));
    $safe      = wp_validate_redirect($candidate, $redirect_to);
    if ($safe) $redirect_to = $safe;
}
$login_error = '';
$login_notice = '';

if (isset($_GET['verified'])) {
    $login_notice = 'Email verified successfully. You can now sign in.';
}
if (isset($_GET['verify_error'])) {
    $code = sanitize_key($_GET['verify_error']);
    $map = array(
        'expired'         => 'Your verification link has expired. Please request a new one.',
        'mismatch'        => 'Invalid verification link.',
        'used_or_missing' => 'This verification link has already been used or is no longer valid.',
        'invalid'         => 'Invalid verification request.',
    );
    $login_error = $map[$code] ?? 'Email verification failed.';
}
if (isset($_GET['resend_status'])) {
    $login_notice = ($_GET['resend_status'] === 'wait')
        ? 'Please wait a minute before requesting another email.'
        : 'Verification email sent — check your inbox.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    if (!wp_verify_nonce($_POST['login_nonce'], 'login_nonce')) {
        wp_die('Security check failed');
    }

    // Simple IP-based throttle: 8 failed attempts per 15 minutes.
    $ip            = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $throttle_key  = 'rpjm_login_fail_' . md5($ip);
    $fail_count    = (int) get_transient($throttle_key);
    $is_throttled  = $fail_count >= 8;

    $username = sanitize_text_field(wp_unslash($_POST['username'] ?? ''));
    // NEVER sanitize_text_field a password — it can strip valid characters.
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $remember = !empty($_POST['remember']);

    if ($is_throttled) {
        $login_error = 'Too many failed attempts. Please wait 15 minutes and try again.';
    } elseif (!$username || !$password) {
        $login_error = 'Please enter both username/email and password.';
    } else {
        $user = wp_signon(array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => $remember,
        ), false);

        if (is_wp_error($user)) {
            // Bump the failure counter (15 min TTL).
            set_transient($throttle_key, $fail_count + 1, 15 * MINUTE_IN_SECONDS);

            $code = $user->get_error_code();
            if ($code === 'email_not_verified') {
                $login_error = $user->get_error_message();
            } else {
                $login_error = 'Invalid username/email or password.';
            }
        } else {
            // Reset on success.
            delete_transient($throttle_key);
            // If no explicit ?redirect_to was passed, land the user on the
            // right home for their role instead of the pre-signon default.
            $target = empty($_GET['redirect_to']) ? $default_redirect($user->ID) : $redirect_to;
            wp_safe_redirect($target);
            exit;
        }
    }
}
$logo = get_option('mytheme_logo');

// UX toggle at the top of the form. Just intent — the actual redirect is
// still role-based (an agent typing on the "User" tab still lands on
// /dashboard/ because they have a subscription).
$intent = isset($_GET['as']) && $_GET['as'] === 'agent' ? 'agent' : 'user';

get_header();
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md">
        <!-- Card -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="flex items-center w-[50%] justify-center mx-auto mb-4">
                    <img src="<?= esc_url($logo); ?>" alt="Logo" class="h-[7.5vw] sm:h-[5vw] lg:h-[60px] object-contain">
                </div>
                <h1 class="text-3xl font-bold text-slate-900">Sign in</h1>
                <p class="text-slate-600 mt-2">
                    <?= $intent === 'agent'
                        ? 'Sign in to manage your listings and subscription.'
                        : 'Sign in to view your saved properties and searches.'; ?>
                </p>
            </div>

            <!-- Register link moved up top per client feedback -->
            <p class="text-center text-sm text-slate-600 mb-6">
                Don't have an account?
                <a href="<?php echo esc_url(add_query_arg('as', $intent, home_url('/register'))); ?>"
                   class="text-[var(--primary-color)] hover:underline font-semibold">
                    <?= $intent === 'agent' ? 'Register as an agent' : 'Create one now'; ?>
                </a>
            </p>

            <!-- Role toggle: User / Agent — cosmetic + steers the register link -->
            <div class="grid grid-cols-2 gap-1 bg-slate-100 rounded-xl p-1 mb-6">
                <a href="<?= esc_url(add_query_arg('as', 'user', remove_query_arg('as'))); ?>"
                   class="text-center py-2.5 rounded-lg font-semibold text-sm transition
                          <?= $intent === 'user' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'; ?>">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        User
                    </span>
                </a>
                <a href="<?= esc_url(add_query_arg('as', 'agent', remove_query_arg('as'))); ?>"
                   class="text-center py-2.5 rounded-lg font-semibold text-sm transition
                          <?= $intent === 'agent' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'; ?>">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M15 9h.01M9 13h.01M15 13h.01M9 17h.01M15 17h.01"/>
                        </svg>
                        Agent / Realtor
                    </span>
                </a>
            </div>

            <!-- Notice Message -->
            <?php if ($login_notice) : ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-green-800 font-semibold"><?php echo esc_html($login_notice); ?></p>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if ($login_error) : ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-red-800 font-semibold"><?php echo wp_kses($login_error, array('a' => array('href' => array()))); ?></p>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" class="space-y-4">
                <?php wp_nonce_field('login_nonce', 'login_nonce'); ?>

                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">Email or Username</label>
                    <input type="text" name="username" placeholder="Enter your email or username" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900" autofocus>
                </div>

                <div x-data="{ show: false }">
                    <label class="block text-sm font-semibold text-slate-900 mb-2">Password</label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" name="password" placeholder="Enter your password" required
                               class="w-full pl-4 pr-11 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900">
                        <button type="button" @click="show = !show"
                                :aria-label="show ? 'Hide password' : 'Show password'"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 p-1.5">
                            <svg x-show="!show" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1 1 0 010-.644C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178a1 1 0 010 .644C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <svg x-show="show" x-cloak class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300">
                        <span class="text-sm text-slate-700">Remember me</span>
                    </label>
                    <a href="<?php echo wp_lostpassword_url(); ?>" class="text-sm text-[var(--primary-color)] hover:underline">Forgot password?</a>
                </div>

                <button type="submit" name="login_submit" class="w-full px-4 py-3 bg-[var(--primary-color)] text-white rounded-lg hover:bg-blue-700 transition font-bold">Sign In</button>
            </form>

            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-slate-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-slate-600">Or continue with</span>
                </div>
            </div>

            <!-- OAuth Buttons (placeholder for integration) -->
            <div class="space-y-3">
                <button type="button" onclick="alert('Google OAuth coming soon')" class="w-full px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#EA4335" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#4285F4" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                    Sign in with Google
                </button>
                <button type="button" onclick="alert('Facebook OAuth coming soon')" class="w-full px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    Sign in with Facebook
                </button>
            </div>

        </div>
    </div>
</div>

<?php get_footer(); ?>
