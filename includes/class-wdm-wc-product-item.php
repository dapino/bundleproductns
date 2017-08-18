<?php

namespace wisdmlabs\cpb;

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

class WdmWcProductItem
{
    protected $enable_transients;
    public function __construct($bundled_item_id, $parent)
    {
        $this->item_id    = $bundled_item_id;
        $this->product_id = $parent->wdm_custom_bundle_data[ $bundled_item_id ][ 'product_id' ];
        $this->bundle_id  = $parent->get_id();

        $this->item_data   = $parent->wdm_custom_bundle_data[ $bundled_item_id ];

        $this->purchasable = true;

        $bundled_product = wc_get_product($this->product_id);
        // echo "<pre>".print_r($bundled_product, 1)."</pre>";
        $postObject = get_post($this->product_id);
                // if not present, item cannot be purchased
        if (!empty($bundled_product)) {
            $this->product = $bundled_product;
            
            if ($this->purchasable) {
                // reduce npath complexity
                // $this->title                = ! empty($this->item_data[ 'override_title' ]) && $this->item_data[ 'override_title' ] == 'yes' ? $this->item_data[ 'product_title' ] : $bundled_product->post->post_title;

                // $this->description            = ! empty($this->item_data[ 'override_description' ]) && $this->item_data[ 'override_description' ] == 'yes' ? $this->item_data[ 'product_description' ] : $bundled_product->post->post_excerpt;

                // $this->quantity            = ! empty($_POST['quantity_'.$bundled_item_id]) ? ( int )$_POST['quantity_'.$bundled_item_id] : 0;

                if (! empty($this->item_data[ 'override_title' ]) && $this->item_data[ 'override_title' ] == 'yes') {
                    $this->title = $this->item_data[ 'product_title' ];
                } else {
                    $this->title = get_the_title($this->product->get_id());
                }

                if (! empty($this->item_data[ 'override_description' ]) && $this->item_data[ 'override_description' ] == 'yes') {
                    $this->description = ( int )$_POST['quantity_'.$bundled_item_id];
                } else {
                    $this->description = $postObject->post_excerpt;
                }

                if (! empty($_POST['quantity_'.$bundled_item_id])) {
                    $this->quantity = ( int )$_POST['quantity_'.$bundled_item_id];
                } else {
                    $this->quantity = 0;
                }
                
                $custom_stk_per_item = get_post_meta($bundled_item_id, '_stock', true);
                $this->setMaxVal($custom_stk_per_item);
                
                $this->sold_individually    = false;
                $this->on_sale                = false;
                $this->nyp                    = false;
                // $this->enable_transients    = false;
                $this->enable_stock = get_post_meta($bundled_item_id, '_manage_stock', true);
                $this->stock_status = get_post_meta($bundled_item_id, '_stock_status', true);
        
                if ($parent->getEnableBndltransient()) {
                    $this->setEnableTransients(true);
                }
        
                $this->wdmInit();
            }
        }
    }

    public function getItemId()
    {
        return $this->item_id;
    }

    public function setMaxVal($custom_stk_per_item)
    {
        $this->max_val = !empty($custom_stk_per_item) ? ( int )$custom_stk_per_item : 1;
    }

    public function getMaxVal()
    {
        return $this->max_val;
    }

    public function setEnableTransients($value)
    {
        $this->enable_transients = isset($value) ? $value : false;
    }

    public function getEnableTransients()
    {
        return $this->enable_transients;
    }
    
    public function wdmInit()
    {
        //global $woo_wdm_bundle;

        //$product_id        = $this->product_id;
        $bundled_product    = $this->product;

        $this->addPriceFilters();
        // if ($bundled_product->product_type == 'simple') {
        if ($bundled_product->is_sold_individually()) {
            $this->sold_individually = true;
        }

        if (! $bundled_product->is_in_stock() || ! $bundled_product->has_enough_stock($this->quantity)) {
            $this->stock_status = 'out-of-stock';
        }

        if ($bundled_product->is_on_backorder() && $bundled_product->backorders_require_notification()) {
            $this->stock_status = 'available-on-backorder';
        }

        $regular_price    = $this->getRegularPrice($bundled_product->get_regular_price(), $bundled_product);
        $price            = $this->getPrice($bundled_product->get_price(), $bundled_product);

        if ($regular_price > $price) {
            $this->on_sale = true;
        }
        // }
        $this->removePriceFilters();
    }
        
        /**
     * Bundled item sale status.
     */
    public function isOnSale()
    {
        return $this->on_sale;
    }

    /**
     * Bundled item purchasable status.
     */
    public function isPurchasable()
    {
        return $this->purchasable;
    }

    /**
     * Bundled item out of stock status.
     */
    public function isOutOfStock()
    {
        if ($this->stock_status == 'out-of-stock') {
            return true;
        }

        return false;
    }

    /**
     * Bundled item backorder status.
     */
    public function isOnBackorder()
    {
        if ($this->stock_status == 'available-on-backorder') {
            return true;
        }

        return false;
    }

