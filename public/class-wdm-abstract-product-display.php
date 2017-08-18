<?php
namespace wisdmlabs\cpb;

class WdmAbstractProductDisplay
{
    protected $priceMessageType = 'html';

    public function __construct()
    {
        add_action('wdm_cpb_before_template_starts', array($this, 'addTemplateHooksActions'));
    }


    public function addTemplateHooksActions()
    {
        global $product;

        if (is_singular('product') && $product->get_type() == 'wdm_bundle_product') {
            remove_all_actions('wdm_cpb_remove_wc_product_display_hooks');
            remove_all_actions('wdm_cpb_main_product_info');
            remove_all_actions('wdm_cpb_add_to_cart_form');
            remove_all_actions('wdm_add_on_product_image');
            remove_all_actions('wdm_add_on_product_title');
            remove_all_actions('wdm_add_on_product_quantity');
            remove_all_actions('wdm_cpb_add_to_cart_button');

            
            add_action('wdm_cpb_remove_wc_product_display_hooks', array($this, 'removeHooks'));
            add_action('wdm_cpb_main_product_info', array($this, 'addOutOfStockNotice'));
            add_action('wdm_cpb_main_product_info', array($this, 'displayMainProductInfo'));
            add_action('wdm_cpb_add_to_cart_form', array($this, 'displayAddToCartForm'));
            add_action('wdm_add_on_product_image', array($this, 'displayAddOnProductImage'), 10, 1);
            add_action('wdm_add_on_product_title', array($this, 'displayAddOnProductTitle'), 10, 2);
            add_action('wdm_add_on_product_quantity', array($this, 'displayAddOnProductQuantity'), 10, 1);
            add_action('wdm_cpb_add_to_cart_button', array($this, 'displayCpbProductAddToCart'), 10, 1);
        }
    }

    public static function setMobileLayoutBreakpoint()
    {
        return apply_filters('wdm_cpb_mobile_layout_breakpoint', 'only screen and (max-width: 760px),
(min-device-width: 768px) and (max-device-width: 1024px)');
    }

    public function __get($templateType)
    {
        switch ($templateType) {
            case 'add_on_product_display_template':
                return $this->add_on_product_display_template = 'single-product/wdm-add-on-product-display.php';
                break;
            case 'add_on_product_title_template':
                return $this->add_on_product_title_template = 'single-product/wdm-bundled-item-title.php';
                break;
            case 'add_on_product_description_template':
                return $this->add_on_product_description_template = 'single-product/wdm-bundled-item-description.php';
                break;
            case 'add_on_product_quantity_template':
                return $this->add_on_product_quantity_template = 'single-product/wdm-bundle-item-quantity.php';
                break;
            case 'add_on_product_image_template':
                return $this->add_on_product_image_template = 'single-product/wdm-bundled-item-image.php';
                break;
            case 'single_product_add_to_cart_template':
                return $this->single_product_add_to_cart_template = 'single-product/add-to-cart/wdm-add-to-cart-button.php';
                break;
        }
    }

    public function displayAddToCartForm()
    {
        global $product;
        $wdm_bundled_items = array();
        $check_order_by_date = get_post_meta(get_the_ID(), 'wdm_order_by_date', true);
        $check_out_of_stock_setting = get_option('woocommerce_hide_out_of_stock_items');
        
        if ($check_order_by_date == 'yes') {
            $wdm_bundled_items = $product->getWdmSortedCustomBundledItems();
        } else {
            $wdm_bundled_items = $product->getWdmCustomBundledItems();
        }

        if (!is_array($wdm_bundled_items)) {
            return;
        }

        foreach ($wdm_bundled_items as $bundled_item) {
            $singleProduct = $bundled_item->product;
            
            //unused while pushing on git
            // $stck_sts = get_post_meta($bundled_item_id, '_stock_status', true);
           
            // $stckMessage = $this->getStockMessage($singleProduct);
            
            if ($singleProduct->is_in_stock() || ($check_out_of_stock_setting == "no" && !$singleProduct->is_in_stock())) {
                $this->displaySingleProduct($bundled_item);
            }
        }

        $this->displayGiftMessage();
    }

    public function displayGiftMessage()
    {
        global $product;
        $enableGiftMessage = get_post_meta($product->get_id(), '_wdm_enable_gift_message', true);

        if ($enableGiftMessage != 'yes') {
            return;
        }

        // Gift Message field
        add_action('wdm_cpb_before_add_to_cart_button', array('wisdmlabs\cpb\WdmCPBGiftMessage', 'addGiftMesssagefield'), 10);
    }

    public function displaySingleProduct($bundled_item)
    {
        global $product;
        $per_product_pricing = $product->per_product_pricing;
        $bundled_item_id = $bundled_item->item_id;
        $stckMessage = $this->getStockMessage($bundled_item->product);
        $post_status = get_post_status($bundled_item_id);
        if ($post_status == 'publish' || $post_status == 'draft') {
            wc_get_template(
                $this->add_on_product_display_template,
                array(
                        'bundled_item'          => $bundled_item,
                        'stckMessage'           => $stckMessage,
                        'per_product_pricing'   => $per_product_pricing,
                        'canBeSwapped'          => $this->canBeSwapped(),
                    ),
                '',
                plugin_dir_path(dirname(__FILE__)) . "templates/"
            );
        }
    }

    public function displayAddOnProductImage($bundled_item_id)
    {
        $sld_ind = get_post_meta($bundled_item_id, '_sold_individually', true);
       
        if ($sld_ind == 'yes') {
            wc_get_template(
                $this->add_on_product_image_template,
                array(
                    'post_id' => $bundled_item_id,
                    'sld_ind' => $sld_ind,
                ),
                '',
                plugin_dir_path(dirname(__FILE__)) . "templates/"
            );
        } else {
            wc_get_template(
                $this->add_on_product_image_template,
                array(
                    'post_id' => $bundled_item_id
                ),
                '',
                plugin_dir_path(dirname(__FILE__)) . "templates/"
            );
        }
    }

    public function displayAddOnProductTitle($bundled_item_title, $bundle_item)
    {

        // if (is_singular('product')) {
        wc_get_template(
            $this->add_on_product_title_template,
            array(
                'title' => $this->getShortTitle($bundled_item_title),
                'alt' => $bundled_item_title,
                'href' => get_permalink($bundle_item->item_id)
            ),
            '',
            plugin_dir_path(dirname(__FILE__)) . "templates/"
        );
    }

    public function displayAddOnProductQuantity($addOnItem)
    {
        wc_get_template(
            $this->add_on_product_quantity_template,
            array(
                'addOnItem' => $addOnItem,
            ),
            '',
            plugin_dir_path(dirname(__FILE__)) . "templates/"
        );
    }

    public function displayCpbProductAddToCart()
    {
        global $product, $post;
        $disabled = "";
        if ($this->canBeSwapped()) {
            $disabled = "disabled";
        }

        wc_get_template(
            $this->single_product_add_to_cart_template,
            array(
                'post_id'               => $post->ID,
                'product'               => $product,
                'disabled'              => $disabled
            ),
            '',
            plugin_dir_path(dirname(__FILE__)) . "templates/"
        );
    }

    public function removeHooks()
    {
        //removed actions and added later to add single product display at product image
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50);
        //removed filter only for this product to remove product image
        remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
        remove_action('woocommerce_before_single_product_summary', array($this, 'woocommerce_show_product_sale_flash'), 10);
    }


