<?php

namespace wisdmlabs\cpb;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Define new product type wdm_bundle_product by extending class WC_Product
 * Overridden methods of WC_Product are not camelcased.
 */

class WCProductWdmBundleProduct extends \WC_Product
{
    public $wdm_custom_bndl_data;
    public $wdm_bundled_items;

    public $price;
    public $min_price;
    public $max_price;

    public $min_bundle_price;
    public $max_bundle_price;
    public $min_bundle_reg_price;
    public $max_bundle_reg_price;

    public $min_bndl_prc_exc_tax;
    public $min_bndl_prc_inc_tax;

    public $per_product_pricing;
    public $per_product_shipping;

    public $allitemssoldseparate;
    public $all_items_in_stock;
    public $hasitems_on_bkorder;
    public $on_sale;

    public $bundle_price_data;

    public $contains_nyp;
    public $isNyp;

    public $contains_sub;
    public $sub_id;
    // item with variables
    public $has_item_with_vars;
    public $all_items_visible;

    protected $enable_bndltransient;
    public $microdata_display = false;

    public function __construct($bundle)
    {
        // $this->product_type = 'wdm_bundle_product';

        parent::__construct($bundle);

        $this->wdm_custom_bundle_data = maybe_unserialize(get_post_meta($this->get_id(), '_bundle_data', true));

        if ($this->wdm_custom_bundle_data) {
            $this->wdm_custom_bundle_data = $this->unlinkAllDeletedProducts($this->wdm_custom_bundle_data, $this->get_id());
        }
        
        $this->contains_nyp = false;
        $this->isNyp = false;

        $this->contains_sub = false;

        $this->on_sale = false;

        $this->has_item_with_vars = false;
        $this->all_items_visible = true;

        $this->allitemssoldseparate = true;
        $this->all_items_in_stock = true;
        $this->hasitems_on_bkorder = false;
        $this->is_sold_individually = false;

        $this->per_product_pricing_active = false;
        if (get_post_meta($this->get_id(), '_per_product_pricing_active', true) == 'yes') {
            $this->per_product_pricing = true;
        }

        $this->product_base_pricing_active = false;
        if (get_post_meta($this->get_id(), '_product_base_pricing_active', true) == 'yes') {
            $this->product_base_pricing = true;
        }

        $this->per_product_shipping = false;
        if (get_post_meta($this->get_id(), '_per_product_shipping_active', true) == 'yes') {
            $this->per_product_shipping = true;
        }

        $this->min_price = get_post_meta($this->get_id(), '_min_bundle_price', true);
        $this->max_price = get_post_meta($this->get_id(), '_max_bundle_price', true);

        $product_price = get_post_meta($this->get_id(), '_price', true);

        // $this->regular_price = get_post_meta($this->get_id(), '_regular_price', true);

        $this->sale_price = get_post_meta($this->get_id(), '_sale_price', true);

        if (isset($this->sale_price) && $this->sale_price > 0) {
            $this->on_sale = true;
        }

        $this->price = $product_price;
        if ($this->per_product_pricing_active) {
            if (!$this->product_base_pricing_active) {
                $this->price = 0;
            }
        }
        $this->initItems();
    }

    public function set_enable_bndltransient($value)
    {

        if (get_post_meta($this->get_id(), 'enable_bndltransient', true) == 'yes') {
            $this->enable_bndltransient = true;
        } elseif ($value) {
            $this->enable_bndltransient = isset($value) ? $value : false;
        }
        // $this->enable_transients = isset($value) ? $value : false;
    }

    public function getEnableBndltransient()
    {
        return $this->enable_bndltransient;
    }

    public function get_type()
    {
        return 'wdm_bundle_product';
    }

