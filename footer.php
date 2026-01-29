<?php
/**
 * Footer template
 */
?>

<footer class="bg-slate-900 text-slate-300 py-12 mt-16">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
            <div>
                <h3 class="text-white font-bold mb-4">PropertyHub</h3>
                <p class="text-sm">Premium real estate platform for modern property management and discovery.</p>
            </div>
            <div>
                <h4 class="text-white font-bold mb-4">Quick Links</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="<?php echo home_url('/properties'); ?>" class="hover:text-white transition-colors">Browse Properties</a></li>
                    <li><a href="<?php echo home_url('/pricing'); ?>" class="hover:text-white transition-colors">Pricing</a></li>
                    <li><a href="<?php echo home_url('/contact'); ?>" class="hover:text-white transition-colors">Contact</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-bold mb-4">Legal</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="<?php echo home_url('/privacy'); ?>" class="hover:text-white transition-colors">Privacy Policy</a></li>
                    <li><a href="<?php echo home_url('/terms'); ?>" class="hover:text-white transition-colors">Terms of Service</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-bold mb-4">Support</h4>
                <p class="text-sm">Email: support@propertyhub.local</p>
                <p class="text-sm">Phone: (555) 123-4567</p>
            </div>
        </div>
        <div class="border-t border-slate-700 pt-8 text-center text-sm">
            <p>&copy; 2025 PropertyHub. All rights reserved.</p>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
