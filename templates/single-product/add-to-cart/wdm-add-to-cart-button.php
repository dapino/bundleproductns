<?php
/**
 * The template for displaying Add to cart button within loops
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
$product_id = $product->get_id();
$bundle_price_data = $product->getBundlePriceData();
$bundle_item_quantity = $product->getBundledItemQuantities();

$quantities_array = esc_attr(json_encode($bundle_item_quantity));
$bundle_price_data = esc_attr(json_encode($bundle_price_data));
$classes = "cart bundle_form bundle_form_{$product_id}";
$availability = $product->get_availability();
$stock_message = "";
if ($availability['availability']) {
    $stock_message = apply_filters('woocommerce_stock_html', '<p class="stock ' . $availability['class'] . '">' . $availability['availability'] . '</p>', $availability['availability']);
}

?>
<div 
    class = "<?php echo $classes; ?>"
    data-bundle-price-data = "<?php echo $bundle_price_data; ?>"
    data-bundled-item-quantities = "<?php echo $quantities_array; ?>"
    data-bundle-id = "<?php echo $product_id; ?>"
>

<?php
do_action('woocommerce_before_add_to_cart_button');
?>

    <div class="bundle_wrap">
        <div class="wdm_bundle_price">
        <?php
            do_action("wdm_product_price_html");
        ?>
        </div>

        <?php
            echo $stock_message;
        ?>
        
        <div class="bundle_button">
        <?php
            woocommerce_quantity_input(array('min_value' => 1));
            $button_text = apply_filters('single_add_to_cart_text', __('Add to cart', 'custom-product-boxes'), $product->get_type());
            ?>
            <button
                type='submit'
                class='single_add_to_cart_button bundle_add_to_cart_button button alt' 
                <?php echo $disabled; ?>
            >
                <?php
                    echo $button_text;
                ?>  
            </button>
        </div>
        <input 
            type = "hidden"
            name = "add-to-cart"
            value = "<?php echo $product_id; ?>"
        />
    </div>
</div>