<?php

namespace wisdmlabs\cpb;

/**
 * Product Bundle cart functions and filters.
 * Referred from Woocommerce Bundle Product plugin.
 */
// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

class WdmWcProductCart
{
    public $wdm_addons_prefix = '';
    public $wdm_nyp_prefix    = '';

    /**
     * Setup cart class
     */
    public function __construct()
    {
        //global $woocommerce, $wp_query;


        // Support for Product Addons
        add_filter('product_addons_field_prefix', array( $this, 'wooBundlesAddonsCartPrefix' ), 10, 2);

        // Support for NYP
        add_filter('nyp_field_prefix', array( $this, 'woo_bundles_nyp_cart_prefix' ), 10, 2);

        // Validate bundle add-to-cart
        add_filter('woocommerce_add_to_cart_validation', array( $this, 'wooBundlesValidation' ), 10, 6);

        // Add bundle-specific cart item data
        add_filter('woocommerce_add_cart_item_data', array( $this, 'wooBundlesAddCartItemData' ), 10, 2);
        
        
        //to distinguish Box-products from other simple products on cart & thank you page & order dashoard
        add_filter('woocommerce_cart_item_class', array( $this, 'wdmCartItemClass'), 10, 3);
        add_filter('woocommerce_order_item_class', array( $this, 'wdmOrderItemClass'), 10, 3);
        add_filter('woocommerce_get_item_data', array( $this, 'wdmDisplayItemData'), 10, 2);
        if (version_compare(WC_VERSION, '3.0.0', '<')) {
            add_action('woocommerce_add_order_item_meta', array( $this, 'wdmAddValuesToOrderItemMeta'), 1, 2);
        } else {
            add_action('woocommerce_new_order_item', array( $this, 'wdmAddValuesToOrderItemMeta'), 1, 2);
        }
        // Add bundled items to the cart
        add_action('woocommerce_add_to_cart', array( $this, 'wooBundlesAddBundleToCart' ), 10, 6);

        // Modify cart items for bundled pricing strategy
        add_filter('woocommerce_add_cart_item', array( $this, 'wooBundlesAddCartItemFilter' ), 10, 2);

        // Load bundle data from session into the cart
        add_filter('woocommerce_get_cart_item_from_session', array( $this, 'wooBundlesGetCartDataFromSession' ), 10, 2);

        // Sync quantities of bundled items with bundle quantity
        add_filter('woocommerce_cart_item_quantity', array( $this, 'wooBundlesCartItemQuantity' ), 10, 2);
        add_filter('woocommerce_cart_item_remove_link', array( $this, 'wooBundlesCartItemRemoveLink' ), 10, 2);

        // Sync quantities of bundled items with bundle quantity
        add_filter('woocommerce_update_cart_action_cart_updated', array( $this, 'woocommerceUpdateCartActionCartUpdatedFunc' ), 10, 1);
        add_filter('woocommerce_update_cart_validation', array( $this, 'woocommerceUpdateCartValidationFunc' ), 10, 4);
        add_action('woocommerce_after_cart_item_quantity_update', array( $this, 'wooBundlesUpdateQuantityInCart' ), 1, 2);
        add_action('woocommerce_before_cart_item_quantity_zero', array( $this, 'wooBundlesUpdateQuantityInCart' ), 1);
        add_action('woocommerce_cart_item_removed', array( $this, 'wdmWooCartRemoveItemFunc' ), 1, 2);
        add_action('woocommerce_cart_item_restored', array( $this, 'wdmWooCartRestoreItemFunc' ), 1, 2);

        // Put back cart item data to allow re-ordering of bundles
        add_filter('woocommerce_order_again_cart_item_data', array( $this, 'wooBundlesOrderAgain' ), 10, 3);

        // Filter cart widget items
        add_filter('woocommerce_widget_cart_item_visible', array( $this, 'wooBundlesCartWidgetFilter' ), 10, 3);

        // Filter cart item count
        add_filter('woocommerce_cart_contents_count', array( $this, 'wooBundlesCartContentsCount' ));
        
        //debug
        // add_action( 'woocommerce_before_cart_contents', array($this, 'wooBundlesBeforeCart') );
    }

    /**
     * Sets a unique prefix for unique add-ons. The prefix is set and re-set globally before validating and adding to cart.
     */

    public function wdmCartItemClass($class, $cart_item)
    {
        if (isset($cart_item['wdm_custom_bundled_by'])) {
            return $class . " wdm_bundled_item";
        } else {
            return $class;
        }
    }
    
    public function wdmOrderItemClass($class, $order_item)
    {
        if (isset($order_item['wdm_bundled_by'])) {
            return $class . " wdm_bundled_item";
        } else {
            return $class;
        }
    }
    
    public function wdmDisplayItemData($data, $cartItem)
    {
        global $woocommerce;

        if (isset($cartItem['wdm_custom_bundled_by'])) {
            $postObject = get_post($woocommerce->cart->cart_contents[$cartItem['wdm_custom_bundled_by']]['data']->get_id());
            // echo "<pre>".print_r()."</pre>";exit;
            $cpb_name = $postObject->post_title ;

            $data[] = array(
            'name' => 'Bundled In2',
            'value' => $cpb_name,
            );
        }
        
        return $data;
    }
    
        
    public function wdmAddValuesToOrderItemMeta($item_id, $values)
    {
        global $woocommerce;
        if (version_compare(WC_VERSION, '3.0.0', '<')) {
            if (isset($values['wdm_custom_bundled_by'])) {
                $postObject = get_post($woocommerce->cart->cart_contents[$values['wdm_custom_bundled_by']]['data']->get_id());
                $cpb_name = $postObject->post_title ;//['post']->post_title;
                wc_add_order_item_meta($item_id, 'Bundled In', $cpb_name);
            }            
        } else {
            if (isset($values->legacy_values['wdm_custom_bundled_by'])) {
                $postObject = get_post($woocommerce->cart->cart_contents[$values->legacy_values['wdm_custom_bundled_by']]['data']->get_id());
                $cpb_name = $postObject->post_title ;//['post']->post_title;
                wc_add_order_item_meta($item_id, 'Bundled In', $cpb_name);
            }
        }
    }
    
