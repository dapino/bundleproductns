<?php

namespace wisdmlabs\cpb;

class WdmCPBMobileListLayout extends WdmAbstractProductDisplay
{
    public function __construct()
    {
        parent::__construct();
        add_action('wdm_cpb_mobile_enqueue_styles', array($this,'enqueueStyles'));
        add_action('wdm_cpb_mobile_product_layout', array($this, 'displayAddToCartForm'));
        add_action('wdm_cpb_mobile_enqueue_scripts', array($this, 'enqueueScripts'));
    }

    public function __get($templateType)
    {
        $this->$templateType = 'product-layouts/mobile-layouts/list/' . parent::__get($templateType);
        return $this->$templateType;
    }

    public function enqueueStyles()
    {
        global $woo_wdm_bundle;
        wp_enqueue_style('wdm-cpb-mobile-list-layout-css', $woo_wdm_bundle->pluginUrl() . '/assets/css/mobile-templates/list-layout.css', array(), CPB_VERSION, self::setMobileLayoutBreakpoint());
        wp_enqueue_style('wdm-cpb-snackbar-css', $woo_wdm_bundle->pluginUrl() . '/assets/css/wdm-snackbar.css', array(), CPB_VERSION);
    }

    public function enqueueScripts()
    {
        global $woo_wdm_bundle;
        wp_enqueue_script('wdm-cpb-mobile-list-layout-js', $woo_wdm_bundle->pluginUrl() . '/assets/js/mobile-templates/list-layout.js', array('jquery'), CPB_VERSION);
        wp_enqueue_script('wdm-cpb-snackbar-js', $woo_wdm_bundle->pluginUrl() . '/assets/js/snackbar/snackbar.js', array('jquery'), CPB_VERSION);
        wp_localize_script('wdm-cpb-mobile-list-layout-js', 'mobileListLayoutParams', array(
            'enableProductsSwap' => $this->enableSwapping() === true ? 1 : 0,
            'giftboxFullMsg'    => __('Está lleno tu carrito', 'custom-product-boxes'),
            'canNotAddProduct'  => __('No se pueden agregar más %s', 'custom-product-boxes'),
            'productsAddedText' => __('Productos en el carrito', 'custom-product-boxes'),
            'totalProductPriceText' => __('Precio total', 'custom-product-boxes'),
        ));
        //wp_localize_script('wdm-add-to-cart-bundle', 'wdm_bundle_params', $params);
    }
}
