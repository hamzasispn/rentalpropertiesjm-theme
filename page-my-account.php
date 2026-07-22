<?php
/**
 * Template Name: Member Dashboard
 *
 * Personal area for regular members (buyers / renters).
 * Only surfaces: Overview, Saved Properties, Saved Searches, Settings.
 * Agents (users with an active subscription plan) are bounced to the
 * realtor dashboard at /dashboard/ so the two experiences never blur.
 */

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$current_user_id = get_current_user_id();

// Agents don't belong here — send them to the realtor dashboard.
if (function_exists('pt_user_is_agent') && pt_user_is_agent($current_user_id)) {
    wp_redirect(pt_get_agent_dashboard_url());
    exit;
}

// Personal page — never let a cache serve someone else's data.
nocache_headers();
if (!defined('DONOTCACHEPAGE'))   define('DONOTCACHEPAGE', true);
if (!defined('DONOTCACHEDB'))     define('DONOTCACHEDB', true);
if (!defined('DONOTCACHEOBJECT')) define('DONOTCACHEOBJECT', true);

get_header();

$logo    = get_option('mytheme_logo');
$user    = wp_get_current_user();
$archive = get_post_type_archive_link('property') ?: home_url('/properties/');
$saved_count = count(function_exists('pt_get_saved_properties') ? pt_get_saved_properties($current_user_id) : array());
$search_count = count(function_exists('pt_get_saved_searches') ? pt_get_saved_searches($current_user_id) : array());
?>

