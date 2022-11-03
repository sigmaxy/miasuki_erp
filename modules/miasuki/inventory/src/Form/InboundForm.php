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
 * Class InboundForm.
 */
class InboundForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'inbound_form';
  }
  public $barcode;
  public $product_id;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $param = \Drupal::request()->query->all();
    $connection = Database::getConnection();
    $form['barcodeset'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Bar Code & SKU'),
      '#open'  => true,
    ];
    $form['barcodeset']['magento_sku'] = [
      '#title'         => 'Magento SKU',
      '#type'          => 'search',
      '#default_value' => isset($param['magento_sku'])?$param['magento_sku']:'',
      '#autocomplete_route_name' => 'product.autocomplete_simple_product_sku',
      '#autocomplete_route_parameters' => array('count' => 10),
      '#weight' => '1',
    ];
    $form['barcodeset']['barcode'] = [
      '#title'         => 'Barcode',
      '#type'          => 'search',
      '#default_value' => isset($param['barcode'])?$param['barcode']:'',
      '#weight' => '1',
    ];
    $form['barcodeset']['check'] = [
      '#type' => 'submit',
      '#value' => $this->t('Check'),
      '#submit' => array('::BarcodeSubmit'),
      '#weight' => '2',
    ];

    $form['fileuploadset'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Mass Inbound'),
      '#open'  => true,
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
      '#title' => $this->t('Mass Inbound File'),
      '#weight' => '1',
    ];
    $form['fileuploadset']['download_template'] = [
      '#markup' => 'Mass Inbound Form Template <a href="'.file_create_url($b2bform_template_uri).'">download</a>.<br><br><br>',
      '#weight' => '2',
    ];
    $form['fileuploadset']['upload'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#submit' => array('::MassInboundUpload'),
      '#weight' => '3',
    ];
    if (isset($param['massrelocationfid'])) {
      unset($form['barcodeset']);
      unset($form['fileuploadset']);
      $uploaded_file = file_load($param['massrelocationfid']);
      $file_uri = $uploaded_file->getFileUri();
      $inputFileType = IOFactory::identify($file_uri);
      $reader = IOFactory::createReader($inputFileType);
      $reader->setReadDataOnly(TRUE);
      $spreadsheet = $reader->load($file_uri);
      $relocation_items = ExcelController::formate_sheet_data($spreadsheet->getSheet(0)->toArray());
      $item_valid_flag = true;
      foreach ($relocation_items as $each_item_index=>$each_item) {
        $simple_product_by_sku = ProductController::get_simple_product_by_sku($each_item['sku']);
        $simple_product_by_barcode = ProductController::get_simple_product_by_barcode($each_item['barcode']);
        if (isset($simple_product_by_sku['id'])) {
          $relocation_items[$each_item_index] = $simple_product_by_sku;
          $relocation_items[$each_item_index]['qty'] = $each_item['qty'];
        }else if(isset($simple_product_by_barcode['magento_sku'])){
          $relocation_items[$each_item_index] = $simple_product_by_barcode;
          $relocation_items[$each_item_index]['sku'] = $simple_product_by_barcode['magento_sku'];
          $relocation_items[$each_item_index]['qty'] = $each_item['qty'];
        }else{
          drupal_set_message('Row '.$each_item_index.' item not found','error');
          $item_valid_flag = false;
        }
      }
      $form['data']['items'] = $relocation_items;
      $form['data']['items_count'] = count($relocation_items);
    }else if(isset($param['magento_sku'])){
      unset($form['fileuploadset']);
      $record = ProductController::get_simple_product_by_sku($param['magento_sku']);
      $form['data']['items'][] = $record;
    }else if(isset($param['barcode'])){
      unset($form['fileuploadset']);
      $record = ProductController::get_simple_product_by_barcode($param['barcode']);
      $form['data']['items'][] = $record;
    } 
    if (!isset($form['data']['items'])) {
      
    }else{
      //Inbound data start
      $form['inbound'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Inbound'),
        '#open'  => true,
      ];
      if (isset($param['barcode'])||isset($param['magento_sku'])) {
        $form['inbound']['qty'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Inbound Qty'),
          '#weight' => '1',
          '#default_value' => '',
        ];
      }
      $warehouse_opt = WarehouseController::get_all_warehouses();
      $form['inbound']['to_warehouse_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Warehouse'),
        '#options' => $warehouse_opt,
        // '#size' => 1,
        '#weight' => '2',
      ];
      $form['inbound']['reason'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Reason'),
        // '#size' => 1,
        '#weight' => '3',
      ];
      $form['inbound']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
        '#submit' => array('::InboundFormSubmit'),
        '#weight' => '4',
      ];
      //Inbound data end


      //Identification data start
      $form['identification'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Identification'),
        '#open'  => true,
        'table' => InventoryController::inventory_data_identification($form['data']['items']),
      ];
      //Identification data end

      //Detail data start
      $form['detail'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Detail'),
        '#open'  => true,
        'table' => InventoryController::inventory_data_detail($form['data']['items']),
      ];
      //Detail data end

      //Price data start
      $form['price'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Price'),
        '#open'  => true,
        'table' => InventoryController::inventory_data_price($form['data']['items']),
      ];

      //inventory data start
      $form['inventory'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Inventory'),
        '#open'  => true,
        'table' => InventoryController::inventory_data_qty($form['data']['items']),
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
    $url = Url::fromRoute('inventory.inbound_form', $query_parameter);
    return $form_state->setRedirectUrl($url);
  }
  public function BarcodeSubmit(array &$form, FormStateInterface $form_state) {
    $query_parameter = array();
    $connection = Database::getConnection();
    // print_r($form_state->getValue('filters'));exit;
    if (!empty($form_state->getValue('magento_sku'))) {
      $query_parameter['magento_sku']=$form_state->getValue('magento_sku');
      $record = ProductController::get_simple_product_by_sku($form_state->getValue('magento_sku'));
      if (empty($record['id'])) {
        drupal_set_message('There is no such product!', $type = 'error');
      }
    }else if (!empty($form_state->getValue('barcode'))) {
      $query_parameter['barcode']=$form_state->getValue('barcode');
      $record = ProductController::get_simple_product_by_barcode($form_state->getValue('barcode'));
      if (empty($record['id'])) {
        drupal_set_message('There is no such product!', $type = 'error');
      }
    }
    $url = Url::fromRoute('inventory.inbound_form', $query_parameter);
    return $form_state->setRedirectUrl($url);
  }
  public function MassInboundUpload(array &$form, FormStateInterface $form_state) {
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
      $simple_product_by_sku = ProductController::get_simple_product_by_sku($each_item['sku']);
      $simple_product_by_barcode = ProductController::get_simple_product_by_barcode($each_item['barcode']);
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
    $url = Url::fromRoute('inventory.inbound_form', $query_parameter);
    return $form_state->setRedirectUrl($url);
  }
  public function InboundFormSubmit(array &$form, FormStateInterface $form_state) {
    $param = \Drupal::request()->query->all();
    $field=$form_state->getValues();
    $connection = Database::getConnection();
    // print_r($field['to_warehouse_id']);exit;
    $inventory_check_flag = true;
    //single product reloaction
    if (isset($param['barcode'])||isset($param['magento_sku'])) {
      $form['data']['items'][0]['qty'] = $field['qty'];
    }
    if ($inventory_check_flag) {
      foreach ($form['data']['items'] as $each_product){
        if (!isset($each_product['id'])) {
          continue;
        }
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
        $param_product['magento_sku'] = $each_product['magento_sku'];
        $param_product['id'] = $each_product['id'];
        $param_product['qty'] = $each_product['qty'];
        $param_products[] = $param_product;

      }
      InventoryController::relocation_log('inbound',null,$field['to_warehouse_id'],$field['reason'],json_encode($param_products));
      drupal_set_message('Products have been updated in warehouse '.$field['to_warehouse_id']);
      $url = Url::fromRoute('inventory.inbound_form');
    }
    return $form_state->setRedirectUrl($url);
  }

}
