<?php
/**
 * Template Name: Admin Login
 */

if (is_user_logged_in() && current_user_can('manage_options')) {
    wp_redirect(home_url('/admin-panel'));
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login_nonce'])) {
    if (!wp_verify_nonce($_POST['admin_login_nonce'], 'admin_login_action')) {
        $error = 'Security check failed. Please try again.';
    } else {
        $username = sanitize_text_field($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = wp_signon([
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => true,
        ], false);

        if (is_wp_error($user)) {
            $error = 'Invalid credentials. Admins only.';
        } elseif (!user_can($user->ID, 'manage_options')) {
            wp_logout();
            $error = 'Access denied. Administrator account required.';
        } else {
            wp_set_current_user($user->ID);
            wp_redirect(home_url('/admin-panel'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #0a0f1e;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 30%, rgba(234, 179, 8, 0.08) 0%, transparent 60%),
                radial-gradient(ellipse 60% 40% at 80% 70%, rgba(234, 179, 8, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .grid-bg {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(234,179,8,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(234,179,8,0.04) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: 0;
        }

        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 16px;
        }

        .login-card {
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(234, 179, 8, 0.2);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 0 60px rgba(234, 179, 8, 0.08), 0 25px 50px rgba(0,0,0,0.5);
            backdrop-filter: blur(20px);
        }

        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(234, 179, 8, 0.1);
            border: 1px solid rgba(234, 179, 8, 0.3);
            color: #eab308;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 100px;
            margin-bottom: 20px;
        }

        .admin-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #eab308;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        h1 {
            color: #f1f5f9;
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 6px;
            letter-spacing: -0.02em;
        }

        .subtitle {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 32px;
        }

        .field-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(71, 85, 105, 0.6);
            color: #f1f5f9;
            font-size: 15px;
            padding: 12px 16px;
            border-radius: 10px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: rgba(234, 179, 8, 0.5);
            box-shadow: 0 0 0 3px rgba(234, 179, 8, 0.1);
        }

        input::placeholder { color: #475569; }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%);
            color: #0a0f1e;
            font-weight: 700;
            font-size: 15px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s, opacity 0.15s;
            letter-spacing: 0.02em;
            margin-top: 8px;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(234, 179, 8, 0.35);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 24px;
            color: #475569;
            font-size: 13px;
            text-decoration: none;
            transition: color 0.2s;
        }

        .back-link:hover { color: #94a3b8; }

        .lock-icon {
            width: 48px;
            height: 48px;
            background: rgba(234, 179, 8, 0.1);
            border: 1px solid rgba(234, 179, 8, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="grid-bg"></div>
<div class="login-wrapper">
    <div class="login-card">
        <div class="lock-icon">
            <svg width="22" height="22" fill="none" stroke="#eab308" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>

        <div class="admin-badge">Admin Portal</div>
        <h1>Welcome back</h1>
        <p class="subtitle">Sign in with your administrator account to continue.</p>

        <?php if ($error): ?>
            <div class="error-msg">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?php echo esc_html($error); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <?php wp_nonce_field('admin_login_action', 'admin_login_nonce'); ?>

            <div class="field-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" placeholder="admin@example.com" required
                       value="<?php echo esc_attr($_POST['username'] ?? ''); ?>">
            </div>

            <div class="field-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••••••" required>
            </div>

            <button type="submit" class="btn-login">
                Sign In to Dashboard
            </button>
        </form>

        <a href="<?php echo esc_url(home_url('/')); ?>" class="back-link">← Back to main site</a>
    </div>
</div>
<?php wp_footer(); ?>
</body>
</html>
