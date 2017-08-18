jQuery( document ).ready( function () {

    jQuery( '.zoom' ).unbind( 'click' );

    jQuery( '.wdm_bundle_price p.price' ).prepend('Total: ');

    // Main Product's 'Add to Cart' button is clicked
    jQuery( 'form' ).delegate( '.bundle_add_to_cart_button', 'click', function ( e ) {
        
        if (wdm_add_to_cart.enableGiftMessage == 'yes') {
            var msgData = jQuery('.cpb_gift_message').val();
            var product_id = jQuery('.cpb_gift_message').attr('data-product-id');


            // Ajax Request to add gift message
            jQuery.ajax({
                url: wdm_add_to_cart.ajax_url,
                type: "POST",
                data: {
                        action:'wdm_add_gift_message_session', 
                        msgData: msgData, //send request data
                        product_id: product_id, 
                      },
                async : false,
                success: function(data){ 
                    return true;
                },
            });            
        }

        var max_div_id = jQuery( '.wdm-bundle-single-product:last-child()' ).attr( 'id' ).split( '_' );
        max_div_id = max_div_id[4];
        jQuery( '.bundled_product_summary' ).each( function () {
            var curr_product_id = jQuery( this ).find( '.cart' ).attr( 'data-bundled-item-id' );

            var curr_product_max_quantity = jQuery( 'input[name^=quantity_' + curr_product_id + ']' ).attr( 'max' );

            var total_bundle_quantity = jQuery( '.bundle_button' ).find( '.buttons_added input:nth-child(2)' ).val();
            var added_product_quantity = 0;
            for ( var k = 1; k <= max_div_id; k++ ) {
                var added_product_id = jQuery( '.wdm_added_image_' + k ).attr( 'data-bundled-item-id' );
                if ( curr_product_id == added_product_id ) {
                    added_product_quantity++;
                }
            }
            if ( curr_product_max_quantity != '' && curr_product_max_quantity != 0 ) {
                if ( (added_product_quantity * total_bundle_quantity > curr_product_max_quantity)) {
                    alert(wdm_add_to_cart.quantity_text);
                    e.preventDefault();
                    return false;
                }
            }
        } );

        var wdm_box_empty_count = 0;
        var wdm_box_filled_count = 0;
        for ( var i = 1; i <= max_div_id; i++ ) {
            if ( jQuery( '#wdm_bundle_bundle_item_' + i + ' .wdm-bundle-box-product' ).html() == '' ) {
                wdm_box_empty_count++;
            }
            else{
                wdm_box_filled_count++;
            }
        }
        validate = wdm_add_to_cart.check_bundle_validation;
        
        if ( (wdm_box_empty_count == 0 && (validate == 'no' || validate == '')) || (wdm_box_filled_count >= 1 && validate == 'yes' )) {
            return true;
        }
        else {
            alert( wdm_add_to_cart.fill_box_text );
            e.preventDefault();
            return false;
        }
    } );

} );