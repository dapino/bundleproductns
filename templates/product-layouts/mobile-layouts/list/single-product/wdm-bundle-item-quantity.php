<?php

/**
 * CPB Add-on Product
 * @version 3.3.0
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

global $woocommerce, $post, $prefillManager;

$allowBackOrder = '';
$disableQty = 'disabled';
$bundled_item_id = $addOnItem->item_id;
$bundled_product = $addOnItem->product;
$quantity_min = 0;
$quantity_max = get_post_meta($bundled_item_id, '_stock', true);
$qty_class = "quantity";

static $prefilledProducts = false;
if ($prefilledProducts === false) {
    $prefilledProductsData = $prefillManager->getPrefilledProducts($post->ID);
    if ($prefilledProductsData) {
        foreach ($prefilledProductsData as $singlePrefillProduct) {
            $prefilledProducts[$singlePrefillProduct['product_id']] = array(
                'product_qty' => $singlePrefillProduct['product_qty'],
                'product_mandatory' => $singlePrefillProduct['product_mandatory'],
            );
        }
    } else {
        $prefilledProducts = array();
    }
}



if (!$bundled_product->is_sold_individually() || $quantity_min > 1) {
    if ($quantity_min == $quantity_max) {
        if ($quantity_min == 0) {
            $qty_class .= " qty_none buttons_added";
        } else {
            $qty_class .= "";
        }
    }
    if ($bundled_product->backorders_allowed()) {
        $qty_class .= " buttons_added";
        $allowBackOrder = 'yes';
    } else {
        $qty_class .= " buttons_added";
        $allowBackOrder = 'no';
    }
}

if ($bundled_product->is_in_stock() && $bundled_product->backorders_allowed()) {
    $quantity_max = '';
    if ($bundled_product->is_sold_individually()) {
        $quantity_max = 1;
    }
}

?>
<div class='bundled_item_wrap'>
<?php
if ($bundled_product->is_in_stock()) {
?>
    <div class="quantity_button">
        <div class = "<?php echo $qty_class; ?>" >
            <div class="mobile-list-layout-plus-button">
                <input type = "button" value = "+" class = "wdm-cpb-addon-qty-plus" />
            </div>
            <div class="mobile-list-layout-quantity-field">
                <input 
                    type = "number"
                    step = "1"
                    min = "<?php echo $quantity_min; ?>"
                    max = "<?php echo $quantity_max; ?>"
                    data-product-mandatory = "<?php echo isset($prefilledProducts[$bundled_item_id]) ? $prefilledProducts[$bundled_item_id]['product_mandatory'] : 0 ?>"
                    data-allow-backorder = "<?php echo $allowBackOrder; ?>"
                    data-product-quantity = "<?php echo isset($prefilledProducts[$bundled_item_id]) ? $prefilledProducts[$bundled_item_id]['product_qty'] : 0; ?>"
                    data-product-prefill-quantity = "<?php echo isset($prefilledProducts[$bundled_item_id]) ? $prefilledProducts[$bundled_item_id]['product_qty'] : 0; ?>"
                    name = "mobile_quantity_<?php echo $bundled_item_id; ?>"
                    value = "<?php echo isset($prefilledProducts[$bundled_item_id]) ? $prefilledProducts[$bundled_item_id]['product_qty'] : $quantity_min; ?>"
                    title = "Qty"
                    class = "input-number qty number"
                    size = "<?php echo $quantity_max; ?>"
                    <?php echo $disableQty; ?>
                />
            </div>
            <div class="mobile-list-layout-minus-button">
                  <input type="button" value="-" class="wdm-cpb-addon-qty-minus" />       
            </div>
            <div class="clear"></div>
        </div>
    </div>
<?php
}
?>
</div>
