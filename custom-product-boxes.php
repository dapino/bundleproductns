<?php

namespace wisdmlabs\cpb;

/**
* Plugin Name: Custom Product Boxes
* Plugin URI: https://www.wisdmlabs.com/assorted-bundles-woocommerce-custom-product-boxes-plugin/
* Description: The Custom Product Boxes is an extension for your WooCommerce store, using which, your customers will be able to select products, and create and purchase their own personalized bundles.
* Version: 2.0.0
* Author: WisdmLabs
* Author URI: http://www.wisdmlabs.com
*
* Text Domain: custom-product-boxes
* Domain Path: /languages/
*
* License: GPL
*/

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

// Define plugin constants
//constant version 
define('CPB_VERSION', '2.0.0');

function is_session_started()
{
    if (php_sapi_name() !== 'cli') {
        if (version_compare(phpversion(), '5.4.0', '>=')) {
            return session_status() === PHP_SESSION_ACTIVE ? true : false;
        } else {
            return session_id() === '' ? false : true;
        }
    }
    return false;
}

if (is_session_started() === false) {
    session_start();
}

global $wdmCpbData;
$wdmCpbData = array(
    'pluginShortName' => 'Custom Product Boxes',
    'pluginSlug' => 'custom_product_boxes',
    'pluginVersion' => CPB_VERSION,
    'pluginName' => 'Custom Product Boxes',
    'storeUrl' => 'https://wisdmlabs.com/check-update',
    'authorName' => 'WisdmLabs',
    'pluginTextDomain'  => 'custom-product-boxes'
);

include('includes/class-wdm-cpb-install.php');

//Install tables associated with plugin
register_activation_hook(__FILE__, array('wisdmlabs\cpb\WdmCpbInstall', 'createTables'));

/**
* Check if WooCommerce is active
**/
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    include_once('includes/class-wdm-add-license-data.php');
    new WdmAddLicenseData($wdmCpbData);
    if (!class_exists('WdmPluginUpdater')) {
        include('includes/class-wdm-plugin-updater.php');
    }
    $l_key = trim(get_option('edd_' . $wdmCpbData['pluginSlug'] . '_license_key'));

// setup the updater
    new WdmPluginUpdater($wdmCpbData['storeUrl'], __FILE__, array(
    'version' => $wdmCpbData['pluginVersion'], // current version number
    'license' => $l_key, // license key (used get_option above to retrieve from DB)
    'item_name' => $wdmCpbData['pluginName'], // name of this plugin
    'author' => $wdmCpbData['authorName'], //author of the plugin
    ));

    $l_key = null;

