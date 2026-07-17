<?php
/**
 * Global login / register modal.
 *
 * Rendered once per page via wp_footer (only when the visitor is logged out).
 * Any component can open it with:
 *     window.dispatchEvent(new CustomEvent('open-auth-modal', {
 *         detail: { mode: 'login' | 'register' }
 *     }));
 *
 * Also exposes:
 *   - window.ptToggleSavedProperty(propertyId) → saves/unsaves; opens modal if guest
 *   - window.ptIsPropertySaved(propertyId)     → boolean helper
 *   - window.ptSaveSearch(label, criteria, weeklyEmail) → save current search
 */
if (is_user_logged_in()) {
    // Even for logged-in users we still emit the JS helpers below.
    ?>
    <script>
    window.ptToggleSavedProperty = async function (propertyId) {
        try {
            const res = await fetch(window.wpUser.restRoot + '/user/toggle-saved-property', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.wpUser.nonce },
                credentials: 'same-origin',
                body: JSON.stringify({ property_id: Number(propertyId) }),
            });
            const data = await res.json();
            if (data && Array.isArray(data.saved_ids)) {
                window.wpUser.savedProperties = data.saved_ids;
                window.dispatchEvent(new CustomEvent('saved-properties-changed', { detail: { ids: data.saved_ids, propertyId } }));
            }
            return data;
        } catch (e) { return { state: 'error' }; }
    };
    window.ptIsPropertySaved = function (propertyId) {
        const ids = (window.wpUser && window.wpUser.savedProperties) || [];
        return ids.map(Number).includes(Number(propertyId));
    };
    window.ptSaveSearch = async function (label, criteria, weeklyEmail) {
        try {
            const res = await fetch(window.wpUser.restRoot + '/user/saved-searches', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.wpUser.nonce },
                credentials: 'same-origin',
                body: JSON.stringify({ label: label, criteria: criteria, weekly_email: !!weeklyEmail }),
            });
            return await res.json();
        } catch (e) { return { success: false }; }
    };
    </script>
    <?php
    return;
}
?>
<div x-data="authModal()"
     x-cloak
     x-show="open"
     @open-auth-modal.window="openWith($event.detail && $event.detail.mode)"
     @keydown.escape.window="close()"
     class="fixed inset-0 z-[9999] flex items-center justify-center px-4"
     style="display:none;">

    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="close()"></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 md:p-8"
         @click.stop>

        <button type="button" @click="close()"
                class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 transition"
                aria-label="Close">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <div class="mb-6 text-center">
            <div class="w-14 h-14 mx-auto mb-3 rounded-full flex items-center justify-center"
                 style="background:color-mix(in srgb, var(--primary-color) 12%, transparent);">
                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor"
                     style="color:var(--primary-color);">
                    <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-slate-900"
                x-text="mode === 'login' ? 'Sign in to continue' : 'Create your account'"></h3>
            <p class="text-slate-500 text-sm mt-1"
               x-text="mode === 'login'
                   ? 'Save properties, get weekly alerts on new matches.'
                   : 'Save properties and never miss a new listing.'"></p>
        </div>

        <div class="flex bg-slate-100 rounded-lg p-1 mb-5">
            <button type="button" @click="mode='login'; error=''"
                :class="mode==='login' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                class="flex-1 py-2 rounded-md text-sm font-semibold transition">Sign in</button>
            <button type="button" @click="mode='register'; error=''"
                :class="mode==='register' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                class="flex-1 py-2 rounded-md text-sm font-semibold transition">Register</button>
        </div>

        <template x-if="error">
            <div class="mb-3 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"
                 x-text="error"></div>
        </template>

        <form @submit.prevent="submit()" class="space-y-3">
            <template x-if="mode === 'register'">
                <input type="text" x-model="form.name" placeholder="Full name" required
                    class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:border-transparent outline-none"
                    style="--tw-ring-color: color-mix(in srgb, var(--primary-color) 30%, transparent);">
            </template>
            <input type="email" x-model="form.email" placeholder="Email address" required
                class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:border-transparent outline-none"
                style="--tw-ring-color: color-mix(in srgb, var(--primary-color) 30%, transparent);">
            <input type="password" x-model="form.password" placeholder="Password" required minlength="6"
                class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:border-transparent outline-none"
                style="--tw-ring-color: color-mix(in srgb, var(--primary-color) 30%, transparent);">

            <button type="submit" :disabled="loading"
                class="w-full py-3 rounded-lg text-white font-semibold hover:opacity-90 disabled:opacity-60 transition"
                style="background:var(--primary-color);">
                <span x-show="!loading" x-text="mode==='login' ? 'Sign in' : 'Create account'"></span>
                <span x-show="loading">Please wait…</span>
            </button>
        </form>

        <p class="text-xs text-slate-400 text-center mt-4">
            By continuing you agree to the terms &amp; privacy policy.
        </p>
    </div>
</div>

<script>
function authModal() {
    return {
        open: false,
        mode: 'login',
        loading: false,
        error: '',
        form: { name: '', email: '', password: '' },
        openWith(mode) {
            this.mode = mode || 'login';
            this.error = '';
            this.loading = false;
            this.open = true;
            document.body.style.overflow = 'hidden';
        },
        close() {
            this.open = false;
            document.body.style.overflow = '';
        },
        async submit() {
            this.loading = true; this.error = '';
            const endpoint = this.mode === 'login'
                ? window.wpUser.restRoot + '/auth/login'
                : window.wpUser.restRoot + '/auth/register';
            try {
                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify(this.form),
                });
                let data = {};
                try { data = await res.json(); } catch (_) {}
                if (!res.ok || !data.success) {
                    this.error = data.message || 'Something went wrong. Try again.';
                    this.loading = false;
                    return;
                }
                // Reload so wpUser + savedProperties reflect the new auth state
                window.location.reload();
            } catch (e) {
                this.error = 'Network error. Please try again.';
                this.loading = false;
            }
        },
    };
}

// Guest → opens the modal. This shim mirrors the logged-in version so callers
// can use the same helper everywhere.
window.ptToggleSavedProperty = async function () {
    window.dispatchEvent(new CustomEvent('open-auth-modal', { detail: { mode: 'login' } }));
    return { state: 'auth-required' };
};
window.ptIsPropertySaved = function () { return false; };
window.ptSaveSearch = async function () {
    window.dispatchEvent(new CustomEvent('open-auth-modal', { detail: { mode: 'login' } }));
    return { success: false, state: 'auth-required' };
};
</script>