    public function displayMainProductInfo()
    {
        woocommerce_template_single_title();
        woocommerce_template_single_rating();
        woocommerce_template_single_excerpt();
        woocommerce_template_single_add_to_cart();
        woocommerce_template_single_meta();
        woocommerce_template_single_sharing();
        woocommerce_template_single_price();
        add_action('wdm_product_price_html', 'woocommerce_template_single_price', 10);
    }

    protected function allowPrefillProducts()
    {
        $allowPrefillProducts = get_post_meta(get_the_ID(), 'wdm_prefilled_box', true);
        if ($allowPrefillProducts == 'yes') {
            return true;
        }
        return false;
    }

    public function addOutOfStockNotice()
    {
        if ($productId = $this->canBeSwapped()) {
            wc_print_notice(sprintf(__('This Custom Box cannot be purchased as mandatory product #%d - %s has run out of stock.', 'custom-product-boxes'), $productId, get_the_title($productId)), 'error');
        }
    }

    public function getShortTitle($title)
    {
        if (strlen($title) > 20) {
            mb_internal_encoding("UTF-8");
            $title = mb_substr($title, 0, 20) . '...';  // change 50 to the number of characters you want to show
        }
        return $title;
    }

    protected function checkInventoryStatus($singleProduct)
    {
        $status = true;

        $prefillProduct = new \WC_Product($singleProduct['product_id']);

        if (!$prefillProduct->is_purchasable()) {
            $status = false;
        }

        if (!$prefillProduct->is_in_stock()) {
            $status = false;
        }
        
        if (!$prefillProduct->has_enough_stock($singleProduct['product_qty'])) {
            $status = false;
        }

        return $status;
    }

