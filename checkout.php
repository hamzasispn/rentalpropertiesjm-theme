<?php
/**
 * Checkout Page Template
 * Template Name: Checkout
 * Updated to use Stripe Native Subscriptions endpoint
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(add_query_arg(array())));
    exit;
}

get_header();

$user_id = get_current_user_id();
$user = wp_get_current_user();
$plan_id = intval($_GET['plan'] ?? 0);

if (!$plan_id) {
    wp_redirect(home_url('/pricing'));
    exit;
}

// require_once get_template_directory() . '/inc/stripe-handler.php';
$plan = property_theme_get_plan($plan_id);
if (!$plan) {
    wp_redirect(home_url('/pricing'));
    exit;
}
?>

<div class="min-h-screen bg-slate-50">
    <div class="max-w-4xl mx-auto px-4 py-16">
        <!-- Back Link -->
        <a href="<?php echo home_url('/pricing'); ?>"
            class="text-blue-600 hover:text-blue-700 font-medium mb-8 inline-block">← Back to Pricing</a>

        <!-- x-data hoisted to the grid so BOTH the form (left) and the Order
             Summary sidebar (right, incl. coupon field + discount + finalTotal)
             live inside the same Alpine scope. Previously the sidebar was
             outside the form and every `coupon.*` binding evaluated to undefined —
             which is why the coupon UI never showed up. -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8"
             x-data="checkoutForm(<?php echo $plan_id; ?>)">
            <!-- Checkout Form -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-lg shadow p-8">
                    <h1 class="text-3xl font-bold text-slate-900 mb-2">Complete Your Subscription</h1>
                    <p class="text-slate-600 mb-8">Secure payment powered by Stripe</p>

                    <form id="checkout-form" @submit.prevent="submit()"
                        class="space-y-6">
                        <!-- Contact Info -->
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 mb-4">Contact Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                                    <input type="email" x-model="form.email"
                                        value="<?php echo esc_attr($user->user_email); ?>" disabled
                                        class="w-full px-4 py-2 border border-slate-300 rounded-lg bg-slate-100 text-slate-600">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Full Name</label>
                                    <input type="text" x-model="form.name"
                                        value="<?php echo esc_attr($user->display_name); ?>" required
                                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900">
                                </div>
                            </div>
                        </div>

                        <!-- Billing Address (Jamaica) -->
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 mb-4">Billing Address</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
                                    <input type="text" x-model="form.address" placeholder="Street address" required
                                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Parish</label>
                                    <select x-model="form.city" required
                                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900 bg-white">
                                        <option value="">Select a parish…</option>
                                        <?php foreach (array_keys(get_jamaica_cities()) as $parish): ?>
                                            <option value="<?php echo esc_attr($parish); ?>"><?php echo esc_html($parish); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 mb-4">Payment Method</h3>
                            <div id="card-element" class="p-4 border border-slate-300 rounded-lg bg-white"></div>
                            <div id="card-errors" class="text-red-600 text-sm mt-2" role="alert"></div>
                        </div>

                        <!-- Terms -->
                        <label class="flex items-start gap-3">
                            <input type="checkbox" x-model="form.terms" required
                                class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 mt-1">
                            <span class="text-sm text-slate-700">I agree to the terms and conditions and privacy
                                policy</span>
                        </label>

                        <!-- Submit Button -->
                        <button type="submit" :disabled="loading"
                            class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-slate-400 text-white font-bold py-3 px-4 rounded-lg transition-colors">
                            <span x-show="!loading">Complete Purchase - <span
                                    x-text="'$' + finalTotal().toFixed(2)"></span></span>
                            <span x-show="loading" class="flex items-center justify-center gap-2">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                                Processing...
                            </span>
                        </button>

                        <!-- Error Message -->
                        <div x-show="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"
                            x-text="error"></div>
                    </form>
                </div>
            </div>

            <!-- Order Summary -->
            <div>
                <div class="sticky top-24 bg-white rounded-lg shadow p-6 space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 mb-2">Order Summary</h3>
                    </div>

                    <!-- Plan Details -->
                    <div class="border-b border-slate-200 pb-6">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-slate-700"><?php echo esc_html($plan['name']); ?> Plan</span>
                            <span class="font-semibold text-slate-900"
                                x-text="'$' + (<?php echo floatval($plan['price']); ?>).toFixed(2)"></span>
                        </div>
                        <p class="text-sm text-slate-600">
                            <?php
                            if ($plan['billing_cycle'] === 'monthly') {
                                echo 'Billed monthly';
                            } else {
                                echo 'Billed annually';
                            }
                            ?>
                        </p>
                    </div>

                    <!-- Plan Features -->
                    <div class="border-b border-slate-200 pb-6">
                        <h4 class="font-semibold text-slate-900 mb-3">Includes:</h4>
                        <ul class="space-y-2 text-sm text-slate-600">
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <?php echo $plan['max_properties']; ?> Property Listings
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <?php echo $plan['featured_limit']; ?> Featured Listings/month
                            </li>
                            <?php if ($plan['analytics']): ?>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    Advanced Analytics
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Coupon / Promo code -->
                    <div class="border-b border-slate-200 pb-6" x-data="{ expand: false }">
                        <template x-if="!coupon.applied">
                            <div>
                                <button type="button" @click="expand = !expand"
                                    class="text-sm font-medium text-[var(--primary-color)] hover:underline inline-flex items-center gap-1.5">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/>
                                    </svg>
                                    <span x-text="expand ? 'Hide coupon' : 'Have a coupon?'"></span>
                                </button>
                                <div x-show="expand" x-transition class="mt-3 flex gap-2">
                                    <input type="text" x-model="coupon.code"
                                        @keydown.enter.prevent="applyCoupon()"
                                        placeholder="Enter code"
                                        class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-slate-900 uppercase text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <button type="button" @click="applyCoupon()" :disabled="coupon.loading || !coupon.code.trim()"
                                        class="px-4 py-2 bg-[var(--primary-color)] text-white rounded-lg text-sm font-semibold disabled:opacity-50 hover:opacity-90 transition">
                                        <span x-show="!coupon.loading">Apply</span>
                                        <span x-show="coupon.loading" x-cloak>Checking…</span>
                                    </button>
                                </div>
                                <p x-show="coupon.error" x-text="coupon.error" x-cloak
                                    class="mt-2 text-sm text-red-600"></p>
                            </div>
                        </template>

                        <template x-if="coupon.applied">
                            <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2.5">
                                <div class="flex items-center gap-2 min-w-0">
                                    <svg class="w-5 h-5 text-green-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-green-900 truncate" x-text="coupon.code"></p>
                                        <p class="text-xs text-green-700 truncate" x-text="coupon.summary"></p>
                                    </div>
                                </div>
                                <button type="button" @click="removeCoupon()"
                                    class="text-xs text-green-800 hover:text-red-600 shrink-0 ml-2">Remove</button>
                            </div>
                        </template>
                    </div>

                    <!-- Discount line (only shown when coupon applied) -->
                    <div x-show="coupon.applied" x-cloak
                        class="flex justify-between items-center text-sm">
                        <span class="text-slate-600">Discount</span>
                        <span class="text-green-700 font-semibold"
                            x-text="'−$' + coupon.discountAmount.toFixed(2)"></span>
                    </div>

                    <!-- Total -->
                    <div class="flex justify-between items-center pt-6 border-t border-slate-200">
                        <span class="text-lg font-semibold text-slate-900">Total</span>
                        <span class="text-2xl font-bold text-blue-600"
                            x-text="'$' + finalTotal().toFixed(2)"></span>
                    </div>

                    <!-- Secure Badge -->
                    <div class="flex items-center justify-center gap-2 text-sm text-slate-600 pt-4">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                clip-rule="evenodd"></path>
                        </svg>
                        Secure payment by Stripe
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
    function checkoutForm(planId) {
        const basePrice = <?php echo floatval($plan['price']); ?>;

        return {
            planId: planId,
            basePrice: basePrice,
            form: {
                name: '',
                email: '<?php echo esc_js($user->user_email); ?>',
                address: '',
                city: '',
                country: 'JM',
                terms: false,
            },
            coupon: {
                code: '',
                applied: false,
                loading: false,
                error: '',
                summary: '',
                percentOff: null,
                amountOff: null,   // cents
                discountAmount: 0, // dollars
            },
            loading: false,
            error: '',
            stripe: null,
            cardElement: null,

            init() {
                this.initStripe();
            },

            finalTotal() {
                const t = this.basePrice - this.coupon.discountAmount;
                return t > 0 ? t : 0;
            },

            async applyCoupon() {
                this.coupon.error = '';
                const code = this.coupon.code.trim().toUpperCase();
                if (!code) return;
                this.coupon.code = code;
                this.coupon.loading = true;
                try {
                    const res = await fetch(`${wpData.restUrl}property-theme/v1/validate-coupon`, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': wpData.nonce,
                        },
                        body: JSON.stringify({ code }),
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        throw new Error(data.message || 'That coupon isn\'t valid.');
                    }
                    this.coupon.summary    = data.summary || 'Discount applied';
                    this.coupon.percentOff = data.percent_off;
                    this.coupon.amountOff  = data.amount_off;
                    // Compute a preview discount for the summary. Server-side is
                    // the source of truth; this is display-only.
                    if (data.percent_off) {
                        this.coupon.discountAmount = +(this.basePrice * (data.percent_off / 100)).toFixed(2);
                    } else if (data.amount_off) {
                        this.coupon.discountAmount = +(data.amount_off / 100).toFixed(2);
                    } else {
                        this.coupon.discountAmount = 0;
                    }
                    this.coupon.applied = true;
                } catch (e) {
                    this.coupon.error = e.message || 'Could not apply coupon.';
                    this.coupon.applied = false;
                    this.coupon.discountAmount = 0;
                } finally {
                    this.coupon.loading = false;
                }
            },

            removeCoupon() {
                this.coupon = {
                    code: '', applied: false, loading: false, error: '',
                    summary: '', percentOff: null, amountOff: null, discountAmount: 0,
                };
            },

            initStripe() {
                const publishableKey = 'pk_test_51S1WzxB1fVG7OgbP1M3aDl9FmKiPor8xJT1vtqgAj33mY37UK75L0oMgSMaQswkQyjpyW9daLLpmWfK5HGjSN49e00VY6HZueY';
                if (!publishableKey) {
                    this.error = 'Stripe configuration error. Please contact support.';
                    return;
                }

                this.stripe = Stripe(publishableKey);
                const elements = this.stripe.elements();
                // Jamaica doesn't use US-style postal codes — hide the postal field.
                this.cardElement = elements.create('card', { hidePostalCode: true });
                this.cardElement.mount('#card-element');

                // Handle real-time validation errors
                this.cardElement.addEventListener('change', (event) => {
                    const displayError = document.getElementById('card-errors');
                    if (event.error) {
                        displayError.textContent = event.error.message;
                    } else {
                        displayError.textContent = '';
                    }
                });
            },

            async submit() {
                this.loading = true;
                this.error = '';

                try {
                    const { paymentMethod, error } = await this.stripe.createPaymentMethod({
                        type: 'card',
                        card: this.cardElement,
                        billing_details: {
                            name: this.form.name,
                            email: this.form.email,
                            address: {
                                line1: this.form.address,
                                city: this.form.city,
                                country: this.form.country || 'JM',
                            }
                        }
                    });

                    if (error) {
                        this.error = error.message;
                        this.loading = false;
                        return;
                    }

                    const response = await fetch(`${wpData.restUrl}property-theme/v1/create-subscription`, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': wpData.nonce
                        },
                        body: JSON.stringify({
                            plan_id: this.planId,
                            payment_method_id: paymentMethod.id,
                            billing_details: this.form,
                            coupon: this.coupon.applied ? this.coupon.code : '',
                        }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        this.error = data.message || data.data?.message || 'Payment failed. Please try again.';
                        console.error('[v0] Payment error response:', data);
                        this.loading = false;
                        return;
                    }

                    const redirectUrl = (data.data && data.data.redirect_url) || data.redirect_url || '<?php echo home_url("/dashboard"); ?>';
                    window.location.href = redirectUrl;
                } catch (error) {
                    console.error('[v0] Payment error:', error);
                    this.error = error.message || 'An error occurred. Please try again.';
                    this.loading = false;
                }
            }
        }
    }
</script>

<?php get_footer(); ?>
