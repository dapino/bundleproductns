<?php

namespace wisdmlabs\cpb;

class WdmProductBundleType
{
    public function __construct()
    {
        if (is_admin()) {
            add_filter('product_type_selector', array($this, 'wdmAddCustomProductType'), 10, 1);
            add_action('woocommerce_product_options_general_product_data', array($this, 'wdmAddCustomSettings'), 10);
            add_action('woocommerce_product_write_panel_tabs', array($this, 'woocommerceProductWritePanelTabsFunc'));
            add_action('woocommerce_process_product_meta_wdm_bundle_product', array($this, 'woocommerceProcessProductMetaBundleFunc'), 100);

            add_action('woocommerce_product_data_panels', array($this, 'woocommerceProductWritePanelsFunc'));

            add_action('wp_ajax_wdm_is_sold_individual', array($this, 'checkIfSoldIndividual'));

             add_filter('woocommerce_json_search_found_products', array($this, 'woocommerceJsonSearchFoundProductsCallback'), 10, 1);
        
            add_action('admin_enqueue_scripts', array($this, 'adminEnqueueScripts'));
        }
    }

    public function adminEnqueueScripts()
    {
        global $post, $woo_wdm_bundle;
        $screen       = get_current_screen();
        $screen_id    = $screen ? $screen->id : '';
        $post_id = isset($post->ID) ? $post->ID : 0;
        if (in_array($screen_id, array('product', 'edit-product'))) {
            $layout = $woo_wdm_bundle->getDesktopLayout($post_id);
            $selectedLayout = basename($layout);
            wp_enqueue_script('wdm-cpb-settings-js');
            wp_localize_script(
                'wdm-cpb-settings-js',
                'cpb_layout_object',
                array(
                    'ajax_url'  => admin_url('admin-ajax.php'),
                    'selectedLayout'    => $selectedLayout
                )
            );
        }
    }

    public function woocommerceJsonSearchFoundProductsCallback($products)
    {
        // To check if the 'woocommerce_json_search_found_products' call is for CPB' add-on products
        if (empty($_SESSION['wdm_cpb'])) {
            return $products;
        }

        $ret_products = array();
        foreach ($products as $product_id => $product_name) {
            $get_product = wc_get_product($product_id);
            if ($get_product->get_type() == 'simple') {
                $ret_products[$product_id] = $product_name;
            }
        }
        return $ret_products;
    }

    public function epLoadJqueryJs()
    {
        global $post;
        //global $post,$woo_wdm_bundle;
        if ($post->post_type == 'product') {
            wp_enqueue_script('jquery');
        }
    }

    public function wdmAddCustomProductType($product_types)
    {
        $product_types['wdm_bundle_product'] = __('Custom Product Box', 'custom-product-boxes');
        return $product_types;
    }

    /**
     * [checkIfSoldIndividual Checks whether the products are sold individual and have prefilled quantity set greater than 1. Invoked by ajax call]
     * @return [json array] [returns array containing produt ids of sold individual products whoes quantity is set greater than 1]
     */
    public function checkIfSoldIndividual()
    {
        if (!isset($_POST['product_ids'])) {
            return;
        }

        $productIds = $_POST['product_ids'];
        $sldindIds = array();
        foreach ($productIds as $productId => $qty) {
            $product = wc_get_product($productId);
            if ($product->is_sold_individually() && $qty > 1) {
                $sldindIds[] = $productId;
            }
        }
        $sldindIds = array_unique($sldindIds);
        echo json_encode($sldindIds);

        die();
    }