    public function unlinkAllDeletedProducts($wdm_custom_bundle_data, $postId)
    {
        global $wpdb, $post;
        $postsTable = $wpdb->prefix . 'posts';
        $allProducts = $wpdb->get_col("SELECT ID FROM $postsTable WHERE post_type IN ('product', 'product_variation')");
        $cpb_keys = array_keys($wdm_custom_bundle_data);

        if ($cpb_keys && $allProducts) {
            $deletedProducts = array_diff($cpb_keys, $allProducts);
            if ($deletedProducts) {
                foreach ($deletedProducts as $deletedKey) {
                    unset($wdm_custom_bundle_data[$deletedKey]);
                }
            }

            update_post_meta($postId, '_bundle_data', $wdm_custom_bundle_data);
        }
        return $wdm_custom_bundle_data;
    }

    public function adjust_price($price)
    {
        $this->price = $this->price + $price;
    }


    public function get_price_html($price = '')
    {
        $price = $this->get_price();
        if ($this->is_on_sale() && $price > 0) {
            if (version_compare(WC_VERSION, '3.0.0', '<')) {
                $price = $this->get_price_html_from_to($this->regular_price, $price);
            } else {
                $price = wc_format_sale_price(wc_get_price_to_display($this, array( 'price' => $this->get_regular_price())), wc_get_price_to_display( $this )) . $this->get_price_suffix();
            }
        } elseif ($price == 0) {
            $price = wc_price( $price ) . $this->get_price_suffix();

            $price = apply_filters('woocommerce_price_html', $price, $this);
        } else {
            $price = parent::get_price_html();
        }

        return apply_filters('wdm_cpb_get_price_html', $price, $this);
    }

    /**
     * In per-product pricing mode, get_regular_price() normally returns zero, since the container item does not have a price of its own.
     */
    public function get_regular_price($context = 'view')
    {
        if ($this->per_product_pricing_active) {
            return (double) 0;
        } else {
            return parent::get_regular_price();
        }
    }

    /**
     * Prices incl. or excl. tax are calculated based on the bundled products prices, so get_price_suffix() must be overridden to return the correct field in per-product pricing mode.
     */
    public function get_price_suffix($price = '', $qty = 1)
    {
        if ( $price === '' ) {
            $price = $this->get_price();
        }
        unset($qty);
        
        if ($this->per_product_pricing_active) {
            $price_display_suffix = get_option('woocommerce_price_display_suffix');

            if ($price_display_suffix) {
                $price_display_suffix = ' <small class="woocommerce-price-suffix">' . $price_display_suffix . '</small>';

                $find = array(
                '{price_including_tax}',
                '{price_excluding_tax}',
                );

                $replace = array(
                wdm_pb_bundles_price($this->min_bndl_prc_inc_tax),
                wdm_pb_bundles_price($this->min_bndl_prc_exc_tax),
                );

                $price_display_suffix = str_replace($find, $replace, $price_display_suffix);
            }

            return apply_filters('woocommerce_get_price_suffix', $price_display_suffix, $this);
        } else {
            return parent::get_price_suffix();
        }
    }

    /**
     * Override on_sale status of product bundles. If a bundled item is on sale or has a discount applied, then the bundle appears as on sale.
     */
    public function is_on_sale($context = 'view')
    {
        $is_on_sale = false;

        if ($this->per_product_pricing_active && !empty($this->wdm_custom_bundle_data)) {
            if ($this->on_sale) {
                $is_on_sale = true;
            }
        } else {
            if ($this->sale_price && $this->sale_price == $this->price) {
                $is_on_sale = true;
            }
        }

        return apply_filters('woocommerce_bundle_is_on_sale', $is_on_sale, $this);
    }

    /**
     * A bundle is sold individually if it is marked as an "individually-sold" product, or if all bundled items are sold individually.
     */
    public function is_sold_individually()
    {
        return false;
    }

    /**
     * A bundle appears "on backorder" if the container is on backorder, or if a bundled item is on backorder (and requires notification).
     */
    // public function is_on_backorder()
    public function is_on_backorder($qty_in_cart = 0)
    {
        return parent::is_on_backorder() || $this->hasitems_on_bkorder;
    }

    /**
     * A bundle on backorder requires notification if the container is defined like this, or a bundled item is on backorder and requires notification.
     */
    public function backorders_require_notification()
    {
        return parent::backorders_require_notification() || $this->hasitems_on_bkorder;
    }

