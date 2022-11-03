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
 * Class InventoryUpdateForm.
 */
class InventoryUpdateForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'inventory_update_form';
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
      '#title' => $this->t('Stock File'),
      '#weight' => '2',
    ];
    $form['warehousefileset']['download_template'] = [
      '#markup' => 'Export Inventory File from All Inventory.<br><br>',
      '#weight' => '3',
    ];
    $form['warehousefileset']['upload'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#submit' => array('::ManuallyStockUpload'),
      '#weight' => '4',
    ];
    if (isset($param['manuallystockfid'])&&isset($param['warehouse_id'])) {
      unset($form['warehousefileset']);
      $uploaded_file = file_load($param['manuallystockfid']);
      $file_uri = $uploaded_file->getFileUri();
      $inputFileType = IOFactory::identify($file_uri);
      $reader = IOFactory::createReader($inputFileType);
      $reader->setReadDataOnly(TRUE);
      $spreadsheet = $reader->load($file_uri);
      $manually_stock_data = ExcelController::formate_sheet_data($spreadsheet->getSheet(0)->toArray());
      $warehouse_id = $param['warehouse_id'];
      $warehouse_name = $warehouse_opt[$param['warehouse_id']];
      $update_products = array();
      foreach ($manually_stock_data as $each_data) {
        if (!array_key_exists($warehouse_name,$each_data)) {
          drupal_set_message('Warehouse not defined','error');
          break;
        }
        
        $db_product = ProductController::get_simple_product_by_sku($each_data['Magento SKU']);
        if (!isset($db_product['id'])) {
          drupal_set_message($each_data['Magento SKU'].' not found','error');
          continue;
        }
        $db_inventory = InventoryController::get_inventory_by_productid_warehouseid($db_product['id'],$warehouse_id);
        $excel_qty = $each_data[$warehouse_name];
        if ($db_inventory!=$excel_qty) {
          $update_product = $db_product;
          $update_product['db_inventory'] = $db_inventory;
          $update_product['excel_qty'] = $excel_qty;
          $update_products[] = $update_product;
        }
      }
      $form['update_products'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Inventory Will be Updated'),
        '#open'  => true,
        'table' => InventoryController::manually_updated_inventory_data($update_products,$form),
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
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
  public function ManuallyStockUpload(array &$form, FormStateInterface $form_state) {
    $fields = $form_state->getValues();
    
    $uploaded_file = file_load($form_state->getValue('excel_file')[0]);
    $query_parameter = array();
    $query_parameter['manuallystockfid']=$uploaded_file->id();
    $query_parameter['warehouse_id']=$fields['warehouse_id'];
    $url = Url::fromRoute('inventory.inventory_update_form', $query_parameter);
    return $form_state->setRedirectUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    $connection = Database::getConnection();
    $param = \Drupal::request()->query->all();
    foreach ($form['tmp']['data']['manually_check_inventory_products'] as $each_update_inventory) {
      if (!isset($each_update_inventory['id'])) {
        continue;
      }
      $check_query = $connection->select('miasuki_inventory', 'mi')
        ->condition('product_id', $each_update_inventory['id'])
        ->condition('warehouse_id', $param['warehouse_id'])
        ->fields('mi');
      $check_record = $check_query->execute()->fetchAssoc();
      if (empty($check_record['id'])) {
        //no record insert new
        $connection->insert('miasuki_inventory')
          ->fields([
            'product_id' => $each_update_inventory['id'],
            'warehouse_id' => $param['warehouse_id'],
            'qty' => $each_update_inventory['excel_qty'],
          ])
          ->execute();
      }else{
        $connection->update('miasuki_inventory')
          ->fields([
            'qty' => $each_update_inventory['excel_qty'],
          ])
          ->condition('product_id', $each_update_inventory['id'])
          ->condition('warehouse_id', $param['warehouse_id'])
          ->execute();
      }
      InventoryController::modified_log($each_update_inventory['id'],$param['warehouse_id'],$each_update_inventory['db_inventory'],$each_update_inventory['excel_qty'],'Manually Update Inventory');
      drupal_set_message('product_id: '.$each_update_inventory['id'].' '.$each_update_inventory['magento_sku'].' warehouse_id: '.$param['warehouse_id'].' qty update:'.$each_update_inventory['excel_qty']);
    }

  }

}