    public function wdmAddCustomSettings()
    {
//        global $woocommerce, $post, $product;

        echo '<div class="options_group show_if_wdm_bundle_product">';

        woocommerce_wp_text_input(
            array(
                'id' => 'wdm_reg_price_field',
                'label' => __('Regular Price', 'custom-product-boxes') . ' (' . get_woocommerce_currency_symbol() . ')',
                'placeholder' => '',
                'desc_tip' => 'true',
                'description' => __('Enter Regular Price.', 'custom-product-boxes'),
                'type' => 'text',
                'data_type' => 'price',
                'value' => get_post_meta(get_the_ID(), '_regular_price', true),
            )
        );
        
        woocommerce_wp_text_input(
            array(
                'id' => 'wdm_sale_price_field',
                'label' => __('Sale Price', 'custom-product-boxes') . ' (' . get_woocommerce_currency_symbol() . ')',
                'placeholder' => '',
                'desc_tip' => 'true',
                'description' => __('Enter Sale Price.', 'custom-product-boxes'),
                'type' => 'text',
                'data_type' => 'price',
                'value' => get_post_meta(get_the_ID(), '_sale_price', true),
                )
        );

        echo '</div>';
    }

    public function woocommerceProductWritePanelTabsFunc()
    {
        echo '<li class="wdm_bundle_product_tab show_if_wdm_bundle_product wdm_bundle_product_options linked_product_options"><a href="#wdm_bundle_product_data">' . __('Custom Box Settings', 'custom-product-boxes') . '</a></li>';
    }


    /**
     * [unlinkDeletedProducts Removes the deleted products data from the CPB Product]
     * @param  [array] $product_field_types [array of products bundled in CPB product]
     * @return [type]                      [description]
     */
    public function unlinkDeletedProducts($product_field_types, $single = false)
    {
        global $wpdb, $post;
        $postsTable = $wpdb->prefix . 'posts';
        $allProducts = $wpdb->get_col("SELECT ID FROM $postsTable WHERE post_type IN ('product', 'product_variation')");
        $cpb_keys = array_keys($product_field_types);

        if ($single) {
            $cpb_keys = $product_field_types;
            
            $deletedProducts = $allProducts ? array_diff($cpb_keys, $allProducts) : false;
            if ($deletedProducts) {
                foreach ($deletedProducts as $deletedKey => $value) {
                    unset($product_field_types[$deletedKey]);
                }
            }
            return $product_field_types;
        }

        if ($cpb_keys && $allProducts) {
            $deletedProducts = array_diff($cpb_keys, $allProducts);
            if ($deletedProducts) {
                foreach ($deletedProducts as $deletedKey) {
                    unset($product_field_types[$deletedKey]);
                }
            }
            update_post_meta($post->ID, '_bundle_data', $product_field_types);
        }
        return $product_field_types;
    }

