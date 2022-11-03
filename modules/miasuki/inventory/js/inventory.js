jQuery(document).ready(function($){
	function do_address_mapping(){

	}
	$(document).on('change','.do_address_trigger',function(e) {
		
	});
	$('#edit-barcode').focus();
	var datatable = $('.inventory_table').DataTable({
		rowReorder: true,
        columnDefs: [
            { orderable: false, targets: '0' },
            { orderable: false, targets: $('.inventory_table').attr('data-col')},
        ]
	});
	var datatable_order_list = $('.order_list_table').DataTable({
        order: [[ 4, "desc" ]],
	});
	var datatable_relocation_list = $('.relocation_table').DataTable({
        order: [[ 5, "desc" ]],
	});
	function update_product_table_for_relocation(message, product, product_index){
		if (message=='Product Not Found') {
				alert(message);
			}else if(message=='Product Found'){
				var add_flag = true;
				$('.relocation_product_sku').each(function(index) {
					if ($( this ).val()==product.magento_sku) {
						var row_index = $( this ).attr('id').match(/\d+/)[0];
						var new_qty = parseInt($('#edit-products-table-product-'+row_index+'-qty').val()) + 1;
						$('#edit-products-table-product-'+row_index+'-qty').val(new_qty);
						// console.log($( this ).attr('id').match(/\d+/)[0] );// "3")
						// alert('Product Already Added');
						add_flag = false;
					}
				});
				if (add_flag) {
					var field_magento_sku = `
						<div class="form-item js-form-item form-type-textfield js-form-type-textfield form-item-products-table-product-`+product_index+`-magento-sku js-form-item-products-table-product-`+product_index+`-magento-sku form-no-label form-group">
							<input readonly="readonly" data-drupal-selector="edit-products-table-product-`+product_index+`-magento-sku" class="form-text form-control relocation_product_sku" type="text" id="edit-products-table-product-`+product_index+`-magento-sku" name="products_table[product_`+product_index+`][magento_sku]" value="`+product.magento_sku+`" size="60" maxlength="128">
							<input type="hidden" name="products_table[product_`+product_index+`][id]" value="`+product.id+`">
						</div>
					`;
					var warehouse_inventory = '';
					Object.keys(product.inventory).forEach(function (key) {
						warehouse_inventory = warehouse_inventory + `
							<td><div class="form-item js-form-item form-type-textfield js-form-type-textfield form-item-products-table-product-`+product_index+`-price js-form-item-products-table-product-`+product_index+`-price form-no-label form-group">
			 					`+product.inventory[key]+`
							</div></td>
						`;

					});

					var field_qty = `
						<div class="form-inline form-item js-form-item form-type-number js-form-type-number form-item-products-table-product-`+product_index+`-qty js-form-item-products-table-product-1-qty form-no-label form-group">
							<input data-drupal-selector="edit-products-table-product-`+product_index+`-qty" class="form-number form-control" type="number" id="edit-products-table-product-`+product_index+`-qty" name="products_table[product_`+product_index+`][qty]" value="1" step="1" min="1" style="width:60px;">
						</div>
					`;

					var del_button = `
						<button onclick="return false;" class="relocation_product_del button js-form-submit form-submit btn-danger btn icon-before" data-drupal-selector="edit-products-table-product-`+product_index+`-del" type="submit" id="edit-products-table-product-`+product_index+`-del" name="op" value="Delete"><span class="icon glyphicon glyphicon-trash" aria-hidden="true"></span>
		Delete</button>
					`;
					if ($('#edit-products-table tbody').length==0) {
						$('#edit-products-table').append('<tbody><tr><td>'+field_magento_sku+'</td><'+warehouse_inventory+'<td class="form-inline">'+field_qty+'</td><td>'+del_button+'</td></tr></tbody>');
					}else{
						$('#edit-products-table tbody').append('<tr><td>'+field_magento_sku+'</td>'+warehouse_inventory+'<td class="form-inline">'+field_qty+'</td><td>'+del_button+'</td></tr>');
					}
					$('#product_count').val(product_index);
				}
			}
	}
	$(document).on('click','#relocation_product_add',function(e) {
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
		        	update_product_table_for_relocation(response[0].message,response[0].result, product_index);
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
		        	update_product_table_for_relocation(response[0].message,response[0].result, product_index);
		        },
		        error: function (request, status, error) {  
		            console.log('error'); 
		            console.log(request); 
		            console.log(status); 
		            console.log(error); 
		        }
		    });
		}
		var barcode = $('#edit-barcode').val('');
	});
	$(document).on('click','.relocation_product_del',function(e) {
		$(this).parents('tr').remove();
		var product_count = parseInt($('#product_count').val())-1;
		$('#product_count').val(product_count);
	});
	function format_child_table_relocation(result,relocation_history_id){
		var relocation_details = '';
		result.forEach(function(entry){
		    relocation_details = relocation_details + '<tr>'+
	            '<td>'+entry.magento_sku+'</td>'+
	            '<td>'+entry.qty+'</td>'+
	        '</tr>'
		});
		return '<table style="padding-left:50px; width:50%; float:left;">'+
	        '<tr>'+
	            '<th>Magento SKU</th>'+
	            '<th>QTY</th>'+
	        '</th>'+
	        relocation_details +
	    '</table>'+drupalSettings.relocation_history_remark_data[relocation_history_id];
	}
	$(document).on('click','.relocation_detail_control',function(e) {
		var tr = $(this).closest('tr');
		var row = datatable_relocation_list.row(tr);
		if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
        }
        else {
        	var formate_data_1 = drupalSettings.relocation_history_data[$(this).attr('title')];
        	var formate_date_2 = format_child_table_relocation(formate_data_1,$(this).attr('title'));
        	// console.log(formate_data_1);
        	// console.log(drupalSettings.relocation_history_data);
            row.child(formate_date_2).show();
            tr.addClass('shown');
        }
	});
	function format_child_table_inventory(result){
		var bacode_mapping = '';
		result.barcode_mapping.forEach(function(entry){
		    bacode_mapping = bacode_mapping + '<tr>'+
	            '<td>Barcode:</td>'+
	            '<td>'+entry.barcode+'</td>'+
	            '<td>Nav SKU:</td>'+
	            '<td>'+entry.nav_sku+'</td>'+
	        '</tr>'
		});
		return '<table style="padding-left:50px; width:50%;">'+
			bacode_mapping +
	        '<tr>'+
	            '<td >US Price:</td>'+
	            '<td>'+result.us_price+'</td>'+
	            '<td >US Special Price:</td>'+
	            '<td>'+result.us_special_price+'</td>'+
	        '</tr>'+
	        '<tr>'+
	            '<td>HK Price:</td>'+
	            '<td>'+result.hk_price+'</td>'+
	            '<td >HK Special Price:</td>'+
	            '<td>'+result.hk_special_price+'</td>'+
	        '</tr>'+
	        '<tr>'+
	            '<td>EU Price:</td>'+
	            '<td>'+result.eu_price+'</td>'+
	            '<td >EU Special Price:</td>'+
	            '<td>'+result.eu_special_price+'</td>'+
	        '</tr>'+
	        '<tr>'+
	            '<td>UK Price:</td>'+
	            '<td>'+result.uk_price+'</td>'+
	            '<td >UK Special Price:</td>'+
	            '<td>'+result.uk_special_price+'</td>'+
	        '</tr>'+
	        '<tr>'+
	            '<td>CN Price:</td>'+
	            '<td>'+result.cn_price+'</td>'+
	            '<td >CN Special Price:</td>'+
	            '<td>'+result.cn_special_price+'</td>'+
	        '</tr>'+
	    '</table>';
	}
	
	$(document).on('click','.inventory_details_control',function(e) {
		var tr = $(this).closest('tr');
		var row = datatable.row(tr);
		$.ajax({
	        url: window.location.origin+drupalSettings.path.baseUrl+'/product/ajax_simple_product_info/'+$(this).attr('title'),
	        success: function (response) {
	        	if ( row.child.isShown() ) {
		            row.child.hide();
		            tr.removeClass('shown');
		        }
		        else {
		            row.child( format_child_table_inventory(response[0].result)).show();
		            tr.addClass('shown');
		        }
	        },
	        error: function (request, status, error) {  
	            console.log('error'); 
	            console.log(request); 
	            console.log(status); 
	            console.log(error); 
	        }
	    });
	});
	function ajax_sync_simple(start_id){
		$.ajax({
			type: "POST",
	        url: window.location.origin+drupalSettings.path.baseUrl+'develop/action/sync_inventory_simple_product_one',
	        data: {start_id: start_id},
    		dataType:'JSON', 
	        success: function (response) {
	        	console.log(response);
	        	var datatable = $('#simple_product_list').DataTable();
				var rowIndex = 0;
				datatable.rows( function ( idx, data, node ) {
					if (data[1]==response.default.sku) {rowIndex = idx;} return false;
			    });
				datatable.cell(rowIndex, 2).data('Synced '+response.default.stock).draw();
				datatable.cell(rowIndex, 3).data('Synced '+response.hk_source.stock).draw();
				datatable.cell(rowIndex, 4).data('Synced '+response.cn_source.stock).draw();
	        	// $('#us_'+response.default.sku).text('Synced '+response.default.stock);
	        	// $('#hk_'+response.hk_source.sku).text('Synced '+response.hk_source.stock);
	        	// $('#cn_'+response.cn_source.sku).text('Synced '+response.cn_source.stock);
	        	var percent = (response.msp_id/$('#total_simple_product').val()*100).toFixed(1);
				$('.sync_progress_bar_inner').width(percent+'%');
				$('#sync_progress_bar_percent').text(percent);
	        	if (parseInt(start_id)<parseInt($('#total_simple_product').val())) {
	        		var next_id = parseInt(start_id) + 1;
	        		ajax_sync_simple(next_id);
	        	}else{
	        		console.log('all simple_product synced');
	        	}
	        },
	        error: function (request, status, error) {  
	            console.log('error'+start_id); 
	            console.log(request); 
	            console.log(status); 
	            console.log(error); 
	        }
	    });
	}
	function ajax_sync_config(start_id){
		$.ajax({
			type: "POST",
	        url: window.location.origin+drupalSettings.path.baseUrl+'develop/action/sync_inventory_config_product_one',
	        data: {start_id: start_id},
    		dataType:'JSON', 
	        success: function (response) {
	        	console.log(response);
	        	var datatable = $('#config_product_list').DataTable();
				var rowIndex = 0;
				datatable.rows( function ( idx, data, node ) {
					if (data[1]==response.sku) {rowIndex = idx;} return false;
			    });
			    datatable.cell(rowIndex, 2).data('Synced Status '+response.us).draw();
				datatable.cell(rowIndex, 3).data('Synced Status '+response.hk).draw();
				datatable.cell(rowIndex, 4).data('Synced Status '+response.eu).draw();
				datatable.cell(rowIndex, 5).data('Synced Status '+response.uk).draw();
				datatable.cell(rowIndex, 6).data('Synced Status '+response.cn).draw();
				var percent = (response.mcp_id/$('#total_config_product').val()*100).toFixed(1);
				$('.sync_progress_bar_inner').width(percent+'%');
				$('#sync_progress_bar_percent').text(percent);
				if (parseInt(start_id)<parseInt($('#total_config_product').val())) {
	        		var next_id = parseInt(start_id) + 1;
	        		ajax_sync_config(next_id);
	        	}else{
	        		console.log('all config_product synced');
	        	}

	        },
	        error: function (request, status, error) {  
	            console.log('error'); 
	            console.log(request); 
	            console.log(status); 
	            console.log(error); 
	        }
	    });
	}
	$(document).on('click','#sync_simple_button',function(e) {
		// console.log($('#edit-start-id').val());
		ajax_sync_simple($('#edit-start-id').val());
	});
	$(document).on('click','#sync_config_button',function(e) {
		ajax_sync_config($('#edit-start-id').val());
	});
});
