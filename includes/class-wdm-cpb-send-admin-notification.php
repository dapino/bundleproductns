<?php

namespace wisdmlabs\cpb;

if (! class_exists('WdmCpbSendAdminNotification')) {
    
    class WdmCpbSendAdminNotification
    {
        public function __construct()
        {
            add_filter('woocommerce_email_content_no_stock', array($this, 'notifyAdminNoStock'), 10, 2);
        }

        public function notifyAdminNoStock($message, $product)
        {
            global $prefillManager;
            $prefillProducts = $prefillManager->getCpbProducts($product->get_id());
            if ($prefillProducts && !empty($prefillProducts)) {
                $msg = "\nThis product is also part of";
                
                $mandatory_msg = "";
                foreach ($prefillProducts as $singleProduct) {
                    if ($product->get_id() == $singleProduct['product_id']) {
                        $msg .= "\n #".$singleProduct['cpb_product_id']." - ".$singleProduct['cpb_name'].",";

                        $enable_swapping = get_post_meta($singleProduct['cpb_product_id'], 'wdm_swap_products', true);
                        if ($enable_swapping == 'yes' && !empty($singleProduct['product_mandatory']) && !($product->managing_stock() && $product->backorders_allowed())) {
                            $mandatory_msg .= "\nSince,\n".get_the_title($product->get_id())." is a mandatory product in #".$singleProduct['cpb_product_id']." - ".$singleProduct['cpb_name'].", it can not be added to cart,";
                        }
                    }
                }

                $mandatory_msg = rtrim($mandatory_msg, ',').".";
                $msg = rtrim($msg, ',');
                $message.= $msg.$mandatory_msg;
            }
            return $message;
        }
    }
}
new WdmCpbSendAdminNotification();