    public function woocommerceProductWritePanelsFunc()
    {
        global $post, $woo_wdm_bundle, $prefillManager;
        $allLayouts = WdmCPBLayoutSetting::getAllLayouts();
        //Bundled products
        $product_field_types = maybe_unserialize(
            get_post_meta(
                $post->ID,
                '_bundle_data',
                true
            )
        );

        if ($product_field_types) {
            $product_field_types = $this->unlinkDeletedProducts($product_field_types);
        }
        
        // json ids
        $json_ids = array();
        
        if (is_array($product_field_types)) {
            foreach ($product_field_types as $product_id => $product_data) {
                $product = wc_get_product($product_id);
                $json_ids[$product_id] = wp_kses_post($product->get_formatted_name());
                unset($product_data);
            }
        }

        // preparing variables to use in the HTML we are parsing
        $product_box_label = __('Product Box Settings', 'custom-product-boxes');
        $json_ids_imploded = implode(',', array_keys($json_ids));
        $json_ids_json = esc_attr(json_encode($json_ids));
        $helptip_image = WC()->plugin_url().'/assets/images/help.png';
        $helptip = __('Select the products which can be added to the Custom Product Box. Kindly select more than one product.', 'custom-product-boxes');
        $addon_products_label = __('Add-On Products', 'custom-product-boxes');
        
        echo "<div id='wdm_bundle_product_data' class='panel woocommerce_options_panel'>
              <div class='options_group'>
              <div class='wc-wdm_bundle_products'>
              <div class='wdm_bundle_products_info'>
                {$product_box_label}
              </div>";

        echo '<div class="options_group show_if_wdm_bundle_product">';
                  
        woocommerce_wp_select(
            array(
            'id' => '_wdm_cpb_pricing_type_field',
            'label' => __('Pricing Type', 'custom-product-boxes'),
            'desc_tip' => 'true',
            'description' => __('Select the pricing type.', 'custom-product-boxes'),
            'options' => array(
            'wdm-cpb-fixed-pp' => __('Fixed Pricing', 'custom-product-boxes'),
            'wdm-cpb-dynamic-pp-base' => __('Per Product Pricing with Base Price', 'custom-product-boxes'),
            'wdm-cpb-dynamic-pp-nobase' => __('Per Product Pricing without Base Price', 'custom-product-boxes')
            ),
            )
        );

        woocommerce_wp_text_input(
            array(
            'id' => '_wdm_grid_field',
            'label' => __('Box Quantity', 'custom-product-boxes'),
            'placeholder' => '',
            'desc_tip' => 'true',
            'description' => __('Set the number of items which can be added to the box.', 'custom-product-boxes'),
            'type' => 'number',
            'custom_attributes' => array(
            'step' => 'any',
            'min' => '2',
            ),
            'value' => get_post_meta($post->ID, '_wdm_grid_field', true),
            )
        );

        woocommerce_wp_select(
            array(
            'id' => '_wdm_desktop_layout',
            'label' => __('Select box layout', 'custom-product-boxes'),
            'desc_tip' => 'true',
            'description' => __('Select the box layout. You can also create a custom layout by overriding our default template. To know more, please refer our user guide.', 'custom-product-boxes'),
            'options' => $allLayouts,
            'value' => $woo_wdm_bundle->getDesktopLayout($post->ID),
            )
        );

        woocommerce_wp_select(
            array(
            'id' => '_wdm_column_field',
            'label' => __('Columns in Gift Box', 'custom-product-boxes'),
            'desc_tip' => 'true',
            'description' => __("Select the number of columns for the gift layout (This will affect the display of the layout on your custom box page).", 'custom-product-boxes'),
            'options' => array(
                    'wdm-bundle-single-product-col-2' => __('2', 'custom-product-boxes'),
                    'wdm-bundle-single-product-col-3' => __('3', 'custom-product-boxes'),
                ),
            )
        );
        woocommerce_wp_select(
            array(
            'id' => '_wdm_item_field',
            'label' => __('Items per row in Gift Box', 'custom-product-boxes'),
            'desc_tip' => 'true',
            'description' => __("Select the number of columns for the gift layout (This will affect the display of the layout on your custom box page).", 'custom-product-boxes'),
            'options' => array(
                    'wdm-bundle-single-product-col-4' => __('4', 'custom-product-boxes'),
                    'wdm-bundle-single-product-col-5' => __('5', 'custom-product-boxes'),
                    'wdm-bundle-single-product-col-6' => __('6', 'custom-product-boxes'),
                    'wdm-bundle-single-product-col-7' => __('7', 'custom-product-boxes'),
                    'wdm-bundle-single-product-col-8' => __('8', 'custom-product-boxes'),
                ),
            )
        );
        // }

        woocommerce_wp_select(
            array(
            'id' => '_wdm_product_grid',
            'label' => __('Columns in Product Layout', 'custom-product-boxes'),
            'desc_tip' => 'true',
            'description' => __("Select the number of columns for the products layout (This will affect the display of the layout on your custom box page).", 'custom-product-boxes'),
            'options' => array(
                    'bundled_product-col-2' => __('2', 'custom-product-boxes'),
                    'bundled_product-col-3' => __('3', 'custom-product-boxes'),
                ),
            )
        );
        woocommerce_wp_select(
            array(
            'id' => '_wdm_product_item_grid',
            'label' => __('Items per row in Product Layout', 'custom-product-boxes'),
            'desc_tip' => 'true',
            'description' => __("Select the number of columns for the products layout (This will affect the display of the layout on your custom box page).", 'custom-product-boxes'),
            'options' => array(
                    'bundled_product-col-4' => __('4', 'custom-product-boxes'),
                    'bundled_product-col-5' => __('5', 'custom-product-boxes'),
                    'bundled_product-col-6' => __('6', 'custom-product-boxes'),
                    'bundled_product-col-7' => __('7', 'custom-product-boxes'),
                    'bundled_product-col-8' => __('8', 'custom-product-boxes'),
                ),
            )
        );
        // }

        woocommerce_wp_checkbox(
            array(
            'id' => 'wdm_boxes_selection',
            'label' => __('Allow Partially-Filled Box', 'custom-product-boxes'),
            'description' => __('Allow the purchase of box which has not been filled to its full capacity', 'custom-product-boxes'),
            'value' => esc_attr(get_post_meta($post->ID, 'wdm_boxes_selection', true)))
        );

        woocommerce_wp_checkbox(
            array(
            'id' => 'wdm_order_by_date',
            'label' => __('Sort Products by Date', 'custom-product-boxes'),
            'description' => __('Adds newly added product to the top', 'custom-product-boxes'),
            'value' => esc_attr(get_post_meta($post->ID, 'wdm_order_by_date', true)))
        );

        woocommerce_wp_checkbox(
            array(
            'id' => 'wdm_disable_scroll',
            'label' => __('Allow Scroll Lock', 'custom-product-boxes'),
            'description' => __('Enables the scroll of gift box', 'custom-product-boxes'),
            'value' => esc_attr(get_post_meta($post->ID, 'wdm_disable_scroll', true)))
        );

        woocommerce_wp_checkbox(
            array(
            'id' => '_wdm_enable_gift_message',
            'label' => __('Enable Gift Message', 'custom-product-boxes'),
            'desc_tip' => 'true',
            'description' => __("Allows Customers to send a message along with the Gift Box", 'custom-product-boxes'),
            'value' => esc_attr(get_post_meta($post->ID, '_wdm_enable_gift_message', true)))
        );

        woocommerce_wp_text_input(
            array(
            'id' => '_wdm_gift_message_label',
            'label' => __('Gift Message Label', 'custom-product-boxes'),
            'placeholder' => '',
            'desc_tip' => 'true',
            'description' => __("Set a label for 'Gift Message' field", 'custom-product-boxes'),
            'type' => 'text',
            )
        );

            echo '</div>';
        
        ?> 
        <div class="wdm_bundle_products_selector">
        <?php
            $this->wdmSelectAddOnProduct($post, $addon_products_label, $json_ids, $json_ids_imploded, $json_ids_json, $helptip, $helptip_image);
        ?>
        </div>
                <?php
                $prefill_list = $prefillManager->getPrefilledProducts($post->ID);

                if (empty($prefill_list)) {
                    update_post_meta($post->ID, 'wdm_prefilled_box', 'no');
                }

                woocommerce_wp_checkbox(
                    array(
                        'id' => 'wdm_prefilled_box',
                        'label' => __('Pre-Filled Box', 'custom-product-boxes'),
                        'description' => __('Allow pre-filled box', 'custom-product-boxes'),
                        'value' => esc_attr(get_post_meta($post->ID, 'wdm_prefilled_box', true))
                    )
                );

// '<span class="help_tip tips" data-tip="' . esc_attr($helptip) . '">' . esc_attr($title) . '</span>'

                $mandatory = __("Mandatory", 'custom-product-boxes');
                $mandatoryTip = __("Check to make the selected pre-filled products mandatory in the custom box. (Products which are marked mandatory cannot be removed from the box)", 'custom-product-boxes');

                echo "<div class = 'prefill_div'>
                <table class = 'prefill_table' id = 'prefill_table_id'>
                <thead><tr><th><span class='help_tip tips' data-tip='" . esc_attr($mandatoryTip) . "'>" . esc_attr($mandatory) . "</span></th><th>".__("Product Name", 'custom-product-boxes')."</th><th>".__("Quantity", 'custom-product-boxes')."</th><th class = 'cpb_blank'></th></tr></thead>
                    <tbody>";

                if ($product_field_types) {
                    $this->wdmCpbPrefillTable($product_field_types, $prefill_list);
                }

                echo "      </tbody>
                        </table>";

                woocommerce_wp_checkbox(
                    array(
                    'id' => 'wdm_swap_products',
                    'label' => __('Remove Mandatory Products', 'custom-product-boxes'),
                    'description' => __('Allows user to remove mandatory products only if they run out of stock', 'custom-product-boxes'),
                    'value' => esc_attr(get_post_meta($post->ID, 'wdm_swap_products', true)))
                );

                echo "</div>";

                echo "</div>
                </div>";

                //$bundle_data = maybe_unserialize(get_post_meta($post->ID, '_bundle_data', true));
                    echo '</div>';

                    echo '<style>
                    /*.woocommerce_options_panel .wdm_bundle_products_selector .chosen-container-multi {
                        width : 90% !important;
                    }

                    .wdm_bundle_products_selector p.form-field.product_field_type{
                        padding : 0 !important;
                    }*/
                    .wdm_bundle_products_selector p.form-field .wc-product-search {
                          width: 95% !important;
                          margin-top: 11px;
                    }

                    .wdm_bundle_products_selector p.form-field.wdm_product_selector {
                        padding-left: 16px !important;
                    }
                    .wc-wdm_bundle_products .wdm_bundle_products_info {
                        padding-left: 12px;
                        margin-top: 11px;
                    }
                    .wdm_bundle_products_selector .product_field_type_title{
                        padding-bottom: 6px;
                    }

                </style>';
    }

