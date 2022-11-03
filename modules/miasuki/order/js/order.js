jQuery(document).ready(function($){
	function update_product_table(message, product, product_index){
		if (message=='Product Not Found') {
				alert(message);
			}else if(message=='Product Found'){
				var add_flag = true;
				$('.order_product_sku').each(function(index) {
					if ($( this ).val()==product.magento_sku) {
						alert('Product Already Added');
						add_flag = false;
					}
				});
				if (add_flag) {
					var field_magento_sku = `
						<div class="form-item js-form-item form-type-textfield js-form-type-textfield form-item-products-table-product-`+product_index+`-magento-sku js-form-item-products-table-product-`+product_index+`-magento-sku form-no-label form-group">
							<input readonly="readonly" data-drupal-selector="edit-products-table-product-`+product_index+`-magento-sku" class="form-text form-control order_product_sku" type="text" id="edit-products-table-product-`+product_index+`-magento-sku" name="products_table[product_`+product_index+`][magento_sku]" value="`+product.magento_sku+`" size="60" maxlength="128">
							<input type="hidden" name="products_table[product_`+product_index+`][id]" value="`+product.id+`">
						</div>
					`;
					switch($('#edit-currency').val()) {
						case 'USD':
							price = product.us_price;
							break;
						case 'HKD':
							price = product.hk_price;
							break;
						case 'EUR':
							price = product.eu_price;
							break;
						case 'GBP':
							price = product.uk_price;
							break;
						case 'CNY':
							price = product.cn_price;
							break;
						default:
							price = product.us_price;
					}
					var field_original_price = `
						<div class="form-item js-form-item form-type-textfield js-form-type-textfield form-item-products-table-product-`+product_index+`-original-price js-form-item-products-table-product-`+product_index+`-original-price form-no-label form-group">
		 					<input readonly="readonly" data-drupal-selector="edit-products-table-product-`+product_index+`-original-price" class="form-text form-control" type="text" id="edit-products-table-product-`+product_index+`-original-price" name="products_table[product_`+product_index+`][original_price]" value="`+price+`" size="60" maxlength="128">
						</div>
					`;
					var field_price = `
						<div class="form-item js-form-item form-type-textfield js-form-type-textfield form-item-products-table-product-`+product_index+`-price js-form-item-products-table-product-`+product_index+`-price form-no-label form-group">
		 					<input data-drupal-selector="edit-products-table-product-`+product_index+`-price" class="form-text form-control" type="text" id="edit-products-table-product-`+product_index+`-price" name="products_table[product_`+product_index+`][price]" value="`+price+`" size="60" maxlength="128">
						</div>
					`;
					var field_qty = `
						<div class="form-inline form-item js-form-item form-type-number js-form-type-number form-item-products-table-product-`+product_index+`-qty js-form-item-products-table-product-1-qty form-no-label form-group">
							<input data-drupal-selector="edit-products-table-product-`+product_index+`-qty" class="form-number form-control" type="number" id="edit-products-table-product-`+product_index+`-qty" name="products_table[product_`+product_index+`][qty]" value="1" step="1" min="1">
						</div>
					`;

					var del_button = `
						<button onclick="return false;" class="offline_order_product_del button js-form-submit form-submit btn-danger btn icon-before" data-drupal-selector="edit-products-table-product-`+product_index+`-del" type="submit" id="edit-products-table-product-`+product_index+`-del" name="op" value="Delete"><span class="icon glyphicon glyphicon-trash" aria-hidden="true"></span>
		Delete</button>
					`;
					if ($('#edit-products-table tbody').length==0) {
						$('#edit-products-table').append('<tbody><tr><td>'+field_magento_sku+'</td><td>'+field_original_price+'</td><td>'+field_price+'</td><td class="form-inline">'+field_qty+'</td><td>'+del_button+'</td></tr></tbody>');
					}else{
						$('#edit-products-table tbody').append('<tr><td>'+field_magento_sku+'</td><td>'+field_original_price+'</td><td>'+field_price+'</td><td class="form-inline">'+field_qty+'</td><td>'+del_button+'</td></tr>');
					}
					$('#product_count').val(product_index);
				}
			}
	}
	$(document).on('click','#offline_order_product_add',function(e) {
		var product_index = parseInt($('#product_count').val())+1;
		
		var magento_sku = $('#edit-magento-sku').val();
		var barcode = $('#edit-barcode').val();
		var message = '';
		var product = null;
		var price = null;
		if (magento_sku!='') {
			$.ajax({
		        url: window.location.origin+drupalSettings.path.baseUrl+'/product/ajax_simple_product_info/'+magento_sku,
		        success: function (response) {
		        	console.log(response);
		        	update_product_table(response[0].message,response[0].result, product_index);
		        },
		        error: function (request, status, error) {  
		            console.log('error'); 
		            console.log(request); 
		            console.log(status); 
		            console.log(error); 
		        }
		    });
		}else if(barcode!=''){
			$.ajax({
		        url: window.location.origin+drupalSettings.path.baseUrl+'/product/ajax_simple_product_info_bybarcode/'+barcode,
		        success: function (response) {
		        	update_product_table(response[0].message,response[0].result, product_index);
		        },
		        error: function (request, status, error) {  
		            console.log('error'); 
		            console.log(request); 
		            console.log(status); 
		            console.log(error); 
		        }
		    });
		}
	});
	$(document).on('click','.offline_order_product_del',function(e) {
		$(this).parents('tr').remove();
		var product_count = parseInt($('#product_count').val())-1;
		$('#product_count').val(product_count);
	});
	$(document).on('click','.seo-control',function(e) {
		var tr = $(this).closest('tr');
		var row = datatable.row(tr);
		var data = {
			link:$(this).attr('data-link'),
			meta_title:$(this).attr('data-meta_title'),
			meta_description:$(this).attr('data-meta_description'),
			meta_keywords:$(this).attr('data-meta_keywords'),
			img_alt:$(this).attr('data-img_alt')
		};
		if ( row.child.isShown() ) {
            row.child.hide();
            tr.removeClass('shown');
        }
        else {
            row.child( format_seo_child_table(data) ).show();
            tr.addClass('shown');
        }
	});
	function format_seo_child_table(data){
		return '<table style="padding-left:50px;width:100%;">'+
			'<tr>'+
	            '<td style="width:10%;">Link</td>'+
	            '<td style="text-align: left;">'+data.link+'</td>'+
	        '</tr>'+
	        '<tr>'+
	            '<td >Meta Title:</td>'+
	            '<td style="text-align: left;">'+data.meta_title+'</td>'+
	        '</tr>'+
	        '<tr>'+
	            '<td >Meta Description:</td>'+
	            '<td style="text-align: left;">'+data.meta_description+'</td>'+
	        '</tr>'+
	        '<tr>'+
	            '<td >Meta Keywords:</td>'+
	            '<td style="text-align: left;">'+data.meta_keywords+'</td>'+
	        '</tr>'+
	        '<tr>'+
	            '<td >IMG Alt Text:</td>'+
	            '<td style="text-align: left;">'+data.img_alt+'</td>'+
	        '</tr>'+
	    '</table>';
	}
});
