<?php
namespace wisdmlabs\cpb;

class WdmCPBProductGiftBox extends WdmAbstractProductDisplay
{
    public function __construct()
    {
        parent::__construct();
        // Shows the grid for the Custom Product Box. And Price and Title of the custom product box.
        add_action('wdm_gift_layout', array($this, 'cpbGiftLayout'));
        
        // to show the products we can add in the custom box. Also the quantity and add to cart button.
        add_action('wdm_cpb_enqueue_scripts', array($this, 'cpBEnqueueScripts'));
    }

    public function cpbGiftLayout()
    {
        global $prefillManager, $post;

        $prefillProducts = $prefillManager->getPrefilledProducts($post->ID);
        if (empty($prefillProducts)) {
            update_post_meta($post->ID, 'wdm_prefilled_box', 'no');
        }
        // Display grid at front end
        $total_clm = get_post_meta(get_the_ID(), '_wdm_grid_field', true);
        if (!$this->allowPrefillProducts()) {
            $this->displayBlankBlocks($total_clm, 1);
        } else {
            $this->displayPrefilledBlocks($total_clm, $prefillProducts);
        }
    }


    public function displayBlankBlocks($total_clm, $clm)
    {
        $columnField = $this->getColumnFieldMetaKeyName(get_the_ID());
        $columnData = get_post_meta(get_the_ID(), $columnField, true);
        if (!empty($total_clm)) {
            for (; $clm <= $total_clm; $clm++) {
                echo '<div id="wdm_bundle_bundle_item_' . $clm . '" class = "wdm-bundle-single-product ' . $columnData . '">';
                echo '<div class = "wdm-bundle-box-product"></div>';
                echo '</div>';
            }
        }
    }

    public function displayPrefilledBlocks($total_clm, $prefillProducts)
    {
        global $prefillManager, $post;
        $clm = 1;
        $prefillProducts = $prefillManager->getPrefilledProducts($post->ID);

        if (!empty($prefillProducts) && !empty($total_clm)) {
            foreach ($prefillProducts as $singleProduct) {
                $clm = $this->displayPrefilledBlock($clm, $singleProduct);
            }
            if ($clm <= $total_clm) {
                $this->displayBlankBlocks($total_clm, $clm);
            }
        }
    }

    public function displayPrefilledBlock($clm, $singleProduct)
    {
        // check stock availability
        $stockStatus = $this->checkInventoryStatus($singleProduct);

        if ($stockStatus || ($singleProduct['product_mandatory'] && $this->enableSwapping())) {
            $clm = $this->addPrefilledProduct($singleProduct, $clm);
        }
        return $clm;
    }

    public function cpBEnqueueScripts()
    {
        wp_enqueue_script('wdm-add-to-cart-bundle');
        wp_enqueue_script('wdm-product-div-height-js');
        wp_enqueue_style('wdm-bundle-css');
        if ($this->enableScroll()) {
            wp_enqueue_script('wdm-scroll-lock-js');
        }
        if ($this->allowPrefillProducts()) {
            wp_enqueue_script('wdm-prefill-boxes-js');
        }
    }
}
