<?php
/**
 * Load assets
 *
 * @author      WisdmLabs
 * @version     2.0.0
 */

namespace wisdmlabs\cpb;

if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('WdmCPBFrontendAssets')) :

    /**
     * WdmCPBFrontendAssets Class.
     */
    class WdmCPBFrontendAssets
    {
        /**
         * Hook in tabs.
         */
        public function __construct()
        {
            add_action('wp_enqueue_scripts', array($this, 'wdmFrontendScripts'), 100);
            add_action('wp_enqueue_scripts', array($this, 'wdmFrontendStyles'), 100);
        }

        public function wdmFrontendStyles()
        {
            global $woo_wdm_bundle;
            if (is_singular('product')) {
                $product = wc_get_product(get_the_ID());
                if ($product->get_type() == 'wdm_bundle_product') {
                    wp_register_style('wdm-bundle-css', $woo_wdm_bundle->wooWdmBundlesPluginUrl() . '/assets/css/bundles-frontend.css', false);
                    wp_register_style('wdm-cpb-style', $woo_wdm_bundle->wooWdmBundlesPluginUrl() . '/assets/css/bundles-style.css', false);
                    wp_enqueue_style('wdm-cpb-style');
                    if (function_exists('storefront_is_woocommerce_activated')) {
                        $singleProductCss = "
                            .single-product div.product {
                                overflow: visible !important;
                            }
                        ";
                        wp_add_inline_style('wdm-cpb-style', $singleProductCss);
                    }
                }
            }
            if (is_cart()) {
                wp_register_style('wdm-cpb-cart-css', $woo_wdm_bundle->wooWdmBundlesPluginUrl() . '/assets/css/bundles-cart.css', array(), false);
                wp_enqueue_style('wdm-cpb-mobile-cart-css', $woo_wdm_bundle->pluginUrl() . '/assets/css/mobile-templates/bundles-mobile-cart.css', array(), CPB_VERSION, WdmAbstractProductDisplay::setMobileLayoutBreakpoint());
                wp_enqueue_style('wdm-cpb-cart-css');
            }
        }

        public function wdmFrontendScripts()
        {
            global $woo_wdm_bundle, $woocommerce;

            if (is_singular('product')) {
                $product = wc_get_product(get_the_ID());
                if ($product->get_type() == 'wdm_bundle_product') {
                    wp_register_script('wdm-functions-js', $woo_wdm_bundle->wooWdmBundlesPluginUrl() . '/assets/js/functions.js', array('jquery', 'wc-add-to-cart-variation', 'wdm-add-to-cart-bundle'), false);
                    wp_enqueue_script('wdm-functions-js');
                    wp_register_script('wdm-add-to-cart-bundle', $woo_wdm_bundle->wooWdmBundlesPluginUrl() . '/assets/js/add-to-cart-bundle.js', array('jquery', 'wc-add-to-cart-variation'), false);
                    wp_register_script('wdm-prefill-boxes-js', $woo_wdm_bundle->wooWdmBundlesPluginUrl() . '/assets/js/wdm-prefill-boxes-handler.js', array('jquery', 'wc-add-to-cart-variation', 'wdm-add-to-cart-bundle'), false);
                    wp_register_script('wdm-add-to-cart-js', $woo_wdm_bundle->wooWdmBundlesPluginUrl() . '/assets/js/add-to-cart.js', array(), false);
                    wp_enqueue_script('wdm-add-to-cart-js');
                    
                    wp_register_script('wdm-sticky-scroll', $woo_wdm_bundle->wooWdmBundlesPluginUrl() . '/assets/js/jquery-stickyscroll.js', array(), false);
                    wp_enqueue_script('wdm-sticky-scroll');
                    

                    wp_register_script('wdm-scroll-lock-js', $woo_wdm_bundle->wooWdmBundlesPluginUrl() . '/assets/js/wdm-custom-scrolling.js', array('jquery', 'wc-add-to-cart-variation', 'wdm-add-to-cart-bundle'), false);

                    wp_register_script('wdm-product-div-height-js', $woo_wdm_bundle->wooWdmBundlesPluginUrl() . '/assets/js/wdm-cpb-product-height.js', array('jquery', 'wc-add-to-cart-variation', 'wdm-add-to-cart-bundle'), false);

                    wp_register_script('wdm-cpb-product-layout', $woo_wdm_bundle->wooWdmBundlesPluginUrl() . '/assets/js/wdm-cpb-product-layout.js', array('jquery', 'wc-add-to-cart-variation', 'wdm-add-to-cart-bundle'), false);
                    wp_enqueue_script('wdm-cpb-product-layout');

                    $layout = $woo_wdm_bundle->getDesktopLayout(get_the_ID());
                    
                    $selectedLayout = basename($layout);

                    wp_localize_script(
                        'wdm-cpb-product-layout',
                        'wdm_cpb_layout',
                        array(
                            'selectedLayout' => $selectedLayout,
                        )
                    );

                    wp_localize_script(
                        'wdm-scroll-lock-js',
                        'wdm_cpb_scroll',
                        array(
                            'selectedLayout' => $selectedLayout,
                        )
                    );

                    if (get_post_meta(get_the_ID(), '_per_product_pricing_active', true) == 'yes') {
                        $per_product_pricing = true;
                    } else {
                        $per_product_pricing = false;
                    }

                    $dynamic_price_enable = 'no';
                    if (!empty($per_product_pricing) && $per_product_pricing) {
                        $dynamic_price_enable = 'yes';
                    }

                    $cpb_sale_price = $product->get_sale_price();
                    $cpb_price = $product->get_price();
                    $wdm_bundle_on_sale = false;
                    $product_price = $cpb_price;
                    if (!empty($cpb_sale_price) && $cpb_sale_price > 0 && $cpb_price > 0) {
                        $wdm_bundle_on_sale = true;
                        $product_price = $cpb_sale_price;
                    }

                    $box_quantity = get_post_meta(get_the_ID(), '_wdm_grid_field', true);


                    $params = array(
                        'i18n_free' => __('Free!', 'custom-product-boxes'),
                        'i18n_total' => __('Total', 'custom-product-boxes') . ': ',
                        'i18n_partially_out_of_stock' => __('Out of stock', 'custom-product-boxes'),
                        'i18n_partially_on_backorder' => __('Available on backorder', 'custom-product-boxes'),
                        'currency_symbol' => get_woocommerce_currency_symbol(),
                        'currency_position' => esc_attr(stripslashes(get_option('woocommerce_currency_pos'))),
                        'currency_format_num_decimals' => absint(get_option('woocommerce_price_num_decimals')),
                        'currency_format_decimal_sep' => esc_attr(stripslashes(get_option('woocommerce_price_decimal_sep'))),
                        'currency_format_thousand_sep' => esc_attr(stripslashes(get_option('woocommerce_price_thousand_sep'))),
                        'currency_format_trim_zeros' => false == apply_filters('woocommerce_price_trim_zeros', false) ? 'no' : 'yes',
                        'dynamic_pricing_enable' => $dynamic_price_enable,
                        'wdm_bundle_on_sale' => $wdm_bundle_on_sale,
                        'product_thumb_size' => get_option('shop_thumbnail_image_size'),
                        'box_quantity'  => $box_quantity,
                        'cpb_product_id' => get_the_ID(),
                        'woocommerce_version'   => $woocommerce->version,
                        'product_price' => $product_price,
                        'giftboxFullMsg'    => __('Gift Box is full.', 'custom-product-boxes'),
                    );

                    wp_localize_script('wdm-add-to-cart-bundle', 'wdm_bundle_params', $params);
                    wp_localize_script('wdm-functions-js', 'wdm_bundle_params', $params);

                    wp_localize_script(
                        'wdm-add-to-cart-js',
                        'wdm_add_to_cart',
                        array(
                            'ajax_url'      => admin_url('admin-ajax.php'),
                            'quantity_text' => __("Quantity exceeds the maximum quantity of certain products in the box. Kindly reduce the number of Boxes selected.", "custom-product-boxes"),
                            'fill_box_text' => __("Kindly fill the box before adding it to the cart.", "custom-product-boxes"),
                            'check_bundle_validation'   => get_post_meta(get_the_ID(), 'wdm_boxes_selection', true),
                            'enableGiftMessage'         => get_post_meta(get_the_ID(), '_wdm_enable_gift_message', true),
                        )
                    );
                }
            }
        }
    }

endif;

return new WdmCPBFrontendAssets();
