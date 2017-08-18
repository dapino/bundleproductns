jQuery(document).ready(function() {

	if(wdm_cpb_scroll.selectedLayout == 'vertical') {
		scrollColumnsOfGrid();		

		jQuery(window).resize(function() {
				scrollColumnsOfGrid();		
		});
	} else if (wdm_cpb_scroll.selectedLayout == 'horizontal') {
		scrollProductLayout();
	}

});

function scrollProductLayout()
{
	 jQuery('.wdm-bundle-bundle-box').stickyScroll({
		container: '.wdm_fix_div',
		offsetBottomValue: offsetBottomValue(),
	});
}

function canBeScrolled()
{
	var gridFirst = jQuery('.wdm-bundle-single-product').first();
    var gridDivTop = gridFirst.offset().top;
    var gridDivHeight = gridFirst.height();
    var productDivTop = jQuery('.bundled_product_summary').first().offset().top;

    if( (productDivTop - gridDivTop) >= gridDivHeight ) {
    	return false;
    }
    return true;
}


/*function scrollColumnsOfGrid() {
	if (canBeScrolled() === true) {
		var cont1height = jQuery('.wdm-bundle-bundle-box').height();
		var cont2height = jQuery('.wdm_product_bundle_container_form').height();
		var priceHeight = jQuery('.bundle_wrap').height();

		var offset = jQuery('.wdm-bundle-bundle-box').offset().top - 50;
		jQuery(window).scroll(function(){
			var windowsc = jQuery(window).scrollTop();
			//When scroll bar is greater than grid layout and less than product layout plus height of grid layout and position of grid top less than product layout height + grid height
			if ((jQuery(window).scrollTop() > offset) && (jQuery(window).scrollTop() <= cont2height+offset) && (jQuery('.wdm-bundle-bundle-box').offset().top <= (cont2height+cont1height))) {
				jQuery('.wdm-bundle-bundle-box').stop().animate({"margin-top": (windowsc-offset) + "px"}, 0);
			}

			if ((jQuery(window).scrollTop() > offset) && (jQuery(window).scrollTop() <= cont2height+offset/2) && (jQuery('.wdm-bundle-bundle-box').offset().top >= (cont2height+cont1height))) {
				jQuery('.wdm-bundle-bundle-box').stop().animate({"margin-top": (windowsc-offset) + "px"}, 0);
			}

			if(jQuery(window).scrollTop() <= 265) {
				jQuery('.wdm-bundle-bundle-box').css('margin-top','0px');
			}

		});
	} else {

		jQuery('.wdm-bundle-bundle-box').css('margin-top','0px');
	}
}*/


function offsetBottomValue()
{	
	var numOfGiftBox = 1 + Math.floor(jQuery(".wdm-bundle-bundle-box").find(".wdm-bundle-single-product").length / 8),
		offsetBottomVal = jQuery(".gift-message-box").outerHeight() + (jQuery(".bundled_product_summary").outerHeight(true) ) ;
	return offsetBottomVal;
}


function scrollColumnsOfGrid() {
	if (canBeScrolled() === true) {
		var giftBoxHeight = jQuery('.wdm-bundle-bundle-box').outerHeight(true),
			gittBoxTop = jQuery('.wdm-bundle-bundle-box').offset().top,
			productsConatinerHeight = jQuery('.wdm-bundle-product-product-group').height(),
			HeaderElementRefrence = jQuery('.wdm-bundle-bundle-box').parents("body").find("header"),
			htmlMarginTop = parseInt(jQuery("html").css("marginTop"));

			function fixedHeaderHeight()
            {  
              if((HeaderElementRefrence.css("position") === "fixed") || (HeaderElementRefrence.children().css("position") === "fixed")  && jQuery(window).scrollTop() > (gittBoxTop/ 2))
              {
                return HeaderElementRefrence.outerHeight(true);
              }
              return 0;
            } 
    
		jQuery(window).scroll(function(){
			var windowsc = jQuery(window).scrollTop();
			if( windowsc >= gittBoxTop - fixedHeaderHeight()  && windowsc <= jQuery('.wdm-bundle-product-product-group').height()+ gittBoxTop - fixedHeaderHeight() - giftBoxHeight)
			{

				jQuery('.wdm-bundle-bundle-box').stop().animate({"margin-top": (windowsc - gittBoxTop + htmlMarginTop + fixedHeaderHeight() ) + "px"}, 0);
			}
			else if( windowsc <= gittBoxTop)
			{
				jQuery('.wdm-bundle-bundle-box').stop().animate({"margin-top":"0px"}, 0);
			}
		});

		
	}
}

