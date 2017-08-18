jQuery(document).ready(function($){
    jQuery('select option:contains("Wdm_bundle_product")').text('Custom product box');
    
    var act = '';

    $("a[href^='#']").click(function(){        
        if ($(this).attr('href') == '#wdm_bundle_product_data')
           act = 'set';
        else
           act = 'unset';
                 
        $.ajax({
        
            type : "POST",
            url : ajax_object.ajax_url,  
            data : {'action' : "wdm_product_type_cpb", 'act': act, 'product_id': ajax_object.product_id},
            success : function(res){
                
            },
            error : function(){alert ("Sorry :( ");}
        });        
    });

    $( document.body ).on( 'keyup change', '#wdm_sale_price_field.wc_input_price[type=text]', function() {
		var sale_price_field = $( this ), regular_price_field;
		regular_price_field = $( '#wdm_reg_price_field' );
		var sale_price    = parseFloat( window.accounting.unformat( sale_price_field.val(), woocommerce_admin.mon_decimal_point ) );
		var regular_price = parseFloat( window.accounting.unformat( regular_price_field.val(), woocommerce_admin.mon_decimal_point ) );

		if ( sale_price >= regular_price ) {
			$( document.body ).triggerHandler( 'wc_add_error_tip', [ $(this), 'i18_sale_less_than_regular_error' ] );
		} else {
			$( document.body ).triggerHandler( 'wc_remove_error_tip', [ $(this), 'i18_sale_less_than_regular_error' ] );
		}
	});

    jQuery('#_wdm_enable_gift_message').change(function(){
        if(jQuery(this).is(":checked")) {
            jQuery('._wdm_gift_message_label_field').show();
        } else {
            jQuery('._wdm_gift_message_label_field').hide();
        }
    });

    jQuery('#_wdm_enable_gift_message').trigger('change');
});
