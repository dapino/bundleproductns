<?php

/**
 * Bundled Product Image
 * @version 3.3.0
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

global $woocommerce;

// get thumbnail title
$thumbnail_title = get_the_title(get_post_thumbnail_id($post_id));
if (isset($sld_ind)) {
    echo '<div class="images wdm-product-sold-individually">';
} else {
    echo '<div class="images">';
}
?>
<!-- <div class="blackout"></div> -->
<div class="cpb-plus-minus">
  <div class="cpb-circle">
    <div class="cpb-horizontal"></div>
    <div class="cpb-vertical"></div>
    <div class="cpb-slantline"></div>
  </div>
</div>


<div itemprop="image" class="zoom" rel="thumbnails" title="<?php echo $thumbnail_title; ?>">
<?php $image_url=get_the_post_thumbnail(
    $post_id,
    apply_filters('bundled_product_large_thumbnail_size', 'shop_thumbnail'),
    array(
    'title'    => get_the_title(get_post_thumbnail_id($post_id)),
    )
);

if (!isset($image_url) || empty($image_url)) {
    $image_url='<img width="180" height="180" src="'.wc_placeholder_img_src().'" class="attachment-shop_thumbnail size-shop_thumbnail wp-post-image" alt="poster_5_up" title="poster_5_up" sizes="(max-width: 180px) 100vw, 180px">';
    //$image_url=wc_placeholder_img_src();
}
echo $image_url;
?>
</div>
<!-- <div class = 'overlay' ></div> -->
</div>