    /**
     * Availability of bundle based on bundle stock and stock of bundled items.
     */
    public function get_availability()
    {
        $backend_availability = parent::get_availability();

        if (!is_admin()) {
            $availability = $class = '';

            if (!$this->allItemsInStock()) {
                $availability = __('Out of stock', 'custom-product-boxes');
                $class = 'out-of-stock';
            } elseif ($this->hasitems_on_bkorder) {
                $availability = __('Available on backorder', 'custom-product-boxes');
                $class = 'available-on-backorder';
            }

            if ($backend_availability['class'] == 'out-of-stock' || $backend_availability['class'] == 'available-on-backorder') {
                return $backend_availability;
            } elseif ($class == 'out-of-stock' || $class == 'available-on-backorder') {
                return array('availability' => $availability, 'class' => $class);
            }
        }

        return $backend_availability;
    }

    /**
     * Get the add to url used mainly in loops.
     */
    public function add_to_cart_url()
    {
        $url = get_permalink($this->get_id());

        return apply_filters('bundle_add_to_cart_url', $url, $this);
    }

    /**
     * Get the add to cart button text
     */
    public function add_to_cart_text()
    {
        $text = __('Read more', 'custom-product-boxes');
        if ($this->is_purchasable() && $this->is_in_stock() && $this->allItemsInStock()) {
            $text = __('Read More', 'custom-product-boxes');
        }
        return apply_filters('bundle_add_to_cart_text', $text, $this);
    }

        /**
     * Returns false if the product cannot be bought.
     *
     * @return bool
     */
    public function is_purchasable()
    {
        $postObject = get_post($this->get_id());

        $purchasable = true;

        // Products must exist of course
        if (! $this->exists()) {
            $purchasable = false;

            // Other products types need a price to be set
        } elseif ($this->get_price() === '') {
            $purchasable = true;

            // Check the product is published
        } elseif ($postObject->post_status !== 'publish' && ! current_user_can('edit_post', $this->get_id())) {
            $purchasable = false;
        }

        return apply_filters('woocommerce_is_purchasable', $purchasable, $this);
    }

    public function initItems()
    {
        if (is_array($this->wdm_custom_bundle_data)) {
            foreach ($this->wdm_custom_bundle_data as $bundled_item_id => $bundled_item_data) {
                $bundled_item = new WdmWcProductItem($bundled_item_id, $this);
                $this->wdm_bundled_items[$bundled_item_id] = $bundled_item;
                unset($bundled_item_data);
            }
        }
    }

    /**
     * Stores bundle pricing strategy data that is passed to JS.
     */
    public function initPriceData()
    {
        //global $woo_wdm_bundle;

        $this->bundle_price_data = array();

        $this->bundle_price_data['per_product_pricing_active'] = $this->per_product_pricing;
        $this->bundle_price_data['prices'] = array();
        $this->bundle_price_data['regular_prices'] = array();

        if (empty($this->wdm_bundled_items)) {
            return;
        }

        if ($this->per_product_pricing_active && $this->contains_sub) {
            add_filter('woocommerce_get_price_suffix', array($this, 'removePriceSuffix'));
            $this->bundle_price_data['price_string'] = $this->getSubscriptionPriceHtml('%s');
            remove_filter('woocommerce_get_price_suffix', array($this, 'removePriceSuffix'));
        } else {
            $this->bundle_price_data['price_string'] = '%s';
        }
    }

    /**
     * Gets price data array. Contains localized strings and price data passed to JS.
     */
    public function getBundlePriceData()
    {
        $this->initPriceData();
        return $this->bundle_price_data;
    }

    /**
     * Gets quantities of all bundled items.
     */
    public function getBundledItemQuantities()
    {
        $bundle_item_quantity = array();

        if (empty($this->wdm_bundled_items)) {
            return $bundle_item_quantity;
        }

        foreach ($this->wdm_bundled_items as $bundled_item) {
            if (!empty($bundled_item->quantity)) {
                $bundle_item_quantity[$bundled_item->item_id] = $bundled_item->quantity;
            }
        }

        return $bundle_item_quantity;
    }

