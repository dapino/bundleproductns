<?php

/**
 * Bundled Item Title.
 * @version 4.2.0
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

if ($title === '') {
    return;
}

?>
 <p class="bundled_product_title product_title" style="margin-bottom: 0px" title="<?php echo $alt; ?>">
    <a href="<?php echo $href; ?>" target="_blank"><?php echo $title; ?></a>
</p> 
