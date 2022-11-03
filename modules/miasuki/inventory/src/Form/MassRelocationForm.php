<?php

namespace Drupal\inventory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\product\Controller\ProductController;
use Drupal\inventory\Controller\InventoryController;
use Drupal\warehouse\Controller\WarehouseController;
use Drupal\attribute\Controller\AttributeController;
use Drupal\develop\Controller\ExcelController;

/**
 * Class MassRelocationForm.
 */
class MassRelocationForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mass_relocation_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $param = \Drupal::request()->query->all();
    $connection = Database::getConnection();
    $form['barcodeset'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Bar Code & SKU (Add product one by one)'),
      '#open'  => true,
      '#weight' => '1',
    ];
    $form['barcodeset']['magento_sku'] = [
      '#title'         => 'Magento SKU',
      '#type'          => 'search',
      '#autocomplete_route_name' => 'product.autocomplete_simple_product_sku',
      '#autocomplete_route_parameters' => array('count' => 10),
      '#weight' => '2',
    ];
    $form['barcodeset']['barcode'] = [
      '#title'         => 'Barcode',
      '#type'          => 'search',
      '#weight' => '3',
    ];
    $form['barcodeset']['add'] = [
      '#type' => 'button',
      '#value' => $this->t('Add'),
      '#attributes' => [
        'onclick' => 'return false;',
        'id' => 'relocation_product_add',
      ],
      '#weight' => '4',
    ];
    $form['products_detail'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Product Detail'),
      '#open'  => true,
      '#weight' => '2',
    ];
    $detail_table_header = array(
      'magento_sku'=>array(
        'data'=>t('Magento SKU'),
        'class'=>array('table_header_magento_sku'),
      ),
    );
    $warehouse_arr = WarehouseController::get_all_warehouses();
    foreach ($warehouse_arr as $warehouse_id=>$each_warehouse) {
      $detail_table_header[$warehouse_id] = array(
        'data'=>$warehouse_id,
        'class'=>array('table_header_warehouse'),
        'title'=>t($each_warehouse),
      );
    }
    $detail_table_header['qty'] = t('QTY');
    $form['products_detail']['products_table'] = [
      '#type' => 'table',
      '#header' => $detail_table_header,
      '#attributes' => [   
        'class' => ['product_detail_table'],
      ],
      '#weight' => '2',
    ];
    $form['products_detail']['product_count'] = array(
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'product_count',
      ],
      '#default_value' => 0,
    );
    $form['products_detail']['relocate'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Relocate'),
      '#submit' => array('::RelocateCheck'),
      '#weight' => '3',
    );

    $form['fileuploadset'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Mass Relocation (Add all products in one template file)'),
      '#open'  => true,
      '#weight' => '3',
    ];
    $b2bform_template_uri = 'public://development/MassRelocationForm.xls';
    $b2bform_template = file_create_url($b2bform_template_uri);
    $form['fileuploadset']['excel_file'] = [
      '#type' => 'managed_file',
      '#upload_location' => 'public://miasuki_file/',
      '#upload_validators' => array(
        'file_validate_extensions' => array('xls'),
        // Pass the maximum file size in bytes
        // 'file_validate_size' => array(MAX_FILE_SIZE*1024*1024),
      ),
      '#title' => $this->t('Mass Relocation File'),
      '#weight' => '1',
    ];
    $form['fileuploadset']['download_template'] = [
      '#markup' => 'Mass Relocation Form Template <a href="'.file_create_url($b2bform_template_uri).'">download</a>.<br><br><br>',
      '#weight' => '2',
    ];
    $form['fileuploadset']['upload'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#submit' => array('::MassRelocationUpload'),
      '#weight' => '3',
    ];
    if (isset($param['massrelocationfid'])) {
      unset($form['barcodeset']);
      unset($form['fileuploadset']);
      unset($form['products_detail']);
      $uploaded_file = file_load($param['massrelocationfid']);
      $file_uri = $uploaded_file->getFileUri();
      $inputFileType = IOFactory::identify($file_uri);
      $reader = IOFactory::createReader($inputFileType);
      $reader->setReadDataOnly(TRUE);
      $spreadsheet = $reader->load($file_uri);
      $relocation_items = ExcelController::formate_sheet_data($spreadsheet->getSheet(0)->toArray());
      $item_valid_flag = true;
      foreach ($relocation_items as $each_item_index=>$each_item) {
        $simple_product_by_sku = ProductController::get_simple_product_by_sku(trim($each_item['sku']));
        $simple_product_by_barcode = ProductController::get_simple_product_by_barcode(trim($each_item['barcode']));
        if (isset($simple_product_by_sku['id'])) {
          $relocation_items[$each_item_index] = $simple_product_by_sku;
          $relocation_items[$each_item_index]['qty'] = $each_item['qty'];
        }else if(isset($simple_product_by_barcode['magento_sku'])){
          $relocation_items[$each_item_index] = $simple_product_by_barcode;
          $relocation_items[$each_item_index]['sku'] = $simple_product_by_barcode['magento_sku'];
          $relocation_items[$each_item_index]['qty'] = $each_item['qty'];
        }else{
          drupal_set_message('Row '.$each_item_index.' item '.$each_item['sku'].$each_item['barcode'].' not found','error');
          $item_valid_flag = false;
        }
      }
      $form['data']['items'] = $relocation_items;
      $form['data']['items_count'] = count($relocation_items);
    }else if(isset($param['products_table'])){
      unset($form['barcodeset']);
      unset($form['fileuploadset']);
      unset($form['products_detail']);
      $form['data']['items'] = json_decode($param['products_table'],1);
      $form['data']['items_count'] = count(json_decode($param['products_table'],1));
    }
    if (!isset($form['data']['items'])) {
      
    }else{
      //Inbound data start
      $form['relocation'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Relocation'),
        '#open'  => true,
        '#weight' => '4',
      ];
      $warehouse_opt = WarehouseController::get_all_warehouses();
      $form['relocation']['from_warehouse_id'] = [
        '#type' => 'select',
        '#title' => $this->t('From Warehouse'),
        '#options' => $warehouse_opt,
        // '#size' => 1,
        '#default_value' => $param['from_warehouse_id'],
        '#weight' => '2',
      ];
      $form['relocation']['to_warehouse_id'] = [
        '#type' => 'select',
        '#title' => $this->t('To Warehouse'),
        '#options' => $warehouse_opt,
        // '#size' => 1,
        '#attributes' => [   
          'class' => ['do_address_trigger'],
        ],
        '#default_value' => $param['to_warehouse_id'],
        '#weight' => '3',
      ];
      $form['relocation']['reason'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Remark'),
        // '#size' => 1,
        '#weight' => '3',
      ];
      $form['relocation']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
        '#submit' => array('::RelocationFormSubmit'),
        '#weight' => '4',
      ];
      //Inbound data end


      //Identification data start
      $form['identification'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Identification'),
        '#open'  => true,
        'table' => InventoryController::inventory_data_identification($form['data']['items']),
        '#weight' => '5',
      ];
      //Identification data end

      //Detail data start
      $form['detail'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Detail'),
        '#open'  => true,
        'table' => InventoryController::inventory_data_detail($form['data']['items']),
        '#weight' => '6',
      ];
      //Detail data end

      //Price data start
      $form['price'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Price'),
        '#open'  => true,
        'table' => InventoryController::inventory_data_price($form['data']['items']),
        '#weight' => '7',
      ];

      //inventory data start
      $form['inventory'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Inventory'),
        '#open'  => true,
        'table' => InventoryController::inventory_data_qty($form['data']['items']),
        '#weight' => '8',
      ];
      //inventory data end
    }
    $form['#attached']['library'][] = 'inventory/inventory';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query_parameter = array();
    $url = Url::fromRoute('inventory.mass_relocation_form', $query_parameter);
    return $form_state->setRedirectUrl($url);
  }
  public function GenerateDO(array &$form, FormStateInterface $form_state) {
    $field=$form_state->getValues();
    $to_warehouse_id = $field['to_warehouse_id'];
    $do_template_file = drupal_realpath('public://development/DOTemplate.xlsx');
    $do_file_uri = 'public://miasuki_file/'.'Delivery Note_'.time().'.xlsx';
    $do_file = drupal_realpath($do_file_uri);
    $inputFileType = IOFactory::identify($do_template_file);
    $reader = IOFactory::createReader($inputFileType);
    $spreadsheet = $reader->load($do_template_file);
    $warehouse_address = WarehouseController::get_address_by_warehouse_id($to_warehouse_id);
    $data_arr = array();
    $total_qty = 0;
    $row_count = 0;
    foreach ($form['data']['items'] as $each_item_key=>$each_item) {
      if (!isset($each_item['id'])) {
        continue;
      }
      $dsproduct = ProductController::get_simple_product_by_sku(trim($each_item['magento_sku']));
      $do_item_index = intval($each_item_key) + 1;
      $item_color = AttributeController::get_color_by_id($dsproduct['color_id'])['color'];
      $item_size = AttributeController::get_size_by_id($dsproduct['size_id'])['size'];
      $item_length = AttributeController::get_length_by_id($dsproduct['length_id'])['length'];
      $data_row = [$do_item_index,$each_item['magento_sku'],$item_color,$item_size,$item_length,$each_item['qty']];
      $total_qty = intval($total_qty) + intval($each_item['qty']);
      $data_arr[] = $data_row;
      $row_count++;
    }
    $spreadsheet->getActiveSheet()->getCell('A1')->setValue($warehouse_address);
    $spreadsheet->getActiveSheet()->getCell('A4')->setValue($total_qty);
    $spreadsheet->getActiveSheet()->getCell('E4')->setValue(date('Y-m-d'));
    $spreadsheet->getActiveSheet()->getCell('A18')->setValue(date('Y-m-d'));
    $spreadsheet->getActiveSheet()->getCell('D18')->setValue(date('Y-m-d'));
    $spreadsheet->getActiveSheet()->getStyle('A3:F3')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('381F32');
    $spreadsheet->getActiveSheet()->getStyle('A5:F5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('381F32');
    $spreadsheet->getActiveSheet()->getStyle('A9')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EEE7E9');
    if ($row_count>2) {
      $new_row = intval($row_count) - 2;
      $spreadsheet->getActiveSheet()->insertNewRowBefore(8,$new_row);
    }
    $spreadsheet->getActiveSheet()
      ->fromArray(
          $data_arr,  // The data to set
          NULL,        // Array values with this value will not be set
          'A6'         // Top left coordinate of the worksheet range where
                       //    we want to set these values (default is A1)
      );
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
    $writer->save($do_file);

    $download_url = '<a target="_black" href="'.file_create_url($do_file_uri).'">Delievery Note</a>';
    drupal_set_message(t("You can download $download_url"));
  }
  public function RelocateCheck(array &$form, FormStateInterface $form_state) {
    $field=$form_state->getValues();
    $products_table = array();
    foreach ($field['products_table'] as $each_product) {
      $products_table[]=$each_product;
    }
    $query_parameter['products_table'] = json_encode($products_table);
    $url = Url::fromRoute('inventory.mass_relocation_form', $query_parameter);
    return $form_state->setRedirectUrl($url);
  }
  public function MassRelocationUpload(array &$form, FormStateInterface $form_state) {
    // echo 'test';exit;
    $query_parameter = array();
    $connection = Database::getConnection();
    $uploaded_file = file_load($form_state->getValue('excel_file')[0]);

    $file_uri = $uploaded_file->getFileUri();
    $inputFileType = IOFactory::identify($file_uri);
    $reader = IOFactory::createReader($inputFileType);
    $reader->setReadDataOnly(TRUE);
    $spreadsheet = $reader->load($file_uri);
    $relocation_items = ExcelController::formate_sheet_data($spreadsheet->getSheet(0)->toArray());
    $item_valid_flag = true;
    foreach ($relocation_items as $each_item_index=>$each_item) {
      $simple_product_by_sku = ProductController::get_simple_product_by_sku(trim($each_item['sku']));
      $simple_product_by_barcode = ProductController::get_simple_product_by_barcode(trim($each_item['barcode']));
      if (isset($simple_product_by_sku['id'])) {
        $relocation_items[$each_item_index]['id'] = $simple_product_by_sku['id'];
      }else if(isset($simple_product_by_barcode['magento_sku'])){
        $relocation_items[$each_item_index]['id'] = $simple_product_by_barcode['id'];
        $relocation_items[$each_item_index]['sku'] = $simple_product_by_barcode['magento_sku'];
      }else{
        drupal_set_message('Row '.$each_item_index.' item not found','error');
        $item_valid_flag = false;
      }
    }
    if ($item_valid_flag) {
      $query_parameter['massrelocationfid']=$uploaded_file->id();
    }
    $url = Url::fromRoute('inventory.mass_relocation_form', $query_parameter);
    return $form_state->setRedirectUrl($url);
  }
  public function RelocationFormSubmit(array &$form, FormStateInterface $form_state) {
    $param = \Drupal::request()->query->all();
    $field=$form_state->getValues();
    $connection = Database::getConnection();
    $inventory_check_flag = true;
    foreach ($form['data']['items'] as $each_product_index=>$each_product) {
      if (!isset($each_product['id'])) {
        continue;
      }
      $check_query = $connection->select('miasuki_inventory', 'mi')
        ->condition('product_id', $each_product['id'])
        ->condition('warehouse_id', $field['from_warehouse_id'])
        ->fields('mi');
      $check_record = $check_query->execute()->fetchAssoc();
      if (empty($check_record['id'])||$check_record['qty']==0) {
        //no record insert new
        drupal_set_message('There is no inventory of product '.$each_product['sku'].' in such warehouse','error');
        $inventory_check_flag = false;
      }else if ($check_record['qty']<$each_product['qty']) {
        //not enough inventory
        drupal_set_message('Not enough inventory for product '.$each_product['sku'].' in such warehouse for deduct','error');
        $inventory_check_flag = false;
      }else{
        $form['data']['items'][$each_product_index]['check_record']=$check_record['qty'];
      }
    }
    if ($inventory_check_flag) {
      InventoryController::relocation_log('relocation',$field['from_warehouse_id'],$field['to_warehouse_id'],$field['reason'],$param['products_table']);
      //end of relocation log
      foreach ($form['data']['items'] as $each_product){
        if (!isset($each_product['id'])) {
          continue;
        }
        //deduct qty from warehouse
        $new_inventory_from = intval($each_product['check_record']) - intval($each_product['qty']);
        $connection->update('miasuki_inventory')
          ->fields([
            'qty' => $new_inventory_from,
          ])
          ->condition('product_id', $each_product['id'])
          ->condition('warehouse_id', $field['from_warehouse_id'])
          ->execute();
        InventoryController::modified_log($each_product['id'],$field['from_warehouse_id'],$each_product['check_record'],$new_inventory_from,'Relocation:'.$field['reason']);
        //end of deduct qty from warehouse

        //add qty to warehouse
        $check_to_query = $connection->select('miasuki_inventory', 'mi')
          ->condition('product_id', $each_product['id'])
          ->condition('warehouse_id', $field['to_warehouse_id'])
          ->fields('mi');
        $check_to_record = $check_to_query->execute()->fetchAssoc();
        if (empty($check_to_record['id'])) {
          //no record insert new
          $connection->insert('miasuki_inventory')
            ->fields([
              'product_id' => $each_product['id'],
              'warehouse_id' => $field['to_warehouse_id'],
              'qty' => $each_product['qty'],
            ])
            ->execute();
            InventoryController::modified_log($each_product['id'],$field['to_warehouse_id'],0,$each_product['qty'],'Relocation:'.$field['reason']);
        }else{
          $new_inventory_to = intval($check_to_record['qty']) + intval($each_product['qty']);
          $connection->update('miasuki_inventory')
            ->fields([
              'qty' => $new_inventory_to,
            ])
            ->condition('product_id', $each_product['id'])
            ->condition('warehouse_id', $field['to_warehouse_id'])
            ->execute();
          InventoryController::modified_log($each_product['id'],$field['to_warehouse_id'],$check_to_record['qty'],$each_product['qty'],'Relocation:'.$field['reason']);
        }
        //end of add qty to warehouse

      }
      drupal_set_message('Products have been relocated from warehouse '.$field['from_warehouse_id'].' to warehouse '.$field['to_warehouse_id']);
      $url = Url::fromRoute('inventory.mass_relocation_form');
    }else{
      $param = \Drupal::request()->query->all();
      // $query_parameter['massrelocationfid']=$param['massrelocationfid'];
      $query_parameter = $param;
      $url = Url::fromRoute('inventory.mass_relocation_form', $query_parameter);
    }
    return $form_state->setRedirectUrl($url);
  }

}
