<?php

namespace wisdmlabs\cpb;

if (! class_exists('WdmCpbInstall')) {
    
    class WdmCpbInstall
    {
        /*
         * Creates all tables required for the plugin. It creates one
         * table in the database. cpb_prefilled_products_data stores the mapping of
         * pre-filled products and their quantity.
         */
        
        public static function createTables()
        {
            global $wpdb;
            $wpdb->hide_errors();

            $collate = self::getWpCharsetCollate();

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $cpb_prefilled_products = $wpdb->prefix . 'cpb_prefilled_products_data';

            //Create cpb_prefilled_products_data Table
            if (! $wpdb->get_var("SHOW TABLES LIKE '$cpb_prefilled_products';")) {
                $prefilled_table_query  = "
                CREATE TABLE IF NOT EXISTS {$cpb_prefilled_products} (
                                    id bigint(20) PRIMARY KEY NOT NULL AUTO_INCREMENT,
                                    cpb_product_id bigint(20),
                                    prefill_product_id bigint(20),
                                    prefill_quantity bigint(20),
                                    prefill_mandatory TINYINT(1),
                                    INDEX product_id (cpb_product_id),
                                    INDEX user_id (prefill_product_id)
                                ) $collate;
                                ";
                @dbDelta($prefilled_table_query);
            }
        }

        protected static function getWpCharsetCollate()
        {
            global $wpdb;
            $charset_collate = '';

            if (! empty($wpdb->charset)) {
                $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
            }

            if (! empty($wpdb->collate)) {
                $charset_collate .= " COLLATE $wpdb->collate";
            }

            return $charset_collate;
        }

    }
}