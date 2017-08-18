<?php

/**
 * CPB Add-on Product
 * @version 3.3.0
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

global $woocommerce, $post;

$wdm_manage_stock = get_option('woocommerce_manage_stock');

$bundled_item_id = $bundled_item->item_id;
$bundled_product = $bundled_item->product;
$price = $bundled_product->get_price();

$wdm_item_product = new \WC_Product($bundled_item_id);
$wdmItemPriceHtml = $wdm_item_product->get_price_html();
$isSoldIndividually = ($wdm_item_product->is_sold_individually() == true) ? 1 : 0;

$wdmItemCategories = get_the_terms( $bundled_item_id, 'product_cat' );
foreach ($wdmItemCategories as $term) {
    $wdmCatId = $term->term_id;
    $wdmCatName = $term->name;
    break;
}
$wdmCatThumbnailId = get_woocommerce_term_meta( $wdmCatId, 'thumbnail_id', true );
$wdmCatThumbnailImage = wp_get_attachment_url( $wdmCatThumbnailId );


$classes = apply_filters(
    'wdm_cpb_addon_product_div_classes',
    array(
        'mobile_list_layout',
        'product',
        'bundled_product',
        'bundled_product_summary',
        $stckMessage['wdm_no_stock'],
        $stckMessage['backorderClass']
    ),
    $post
);

if ($canBeSwapped !== false) {
    $classes[] = 'unpurchasable-product';
}
// Not allowing this through filter becasuse developers should not change this class name. This class is
// necessary to identify exact product code needs to work with.
$classes[] =  "mobile_bundled_product_{$bundled_item_id}";

$classes = implode(" ", $classes);
?>

<div 
    class = "<?php echo $classes; ?>"
    data-product-id = "<?php echo $bundled_item_id; ?>"
    data-product-price = "<?php echo $price; ?>"
    data-sold-individually = "<?php echo $isSoldIndividually; ?>"
    data-product-cat-id="<?php echo $wdmCatId; ?>"
    data-product-cat-name="<?php echo $wdmCatName; ?>"
    data-product-cat-thumbnail="<?php echo $wdmCatThumbnailImage; ?>"
>
    <?php
    //image template
    // do_action('wdm_add_on_product_image', $bundled_item_id);
?>
    <div class='mobile-list-layout-add-on-product-quantity'>
        <div 
            class = "cart"
            data-bundled-item-id = "<?php echo $bundled_item_id; ?>"
            data-product-id = "<?php echo $post->ID.$text; ?>"
            data-bundle-id = "<?php echo $post->ID; ?>"
        >

            <?php
                do_action('wdm_add_on_product_quantity', $bundled_item);
            ?>
        
        </div>
    </div>
    <div class='mobile-list-layout-addon-product-info'>
        <?php
        //title template
        do_action('wdm_add_on_product_title', $bundled_item->getTitle(), $bundled_item);
            
        if ($wdm_manage_stock == 'yes' || ! $wdm_item_product->is_in_stock() || ($wdm_item_product->is_in_stock() && $wdm_item_product->is_sold_individually())) {
            echo $stckMessage['price_message'];
        }

        if ($per_product_pricing) {
            ?>
            <p class="wdm_price">
                <?php echo $wdmItemPriceHtml; ?>
            </p>
            <?php
        }

        $text = str_replace('_', '', $bundled_item_id);
        ?>        
    </div>

    <div class="clear"></div>
</div>