    public function wooBundlesAddonsCartPrefix($prefix)
    {
        if (! empty($this->wdm_addons_prefix)) {
            return $this->wdm_addons_prefix . '-';
        }

        return $prefix;
    }

    /**
     * Validates add-to-cart for bundles.
     */
    public function wooBundlesValidation(
        $add,
        $product_id,
        $product_quantity,
        $variation_id = '',
        $variations = array(),
        $cart_item_data = array()
    ) {
        global $woocommerce;

        // Get product type
        $terms = get_the_terms($product_id, 'product_type');
        
        $product_type = 'simple';
        if (isset(current($terms)->name)) {
            $product_type = sanitize_title(current($terms)->name);
        }

        // prevent bundled items from getting validated - they will be added by the container item
        if (isset($cart_item_data[ 'is_bundled' ]) && isset($_GET[ 'order_again' ])) {
            return false;
        }
        
        if ($product_type == 'simple') {
            $alreadyInCartFlag = false;
            $product = wc_get_product($product_id);
            $ProductInCart = array();
            $cart_contents = $woocommerce->cart->cart_contents;
            foreach ($cart_contents as $key => $value) {
                if (isset($cart_contents[$key]['wdm_custom_stamp'][$product_id])) {              
                    $ProductInCart = $cart_contents[$key]['wdm_custom_stamp'][$product_id];
                    if ($ProductInCart['quantity'] > 0) {
                        $alreadyInCartFlag = true;
                        break;
                    }
                }
            }

            if ($alreadyInCartFlag) {
                return $this->bundledAddToCart($product, $product_id, $ProductInCart[ 'quantity' ], '', array(), array(), false);
            }
        }

        if ($product_type == 'wdm_bundle_product') {
            $product = wc_get_product($product_id);

            if (! $product) {
                return false;
            }

            // check request and prepare variation stock check data
            $stock_check_data = array();

            // grab bundled items
            $wdm_bundled_items = $product->getWdmCustomBundledItems();

            if (empty($wdm_bundled_items)) {
                return $add;
            }
                            
            foreach ($wdm_bundled_items as $bundled_item_id => $bundled_item) {
                $id                   = $bundled_item->product_id;
                $bundled_product_type = $bundled_item->product->get_type();

                $item_quantity = $bundled_item->quantity;

                $quantity      = $item_quantity * $product_quantity;
                if ($bundled_product_type == 'variable') {
                    if (isset($cart_item_data[ 'wdm_custom_stamp' ][ $bundled_item_id ][ 'variation_id' ]) && isset($_GET[ 'order_again' ])) {
                        $variation_id = $cart_item_data[ 'wdm_custom_stamp' ][ $bundled_item_id ][ 'variation_id' ];
                    } else {
                        $variation_id = $_REQUEST[ 'bundle_variation_id' ][ $bundled_item_id ];
                    }

                    if (isset($variation_id) && $variation_id > 1) {
                        $stock_check_data[ $id ][ 'type' ] = 'variable';

                        $variation_stock = get_post_meta($variation_id, '_stock', true);

                        if (get_post_meta($variation_id, '_price', true) === '') {
                            wdm_pb_bundles_add_notice(sprintf(__('Sorry, the selected variation of &quot;%s&quot; cannot be purchased.', 'custom-product-boxes'), get_the_title($id)), 'error');
                            return false;
                        }

                        if (! isset($stock_check_data[ $id ][ 'variations' ])) {
                            $stock_check_data[ $id ][ 'variations' ] = array();
                        }

                        if (! isset($stock_check_data[ $id ][ 'managed_quantities' ])) {
                            $stock_check_data[ $id ][ 'managed_quantities' ] = array();
                        }

                        if (! in_array($variation_id, $stock_check_data[ $id ][ 'variations' ])) {
                            $stock_check_data[ $id ][ 'variations' ][] = $variation_id;
                        }

// If stock is managed on a variation level
                        if (isset($variation_stock) && $variation_stock !== '') {
                            // If a stock-managed variation is added to the cart multiple times, its stock must be checked for the sum of all quantities
                            if (isset($stock_check_data[ $id ][ 'managed_quantities' ][ $variation_id ])) {
                                $stock_check_data[ $id ][ 'managed_quantities' ][ $variation_id ] += $quantity;
                            } else {
                                $stock_check_data[ $id ][ 'managed_quantities' ][ $variation_id ] = $quantity;
                            }
                        } else {
                            // Non-stock-managed variations of the same item
// must be stock-checked together
                            if (isset($stock_check_data[ $id ][ 'quantity' ])) {
                                $stock_check_data[ $id ][ 'quantity' ] += $quantity;
                            } else {
                                $stock_check_data[ $id ][ 'quantity' ] = $quantity;
                            }
                        }
                    } else {
                        wdm_pb_bundles_add_notice(__('Please choose product options&hellip;', 'custom-product-boxes'), 'error');
                        return false;
                    }

// Verify all attributes for the variable product were set - TODO: verify with filters

                    $attributes = (array) maybe_unserialize(get_post_meta($id, '_product_attributes', true));
                    $variations = array();
                    $all_set    = true;

                    $variation_data = array();

                    $custom_fields = get_post_meta($variation_id);

// Get the variation attributes from meta
                    foreach ($custom_fields as $name => $value) {
                        if (! strstr($name, 'attribute_')) {
                            continue;
                        }

                        $variation_data[ $name ] = sanitize_title($value[ 0 ]);
                    }


// Verify all attributes
                    foreach ($attributes as $attribute) {
                        if (! $attribute[ 'is_variation' ]) {
                            continue;
                        }

                        $taxonomy = 'attribute_' . sanitize_title($attribute[ 'name' ]);

                        if (! empty($_REQUEST[ 'bundle_' . $taxonomy ][ $bundled_item_id ])) {
                            // Get value from post data
// Don't use woocommerce_clean as it destroys sanitized characters
                            $value = sanitize_title(trim(stripslashes($_REQUEST[ 'bundle_' . $taxonomy ][ $bundled_item_id ])));

// Get valid value from variation
                            $valid_value = $variation_data[ $taxonomy ];

// Allow if valid
                            if ($valid_value == '' || $valid_value == $value) {
                                continue;
                            }
                        } elseif (isset($cart_item_data[ 'wdm_custom_stamp' ][ $bundled_item_id ][ 'attributes' ]) && isset($cart_item_data[ 'wdm_custom_stamp' ][ $bundled_item_id ][ 'variation_id' ]) && isset($_GET[ 'order_again' ])) {
                            $value = sanitize_title(trim(stripslashes($cart_item_data[ 'wdm_custom_stamp' ][ $bundled_item_id ][ 'attributes' ][ esc_html($attribute[ 'name' ]) ]))); // $taxonomy in WC 2.1

                            $valid_value = $variation_data[ $taxonomy ];

                            if ($valid_value == '' || $valid_value == $value) {
                                continue;
                            }
                        }

                        $all_set = false;
                        wdm_pb_bundles_add_notice(__('Please choose product options&hellip;', 'custom-product-boxes'), 'error');
                        
                        return false;
                    }

                    // if (! $all_set) {
                    //     wdm_pb_bundles_add_notice(__('Please choose product options&hellip;', 'custom-product-boxes'), 'error');
                    //     return false;
                    // }
                } elseif ($bundled_product_type == 'simple' || $bundled_product_type == 'subscription') {
                    $stock_check_data[ $id ][ 'type' ] = 'simple';

                    if (isset($stock_check_data[ $id ][ 'quantity' ])) {
                        $stock_check_data[ $id ][ 'quantity' ] += $quantity;
                    } else {
                        $stock_check_data[ $id ][ 'quantity' ] = $quantity;
                    }
                }


// Validate add-ons
                global $Product_Addon_Cart;

                if (! empty($Product_Addon_Cart)) {
                    $this->wdm_addons_prefix = $bundled_item_id;

                    if (! $Product_Addon_Cart->validate_add_cart_item(true, $id, $quantity)) {
                        return false;
                    }

                    $this->wdm_addons_prefix = '';
                }

// Validate nyp

                if (get_post_meta($product_id, '_per_product_pricing_active', true) == 'yes' && function_exists('WC_Name_Your_Price')) {
                    $this->wdm_nyp_prefix = $bundled_item_id;

                    if (! WC_Name_Your_Price()->cart->validate_add_cart_item(true, $id, $quantity)) {
                        return false;
                    }

                    $this->wdm_nyp_prefix = '';
                }
            }


            // Check stock for bundled items one by one
            // If out of stock, don't proceed

            foreach ($stock_check_data as $item_id => $data) {

                if ($data[ 'type' ] == 'simple' && $data[ 'quantity' ] != 0) {
                    $item_stock_status = get_post_meta($item_id, "_stock", true);
                    $single_product = wc_get_product($item_id);

                //The most important condition to check if whether product is purchasable or not. All products in the box must be purchasable.
                    if (! $single_product->is_purchasable()) {
                        $this->addNoticeConditionally(false, 'is_pur', $product, $single_product);
                        return false;
                    }

                    if (! $this->bundledAddToCart($product, $item_id, $data[ 'quantity' ], '', array(), array(), false)) {
                            return false;
                    }
                }
                
            }
        }
        return $add;
    }

