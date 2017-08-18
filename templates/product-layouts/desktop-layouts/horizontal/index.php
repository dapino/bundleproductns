<?php
/**
 * Template Name: Horizontal Layout
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/custom-product-boxes/template/horizontal/wdm-cpb-horizontal-product-layout.php.
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
?>
<div id = "wdm-horizontal-cpb-container" class="wdm-horizontal-cpb-layout wdm-cpb-product-size">

    <?php
    do_action('wdm_cpb_remove_wc_product_display_hooks');

    do_action('woocommerce_before_add_to_cart_form');
    ?>

    <div class = "wdm_fix_div">
        <div class="wdm_product_info">
            <?php
                do_action('before_wdm_cpb_main_product_info');

                do_action('wdm_cpb_main_product_info');

                do_action('after_wdm_cpb_main_product_info');
            ?>
        </div>
        
        <?php

        /*
        Custom product boxes product layout hook
        */
        do_action('before_wdm_product_layout');

        do_action('wdm_cpb_enqueue_scripts');
        ?>

        <div class="wdm_product_bundle_container_form" >
            <?php
                do_action('wdm_cpb_before_add_to_cart_form');
            ?>
            <form name = "wdmBundleProduct" method="post" enctype="multipart/form-data" id="contactTrigger" novalidate>
                <div class = "wdm-bundle-product-product-group no-height">

                <?php
                    do_action('wdm_cpb_add_to_cart_form');
                ?>

                </div>
                <div class="gift-message-box">
                <?php
                    do_action('wdm_cpb_before_add_to_cart_button');
                    
                    do_action('wdm_cpb_add_to_cart_button');
                    
                    do_action('wdm_cpb_after_add_to_cart_button');
                ?>
                </div>
            </form>
            <?php

                do_action('wdm_cpb_after_add_to_cart_form');

                do_action('after_wdm_product_layout');

            ?>
        </div>
        <div class ="wdm-bundle-bundle-box" data-bundle-price = "<?php echo $product->get_price(); ?>">
            <div class ="gift_box_wrap">
                <?php
                    do_action('before_wdm_gift_layout');
                    /*
                    Custom product boxes gift layout hook
                    */
                    do_action('wdm_gift_layout');

                    do_action('after_wdm_gift_layout');
                ?>
            </div>
        </div>
    </div>
    <div class="clear"></div>

</div>