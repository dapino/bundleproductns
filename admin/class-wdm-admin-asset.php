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

if (! class_exists('WdmCPBAdminAssets')) :

    /**
     * WdmCPBAdminAssets Class.
     */
    class WdmCPBAdminAssets
    {
        /**
         * Hook in tabs.
         */
        public function __construct()
        {
            add_action('admin_enqueue_scripts', array($this, 'wdmLoadAdminCss'));
            add_action('admin_enqueue_scripts', array($this, 'wdmLoadAdminScripts'));
            add_action('admin_head-post.php', array($this, 'wdmPublishAdminHook'));
            add_action('admin_head-post-new.php', array($this, 'wdmPublishAdminHook'));
            // add_action('admin_head',            array($this, 'product_taxonomy_styles' ) );
        }

        public function wdmLoadAdminCss()
        {
            global $woo_wdm_bundle;
            $screen       = get_current_screen();
            $screen_id    = $screen ? $screen->id : '';
            if (in_array($screen_id, array('product', 'edit-product'))) {
                // Register admin styles
                wp_register_style('wdm-bundle-admin-css', $woo_wdm_bundle->wooWdmBundlesPluginUrl() . '/assets/css/bundles-admin.css', array(), CPB_VERSION);
                wp_enqueue_style('wdm-bundle-admin-css');
            }
        }

        public function wdmLoadAdminScripts()
        {
            global $post,$woo_wdm_bundle;
            $screen       = get_current_screen();
            $screen_id    = $screen ? $screen->id : '';
            // If wdm_cpb is already set for another product
            if (isset($_SESSION['wdm_cpb'])) {
                unset($_SESSION['wdm_cpb']);
            }

            if (in_array($screen_id, array('product', 'edit-product'))) {
                wp_enqueue_script('jquery');
                wp_register_script('wdm-product-type-cpb', $woo_wdm_bundle->wooWdmBundlesPluginUrl() . '/assets/js/wdm-product-type-cpb.js', array('jquery'), CPB_VERSION);
                wp_localize_script('wdm-product-type-cpb', 'ajax_object', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'product_id' => isset($post->ID) ? $post->ID : 0,
                ));
                wp_enqueue_script('wdm-product-type-cpb');
                wp_register_script('wdm-cpb-settings-js', $woo_wdm_bundle->wooWdmBundlesPluginUrl() . '/assets/js/wdm-cpb-settings.js', array('jquery'), CPB_VERSION);
            }
        }

        public function wdmPublishAdminHook()
        {
            global $post, $woo_wdm_bundle;

            if (is_admin() && $post->post_type == 'product') {
                wp_enqueue_script('wdm-publish-admin-hook', plugins_url('/assets/js/wdm-publish-admin-hook.js', dirname(__FILE__)), array('jquery'));
                wp_localize_script('wdm-publish-admin-hook', 'cpb_settings_object', array(
                    'text_box_quantity'   => __('Please enter value in "Box Quantity" field.', 'custom-product-boxes'),
                    'text_addon_products'           => __('Please enter value in "Add-On Products" field.', 'custom-product-boxes'),
                    'text_regular_price'     => __('Please enter value in "Regular Price" field.', 'custom-product-boxes'),
                    'text_sale_price'    => __('Please enter value in "Sale Price" field less than regular price.', 'custom-product-boxes')
                ));

                $enablePrefillProducts = get_post_meta(get_the_ID(), 'wdm_prefilled_box', true);
                wp_enqueue_script('wdm-cpb-function', $woo_wdm_bundle->wooWdmBundlesPluginUrl() . '/assets/js/functions.js', false);
                
                wp_enqueue_script('wdm-prefilled-products-cpb', $woo_wdm_bundle->wooWdmBundlesPluginUrl() . '/assets/js/wdm-cpb-prefilled-products.js', false);
                
                wp_localize_script('wdm-prefilled-products-cpb', 'cpb_prefilled_object', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'add_image'       => $woo_wdm_bundle->wooWdmBundlesPluginUrl() .'/assets/images/plus-icon.png',
                    'remove_image'    => $woo_wdm_bundle->wooWdmBundlesPluginUrl() .'/assets/images/minus-icon.png',
                    'enablePrefillProducts' => $enablePrefillProducts,
                    'total_prefill_qty_text' => __("Total quantity of products selected for pre filled boxes should be lesser than or equal to CPB box quantity", 'custom-product-boxes'),
                    'sld_ind_text'  => __('Quantity of product(s) sold individually cannot be more than 1. Please change the quantity of the following product(s): ', 'custom-product-boxes'),
                    'qty_greater_zero'  => __('Quantity of prefilled product should be greater than 1. Please change the quantity of products', 'custom-product-boxes'),
                ));
            }
        }
    }

endif;

return new WdmCPBAdminAssets();
