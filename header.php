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
    <?php if ( ! is_page([8, 26, 28]) ) : ?>
        <?php get_template_part('template-parts/header/component', 'header', array('logo' => $logo)); ?>
    <?php endif; ?>


    <?php if (is_front_page()): ?>
        <?php get_template_part('template-parts/section', 'hero'); ?>
    <?php endif; ?>

