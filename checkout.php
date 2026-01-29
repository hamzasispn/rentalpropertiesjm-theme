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

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Checkout Form -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-lg shadow p-8">
                    <h1 class="text-3xl font-bold text-slate-900 mb-2">Complete Your Subscription</h1>
                    <p class="text-slate-600 mb-8">Secure payment powered by Stripe</p>

                    <form id="checkout-form" x-data="checkoutForm(<?php echo $plan_id; ?>)" @submit.prevent="submit()"
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

                        <!-- Billing Address -->
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 mb-4">Billing Address</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
                                    <input type="text" x-model="form.address" placeholder="123 Main St" required
                                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">City</label>
                                        <input type="text" x-model="form.city" placeholder="New York" required
                                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900">
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-slate-700 mb-1">State/Province</label>
                                        <input type="text" x-model="form.state" placeholder="NY" required
                                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Postal Code</label>
                                        <input type="text" x-model="form.zip" placeholder="10001" required
                                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Country</label>
                                        <input type="text" x-model="form.country" placeholder="United States" required
                                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-900">
                                    </div>
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
                                    x-text="'$' + (<?php echo floatval($plan['price']); ?>).toFixed(2)"></span></span>
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

                    <!-- Total -->
                    <div class="flex justify-between items-center pt-6 border-t border-slate-200">
                        <span class="text-lg font-semibold text-slate-900">Total</span>
                        <span class="text-2xl font-bold text-blue-600"
                            x-text="'$' + (<?php echo floatval($plan['price']); ?>).toFixed(2)"></span>
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
        return {
            planId: planId,
            form: {
                name: '',
                email: '<?php echo esc_js($user->user_email); ?>',
                address: '',
                city: '',
                state: '',
                zip: '',
                country: '',
                terms: false,
            },
            loading: false,
            error: '',
            stripe: null,
            cardElement: null,

            init() {
                this.initStripe();
            },

            initStripe() {
                const publishableKey = 'pk_test_51S1WzxB1fVG7OgbP1M3aDl9FmKiPor8xJT1vtqgAj33mY37UK75L0oMgSMaQswkQyjpyW9daLLpmWfK5HGjSN49e00VY6HZueY';
                if (!publishableKey) {
                    this.error = 'Stripe configuration error. Please contact support.';
                    return;
                }

                this.stripe = Stripe(publishableKey);
                const elements = this.stripe.elements();
                this.cardElement = elements.create('card');
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
                                state: this.form.state,
                                postal_code: this.form.zip,
                                country: this.form.country,
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