    /**
     * Adds bundle specific cart-item data.
     */
    public function wooBundlesAddCartItemData($cart_item_data, $product_id)
    {
        //global $woo_wdm_bundle, $Product_Addon_Cart;
        global $Product_Addon_Cart;

// Get product type
        $terms        = get_the_terms($product_id, 'product_type');
        //$product_type = ! empty($terms) && isset(current($terms)->name) ? sanitize_title(current($terms)->name) : 'simple';
        if (! empty($terms) && isset(current($terms)->name)) {
            $product_type = sanitize_title(current($terms)->name);
        } else {
            $product_type = 'simple';
        }

        if ($product_type == 'wdm_bundle_product') {
            $product = wc_get_product($product_id);

            if (! $product) {
                return false;
            }

// grab bundled items
            $wdm_bundled_items = $product->getWdmCustomBundledItems();

            if (empty($wdm_bundled_items)) {
                return;
            }

// Create a unique stamp id with the bundled items' configuration
            $stamp = array();

            foreach ($wdm_bundled_items as $bundled_item_id => $bundled_item) {
                $id                   = $bundled_item->product_id;
                $bundled_product_type = $bundled_item->product->get_type();

                $stamp[ $bundled_item_id ][ 'product_id' ] = $id;
                $stamp[ $bundled_item_id ][ 'type' ]       = $bundled_product_type;
                $stamp[ $bundled_item_id ][ 'quantity' ]   = $bundled_item->quantity;

// Store bundled item addons add-ons config in stamp to avoid generating the same bundle cart id
                if (! empty($Product_Addon_Cart)) {
                    $addon_data = array();

// Set addons prefix
                    $this->wdm_addons_prefix = $bundled_item_id;

                    $bundled_product_id           = $id;
                    // commented as unused
                    //$bundled_product_variation_id = isset($stamp[ $bundled_item_id ][ 'variation_id' ]) ? $stamp[ $bundled_item_id ][ 'variation_id' ] : '';

                    $addon_data = $Product_Addon_Cart->add_cart_item_data($addon_data, $bundled_product_id);

// Reset addons prefix
                    $this->wdm_addons_prefix = '';

                    if (! empty($addon_data[ 'addons' ])) {
                        $stamp[ $bundled_item_id ][ 'addons' ] = $addon_data[ 'addons' ];
                    }
                }
            }

            $cart_item_data[ 'wdm_custom_stamp' ] = $stamp;

// Prepare additional data for later use
            $cart_item_data[ 'wdm_bundled_items' ] = array();

            return $cart_item_data;
        } else {
            return $cart_item_data;
        }
    }

