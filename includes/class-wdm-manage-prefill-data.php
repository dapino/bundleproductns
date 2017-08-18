<?php

namespace wisdmlabs\cpb;

if (! class_exists('WdmManagePrefillProducts')) {
    
    class WdmManagePrefillProducts
    {

        public $prefillProductTable;

        /**
         * @var Singleton The reference to *Singleton* instance of this class
         */
        private static $instance;
        public $errors;

        /**
         * Returns the *Singleton* instance of this class.
         *
         * @return Singleton The *Singleton* instance.
         */
        public static function getInstance()
        {
            if (null === static::$instance) {
                static::$instance = new static();
            }

            return static::$instance;
        }

        /**
         * Protected constructor to prevent creating a new instance of the
         * *Singleton* via the `new` operator from outside of this class.
         */
        protected function __construct()
        {
            global $wpdb;
            $this->prefillProductTable = $wpdb->prefix . 'cpb_prefilled_products_data';
        }

        public function addError($message)
        {
            $this->errors .= $message;
        }

        /**
         * [getAllPrefilledProducts Retrives all pre-filled products information for all CPB products]
         * @return [array] [if CPB products contain pre-filled products then returns an array of prefilled products information else empty array]
         */
        public function getAllPrefilledProducts()
        {
            global $wpdb;

            $productsList = array();
            $prefillResult = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->prefillProductTable}"));

            if ($prefillResult) {
                $key = 0;
                foreach ($prefillResult as $singleResult) {
                    $productsList[$key]['cpb_product_id'] = $singleResult->cpb_product_id;
                    $productsList[$key]['product_id'] = $singleResult->prefill_product_id;
                    $productsList[$key]['product_qty'] = $singleResult->prefill_quantity;
                    $productsList[$key]['product_mandatory'] = $singleResult->prefill_mandatory;
                    $key++;
                }
            }

            if (! empty($productsList)) {
                $productsList = array_filter($productsList);
            }

            return $productsList;
        }

        /**
         * [getCpbProducts Retrives information of the CPB product to which the pre-filled product belongs]
         * @param  [int] $productId [pre-filled product id]
         * @return [array] [if product id belongs to any CPB product then returns array containing information about that CPB product else empty array]
         */
        public function getCpbProducts($productId)
        {
            global $wpdb;

            $productsList = array();
            if (get_post_status($productId) == false) { //deleted product sync
                $wpdb->delete(
                    $this->prefillProductTable,
                    array(
                        'prefill_product_id'    => $productId,
                    ),
                    array(
                        '%d'
                    )
                );
            }

            $prefillResult = $wpdb->get_results($wpdb->prepare("SELECT cpb_product_id, prefill_quantity, prefill_mandatory FROM {$this->prefillProductTable} WHERE prefill_product_id = %d", $productId));

            if ($prefillResult) {
                $key = 0;
                foreach ($prefillResult as $singleResult) {
                    $productsList[$key]['product_id'] = $productId;
                    $productsList[$key]['cpb_product_id'] = $singleResult->cpb_product_id;
                    $productsList[$key]['product_qty'] = $singleResult->prefill_quantity;
                    $productsList[$key]['product_mandatory'] = $singleResult->prefill_mandatory;
                    $productsList[$key]['cpb_name'] = get_the_title($singleResult->cpb_product_id);
                    $key++;
                }
            }

            if (! empty($productsList)) {
                $productsList = array_filter($productsList);
            }

            return $productsList;
        }

        /**
         * [getPrefilledProducts Retrives information for all pre-filled products for a particular CPB product]
         * @param  [int] $cpbId [CPB product id]
         * @return [array] [if CPB product contains pre-filled products then returns an array of prefilled products information else empty array]
         */
        public function getPrefilledProducts($cpbId)
        {
            global $wpdb;

            $productsList = array();
            $prefillResult = $wpdb->get_results($wpdb->prepare("SELECT id, prefill_product_id, prefill_quantity, prefill_mandatory FROM {$this->prefillProductTable} WHERE cpb_product_id = %d", $cpbId));

            if ($prefillResult) {
                $key = 0;
                foreach ($prefillResult as $singleResult) {
                    if (get_post_status($singleResult->prefill_product_id) != false) {
                        $productsList[$key]['product_id'] = $singleResult->prefill_product_id;
                        $productsList[$key]['product_qty'] = $singleResult->prefill_quantity;
                        $productsList[$key]['product_mandatory'] = $singleResult->prefill_mandatory;
                        $key++;
                    } else {
                        $wpdb->delete($this->prefillProductTable, array(
                            'cpb_product_id'        => $cpbId,
                            'prefill_product_id'    => $singleResult->prefill_product_id
                        ), array(
                            '%d',
                            '%d',
                        ));
                    }
                }
            }

            if (! empty($productsList)) {
                $productsList = array_filter($productsList);
            }

            return $productsList;
        }

        /**
         * [getPrefilledProductIds Retrives pre-filled product ids for a particular CPB product ]
         * @param  [int] $cpbId [CPB product id]
         * @return [array]        [if CPB product contains pre-filled products then returns an array containing ids of prefilled products else empty array]
         */
        public function getPrefilledProductIds($cpbId)
        {
            global $wpdb;

            $productsIds = array();
            $prefillResult = $wpdb->get_results($wpdb->prepare("SELECT prefill_product_id FROM {$this->prefillProductTable} WHERE cpb_product_id = %d", $cpbId));

            if ($prefillResult) {
                foreach ($prefillResult as $singleResult => $value) {
                    unset($singleResult);
                    if (get_post_status($value->prefill_product_id) != false) {
                        $productsIds[] = $value->prefill_product_id;
                    } else {
                        $wpdb->delete(
                            $this->prefillProductTable,
                            array(
                                'cpb_product_id'        => $cpbId,
                                'prefill_product_id'    => $value->prefill_product_id
                            ),
                            array(
                                '%d',
                                '%d',
                            )
                        );
                    }
                }
            }

            if (! empty($productsIds)) {
                $productsIds = array_filter($productsIds);
            }

            return $productsIds;
        }

        /**
         * [insertPrefilledProducts Inserts pre-filled products data in DB]
         * @param  [int] $cpbId            [CPB Product id]
         * @param  [array] $prefillProducts  [Array of all pre-filled products id]
         * @param  [array] $prefillQty       [Array of all pre-filled products quantity]
         * @param  [array] $prefillMandatory [Array of all mandatory pre-filled products]
         * @param  string $key              [position of value to be inserted]
         * @param  string $value            [value to be inserted]
         * @param  array  $insertValues     [Array of product ids to be inserted]
         * @return [void]
         */
        public function insertPrefilledProducts($cpbId, $prefillProducts, $prefillQty, $prefillMandatory, $key = "", $value = "", $insertValues = array())
        {
            global $wpdb;
            if (empty($insertValues)) {
                $prefillProducts = array_unique($prefillProducts);
                foreach ($prefillProducts as $index => $prefillId) {
                    if (get_post_status($prefillId) != false) {
                        $wpdb->insert($this->prefillProductTable, array(
                            'cpb_product_id'         => $cpbId,
                            'prefill_product_id'     => $prefillId,
                            'prefill_quantity'       => $prefillQty[$index],
                            'prefill_mandatory'      => $prefillMandatory[$index],
                        ), array(
                            '%d',
                            '%d',
                            '%d',
                            '%d',
                        ));
                    }
                }
            } else {
                if (get_post_status($value) != false) {
                    $wpdb->insert($this->prefillProductTable, array(
                        'cpb_product_id'         => $cpbId,
                        'prefill_product_id'     => $value,
                        'prefill_quantity'       => $prefillQty[$key],
                        'prefill_mandatory'      => $prefillMandatory[$key],
                    ), array(
                        '%d',
                        '%d',
                        '%d',
                        '%d',
                    ));
                }
            }
        }


        /**
         * [updatePrefilledProducts Updates pre-filled products data in DB]
         * @param  [int] $cpbId            [CPB Product id]
         * @param  [Array] $prefillProducts  [Array of all pre-filled products id]
         * @param  [Array] $prefillQty       [Array of all pre-filled products quantity]
         * @param  [Array] $prefillMandatory [Array of all mandatory pre-filled products]
         * @param  string $key              [position of value to be updated]
         * @param  string $value            [value to be updated]
         * @param  array  $updateValues     [Array of product ids to be updated]
         * @return [void]
         */
        public function updatePrefilledProducts($cpbId, $prefillProducts, $prefillQty, $prefillMandatory, $key, $value, $updateValues)
        {
            global $wpdb;
            unset($prefillProducts);
            unset($updateValues);

            if (get_post_status($value) != false) {
                $wpdb->update($this->prefillProductTable, array(
                    'prefill_quantity'  => $prefillQty[$key],
                    'prefill_mandatory' => $prefillMandatory[$key],
                ), array(
                    'cpb_product_id'        => $cpbId,
                    'prefill_product_id'    => $value
                ), array(
                    '%d',
                    '%d',
                ), array(
                    '%d',
                    '%d'
                ));
            } else { // If a product is deleted
                $wpdb->delete(
                    $this->prefillProductTable,
                    array(
                        'cpb_product_id'        => $cpbId,
                        'prefill_product_id'    => $value
                    ),
                    array(
                        '%d',
                        '%d',
                    )
                );
            }
        }

        /**
         * [deletePrefilledProducts Deletes pre-filled products from DB]
         * @param  [int] $cpbId         [CPB Product id]
         * @param  array  $deletedValues [Array of product ids to be deleted]
         * @return [void]
         */
        public function deletePrefilledProducts($cpbId, $deletedValues = array())
        {
            global $wpdb;

            if (empty($deletedValues)) {
                $existing = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$this->prefillProductTable} WHERE cpb_product_id = %d", $cpbId));
                if (!empty($existing)) {
                    $existing = array_values($existing);
                    $deleteCount = count($existing);

                    if ($deleteCount > 0) {
                        $deletePlaceholders = array_fill(0, $deleteCount, '%d');

                        $placeholders = implode(',', $deletePlaceholders);

                        $deleteQuery = "DELETE FROM {$this->prefillProductTable} WHERE id IN ($placeholders)";
                        $wpdb->query($wpdb->prepare($deleteQuery, $existing));
                    }
                }
            } else {
                $existing = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$this->prefillProductTable} WHERE cpb_product_id = %d and prefill_product_id IN (" . implode(', ', $deletedValues) . ")", $cpbId));
                if (!empty($existing)) {
                    $existing = array_values($existing);
                    $deleteCount = count($existing);

                    if ($deleteCount > 0) {
                        $deletePlaceholders = array_fill(0, $deleteCount, '%d');

                        $placeholders = implode(',', $deletePlaceholders);

                        $deleteQuery = "DELETE FROM {$this->prefillProductTable} WHERE id IN ($placeholders)";

                        $wpdb->query($wpdb->prepare($deleteQuery, $existing));
                    }
                }
            }
        }

        /**
         * [savePrefilledProducts Processes records for insert, update or delete]
         * @param  [int] $cpbId            [CPB Product id]
         * @param  [Array] $prefillProducts  [Array of all pre-filled products id]
         * @param  [Array] $prefillQty       [Array of all pre-filled products quantity]
         * @param  [Array] $prefillMandatory [Array of all mandatory pre-filled products]
         * @return [void]
         */
        public function savePrefilledProducts($cpbId, $prefillProducts, $prefillQty, $prefillMandatory)
        {
            // global $wpdb;
            $insertValues = array();
            $deletedValues = array();
            $updateValues = array();
            $existing = $this->getPrefilledProductIds($cpbId);

            if (!empty($existing)) {
                $insertValues = array_diff($prefillProducts, $existing);
                $deletedValues = array_diff($existing, $prefillProducts);
                $updateValues = array_intersect($existing, $prefillProducts);

                if (!empty($deletedValues)) {
                    $this->deletePrefilledProducts($cpbId, $deletedValues);
                }
                foreach ($prefillProducts as $key => $value) {
                    if (!empty($insertValues) && in_array($value, $insertValues)) {
                        $this->insertPrefilledProducts($cpbId, $prefillProducts, $prefillQty, $prefillMandatory, $key, $value, $insertValues);
                    }
                    if (!empty($updateValues) && in_array($value, $updateValues)) {
                        $this->updatePrefilledProducts($cpbId, $prefillProducts, $prefillQty, $prefillMandatory, $key, $value, $updateValues);
                    }
                } // end foreach
            } else {
                $this->insertPrefilledProducts($cpbId, $prefillProducts, $prefillQty, $prefillMandatory);
            }
        }
    }
}
$GLOBALS['prefillManager'] = WdmManagePrefillProducts::getInstance();
