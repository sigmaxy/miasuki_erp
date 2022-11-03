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
 * Class MGBStockCheckForm.
 */
class MGBStockCheckForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mgb_stock_check_form';
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
      '#title' => $this->t('MGB Stock File'),
      '#weight' => '2',
    ];
    $form['warehousefileset']['download_template'] = [
      '#markup' => 'Get MGB Stock File <a href="http://b2b.magazzinibrianza.it">MGB WebSite</a>.<br><br>',
      '#weight' => '3',
    ];
    $form['warehousefileset']['upload'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#submit' => array('::MGBStockUpload'),
      '#weight' => '4',
    ];
    if (isset($param['mgbstockfid'])&&isset($param['warehouse_id'])) {
      unset($form['warehousefileset']);
      $uploaded_file = file_load($param['mgbstockfid']);
      $file_uri = $uploaded_file->getFileUri();
      $inputFileType = IOFactory::identify($file_uri);
      $reader = IOFactory::createReader($inputFileType);
      $reader->setReadDataOnly(TRUE);
      $spreadsheet = $reader->load($file_uri);
      $mgb_stock_data = ExcelController::formate_sheet_data($spreadsheet->getSheet(0)->toArray());
      $found_stock = array();
      $wrong_barcode_stock = array();
      $missing_stock = array();

      foreach ($mgb_stock_data as $each_data) {
        $nav_sku = $each_data['DESCRIZIONE_PRODOTTO'];
        $barcode = $each_data['CODICE_ARTICOLO'];
        $query = $connection->select('miasuki_barcode', 'mb');
        $query->fields('mb');
        $query->condition('barcode', $barcode);
        $record = $query->execute()->fetchAssoc();
        $db_product = ProductController::get_simple_product_by_id($record['product_id']);
        $ignore_product = InventoryController::check_ignore_list_by_barcode($barcode);
        if (isset($db_product['id'])) {
          $each_data['magento_sku'] = $db_product['magento_sku'];
          $each_data['nav_sku'] = $record['nav_sku'];
          $each_data['product_id'] = $db_product['id'];
          $found_stock[] = $each_data;
          if ($record['nav_sku']!=$nav_sku) {
            $wrong_barcode_stock[] = $each_data;
          }
        }else if(isset($ignore_product['id'])){
          $ignore_stock[] = $each_data;
        }else{
          $missing_stock[] = $each_data;
        }
      }
      $warning_class_wrong = count($wrong_barcode_stock)>0?array('details_warning'):NULL;
      $warning_class_missing = count($missing_stock)>0?array('details_warning'):NULL;
      $form['wrong_barcode_nav'] = [
        '#type'  => 'details',
        '#title' => $this->t('Wrong Mapping Barcode NAV Code'),
        '#open'  => true,
        'table' => InventoryController::wrong_mapping_barcode_nav_data($wrong_barcode_stock),
        '#attributes' => array('class' => $warning_class_wrong),
        '#weight' => '1',
      ];
      $form['missing_barcode_nav'] = [
        '#type'  => 'details',
        '#title' => $this->t('Missing Barcode NAV Code'),
        '#open'  => true,
        'table' => InventoryController::missing_barcode_nav_data($missing_stock),
        '#attributes' => array('class' => $warning_class_missing),
        '#weight' => '2',
      ];
      $form['ignore_barcode_nav'] = [
        '#type'  => 'details',
        '#title' => $this->t('Ignored Barcode NAV Code'),
        '#open'  => false,
        'table' => InventoryController::ignore_barcode_nav_data($ignore_stock),
        '#weight' => '2',
      ];
      $form['found_barcode_nav'] = [
        '#type'  => 'details',
        '#title' => $this->t('Found Barcode NAV Code'),
        '#open'  => true,
        'table' => InventoryController::found_barcode_nav_data($found_stock,$param['warehouse_id'],$form),
        '#weight' => '4',
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
        '#weight' => '5',
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

  public function MGBStockUpload(array &$form, FormStateInterface $form_state) {
    $fields = $form_state->getValues();
    
    $uploaded_file = file_load($form_state->getValue('excel_file')[0]);
    $query_parameter = array();
    $query_parameter['mgbstockfid']=$uploaded_file->id();
    $query_parameter['warehouse_id']=$fields['warehouse_id'];
    $url = Url::fromRoute('inventory.mgb_stock_check_form', $query_parameter);
    return $form_state->setRedirectUrl($url);
  }

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
      InventoryController::modified_log($each_update_inventory['product_id'],$each_update_inventory['warehouse_id'],$each_update_inventory['warehouse_qty'],$each_update_inventory['qty'],'Relocation:MGB File Check');
      drupal_set_message('product_id: '.$each_update_inventory['product_id'].' '.$each_update_inventory['magento_sku'].' warehouse_id: '.$each_update_inventory['warehouse_id'].' qty update:'.$each_update_inventory['qty']);
    }


  }

}
