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
$productColumnField = wisdmlabs\cpb\WdmCPBLayoutSetting::getProductField(get_the_ID());
$productFieldClass = get_post_meta(get_the_ID(), $productColumnField, true);
$classes = apply_filters(
    'wdm_cpb_addon_product_div_classes',
    array(
        'product',
        'bundled_product',
        'bundled_product_summary',
        $productFieldClass,
        $stckMessage['wdm_no_stock'],
        $stckMessage['backorderClass']
    ),
    $post
);
$bundled_item_id = $bundled_item->item_id;
// Not allowing this through filter becasuse developers should not change this class name. This class is
// necessary to identify exact product code needs to work with.
$classes[] =  "desktop_bundled_product_{$bundled_item_id}";

$classes = implode(" ", $classes);


$bundled_product = $bundled_item->product;
$price = $bundled_product->get_price();

$wdm_item_product = new \WC_Product($bundled_item_id);
$wdmItemPriceHtml = $wdm_item_product->get_price_html();
$wdmItemCategories = get_the_terms( $bundled_item_id, 'product_cat' );
foreach ($wdmItemCategories as $term) {
    $wdmCatId = $term->term_id;
    $wdmCatName = $term->name;
    break;
}
$wdmCatThumbnailId = get_woocommerce_term_meta( $wdmCatId, 'thumbnail_id', true );
$wdmCatThumbnailImage = wp_get_attachment_url( $wdmCatThumbnailId );


?>

<div 
    class = "<?php echo $classes; ?>"
    data-product-id="<?php echo $bundled_item_id; ?>"
    data-product-price="<?php echo $price; ?>"
    data-product-cat-id="<?php echo $wdmCatId; ?>"
    data-product-cat-name="<?php echo $wdmCatName; ?>"
    data-product-cat-thumbnail="<?php echo $wdmCatThumbnailImage; ?>"

>
    <?php
    //image template
    do_action('wdm_add_on_product_image', $bundled_item_id);

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
    <div class='details'>
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
</div>