<?php


/**
* custom_product_boxes woocommerce 2.1 Compatibility Functions
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}


function wdm_pb_bundles_price($arg)
{
    return wc_price($arg);
}

function wdm_pb_bundles_add_notice($message, $notice_type)
{
    return wc_add_notice($message, $notice_type);
}
