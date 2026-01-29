<?php
/**
 * Pricing / Subscription Plans Page Template
 * Template Name: Pricing
 */
get_header();
?>

<div class="min-h-screen bg-slate-50">
    <!-- Hero Section -->
    <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white py-16 md:py-24">
        <div class="max-w-6xl mx-auto px-4 text-center">
            <h1 class="text-5xl md:text-6xl font-bold mb-4 text-balance">Simple, Transparent Pricing</h1>
            <p class="text-xl text-slate-300 text-balance">Choose the perfect plan to grow your real estate business</p>
        </div>
    </div>

    <!-- Pricing Plans -->
    <div class="max-w-7xl mx-auto px-4 py-16" x-data="pricingPlans()">
        <!-- Billing Toggle -->
        <div class="flex justify-center items-center gap-4 mb-12">
            <span :class="billingCycle === 'monthly' ? 'text-slate-900 font-bold' : 'text-slate-600'" class="text-lg">Monthly</span>
            <button 
                @click="billingCycle = billingCycle === 'monthly' ? 'yearly' : 'monthly'"
                class="relative inline-flex h-8 w-14 items-center rounded-full bg-slate-300"
            >
                <span 
                    :class="billingCycle === 'yearly' ? 'translate-x-7' : 'translate-x-1'"
                    class="inline-block h-6 w-6 transform rounded-full bg-white transition-transform"
                ></span>
            </button>
            <span :class="billingCycle === 'yearly' ? 'text-slate-900 font-bold' : 'text-slate-600'" class="text-lg">
                Yearly <span class="text-green-600 text-sm font-semibold">Save 20%</span>
            </span>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <!-- Plans Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8" x-show="!loading">
            <template x-for="plan in displayedPlans" :key="plan.id">
                <div 
                    :class="plan.popular ? 'ring-2 ring-blue-600 scale-105' : ''"
                    class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-all p-8 relative flex flex-col"
                >
                    <!-- Popular Badge -->
                    <div x-show="plan.popular" class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="bg-blue-600 text-white px-4 py-1 rounded-full text-sm font-bold">MOST POPULAR</span>
                    </div>

                    <!-- Plan Header -->
                    <div class="mb-8">
                        <h3 class="text-2xl font-bold text-slate-900 mb-2" x-text="plan.name"></h3>
                        <p class="text-slate-600 text-sm" x-text="plan.description || 'Perfect for getting started'"></p>
                    </div>

                    <!-- Pricing -->
                    <div class="mb-8 pb-8 border-b border-slate-200">
                        <div class="flex items-baseline gap-2">
                            <span class="text-5xl font-bold text-slate-900" x-text="'$' + getPrice(plan).toFixed(0)"></span>
                            <span class="text-slate-600 text-lg" x-text="billingCycle === 'monthly' ? '/month' : '/year'"></span>
                        </div>
                        <p x-show="plan.billing_cycle === 'yearly'" class="text-green-600 text-sm font-semibold mt-2">
                            <span x-text="'$' + (getPrice(plan) / 12).toFixed(0)"></span> per month, billed annually
                        </p>
                    </div>

                    <!-- Features List -->
                    <div class="mb-8 flex-grow">
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-slate-700" x-text="plan.max_properties + ' Property Listings'"></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-slate-700" x-text="plan.featured_limit + ' Featured Listings/month'"></span>
                            </div>
                            <div x-show="plan.analytics" class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-slate-700">Advanced Analytics Dashboard</span>
                            </div>
                            <template x-for="feature in plan.features" :key="feature">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-slate-700" x-text="feature"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- CTA Button -->
                    <button 
                        @click="selectPlan(plan.id)"
                        :class="plan.popular ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-900'"
                        class="w-full font-bold py-3 px-4 rounded-lg transition-colors"
                        x-text="isUserLoggedIn ? 'Subscribe Now' : 'Get Started'"
                    ></button>

                    <!-- Annual Discount Badge -->
                    <div x-show="billingCycle === 'yearly' && plan.billing_cycle === 'yearly'" class="mt-4 text-center">
                        <span class="text-green-600 text-sm font-semibold">Save $<span x-text="(getAnnualPrice(plan) * 0.2).toFixed(0)"></span>/year</span>
                    </div>
                </div>
            </template>
        </div>

        <!-- FAQ Section -->
        <div class="mt-20 max-w-3xl mx-auto">
            <h2 class="text-3xl font-bold text-slate-900 mb-12 text-center">Frequently Asked Questions</h2>
            
            <div class="space-y-4">
                <div class="bg-white rounded-lg shadow p-6" x-data="{ open: false }">
                    <button @click="open = !open" class="w-full flex justify-between items-center">
                        <span class="text-lg font-semibold text-slate-900">Can I change plans anytime?</span>
                        <svg :class="open ? 'transform rotate-180' : ''" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    </button>
                    <p x-show="open" class="mt-4 text-slate-600">Yes! You can upgrade or downgrade your subscription plan at any time. Changes take effect immediately.</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6" x-data="{ open: false }">
                    <button @click="open = !open" class="w-full flex justify-between items-center">
                        <span class="text-lg font-semibold text-slate-900">Is there a free trial?</span>
                        <svg :class="open ? 'transform rotate-180' : ''" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    </button>
                    <p x-show="open" class="mt-4 text-slate-600">The Starter plan is free forever! Upgrade anytime to unlock more features.</p>
                </div>

                <div class="bg-white rounded-lg shadow p-6" x-data="{ open: false }">
                    <button @click="open = !open" class="w-full flex justify-between items-center">
                        <span class="text-lg font-semibold text-slate-900">What payment methods do you accept?</span>
                        <svg :class="open ? 'transform rotate-180' : ''" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    </button>
                    <p x-show="open" class="mt-4 text-slate-600">We accept all major credit cards, PayPal, and bank transfers. All payments are processed securely through Stripe.</p>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="mt-20 bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg shadow-lg p-12 text-center text-white">
            <h3 class="text-3xl font-bold mb-4">Ready to get started?</h3>
            <p class="text-xl text-blue-100 mb-8">Join thousands of real estate professionals already using PropertyHub</p>
            <button 
                @click="selectPlan(plans[0]?.id)"
                class="bg-white text-blue-600 font-bold py-3 px-8 rounded-lg hover:bg-blue-50 transition-colors text-lg"
                x-text="isUserLoggedIn ? 'Choose Your Plan' : 'Sign Up Free'"
            ></button>
        </div>
    </div>
