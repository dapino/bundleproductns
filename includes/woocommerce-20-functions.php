<?php


/**
 * custom_product_boxes woocommerce 2.0 Compatibility Functions
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

function wdm_pb_bundles_price($arg)
{
    return woocommerce_price($arg);
}

function wdm_pb_bundles_add_notice($message, $notice_type)
{
    global $woocommerce;

    if ($notice_type == 'success' || $notice_type == 'notice') {
        return $woocommerce->add_message($message);
    } elseif ($notice_type == 'error') {
        return $woocommerce->add_error($message);
    }
}