<div class="min-h-screen bg-slate-50 flex member-account" x-data="memberAccount()" x-init="initTabs()">

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-white flex flex-col fixed top-0 z-[99] h-screen border-r border-white/10">
        <a href="<?= esc_url(home_url()); ?>" class="px-6 py-4 border-b border-white/10 flex items-center gap-2">
            <?php if ($logo): ?>
                <img src="<?= esc_url($logo); ?>" alt="Logo" class="h-9 object-contain filter invert brightness-0">
            <?php else: ?>
                <span class="text-lg font-bold">Rental Properties JM</span>
            <?php endif; ?>
        </a>

        <nav class="flex-1 px-4 py-6 space-y-2 text-sm">
            <a href="#overview" @click="activateTab('overview', true)"
                :class="{ 'bg-slate-800 font-semibold': activeTab === 'overview' }"
                class="px-4 py-2.5 rounded-lg hover:bg-slate-800 transition flex items-center gap-3">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Overview
            </a>
            <a href="#saved-properties" @click="activateTab('saved-properties', true)"
                :class="{ 'bg-slate-800 font-semibold': activeTab === 'saved-properties' }"
                class="px-4 py-2.5 rounded-lg hover:bg-slate-800 transition flex items-center gap-3">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
                Saved Properties
            </a>
            <a href="#saved-searches" @click="activateTab('saved-searches', true)"
                :class="{ 'bg-slate-800 font-semibold': activeTab === 'saved-searches' }"
                class="px-4 py-2.5 rounded-lg hover:bg-slate-800 transition flex items-center gap-3">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Saved Searches
            </a>

            <!-- Upsell tab — highlighted so a member sees the upgrade path -->
            <a href="#become-realtor" @click="activateTab('become-realtor', true)"
                :class="activeTab === 'become-realtor'
                    ? 'bg-white text-slate-900 font-semibold'
                    : 'text-white hover:bg-slate-800'"
                class="px-4 py-2.5 rounded-lg transition flex items-center gap-3 border border-white/15 mt-3">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M15 9h.01M9 13h.01M15 13h.01"/>
                </svg>
                Become a Realtor
            </a>

            <a href="#settings" @click="activateTab('settings', true)"
                :class="{ 'bg-slate-800 font-semibold': activeTab === 'settings' }"
                class="px-4 py-2.5 rounded-lg hover:bg-slate-800 transition flex items-center gap-3">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                Settings
            </a>
        </nav>

        <div class="px-6 py-5 border-t border-white/10">
            <p class="text-xs text-slate-400">Signed in as</p>
            <p class="font-semibold text-sm mt-0.5 truncate"><?= esc_html($user->display_name); ?></p>
            <p class="text-xs text-slate-400 truncate"><?= esc_html($user->user_email); ?></p>
        </div>
    </aside>

    <!-- Main -->
    <main class="ml-64 flex-1 min-h-screen">
        <header class="bg-slate-900 h-[72px] flex items-center justify-end px-6 sticky top-0 z-10">
            <a href="<?= esc_url(wp_logout_url(home_url())); ?>"
               class="text-xs text-slate-300 hover:text-white px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 inline-flex items-center gap-2 transition">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Sign out
            </a>
        </header>

        <div class="p-6 md:p-10">

            <!-- Overview -->
            <section id="overview" x-show="activeTab === 'overview'" x-transition class="space-y-6">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Welcome back, <?= esc_html($user->display_name); ?></h1>
                    <p class="text-slate-500 mt-1">Your saved homes and search alerts, all in one place.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="#saved-properties" @click.prevent="activateTab('saved-properties', true)"
                       class="bg-white rounded-2xl border border-slate-200 p-6 hover:border-[var(--primary-color)] hover:shadow-md transition group">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Saved properties</p>
                                <p class="text-4xl font-bold text-slate-900 mt-2"><?= (int) $saved_count; ?></p>
                                <p class="text-sm text-[var(--primary-color)] font-semibold mt-4 inline-flex items-center gap-1 group-hover:gap-2 transition-all">
                                    View all
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </p>
                            </div>
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                                 style="background:color-mix(in srgb, var(--primary-color) 12%, transparent);">
                                <svg class="w-6 h-6" style="color:var(--primary-color);" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                </svg>
                            </div>
                        </div>
                    </a>

                    <a href="#saved-searches" @click.prevent="activateTab('saved-searches', true)"
                       class="bg-white rounded-2xl border border-slate-200 p-6 hover:border-[var(--primary-color)] hover:shadow-md transition group">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Saved searches</p>
                                <p class="text-4xl font-bold text-slate-900 mt-2"><?= (int) $search_count; ?></p>
                                <p class="text-sm text-[var(--primary-color)] font-semibold mt-4 inline-flex items-center gap-1 group-hover:gap-2 transition-all">
                                    Manage
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </p>
                            </div>
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                                 style="background:color-mix(in srgb, var(--primary-color) 12%, transparent);">
                                <svg class="w-6 h-6" style="color:var(--primary-color);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Ready to find your next home?</h3>
                    <p class="text-slate-500 text-sm mb-4">Browse the latest listings and save the ones you like — we'll email you every week when new matches land.</p>
                    <a href="<?= esc_url($archive); ?>" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-white font-semibold" style="background:var(--primary-color);">
                        Browse properties
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                <!-- Become a Realtor CTA — same as the sidebar item, keeps the upgrade path visible on entry -->
                <div class="rounded-2xl p-6 md:p-8 border relative overflow-hidden"
                     style="background:linear-gradient(135deg, var(--primary-color) 0%, color-mix(in srgb, var(--primary-color) 78%, #000) 100%); border-color:transparent;">
                    <div class="absolute -top-8 -right-8 w-40 h-40 rounded-full opacity-10 bg-white"></div>
                    <div class="relative flex items-start justify-between gap-6 flex-wrap">
                        <div class="max-w-xl">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/15 text-white text-xs font-bold uppercase tracking-wide mb-3">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Upgrade
                            </span>
                            <h3 class="text-white text-2xl md:text-3xl font-bold">Become a Realtor</h3>
                            <p class="text-white/80 text-sm md:text-base mt-2">
                                Pick a subscription and start listing your own properties on RentalPropertiesJM.
                            </p>
                        </div>
                        <a href="#become-realtor" @click.prevent="activateTab('become-realtor', true)"
                           class="inline-flex items-center gap-2 px-5 py-3 rounded-lg bg-white text-slate-900 font-semibold shadow-sm hover:shadow-lg transition">
                            View plans
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Saved Properties -->
            <section id="saved-properties" x-show="activeTab === 'saved-properties'" x-transition
                     x-data="savedPropertiesTab()" x-init="load()"
                     class="space-y-6">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900">Saved Properties</h1>
                        <p class="text-slate-500 mt-1">Homes you've bookmarked for later.</p>
                    </div>
                    <!-- Cross-link so users don't hunt for their search presets -->
                    <a href="#saved-searches"
                       class="group inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white hover:border-[var(--primary-color)] hover:shadow-sm transition text-sm font-semibold text-slate-700 hover:text-[var(--primary-color)]">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        View your saved searches
                        <svg class="w-4 h-4 group-hover:translate-x-0.5 transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>

                <div x-show="loading" class="bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-500">Loading…</div>

                <div x-show="!loading && items.length === 0" class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
                    <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center">
                        <svg class="w-7 h-7 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900">No saved properties yet</h3>
                    <p class="text-slate-500 mt-1">Tap the save icon on any property to bookmark it here.</p>
                    <a href="<?= esc_url($archive); ?>" class="inline-block mt-4 px-5 py-2 rounded-lg text-white font-semibold" style="background:var(--primary-color);">Browse listings</a>
                </div>

                <div x-show="!loading && items.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    <template x-for="item in items" :key="item.id">
                        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col hover:shadow-md transition">
                            <a :href="item.permalink" class="block relative h-48 bg-slate-100">
                                <img :src="item.thumbnail" :alt="item.title" class="w-full h-full object-cover">
                            </a>
                            <div class="p-4 flex-1 flex flex-col">
                                <div class="flex items-start justify-between gap-3">
                                    <a :href="item.permalink" class="font-semibold text-slate-900 line-clamp-2 hover:text-[var(--primary-color)]" x-text="item.title"></a>
                                    <span class="text-[var(--primary-color)] font-bold whitespace-nowrap" x-text="'$' + Number(item.price).toLocaleString()"></span>
                                </div>
                                <p class="text-sm text-slate-500 mt-1 line-clamp-1" x-text="item.address"></p>
                                <div class="flex items-center gap-3 mt-3 text-xs text-slate-600">
                                    <span x-show="item.bedrooms" x-text="item.bedrooms + ' Beds'"></span>
                                    <span x-show="item.bathrooms" x-text="item.bathrooms + ' Baths'"></span>
                                    <span x-show="item.area" x-text="item.area + ' sqft'"></span>
                                </div>
                                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                                    <a :href="item.permalink" class="text-sm font-semibold text-[var(--primary-color)]">View →</a>
                                    <button type="button" @click="remove(item.id)"
                                        class="text-sm text-slate-400 hover:text-red-500 inline-flex items-center gap-1">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            <!-- Saved Searches -->
            <section id="saved-searches" x-show="activeTab === 'saved-searches'" x-transition
                     x-data="savedSearchesTab()" x-init="load()"
                     class="space-y-6">
                <div class="flex justify-between items-start flex-wrap gap-3">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900">Saved Searches</h1>
                        <p class="text-slate-500 mt-1">Your search presets — reopen them anytime, or get weekly emails on new matches.</p>
                    </div>
                    <a href="<?= esc_url($archive); ?>" class="px-4 py-2 rounded-lg text-white font-semibold text-sm" style="background:var(--primary-color);">+ New search</a>
                </div>

                <div x-show="loading" class="bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-500">Loading…</div>

                <div x-show="!loading && items.length === 0" class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
                    <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center">
                        <svg class="w-7 h-7 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900">No saved searches yet</h3>
                    <p class="text-slate-500 mt-1">Run a search on the properties page and hit "Save this search".</p>
                </div>

                <div x-show="!loading && items.length > 0" class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100">
                    <template x-for="item in items" :key="item.id">
                        <div class="p-5 flex items-center gap-4 flex-wrap">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-semibold text-slate-900 truncate" x-text="item.label"></h3>
                                <p class="text-sm text-slate-500 mt-0.5" x-text="describeCriteria(item.criteria)"></p>
                            </div>
                            <label class="inline-flex items-center gap-2 cursor-pointer select-none px-3 py-1.5 border border-slate-200 rounded-lg hover:bg-slate-50">
                                <input type="checkbox" :checked="item.weekly_email"
                                       @change="toggleWeekly(item)"
                                       class="rounded text-[var(--primary-color)] focus:ring-[var(--primary-color)]/30">
                                <span class="text-sm text-slate-700">Weekly email</span>
                            </label>
                            <a :href="buildSearchUrl(item.criteria)"
                               class="px-3 py-1.5 rounded-lg text-white text-sm font-semibold"
                               style="background:var(--primary-color);">Run</a>
                            <button type="button" @click="remove(item.id)"
                                    class="text-slate-400 hover:text-red-500 p-1.5" title="Delete">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </section>

            <!-- Become a Realtor — uses the same plan-card component as home + billing -->
            <section id="become-realtor" x-show="activeTab === 'become-realtor'" x-transition class="space-y-6">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900">Become a Realtor</h1>
                        <p class="text-slate-500 mt-1">Choose the right plan for your property.</p>
                    </div>
                    <a href="<?= esc_url(home_url('/pricing')); ?>"
                       class="text-sm font-semibold text-[var(--primary-color)] inline-flex items-center gap-1 hover:gap-2 transition-all">
                        Full pricing page
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-6 md:p-8">
                    <?php get_template_part('template-parts/component', 'plan-card', array('subscription' => null)); ?>
                </div>
            </section>

            <!-- Settings -->
            <section id="settings" x-show="activeTab === 'settings'" x-transition class="space-y-6">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Account Settings</h1>
                    <p class="text-slate-500 mt-1">Manage your profile and preferences.</p>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-6 max-w-2xl">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4">Profile</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between border-b border-slate-100 pb-3">
                            <span class="text-slate-500">Name</span>
                            <span class="font-medium text-slate-900"><?= esc_html($user->display_name); ?></span>
                        </div>
                        <div class="flex justify-between border-b border-slate-100 pb-3">
                            <span class="text-slate-500">Email</span>
                            <span class="font-medium text-slate-900"><?= esc_html($user->user_email); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Member since</span>
                            <span class="font-medium text-slate-900"><?= esc_html(date_i18n('F Y', strtotime($user->user_registered))); ?></span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-6 max-w-2xl">
                    <h3 class="text-lg font-semibold text-slate-900 mb-2">Upgrade your account</h3>
                    <p class="text-slate-500 text-sm mb-4">Pick a plan to start posting properties on RentalPropertiesJM.</p>
                    <ol class="list-decimal list-inside space-y-1 text-sm text-slate-700 mb-4">
                        <li>Get a listing plan</li>
                        <li>Get a realtor plan</li>
                    </ol>
                    <a href="<?= esc_url(home_url('/pricing')); ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-sm"
                       style="background:color-mix(in srgb, var(--primary-color) 10%, transparent); color:var(--primary-color);">
                        View plans →
                    </a>
                </div>

                <div class="bg-white rounded-2xl border border-red-200 p-6 max-w-2xl">
                    <h3 class="text-lg font-semibold text-red-700 mb-2">Delete account</h3>
                    <p class="text-slate-500 text-sm mb-4">Permanently remove your account and all saved data. This can't be undone.</p>
                    <button type="button" onclick="alert('Please email support@rentalpropertiesjm.com to delete your account.');"
                            class="px-4 py-2 rounded-lg font-semibold text-sm bg-red-50 text-red-700 hover:bg-red-100 transition">
                        Request account deletion
                    </button>
                </div>
            </section>

        </div>
    </main>
