jQuery( document ).ready( function () {
// jQuery(function () {
    jQuery( 'form#post' ).submit( function ( e ) {
        if ( jQuery( '#product-type' ).val() == 'wdm_bundle_product' ) {
            if ( jQuery( this ).data( 'valid' ) ) {
                return true;
            }
            //var form_data = jQuery('#post').serializeArray();
            var _wdm_grid_field = jQuery( '#_wdm_grid_field' ).val();
            var product_field_type = jQuery( '#product_field_type' ).val();
            var wdm_cpb_regular_price = parseFloat(jQuery('#wdm_reg_price_field').val());
            var wdm_cpb_sale_price = parseFloat(jQuery('#wdm_sale_price_field').val());

            if ( _wdm_grid_field == null || _wdm_grid_field == '' ) {
                alert(cpb_settings_object.text_box_quantity);
                e.preventDefault();
            }
            else if ( product_field_type == null || product_field_type == '' ) {
                alert(cpb_settings_object.text_addon_products);
                e.preventDefault();
            }
            else if ( (wdm_cpb_regular_price == null || wdm_cpb_regular_price == '') && wdm_cpb_regular_price != 0 ) {
                alert(cpb_settings_object.text_regular_price);
                e.preventDefault();
            }
            else if ( wdm_cpb_sale_price >= wdm_cpb_regular_price ) {
                alert(cpb_settings_object.text_sale_price);
                e.preventDefault();
            }
            else{
                return true;
            }
        }
    } );
} );