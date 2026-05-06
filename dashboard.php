<?php
/**
 * User Dashboard Page Template with Sidebar
 * Template Name: Dashboard
 */

require_once get_template_directory() . '/inc/subscription/stripe-products-setup.php';


if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$logo = get_option('mytheme_logo');
$user_id = get_current_user_id();
$user = wp_get_current_user();
$stats = property_theme_get_subscription_stats($user_id);
$subscription = property_theme_get_user_subscription($user_id) ?? array();

?>

<div class="min-h-screen bg-slate-50 flex dashboard-page" x-data="dashboard()" x-init="initTabs()">
    <!-- Sidebar Navigation -->
    <div class="w-64 bg-slate-900 text-white flex flex-col fixed top-0 z-[99] h-screen border-b border-gray-50/10">

        <a href="<?= home_url(); ?>" class="px-6 py-3 border-r border-b border-gray-50/10 w-64">
            <?php if ($logo): ?>
                <img src="<?php echo esc_url($logo); ?>" alt="Logo"
                    class="h-[60px] object-contain filter invert brightness-0">
            <?php else: ?>
                <span class="text-2xl font-bold text-white-900">Rental Properties JM</span>
            <?php endif; ?>
        </a>

        <!-- Navigation -->
        <nav class="flex-1 px-4 py-8 space-y-2">
            <a href="#overview" @click="activateTab('overview', true)"
                :class="{ 'bg-slate-800 font-semibold': activeTab === 'overview' }"
                class="nav-link px-4 py-3 rounded-lg hover:bg-slate-800 transition block">
                📊 Overview
            </a>
            <a href="#properties" @click="activateTab('properties', true)"
                :class="{ 'bg-slate-800 font-semibold': activeTab === 'properties' }"
                class="nav-link px-4 py-3 rounded-lg hover:bg-slate-800 transition block">
                🏠 My Properties
            </a>
            <a href="#add-property" @click="activateTab('add-property', true)"
                :class="{ 'bg-slate-800 font-semibold': activeTab === 'add-property' }"
                class="nav-link px-4 py-3 rounded-lg hover:bg-slate-800 transition block">
                ➕ Add Property
            </a>
            <a href="#analytics" @click="activateTab('analytics', true)"
                :class="{ 'bg-slate-800 font-semibold': activeTab === 'analytics' }"
                class="nav-link px-4 py-3 rounded-lg hover:bg-slate-800 transition block">
                📈 Analytics
            </a>
            <a href="#billing" @click="activateTab('billing', true)"
                :class="{ 'bg-slate-800 font-semibold': activeTab === 'billing' }"
                class="nav-link px-4 py-3 rounded-lg hover:bg-slate-800 transition block">
                💳 Billing
            </a>
            <a href="#invoices" @click="activateTab('invoices', true)"
                :class="{ 'bg-slate-800 font-semibold': activeTab === 'invoices' }"
                class="nav-link px-4 py-3 rounded-lg hover:bg-slate-800 transition block">
                🧾 Invoices
            </a>
            <a href="#settings" @click="activateTab('settings', true)"
                :class="{ 'bg-slate-800 font-semibold': activeTab === 'settings' }"
                class="nav-link px-4 py-3 rounded-lg hover:bg-slate-800 transition block">
                ⚙️ Settings
            </a>
        </nav>

        <!-- User Info -->
        <div class="px-6 py-6 border-t border-slate-800">
            <p class="text-sm text-slate-400">Logged in as</p>
            <p class="font-semibold mt-1"><?php echo esc_html($user->display_name); ?></p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="ml-64 flex-1 overflow-y-auto relative py-[5rem]">
        <div class="bg-slate-900 flex items-center justify-end top-0 left-0 fixed w-full z-10 h-[85px] px-4 py-2">
            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>"
                class="text-xs text-slate-400 p-2 flex items-center justify-center rounded bg-white/10 w-12 h-12 border border-white/20 ">
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink"
                    class="fill-current w-full h-full" viewBox="0 0 24 24">
                    <g>
                        <g fill-rule="evenodd">
                            <path
                                d="M11.112.815a5.25 5.25 0 1 0 0 10.5 5.25 5.25 0 0 0 0-10.5zM19.652 12.09c-.486-.55-1.2-.9-1.993-.9H15.75a2.658 2.658 0 0 0-2.658 2.659v6.678a2.658 2.658 0 0 0 2.658 2.658h1.908c.794 0 1.507-.35 1.993-.9a.75.75 0 0 0-1.124-.992 1.153 1.153 0 0 1-.87.392h-1.907c-.64 0-1.158-.519-1.158-1.158v-6.678c0-.64.518-1.159 1.158-1.159h1.908c.346 0 .655.151.869.393a.75.75 0 0 0 1.124-.993z"
                                class="fill-current"></path>
                            <path
                                d="M22.497 16.657a.75.75 0 0 1 0 1.06l-2 2a.75.75 0 0 1-1.061-1.06l2-2a.75.75 0 0 1 1.06 0z"
                                class="fill-current"></path>
                            <path
                                d="M22.497 17.718a.75.75 0 0 0 0-1.06l-2-2a.75.75 0 0 0-1.061 1.06l2 2a.75.75 0 0 0 1.06 0z"
                                class="fill-current"></path>
                            <path d="M15.716 17.188a.75.75 0 0 1 .75-.75h5a.75.75 0 0 1 0 1.5h-5a.75.75 0 0 1-.75-.75z"
                                class="fill-current"></path>
                        </g>
                        <path
                            d="M11.608 13.49c-.01.119-.015.238-.015.359v6.678c0 .746.196 1.445.54 2.05h-9.11a1.75 1.75 0 0 1-1.75-1.75v-3.671c0-.613.338-1.176.88-1.464a18.895 18.895 0 0 1 9.455-2.201z"
                            class="fill-current"></path>
                    </g>
                </svg>
            </a>
        </div>

        <div class="p-8">
            <!-- Overview Tab -->
            <div id="overview" x-show="activeTab === 'overview'" x-transition class="tab-content space-y-6">
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h1 class="text-4xl font-bold text-slate-900">Welcome,
                            <?php echo esc_html($user->display_name); ?>
                        </h1>
                        <p class="text-slate-600 mt-2">Manage your properties and subscription</p>
                    </div>
                </div>

                <!-- Subscription Card -->
                <div class="bg-white rounded-lg shadow p-8">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900">
                                <?php echo esc_html($stats['plan']['name'] ?? 'No Plan'); ?>
                            </h2>
                            <p class="text-slate-600 mt-1">Your current subscription</p>
                        </div>
                        <span class="px-4 py-2 bg-green-100 text-green-800 rounded-full font-semibold text-sm">
                            <?php echo $stats['subscription'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                        <div class="border-l-4 border-blue-600 pl-4">
                            <p class="text-slate-600 text-sm">Properties</p>
                            <p class="text-3xl font-bold text-slate-900 mt-1">
                                <span><?php echo esc_html($stats['published_properties'] ?? '0'); ?></span>/<span><?php echo esc_html($stats['plan']['max_properties'] ?? '0'); ?></span>
                            </p>
                        </div>
                        <div class="border-l-4 border-amber-600 pl-4">
                            <p class="text-slate-600 text-sm">Featured (this month)</p>
                            <p class="text-3xl font-bold text-slate-900 mt-1">
                                <span><?php echo esc_html($stats['featured_this_month'] ?? '0'); ?></span>/<span><?php echo esc_html($stats['plan']['featured_limit'] ?? '0'); ?></span>
                            </p>
                        </div>
                        <div class="border-l-4 border-green-600 pl-4">
                            <p class="text-slate-600 text-sm">Total Views</p>
                            <p class="text-3xl font-bold text-slate-900 mt-1">
                                <?php echo esc_html($stats['total_views']); ?>
                            </p>
                        </div>
                        <div class="border-l-4 border-purple-600 pl-4">
                            <p class="text-slate-600 text-sm">Days Remaining</p>
                            <p class="text-3xl font-bold text-slate-900 mt-1">
                                <?php echo esc_html($stats['days_remaining']); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Analytics Chart -->
                    <div class="bg-slate-50 rounded-lg p-6 mb-8">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Analytics Overview</h3>
                        <canvas id="analyticsChart" height="80"></canvas>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-4">
                        <button @click="activateTab('add-property', true)"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">+ New
                            Property</button>
                        <button @click="activateTab('billing', true)"
                            class="px-6 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition">View
                            Plans</button>
                    </div>
                </div>
            </div>

            <!-- Properties Tab -->
            <div id="properties" x-show="activeTab === 'properties'" x-transition class="tab-content space-y-6">
                <h1 class="text-3xl font-bold text-slate-900">My Properties</h1>

                <div class="bg-white rounded-lg shadow p-8">
                    <?php
                    $user_properties = get_posts(array(
                        'post_type' => 'property',
                        'author' => $user_id,
                        'numberposts' => -1,
                    ));

                    if (!empty($user_properties)): ?>
                        <div class="space-y-4">
                            <?php foreach ($user_properties as $property):
                                $price = get_post_meta($property->ID, '_property_price', true);
                                $featured = get_post_meta($property->ID, '_property_featured', true);
                                $leads_count = get_posts(array(
                                    'post_type' => 'property',
                                    'post__in' => array($property->ID),
                                    'numberposts' => -1,
                                ));
                                ?>
                                <div class="flex items-center justify-between border-b pb-4">
                                    <div>
                                        <h4 class="font-semibold text-slate-900"><?php echo esc_html($property->post_title); ?>
                                        </h4>
                                        <p class="text-slate-600 text-sm">Price:
                                            $<?php echo esc_html(number_format(intval($price))); ?></p>
                                    </div>
                                    <div class="flex gap-2">
                                        <?php if ($featured): ?>
                                            <span class="px-3 py-1 bg-amber-100 text-amber-800 text-sm rounded">⭐ Featured</span>
                                        <?php endif; ?>
                                        <a href="<?php echo esc_url(home_url('/dashboard/?property_id=' . $property->ID . '#add-property')); ?>"
                                            class="px-3 py-1 text-blue-600 hover:underline">Edit</a>
                                        <a href="<?php echo esc_url(get_permalink($property->ID)); ?>"
                                            class="px-3 py-1 text-blue-600 hover:underline">View</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-slate-600">You haven't created any properties yet.
                            <button @click="activateTab('add-property', true)" class="text-blue-600 hover:underline">Create
                                your first property</button>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Add Property Section in Dashboard -->
            <div id="add-property" x-show="activeTab === 'add-property'" x-transition class="tab-content space-y-6">
                <?php get_template_part('template-parts/section', 'add-property'); ?>
            </div>

            <!-- Analytics Tab -->
            <div id="analytics" x-show="activeTab === 'analytics'" x-transition class="tab-content space-y-6">
                <h1 class="text-3xl font-bold text-slate-900">Analytics & Leads</h1>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-slate-600 text-sm font-semibold">Total Views</h3>
                        <p class="text-4xl font-bold text-slate-900 mt-2">
                            <?php echo esc_html($stats['total_views'] ?? '0'); ?>
                        </p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-slate-600 text-sm font-semibold">Active Properties</h3>
                        <p class="text-4xl font-bold text-slate-900 mt-2">
                            <?php echo esc_html($stats['published_properties']); ?>
                        </p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-slate-600 text-sm font-semibold">Days Left</h3>
                        <p class="text-4xl font-bold text-slate-900 mt-2">
                            <?php echo esc_html($stats['days_remaining']); ?>
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-8">
                    <h3 class="text-xl font-bold text-slate-900 mb-6">Property Performance</h3>
                    <div class="space-y-4">
                        <?php foreach ($user_properties as $property):
                            global $wpdb;
                            $views = intval($wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}property_analytics WHERE property_id = %d AND event_type = 'page_view'",
                                $property->ID
                            )));
                            $leads = intval($wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}property_leads WHERE property_id = %d",
                                $property->ID
                            )));
                            ?>
                            <div class="border rounded-lg p-4 flex justify-between items-center">
                                <div>
                                    <h4 class="font-semibold text-slate-900"><?php echo esc_html($property->post_title); ?>
                                    </h4>
                                    <p class="text-slate-600 text-sm mt-1"><?php echo $views; ?> views •
                                        <?php echo $leads; ?> leads
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    <a href="tel:+1234567890"
                                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">📞
                                        Call</a>
                                    <a href="https://wa.me/1234567890" target="_blank"
                                        class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm">💬
                                        WhatsApp</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Billing Tab -->
            <div id="billing" x-show="activeTab === 'billing'" x-transition class="tab-content space-y-6">
                <h1 class="text-3xl font-bold text-slate-900">Billing & Subscription</h1>

                <!-- Current Plan -->
                <?php if ($stats['subscription']): ?>
                    <div class="bg-white rounded-lg shadow p-8">
                        <h2 class="text-2xl font-bold text-slate-900 mb-6">Current Plan:
                            <?php echo esc_html($stats['plan']['name']); ?>
                        </h2>
                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <p class="text-slate-600 text-sm">Plan Price</p>
                                <p class="text-3xl font-bold text-slate-900 mt-1">
                                    $<?php echo esc_html($stats['plan']['price']); ?>/mo</p>
                            </div>
                            <div>
                                <p class="text-slate-600 text-sm">Expires</p>
                                <p class="text-xl font-bold text-slate-900 mt-1">
                                    <?php echo esc_html(date('M d, Y', strtotime($stats['subscription']->expiry_date))); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <?php $pm = $stats['payment_method']; ?>
                        <?php if (!empty($pm['card_last_four'])): ?>
                        <div class="mb-6 p-4 bg-slate-50 rounded-lg flex items-center justify-between">
                            <div>
                                <p class="text-slate-600 text-sm">Payment Method</p>
                                <p class="font-semibold text-slate-900 mt-1">
                                    <?= esc_html(ucfirst($pm['card_brand'] ?? '')); ?> •••• <?= esc_html($pm['card_last_four']); ?>
                                    <span class="text-slate-500 font-normal text-sm ml-2">Expires <?= esc_html(($pm['exp_month'] ?? '') . '/' . ($pm['exp_year'] ?? '')); ?></span>
                                </p>
                            </div>
                            <!-- FIXED: dispatch event instead of calling parent method directly -->
                            <button type="button"
                                @click="$dispatch('open-update-payment-modal')"
                                class="update-payment-btn px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm">
                                Change Card
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="mb-6">
                            <!-- FIXED: dispatch event instead of calling parent method directly -->
                            <button type="button"
                                @click="$dispatch('open-update-payment-modal')"
                                class="update-payment-btn px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm">
                                + Add Payment Method
                            </button>
                        </div>
                        <?php endif; ?>

                        <div class="flex gap-4 flex-wrap items-center">
                            <button data-subscription-id="<?= $stats['subscription']->id ?>"
                                class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                                @click="confirmCancelSubscription()">
                                Cancel Subscription
                            </button>
                            <button
                                x-data="{ autoRenew: <?= $stats['auto_renew'] ? 'true' : 'false'; ?>, loading: false }"
                                @click="
                                    if (loading) return;
                                    loading = true;
                                    fetch(propertyTheme.rest_url + 'property-theme/v1/toggle-auto-renew', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': propertyTheme.nonce },
                                        body: JSON.stringify({ enable: !autoRenew })
                                    }).then(async r => {
                                        const d = await r.json();
                                        if (!r.ok) { throw new Error(d.message || 'Failed to update auto-renew'); }
                                        autoRenew = !!d.auto_renew;
                                        window.toast && window.toast(autoRenew ? 'Auto-renew is on' : 'Auto-renew is off — your plan will end at the period\'s end', 'success');
                                        setTimeout(() => window.location.reload(), 700);
                                    }).catch((e) => {
                                        loading = false;
                                        window.toast && window.toast(e.message || 'Could not update auto-renew', 'error');
                                    });
                                "
                                :disabled="loading"
                                :class="autoRenew ? 'bg-green-600 hover:bg-green-700' : 'bg-slate-500 hover:bg-slate-600'"
                                class="px-6 py-2 text-white rounded-lg transition disabled:opacity-50">
                                <span x-text="loading ? 'Saving...' : (autoRenew ? '✓ Auto-Renew On' : 'Auto-Renew Off')"></span>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Available Plans -->
                <?php get_template_part('template-parts/component', 'plan-card', array('subscription' => $stats['subscription'])); ?>
            </div>

            <!-- Invoices Tab -->
            <div id="invoices" x-show="activeTab === 'invoices'" x-transition class="tab-content space-y-6"
                x-data="invoicesTab()" x-init="load()">
                <h1 class="text-3xl font-bold text-slate-900">Invoices</h1>

                <div class="bg-white rounded-lg shadow p-8">
                    <template x-if="loading">
                        <p class="text-slate-500">Loading invoices...</p>
                    </template>
                    <template x-if="!loading && invoices.length === 0">
                        <p class="text-slate-500">No invoices found.</p>
                    </template>
                    <template x-if="!loading && invoices.length > 0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b text-left text-slate-500">
                                        <th class="pb-3 pr-4">Date</th>
                                        <th class="pb-3 pr-4">Invoice</th>
                                        <th class="pb-3 pr-4">Plan</th>
                                        <th class="pb-3 pr-4">Amount</th>
                                        <th class="pb-3 pr-4">Status</th>
                                        <th class="pb-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="inv in invoices" :key="inv.id">
                                        <tr class="border-b hover:bg-slate-50">
                                            <td class="py-3 pr-4" x-text="new Date(inv.created_at).toLocaleDateString()"></td>
                                            <td class="py-3 pr-4 text-slate-600" x-text="inv.invoice_number || inv.stripe_invoice_id.slice(0,14) + '...'"></td>
                                            <td class="py-3 pr-4" x-text="inv.plan_name || '—'"></td>
                                            <td class="py-3 pr-4 font-semibold" x-text="'$' + parseFloat(inv.amount).toFixed(2) + ' ' + inv.currency.toUpperCase()"></td>
                                            <td class="py-3 pr-4">
                                                <span :class="inv.status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                                    class="px-2 py-1 rounded-full text-xs font-semibold capitalize" x-text="inv.status"></span>
                                            </td>
                                            <td class="py-3">
                                                <a x-show="inv.hosted_url" :href="inv.hosted_url" target="_blank"
                                                    class="text-blue-600 hover:underline text-xs">View</a>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Settings Tab -->
            <div id="settings" x-show="activeTab === 'settings'" x-transition class="tab-content space-y-6">
                <h1 class="text-3xl font-bold text-slate-900">Account Settings</h1>

                <!-- Account Info -->
                <div class="bg-white rounded-lg shadow p-8">
                    <h2 class="text-2xl font-bold text-slate-900 mb-6">Account Information</h2>
                    <div class="space-y-4">
                        <div>
                            <p class="text-slate-600 text-sm">Email</p>
                            <p class="font-semibold text-slate-900"><?php echo esc_html($user->user_email); ?></p>
                        </div>
                        <div>
                            <p class="text-slate-600 text-sm">Name</p>
                            <p class="font-semibold text-slate-900"><?php echo esc_html($user->display_name); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Agent Info (for leads contact) -->
                <div class="bg-white rounded-lg shadow p-8">
                    <h2 class="text-2xl font-bold text-slate-900 mb-6">Contact Information for Leads</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Phone Number</label>
                            <input type="tel" x-model="agentPhone" placeholder="+1 (555) 000-0000"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">WhatsApp Number</label>
                            <input type="tel" x-model="agentWhatsapp" placeholder="+1 (555) 000-0000"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <button @click="saveAgentInfo()"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Save
                            Contact Info</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!--
    =====================================================================
    UPDATE PAYMENT METHOD MODAL
    FIXED: Uses its own x-data with open/close driven by window events
    dispatched via Alpine's $dispatch or window.dispatchEvent().
    This avoids the cross-component scope issue where x-show on a sibling
    element couldn't reliably react to state owned by the dashboard() root.
    =====================================================================
