jQuery( document ).ready( function ( $ ) {
    var bundle_stock_status = [ ];
    var sld_ind = {};
    start_price = parseFloat(jQuery('.wdm-bundle-bundle-box').data('bundle-price'));  //base price
   // per_product_pricing_active_enable = wdm_bundle_params.dynamic_pricing_enable;


    $( 'body' ).on( 'quick-view-displayed', function () {

        $( '.bundle_form' ).each( function () {

            $( this ).wc_bundle_form();

        } );

    } );

    $.fn.wc_bundle_form = function () {

        // Listeners

        $( '.bundled_product' )

            .on( 'found_variation', function ( event, variation ) {
                var variations = $( this ).find( '.variations_form' );
                var bundle_id = variations.attr( 'data-bundle-id' );
                var product_id = variations.attr( 'data-product-id' );
                var bundled_item_id = variations.attr( 'data-bundled-item-id' );

                var bundle_price_data = $( '.bundle_form_' + bundle_id ).data( 'bundle_price_data' );

                if ( bundle_price_data[ 'per_product_pricing_active' ] == true ) {
                    // put variation price in price table
                    bundle_price_data[ 'prices' ][ bundled_item_id ] = variation.price;
                    bundle_price_data[ 'regular_prices' ][ bundled_item_id ] = variation.regular_price;
                }

                $( '.bundle_form_' + bundle_id + ' .bundle_wrap' ).find( 'input[name="bundle_variation_id[' + bundled_item_id + ']"]' ).val( variation.variation_id ).change();

                for ( attribute in variation.attributes ) {
                    $( '.bundle_form_' + bundle_id + ' .bundle_wrap' ).find( 'input[name="bundle_' + attribute + '[' + bundled_item_id + ']"]' ).val( variations.find( '.attribute-options select[name="' + attribute + '"]' ).val() );
                }

                attempt_show_bundle( bundle_id );

            } )

            .on( 'woocommerce_update_variation_values', function () {
                var variations = $( this ).find( '.variations_form' );
                var bundle_id = variations.attr( 'data-bundle-id' );
                var bundled_item_id = variations.attr( 'data-bundled-item-id' );

                variations.find( '.bundled_item_wrap input[name="variation_id"]' ).each( function () {
                    if ( $( this ).val() == '' ) {
                        $( '.bundle_form_' + bundle_id + ' .bundle_wrap' ).find( 'input[name="bundle_variation_id[' + bundled_item_id + ']"]' ).val( '' );
                        $( '.bundle_form_' + bundle_id + ' .bundle_wrap' ).slideUp( '200' );
                    }
                } );

            } );


        $( '.bundled_product .cart' )

            .on( 'woocommerce-product-addons-update', function () {

                var addon = $( this ).closest( '.product-addon' );
                var item = $( this ).closest( '.cart' );
                var bundle_id = item.attr( 'data-bundle-id' );

                attempt_show_bundle( bundle_id );

            } )

            .on( 'woocommerce-nyp-updated-item', function () {

                var item = $( this );
                var bundle_id = item.attr( 'data-bundle-id' );
                var item_id = item.attr( 'data-bundled-item-id' );
                var nyp = item.find( '.nyp' );

                if ( nyp.is( ":visible" ) ) {

                    var bundle_price_data = $( '.bundle_form_' + bundle_id ).data( 'bundle_price_data' );

                    bundle_price_data[ 'prices' ][item_id] = nyp.data( 'price' );

                    attempt_show_bundle( bundle_id );
                }

            } );


        $( '.bundle_form' )

            .on( 'woocommerce-nyp-updated-item', function () {

                var item = $( this );
                var bundle_id = item.attr( 'data-bundle-id' );
                var nyp = item.find( '.nyp' );

                if ( nyp.is( ":visible" ) ) {

                    var bundle_price_data = $( '.bundle_form_' + bundle_id ).data( 'bundle_price_data' );

                    bundle_price_data[ 'total' ] = nyp.data( 'price' );

                    attempt_show_bundle( bundle_id );
                }

            } );


        /**
         * Initial states and loading
         */

        // Add-ons support: move totals

        var addons_totals = $( this ).find( '#product-addons-total' );

        $( this ).find( '.bundle_price' ).after( addons_totals );

        var bundle_id = $( this ).attr( 'data-bundle-id' );

        if ( $( this ).find( '.bundle_wrap p.stock' ).length > 0 )
            bundle_stock_status[bundle_id] = $( this ).find( '.bundle_wrap p.stock' ).clone().wrap( '<p>' ).parent().html();

        // Init addons - not needed anymore since filtered getPrice returns the right result

        // Init variations JS and addons
        $( this ).parent().find( '.variations select' ).change();

        $( this ).parent().find( 'input.nyp' ).change();

        if ( check_all_simple( bundle_id ) )
            attempt_show_bundle( bundle_id );

    }


    function attempt_show_bundle( bundle_id ) {

        var all_set = true;

        var addons_prices = [ ];

        // Save addons prices
        $( '.bundle_form_' + bundle_id ).parent().find( '.bundled_product .cart' ).each( function () {

            var item = $( this );
            var item_id = $( this ).attr( 'data-bundled-item-id' );

            addons_prices[ item_id ] = 0;

            item.find( '.addon' ).each( function () {
                var addon_cost = 0;

                if ( $( this ).is( '.addon-custom-price' ) ) {
                    addon_cost = $( this ).val();
                } else if ( $( this ).is( '.addon-input_multiplier' ) ) {
                    if ( isNaN( $( this ).val() ) || $( this ).val() == "" ) { // Number inputs return blank when invalid
                        $( this ).val( '' );
                        $( this ).closest( 'p' ).find( '.addon-alert' ).show();
                    } else {
                        if ( $( this ).val() != "" ) {
                            $( this ).val( Math.ceil( $( this ).val() ) );
                        }
                        $( this ).closest( 'p' ).find( '.addon-alert' ).hide();
                    }
                    addon_cost = $( this ).data( 'price' ) * $( this ).val();
                } else if ( $( this ).is( '.addon-checkbox, .addon-radio' ) ) {
                    if ( $( this ).is( ':checked' ) )
                        addon_cost = $( this ).data( 'price' );
                } else if ( $( this ).is( '.addon-select' ) ) {
                    if ( $( this ).val() )
                        addon_cost = $( this ).find( 'option:selected' ).data( 'price' );
                } else {
                    if ( $( this ).val() )
                        addon_cost = $( this ).data( 'price' );
                }

                if ( !addon_cost )
                    addon_cost = 0;

                addons_prices[ item_id ] = parseFloat( addons_prices[ item_id ] ) + parseFloat( addon_cost );

            } );

        } );

        $( '.bundle_form_' + bundle_id ).parent().find( '.variations select' ).each( function () {
            if ( $( this ).val().length == 0 ) {
                all_set = false;
            }
        } );

        if ( all_set ) {

            var bundle_price_data = $( '.bundle_form_' + bundle_id ).data( 'bundle_price_data' );
            var bundled_item_quantities = $( '.bundle_form_' + bundle_id ).data( 'bundled_item_quantities' );

            if ( ( bundle_price_data[ 'per_product_pricing_active' ] == false ) && ( bundle_price_data[ 'total' ] === '' ) )
                return;

            if ( bundle_price_data[ 'per_product_pricing_active' ] == true ) {
                bundle_price_data[ 'total' ] = 0;
                bundle_price_data[ 'regular_total' ] = 0;
                for ( prod_id in bundle_price_data[ 'prices' ] ) {
                    bundle_price_data[ 'total' ] += ( parseFloat( bundle_price_data[ 'prices' ][ prod_id ] ) + parseFloat( addons_prices[ prod_id ] ) ) * bundled_item_quantities[ prod_id ];
                    bundle_price_data[ 'regular_total' ] += ( parseFloat( bundle_price_data[ 'regular_prices' ][ prod_id ] ) + parseFloat( addons_prices[ prod_id ] ) ) * bundled_item_quantities[ prod_id ];
                }
            } else {
                bundle_price_data[ 'total_backup' ] = parseFloat( bundle_price_data[ 'total' ] );
                bundle_price_data[ 'regular_total_backup' ] = parseFloat( bundle_price_data[ 'regular_total' ] );
                for ( item_id in addons_prices ) {
                    bundle_price_data[ 'total' ] += parseFloat( addons_prices[ item_id ] ) * bundled_item_quantities[ item_id ];
                    bundle_price_data[ 'regular_total' ] += parseFloat( addons_prices[ item_id ] ) * bundled_item_quantities[ item_id ];
                }
            }

            $( '.bundle_form_' + bundle_id + ' #product-addons-total' ).data( 'price', bundle_price_data[ 'total' ] );
            $( '.bundle_form_' + bundle_id ).trigger( 'woocommerce-product-addons-update' );

            if ( bundle_price_data[ 'total' ] == 0 )
                $( '.bundle_form_' + bundle_id + ' .bundle_price' ).html( '<p class="price"><span class="total">' + wdm_bundle_params.i18n_total + '</span>' + wdm_bundle_params.i18n_free + '</p>' );
            else {

                var sales_price = number_format( bundle_price_data[ 'total' ] );

                var regular_price = number_format( bundle_price_data[ 'regular_total' ] );

                var remove = wdm_bundle_params.currency_format_decimal_sep;

                if ( wdm_bundle_params.currency_format_trim_zeros == 'yes' && wdm_bundle_params.currency_format_num_decimals > 0 ) {

                    for ( var i = 0; i < wdm_bundle_params.currency_format_num_decimals; i++ ) {
                        remove = remove + '0';
                    }

                    sales_price = sales_price.replace( remove, '' );
                    regular_price = regular_price.replace( remove, '' );
                }

                var sales_price_format = '';
                var regular_price_format = '';

                if ( wdm_bundle_params.currency_position == 'left' ) {
                    sales_price_format = '<span class="amount">' + wdm_bundle_params.currency_symbol + sales_price + '</span>';
                    regular_price_format = '<span class="amount">' + wdm_bundle_params.currency_symbol + regular_price + '</span>';
                }
                else if ( wdm_bundle_params.currency_position == 'right' ) {
                    sales_price_format = '<span class="amount">' + sales_price + wdm_bundle_params.currency_symbol + '</span>';
                    regular_price_format = '<span class="amount">' + regular_price + wdm_bundle_params.currency_symbol + '</span>';
                }
                else if ( wdm_bundle_params.currency_position == 'left_space' ) {
                    sales_price_format = '<span class="amount">' + wdm_bundle_params.currency_symbol + '&nbsp;' + sales_price + '</span>';
                    regular_price_format = '<span class="amount">' + wdm_bundle_params.currency_symbol + '&nbsp;' + regular_price + '</span>';
                }
                else if ( wdm_bundle_params.currency_position == 'right_space' ) {
                    sales_price_format = '<span class="amount">' + sales_price + '&nbsp;' + wdm_bundle_params.currency_symbol + '</span>';
                    regular_price_format = '<span class="amount">' + regular_price + '&nbsp;' + wdm_bundle_params.currency_symbol + '</span>';
                }

                if ( bundle_price_data[ 'regular_total' ] > bundle_price_data[ 'total' ] ) {
                    $( '.bundle_form_' + bundle_id + ' .bundle_price' ).html( '<p class="price">' + bundle_price_data[ 'price_string' ].replace( '%s', '<span class="total">' + wdm_bundle_params.i18n_total + '</span><del>' + regular_price_format + '</del> <ins>' + sales_price_format + '</ins>' ) + '</p>' );
                } else {
                    $( '.bundle_form_' + bundle_id + ' .bundle_price' ).html( '<p class="price">' + bundle_price_data[ 'price_string' ].replace( '%s', '<span class="total">' + wdm_bundle_params.i18n_total + '</span>' + sales_price_format ) + '</p>' );
                }
            }

            // reset bundle stock status
            $( '.bundle_form_' + bundle_id + ' .bundle_wrap p.stock' ).replaceWith( bundle_stock_status[ bundle_id ] );

            // set bundle stock status as out of stock if any selected variation is out of stock
            $( '.bundle_form_' + bundle_id ).parent().find( '.bundled_product .cart' ).each( function () {

                var $item_stock_p = $( this ).find( 'p.stock' );

                if ( $item_stock_p.hasClass( 'out-of-stock' ) ) {
                    if ( $( '.bundle_form_' + bundle_id + ' .bundle_wrap p.stock' ).length > 0 ) {
                        $( '.bundle_form_' + bundle_id + ' .bundle_wrap p.stock' ).replaceWith( $item_stock_p.clone().html( wdm_bundle_params.i18n_partially_out_of_stock ) );
                    } else {
                        $( '.bundle_form_' + bundle_id + ' .bundle_wrap .bundle_price' ).after( $item_stock_p.clone().html( wdm_bundle_params.i18n_partially_out_of_stock ) );
                    }
                }

                if ( $item_stock_p.hasClass( 'available-on-backorder' ) && !$( '.bundle_form_' + bundle_id + ' .bundle_wrap p.stock' ).hasClass( 'out-of-stock' ) ) {
                    if ( $( '.bundle_form_' + bundle_id + ' .bundle_wrap p.stock' ).length > 0 ) {
                        $( '.bundle_form_' + bundle_id + ' .bundle_wrap p.stock' ).replaceWith( $item_stock_p.clone().html( wdm_bundle_params.i18n_partially_on_backorder ) );
                    } else {
                        $( '.bundle_form_' + bundle_id + ' .bundle_wrap .bundle_price' ).after( $item_stock_p.clone().html( wdm_bundle_params.i18n_partially_on_backorder ) );
                    }
                }

            } );

            if ( $( '.bundle_form_' + bundle_id + ' .bundle_wrap p.stock' ).hasClass( 'out-of-stock' ) )
                $( '.bundle_form_' + bundle_id + ' .bundle_button' ).hide();
            else
                $( '.bundle_form_' + bundle_id + ' .bundle_button' ).show();

            $( '.bundle_form_' + bundle_id + ' .bundle_wrap' ).slideDown( '200' ).trigger( 'show_bundle' );

            bundle_price_data[ 'total' ] = bundle_price_data[ 'total_backup' ];
            bundle_price_data[ 'regular_total' ] = bundle_price_data[ 'regular_total_backup' ];
        }
    }

    function check_all_simple( bundle_id ) {

        var bundle_price_data = $( '.bundle_form_' + bundle_id ).data( 'bundle_price_data' );

        if ( typeof bundle_price_data == 'undefined' ) {
            return false;
        }
        if ( bundle_price_data[ 'prices' ].length < 1 ) {
            return false;
        }
        if ( $( '.bundle_form_' + bundle_id + ' input[value="variable"]' ).length > 0 ) {
            return false;
        }
        return true;
    }


    /**
     * Function for sale individual
     */
    function canProductBeAdded( item_id )
    {
        if ( sld_ind[ item_id ] == undefined) {
            return true;
        }
        return false;
    }

    /**
     * Helper functions for variations
     */

    // function number_format( number ) {

    //     var decimals = wdm_bundle_params.currency_format_num_decimals;
    //     var decimal_sep = wdm_bundle_params.currency_format_decimal_sep;
    //     var thousands_sep = wdm_bundle_params.currency_format_thousand_sep;

    //     var n = number, c = isNaN( decimals = Math.abs( decimals ) ) ? 2 : decimals;
    //     var d = decimal_sep == undefined ? "," : decimal_sep;
    //     var t = thousands_sep == undefined ? "." : thousands_sep, s = n < 0 ? "-" : "";
    //     var i = parseInt( n = Math.abs( +n || 0 ).toFixed( c ) ) + "", j = ( j = i.length ) > 3 ? j % 3 : 0;

    //     return s + ( j ? i.substr( 0, j ) + t : "" ) + i.substr( j ).replace( /(\d{3})(?=\d)/g, "$1" + t ) + ( c ? d + Math.abs( n - i ).toFixed( c ).slice( 2 ) : "" );
    // }


    $( '.bundle_form' ).each( function () {

        $( this ).wc_bundle_form();

    } );

    $('.wdm-bundle-single-product').on('mouseenter', function(){
        $(this).find('.cpb-plus-minus').show();
    });

    $('.wdm-bundle-single-product').on('mouseleave', function(){
        $(this).find('.cpb-plus-minus').hide();
    });

    $('.images').on('mouseenter', function(){
        $(this).find('.cpb-plus-minus').show();
    });

    $('.images').on('mouseleave', function(){
        $(this).find('.cpb-plus-minus').hide();
    });

    //When gift message is changed, modify its value in all modes (desktop as well as mobile)
    $('.cpb_gift_message').change(function(){
        $('.cpb_gift_message').val( $(this).val() );
    })

    // CPB jquery
    var per_product_pricing_active_enable = wdm_bundle_params.dynamic_pricing_enable;

    // When User clicks on the Bundle Product Image.
    $( '.wdm_simple_product_image, .plus, .images' ).on( "click", function (event) {
        if (isGiftBoxEmpty()) {
            var max_div_id = $( '.wdm-bundle-single-product:last-child()' ).attr( 'id' ).split( '_' );
            max_div_id = max_div_id[4];
            var isi = $(this).hasClass('wdm-product-sold-individually');
            var cpb_pid = $( '.bundled_product_summary' ).attr( 'data-product-id' );
            var $this = $( this ).closest( ".bundled_product_summary" );
            var item_id = $this.find( ".cart" ).attr( 'data-bundled-item-id' );
            var bundle_id = $this.find( ".cart" ).attr( 'data-bundle-id' );
            var temp_max_quantity = $this.find( ".buttons_added" ).find( ".input-text" ).attr( "max" );
            var item_quantity_max = (typeof temp_max_quantity === 'undefined') ? "" : temp_max_quantity;// because some sites had this issue where initial 0 values is read as '' or undefined
            var item_quantity_current = $this.find( ".buttons_added" ).find( ".input-text" ).val();
            
            if (item_quantity_current == '' || item_quantity_current == undefined) {
                item_quantity_current = 0;                 // because some sites had this issue where initial 0 values is read as '' or undefined
            }
        
            var stock_in_out = $this.find( ".wdm_stock" ).html();
            var counter = 0;
            if ( stock_in_out != "Out of stock" && canProductBeAdded(item_id) && !$this.hasClass('wdm-no-stock')) {
                //If sold individual set flag for first time
                if (isi) {
                    sld_ind[item_id] = 1;
                }

                if ( (parseInt( item_quantity_current ) < parseInt( item_quantity_max )) || ( (parseInt( item_quantity_current ) >= parseInt( item_quantity_max )) && $( this ).closest( ".bundled_product_summary" ).hasClass('allow_notify') ) || item_quantity_max == "" ) {
                    for ( var i = 0; i <= max_div_id; i++ ) {
                        if ( $( "#wdm_bundle_bundle_item_" + i + " .wdm-bundle-box-product" ).html() == "" ) {

                            var product_image_div = '<div class = "wdm_box_item wdm_added_image_' + i + ' wdm_filled_product_' + item_id + '" data-bundled-item-id = ' + item_id + ' data-bundle-id = ' + bundle_id + '" data-product-price = "' + $this.data( 'product-price' ) + '" ><div class="cpb-plus-minus"><div class="cpb-circle"><div class="cpb-horizontal"></div></div></div>';
                            product_image_div += $this.find( ".images" ).find( ".zoom" ).html();
                            product_image_div += '</div>';
                            $( "#wdm_bundle_bundle_item_" + i + " .wdm-bundle-box-product" ).append( product_image_div );
                            $( "#wdm_bundle_bundle_item_" + i ).css( "display", "none" );
                            $( "#wdm_bundle_bundle_item_" + i ).fadeIn( 'slow' );
                            $("#wdm_bundle_bundle_item_"+i).addClass('wdm-product-added');
                            if ( per_product_pricing_active_enable == "yes" ) {

                                var product_price = parseFloat( $this.data( 'product-price' ) );

                                var new_cpb_price = get_added_price( product_price );
                                jQuery('.wdm-bundle-bundle-box').data('bundle-price', new_cpb_price);
                                var $option_value = 0;
                                
                                $opt_val =   $('.product-addon-totals .amount').text();
                                
                                if ($opt_val) {
                                    // $option_value = parseFloat($opt_val.replace(wdm_bundle_params.currency_symbol, ""));
                                    $opt_val = $opt_val.replace( wdm_bundle_params.currency_format_symbol, '' );
                                    $opt_val = $opt_val.replace( wdm_bundle_params.currency_format_thousand_sep, '' );
                                    $opt_val = $opt_val.replace( wdm_bundle_params.currency_format_decimal_sep, '.' );
                                    $opt_val = $opt_val.replace(/[^0-9\.]/g, '');
                                    $option_value = parseFloat( $opt_val );
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
                            if(!canProductBeAdded(item_id)) {
                                $( this ).closest( ".bundled_product_summary" ).addClass( "wdm-no-stock" );
                            }

                            if ( $( this ).hasClass( 'plus' ) == false ) {
                                $this.find( ".buttons_added" ).find( ".input-text" ).val( parseInt( item_quantity_current ) + 1 );
                            }
                            counter++;

                            if(event.hasOwnProperty('originalEvent')) {
                                cpbMobileListLayout.addProductInMobileBundle(item_id);
                            }
                            
                            break;
                        }

                    }
                    // If event is binded with plus button then following condition will be true. won't affect as plus minus buttons are hidden
                    if ( counter == 0 && $( this ).hasClass( 'plus' ) == true ) {

                        $this.find( ".buttons_added" ).find( ".input-text" ).val( parseInt( item_quantity_current ) - 1 );

                    }
                }
            }
        } else {
            snackbar(wdm_bundle_params.giftboxFullMsg);
        }

    } );

    $( '.wdm-bundle-single-product' ).on( "click", function (event) {
        $prefillOOS = $(this).find('.wdm_box_item').hasClass('prefill-out-stock');
        if (!$(this).find('.wdm_box_item').hasClass('wdm-prefill-mandatory')) {
            var item_id = $( this ).find( ".wdm_box_item" ).attr( "data-bundled-item-id" );
            var item_quantity_current = $( "input[name^=quantity_" + item_id + "]" ).val();
            var $this = $( this ).find( ".wdm_box_item" );
            if ( parseInt( item_quantity_current ) > 0 ) {
                $( "input[name^=quantity_" + item_id + "]" ).val( parseInt( item_quantity_current ) - 1 );
                
                if(event.hasOwnProperty('originalEvent')) {
                    cpbMobileListLayout.removeProductFromMobileBundle(item_id);
                }
                
            }
            if( sld_ind[ item_id ] != undefined) {
                // sld_ind.splice(item_id, 1);
                delete sld_ind[item_id];
            }
            if ( $( this ).find( ".wdm_box_item" ).length > 0 ) {
                $( this ).find( ".wdm-bundle-box-product" ).empty();
                $( this ).css( "display", "none" );
                $( this ).fadeIn( 'slow' );
                $(this).removeClass('wdm-product-added');
                if ( per_product_pricing_active_enable == "yes" ) {
                    var product_price = parseFloat( $this.data( 'product-price' ) );

                    var new_cpb_price = get_removed_price( product_price );
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
                if (!$prefillOOS) {
                    $( "input[name^=quantity_" + item_id + "]" ).closest( ".bundled_product_summary" ).removeClass( "wdm-no-stock" );
                }
            }
        }
    } );

    $( ".minus" ).on( "click", function () {
        var max_div_id = $( '.wdm-bundle-single-product:last-child()' ).attr( 'id' ).split( '_' );
        max_div_id = max_div_id[4];
        $this = $( this ).closest( ".bundled_product_summary" );
        var item_id = $this.find( ".cart" ).attr( 'data-bundled-item-id' );
        var bundle_id = $this.find( ".cart" ).attr( 'data-bundle-id' );
        for ( var i = 0; i <= max_div_id; i++ ) {
            if ( $( "#wdm_bundle_bundle_item_" + i ).find( ".wdm_added_image_" + i ).attr( "data-bundled-item-id" ) == item_id ) {
                $( "#wdm_bundle_bundle_item_" + i ).find( ".wdm-bundle-box-product" ).empty();
                $( "#wdm_bundle_bundle_item_" + i ).css( "display", "none" );
                ;
                $( "#wdm_bundle_bundle_item_" + i ).fadeIn( 'slow' );
                $( this ).closest( ".bundled_product_summary" ).removeClass( "wdm-no-stock" );
                break;
            }
        }

    } );


    $('.bundle_button .qty').change(function(e){ 

        //Syncing Main Products Quantity between different layouts viz Mobile Layout, Desktop Layout etc
        $('.bundle_button .qty').val($(this).val());    
        
        if ( per_product_pricing_active_enable == "yes" ) {
            
            $overall_qty = $(this).val();
            $total = 0;
            $option_value = 0;
            
            $('.wdm-bundle-bundle-box .wdm-product-added .wdm_box_item').each(function(){    
                $total += parseInt($(this).attr('data-product-price')) * $overall_qty;      
            });
            
            if (start_price > 0) {
                $total += start_price * $overall_qty;
            }
     
            jQuery('.wdm-bundle-bundle-box').data('bundle-price', $total);
            $('meta[itemprop="price"]').attr('content', $total );
            
            $opt_val =   $('.product-addon-totals .amount').text();
         
            if ($opt_val) {
               $option_value = parseFloat($opt_val.replace(wdm_bundle_params.currency_symbol, ""));
            }
            
            
            $total += $option_value;
            
            if ($total >= 0) {    
                var new_price = wdm_get_price_format($total);
                    if( wdm_bundle_params.wdm_bundle_on_sale ){
                    $( ".price" ).find( "ins .amount" ).html( new_price );
                    }
                    else{
                    $( ".price" ).find( ".amount" ).html( new_price );
                    }
            }
        }
        else
        {
             $overall_qty = $(this).val();
            
             $total = start_price * $overall_qty;
             jQuery('.wdm-bundle-bundle-box').data('bundle-price', $total );
             $('meta[itemprop="price"]').attr('content', $total );
            
            $option_value = 0;
             
             $opt_val =   $('.product-addon-totals .amount').text();
         
            if ($opt_val) {
               $option_value = parseFloat($opt_val.replace(wdm_bundle_params.currency_symbol, ""));
            }
       
            $total += $option_value;
             
            if ($total >= 0) {
                
                var new_price = wdm_get_price_format($total);
                    if( wdm_bundle_params.wdm_bundle_on_sale ){
                    $( ".price" ).find( "ins .amount" ).html( new_price );
                    }
                    else{
                    $( ".price" ).find( ".amount" ).html( new_price );
                    }
            } 
        }
    });
        
    $('#product-addons-total').on('DOMSubtreeModified',function(){
        $overall_qty = $('.bundle_button .qty').val();

        if ( per_product_pricing_active_enable == "yes" ) {
                

            $total = 0;
            $option_value = 0;
            
            $('.wdm-bundle-bundle-box .wdm-product-added .wdm_box_item').each(function(){    
                $total += parseInt($(this).attr('data-product-price')) * $overall_qty;      
            });
            
            if (start_price > 0) {
                $total += start_price * $overall_qty;
            }
     jQuery('.wdm-bundle-bundle-box').data('bundle-price', $total );
            $('meta[itemprop="price"]').attr('content', $total );
            
            $opt_val =   $('.product-addon-totals .amount').text();
         
            if ($opt_val) {
               $option_value = parseFloat($opt_val.replace(wdm_bundle_params.currency_symbol, ""));
            }
            
            
            $total += $option_value;
            
            if ($total >= 0) {
                
                var new_price = wdm_get_price_format($total);
                    if( wdm_bundle_params.wdm_bundle_on_sale ){
                    $( ".price" ).find( "ins .amount" ).html( new_price );
                    }
                    else{
                    $( ".price" ).find( ".amount" ).html( new_price );
                    }
            }
        }
        else
        {
             $total = start_price * $overall_qty;
            
              $option_value = 0;
            
            jQuery('.wdm-bundle-bundle-box').data('bundle-price', $total );
             $('meta[itemprop="price"]').attr('content', $total );
            
             
             $opt_val =   $('.product-addon-totals .amount').text();
         
            if ($opt_val) {
               $option_value = parseFloat($opt_val.replace(wdm_bundle_params.currency_symbol, ""));
            }
       
            $total += $option_value;
             
            if ($total >= 0) {
                
                var new_price = wdm_get_price_format($total);
                    if( wdm_bundle_params.wdm_bundle_on_sale ){
                    $( ".price" ).find( "ins .amount" ).html( new_price );
                    }
                    else{
                    $( ".price" ).find( ".amount" ).html( new_price );
                    }
            } 
        }
    });
    
    $('.bundle_button .qty').keypress(function(){
        if ( per_product_pricing_active_enable == "yes" ) {
            
            $overall_qty = $(this).val();
            $total = 0;
            $option_value = 0;
            
            $('.wdm-bundle-bundle-box .wdm-product-added .wdm_box_item').each(function(){    
                $total += parseInt($(this).attr('data-product-price')) * $overall_qty;      
            });
            
            if (start_price > 0) {
                $total += start_price * $overall_qty;
            }
     jQuery('.wdm-bundle-bundle-box').data('bundle-price', $total );
            $('meta[itemprop="price"]').attr('content', $total );
            
            $opt_val =   $('.product-addon-totals .amount').text();
         
            if ($opt_val) {
               $option_value = parseFloat($opt_val.replace(wdm_bundle_params.currency_symbol, ""));
            }
            
            
            $total += $option_value;
            
            if ($total >= 0) {    
                var new_price = wdm_get_price_format($total);
                    if( wdm_bundle_params.wdm_bundle_on_sale ){
                    $( ".price" ).find( "ins .amount" ).html( new_price );
                    }
                    else{
                    $( ".price" ).find( ".amount" ).html( new_price );
                    }
            }
        }
        else
        {
             $overall_qty = $(this).val();
            
             $total = start_price * $overall_qty;
            jQuery('.wdm-bundle-bundle-box').data('bundle-price', $total );
             $('meta[itemprop="price"]').attr('content', $total );
            
            $option_value = 0;
             
             $opt_val =   $('.product-addon-totals .amount').text();
         
            if ($opt_val) {
               $option_value = parseFloat($opt_val.replace(wdm_bundle_params.currency_symbol, ""));
            }

            $total += $option_value;
             
            if ($total >= 0) {
                
                var new_price = wdm_get_price_format($total);
                    if( wdm_bundle_params.wdm_bundle_on_sale ){
                    $( ".price" ).find( "ins .amount" ).html( new_price );
                    }
                    else{
                    $( ".price" ).find( ".amount" ).html( new_price );
                    }
            } 
        }
    });

    /*
     * Function to check whether the Gift box is empty
     */
    function isGiftBoxEmpty() {
        var i = 1, emptyFlag = false;
        jQuery('.wdm-bundle-single-product').each(function(){
            i++;
            if ( $( "#wdm_bundle_bundle_item_" + i + " .wdm-bundle-box-product" ).html() == "" ) {
                emptyFlag = true;
            }
        });
        return emptyFlag;
    }
    
    /*
     * Function for finding out the aspect ratio of WC product thumbnail and applying it to the grid
     *
     */
    function wdm_set_grid_aspect_ratio(){
        var size_obj = wdm_bundle_params.product_thumb_size;
        var size_width = size_obj.width;
        var size_height = size_obj.height;
        var aspect_ratio = size_height / size_width;
        var padding = aspect_ratio*100;
        jQuery('head').append('<style>.wdm-bundle-single-product::before{padding-top:'+padding+'%}</style>');
    }

    wdm_set_grid_aspect_ratio();

    function get_removed_price( $product_price ) {
        var old_price = jQuery('.wdm-bundle-bundle-box').data('bundle-price');
        var overall_qty = parseInt($('.bundle_button').find('.qty').val());
        
        var new_price = get_new_price( $product_price, old_price );
        jQuery('.wdm-bundle-bundle-box').data('bundle-price', new_price );
        $('meta[itemprop="price"]').attr('content', new_price );
        return new_price;
    }

    function get_new_price( $product_price, old_price )
    {
        var overall_qty = parseInt($('.bundle_button').find('.qty').val());
        var new_price = parseFloat( parseFloat( old_price ) - parseFloat( $product_price ) * overall_qty );
        return new_price;
    }
} );
