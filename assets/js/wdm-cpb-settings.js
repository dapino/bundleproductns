jQuery( document ).ready( function() {
	if (cpb_layout_object.selectedLayout == 'horizontal') {
		hideVerticalFields();
	} else if (cpb_layout_object.selectedLayout == 'vertical') {
		hideHorizontalFields();
	}

	jQuery('#_wdm_desktop_layout').change(function(){
		var changedLayoutPath = jQuery(this).val();
		var temp = changedLayoutPath.split("/");
		var changedLayout = temp[temp.length - 1];

		if (changedLayout == 'vertical') {
			showVerticalFields();
			hideHorizontalFields();
		} else if (changedLayout == 'horizontal') {
			showHorizontalFields();
			hideVerticalFields()		;
		}
	});

	function hideHorizontalFields()
	{
		jQuery('#_wdm_product_item_grid').hide();
		jQuery('#_wdm_item_field').hide();
		jQuery('._wdm_product_item_grid_field').hide();
		jQuery('._wdm_item_field_field').hide();	
	}

	function showHorizontalFields()
	{
		jQuery('#_wdm_product_item_grid').show();
		jQuery('#_wdm_item_field').show();
		jQuery('._wdm_product_item_grid_field').show();
		jQuery('._wdm_item_field_field').show();	
	}

	function hideVerticalFields()
	{
		jQuery('#_wdm_product_grid').hide();
		jQuery('#_wdm_column_field').hide();
		jQuery('._wdm_column_field_field').hide();
		jQuery('._wdm_product_grid_field').hide();		
	}

	function showVerticalFields()
	{
		jQuery('#_wdm_product_grid').show();
		jQuery('#_wdm_column_field').show();
		jQuery('._wdm_column_field_field').show();
		jQuery('._wdm_product_grid_field').show();		
	}

});