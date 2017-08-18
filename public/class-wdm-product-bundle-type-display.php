<?php

namespace wisdmlabs\cpb;

class WdmProductBundleTypeDisplay
{
    public function __construct()
    {
        global $woocommerce;
        // Shows the grid for the Custom Product Box. And Price and Title of the custom product box.
        add_action('woocommerce_before_single_product_summary', array($this, 'loadProductLayoutHtml'));

        add_action('woocommerce_before_single_product_summary', array($this, 'mobileListProductLayoutHtml'), 20);

        // Change the tr class attributes when displaying bundled items in templates
        if (version_compare($woocommerce->version, '2.0.22') > 0) {
            add_filter('woocommerce_cart_item_class', array($this, 'wooBundlesTableItemClass'), 10, 3);
            add_filter('woocommerce_order_item_class', array($this, 'wooBundlesTableItemClass'), 10, 3);
        } else {
            // Deprecated
            add_filter('woocommerce_cart_table_item_class', array($this, 'wooBundlesTableItemClass'), 10, 3);
            add_filter('woocommerce_order_table_item_class', array($this, 'wooBundlesTableItemClass'), 10, 3);
            add_filter('woocommerce_checkout_table_item_class', array($this, 'wooBundlesTableItemClass'), 10, 3);
        }
        require_once('class-wdm-abstract-product-display.php');
        

        // Front end variation select box jquery for multiple products
        // add_action('wp_enqueue_scripts', array($this, 'wooBundlesFrontendScripts'), 100);
        add_filter('woocommerce_product_add_to_cart_text', array($this, 'wooBundlesAddToCartText'));
    }

    public function loadProductLayoutHtml()
    {
        global $post, $woo_wdm_bundle;

        $selectedLayout = $woo_wdm_bundle->getDesktopLayout($post->ID);
       
        // $tempDir = "template";
     //    $pos = strpos($selectedLayout, $tempDir);
     //    $path = substr($selectedLayout, $pos + strlen($tempDir) + 2);
        $path = substr($selectedLayout, strpos($selectedLayout, 'product-layouts/desktop-layouts'));

        require_once('class-wdm-product-gift-box.php');
        new WdmCPBProductGiftBox();

        wc_get_template(
            $path.'/index.php',
            array(
            ),
            'custom-product-boxes',
            plugin_dir_path(dirname(__FILE__)) . "templates/"
        );
    }

    public function mobileListProductLayoutHtml()
    {

        require_once('class-wdm-mobile-list-layout.php');
        new WdmCPBMobileListLayout();

        wc_get_template(
            'list/index.php',
            array(
                'layoutType' => 'mobile',
                'layoutName' => 'mobile_list',
            ),
            'custom-product-boxes',
            plugin_dir_path(dirname(__FILE__)) . "templates/product-layouts/mobile-layouts/"
        );
    }

    /**
     * Show something instead of an empty price (abandoned).
     */
    public function wooBundlesEmptyPrice($price, $product)
    {
        if (($product->get_type() == 'wdm_bundle_product') && (get_post_meta($product->get_id(), '_per_product_pricing_active', true) == 'no')) {
            return __('Price not set', 'custom-product-boxes');
        }

        return $price;
    }

    /**
     * Replaces add_to_cart button url with something more appropriate.
     * */
    public function wooBundlesLoopAddToCartUrl($url)
    {
        global $product;

        if ($product->get_type() == 'wdm_bundle_product') {
            return $product->add_to_cart_url();
        }

        return $url;
    }

    /**
     * Adds product_type_simple class for Ajax add to cart when all items are simple.
     * */
    public function wooBundlesAddToCartClass($class)
    {
        global $product;

        if ($product->get_type() == 'wdm_bundle_product') {
            if ($product->hasVariables()) {
                return '';
            } else {
                return $class . ' product_type_simple';
            }
        }

        return $class;
    }

    /**
     * Replaces add_to_cart text with something more appropriate.
     * */
    public function wooBundlesAddToCartText($text)
    {
        global $product;
        if (is_product()) {
            if ($product->get_type() == 'wdm_bundle_product') {
                return $product->add_to_cart_text();
            }
        }
        return $text;
    }

    /**
     * Adds QuickView support
     */
    public function wooBundlesLoopAddToCartLink($link, $product)
    {
        if ($product->get_type() == 'wdm_bundle_product') {
            if ($product->is_in_stock() && $product->allItemsInStock() && !$product->hasVariables()) {
                return str_replace('product_type_bundle', 'product_type_bundle product_type_simple', $link);
            } else {
                return str_replace('add_to_cart_button', '', $link);
            }
        }

        return $link;
    }

    /**
     * Change the tr class of bundled items in all templates to allow their styling.
     */
    public function wooBundlesTableItemClass($classname, $values)
    {
        if (isset($values['bundled_by'])) {
            return $classname . ' bundled_table_item';
        } elseif (isset($values['stamp'])) {
            return $classname . ' bundle_table_item';
        }

        return $classname;
    }
}