/**
* Check if WooCommerce is active
* */
    if (!class_exists('WdmProductBundleMain')) {
        class WdmProductBundleMain
        {
            public function __construct()
            {
                add_action('init', array($this, 'wdmCpbLoadTextDomain'));
                include_once('includes/class-wdm-get-license-data.php');
                global $wdmCpbData;
                $getDataFromDb = WdmGetLicenseData::getDataFromDb($wdmCpbData);
                if ($getDataFromDb == 'available') {
                    $this ->product_type = 'wdm_bundle_product';
                    add_action('plugins_loaded', array($this, 'bundlePluginsLoaded'));
                    add_action('admin_init', array($this, 'wdmActivate'));
                    add_action('wp_ajax_wdm_product_type_cpb', array($this, 'wdmProductTypeCpb'));
                    add_action('wp_ajax_nopriv_wdm_product_type_cpb', array($this, 'wdmProductTypeCpb'));

                    add_action('woocommerce_product_class', array($this,'fixProductTypeClassName'), 10, 4);

                //add_action('wp_head', array($this,'loadMediaManager'));
                }
            }

            public function pluginPath()
            {
                return untrailingslashit(plugin_dir_path(__FILE__));
            }

            public function pluginUrl()
            {
                return untrailingslashit(plugins_url('/', __FILE__));
            }
             

        // fix bundle product type classname for PSR2 standards
            public function fixProductTypeClassName($classname)
            {
                if ($classname == 'WC_Product_Wdm_bundle_product') {
                    return '\wisdmlabs\cpb\WCProductWdmBundleProduct';
                } else {
                    return $classname;
                }
            }

            // public function loadMediaManager()
            // {
            // //wp_enqueue_media();
            // }

            public function wooWdmBundlesPluginUrl()
            {
                return untrailingslashit(plugins_url('/', __FILE__));
            }

            public function bundlePluginsLoaded()
            {
                global $woocommerce;

            // Compatibility
                if (version_compare($woocommerce->version, '2.0.22') > 0) {
                    include('includes/woocommerce-21-functions.php');
                } else {
                    include('includes/woocommerce-20-functions.php');
                }
                require_once('includes/class-wdm-cpb-function.php');
                require_once('includes/class-wc-product-wdm-product-bundle.php');

                require_once('includes/class-wdm-wc-product-item.php');
                require_once('includes/class-wdm-manage-prefill-data.php');
                require_once('includes/class-wdm-cpb-send-admin-notification.php');
                require_once('admin/class-wdm-cbp-layout-setting.php');

            // Admin functions and meta-boxes
                if (is_admin()) {
                    require_once('admin/class-wdm-admin-asset.php');
                    require_once('admin/class-wdm-product-bundle-type.php');
                    $this->admin = new WdmProductBundleType();
                }

            // Cart-related bundle functions and filters
                require_once('includes/class-wdm-wc-product-cart.php');
                $this->cart = new WdmWcProductCart();

            // Front-end filters
                require_once('public/class-wdm-frontend-scripts.php');

            // Gift Message
                require_once('public/class-wdm-gift-message.php');
                $this->giftMessage = new WdmCPBGiftMessage();
                require_once('public/class-wdm-product-bundle-type-display.php');
                $this->productDisplay = new WdmProductBundleTypeDisplay();
            }

            public function wdmCpbLoadTextDomain()
            {
                load_plugin_textdomain('custom-product-boxes', false, dirname(plugin_basename(__FILE__)) . '/languages/');
            }

            public function wdmProductTypeCpb()
            {
                $act = $_POST['act'];
                $wdm_cpb = "wdm_cpb";

                if ($act == 'set') {
                    $_SESSION[$wdm_cpb] = 1;
                } else {
                    unset($_SESSION[$wdm_cpb]);
                }

                die();
            }

            public function getDesktopLayout($product_id)
            {

                $layout = get_post_meta($product_id, '_wdm_desktop_layout', true);

                // Set Vertical layout as a default layout
                if (empty($layout)) {
                    $layout = $this->pluginPath() . '/templates/product-layouts/desktop-layouts/vertical';
                }

                return apply_filters('wdm_cpb_desktop_layout', $layout, $product_id);
            }

            public function wdmActivate()
            {
                global $wpdb;

                $is_active = get_option('woocommerce_product_bundles_active', false);

                if ($is_active == false) {
                    $bundle_type_exists = false;

                    $product_type_terms = get_terms('product_type', array('hide_empty' => false));

                    foreach ($product_type_terms as $product_type_term) {
                        if ($product_type_term->slug === 'wdm_bundle_product') {
                            $bundle_type_exists = true;
                        }
                    }

                    // Check for existing 'bundle' slug and if it exists, modify it
                    if ($existing_term_id = term_exists('wdm_bundle_product')) {
                        $wpdb->update(
                            $wpdb->terms,
                            array(
                                'slug' => 'wdm_bundle_product-b'
                            ),
                            array(
                                'term_id' => $existing_term_id
                            )
                        );
                    }

                    if (! $bundle_type_exists) {
                        wp_insert_term(
                            'wdm_bundle_product', // name
                            'product_type',
                            array(
                                'description'=> 'CPB',
                                'slug' => 'wdm_bundle_product',
                            )
                        );
                    }

                    add_option('woocommerce_product_bundles_active', true);
                }
            }
        }
        $GLOBALS[ 'woo_wdm_bundle' ]  = new WdmProductBundleMain();
    }
} else {
    add_action('admin_notices', 'wisdmlabs\cpb\cpb_addon_base_plugin_inactive_notice');
}


if (! function_exists('cpb_addon_base_plugin_inactive_notice')) {
    function cpb_addon_base_plugin_inactive_notice()
    {
        if (current_user_can('activate_plugins')) {
            global $wdmCpbData;

            $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
            if (! in_array('woocommerce/woocommerce.php', $active_plugins)) {
                //deactivate_plugins(plugin_basename(__FILE__));
                ?>
                <div id="message" class="error">
                <p><?php printf(__('%s %s is inactive.%s Install and activate %sWooCommerce%s for %s to work.', 'custom-product-boxes'), '<strong>', $wdmCpbData['pluginName'], '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', $wdmCpbData['pluginName']); ?></p>
                </div>
                <?php
            }
        }
    }
}


