<?php

/**
 * Bundles WC 2.1 Compatibility Functions
 * @version 4.0.0
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}


function wc_wdm_product_bundles_get_template($file, $data, $empty, $path)
{
    return wc_get_template($file, $data, $empty, $path);
}