    public function wdmSelectAddOnProduct($post, $addon_products_label, $json_ids, $json_ids_imploded, $json_ids_json, $helptip, $helptip_image)
    {
        if (version_compare(WC_VERSION, '3.0.0', '<')) {
            ?>
                <p class='form-field wdm_product_selector'>
                    <span class='product_field_type_title'>
                        <?php echo $addon_products_label; ?>
                    </span>
            
                <input type='hidden' id='product_field_type' name='product_field_type' class='wc-product-search' style='width: 50%;' data-placeholder='Search for a product&hellip;' data-action='woocommerce_json_search_products' data-multiple='true' data-selected="<?php echo $json_ids_json; ?>" value ="<?php echo $json_ids_imploded; ?>" />

                <img class='help_tip' data-tip="<?php echo $helptip; ?>" src="<?php echo $helptip_image; ?>" height='16' width='16' />
                </p>
            <?php
        } else {
            ?>      
            <p class='form-field add-on-class'>
                <label for="product_field_type"><?php echo $addon_products_label; ?></label>          
                <select class="wc-product-search" multiple="multiple" style="width: 80%;" id="product_field_type" name="product_field_type[]" data-placeholder="<?php esc_attr_e("Search for a product&hellip;", "woocommerce"); ?>" data-action="woocommerce_json_search_products" data-exclude="<?php echo intval($post->ID); ?>"
                >
                    <?php

                    foreach ($json_ids as $product_id => $product_name) {
                        $product = wc_get_product($product_id);
                        if (is_object($product)) {
                            echo '<option value="' . esc_attr($product_id) . '"' . selected(true, true, false) . '>' . $product_name . '</option>';
                        }
                    }
                    ?>
                </select>
                <?php echo wc_help_tip($helptip); ?>
            </p>
    <?php
        }
    }


