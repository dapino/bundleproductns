<?php

global $woocommerce, $product;

do_action('woocommerce_before_add_to_cart_form');
$product_bundle_ids = get_post_meta(get_the_ID(), '_bundle_data', true);

echo '<form method="post" enctype="multipart/form-data">';

foreach ($product_bundle_ids as $bundle_id => $value) {
    $bundled_item_id = $bundle_id->item_id;
    $bundled_product = $bundle_id->product;
    $item_quantity = $bundle_id->quantity;

    echo '<div class="bundled_product bundled_product_summary product">';

    // title template
    wc_wdm_product_bundles_get_template(
        'single-product/wdm-bundled-item-title.php',
        array(
        'title' => get_post(18)->post_title,
        ),
        false,
        plugin_dir_path(dirname(__FILE__)).'templates/'
    );

    wc_wdm_product_bundles_get_template(
        'single-product/wdm-bundled-item-image.php',
        array(
        'post_id' => 18,
        ),
        false,
        plugin_dir_path(dirname(__FILE__)).'templates/'
    );

    echo '<div class="details">';

// description template
    wc_wdm_product_bundles_get_template(
        'single-product/wdm-bundled-item-description.php',
        array(
        'description' => get_post($bundle_id)->post_content,
        ),
        false,
        plugin_dir_path(dirname(__FILE__)).'templates/'
    );

    echo '</div>';

    $product_id_generated = $post->ID.str_replace('_', '', $bundled_item_id);

    echo "<div class='cart' data-bundled-item-id='{$bundled_item_id}' data-product-id='{$product_id_generated}' data-bundle-id='{$post->ID}'>";
    echo '<div class="bundled_item_wrap">';

    if ($per_product_pricing) {
        wc_wdm_product_bundles_get_template(
            'single-product/bundled-item-price.php',
            array(
            'bundled_item' => $bundled_item,
            ),
            false,
            $woocommerce_bundles->woo_bundles_plugin_path().'/templates/'
        );
    }

    echo "<div class='quantity' style='display:none;'>
<input class='qty' type='hidden' name='bundled_item_quantity' value='{$item_quantity}' />
</div>";

    echo '</div></div></div>';
    echo '</br></br> </br></br> </br></br>';
}
echo '</form>';