    /**
     * Adds bundled items to the cart.
     */
    public function wooBundlesAddBundleToCart(
        $bundle_cart_key,
        $bundle_id,
        $bundle_quantity,
        $variation_id,
        $variation,
        $cart_item_data
    ) {
        global $woocommerce;
        if (isset($cart_item_data[ 'wdm_custom_stamp' ]) && ! isset($cart_item_data[ 'wdm_custom_bundled_by' ])) {
            // this id is unique, so that bundled and non-bundled versions of the same product will be added separately to the cart.
            $bundleitem_cart_data = array( 'bundled_item_id' => '', 'wdm_custom_bundled_by' => $bundle_cart_key, 'wdm_custom_stamp' => $cart_item_data[ 'wdm_custom_stamp' ], 'dynamic_pricing_allowed' => 'no' );

            // the bundle
            $bundle = $woocommerce->cart->cart_contents[ $bundle_cart_key ][ 'data' ];
            // Now add all items - yay
            foreach ($cart_item_data[ 'wdm_custom_stamp' ] as $bundled_item_id => $bundled_item_stamp) {
                // identifier needed for fetching post meta
                $bundleitem_cart_data[ 'bundled_item_id' ] = $bundled_item_id;

                $item_quantity = $bundled_item_stamp[ 'quantity' ];
                $quantity      = $item_quantity * $bundle_quantity;

                $product_id = $bundled_item_stamp[ 'product_id' ];

                $bundled_product_type = $bundled_item_stamp[ 'type' ];

                if ($bundled_product_type == 'simple' || $bundled_product_type == 'subscription') {
                    $variation_id = '';
                    $variations   = array();
                } elseif ($bundled_product_type == 'variable') {
                    $variation_id = $bundled_item_stamp[ 'variation_id' ];
                    $variations   = $bundled_item_stamp[ 'attributes' ];
                }

// Set addons and nyp prefix
                $this->wdm_addons_prefix = $this->wdm_nyp_prefix    = $bundled_item_id;

// Add to cart
                $bundleitem_cart_key = $this->bundledAddToCart($bundle, $product_id, $quantity, $variation_id, $variations, $bundleitem_cart_data, true);

                if (!$bundleitem_cart_key) {
                    return false;
                } else {
                    if (! in_array($bundleitem_cart_key, $woocommerce->cart->cart_contents[ $bundle_cart_key ][ 'wdm_bundled_items' ])) {
                        $woocommerce->cart->cart_contents[ $bundle_cart_key ][ 'wdm_bundled_items' ][] = $bundleitem_cart_key;
                    }
                }


// Reset addons and nyp prefix
                $this->wdm_addons_prefix = $this->wdm_nyp_prefix    = '';
            }
        }

        unset($bundle_id);
        unset($variation);
    }

