/*global mobileListLayoutParams, wdm_bundle_params */ ;
(function($, window, document) {
    // Defining Namespace here
    var cpbMobileListLayout = {};

    cpbMobileListLayout.mobileLayoutSelector = $('.wdm-mobile-list-cpb-layout');

    cpbMobileListLayout.cpbProductData = {
        productId: wdm_bundle_params.cpb_product_id,
        boxQuantity: parseInt(wdm_bundle_params.box_quantity),
        isDynamicPricingEnabled: wdm_bundle_params.dynamic_pricing_enable,
        productPrice: wdm_bundle_params.product_price,
        enableProductsSwap: mobileListLayoutParams.enableProductsSwap,
        numberOfProductsInBox: parseInt(($('.wdm-product-added').length <= 0) ? 0 : $('.wdm-product-added').length),
    };

    cpbMobileListLayout.addonProductData = function($mobileListCpbRow) {

        var _isEmpty = function(mixedVar) {
            //  discuss at: http://locutus.io/php/empty/
            var undef
            var key
            var i
            var len
            var emptyValues = [undef, null, false, 0, '', '0']
            for (i = 0, len = emptyValues.length; i < len; i++) {
                if (mixedVar === emptyValues[i]) {
                    return true;
                }
            }
            if (typeof mixedVar === 'object') {
                for (key in mixedVar) {
                    if (mixedVar.hasOwnProperty(key)) {
                        return false;
                    }
                }
                return true;
            }
            return false;
        }

        this.mobileListCpbRow = $mobileListCpbRow;
        this.qtyTextField = $mobileListCpbRow.find('.qty.number');
        this.addonProductId = $mobileListCpbRow.data('product-id');
        this.addonProductPrice = $mobileListCpbRow.data('product-price');
        this.addonProductQty = (_isEmpty(this.qtyTextField.data('product-quantity'))) ? 0 : this.qtyTextField.data('product-quantity');
        this.minAllowedQty = (_isEmpty(this.qtyTextField.attr('min'))) ? 0 : this.qtyTextField.attr('min');
        this.maxAllowedQty = (_isEmpty(this.qtyTextField.attr('max'))) ? false : this.qtyTextField.attr('max');
        this.isSoldIndividually = $mobileListCpbRow.data('sold-individually');
        this.isProductMandatory = this.qtyTextField.data('product-mandatory');
        this.enableProductsSwap = mobileListLayoutParams.enableProductsSwap;
        this.productName = $mobileListCpbRow.find('.product_title').attr('title');
        if (this.isSoldIndividually == 1) {
            this.maxAllowedQty = 1;
        }

        if (this.isProductMandatory == 1) {
            this.minAllowedQty = this.qtyTextField.data('product-prefill-quantity');
        }

        return this;
    };


    /**
     * This class deals with all events related to Mobile Layout. It does following things
     * - Increase Quantity when + button is clicked
     * - Decreases Quantity when - button is clicked
     * - Triggers Desktop Layout's Add to Cart button when Mobile layout's Add to cart is clicked
     * - Sends data to Desktop layout on different actions.
     */
     cpbMobileListLayout.cpbMobileLayout = function() {

        this.layoutSelector = cpbMobileListLayout.mobileLayoutSelector;

        this.enableProductsSwap = this.layoutSelector.data('enable-swapping');

        this.totalProductPrice = parseFloat(jQuery('.wdm-bundle-bundle-box').data('bundle-price'));

        //Hook Events
        this.layoutSelector.on('click', '.wdm-cpb-addon-qty-plus', {
            cpbMobileLayout: this
        }, this.increaseAddonProductQty);
        this.layoutSelector.on('click', '.wdm-cpb-addon-qty-minus', {
            cpbMobileLayout: this
        }, this.decreaseAddonProductQty);
        this.layoutSelector.on('click', '.bundle_add_to_cart_button', {
            cpbMobileLayout: this
        }, this.bundleAddToCart);
        this.layoutSelector.on('change', '.cpb_gift_message', {
            cpbMobileLayout: this
        }, this.setGiftMessage);
    };


    cpbMobileListLayout.cpbMobileLayout.prototype.increaseAddonProductQty = function(event) {

        addonProductData = cpbMobileListLayout.addonProductData(jQuery(this).closest('.bundled_product_summary'));

        if (addonProductData.maxAllowedQty !== false) {

            if (addonProductData.addonProductQty >= addonProductData.maxAllowedQty) {
                snackbar(sprintf(mobileListLayoutParams.canNotAddProduct, addonProductData.productName));
                return;
            }
        }

        //If total number of products added in the box == box quantity, then do not add new product in box
        if (event.data.cpbMobileLayout.sumOfAllAddonQtys() == cpbMobileListLayout.cpbProductData.boxQuantity) {
            snackbar(mobileListLayoutParams.giftboxFullMsg);
            return;
        }

        cpbMobileListLayout.cpbProductData.numberOfProductsInBox = cpbMobileListLayout.cpbProductData.numberOfProductsInBox + 1;

        event.data.cpbMobileLayout.updateAddonProductQty(addonProductData, addonProductData.addonProductQty + 1);

        //Update Total Price
        if (cpbMobileListLayout.cpbProductData.isDynamicPricingEnabled == 'yes') {
            event.data.cpbMobileLayout.totalProductPrice = event.data.cpbMobileLayout.totalProductPrice + parseFloat(addonProductData.addonProductPrice);
        }

        //Display Total Price
        event.data.cpbMobileLayout.displayTotalPrice();

        // If user has actually clicked + button in mobile mode, then only increase the quantity in desktop mode
        if (event.hasOwnProperty('originalEvent')) {
            //Increase Quantity in Desktop Mode too
            cpbMobileListLayout.addProductInDesktopBundle(addonProductData.addonProductId);
        }

    };

    cpbMobileListLayout.cpbMobileLayout.prototype.decreaseAddonProductQty = function(event) {
        addonProductData = cpbMobileListLayout.addonProductData(jQuery(this).closest('.bundled_product_summary'));

        // If quantity is going to be negative after substraction, then do not decrease quantity
        if ((addonProductData.addonProductQty - 1) < 0) {
            return;
        }

        //do not decrease quantity below min quantity
        if (addonProductData.minAllowedQty == addonProductData.addonProductQty) {
            return;
        }

        cpbMobileListLayout.cpbProductData.numberOfProductsInBox = cpbMobileListLayout.cpbProductData.numberOfProductsInBox - 1;

        event.data.cpbMobileLayout.updateAddonProductQty(addonProductData, addonProductData.addonProductQty - 1);

        //Update Total Price
        if (cpbMobileListLayout.cpbProductData.isDynamicPricingEnabled == 'yes') {
            event.data.cpbMobileLayout.totalProductPrice = event.data.cpbMobileLayout.totalProductPrice - parseFloat(addonProductData.addonProductPrice);
        }

        event.data.cpbMobileLayout.displayTotalPrice();

        // If user has actually clicked - button in mobile mode, then only decrease the quantity in desktop mode
        if (event.hasOwnProperty('originalEvent')) {
            //Decrease Quantity in Desktop Mode too
            cpbMobileListLayout.removeProductFromDesktopBundle(addonProductData.addonProductId);
        }

    };


    cpbMobileListLayout.cpbMobileLayout.prototype.sumOfAllAddonQtys = function() {
        var qtyTotal = 0;
        this.layoutSelector.find('.qty.number').each(function() {
            qtyTotal = qtyTotal + parseInt($(this).val());
        });
        return qtyTotal;
    }

    cpbMobileListLayout.cpbMobileLayout.prototype.updateAddonProductQty = function($addonProductData, $newQuantity) {
        $addonProductData.mobileListCpbRow.find('.qty.number').val($newQuantity);
        $addonProductData.qtyTextField.data('product-quantity', $newQuantity);
    };

    cpbMobileListLayout.cpbMobileLayout.prototype.displayTotalPrice = function() {
        var currentPrice = wdm_get_price_format(this.totalProductPrice);
        this.layoutSelector.find('.wdm_bundle_price .amount:last').html(currentPrice);
        snackbar(cpbMobileListLayout.cpbProductData.numberOfProductsInBox + ' / ' + cpbMobileListLayout.cpbProductData.boxQuantity + ' ' + mobileListLayoutParams.productsAddedText + '<br />' + mobileListLayoutParams.totalProductPriceText + ': ' + currentPrice);
    };

    cpbMobileListLayout.cpbMobileLayout.prototype.setGiftMessage = function(event) {
        $('.cpb_gift_message').val($(this).val());
    }

    cpbMobileListLayout.cpbMobileLayout.prototype.bundleAddToCart = function(event) {
        $('form .bundle_add_to_cart_button').trigger('click');
    }


    cpbMobileListLayout.cpbMobileLayout.prototype.displayUpdateMessage = function($message) {
        snackbar($message);
    };

    // Class Ends Here
    $.fn.cpb_mobile_layout = function() {
        new cpbMobileListLayout.cpbMobileLayout();
        return this;
    };

    $(function() {
        cpbMobileListLayout.mobileLayoutSelector.cpb_mobile_layout();

        if ($('.unpurchasable-product').length) {
            $('.unpurchasable-product .wdm-cpb-addon-qty-plus, .unpurchasable-product .qty.number , .unpurchasable-product .wdm-cpb-addon-qty-minus').attr('disabled', 'disabled');
        }

    });

    cpbMobileListLayout.addProductInMobileBundle = function($product_id) {
        $('.mobile_bundled_product_' + $product_id + ' .wdm-cpb-addon-qty-plus').trigger('click');
    }

    cpbMobileListLayout.removeProductFromMobileBundle = function($product_id) {
        $('.mobile_bundled_product_' + $product_id + ' .wdm-cpb-addon-qty-minus').trigger('click');
    }

    cpbMobileListLayout.addProductInDesktopBundle = function($product_id) {
        $('.desktop_bundled_product_' + $product_id + ' .images').trigger('click');
    }

    cpbMobileListLayout.removeProductFromDesktopBundle = function($product_id) {
        $('.wdm_filled_product_' + $product_id + ':last').trigger('click');
    }

    // Creating a new global variable.
    window.cpbMobileListLayout = cpbMobileListLayout;

    jQuery( document ).ready( function () {
        var classes = jQuery('[data-product-cat-id]').map(function() {
            return jQuery(this).data('product-cat-id');
        });

        var uniqueClasses = jQuery.unique(classes);

        jQuery(uniqueClasses).each(function(i, v) {
            var image = jQuery('.wdm-cpb-product-layout-wrapper *[data-product-cat-id="'+v+'"]').data('product-cat-thumbnail');
            var name = jQuery('.wdm-cpb-product-layout-wrapper *[data-product-cat-id="'+v+'"]').data('product-cat-name');
            jQuery('.wdm-cpb-product-layout-wrapper *[data-product-cat-id="'+v+'"]').wrapAll('<div id="mobile-product-cat-'+v+'" class="product-cat-wrap"></div>');
            jQuery('#mobile-product-cat-'+v).prepend('<div class="product-cat-image"><img src="'+image+'" alt="'+name+'" /></div><h3 class="product-cat-title">'+name+'</h3>')
        });
    });

    

})(jQuery, window, document);