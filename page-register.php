<?php
/**
 * Template Name: Register
 * Registration page template
 */

if (is_user_logged_in()) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

$registration_error = '';
$registration_success = false;

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    if (!wp_verify_nonce($_POST['register_nonce'], 'register_nonce')) {
        wp_die('Security check failed');
    }

    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['last_name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $password = sanitize_text_field($_POST['password'] ?? '');
    $password_confirm = sanitize_text_field($_POST['password_confirm'] ?? '');
    $agree_terms = isset($_POST['agree_terms']) ? true : false;

    // Validation
    if (!$first_name || !$last_name || !$email || !$password) {
        $registration_error = 'All fields are required.';
    } elseif ($password !== $password_confirm) {
        $registration_error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $registration_error = 'Password must be at least 8 characters long.';
    } elseif (!$agree_terms) {
        $registration_error = 'You must agree to the terms and conditions.';
    } elseif (email_exists($email)) {
        $registration_error = 'An account with this email already exists.';
    } else {
        // Create username from email
        $username = sanitize_user(explode('@', $email)[0]);
        $username_base = $username;
        $counter = 1;

        // Ensure unique username
        while (username_exists($username)) {
            $username = $username_base . $counter;
            $counter++;
        }

        // Create user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            $registration_error = $user_id->get_error_message();
        } else {
            // Update user metadata
            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'display_name' => $first_name . ' ' . $last_name,
            ));

            // Set user role
            $user = new WP_User($user_id);
            $user->set_role('subscriber');

            $registration_success = true;
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
                    <img src="<?= esc_url($logo); ?>" alt="Logo"
                        class="h-[7.5vw] sm:h-[5vw] lg:h-[60px] object-contain">
                </div>
                <h1 class="text-2xl font-bold text-slate-900">Join Rental Properties JM</h1>
                <p class="text-slate-600 mt-2">Create your account to get started</p>
            </div>

            <?php if ($registration_success): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-center">
                    <p class="text-green-800 font-semibold">Account created successfully!</p>
                    <p class="text-green-700 text-sm mt-1">Redirecting to login...</p>
                    <script>
                        setTimeout(() => window.location.href = '<?php echo esc_js(home_url('/login')); ?>', 2000);
                    </script>
                </div>
            <?php else: ?>
                <!-- Error Message -->
                <?php if ($registration_error): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-800 font-semibold"><?php echo esc_html($registration_error); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form method="POST" class="space-y-4">
                    <?php wp_nonce_field('register_nonce', 'register_nonce'); ?>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">First Name</label>
                            <input type="text" name="first_name" placeholder="John" required
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900"
                                autofocus>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Last Name</label>
                            <input type="text" name="last_name" placeholder="Doe" required
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-2">Email Address</label>
                        <input type="email" name="email" placeholder="john@example.com" required
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-2">Password</label>
                        <input type="password" name="password" placeholder="At least 8 characters" required
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-2">Confirm Password</label>
                        <input type="password" name="password_confirm" placeholder="Confirm your password" required
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900">
                    </div>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="agree_terms" required class="w-4 h-4 rounded border-slate-300 mt-1">
                        <span class="text-sm text-slate-700">I agree to the <a href="#"
                                class="text-[var(--primary-color)] hover:underline">Terms and Conditions</a> and <a href="#"
                                class="text-[var(--primary-color)] hover:underline">Privacy Policy</a></span>
                    </label>

                    <button type="submit" name="register_submit"
                        class="w-full px-4 py-3 bg-[var(--primary-color)] text-white rounded-lg hover:bg-blue-700 transition font-bold">Create
                        Account</button>
                </form>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-slate-600">Or sign up with</span>
                    </div>
                </div>

                <!-- OAuth Buttons -->
                <div class="space-y-3">
                    <button type="button" onclick="alert('Google OAuth coming soon')"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path fill="#EA4335"
                                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                            <path fill="#34A853"
                                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                            <path fill="#FBBC05"
                                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                            <path fill="#4285F4"
                                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                        </svg>
                        Sign up with Google
                    </button>
                    <button type="button" onclick="alert('Facebook OAuth coming soon')"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                        Sign up with Facebook
                    </button>
                </div>

                <!-- Login Link -->
                <div class="text-center mt-6 pt-6 border-t border-slate-200">
                    <p class="text-slate-600">Already have an account? <a href="<?php echo home_url('/login'); ?>"
                            class="text-[var(--primary-color)] hover:underline font-semibold">Sign in here</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>