    /**
     * Add a bundled product to the cart. Must be done without updating session data, recalculating totals or calling 'woocommerce_add_to_cart' recursively.
     */
    public function bundledAddToCart(
        $bundle,
        $product_id,
        $quantity = 1,
        $variation_id = '',
        $variation = '',
        $cart_item_data = array(),
        $add = false
    ) {
        global $woocommerce, $Product_Addon_Cart;
        if ($quantity < 0) {
            return false;
        }

        // Load cart item data when adding to cart
        if ($add) {
            // Add-ons cart item data is already stored in the composite_data array, so we can grab it from there.

            // Not doing so results in issues with file upload validation.
            if (! empty($Product_Addon_Cart) && isset($cart_item_data[ 'wdm_custom_stamp' ][ $cart_item_data[ 'bundled_item_id' ] ][ 'addons' ])) {
                remove_filter('woocommerce_add_cart_item_data', array( $Product_Addon_Cart, 'add_cart_item_data' ), 10, 2);

                $cart_item_data = (array) apply_filters('woocommerce_add_cart_item_data', $cart_item_data, $product_id, $variation_id);
                $cart_item_data[ 'addons' ] = $cart_item_data[ 'wdm_custom_stamp' ][ $cart_item_data[ 'bundled_item_id' ] ][ 'addons' ];

                add_filter('woocommerce_add_cart_item_data', array( $Product_Addon_Cart, 'add_cart_item_data' ), 10, 2);
            } else {
                $cart_item_data = (array) apply_filters('woocommerce_add_cart_item_data', $cart_item_data, $product_id, $variation_id);
            }

            // counted quantity
            $qty_counted_in_cart = $quantity;
        } else {
            $qty_counted_in_cart = 0;
        }

        // Generate a ID based on product ID, variation ID, variation data, and other cart item data
        $cart_id = $woocommerce->cart->generate_cart_id($product_id, $variation_id, $variation, $cart_item_data);

        // See if this product and its options is already in the cart
        $cart_item_key = $woocommerce->cart->find_product_in_cart($cart_id);

        // Ensure we don't add a variation to the cart directly by variation ID
        if ('product_variation' == get_post_type($product_id)) {
            $variation_id = $product_id;
            $product_id   = wp_get_post_parent_id($variation_id);
        }

        // Get the product
        if ($variation_id) {
            $product_data = wc_get_product($variation_id);
        } else {
            $product_data = wc_get_product($product_id);
        }

        if (! $product_data) {
            return false;
        }
        // echo '<pre>' . print_r($_POST) . '</pre>';
        // exit;
        // Check product is_purchasable
        if (! $product_data->is_purchasable()) {
            $this->addNoticeConditionally($add, 'is_pur', $bundle, $product_data);
            return false;
        }

        //Check for backorder. If Backorder is allowed, check for sold_individually. If such sold_individually product is alreafy in the cart, throw the error.
        if ($product_data->backorders_allowed()) {
            // is_sold_individually
            if ($product_data->is_sold_individually() == 'yes' && $quantity > 1) {
                $this->addNoticeConditionally($add, 'isi', $bundle, $product_data);
                return false;
            }
        } else {

             // Stock check - only check if we're managing stock and backorders are not allowed
            if (! $product_data->is_in_stock() && $quantity>0) {
                $this->addNoticeConditionally($add, 'instk', $bundle, $product_data);
                return false;

            }

            // is_sold_individually
            if ($product_data->is_sold_individually() == 'yes' && $quantity > 1) {
                $this->addNoticeConditionally($add, 'isi', $bundle, $product_data);
                return false;
            }

            if (! $product_data->has_enough_stock($quantity)) {
                $this->addNoticeConditionally($add, 'hasstk', $bundle, $product_data);
                return false;
            }
           
        // Stock check - this time accounting for whats already in-cart
            $product_qty_in_cart = $woocommerce->cart->get_cart_item_quantities();

            if ($product_data->managing_stock()) {
                // Variations
                if ($variation_id && $product_data->variation_has_stock) {
                    if (isset($product_qty_in_cart[ $variation_id ]) && ! $product_data->has_enough_stock($product_qty_in_cart[ $variation_id ] - $qty_counted_in_cart + $quantity)) {
                        if (! $add) {
                            wdm_pb_bundles_add_notice(sprintf(
                                '<a href="%s" class="button wc-forward">%s</a> %s',
                                $woocommerce->cart->get_cart_url(),
                                __('View Cart', 'custom-product-boxes'),
                                sprintf(__('&quot;%s&quot; cannot be added to the cart because there is not enough stock of &quot;%s&quot; &mdash; we have %s in stock and you already have %s in your cart.', 'custom-product-boxes'), $bundle->get_title(), $product_data->get_title(), $product_data->get_stock_quantity(), $product_qty_in_cart[ $variation_id ])
                            ), 'error');
                        }
                        return false;
                    }

                // Products
                } else {
                    if (isset($product_qty_in_cart[ $product_id ]) && ! $product_data->has_enough_stock($product_qty_in_cart[ $product_id ] - $qty_counted_in_cart + $quantity)) {
                        if (! $add) {
                            wdm_pb_bundles_add_notice(sprintf(
                                '<a href="%s" class="button wc-forward">%s</a> %s',
                                $woocommerce->cart->get_cart_url(),
                                __('View Cart', 'custom-product-boxes'),
                                sprintf(__('&quot;%s&quot; cannot be added to the cart because there is not enough stock of &quot;%s&quot; &mdash; we have %s in stock and you already have %s in your cart.', 'custom-product-boxes'), $bundle->get_title(), $product_data->get_title(), $product_data->get_stock_quantity(), $product_qty_in_cart[ $product_id ])
                            ), 'error');
                        }
                        return false;
                    }
                }
            } elseif ($product_data->is_sold_individually() == 'yes') {
                if (isset($product_qty_in_cart[ $product_id ]) && ($product_qty_in_cart[ $product_id ] == 1 && $quantity == 1)) {
                    if (! $add) {
                        wdm_pb_bundles_add_notice(sprintf(
                            '<a href="%s" class="button wc-forward">%s</a> %s',
                            $woocommerce->cart->get_cart_url(),
                            __('View Cart', 'custom-product-boxes'),
                            sprintf(__('&quot;%s&quot; cannot be added to the cart &mdash; only 1 &quot;%s&quot; can be purchased and you already have it in your cart.', 'custom-product-boxes'), $bundle->get_title(), $product_data->get_title())
                        ), 'error');
                    }
                    return false;
                }
            }
        }

        if (! $add) {
            return true;
        }

        // If cart_item_key is set, the item is already in the cart and its quantity will be handled by wooBundlesUpdateQuantityInCart.
        if (! $cart_item_key) {
            $cart_item_key = $cart_id;
            // Add item after merging with $cart_item_data - allow plugins and wooBundlesAddCartItemFilter to modify cart item
            $woocommerce->cart->cart_contents[ $cart_item_key ] = apply_filters('woocommerce_add_cart_item', array_merge($cart_item_data, array(
                'product_id'   => $product_id,
                'variation_id' => $variation_id,
                'variation'    => $variation,
                'quantity'     => $quantity,
                'data'         => $product_data
            )), $cart_item_key);
        }

        return $cart_item_key;
    }

    /**
     * add notices conditionally
     * called by bundledAddToCart() function
     */
    public function addNoticeConditionally($add, $type, $bundle, $product_data)
    {

        if (! $add) {
            if ($type == 'isi') {
                wdm_pb_bundles_add_notice(sprintf(__('&quot;%s&quot; cannot be added to the cart &mdash; only 1 &quot;%s&quot; can be purchased.', 'custom-product-boxes'), $bundle->get_title(), $product_data->get_title()), 'error');
            } elseif ($type == 'is_pur') {
                wdm_pb_bundles_add_notice(sprintf(__('&quot;%s&quot; cannot be added to the cart because &quot;%s&quot; cannot be purchased.', 'custom-product-boxes'), $bundle->get_title(), $product_data->get_title()), 'error');
            } elseif ($type == 'instk') {
                wdm_pb_bundles_add_notice(sprintf(__('&quot;%s&quot; cannot be added to the cart because &quot;%s&quot; is out of stock.', 'custom-product-boxes'), $bundle->get_title(), $product_data->get_title()), 'error');
            } elseif ($type == 'hasstk') {
                wdm_pb_bundles_add_notice(sprintf(__('&quot;%s&quot; cannot be added to the cart because there is not enough stock of &quot;%s&quot; (%s remaining).', 'custom-product-boxes'), $bundle->get_title(), $product_data->get_title(), $product_data->get_stock_quantity()), 'error');
            }
        }
    }