    /**
     * Bundled item sold individually status.
     */
    public function isSoldIndividually()
    {
        return false;
    }

    /**
     * Bundled item name-your-price status.
     */
    public function isNyp()
    {
        return $this->nyp;
    }

    /**
     * Check if the product has variables to adjust before adding to cart.
     */
    public function hasHariables()
    {
        global $woocommerce_bundles;

        if ($this->isNyp() || $woocommerce_bundles->helpers->has_required_addons($this->product_id) || $this->product->get_type() == 'variable') {
            return true;
        }

        return false;
    }

    /**
     * Check if the item is a subscription.
     */
    public function isSub()
    {
        return false;
    }

    
    public function addPriceFilters()
    {
        if (version_compare(WC_VERSION, '3.0.0', '<')) {
            add_filter('woocommerce_get_price', array( $this, 'getPrice' ), 15, 2);
            add_filter('woocommerce_get_regular_price', array( $this, 'getRegularPrice' ), 15, 2);
        } else {
            add_filter('woocommerce_product_get_price', array( $this, 'getPrice' ), 15, 2);
            add_filter('woocommerce_product_get_regular_price', array( $this, 'getRegularPrice' ), 15, 2);
        }

        add_filter('woocommerce_get_sale_price', array( $this, 'getSalePrice' ), 15, 2);
        add_filter('woocommerce_get_price_html', array( $this, 'getPriceHtml' ), 10, 2);
        add_filter('woocommerce_get_variation_price_html', array( $this, 'getPriceHtml' ), 10, 2);
    }

    /**
     * Removes discount filters.
     */
    public function removePriceFilters()
    {
        if (version_compare(WC_VERSION, '3.0.0', '<')) {
            remove_filter('woocommerce_get_price', array( $this, 'getPrice' ), 15, 2);
            remove_filter('woocommerce_get_regular_price', array( $this, 'getRegularPrice' ), 15, 2);
        } else {
            remove_filter('woocommerce_product_get_price', array( $this, 'getPrice' ), 15, 2);
            remove_filter('woocommerce_product_get_regular_price', array( $this, 'getRegularPrice' ), 15, 2);
        }

        remove_filter('woocommerce_get_sale_price', array( $this, 'getSalePrice' ), 15, 2);
        remove_filter('woocommerce_get_price_html', array( $this, 'getPriceHtml' ), 10, 2);
        remove_filter('woocommerce_get_variation_price_html', array( $this, 'getPriceHtml' ), 10, 2);
    }

    /**
     * Filter get_price() calls for bundled products to include discounts.
     */
    public function getPrice($price, $product)
    {
        if ($product->get_id() !== $this->product->get_id()) {
            return $price;
        }

        return $price;
    }

    /**
     * Filter get_sale_price() calls for bundled products to include discounts.
     */
    public function getSalePrice($sale_price, $product)
    {
        if ($product->get_id() !== $this->product->get_id()) {
            return $sale_price;
        }
        //return empty($discount) ? $sale_price : $product->get_price();
        return $sale_price;
    }

    /**
     * Filter get_regular_price() calls for bundled products to include discounts.
     */
    public function getRegularPrice($regular_price, $product)
    {
        if ($product->get_id() !== $this->product->get_id()) {
            return $regular_price;
        }

        $price = $product->get_price();

        return empty($regular_price) ? ( double ) $price : ( double ) $regular_price;
    }

    /**
     * Filter the html price string of bundled items to show the correct price with discount and tax - needs to be hidden in per-product pricing mode.
     */
    public function getPriceHtml($price_html, $product)
    {
        //global $woocommerce_bundles;

        if (! isset($product->is_filtered_price_html)) {
            if (! $this->per_product_pricing) {
                return '';
            }
        }
        /* translators: for quantity use %2$s */
        return apply_filters('woocommerce_bundled_item_price_html', $this->quantity > 1 ? sprintf(__('%1$s <span class="bundled_item_price_quantity">/ pc.</span>', 'custom-product-boxes'), $price_html, $this->quantity) : $price_html, $price_html, $this);
    }

    /**
     * Filter get_sign_up_fee() calls for bundled subs to include discounts.
     */
    public function getSignUpFee($sign_up_fee, $product)
    {
        if ($product->get_id() !== $this->product->get_id()) {
            return;
        }

        if (! $this->per_product_pricing) {
            return 0;
        }

        $discount = $this->sign_up_discount;

        return empty($discount) ? ( double ) $sign_up_fee : ( double ) $sign_up_fee * (100 - $discount) / 100;
    }

    /**
     * Item title.
     */
    public function getTitle()
    {
        return apply_filters('woocommerce_bundled_item_title', $this->title, $this);
    }

    /**
     * Item Description.
     */
    public function getDescription()
    {
        return apply_filters('woocommerce_bundled_item_description', $this->description, $this);
    }
}