</div>

<script>
function memberAccount() {
    return {
        activeTab: 'overview',
        initTabs() {
            const setFromHash = () => {
                const hash = (window.location.hash || '').replace('#', '');
                if (hash && document.getElementById(hash)) {
                    this.activeTab = hash;
                }
            };
            setFromHash();
            // React to same-page anchor clicks (e.g. the shortcut inside
            // Saved Properties that links to Saved Searches).
            window.addEventListener('hashchange', setFromHash);
        },
        activateTab(name, pushHash) {
            this.activeTab = name;
            if (pushHash) history.pushState(null, '', '#' + name);
        },
    };
}

function savedPropertiesTab() {
    return {
        loading: true, items: [],
        async load() {
            this.loading = true;
            try {
                const res = await fetch(window.wpUser.restRoot + '/user/saved-properties', {
                    headers: { 'X-WP-Nonce': window.wpUser.nonce }, credentials: 'same-origin',
                });
                this.items = ((await res.json()) || {}).items || [];
            } catch (e) { this.items = []; }
            this.loading = false;
        },
        async remove(id) {
            const before = this.items;
            this.items = this.items.filter(x => Number(x.id) !== Number(id));
            const res = await window.ptToggleSavedProperty(id);
            if (!res || res.state === 'error') this.items = before;
        },
    };
}