    /**
     * When a bundle is static-priced, the price of all bundled items is set to 0.
     */
    public function wooBundlesAddCartItemFilter($cart_data, $itm_id)
    {
        global $woocommerce;
        $cart_contents = $woocommerce->cart->cart_contents;

        if (isset($cart_data[ 'wdm_custom_bundled_by' ])) {
            $bundle_cart_id = $cart_data[ 'wdm_custom_bundled_by' ];

            $per_product_pricing  = ($cart_contents[ $bundle_cart_id ][ 'data' ]->per_product_pricing == true) ? true : false;
            $per_product_shipping = ($cart_contents[ $bundle_cart_id ][ 'data' ]->per_product_shipping == true) ? true : false;

            if ($per_product_pricing == false) {
                $cart_data[ 'data' ]->set_price(0);
                $cart_data[ 'data' ]->subscription_sign_up_fee = 0;
            } else {
                //                $discount = $cart_data[ 'wdm_custom_stamp' ][ $cart_data[ 'bundled_item_id' ] ][ 'discount' ];
//
//                if ( ! empty( $discount ) ) {
//
//                    $bundled_item_id = $cart_data[ 'bundled_item_id' ];
//                    $bundled_item    = $cart_contents[ $bundle_cart_id ][ 'data' ]->wdm_bundled_items[ $bundled_item_id ];
//
//                    $cart_data[ 'data' ]->price = $bundled_item->get_price( $cart_data[ 'data' ]->price, $cart_data[ 'data' ] );
//                }
            }

            if ($per_product_shipping == false) {
                $cart_data[ 'data' ]->virtual = 'yes';
            }
        }
        unset($itm_id);
        return $cart_data;
    }

    /**
     * Reload all bundle-related session data in the cart.
     */
    public function wooBundlesGetCartDataFromSession(
        $cart_item,
        $item_session_values
    ) {
        global $woocommerce;

        //$cart_contents = ! empty( $woocommerce->cart ) ? $woocommerce->cart->get_cart() : '';  //removed as woocommerce 2.4.4 doesn't give cart_contents in get_cart()...Fatal Error: 5000 times repeadtedly called function

        $cart_contents = '';
        if (! empty($woocommerce->cart)) {
            $cart_contents = $woocommerce->cart->cart_contents;
        }
        
        if (isset($item_session_values[ 'wdm_bundled_items' ])) {
            $cart_item[ 'wdm_bundled_items' ] = $item_session_values[ 'wdm_bundled_items' ];
        }

        if (isset($item_session_values[ 'wdm_custom_stamp' ])) {
            $cart_item[ 'wdm_custom_stamp' ] = $item_session_values[ 'wdm_custom_stamp' ];
        }

        if (isset($item_session_values[ 'wdm_custom_bundled_by' ])) {
            // load 'wdm_custom_bundled_by' field

            $cart_item[ 'wdm_custom_bundled_by' ] = $item_session_values[ 'wdm_custom_bundled_by' ];

            // load product bundle post meta identifier
            $cart_item[ 'bundled_item_id' ] = $item_session_values[ 'bundled_item_id' ];

            // load dynamic pricing permission
            $cart_item[ 'dynamic_pricing_allowed' ] = $item_session_values[ 'dynamic_pricing_allowed' ];

            // now modify item depending on bundle pricing & shipping options
            $bundle_cart_id = $cart_item[ 'wdm_custom_bundled_by' ];

            $per_product_pricing = false;
            if (isset($cart_contents[ $bundle_cart_id ][ 'data' ]->per_product_pricing)) {
                $per_product_pricing = $cart_contents[ $bundle_cart_id ][ 'data' ]->per_product_pricing;
            }

            $per_product_shipping = false;
            if (isset($cart_contents[ $bundle_cart_id ][ 'data' ]->per_product_shipping)) {
                $per_product_shipping = $cart_contents[ $bundle_cart_id ][ 'data' ]->per_product_shipping;
            }

            if ($per_product_pricing == false) {
                $cart_item[ 'data' ]->set_price(0);
                $cart_item[ 'data' ]->subscription_sign_up_fee = 0;
            } else {
                //                $discount = $cart_item[ 'wdm_custom_stamp' ][ $cart_item[ 'bundled_item_id' ] ][ 'discount' ];
//
//                if ( ! empty( $discount ) ) {
//
//                    $bundled_item_id = $cart_item[ 'bundled_item_id' ];
//                    $bundled_item    = $cart_contents[ $bundle_cart_id ][ 'data' ]->wdm_bundled_items[ $bundled_item_id ];
//
//                    $cart_item[ 'data' ]->price = $bundled_item->get_price( $cart_item[ 'data' ]->price, $cart_item[ 'data' ] );
//                }
            }

            if ($per_product_shipping == false) {
                $cart_item[ 'data' ]->virtual = 'yes';
            }
        }

        return $cart_item;
    }

    /**
     * Add "included with" bundle metadata.
     */
    public function wooBundlesGetItemData($data, $cart_item)
    {
        global $woocommerce;

        if (isset($cart_item[ 'wdm_custom_bundled_by' ]) && isset($cart_item[ 'wdm_custom_stamp' ])) {
            // not really necessary since we know its going to be there
            $product_key = $woocommerce->cart->find_product_in_cart($cart_item[ 'wdm_custom_bundled_by' ]);

            if (! empty($product_key)) {
                $product_name = get_post($woocommerce->cart->cart_contents[ $product_key ][ 'product_id' ])->post_title;
                $data[]       = array(
                    'name'    => __('Included with', 'custom-product-boxes'),
                    'display' => __($product_name)
                );
            }
        }

        return $data;
    }

    /**
     * Bundled items can't be removed individually from the cart - this hides the remove buttons.
     */
    public function wooBundlesCartItemRemoveLink($link, $cart_item_key)
    {
        global $woocommerce;

        if (isset($woocommerce->cart->cart_contents[ $cart_item_key ][ 'wdm_custom_bundled_by' ])) {
            return '';
        }

        return $link;
    }

    /**
     * Bundled item quantities can't be changed individually. When adjusting quantity for the container item, the bundled products must follow.
     */
    public function wooBundlesCartItemQuantity($quantity, $cart_item_key)
    {
        global $woocommerce;

        if (isset($woocommerce->cart->cart_contents[ $cart_item_key ][ 'wdm_custom_stamp' ])) {
            if (isset($woocommerce->cart->cart_contents[ $cart_item_key ][ 'wdm_custom_bundled_by' ])) {
                return $woocommerce->cart->cart_contents[ $cart_item_key ][ 'quantity' ];
            }
        }

        return $quantity;
    }