    /**
     * [wdmCpbPrefillTable generates the table structure for prefilled products saved in databse. i.e loads the prefilled product in php]
     * @param  [array] $product_field_types [array of products bundled in CPB product]
     * @param  [array] $prefill_list [array of products to be prefilled]
     * @return [void]
     */
    public function wdmCpbPrefillTable($product_field_types, $prefill_list)
    {
        global $post, $woo_wdm_bundle;
        $cpbKeys = array_keys($product_field_types);
        // $prefill_list = $prefillManager->getPrefilledProducts($post->ID);
        $maxVal = get_post_meta($post->ID, '_wdm_grid_field', true);
        $addImage = $woo_wdm_bundle->wooWdmBundlesPluginUrl() .'/assets/images/plus-icon.png';
        $removeImage = $woo_wdm_bundle->wooWdmBundlesPluginUrl() .'/assets/images/minus-icon.png';
        $i = 0;

        if (!empty($prefill_list)) {
            foreach ($prefill_list as $key) {
                echo "<tr>";
                echo $this->generateMandatoryCheckbox($cpbKeys, $key);
                echo "<td><select name='wdm_cpb_products[]' class='prefill_products_holder'>".$this->generatePrefilledOption($cpbKeys, $key)."</select></td>";

                echo "<td class = 'prefill_qty'><input type = 'number' name = 'wdm_prefill_qty[]' min = '1' max = '".$maxVal."' class = 'prefill_qty_id' value = '".$key['product_qty']."' /></td>";
                echo "<td><a class='wdm_cpb_rem' href='#' id=''><img class='add_new_row_image' src='".$removeImage."' /></a>";
                if ($i == count($prefill_list) - 1) {
                    echo "<a class='wdm_cpb_add' href='#' id=''><img class='add_new_row_image' src='".$addImage."' /></a>";
                }
                echo "</td></tr>";

                $i++;
            } //end of for
        } // end if
    } // end of function

