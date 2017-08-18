jQuery(document).ready(function($){

    jQuery('.wdm-bundle-single-product .wdm-bundle-box-product .wdm-prefill-product').each(function(){
        $prefil_prod_id = $(this).attr('data-bundled-item-id');
        $this = $( '.images' ).closest('div[data-product-id='+$prefil_prod_id+']');
        $isSoldInd = $this.find('.images').hasClass('wdm-product-sold-individually');

        if($isSoldInd) {
            $this.addClass('wdm-no-stock');
        }
        var item_quantity_max = $this.find( ".buttons_added" ).find( ".input-text" ).attr( "max" );
        var item_quantity_current = $this.find( ".buttons_added" ).find( ".input-text" ).val();
        if (item_quantity_current == '' || item_quantity_current == undefined) {
            item_quantity_current = 0; // because some sites had this issue where initial 0 values is read as '' or undefined
        }

        var per_product_pricing_active_enable = wdm_bundle_params.dynamic_pricing_enable;

        if ( per_product_pricing_active_enable == "yes" ) {

            var product_price = parseFloat( $this.data( 'product-price' ) );
            var new_cpb_price = get_added_price( product_price );
jQuery('.wdm-bundle-bundle-box').data('bundle-price', new_cpb_price);
            var $option_value = 0;
            
            $opt_val =   $('.product-addon-totals .amount').text();

            if ($opt_val) {
                $option_value = parseFloat($opt_val.replace(wdm_bundle_params.currency_symbol, ""));
            }
            
            new_cpb_price += $option_value;

            
            new_cpb_price = wdm_get_price_format( new_cpb_price );

            if( wdm_bundle_params.wdm_bundle_on_sale ){
                $( ".price" ).find( "ins .amount" ).html( new_cpb_price );
            }
            else{
                $( ".price" ).find( ".amount" ).html( new_cpb_price );
            }
        }

        //if item_quantity_max is empty then (parseInt(item_quantity_max) - 1) will be NaN.
        if ( item_quantity_max != "") {
            if ( item_quantity_current == ( parseInt( item_quantity_max ) - 1 )  && (!$( this ).closest( ".bundled_product_summary" ).hasClass('allow_notify') )) {
                $( this ).closest( ".bundled_product_summary" ).addClass( "wdm-no-stock" );
            }
        }
        // if(!canProductBeAdded(item_id)) {
        //     $( this ).closest( ".bundled_product_summary" ).addClass( "wdm-no-stock" );
        // }

        if ( $( this ).hasClass( 'plus' ) == false ) {
            $this.find( ".buttons_added" ).find( ".input-text" ).val( parseInt( item_quantity_current ) + 1 );
        }

    });

});