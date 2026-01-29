<?php
/* ==============================
   1. REGISTER THEME OPTIONS PAGE
================================= */

function mytheme_add_settings_page()
{
    add_menu_page(
        'Theme Options',
        'Theme Options',
        'manage_options',
        'theme-options',
        'mytheme_render_settings_page',
        'dashicons-admin-customizer',
        61
    );
}
add_action('admin_menu', 'mytheme_add_settings_page');


/* ==============================
   2. REGISTER SETTINGS
================================= */

function mytheme_register_settings()
{

    // Logo
    register_setting('mytheme_settings_group', 'mytheme_logo');

    // Social repeater
    register_setting('mytheme_settings_group', 'mytheme_social_links');

    // Contact Info
    register_setting('mytheme_settings_group', 'mytheme_email');
    register_setting('mytheme_settings_group', 'mytheme_phone');

    // Colors
    register_setting('mytheme_settings_group', 'mytheme_color_primary');
    register_setting('mytheme_settings_group', 'mytheme_color_secondary');
    register_setting('mytheme_settings_group', 'mytheme_color_text_primary');
    register_setting('mytheme_settings_group', 'mytheme_color_text_secondary');
}
add_action('admin_init', 'mytheme_register_settings');


/* ==============================
   3. ADMIN PAGE HTML
================================= */

function mytheme_render_settings_page()
{
    $social_links = get_option('mytheme_social_links', []);
    ?>
    <div class="wrap">
        <h1>Theme Options</h1>

        <form method="post" action="options.php">
            <?php settings_fields('mytheme_settings_group'); ?>

            <h2>Logo</h2>
            <input type="text" name="mytheme_logo" id="mytheme_logo"
                value="<?php echo esc_attr(get_option('mytheme_logo')); ?>" style="width:60%;" />
            <button class="button upload-logo-button">Upload</button>

            <hr>

            <h2>Social Links (Repeater)</h2>

            <div id="social-repeater">
                <?php if (!empty($social_links)): ?>
                    <?php foreach ($social_links as $index => $item): ?>
                        <div class="social-item">
                            <input type="text" name="mytheme_social_links[<?php echo $index; ?>][image]"
                                value="<?php echo esc_attr($item['image']); ?>" placeholder="Image URL" style="width:40%;" />
                            <button class="button upload-social-image">Upload</button>

                            <input type="text" name="mytheme_social_links[<?php echo $index; ?>][link]"
                                value="<?php echo esc_attr($item['link']); ?>" placeholder="Link URL" style="width:40%;" />

                            <button class="button remove-item">Remove</button>
                            <br><br>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button class="button" id="add-social">Add Social Link</button>

            <hr>

            <h2>Contact Info</h2>
            <p><strong>Email</strong></p>
            <input type="text" name="mytheme_email" value="<?php echo esc_attr(get_option('mytheme_email')); ?>"
                style="width:60%;" />

            <p><strong>Phone</strong></p>
            <input type="text" name="mytheme_phone" value="<?php echo esc_attr(get_option('mytheme_phone')); ?>"
                style="width:60%;" />

            <hr>

            <h2>Colors</h2>
            <p>Primary Color</p>
            <input type="color" name="mytheme_color_primary"
                value="<?php echo esc_attr(get_option('mytheme_color_primary')); ?>" />

            <p>Secondary Color</p>
            <input type="color" name="mytheme_color_secondary"
                value="<?php echo esc_attr(get_option('mytheme_color_secondary')); ?>" />

            <p>Text Primary</p>
            <input type="color" name="mytheme_color_text_primary"
                value="<?php echo esc_attr(get_option('mytheme_color_text_primary')); ?>" />

            <p>Text Secondary</p>
            <input type="color" name="mytheme_color_text_secondary"
                value="<?php echo esc_attr(get_option('mytheme_color_text_secondary')); ?>" />

            <hr><br>
            <?php submit_button(); ?>
        </form>
    </div>

    <style>
        .social-item {
            margin-bottom: 10px;
            padding: 10px;
            background: #f8f8f8;
            border-radius: 6px;
        }
    </style>

    <script>
        jQuery(function ($) {

            // Ensure WP Media uploader is available
            if (typeof wp.media !== 'undefined') {

                // Upload Logo Button
                $('.upload-logo-button').on('click', function (e) {
                    e.preventDefault();

                    var file_frame = wp.media({
                        title: 'Select Logo',
                        button: { text: 'Use this logo' },
                        multiple: false
                    });

                    file_frame.on('select', function () {
                        var attachment = file_frame.state().get('selection').first().toJSON();
                        $('#mytheme_logo').val(attachment.url);
                    });

                    file_frame.open();
                });

                // Upload Social Icon Button
                $(document).on('click', '.upload-social-image', function (e) {
                    e.preventDefault();

                    var button = $(this);
                    var input = button.prev('input');

                    var file_frame = wp.media({
                        title: 'Select Image',
                        button: { text: 'Use this image' },
                        multiple: false
                    });

                    file_frame.on('select', function () {
                        var attachment = file_frame.state().get('selection').first().toJSON();
                        input.val(attachment.url);
                    });

                    file_frame.open();
                });
            }

            // ADD SOCIAL REPEATER ITEM
            $("#add-social").on("click", function (e) {
                e.preventDefault();
                const index = $("#social-repeater .social-item").length;

                $("#social-repeater").append(`
                    <div class="social-item">
                        <input type="text" name="mytheme_social_links[${index}][image]" placeholder="Image URL" style="width:40%;" />
                        <button class="button upload-social-image">Upload</button>

                        <input type="text" name="mytheme_social_links[${index}][link]" placeholder="Link URL" style="width:40%;" />

                        <button class="button remove-item">Remove</button>
                        <br><br>
                    </div>
                `);
            });

            // REMOVE SOCIAL ITEM
            $(document).on("click", ".remove-item", function (e) {
                e.preventDefault();
                $(this).closest(".social-item").remove();
            });

        });
    </script>
    <?php
}