-->
<div id="update-payment-modal"
    x-data="{ open: false }"
    x-on:open-update-payment-modal.window="open = true; mountUpdateCard()"
    x-on:close-update-payment-modal.window="open = false; unmountUpdateCard()"
    x-show="open"
    x-transition
    class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-slate-900">Update Payment Method</h2>
            <!-- FIXED: dispatch close event instead of calling parent closeUpdatePaymentModal() -->
            <button @click="$dispatch('close-update-payment-modal')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>
        </div>

        <form id="update-payment-form" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">New Card Information</label>
                <div id="update-card-element" class="p-4 border border-slate-300 rounded-lg bg-white"></div>
                <div id="update-card-errors" class="text-red-600 text-sm mt-2" role="alert"></div>
            </div>

            <button type="submit" id="update-payment-submit-btn"
                class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-slate-400 text-white font-bold py-3 px-4 rounded-lg transition">
                Save Payment Method
            </button>

            <div id="update-payment-error"
                class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm"></div>
        </form>
    </div>
</div>


<!-- Toast notifications -->
<style>[x-cloak]{display:none !important;}</style>
<div x-data="toastBus()" x-init="register()"
     class="fixed top-6 right-6 z-[60] flex flex-col gap-3 pointer-events-none" style="max-width:380px;">
    <template x-for="t in toasts" :key="t.id">
        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-x-4"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-end="opacity-0 translate-x-4"
             :class="{
                'bg-emerald-50 border-emerald-200 text-emerald-900': t.type==='success',
                'bg-red-50 border-red-200 text-red-900': t.type==='error',
                'bg-blue-50 border-blue-200 text-blue-900': t.type==='info'
             }"
             class="pointer-events-auto border rounded-lg shadow-lg px-4 py-3 flex items-start gap-3">
            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <template x-if="t.type==='success'"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.7a1 1 0 00-1.4-1.4L9 10.2 7.7 8.9a1 1 0 10-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></template>
                <template x-if="t.type==='error'"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.7 7.3a1 1 0 011.4 0L10 8.6l1.3-1.3a1 1 0 111.4 1.4L11.4 10l1.3 1.3a1 1 0 11-1.4 1.4L10 11.4l-1.3 1.3a1 1 0 01-1.4-1.4L8.6 10 7.3 8.7a1 1 0 010-1.4z" clip-rule="evenodd"/></template>
                <template x-if="t.type==='info'"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-7a1 1 0 112 0v3a1 1 0 11-2 0v-3zm1-5a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/></template>
            </svg>
            <div class="flex-1">
                <p x-show="t.title" x-text="t.title" class="font-semibold text-sm"></p>
                <p x-text="t.message" class="text-sm leading-snug"></p>
            </div>
            <button @click="dismiss(t.id)" class="text-slate-400 hover:text-slate-700 -mt-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>
