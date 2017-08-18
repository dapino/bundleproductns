	jQuery( document ).ready( function () {
	// alert("Hi"+wdm_cpb_layout.selectedLayout);
	if (wdm_cpb_layout.selectedLayout == "horizontal") {
		jQuery('.wdm-bundle-bundle-box').addClass('wdm-bundle-bundle-box-horizontal');
		jQuery('.wdm_product_bundle_container_form').addClass('wdm_product_bundle_horizontal');
	}

	var classes = jQuery('[data-product-cat-id]').map(function() {
		return jQuery(this).data('product-cat-id');
	});

	var uniqueClasses = jQuery.unique(classes);

	// jQuery(uniqueClasses).each(function(i, v) {
	// 	var image = jQuery('.wdm-bundle-product-product-group *[data-product-cat-id="'+v+'"]').data('product-cat-thumbnail');
	// 	var name = jQuery('.wdm-bundle-product-product-group *[data-product-cat-id="'+v+'"]').data('product-cat-name');
	// 	jQuery('.wdm-bundle-product-product-group *[data-product-cat-id="'+v+'"]').wrapAll('<div id="product-cat-'+v+'" class="product-cat-wrap"></div>');
	// 	jQuery('#product-cat-'+v).prepend('<div class="product-cat-image"><img src="'+image+'" alt="'+name+'" /></div><h3 class="product-cat-title">'+name+'</h3>')
	// });
});
