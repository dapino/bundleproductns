<?php

/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/custom-product-boxes/template/vertical/wdm-cpb-vertical-product-layout.php.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.6.1
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

global $product;

if (!is_singular('product') || $product->get_type() !== 'wdm_bundle_product') {
    return;
}
do_action('wdm_cpb_before_template_starts');

do_action("wdm_cpb_{$layoutType}_enqueue_styles");

?>
<div class="wdm-mobile-list-cpb-layout">

    <?php
    do_action('wdm_cpb_remove_wc_product_display_hooks');

    do_action('woocommerce_before_add_to_cart_form');
    ?>

    <div class="wdm_product_info">
    <?php
        do_action('before_wdm_cpb_main_product_info');

        do_action('wdm_cpb_main_product_info');

        do_action('after_wdm_cpb_main_product_info');
    ?>
    </div>

    <div class="wdm-cpb-product-layout-wrapper">
    <?php
        do_action("before_wdm_cpb_{$layoutType}_product_layout");
        /*
        Custom product boxes gift layout hook
        */
        do_action("wdm_cpb_{$layoutType}_product_layout");

        do_action("after_wdm_cpb_{$layoutType}_product_layout");

    ?>
    </div>
    <div class="clear"></div>
    
    <h4 class="instructionTitle">Agrega máximo 24 unidades</h4>

    <table id="bundled_product_table" class="shop_table shop_table_responsive cart woocommerce-cart-form__contents">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th class="product-table-actions">Añadir/Eliminar</th>
            </tr>
        </thead>
        <tbody>
            <tr>

            </tr>
            
        </tbody>
        
    </table>


    <div class='mobile-list-layout-cpb-product-add-to-cart hide-numeric-button'>
        <?php
            do_action('wdm_cpb_before_add_to_cart_button');
            do_action('wdm_cpb_add_to_cart_button', $product);
            do_action('wdm_cpb_after_add_to_cart_button');
        ?>
    </div>
    <?php do_action("wdm_cpb_{$layoutType}_enqueue_scripts"); ?>
</div>
<?php do_action('wdm_cpb_after_template_ends'); ?>