<script>
function toastBus() {
    return {
        toasts: [],
        register() {
            window.toast = (message, type = 'info', title = '', timeout = 4500) => {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, message, type, title });
                setTimeout(() => this.dismiss(id), timeout);
            };
        },
        dismiss(id) { this.toasts = this.toasts.filter(t => t.id !== id); }
    };
}

// =====================================================================
// mountUpdateCard / unmountUpdateCard
// Defined globally so the modal's own x-data scope can call them.
// Stripe card mounting is decoupled from the dashboard() component
// entirely — no cross-scope method calls needed.
// =====================================================================
function mountUpdateCard() {
    const tryMount = (attempt = 0) => {
        const target = document.getElementById('update-card-element');
        // Wait until the element is visible in the DOM (x-show animates in)
        if (!target || target.offsetParent === null || target.offsetWidth < 10) {
            if (attempt < 40) return setTimeout(() => tryMount(attempt + 1), 50);
            console.warn('[Stripe] update-card-element never became visible');
            return;
        }
        try {
            if (!window.stripe) {
                window.toast && window.toast('Stripe.js not loaded yet — please retry', 'error');
                return;
            }
            // Unmount any previous instance cleanly
            if (window.updateCardElement) {
                try { window.updateCardElement.unmount(); } catch (e) {}
                window.updateCardElement = null;
            }
            const els = window.stripe.elements();
            window.updateCardElement = els.create('card');
            window.updateCardElement.mount('#update-card-element');
            window.updateCardElement.on('change', (event) => {
                const errEl = document.getElementById('update-card-errors');
                if (errEl) errEl.textContent = event.error ? event.error.message : '';
            });
        } catch (e) {
            console.error('[Stripe mount]', e);
            window.toast && window.toast('Could not load card form — please refresh', 'error');
        }
    };
    tryMount();
}

