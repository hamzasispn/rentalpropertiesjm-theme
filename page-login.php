<?php
/**
 * Template Name: Login
 * Login page template
 */

if (is_user_logged_in()) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

$redirect_to = isset($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : home_url('/dashboard');
$login_error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    if (!wp_verify_nonce($_POST['login_nonce'], 'login_nonce')) {
        wp_die('Security check failed');
    }

    $username = sanitize_text_field($_POST['username'] ?? '');
    $password = sanitize_text_field($_POST['password'] ?? '');
    $remember = isset($_POST['remember']) ? true : false;

    if (!$username || !$password) {
        $login_error = 'Please enter both username/email and password.';
    } else {
        $user = wp_signon(array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember,
        ), false);

        if (is_wp_error($user)) {
            $login_error = 'Invalid username/email or password.';
        } else {
            wp_redirect($redirect_to);
            exit;
        }
    }
}
$logo = get_option('mytheme_logo');
get_header();
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center py-12 px-4">
    <div class="w-full max-w-md">
        <!-- Card -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="flex items-center w-[50%] justify-center mx-auto mb-4">
                    <img src="<?= esc_url($logo); ?>" alt="Logo" class="h-[7.5vw] sm:h-[5vw] lg:h-[60px] object-contain">
                </div>
                <h1 class="text-3xl font-bold text-slate-900">Welcome Back</h1>
                <p class="text-slate-600 mt-2">Sign in to your Rental Properties JM account</p>
            </div>

            <!-- Error Message -->
            <?php if ($login_error) : ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-red-800 font-semibold"><?php echo esc_html($login_error); ?></p>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" class="space-y-4">
                <?php wp_nonce_field('login_nonce', 'login_nonce'); ?>

                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">Email or Username</label>
                    <input type="text" name="username" placeholder="Enter your email or username" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900" autofocus>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900">
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

            <!-- Signup Link -->
            <div class="text-center mt-6 pt-6 border-t border-slate-200">
                <p class="text-slate-600">Don't have an account? <a href="<?php echo home_url('/register'); ?>" class="text-[var(--primary-color)] hover:underline font-semibold">Create one now</a></p>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
