<?php
/**
 * Header template
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<?php
$primary = get_option('mytheme_color_primary');
$secondary = get_option('mytheme_color_secondary');
$text_primary = get_option('mytheme_color_text_primary');
$text_secondary = get_option('mytheme_color_text_secondary');
?>
<style>
    :root {
        --primary-color:
            <?= $primary ?>
        ;
        --secondary-color:
            <?= $secondary ?>
        ;
        --text-primary-color:
            <?= $text_primary ?>
        ;
        --text-secondary-color:
            <?= $text_secondary ?>
        ;
        --secondary-font: 'Inter', sans-serif;
    }
</style>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>

    <?php
    $logo = get_option('mytheme_logo');
    ?>
    <?php if (!is_page(8)): ?>
        <header class="top-0 left-0 w-full pt-[1.875vw] pb-[1.615vw] z-10 bg-transparent absolute">
            <div class="bg-white rounded-br-[24px] absolute top-0 left-0 w-[21.51vw] h-full -z-10"></div>
            <nav class="w-[80%] mx-auto flex flex-wrap items-center justify-between">
                <div class="logo w-[9.948vw]">
                    <a href="<?= home_url(); ?>">
                        <?php if ($logo): ?>
                            <img src="<?php echo esc_url($logo); ?>" alt="Logo" class="h-[60px] object-contain">
                        <?php else: ?>
                            <span class="text-2xl font-bold text-slate-900">PropertyHub</span>
                        <?php endif; ?>
                    </a>
                </div>

                <?php get_template_part('template-parts/component', 'main-menu'); ?>
                <?php get_template_part('template-parts/component', 'cta-button'); ?>
            </nav>
        </header>
    <?php endif; ?>


    <?php if (is_front_page()): ?>
        <?php get_template_part('template-parts/section', 'hero'); ?>
    <?php endif; ?>


    <script>
        function searchComponent() {
            return {
                filters: {
                    search: '',
                    property_type: '',
                    bedrooms: '',
                    min_price: 0,
                    max_price: 0,
                },
                search() {
                    window.location.href = '/properties/?' + new URLSearchParams(this.filters).toString();
                }
            }
        }
    </script>