function unmountUpdateCard() {
    if (window.updateCardElement) {
        try { window.updateCardElement.unmount(); } catch (e) {}
        window.updateCardElement = null;
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://js.stripe.com/v3/"></script>
<script>
    function dashboard() {
        return {
            activeTab: 'overview',
            agentPhone: localStorage.getItem('agent_phone') || '',
            agentWhatsapp: localStorage.getItem('agent_whatsapp') || '',
            cancelLoading: false,
            deleteLoading: false,
            selectedPlanId: null,
            selectedPlanName: null,
            upgradeAmount: 0,
            deleteAccountPassword: '',

            // Initialize tabs from URL hash on page load
            initTabs() {
                const fullHash = window.location.hash.replace('#', '');
                let activeTabName = 'overview';

                // After saving a property the redirect lands here without a hash —
                // jump to the add-property tab so the success/review banner shows.
                const urlSearch = new URLSearchParams(window.location.search);
                if (!fullHash && (urlSearch.has('saved') || urlSearch.has('property_id') || urlSearch.has('error'))) {
                    activeTabName = 'add-property';
                }

                if (fullHash) {
                    const [tabName, queryString] = fullHash.split('?');

                    if (tabName && document.getElementById(tabName)) {
                        activeTabName = tabName;
                        console.log('[v0] Opening tab from hash:', tabName);

                        if (queryString) {
                            const hashParams = new URLSearchParams(queryString);
                            const propertyId = hashParams.get('property_id');
                            if (propertyId) {
                                console.log('[v0] Loading edit property:', propertyId);
                            }
                        }
                    }
                }

                this.activeTab = activeTabName;
                this.initChart();
                this.initStripe();
                this.setupDeleteAccount();
            },

            // Activate tab and update URL
            activateTab(tabId, pushToUrl = false) {
                this.activeTab = tabId;
                if (pushToUrl) {
                    history.pushState(null, '', `#${tabId}`);
                }
                if (tabId === 'analytics') {
                    setTimeout(() => {
                        if (window.analyticsChartInstance) {
                            window.analyticsChartInstance.destroy();
                            window.analyticsChartInstance = null;
                        }
                        this.initChart();
                    }, 50);
                }
            },

            // Agent form methods
            saveAgentInfo() {
                localStorage.setItem('agent_phone', this.agentPhone);
                localStorage.setItem('agent_whatsapp', this.agentWhatsapp);
                window.toast && window.toast('Contact information saved', 'success');
            },

            // Cancel subscription
            async confirmCancelSubscription() {
                if (!confirm('Are you sure you want to cancel your subscription?')) {
                    return;
                }

                this.cancelLoading = true;
                try {
                    const subscriptionId = document.querySelector('[data-subscription-id]')?.dataset.subscriptionId;
                    if (!subscriptionId) {
                        window.toast && window.toast('Subscription ID missing', 'error');
                        return;
                    }

                    const response = await fetch(
                        propertyTheme.rest_url + 'property-theme/v1/cancel-subscription',
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': propertyTheme.nonce,
                            },
                            body: JSON.stringify({
                                id: subscriptionId,
                                at_period_end: true
                            })
                        }
                    );

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'Cancel failed');
                    }

                    window.toast && window.toast(data.message || 'Subscription will cancel at the end of the period', 'success', 'Subscription canceled');
                    setTimeout(() => window.location.reload(), 900);
                } catch (error) {
                    console.error('[Cancel Subscription]', error);
                    window.toast && window.toast(error.message || 'Could not cancel', 'error');
                } finally {
                    this.cancelLoading = false;
                }
            },

            // Delete account
            async deleteAccount() {
                const password = prompt('Enter your password to confirm deletion:');
                if (!password) return;

                this.deleteLoading = true;
                try {
                    const response = await fetch(propertyTheme.rest_url + 'property-theme/v1/user/account/delete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': propertyTheme.nonce,
                        },
                        body: JSON.stringify({ password: password })
                    });

                    const data = await response.json();
                    if (data.success) {
                        window.toast && window.toast('Account deleted. Redirecting...', 'success');
                        setTimeout(() => { window.location.href = '/'; }, 900);
                    } else {
                        window.toast && window.toast(data.message || 'Error deleting account', 'error');
                    }
                } catch (error) {
                    console.error('Delete error:', error);
                    window.toast && window.toast('Error deleting account', 'error');
                } finally {
                    this.deleteLoading = false;
                }
            },

            // Setup delete account button
            setupDeleteAccount() {
                document.querySelector('.delete-account-btn')?.addEventListener('click', () => {
                    if (confirm('This will permanently delete your account and all properties. This cannot be undone. Are you sure?')) {
                        this.deleteAccount();
                    }
                });
            },

            // NOTE: openUpdatePaymentModal / closeUpdatePaymentModal removed from dashboard().
            // The modal now manages itself via window events. Buttons use $dispatch() directly.
            // Stripe card mounting is handled by the global mountUpdateCard() / unmountUpdateCard().

            initChart() {
                const ctx = document.getElementById('analyticsChart');
                if (ctx && !window.analyticsChartInstance) {
                    const analyticsData = <?php
                    global $wpdb;
                    $properties = get_posts(array('post_type' => 'property', 'author' => get_current_user_id(), 'posts_per_page' => -1, 'fields' => 'ids'));
                    $last_30_days_views = array();

                    for ($i = 29; $i >= 0; $i--) {
                        $date = date('Y-m-d', strtotime("-$i days"));
                        $count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}property_analytics WHERE event_type = 'page_view' AND DATE(created_at) = %s AND property_id IN (" . implode(',', $properties ?: array(0)) . ")",
                            $date
                        ));
                        $last_30_days_views[date('M d', strtotime($date))] = intval($count);
                    }
                    echo json_encode($last_30_days_views);
                    ?>;

                    window.analyticsChartInstance = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: Object.keys(analyticsData),
                            datasets: [{
                                label: 'Property Views',
                                data: Object.values(analyticsData),
                                borderColor: '#2563eb',
                                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 4,
                                pointBackgroundColor: '#2563eb',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    display: true,
                                    labels: { color: '#475569', font: { weight: 'bold' } }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { color: '#e2e8f0' },
                                    ticks: { color: '#64748b' }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { color: '#64748b' }
                                }
                            }
                        }
                    });
                }
            },

            initStripe() {
                if (typeof Stripe !== 'function') return;
                const publishableKey = 'pk_test_51S1WzxB1fVG7OgbP1M3aDl9FmKiPor8xJT1vtqgAj33mY37UK75L0oMgSMaQswkQyjpyW9daLLpmWfK5HGjSN49e00VY6HZueY';
                window.stripe = Stripe(publishableKey);

                document.getElementById('upgrade-payment-form')?.addEventListener('submit', (e) => this.handleUpgradePayment(e));
                document.getElementById('update-payment-form')?.addEventListener('submit', (e) => this.handleUpdatePayment(e));
            },

            async handleUpdatePayment(e) {
                e.preventDefault();
                const submitBtn = document.getElementById('update-payment-submit-btn');
                const errorDiv = document.getElementById('update-payment-error');

                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';
                errorDiv.classList.add('hidden');

                try {
                    const { paymentMethod, error } = await window.stripe.createPaymentMethod({
                        type: 'card',
                        card: window.updateCardElement,
                    });

                    if (error) {
                        throw new Error(error.message);
                    }

                    const response = await fetch(propertyTheme.rest_url + 'property/v1/user/payment-method', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': propertyTheme.nonce,
                        },
                        body: JSON.stringify({
                            payment_method_id: paymentMethod.id
                        })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to update payment method');
                    }

                    window.toast && window.toast('Payment method updated', 'success');
                    // Close modal via event then reload
                    window.dispatchEvent(new CustomEvent('close-update-payment-modal'));
                    setTimeout(() => location.reload(), 700);
                } catch (error) {
                    console.error('Update payment error:', error);
                    errorDiv.textContent = error.message;
                    errorDiv.classList.remove('hidden');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Save Payment Method';
                }
            }
        };
    }

    function invoicesTab() {
        return {
            invoices: [],
            loading: true,
            async load() {
                try {
                    const r = await fetch(propertyTheme.rest_url + 'property-theme/v1/user/invoices', {
                        headers: { 'X-WP-Nonce': propertyTheme.nonce }
                    });
                    this.invoices = await r.json();
                } catch(e) {
                    this.invoices = [];
                } finally {
                    this.loading = false;
                }
            }
        };
    }

    document.querySelectorAll('.upgrade-plan-btn').forEach(btn => {
        btn.addEventListener('click', async () => {

            const planId = btn.dataset.planId;
            const subscriptionId = btn.dataset.subscriptionId;
            const billingCycle = btn.dataset.billingCycle || 'monthly';

            if (!planId || !subscriptionId) {
                window.toast && window.toast('Invalid subscription or plan', 'error');
                return;
            }

            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = 'Updating…';
            window.toast && window.toast('Processing your plan change…', 'info', 'One moment', 8000);

            try {
                const response = await fetch(
                    propertyTheme.rest_url + 'property-theme/v1/update-subscription-plan',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': propertyTheme.nonce,
                        },
                        body: JSON.stringify({
                            subscription_id: subscriptionId,
                            plan_id: planId,
                            billing_cycle: billingCycle,
                            prorate: true,
                        }),
                    }
                );

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Plan change failed');
                }

                window.toast && window.toast('Plan updated successfully', 'success');
                setTimeout(() => window.location.reload(), 800);

            } catch (err) {
                console.error(err);
                window.toast && window.toast(err.message || 'Something went wrong', 'error');
                btn.disabled = false;
                btn.innerText = originalText || 'Upgrade / Downgrade';
            }
        });
    });
</script>

<?php get_footer(); ?>