    /**
     * Gets all bundled items.
     */
    public function getWdmCustomBundledItems()
    {
        return $this->wdm_bundled_items;
    }

    /**
     * Gets all bundled items ordered by date.
     */
    public function getWdmSortedCustomBundledItems()
    {
        $items = array();
        $new_wdm_bundle = array();
        if ($this->wdm_bundled_items) {
            // CREATING NEW ARRAY OF ITEM ID AND DATE FOR SORTING
            foreach ($this->wdm_bundled_items as $key => $value) {
                if (version_compare(WC_VERSION, '3.0.0', '<')) {
                    $items[$value->item_id] = $value->product->post->post_date;
                } else {
                    $product = wc_get_product($value->getItemId());
                    $date = $product->get_date_created();
                    $items[$value->getItemId()] = $date->date_i18n();
                }
            }
            // GETTING SORTED DATE ARRAY
            uasort($items, array($this, 'sortByDate'));
            
            // SORTING OBJECY ARRAY ACCORDING TO SORTED ARRAY
            array_multisort($this->wdm_bundled_items, $items, SORT_STRING);
            
            // FIXING INDEXES OF THE SORTED OBJECT ARRAY
            $new_wdm_bundle = array();
            foreach ($this->wdm_bundled_items as $key => $value) {
                $new_wdm_bundle[$value->getItemId()] = $value;
            }
            krsort($new_wdm_bundle);
            return $new_wdm_bundle;
        }
        return $new_wdm_bundle;
    }

    public function sortByDate($a, $b)
    {
        return strtotime($a) - strtotime($b);
    }

    /**
     * Makes a subscription product temporarily appear as simple to isolate the recurring price html string
     */
    public function isolateRecurringPriceHtml()
    {
        return false;
    }

    /**
     * Used to filter the price suffix contained inside the JS sub price string.
     */
    public function removePriceSuffix()
    {
        return '';
    }

    /**
     * Returns subscription style html price string.
     */
    public function getSubscriptionPriceHtml($initial_amount = '', $recurring_amount = '', $subscription_intrval = '', $subscription_length = '', $subscription_period = '', $trial_length = '', $trial_period = '')
    {
        $product = $this->wdm_bundled_items[$this->sub_id]->product;

        if ($initial_amount === '') {
            $initial_amount = $this->getOldStylePriceHtml();
        }

        if ($recurring_amount === '') {
            add_filter('woocommerce_is_subscription', array($this, 'isolateRecurringPriceHtml'), 10);
            $this->wdm_bundled_items[$this->sub_id]->addPriceFilters();
            $recurring_amount = $product->get_price_html();
            remove_filter('woocommerce_is_subscription', array($this, 'isolateRecurringPriceHtml'), 10);
            $this->wdm_bundled_items[$this->sub_id]->removePriceFilters();
        }

        // default values to get
        $default_array = array(
                'subscription_intrval' => $product->subscription_period_interval,
                'subscription_length' => $product->subscription_period_interval,
                'subscription_period' => $product->subscription_period_interval,
                'trial_length' => $product->subscription_period_interval,
                'trial_period' => $product->subscription_period_interval
        );

        // values we get from function
        $func_value_array = array(
                'subscription_intrval' => $subscription_intrval,
                'subscription_length' => $subscription_length,
                'subscription_period' => $subscription_period,
                'trial_length' => $trial_length,
                'trial_period' => $trial_period
            );

        $merged_values = wp_parse_args($func_value_array, $default_array);

        $subscription_intrval = $merged_values['subscription_intrval'];
        $subscription_length = $merged_values['subscription_length'];
        $subscription_period = $merged_values['subscription_period'];
        $trial_length = $merged_values['trial_length'];
        $trial_period = $merged_values['trial_period'];

        // if ($subscription_intrval === '') {
        //     $subscription_intrval = $product->subscription_period_interval;
        // }

        // if ($subscription_length === '') {
        //     $subscription_length = $product->subscription_length;
        // }

        // if ($subscription_period === '') {
        //     $subscription_period = $product->subscription_period;
        // }

        // if ($trial_length === '') {
        //     $trial_length = $product->subscription_trial_length;
        // }

        // if ($trial_period === '') {
        //     $trial_period = $product->subscription_trial_period;
        // }

        $subscription_details = array(
            'initial_amount' => $initial_amount,
            'initial_description' => __('now', 'woocommerce-subscriptions'),
            'recurring_amount' => $recurring_amount,
            'subscription_interval' => $subscription_intrval,
            'subscription_length' => $subscription_length,
            'subscription_period' => $subscription_period,
            'trial_length' => $trial_length,
            'trial_period' => $trial_period,
        );

        $is_one_payment = false;
        if ($subscription_length > 0 && $subscription_length == $subscription_intrval) {
            $is_one_payment = true;
        }

        $initial_amount_str = getAmountStr($subscription_details['initial_amount']);
        $recurring_amount_str = getAmountStr($subscription_details['recurring_amount']);

        // Don't show up front fees when there is no trial period and no sign up fee and they are the same as the recurring amount
        if ($trial_length == 0 && $this->min_bundle_price == 0 && $this->max_bundle_price == 0 && $initial_amount_str == $recurring_amount_str) {
            $subscription_details['initial_amount'] = '';
        } elseif (wc_price(0) == $initial_amount_str && false === $is_one_payment) {
            $subscription_details['initial_amount'] = '';
        }

        // Include details of a synced subscription in the cart
        if (WC_Subscriptions_Synchroniser::is_product_synced($product)) {
            $subscription_details += array(
                'is_synced' => true,
                'synchronised_payment_day' => WC_Subscriptions_Synchroniser::get_products_payment_day($product),
            );
        }

        $subscription_details = apply_filters('woocommerce_bundle_subscription_string_details', $subscription_details, $args = array());

        $subscription_string = WC_Subscriptions_Manager::get_subscription_price_string($subscription_details);

        unset($args);
        return $subscription_string;
    }