</div>

<script>
function pricingPlans() {
    return {
        plans: [],
        billingCycle: 'monthly',
        loading: true,
        isUserLoggedIn: <?php echo is_user_logged_in() ? 'true' : 'false'; ?>,

        get displayedPlans() {
            return this.plans.map((plan, index) => ({
                ...plan,
                popular: index === 1,
                description: index === 0 ? 'Getting started' : index === 1 ? 'Most popular' : 'For professionals'
            }));
        },

        getPrice(plan) {
            if (this.billingCycle === 'yearly' && plan.billing_cycle === 'yearly') {
                return parseFloat(plan.yearly_price || plan.price) * 12;
            } else if (this.billingCycle === 'monthly' && plan.billing_cycle === 'monthly') {
                return parseFloat(plan.monthly_price || plan.price);
            }
            return parseFloat(plan.price);
        },

        getAnnualPrice(plan) {
            if (plan.billing_cycle === 'monthly') {
                return parseFloat(plan.monthly_price || plan.price) * 12;
            }
            return parseFloat(plan.yearly_price || plan.price) * 12;
        },

        async loadPlans() {
            try {
                const baseUrl = '<?php echo rest_url('property-theme/v1/subscription-plans'); ?>';
                const response = await fetch(baseUrl, {
                    headers: {
                        'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>',
                    }
                });
                if (!response.ok) throw new Error('Failed to load plans');
                const data = await response.json();
                console.log('[v0] Plans loaded:', data);
                this.plans = data || [];
            } catch (error) {
                console.error('[v0] Error loading plans:', error);
                this.plans = [];
            } finally {
                this.loading = false;
            }
        },

        selectPlan(planId) {
            if (this.isUserLoggedIn) {
                window.location.href = '<?php echo home_url('/checkout'); ?>?plan=' + planId;
            } else {
                window.location.href = '<?php echo wp_login_url(add_query_arg(array())); ?>';
            }
        },

        init() {
            this.loadPlans();
        }
    }
}
</script>

<?php get_footer(); ?>
