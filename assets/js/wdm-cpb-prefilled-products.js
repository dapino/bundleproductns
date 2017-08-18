jQuery(document).ready(function($){
    var plist_size = $('ul.select2-choices > *').size();
    var maxVal = parseInt($('#_wdm_grid_field').val(), 10);
    var product_list = {};
    $('.wdm_bundle_products_selector .select2-container .select2-choices .selected-option').each( function() {
        var pro_id = parseInt($(this).attr('data-id'));
        var prod_name = $(this).text();
        product_list[pro_id] = prod_name;
    });

    $('#product_field_type > option:selected').each( function() {
        var pro_id = parseInt($(this).val());
        var prod_name = $(this).text();
        product_list[pro_id] = prod_name;
    });

    function addTR()
    {
        var preBody = $(".prefill_table tbody");

        var checkbox_holder = "<tr><td><input type = 'checkbox' class = 'prefill_checkbox' name = 'prod_mandatory[]' value = '0' /></td>";

        var product_holder = "<td><select name='wdm_cpb_products[]' class='prefill_products_holder'>";
        for( var item in product_list) {
            product_holder += "<option value = '"+item+"'>"+product_list[item]+"</option>";
        }
        product_holder += "</select></td>";

        var qty_holder = "<td class = 'prefill_qty'><input type = 'number' name = 'wdm_prefill_qty[]' min = '1' max = '"+maxVal+"' class = 'prefill_qty_id' /></td>";
        var remove_button_holder = "<td><a class='wdm_cpb_rem' href='#' id=''><img class='add_new_row_image' src='" + cpb_prefilled_object.remove_image + "' /></a>";
        var add_button_holder = "<a class='wdm_cpb_add' href='#' id=''><img class='add_new_row_image' src='" + cpb_prefilled_object.add_image + "' /></a>";
        var end_tableRow = "</td></tr>";

        $(checkbox_holder + product_holder + qty_holder + remove_button_holder + add_button_holder + end_tableRow).appendTo(preBody);
        $('.prefill_table').show();        
    }

    function getProductList()
    {
        var new_array = {};

        $('#product_field_type > option:selected').each( function() {
            var pro_id = parseInt($(this).val());
            var prod_name = $(this).text();
            new_array[pro_id] = prod_name;
        });

        return new_array;
    }

    function afterRemoveTr()
    {
        if (!$(".prefill_table tbody tr:last td:last .wdm_cpb_add").length) {
            $(".prefill_table tbody tr:last td:last").append("<a class='wdm_cpb_add' href='#' id=''><img class='add_new_row_image' src='" + cpb_prefilled_object.add_image + "' /></a>");
        }

        var last_row = $('.prefill_table').find('tr:last');

        if (last_row.find("th:last").length) {
            $('.prefill_table').hide();
            $('.prefill_div').hide();
            $('#wdm_prefilled_box').prop('checked', false);
            $('.prefill_table tbody').empty();
        }
    }

    function removeProduct(newId)
    {
        // var newId = parseInt($(current_product).parents('li').find('.selected-option').attr('data-id'));
        if (newId in product_list) {
            delete product_list[newId];
            $("select[name='wdm_cpb_products[]']").each(function(index, object){
                if(parseInt($(this).val()) == newId){
                    $(this).parents('tr').detach();
                    afterRemoveTr();
                }
            });

            $( "select[name='wdm_cpb_products[]']").each(function(){
                $("select[name='wdm_cpb_products[]'] option[value='"+newId+"']").remove();
            });
        }
    }

    $('#prefill_table_id').delegate('.prefill_products_holder', 'change', function(){
        $(this).parents('tr').find('.prefill_checkbox').val(this.value);
    });

    $('#prefill_table_id').delegate('.prefill_checkbox', 'change', function(){

        var associated_product = $(this).parents('tr').find('.prefill_products_holder').find(':selected').val();

        $(this).val(associated_product);
    });

    $('.wc-product-search').change(function(){
     
        var newProduct = null;
        var newId = 0;
        var newName = '';

        // woocommerce 3.0.0 uses $('#product_field_type > option:last')
        if ($('select#product_field_type').length){
            
            var newProductList = getProductList();

            var diff = object_diff(product_list, newProductList);
            //Removing Product 
            if (Object.keys(diff).length !== 0 && diff.constructor === Object) {
                var prod_id = Object.keys(diff)[0];
                removeProduct(prod_id);
                return;
            } else {
                //Adding Product
                var diff = object_diff(newProductList, product_list);
                newId = Object.keys(diff)[0];
                newName = newProductList[newId];
            }

        } else {
             newProduct = $('.wdm_bundle_products_selector .select2-container .select2-choices .selected-option:last');
             newId = parseInt($(newProduct).val());
             newName = $(newProduct).text();

        }
        
        if(newId === 0){
            return;
        }

        if (!(newId in product_list)) {
            product_list[newId] = newName;  
               $( "select[name='wdm_cpb_products[]']").each(function(){
                $("<option></option>", {value: newId, text: newName}).appendTo(this);   
            });
        }
    });

    $('body').on('focus', '.select2-container' , function() { 
        $('a.select2-search-choice-close').click(function(){ 
            removeProduct($(this));
        });      
        
    });

    $('a.select2-search-choice-close').click(function(){  
        removeProduct(this);
    });

    if(cpb_prefilled_object.enablePrefillProducts == 'yes') {
        if ($('.prefill_table tbody tr').length > 0) {
            $('.prefill_table').show();
            $('.prefill_div').show();
        } else {
            $('.prefill_table').hide();
            $('.prefill_div').hide();
        }
    }

    $('#wdm_prefilled_box').change(function() {
        maxVal = parseInt($('#_wdm_grid_field').val(), 10);
        if(this.checked) {
            if($(".prefill_table tbody").is(":empty")) {
                addTR();
                $('.prefill_div').show();
            } else {
                var last_row = $('.prefill_table').find('tr:last');
                if (last_row.find("th:last").length) {
                    addTR();
                    $('.prefill_div').show();
                } else {
                    $('.prefill_div').show();
                    $('.prefill_table').show();
                }
            }
        } else if (!this.checked) {
            $('.prefill_table').hide();
            $('.prefill_div').hide();
        }
    });


    $('#wdm_bundle_product_data').delegate('.wdm_cpb_add', 'click', function(){
        $(this).remove();
        addTR();
        return false;
    });

    $('#wdm_bundle_product_data').delegate('.wdm_cpb_rem', 'click', function(){
        $(this).parents('tr').remove();
        afterRemoveTr();
        return false;
    });

    $('#_wdm_grid_field').on('change', function(){
        maxVal = parseInt($(this).val(), 10);
        $('.prefill_qty .prefill_qty_id').each(function(){
            $(this).attr('max', maxVal);
        });
    });

    $('.prefill_table').delegate('.prefill_qty_id', 'change', function(){

        maxVal = parseInt($('#_wdm_grid_field').val(), 10);
        var qty = parseInt($(this).val(), 10);

        if(qty < 0 || qty > maxVal) {
            $(this).addClass('invalid_qty');
        } else {
            $(this).removeClass('invalid_qty');
        }
    });


// Total quantity of products selected for pre filled boxes should be lesser than or equal to CPB box quantity
    $('#post').submit(function( event ){
        if ( $('#product-type').val() == 'wdm_bundle_product' && $('#wdm_prefilled_box').is(':checked') ) {
            var total_qty = 0;
            maxVal = parseInt($('#_wdm_grid_field').val(), 10);      
            var sldText = '';
            $productIds = {};
            var $validQuantities = true;
            var qtyFlag = false;
            $('.prefill_qty .prefill_qty_id').each(function(){
                if ($(this).val() == '' || $(this).val() == 0) {
                    $(this).addClass('invalid_qty');
                    qtyFlag = true;
                }

                var productId = $(this).closest('tr').find('.prefill_products_holder').val();
                $productIds[productId] = $(this).val();
            });


            if (qtyFlag) {
                alert(cpb_prefilled_object.qty_greater_zero);
                event.preventDefault();
            }

            $.ajax({
            
                type : "POST",
                url : cpb_prefilled_object.ajax_url,  
                data : {
                    'action' : "wdm_is_sold_individual", 
                    'product_ids': $productIds,
                },
                dataType: "json",
                async: false,
                success : function(response){
                    if(!$.isEmptyObject(response)) {
                        for (var item in response) {  
                            $('.prefill_products_holder').each(function(){
                                if($(this).val() == response[item]) {
                                    sldText += "\n"+product_list[response[item]];
                                    //Reset Quantity of Sold individual product to 1
                                    $(this).closest('tr').find('.prefill_qty .prefill_qty_id').addClass('invalid_qty');
                                }
                            });                    
                        }

                        alert(cpb_prefilled_object.sld_ind_text + sldText);
                        event.preventDefault();
                        $validQuantities = false;
                    }
                },
                error : function(){alert ("Sorry :( ");}
            });

            if($validQuantities) {

                $('.prefill_table .prefill_qty_id').each(function(){
                // As 0 qty can not be added to cart if set mandatory
                    if (parseInt($(this).val(), 10) <= 0) {
                        $(this).parents('tr').find('.prefill_checkbox').attr('checked', false);
                    }
                    total_qty += parseInt($(this).val(), 10);
                });

                //Do not submit form if Total Quantity is greater than Box Quantity
                if (total_qty > maxVal && $('#wdm_prefilled_box').is(':checked')) {
                    event.preventDefault();
                    alert(cpb_prefilled_object.total_prefill_qty_text);
                }
            }

        }
    });
});