    /**
     * [generateMandatoryCheckbox Function to Generate Checkbox for pre-filled Mandatory products fetched from DB ]
     * @param  [array] $cpbKeys [array of product id bundled in CPB product]
     * @param  [array] $prefilledProduct     [prefilled product data]
     * @return [string]          [html tags]
     */
    public function generateMandatoryCheckbox($cpbKeys, $prefilledProduct)
    {
        if (in_array($prefilledProduct['product_id'], $cpbKeys)) {
            if ($prefilledProduct['product_mandatory'] == 1) {
                return "<td><input type = 'checkbox' class = 'prefill_checkbox' name = 'prod_mandatory[]' value = '".$prefilledProduct['product_id']."' checked/></td>";
            } else {
                return "<td><input type = 'checkbox' class = 'prefill_checkbox' name = 'prod_mandatory[]' value = '0' /></td>";
            }
        }
    }

    /**
     * [generatePrefilledOption Function to Generate dropdown options for pre-filled products fetched from DB ]
     * @param  [array] $cpbKeys [array of product id bundled in CPB product]
     * @param  [type] $prefilledProduct     [prefilled product data]
     * @return [string]          [html tags]
     */
    public function generatePrefilledOption($cpbKeys, $prefilledProduct)
    {
        $productHolder = "";

        foreach ($cpbKeys as $index => $value) {
            $productSku = get_post_meta($value, '_sku', true);
            if (!empty($productSku)) {
                if ($value == $prefilledProduct['product_id']) {
                    $productHolder .= "<option value = '".$value."' selected>".get_the_title($value)." (".$productSku.")"."</option>";
                } else {
                    $productHolder .= "<option value = '".$value."'>".get_the_title($value)." (".$productSku.")"."</option>";
                }
            } else {
                if ($value == $prefilledProduct['product_id']) {
                    $productHolder .= "<option value = '".$value."' selected>".get_the_title($value)." (#".$value.")"."</option>";
                } else {
                    $productHolder .= "<option value = '".$value."'>".get_the_title($value)." (#".$value.")"."</option>";
                }
            }
        }

        return $productHolder;
    }


    //public function wdm_add_extra_options_setting($options) {
    //
    //  $options['_per_product_pricing_active_cpbz'] = array(
    //      'id' => '_per_product_pricing_active_cpb',
    //      'wrapper_class' => 'show_if_wdm_bundle_product',
    //      'label' => __('Per Product Pricing', 'custom-product-boxes'),
    //      'description' => __('If enabled, the Custom product box will be priced per-item, based on the prices of the selected items.', 'custom-product-boxes'),
    //      'default' => (get_post_meta(get_the_ID(), '_per_product_pricing_active', true) == 'yes') ? 'yes' : 'no',
    //  );
    //
    //  $options['_product_base_pricing_cpb'] = array(
    //      'id' => '_product_base_pricing_cpb',
    //      'wrapper_class' => 'show_if_wdm_bundle_product',
    //      'label' => __('Product Base Pricing', 'custom-product-boxes'),
    //      'description' => __('If enabled, the price of the Custom product box will be taken into consideration for dynamic pricing feature.', 'custom-product-boxes'),
    //      'default' => (get_post_meta(get_the_ID(), '_product_base_pricing_active', true) == 'yes') ? 'yes' : 'no',
    //  );
    //
    //  return $options;
    //}


