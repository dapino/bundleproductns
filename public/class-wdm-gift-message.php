<?php

namespace wisdmlabs\cpb;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WdmCPBGiftMessage')) {
    class WdmCPBGiftMessage
    {
        public function __construct()
        {
            add_action('wp_ajax_wdm_add_gift_message_session', array($this, 'addGiftMesssageSession'));
            add_action('wp_ajax_nopriv_wdm_add_gift_message_session', array($this, 'addGiftMesssageSession'));
            add_filter('woocommerce_add_cart_item_data', array($this, 'addMessageData'), 1, 2);
            add_filter('woocommerce_get_cart_item_from_session', array($this, 'getGiftMessageFromSession'), 1, 3);
            // add_filter('woocommerce_checkout_cart_item_quantity', array($this, 'checkoutGiftMessage'), 1, 3);
            // add_filter('woocommerce_cart_item_name', array($this, 'checkoutGiftMessage'), 1, 3);
            add_filter('woocommerce_get_item_data', array( $this, 'displayGiftMessage'), 10, 2);
            if (version_compare(WC_VERSION, '3.0.0', '<')) {
                add_action('woocommerce_add_order_item_meta', array( $this, 'addGiftMessageToOrderMeta'), 1, 2);
            } else {
                add_action('woocommerce_new_order_item', array( $this, 'addGiftMessageToOrderMeta'), 1, 2);
            }
            add_action('woocommerce_cart_item_removed', array($this, 'removeMessageOnProductRemove'), 1, 1);
        }

        public static function getMessageLabel($product_id)
        {
            $giftLabel = get_post_meta($product_id, '_wdm_gift_message_label', true);

            if (empty($giftLabel)) {
                $giftLabel = __('Gift Message', 'custom-product-boxes');
            }

            return apply_filters('wdm_cpb_gift_msg_label', $giftLabel);
        }

        public static function addGiftMesssagefield()
        {

            global $product;
            
            $giftLabel = self::getMessageLabel($product->get_id());
            do_action('wdm_cpb_before_gift_message');
            ?>
                <div class = "cpb_gift_msg" >
                    <label for = "cpb_gift_message" ><p class = "price">
                        <?php
                            echo $giftLabel;
                        ?></p>
                    </label>
                    <input
                        class = "cpb_gift_message"
                        type = "text"
                        name = "wdm_gift_message"
                        placeholder = "Add a gift message here"
                        data-product-id = "<?php echo $product->get_id(); ?>"
                    />              
                </div>
            <?php
            do_action('wdm_cpb_after_gift_message');
        }

        public function addGiftMesssageSession()
        {
            //Gift Message data - Sent Via AJAX post method
            
            if (!isset($_POST['product_id'])) {
                die();
            }

            $product_id = $_POST['product_id'];
            $msgData =  $_POST['msgData'];
            
            // session_start();
            $_SESSION['wdm_gift_message_'.$product_id] = $msgData;
            die();
        }

        public function addMessageData($cart_item_data, $product_id)
        {
            /*Here, We are adding item in WooCommerce session with, wdm_user_custom_data_value name*/
            // session_start();
            $enableGiftMessage = get_post_meta($product_id, '_wdm_enable_gift_message', true);
            if ($enableGiftMessage != 'yes') {
                return $cart_item_data;
            }

            if (isset($_SESSION['wdm_gift_message_'.$product_id])) {
                $giftMessage = $_SESSION['wdm_gift_message_'.$product_id];
                $newGiftMessage = array('wdm_gift_message_'.$product_id => $giftMessage);
            }

            if (empty($giftMessage)) {
                return $cart_item_data;
            } else {
                if (empty($cart_item_data)) {
                    return $newGiftMessage;
                } else {
                    return array_merge($cart_item_data, $newGiftMessage);
                }
            }
            unset($_SESSION['wdm_gift_message_'.$product_id]);
        }

        public function getGiftMessageFromSession($item, $values, $key)
        {
            $product_id = $item['product_id'];
            $enableGiftMessage = get_post_meta($product_id, '_wdm_enable_gift_message', true);
            if ($enableGiftMessage != 'yes') {
                return $item;
            }

            if (array_key_exists('wdm_gift_message_'.$product_id, $values)) {
                $item['wdm_gift_message_'.$product_id] = $values['wdm_gift_message_'.$product_id];
            }
            unset($key); // unsed while pushing to git
            return $item;
        }

        public function checkoutGiftMessage($product_name, $values, $cart_item_key)
        {
            unset($cart_item_key); // unsed while pushing to git
            $product_id = $values['product_id'];
            $enableGiftMessage = get_post_meta($product_id, '_wdm_enable_gift_message', true);
            if ($enableGiftMessage != 'yes') {
                return $product_name;
            }


            if (!isset($values['wdm_gift_message_'.$product_id])) {
                return $product_name;
            }

            if (count($values['wdm_gift_message_'.$product_id]) > 0) {
                $giftLabel = self::getMessageLabel($product_id);
                $return_string = $product_name . "</a><dl class='variation'>";
                $return_string .= "<p class = 'msg_title'>" .$giftLabel.": </p><p class = 'msg_cart'>". $values['wdm_gift_message_'.$product_id] . "</p>";
                $return_string .= "</dl>";
                return $return_string;
            } else {
                return $product_name;
            }
        }

        public function addGiftMessageToOrderMeta($item_id, $values)
        {
            $product_id = $values['product_id'];
            $enableGiftMessage = get_post_meta($product_id, '_wdm_enable_gift_message', true);
            if ($enableGiftMessage != 'yes') {
                return;
            }

            $giftLabel = self::getMessageLabel($product_id);
            if (version_compare(WC_VERSION, '3.0.0', '<')) {
                $user_custom_values = isset($values['wdm_gift_message_'.$product_id]) ? $values['wdm_gift_message_'.$product_id] : '';
            } else {
                $user_custom_values = isset($values->legacy_values['wdm_gift_message_'.$product_id]) ? $values->legacy_values['wdm_gift_message_'.$product_id] : '';
            }

            if (!empty($user_custom_values)) {
                wc_add_order_item_meta($item_id, $giftLabel, $user_custom_values);
            }
        }

        public function displayGiftMessage($data, $cartItem)
        {
            // global $woocommerce;
            $product_id = $cartItem['product_id'];
            if (isset($cartItem['wdm_gift_message_'.$product_id])) {
                $enableGiftMessage = get_post_meta($product_id, '_wdm_enable_gift_message', true);
                if ($enableGiftMessage != 'yes') {
                    return $data;
                }
                $giftLabel = self::getMessageLabel($product_id);
                $value = $cartItem['wdm_gift_message_'.$product_id];
               
                $data[] = array(
                'name' => $giftLabel,
                'value' => $value
                );
            }
            
            return $data;
        }

        public function removeMessageOnProductRemove($cart_item_key)
        {
            global $woocommerce;
            // Get cart
            $cart = $woocommerce->cart->get_cart();
            // For each item in cart, if item is upsell of deleted product, delete it
            foreach ($cart as $key => $values) {
                $product_id = $values['product_id'];
                if (isset($values['wdm_gift_message_'.$product_id]) && $values['wdm_gift_message_'.$product_id] == $cart_item_key) {
                    unset($woocommerce->cart->cart_contents[ $key ]);
                }
            }
        }
    }
}