    public function woocommerceUpdateCartValidationFunc(
        $update_status,
        $cart_item_key,
        $values,
        $quantity
    ) {
        global $woocommerce;
//check if the request is occurred only for custom product boxes. If total quantity * bundle quantity exceeds stock then add notice.
        if (isset($woocommerce->cart->cart_contents[ $cart_item_key ][ 'wdm_custom_stamp' ]) && ! empty($woocommerce->cart->cart_contents[ $cart_item_key ][ 'wdm_custom_stamp' ]) && !empty($quantity)) {
            //check if value is set then proceed to verify quantity exceeds or not.
            if (isset($values[ 'wdm_custom_stamp' ])) {
                $bundle_item_ids = $values[ 'wdm_custom_stamp' ];
                if (is_array($bundle_item_ids)) {
                    foreach ($bundle_item_ids as $item_id => $item_data) {
                        $itemObject = wc_get_product($item_id);
                        if (isset($values[ 'data' ]->wdm_bundled_items) && !empty($item_data[ 'quantity' ])) {
                            $pre_item_data = $values[ 'data' ]->wdm_bundled_items;
                            $purchasable = $itemObject->is_purchasable();

                            if ($purchasable == 1) {
                                // if (isset($pre_item_data[ $item_id ]->enable_stock)) {
                                if (version_compare(WC_VERSION, '3.0.0', '<')) {
                                    $enable_stock = $pre_item_data[ $item_id ]->enable_stock;
                                    $wdm_custom_stkstatus = $itemObject->stock_status;
                                    $backordersAllowed = $itemObject->backorders;
                                    $soldIndividually = $itemObject->sold_individually;
                                } else {
                                    $enable_stock = $itemObject->managing_stock();
                                    $wdm_custom_stkstatus = $itemObject->get_stock_status();
                                    $backordersAllowed = $itemObject->get_backorders();
                                    $soldIndividually = $itemObject->is_sold_individually();                                    
                                }

                                if ($soldIndividually == "yes") {
                                    $update_status = "false";
                                    wc_add_notice(__('Quantity exceeds the maximum quantity of certain products in the box. Kindly reduce the number of boxes selected.', 'custom-product-boxes'), 'error');
                                    $_SESSION[ 'cart_update_status' ] = false;
                                    return false;
                                } elseif ($backordersAllowed == "no") {
                                    if ($wdm_custom_stkstatus == "out-of-stock") {
                                        $update_status = "false";
                                        wc_add_notice(__('Quantity exceeds the maximum quantity of certain products in the box. Kindly reduce the number of boxes selected.', 'custom-product-boxes'), 'error');
                                        $_SESSION[ 'cart_update_status' ] = false;
                                        return false;
                                    } elseif ($enable_stock == "yes") {
                                        $bundle_item_max = $pre_item_data[ $item_id ]->getMaxVal();
                                        if (! empty($bundle_item_max)) {
                                            if ($item_data[ 'quantity' ] * $quantity > $bundle_item_max) {
                                                $update_status = "false";
                                                wc_add_notice(__('Quantity exceeds the maximum quantity of certain products in the box. Kindly reduce the number of boxes selected.', 'custom-product-boxes'), 'error');
                                                $_SESSION[ 'cart_update_status' ] = false;
                                                return false;
                                            }
                                        }
                                    }
                                } //
                            }
                        }//data
                    }//for
                }
            }
        }
        return $update_status;
    }

    public function woocommerceUpdateCartActionCartUpdatedFunc($cart_updated)
    {
        //session_start();
        if (! empty($_SESSION)) {
            if (array_key_exists('cart_update_status', $_SESSION)) {
                if (! $_SESSION[ 'cart_update_status' ]) {
                    return false;
                }
            }
        }

        return $cart_updated;
    }

    /**
     * Keep quantities between bundled products and container items in sync.
     */
    public function wooBundlesUpdateQuantityInCart($cart_item_key, $quantity = 0)
    {
        global $woocommerce;

        if (isset($woocommerce->cart->cart_contents[ $cart_item_key ]) && ! empty($woocommerce->cart->cart_contents[ $cart_item_key ])) {
            if ($quantity == 0 || $quantity < 0) {
                $quantity = 0;
            } else {
                $quantity = $woocommerce->cart->cart_contents[ $cart_item_key ][ 'quantity' ];
            }

            if (isset($woocommerce->cart->cart_contents[ $cart_item_key ][ 'wdm_custom_stamp' ]) && ! empty($woocommerce->cart->cart_contents[ $cart_item_key ][ 'wdm_custom_stamp' ]) && ! isset($woocommerce->cart->cart_contents[ $cart_item_key ][ 'wdm_custom_bundled_by' ])) {
                // unique bundle wdm_custom_stamp added to all bundled items & the grouping item
                $stamp = $woocommerce->cart->cart_contents[ $cart_item_key ][ 'wdm_custom_stamp' ];

// change the quantity of all bundled items that belong to the same bundle config
                foreach ($woocommerce->cart->cart_contents as $key => $value) {
                    if (isset($value[ 'wdm_custom_bundled_by' ]) && isset($value[ 'wdm_custom_stamp' ]) && $cart_item_key == $value[ 'wdm_custom_bundled_by' ] && $stamp == $value[ 'wdm_custom_stamp' ]) {
                        if ($value[ 'data' ]->is_sold_individually() && $quantity > 0) {
                            $woocommerce->cart->set_quantity($key, 1, false);
                        } else {
                            $bundle_quantity = $value[ 'wdm_custom_stamp' ][ $value[ 'bundled_item_id' ] ][ 'quantity' ];
                            $woocommerce->cart->set_quantity($key, $quantity * $bundle_quantity, false);
                        }
                    }
                }
            }
        }
    }