    public function getStockMessage($singleProduct)
    {
        $availabilityText = array();
        if (! $singleProduct->is_in_stock()) {
            $message = __('Out of stock', 'custom-product-boxes');
            $availabilityText = $this->setAvailabilityInfo(
                $this->priceMessageType == 'html' ? $this->getHtmlPriceMessage($message, array('stock_warning', 'wdm_stock')) : $message,
                '',
                'wdm-no-stock'
            );
        } elseif ($singleProduct->is_in_stock() && $singleProduct->is_sold_individually()) {
            $message = __('Only 1 allowed per order', 'custom-product-boxes');

            if ($singleProduct->is_on_backorder(1) && $singleProduct->backorders_require_notification()) {
                $message .= __(' (Available on backorder)', 'custom-product-boxes');
            }

            $availabilityText = $this->setAvailabilityInfo(
                $this->priceMessageType == 'html' ? $this->getHtmlPriceMessage($message) : $message,
                ''
            );
        } elseif ($singleProduct->managing_stock() && $singleProduct->is_on_backorder(1)) {
            $message = $singleProduct->backorders_require_notification() ? __('Available on backorder', 'custom-product-boxes') : __('In stock', 'custom-product-boxes');
            $availabilityText = $this->setAvailabilityInfo(
                $this->priceMessageType == 'html' ? $this->getHtmlPriceMessage($message) : $message
            );
        } elseif ($singleProduct->managing_stock()) {
            $availabilityText = $this->stockFormat($singleProduct);
        } else {
            $message = __('In stock', 'custom-product-boxes');
            $availabilityText = $this->setAvailabilityInfo(
                $this->priceMessageType == 'html' ? $this->getHtmlPriceMessage($message) : $message,
                ''
            );
        }

        return apply_filters('wdm_get_availability_text', $availabilityText, $singleProduct);
    }

    protected function stockFormat($singleProduct)
    {
        switch (get_option('woocommerce_stock_format')) {
            case 'no_amount':
                $backorderClass = '';
                $message = __('In stock', 'custom-product-boxes');
                if ($singleProduct->backorders_allowed() && !$singleProduct->backorders_require_notification()) {
                    $backorderClass = 'allow_notify';
                }
                $availabilityText = $this->setAvailabilityInfo(
                    $this->priceMessageType == 'html' ? $this->getHtmlPriceMessage($message) : $message,
                    $backorderClass
                );
                break;
            case 'low_amount':
                $backorderClass = '';
                $message = __('In stock', 'custom-product-boxes');
                if ($singleProduct->get_stock_quantity() <= get_option('woocommerce_notify_low_stock_amount')) {
                    $message = sprintf(__('Only %s left in stock', 'custom-product-boxes'), $singleProduct->get_stock_quantity());

                    if ($singleProduct->backorders_allowed() && $singleProduct->backorders_require_notification()) {
                        $backorderClass = 'allow_notify';
                        $message .= __(' (also available on backorder)', 'custom-product-boxes');
                    } elseif ($singleProduct->backorders_allowed() && !$singleProduct->backorders_require_notification()) {
                        $backorderClass = "allow_notify";
                    }
                }
                $availabilityText = $this->setAvailabilityInfo(
                    $this->priceMessageType == 'html' ? $this->getHtmlPriceMessage($message) : $message,
                    $backorderClass
                );
                break;
            default:
                $backorderClass = '';
                $message = sprintf(__('%s in stock', 'custom-product-boxes'), $singleProduct->get_stock_quantity());

                if ($singleProduct->backorders_allowed() && $singleProduct->backorders_require_notification()) {
                    $backorderClass = "allow_notify";
                    $message .= __(' (also available on backorder)', 'custom-product-boxes');
                } elseif ($singleProduct->backorders_allowed() && !$singleProduct->backorders_require_notification()) {
                    $backorderClass = "allow_notify";
                }
                $availabilityText = $this->setAvailabilityInfo(
                    $this->priceMessageType == 'html' ? $this->getHtmlPriceMessage($message) : $message,
                    $backorderClass
                );
                break;
        }
        return $availabilityText;
    }

    protected function getHtmlPriceMessage($message, $classes = array('wdm_stock', 'stock'))
    {
        if (is_array($classes) && !empty($classes)) {
            $classes = implode(' ', $classes);
            $message = "<p class='{$classes}'>$message</p>";
        }
        return $message;
    }

    protected function setAvailabilityInfo($priceMessage, $backorderClass = 'allow_notify', $stockStatus = '')
    {
        return array(
            'wdm_no_stock'      =>  $stockStatus,
            'backorderClass'    =>  $backorderClass,
            'price_message'     =>  $priceMessage,
        );
    }

    protected function enableScroll()
    {
        $enable_scroll = get_post_meta(get_the_ID(), 'wdm_disable_scroll', true);
        if ($enable_scroll == 'yes') {
            return true;
        }
        return false;
    }

