jQuery(document).ready(function($){
	$(document).on('click','.barcode_nav_add',function(e) {
		if (typeof $('#edit-barcode-nav').attr('new_row') !== 'undefined') {
	  		var new_row = parseInt($('#edit-barcode-nav').attr('new_row'))+1;
		}else{
			var new_row = 1;
		}

		var barcode_field = `
		  <div class="form-item js-form-item form-type-textfield js-form-type-textfield form-no-label form-group">
		    <input data-drupal-selector="edit-barcode-nav-new-`+new_row+`-barcode" class="form-text form-control" type="text" id="edit-barcode-nav-new-`+new_row+`-barcode" name="barcode_nav[new_`+new_row+`][barcode]" value="" size="40" maxlength="128">
		  </div>
		`;
		var nav_sku_field = `
		  <div class="form-item js-form-item form-type-textfield js-form-type-textfield form-no-label form-group">
		    <input data-drupal-selector="edit-barcode-nav-new-`+new_row+`-nav-sku" class="form-text form-control" type="text" id="edit-barcode-nav-new-`+new_row+`-nav-sku" name="barcode_nav[new_`+new_row+`][nav_sku]" value="" size="40" maxlength="128">
		  </div>
		`;
		var del_button = '<button data-drupal-selector="edit-barcode-nav-new-'+new_row+'-del" class="barcode_nav_del button js-form-submit form-submit btn-danger btn" type="button" id="edit-barcode-nav-new-'+new_row+'-del" name="op" value="Delete">Delete</button>';
		var add_button = '<button onclick="return false;" data-drupal-selector="edit-barcode-nav-new-'+new_row+'-add" class="barcode_nav_add button js-form-submit form-submit btn-success btn" type="button" id="edit-barcode-nav-new-'+new_row+'-add" name="op" value="Delete">Add</button>';
		$('#edit-barcode-nav tbody').append('<tr><td></td><td>'+barcode_field+'</td><td>'+nav_sku_field+'</td><td>'+del_button+'</td><td>'+add_button+'</td></tr>');
		$('#edit-barcode-nav').attr('new_row',new_row);
	});
	$(document).on('click','.barcode_nav_del',function(e) {
		var rowCount = $('#edit-barcode-nav tr:visible').length;
		if (rowCount<=2) {
			return false;
		}
		if (typeof $(this).attr('mapping_id') !== 'undefined'){
			$(this).parents('tr').hide();
			$(this).append('<input type="hidden" name="barcode_nav['+$(this).attr('mapping_id')+'][ops]" value="del">');
		}else{
			$(this).parents('tr').remove();
			var new_row = parseInt($('#edit-barcode-nav').attr('new_row'))-1;
			$('#edit-barcode-nav').attr('new_row',new_row);
		}
	});
	var datatable = $('.seo_table').DataTable({
		rowReorder: true,
        columnDefs: [
            { orderable: false, targets: '0' },
            { orderable: false, targets: $('.seo_table').attr('data-col')},
        ]
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
	