    /**
     * [woocommerceProcessProductMetaBundleFunc Saves the changes in database]
     * @param  [int] $post_id [CPB product id]
     * @return [void]
     */
    public function woocommerceProcessProductMetaBundleFunc($post_id)
    {
        global $prefillManager;
        // update_option( 'notify_outofstock', 'yes', 'no' );
        // save custom product data
        $product_val = array(
            '_wdm_product_grid'             => '',
            '_wdm_product_item_grid'        => '',
            '_wdm_grid_field'               => '',
            '_wdm_column_field'             => '',
            '_wdm_item_field'               => '',
            '_wdm_cpb_pricing_type_field'   => '',
            '_wdm_desktop_layout'           => '',
        );

        $productkeys = array_replace($product_val, array_intersect_key($_POST, $product_val));
        $productkeys = array_filter($productkeys);
        $wdm_reg_price_field='';
        $wdm_sale_price_field='';
        foreach ($productkeys as $key => $value) {
            update_post_meta($post_id, $key, esc_attr($value));
        }

        // save product price
        if (isset($_POST['wdm_reg_price_field'])) {
            $wdm_reg_price_field    = wc_clean($_POST['wdm_reg_price_field']);
        }

        if (empty($_POST['wdm_reg_price_field'])) {
            $wdm_reg_price_field    = 0;
        }

        if (isset($_POST['wdm_sale_price_field']) && !empty($_POST['wdm_sale_price_field'])) {
            $wdm_sale_price_field=wc_clean($_POST['wdm_sale_price_field']);
        }

        update_post_meta($post_id, '_regular_price', '' === $wdm_reg_price_field ? '' : wc_format_decimal($wdm_reg_price_field));

        update_post_meta($post_id, '_sale_price', '' === $wdm_sale_price_field ? '' : wc_format_decimal($wdm_sale_price_field));

        if ('' !== $wdm_sale_price_field) {
            update_post_meta($post_id, '_price', wc_format_decimal($wdm_sale_price_field));
        } else {
            update_post_meta($post_id, '_price', wc_format_decimal($wdm_reg_price_field));
        }

        $wdm_product_column = $productkeys['_wdm_cpb_pricing_type_field'];
        switch ($wdm_product_column) {
            case 'wdm-cpb-fixed-pp':
                update_post_meta($post_id, '_per_product_pricing_active', 'no');
                update_post_meta($post_id, '_product_base_pricing_active', 'no');
                break;
        
            case 'wdm-cpb-dynamic-pp-base':
                update_post_meta($post_id, '_per_product_pricing_active', 'yes');
                update_post_meta($post_id, '_product_base_pricing_active', 'yes');
                break;
        
            case 'wdm-cpb-dynamic-pp-nobase':
                update_post_meta($post_id, '_per_product_pricing_active', 'yes');
                update_post_meta($post_id, '_product_base_pricing_active', 'no');
                update_post_meta($post_id, '_price', 0);
                break;
        }
        
        update_post_meta($post_id, '_sold_individually', "no");
        update_post_meta($post_id, '_per_product_shipping_active', 'no');
        update_post_meta($post_id, '_virtual', 'no');
        update_post_meta($post_id, '_weight', stripslashes($_POST['_weight']));
        update_post_meta($post_id, '_length', stripslashes($_POST['_length']));
        update_post_meta($post_id, '_width', stripslashes($_POST['_width']));
        update_post_meta($post_id, '_height', stripslashes($_POST['_height']));

        $wdm_bundle_data = array();
        if (isset($_POST['product_field_type'])) {
            if (version_compare(WC_VERSION, '3.0.0', '<')) {
                $wdm_bundle_data = array_filter(array_map('intval', explode(',', $_POST['product_field_type'])));
            } else {
                $wdm_bundle_data = array_filter(array_map('intval', $_POST['product_field_type']));
            }
        }

        $wdm_bundle_data = $this->unlinkDeletedProducts($wdm_bundle_data, true);

        foreach ($wdm_bundle_data as $item_id) {
            $product = wc_get_product($item_id);
            if ($product->get_type() == 'simple') {
                $bundle_item_quantity = get_post_meta($item_id, '_stock', true);
                $bundle_data[$item_id]['min_val'] = 0;
                if (!empty($bundle_item_quantity)) {
                    $bundle_data[$item_id]['max_val'] = $bundle_item_quantity;
                }

                $bundle_data[$item_id]['item_quantity'] = $bundle_item_quantity;
                $bundle_data[$item_id]['product_id'] = $item_id;
                $bundle_data[$item_id]['bundle_quantity'] = 0;
            }
        }

        update_post_meta($post_id, '_bundle_data', $bundle_data);

        if (isset($_POST['wdm_boxes_selection'])) {
            update_post_meta($post_id, 'wdm_boxes_selection', $_POST['wdm_boxes_selection']);
        } else {
            update_post_meta($post_id, 'wdm_boxes_selection', 'no');
        }
    
        if (isset($_POST['wdm_order_by_date'])) {
            update_post_meta($post_id, 'wdm_order_by_date', $_POST['wdm_order_by_date']);
        } else {
            update_post_meta($post_id, 'wdm_order_by_date', 'no');
        }

        if (isset($_POST['wdm_disable_scroll'])) {
            update_post_meta($post_id, 'wdm_disable_scroll', $_POST['wdm_disable_scroll']);
        } else {
            update_post_meta($post_id, 'wdm_disable_scroll', 'no');
        }

        if (isset($_POST['wdm_prefilled_box'])) {
            update_post_meta($post_id, 'wdm_prefilled_box', $_POST['wdm_prefilled_box']);
            $this->processPrefilledProductsData($post_id);
        } else {
            $prefillManager->deletePrefilledProducts($post_id);
            update_post_meta($post_id, 'wdm_prefilled_box', 'no');
        }

        if (isset($_POST['wdm_swap_products'])) {
            update_post_meta($post_id, 'wdm_swap_products', $_POST['wdm_swap_products']);
        } else {
            update_post_meta($post_id, 'wdm_swap_products', 'no');
        }

        $enableGiftMessage = isset($_POST['_wdm_enable_gift_message']) ? $_POST['_wdm_enable_gift_message'] : 'no';
        $giftMessageLabel = isset($_POST['_wdm_gift_message_label']) ? $_POST['_wdm_gift_message_label'] : '';

        update_post_meta($post_id, '_wdm_enable_gift_message', $enableGiftMessage);

        if ($enableGiftMessage == 'yes') {
            update_post_meta($post_id, '_wdm_gift_message_label', $giftMessageLabel);
        } else {
            update_post_meta($post_id, '_wdm_gift_message_label', '');
        }
    }

    /**
     * [processPrefilledProductsData Processes the prefilled data to be stored in DB]
     * @param  [int] $postId [CPB product id]
     * @return [void]
     */
    public function processPrefilledProductsData($postId)
    {
        global $prefillManager;
        $prefillProducts = isset($_POST['wdm_cpb_products']) ? $_POST['wdm_cpb_products'] : array();
        $prefillQty = isset($_POST['wdm_prefill_qty']) ? $_POST['wdm_prefill_qty'] : array();
        $prefillMandatory = isset($_POST['prod_mandatory']) ? $_POST['prod_mandatory'] : array();

        $prefillCheckbox = array();
        foreach ($prefillProducts as $key => $value) {
            if (in_array($value, $prefillMandatory)) {
                $prefillCheckbox[$key] = 1;
            } else {
                $prefillCheckbox[$key] = 0;
            }
        }

        if ($prefillProducts && $prefillQty) {
            $prefillManager->savePrefilledProducts($postId, $prefillProducts, $prefillQty, $prefillCheckbox);
        }
    }
}