function savedSearchesTab() {
    return {
        loading: true, items: [],
        async load() {
            this.loading = true;
            try {
                const res = await fetch(window.wpUser.restRoot + '/user/saved-searches', {
                    headers: { 'X-WP-Nonce': window.wpUser.nonce }, credentials: 'same-origin',
                });
                this.items = ((await res.json()) || {}).items || [];
            } catch (e) { this.items = []; }
            this.loading = false;
        },
        async remove(id) {
            if (!confirm('Delete this saved search?')) return;
            const before = this.items;
            this.items = this.items.filter(x => x.id !== id);
            const res = await fetch(window.wpUser.restRoot + '/user/saved-searches/' + encodeURIComponent(id), {
                method: 'DELETE', headers: { 'X-WP-Nonce': window.wpUser.nonce }, credentials: 'same-origin',
            });
            if (!res.ok) this.items = before;
        },
        async toggleWeekly(item) {
            const nextVal = !item.weekly_email;
            item.weekly_email = nextVal;
            const res = await fetch(window.wpUser.restRoot + '/user/saved-searches/' + encodeURIComponent(item.id), {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.wpUser.nonce },
                credentials: 'same-origin',
                body: JSON.stringify({ weekly_email: nextVal }),
            });
            if (!res.ok) item.weekly_email = !nextVal;
        },
        describeCriteria(c) {
            if (!c) return 'All properties';
            const parts = [];
            if (c.listing_status) parts.push(c.listing_status === 'rent' ? 'For Rent' : 'For Sale');
            if (c.city) parts.push(c.city);
            if (c.location) parts.push(c.location);
            if (c.property_type) parts.push(c.property_type);
            if (c.beds) parts.push(c.beds + '+ Beds');
            if (c.baths) parts.push(c.baths + '+ Baths');
            if (c.price_min || c.price_max) {
                const cur = (c.currency || 'usd').toUpperCase();
                const mn = c.price_min ? Number(c.price_min).toLocaleString() : '0';
                const mx = c.price_max ? Number(c.price_max).toLocaleString() : '∞';
                parts.push(cur + ' ' + mn + ' – ' + mx);
            }
            if (c.featured) parts.push('Featured only');
            if (c.search) parts.push('"' + c.search + '"');
            return parts.length ? parts.join(' · ') : 'All properties';
        },
        buildSearchUrl(c) {
            const base = '<?= esc_url($archive); ?>';
            const map = {
                search: 'search', property_type: 'property_type', listing_status: 'listing_status',
                city: 'prop_city', location: 'location', beds: 'beds', baths: 'baths',
                price_min: 'price_min', price_max: 'price_max', area_min: 'area_min',
                area_max: 'area_max', featured: 'featured', currency: 'currency', keyword: 'keyword',
            };
            const params = new URLSearchParams();
            for (const key of Object.keys(map)) {
                if (c && c[key] !== undefined && c[key] !== '' && c[key] !== 0 && c[key] !== '0') {
                    params.set(map[key], c[key]);
                }
            }
            const qs = params.toString();
            return qs ? (base + '?' + qs) : base;
        },
    };
}
</script>

<?php
// Personal dashboard has its own chrome — skip the site footer's parish
// grid / link columns which would compete with the sidebar here.
wp_footer();
?>
</body>
</html>
