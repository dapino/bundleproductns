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

$allowBackOrder = '';
$disableQty = '';
$bundled_item_id = $addOnItem->item_id;
$bundled_product = $addOnItem->product;
$quantity_min = 0;
$quantity_max = get_post_meta($bundled_item_id, '_stock', true);
$qty_class = "quantity buttons_added";

if (!$bundled_product->is_sold_individually() || $quantity_min > 1) {
    if ($quantity_min == $quantity_max) {
        if ($quantity_min == 0) {
            $qty_class .= " qty_none";
        } else {
            $qty_class .= "";
            $disableQty = "disabled";
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
?>
<div class='bundled_item_wrap'>
<?php
if ($bundled_product->is_in_stock()) {
?>
    <div class="quantity_button">
        <div class = "<?php echo $qty_class; ?>" >
            <input type="button" value="-" class="minus" />
            <input 
                type = "number"
                step = "1"
                min = "<?php echo $quantity_min; ?>"
                max = "<?php echo $quantity_max; ?>"
                data-allow-backorder = "<?php echo $allowBackOrder; ?>"
                name = "quantity_<?php echo $bundled_item_id; ?>"
                value = "<?php echo $quantity_min; ?>"
                title = "Qty"
                class = "input-text qty text"
                size = "<?php echo $quantity_max; ?>"
                <?php echo $disableQty; ?>
            />
            <input type = "button" value = "+" class = "plus" />
        </div>
    </div>
<?php
}
?>
</div>