    // prepare amount string
    public function getAmountStr($amount)
    {
        if (is_numeric($amount)) {
            $amount = woocommerce_price($amount);
        } else {
            $amount;
        }

        return $amount;
    }

    public function getOldStylePriceHtml($price = '')
    {
        // Get the price
        if ($this->min_bundle_price > 0) {
            if (!$this->min_bundle_price || $this->min_bundle_price !== $this->max_bundle_price || $this->contains_nyp) {
                    $price .= $this->get_price_html_from_text();
            }

            if ($this->is_on_sale() && $this->min_bundle_reg_price !== $this->min_bundle_price) :
                $price .= $this->wc_format_price_range($this->min_bundle_reg_price, $this->min_bundle_price) . $this->get_price_suffix();

                $price = apply_filters('woocommerce_bundle_sale_price_html', $price, $this);
            else :
                $price .= wdm_pb_bundles_price($this->min_bundle_price) . $this->get_price_suffix();

                $price = apply_filters('woocommerce_bundle_price_html', $price, $this);
            endif;
        } elseif ($this->min_bundle_price == 0) {
            if (!$this->min_bundle_price || $this->min_bundle_price !== $this->max_bundle_price || $this->contains_nyp) {
                    $price .= $this->get_price_html_from_text();
            }

            if ($this->is_on_sale() && isset($this->min_bundle_reg_price) && $this->min_bundle_reg_price !== $this->min_bundle_price) :
                $price .= $this->wc_format_price_range($this->min_bundle_reg_price, __('Free!', 'custom-product-boxes'));
                $price = apply_filters('woocommerce_bundle_free_sale_price_html', $price, $this);
            else :
                $price .= __('Free!', 'custom-product-boxes');
                $price = apply_filters('woocommerce_bundle_free_price_html', $price, $this);

            endif;

        } elseif ($this->min_bundle_price === '') {
            $price = apply_filters('woocommerce_bundle_empty_price_html', '', $this);
        }

        return apply_filters('woocommerce_get_price_html', $price, $this);
    }

    /**
     * True if all bundled items are in stock in the desired quantities.
     */
    public function allItemsInStock()
    {
        return $this->all_items_in_stock;
    }
}