    /**
     * Remove child products when custom product box is removed from the cart.
     */
    public function wdmWooCartRemoveItemFunc($cart_item_key, $cart_item)
    {
        global $woocommerce;
        if (isset($cart_item->cart_contents)) {
            foreach ($cart_item->cart_contents as $wdm_remove_key => $wdm_rmv_key_content) {
                if (isset($wdm_rmv_key_content[ 'wdm_custom_bundled_by' ])) {
                    if ($wdm_rmv_key_content[ 'wdm_custom_bundled_by' ] == $cart_item_key) {
                        $child_key = $wdm_remove_key;
                        if (! empty($child_key)) {
                            $woocommerce->cart->remove_cart_item($child_key);
                            unset($woocommerce->cart->cart_contents[ $child_key ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Restore child products when custom product box is restored from the cart.
     */
    public function wdmWooCartRestoreItemFunc($cart_item_key, $cart_item)
    {
        global $woocommerce;

        foreach ($cart_item as $wdm_rmv_key_content) {
            if (is_array($wdm_rmv_key_content)) {
                foreach ($wdm_rmv_key_content as $child_key => $child_key_content) {
                    if (isset($child_key_content[ 'wdm_custom_bundled_by' ])) {
                        if ($child_key_content[ 'wdm_custom_bundled_by' ] == $cart_item_key) {
                            $woocommerce->cart->cart_contents[ $child_key ] = $child_key_content;
                            $woocommerce->cart->cart_contents[ $child_key ]['data'] = wc_get_product($child_key_content['variation_id'] ? $child_key_content['variation_id'] : $child_key_content['product_id']);
                        }
                    }
                }
            }
        }
    }

    /**
     * Reinialize cart item data for re-ordering purchased orders.
     */
    public function wooBundlesOrderAgain($cart_item_data, $order_item)
    {
        if (isset($order_item[ 'wdm_custom_bundled_by' ]) && isset($order_item[ 'wdm_custom_stamp' ])) {
            $cart_item_data[ 'is_bundled' ] = 'yes';
        }

        if (! isset($order_item[ 'wdm_custom_bundled_by' ]) && isset($order_item[ 'wdm_custom_stamp' ])) {
            $cart_item_data[ 'wdm_custom_stamp' ] = maybe_unserialize($order_item[ 'wdm_custom_stamp' ]);
        }

        return $cart_item_data;
    }

    /**
     * Do not show container items or bundled items, depending on the chosen pricing method.
     */
    public function wooBundlesCartWidgetFilter($show, $cart_item)
    {
        global $woocommerce;

        if (isset($cart_item[ 'wdm_custom_bundled_by' ])) {
            // not really necessary since we know its going to be there
            $bundle_key = $woocommerce->cart->find_product_in_cart($cart_item[ 'wdm_custom_bundled_by' ]);

            if (! empty($bundle_key)) {
                $product_id = $woocommerce->cart->cart_contents[ $bundle_key ][ 'product_id' ];

                if (get_post_meta($product_id, '_per_product_pricing_active', true) == 'no') {
                    return false;
                }
            }
        }

        if (! isset($cart_item[ 'wdm_custom_bundled_by' ]) && isset($cart_item[ 'wdm_custom_stamp' ])) {
            if (get_post_meta($cart_item[ 'product_id' ], '_per_product_pricing_active', true) == 'yes') {
                return false;
            }
        }

        return $show;
    }

    /**
     * Filters the reported number of cart items depending on pricing strategy: per-item price: container is subtracted, static price: items are subtracted.
     */
    public function wooBundlesCartContentsCount($count)
    {
        global $woocommerce;

        $cart = $woocommerce->cart->get_cart();

        $subtract = 0;

        foreach ($cart as $value) {
            if (isset($value[ 'wdm_custom_bundled_by' ])) {
                $bundle_cart_id    = $value[ 'wdm_custom_bundled_by' ];
                $bundle_product_id = $cart[ $bundle_cart_id ][ 'product_id' ];

                $per_product_shipping = (get_post_meta($bundle_product_id, '_per_product_shipping_active', true) == 'yes') ? true : false;

                if (! $per_product_shipping) {
                    $subtract += $value[ 'quantity' ];
                }
            }

            if (isset($value[ 'wdm_custom_stamp' ]) && ! isset($value[ 'wdm_custom_bundled_by' ])) {
                $bundle_product_id = $value[ 'product_id' ];

                $per_product_shipping = (get_post_meta($bundle_product_id, '_per_product_shipping_active', true) == 'yes') ? true : false;

                if ($per_product_shipping) {
                    $subtract += $value[ 'quantity' ];
                }
            }
        }

        return $count - $subtract;
    }

    /**
     * Outputs a formatted subtotal ( @see woo_bundles_item_subtotal() ).
     */
    public function formatProductSubtotal($product, $subtotal)
    {
        global $woocommerce;

        $cart = $woocommerce->cart;

        $taxable = $product->is_taxable();

// Taxable
        if ($taxable) {
            if ($cart->tax_display_cart == 'excl') {
                $product_subtotal = wdm_pb_bundles_price($subtotal);

                if ($cart->prices_include_tax && $cart->tax_total > 0) {
                    $product_subtotal .= ' <small class="tax_label">' . $woocommerce->countries->ex_tax_or_vat() . '</small>';
                }
            } else {
                $product_subtotal = wdm_pb_bundles_price($subtotal);

                if (! $cart->prices_include_tax && $cart->tax_total > 0) {
                    $product_subtotal .= ' <small class="tax_label">' . $woocommerce->countries->inc_tax_or_vat() . '</small>';
                }
            }

// Non-taxable
        } else {
            //$product_subtotal = wdm_pb_bundles_price( $subtotal );
        }

        return $subtotal;
    }

    // Debugging only
    public function wooBundlesBeforeCart()
    {
        global $woocommerce;

        $cart = $woocommerce->cart->get_cart();

        echo '<br/>';
        echo '<br/>';

        echo 'Cart Contents Total: ' . $woocommerce->cart->cart_contents_total . '<br/>';
        echo 'Cart Tax Total: ' . $woocommerce->cart->tax_total . '<br/>';
        echo 'Cart Total: ' . $woocommerce->cart->get_cart_total() . '<br/>';

        foreach ($cart as $key => $data) {
            echo '<br/>Cart Item - ' . $key . ' (' . count($data) . ' items):<br/>';

            echo 'Price: ' . $data[ 'data' ]->get_price();
            echo '<br/>';

            foreach ($data as $datakey => $value) {
                print_r($datakey);
                if (is_numeric($value) || is_string($value)) {
                    echo ': ' . $value;
                }
                echo ' | ';
            }
        }
    }
}