    public function enableSwapping()
    {
        $enable_swapping = get_post_meta(get_the_ID(), 'wdm_swap_products', true);
        if ($enable_swapping == 'yes') {
            return true;
        }
        return false;
    }

    /**
     * [canBeSwapped description]
     * @return [type] [description]
     */
    public function canBeSwapped()
    {
        global $post, $prefillManager;
        $oosFlag = false;
        $productId = "";
        $prefillProducts = $prefillManager->getPrefilledProducts($post->ID);

        foreach ($prefillProducts as $singleProduct) {
            // $stckSts = '';
            if (!empty($singleProduct['product_mandatory']) && !$this->checkInventoryStatus($singleProduct)) {
                $oosFlag = true;
                $productId = $singleProduct['product_id'];
                break;
            }
        }

        if (!$this->enableSwapping() && $oosFlag) {
            return $productId;
        }
        return false;
    }

    protected function addPrefilledProduct($singleProduct, $position)
    {

        $mainProductId = get_the_ID();

        for ($pre = 1; $pre <= $singleProduct['product_qty']; $pre++) {
            $classes = array();
            // Mandatory Removable Product (Because it is out of Stock)
            if (!empty($singleProduct['product_mandatory']) && !$this->checkInventoryStatus($singleProduct)) {
                continue;
                //$classes = array('wdm-prefill-out-stock');
            } elseif (!empty($singleProduct['product_mandatory'])) { // Mandatory Prefilled Product
                $classes = array('wdm-prefill-mandatory');
            }

            $this->displaySinglePrefilledProduct($singleProduct['product_id'], $mainProductId, $position, $classes);
            $position++;
        }
        return $position;
    }

    protected function getProductImage($productId)
    {

        $image = get_the_post_thumbnail(
            $productId,
            apply_filters('bundled_product_large_thumbnail_size', 'shop_thumbnail'),
            array(
            'title'    => get_the_title(get_post_thumbnail_id($productId)),
            )
        );
        if (!isset($image) || empty($image)) {
            $image='<img width="180" height="180" src="'.wc_placeholder_img_src().'" class="attachment-shop_thumbnail size-shop_thumbnail wp-post-image" alt="poster_5_up" title="poster_5_up" sizes="(max-width: 180px) 100vw, 180px">';
        }

        return $image;
    }

    protected function displaySinglePrefilledProduct($prefillProductId, $mainProductId, $position, $classes = array())
    {

        $preProduct = new \WC_Product($prefillProductId);
        
        $prePrice = $preProduct->get_price();
        $preName  = $preProduct->get_name();

        $preCategories = get_the_terms( $prefillProductId, 'product_cat' );
        foreach ($preCategories as $term) {
            $preCatId = $term->term_id;
            break;
        }

        if (is_array($classes)) {
            $classes = implode(' ', $classes);
        }

        $classes .= " wdm-prefill-product wdm_box_item wdm_added_image_{$position} wdm_filled_product_{$prefillProductId}";

        $columnField = $this->getColumnFieldMetaKeyName($mainProductId);
        $columnData = get_post_meta($mainProductId, $columnField, true);

        ?>
        <div id = "wdm_bundle_bundle_item_<?php echo $position; ?>" 
             class = "wdm-product-added wdm-bundle-single-product <?php echo $columnData ?>"
        >
            <div class = "wdm-bundle-box-product">
                <div    class="<?php echo $classes; ?>" 
                        data-bundled-item-id="<?php echo $prefillProductId; ?>" 
                        data-product-cat-id="<?php echo $preCatId; ?>"
                        data-bundle-id="<?php echo $mainProductId; ?>"
                        data-product-price="<?php echo $prePrice; ?>" 
                        data-product-name="<?php echo $preName; ?>"
                >
                    <div class="cpb-plus-minus">
                        <div class="cpb-circle">
                            <div class="cpb-horizontal"></div>
                            <div class="cpb-slantline"></div>
                        </div>
                    </div>
                    <?php echo $this->getProductImage($prefillProductId); ?>
                </div>
                <p class="bundled_product_title product_title"><?php echo $prefillProductId; ?><?php echo $preName; ?><?php echo get_the_title($prefillProductId); ?></p>
            </div>
        </div>
        <?php
    }

    protected function getColumnFieldMetaKeyName($mainProductId)
    {
        global $woo_wdm_bundle;
        $layout = $woo_wdm_bundle->getDesktopLayout($mainProductId);
        $selectedLayout = basename($layout);
        $gridField = array(
            'vertical' => '_wdm_column_field',
            'horizontal' => '_wdm_item_field',
        );

        return apply_filters('wdm_columns_gift_layout', $gridField[$selectedLayout]);
    }
}