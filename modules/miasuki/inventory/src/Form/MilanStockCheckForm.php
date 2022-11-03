<?php

namespace Drupal\inventory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;
use Drupal\product\Controller\ProductController;
use Drupal\inventory\Controller\InventoryController;
use Drupal\warehouse\Controller\WarehouseController;
use Drupal\develop\Controller\ExcelController;

/**
 * Class MilanStockCheckForm.
 */
class MilanStockCheckForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'milan_stock_check_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $param = \Drupal::request()->query->all();
    $connection = Database::getConnection();
    $form['warehousefileset'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Warehouse and File'),
        '#open'  => true,
      ];
    $warehouse_opt = WarehouseController::get_all_warehouses();
    $form['warehousefileset']['warehouse_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Warehouse'),
      '#options' => $warehouse_opt,
      '#default_value' => 3,
      // '#size' => 1,
      '#weight' => '1',
    ];
    $form['warehousefileset']['excel_file'] = [
      '#type' => 'managed_file',
      '#upload_location' => 'public://miasuki_file/',
      '#upload_validators' => array(
        'file_validate_extensions' => array('xls'),
        // Pass the maximum file size in bytes
        // 'file_validate_size' => array(MAX_FILE_SIZE*1024*1024),
      ),
      '#title' => $this->t('Milan Office Stock File'),
      '#weight' => '2',
    ];
    $form['warehousefileset']['download_template'] = [
      '#markup' => 'Get Milan Office Stock From NAV. 1 Hide Table Name, 2 Export as XLS<br><br>',
      '#weight' => '3',
    ];
    $form['warehousefileset']['upload'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#submit' => array('::MilanStockUpload'),
      '#weight' => '4',
    ];
    if (isset($param['milanstockfid'])&&isset($param['warehouse_id'])) {
      unset($form['warehousefileset']);
      $uploaded_file = file_load($param['milanstockfid']);
      $file_uri = $uploaded_file->getFileUri();
      $inputFileType = IOFactory::identify($file_uri);
      $reader = IOFactory::createReader($inputFileType);
      $reader->setReadDataOnly(TRUE);
      $spreadsheet = $reader->load($file_uri);
      $milan_stock_data = ExcelController::formate_sheet_data($spreadsheet->getSheet(0)->toArray());
      $found_stock = array();
      $wrong_barcode_stock = array();
      $ignore_stock = array();
      $missing_stock = array();
      foreach ($milan_stock_data as $each_data) {
        $nav_code = trim($each_data['Nr. articolo']);
        $color_code = trim($each_data['Cod. variante']);
        $nav_sku = $nav_code.' '.$color_code;
        $qty = trim($each_data['Qtà disponibile da prendere']);
        $db_product = ProductController::get_simple_product_by_nav($nav_sku);
        $ignore_product = InventoryController::check_ignore_list_by_nav_code($nav_sku);
        if (isset($db_product['id'])) {
          $each_data['magento_sku'] = $db_product['magento_sku'];
          $each_data['nav_sku'] = $nav_sku;
          $each_data['product_id'] = $db_product['id'];
          $found_stock[] = $each_data;
        }else if(isset($ignore_product['id'])){
          $ignore_stock[] = $each_data;
        }else{
          $missing_stock[] = $each_data;
        }
      }
      $form['missing_nav'] = [
        '#type'  => 'details',
        '#title' => $this->t('Missing Mapping NAV Code'),
        '#open'  => true,
        'table' => InventoryController::milan_missing_nav_data($missing_stock),
        '#weight' => '1',
      ];
      $form['ignore_nav'] = [
        '#type'  => 'details',
        '#title' => $this->t('Ignored Mapping NAV Code'),
        '#open'  => false,
        'table' => InventoryController::milan_ignore_nav_data($ignore_stock),
        '#weight' => '2',
      ];
      $form['found_barcode_nav'] = [
        '#type'  => 'details',
        '#title' => $this->t('Found Barcode NAV Code'),
        '#open'  => true,
        'table' => InventoryController::milan_found_nav_data($found_stock,$param['warehouse_id'],$form),
        '#weight' => '3',
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
        '#weight' => '4',
      ];
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
  public function MilanStockUpload(array &$form, FormStateInterface $form_state) {
    $fields = $form_state->getValues();
    
    $uploaded_file = file_load($form_state->getValue('excel_file')[0]);
    $query_parameter = array();
    $query_parameter['milanstockfid']=$uploaded_file->id();
    $query_parameter['warehouse_id']=$fields['warehouse_id'];
    $url = Url::fromRoute('inventory.milan_stock_check_form', $query_parameter);
    return $form_state->setRedirectUrl($url);
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $connection = Database::getConnection();
    // print_r($form['tmp']['data']['update_inventory_data']);exit;
    foreach ($form['tmp']['data']['update_inventory_data'] as $each_update_inventory) {
      if (!isset($each_update_inventory['product_id'])) {
        continue;
      }
      $check_query = $connection->select('miasuki_inventory', 'mi')
        ->condition('product_id', $each_update_inventory['product_id'])
        ->condition('warehouse_id', $each_update_inventory['warehouse_id'])
        ->fields('mi');
      $check_record = $check_query->execute()->fetchAssoc();
      if (empty($check_record['id'])) {
        //no record insert new
        $connection->insert('miasuki_inventory')
          ->fields([
            'product_id' => $each_update_inventory['product_id'],
            'warehouse_id' => $each_update_inventory['warehouse_id'],
            'qty' => $each_update_inventory['qty'],
          ])
          ->execute();
      }else{
        $connection->update('miasuki_inventory')
          ->fields([
            'qty' => $each_update_inventory['qty'],
          ])
          ->condition('product_id', $each_update_inventory['product_id'])
          ->condition('warehouse_id', $each_update_inventory['warehouse_id'])
          ->execute();
      }
      InventoryController::modified_log($each_update_inventory['product_id'],$each_update_inventory['warehouse_id'],$each_update_inventory['warehouse_qty'],$each_update_inventory['qty'],'Milan office NAV File check');
      drupal_set_message('product_id: '.$each_update_inventory['product_id'].' '.$each_update_inventory['magento_sku'].' warehouse_id: '.$each_update_inventory['warehouse_id'].' qty update:'.$each_update_inventory['qty']);
    }

  }

}
