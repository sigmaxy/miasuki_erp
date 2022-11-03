<?php

namespace Drupal\inventory\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Drupal\warehouse\Controller\WarehouseController;
use Drupal\Core\Url;
use Drupal\attribute\Controller\AttributeController;
use Drupal\product\Controller\ProductController;

/**
 * Class InventoryController.
 */
class InventoryController extends ControllerBase {

  /**
   * Inventory.
   *
   * @return string
   *   Return Hello string.
   */
  public function GenerateDO($relocation_history_id) {
    $relocation_data = self::get_relocation_log_by_id($relocation_history_id);
    $data_items = json_decode($relocation_data['details'],1);
    $to_warehouse_id = $relocation_data['to_warehouse'];
    $do_template_file = drupal_realpath('public://development/DOTemplate.xlsx');
    $do_file_uri = 'public://miasuki_file/'.'Delivery Note_'.time().'.xlsx';
    $do_file = drupal_realpath($do_file_uri);
    $inputFileType = IOFactory::identify($do_template_file);
    $reader = IOFactory::createReader($inputFileType);
    $spreadsheet = $reader->load($do_template_file);
    $warehouse_address = WarehouseController::get_address_by_warehouse_id($to_warehouse_id);
    $warehouse_name = WarehouseController::get_all_warehouses()[$to_warehouse_id];
    $data_arr = array();
    $total_qty = 0;
    $row_count = 0;
    foreach ($data_items as $each_item_key=>$each_item) {
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
    $spreadsheet->getActiveSheet()->getCell('D19')->setValue($warehouse_name);
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
    return $this->redirect('inventory.relocation_history');
  }

  public function Inventory() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: Inventory')
    ];
  }
  public function get_ignore_type_options(){
    $ignore_opt = array(
      'milan_office'=> 'Milan Office',
      'mgb'=>'MGB',
    );
    return $ignore_opt;
  }
  public function check_ignore_list_by_nav_code($nav_code){
    $connection = Database::getConnection();
    $query_check = $connection->select('miasuki_ignore_list', 'mil')
        ->condition('nav_code', $nav_code)
        ->fields('mil');
    $record = $query_check->execute()->fetchAssoc();
    if (isset($record['id'])) {
      return $record;
    }else{
      return NULL;
    }
  }
  public function check_ignore_list_by_barcode($barcode){
    $connection = Database::getConnection();
    $query_check = $connection->select('miasuki_ignore_list', 'mil')
        ->condition('barcode', $barcode)
        ->fields('mil');
    $record = $query_check->execute()->fetchAssoc();
    if (isset($record['id'])) {
      return $record;
    }else{
      return NULL;
    }
  }
  public function milan_ignore_nav_data($ignore_stock){
    $ignore_stock_header = array(
      'milan_nav_code'=>'Nr. articolo',
      'milan_color_code'=>'Cod. variante',
      'qty'=>'Qtà disponibile da prendere',
    );
    $ignore_stock_rows=array();
    foreach ($ignore_stock as $each_ignore_stock){
      $ignore_stock_row_data = array();
      $ignore_stock_row_data['milan_nav_code'] = $each_ignore_stock['Nr. articolo'];
      $ignore_stock_row_data['milan_color_code'] = $each_ignore_stock['Cod. variante'];
      $ignore_stock_row_data['qty'] = $each_ignore_stock['Qtà disponibile da prendere'];
      $ignore_stock_rows[] = $ignore_stock_row_data;
    }
    $table_data = [
      '#type' => 'table',
      '#header' => $ignore_stock_header,
      '#rows' => $ignore_stock_rows,
      '#empty' => t('No Data found'),
      '#attributes' => [   
        'class' => [''],
      ],
    ];
    return $table_data;
  }
  public function milan_missing_nav_data($missing_stock){
    $missing_stock_header = array(
      'milan_nav_code'=>'Nr. articolo',
      'milan_color_code'=>'Cod. variante',
      'qty'=>'Qtà disponibile da prendere',
    );
    $missing_stock_rows=array();
    foreach ($missing_stock as $each_missing_stock){
      $missing_stock_row_data = array();
      $missing_stock_row_data['milan_nav_code'] = $each_missing_stock['Nr. articolo'];
      $missing_stock_row_data['milan_color_code'] = $each_missing_stock['Cod. variante'];
      $missing_stock_row_data['qty'] = $each_missing_stock['Qtà disponibile da prendere'];
      $missing_stock_rows[] = $missing_stock_row_data;
    }
    $table_data = [
      '#type' => 'table',
      '#header' => $missing_stock_header,
      '#rows' => $missing_stock_rows,
      '#empty' => t('No Data found'),
      '#attributes' => [   
        'class' => [''],
      ],
    ];
    return $table_data;
  }
  public function milan_found_nav_data($found_stock,$warehouse_id,array &$form){
    $found_stock_header = array(
      'product_id'=>'ERP Product ID',
      'magento_sku'=>'Magento SKU',
      'milan_nav_sku'=>'Nr. articolo Cod. variante',
      'qty'=>'File QTY',
      'warehouse_qty'=>'Warehouse QTY'
    );
    $found_stock_rows=array();
    foreach ($found_stock as $each_found_stock){
      if (isset($found_stock_rows[$each_found_stock['product_id']]['data']['magento_sku'])) {
        $found_stock_rows[$each_found_stock['product_id']]['data']['milan_nav_sku'] = $found_stock_rows[$each_found_stock['product_id']]['data']['milan_nav_sku'].';'.$each_found_stock['nav_sku'];
        $found_stock_rows[$each_found_stock['product_id']]['data']['qty'] +=  (int)$each_found_stock['QTA_FISICA'];
      }else{
        $found_stock_rows[$each_found_stock['product_id']]['data']['product_id'] = $each_found_stock['product_id'];
        $found_stock_rows[$each_found_stock['product_id']]['data']['magento_sku'] = $each_found_stock['magento_sku'];
        $found_stock_rows[$each_found_stock['product_id']]['data']['milan_nav_sku'] = $each_found_stock['nav_sku'];
        $found_stock_rows[$each_found_stock['product_id']]['data']['qty'] = $each_found_stock['Qtà disponibile da prendere'];
        $found_stock_rows[$each_found_stock['product_id']]['data']['warehouse_qty'] = self::get_inventory_by_productid_warehouseid($each_found_stock['product_id'],$warehouse_id);
      }
    }
    foreach ($found_stock_rows as $each_stock_row) {
      if ($each_stock_row['data']['qty']!=$each_stock_row['data']['warehouse_qty']) {
        $found_stock_rows[$each_stock_row['data']['product_id']]['class']='not_equal';
        $form['tmp']['data']['update_inventory_data'][$each_stock_row['data']['product_id']]=$each_stock_row['data'];
        $form['tmp']['data']['update_inventory_data'][$each_stock_row['data']['product_id']]['warehouse_id'] = $warehouse_id;
      }
    }
    $table_data = [
      '#type' => 'table',
      '#header' => $found_stock_header,
      '#rows' => $found_stock_rows,
      '#empty' => t('No Data found'),
      '#attributes' => [   
        'class' => [''],
      ],
    ];
    return $table_data;
  }
  public function manually_updated_inventory_data($manually_check_inventory_products,array &$form){
    $manually_check_inventory_header = array(
      'magento_sku'=>'Magento SKU',
      'warehouse_qty'=>'Warehouse QTY',
      'excel_qty'=>'Excel QTY',
    );
    $manually_check_inventory_rows=array();
    foreach ($manually_check_inventory_products as $each_manually_check_inventory_products){
      $each_manually_check_inventory_products_data = array();
      $each_manually_check_inventory_products_data['magento_sku'] = $each_manually_check_inventory_products['magento_sku'];
      $each_manually_check_inventory_products_data['warehouse_qty'] = $each_manually_check_inventory_products['db_inventory'];
      $each_manually_check_inventory_products_data['excel_qty'] = $each_manually_check_inventory_products['excel_qty'];
      $manually_check_inventory_rows[] = $each_manually_check_inventory_products_data;
      $form['tmp']['data']['manually_check_inventory_products'][]=$each_manually_check_inventory_products;
    }

    $table_data = [
      '#type' => 'table',
      '#header' => $manually_check_inventory_header,
      '#rows' => $manually_check_inventory_rows,
      '#empty' => t('No Data found'),
      '#attributes' => [   
        'class' => [''],
      ],
    ];
    return $table_data;
  }

  public function found_barcode_nav_data($found_stock,$warehouse_id,array &$form){
    $found_stock_header = array(
      'product_id'=>'ERP Product ID',
      'mgb_barcode'=>'CODICE_ARTICOLO',
      'mgb_nav_sku'=>'DESCRIZIONE_PRODOTTO',
      'magento_sku'=>'Magento SKU',
      'qty'=>'File QTY',
      'warehouse_qty'=>'Warehouse QTY'
    );
    $found_stock_rows=array();
    foreach ($found_stock as $each_found_stock){
      if (isset($found_stock_rows[$each_found_stock['product_id']]['data']['magento_sku'])) {
        $found_stock_rows[$each_found_stock['product_id']]['data']['mgb_barcode'] = $found_stock_rows[$each_found_stock['product_id']]['data']['mgb_barcode'].';'.$each_found_stock['CODICE_ARTICOLO'];
        $found_stock_rows[$each_found_stock['product_id']]['data']['mgb_nav_sku'] = $found_stock_rows[$each_found_stock['product_id']]['data']['mgb_nav_sku'].';'.$each_found_stock['DESCRIZIONE_PRODOTTO'];
        $found_stock_rows[$each_found_stock['product_id']]['data']['qty'] +=  (int)$each_found_stock['QTA_FISICA'];
      }else{
        $found_stock_rows[$each_found_stock['product_id']]['data']['product_id'] = $each_found_stock['product_id'];
        $found_stock_rows[$each_found_stock['product_id']]['data']['mgb_barcode'] = $each_found_stock['CODICE_ARTICOLO'];
        $found_stock_rows[$each_found_stock['product_id']]['data']['mgb_nav_sku'] = $each_found_stock['DESCRIZIONE_PRODOTTO'];
        $found_stock_rows[$each_found_stock['product_id']]['data']['magento_sku'] = $each_found_stock['magento_sku'];
        $found_stock_rows[$each_found_stock['product_id']]['data']['qty'] = $each_found_stock['QTA_FISICA'];
        $found_stock_rows[$each_found_stock['product_id']]['data']['warehouse_qty'] = self::get_inventory_by_productid_warehouseid($each_found_stock['product_id'],$warehouse_id);
      }
    }
    foreach ($found_stock_rows as $each_stock_row) {
      if ($each_stock_row['data']['qty']!=$each_stock_row['data']['warehouse_qty']) {
        $found_stock_rows[$each_stock_row['data']['product_id']]['class']='not_equal';
        $form['tmp']['data']['update_inventory_data'][$each_stock_row['data']['product_id']]=$each_stock_row['data'];
        $form['tmp']['data']['update_inventory_data'][$each_stock_row['data']['product_id']]['warehouse_id'] = $warehouse_id;
      }
    }
    $table_data = [
      '#type' => 'table',
      '#header' => $found_stock_header,
      '#rows' => $found_stock_rows,
      '#empty' => t('No Data found'),
      '#attributes' => [   
        'class' => [''],
      ],
    ];
    return $table_data;
  }

  public function ignore_barcode_nav_data($ignore_stock){
    $ignore_stock_header = array(
      'mgb_barcode'=>'CODICE_ARTICOLO',
      'mgb_nav_sku'=>'DESCRIZIONE_PRODOTTO',
      'mgb_description'=>'DESCRIZIONE_1',
      'qty'=>'QTY',
    );
    $ignore_stock_rows=array();
    foreach ($ignore_stock as $each_ignore_stock){
      $ignore_stock_row_data = array();
      $ignore_stock_row_data['mgb_barcode'] = $each_ignore_stock['CODICE_ARTICOLO'];
      $ignore_stock_row_data['mgb_nav_sku'] = $each_ignore_stock['DESCRIZIONE_PRODOTTO'];
      $ignore_stock_row_data['mgb_description'] = $each_ignore_stock['DESCRIZIONE_1'];
      $ignore_stock_row_data['qty'] = $each_ignore_stock['QTA_FISICA'];
      $ignore_stock_rows[] = $ignore_stock_row_data;
    }
    $table_data = [
      '#type' => 'table',
      '#header' => $ignore_stock_header,
      '#rows' => $ignore_stock_rows,
      '#empty' => t('No Data found'),
      '#attributes' => [   
        'class' => [''],
      ],
    ];
    return $table_data;
  }

  public function missing_barcode_nav_data($missing_stock){
    $missing_stock_header = array(
      'mgb_barcode'=>'CODICE_ARTICOLO',
      'mgb_nav_sku'=>'DESCRIZIONE_PRODOTTO',
      'mgb_description'=>'DESCRIZIONE_1',
      'qty'=>'QTY',
    );
    $missing_stock_rows=array();
    foreach ($missing_stock as $each_missing_stock){
      $missing_stock_row_data = array();
      $missing_stock_row_data['mgb_barcode'] = $each_missing_stock['CODICE_ARTICOLO'];
      $missing_stock_row_data['mgb_nav_sku'] = $each_missing_stock['DESCRIZIONE_PRODOTTO'];
      $missing_stock_row_data['mgb_description'] = $each_missing_stock['DESCRIZIONE_1'];
      $missing_stock_row_data['qty'] = $each_missing_stock['QTA_FISICA'];
      $missing_stock_rows[] = $missing_stock_row_data;
    }
    $table_data = [
      '#type' => 'table',
      '#header' => $missing_stock_header,
      '#rows' => $missing_stock_rows,
      '#empty' => t('No Data found'),
      '#attributes' => [   
        'class' => [''],
      ],
    ];
    return $table_data;
  }

  public function wrong_mapping_barcode_nav_data($wrong_barcode_stock){
    $wrong_mapping_header = array(
      'mgb_barcode'=>'CODICE_ARTICOLO',
      'mgb_nav_sku'=>'DESCRIZIONE_PRODOTTO',
      'magento_sku'=>'SKU',
      'mapping'=>'ERP Mapping Data',
    );
    $wrong_mapping_rows=array();
    foreach ($wrong_barcode_stock as $each_wrong_barcode_stock){
      $wrong_mapping_row_data = array();
      $wrong_mapping_row_data['mgb_barcode'] = $each_wrong_barcode_stock['CODICE_ARTICOLO'];
      $wrong_mapping_row_data['mgb_nav_sku'] = $each_wrong_barcode_stock['DESCRIZIONE_PRODOTTO'];
      $wrong_mapping_row_data['magento_sku'] = $each_wrong_barcode_stock['magento_sku'];
      $wrong_mapping_row_data['mapping'] = $each_wrong_barcode_stock['CODICE_ARTICOLO'].':'.$each_wrong_barcode_stock['nav_sku'];
      $wrong_mapping_rows[] = $wrong_mapping_row_data;
    }
    $table_data = [
      '#type' => 'table',
      '#header' => $wrong_mapping_header,
      '#rows' => $wrong_mapping_rows,
      '#empty' => t('No Data found'),
      '#attributes' => [   
        'class' => [''],
      ],
    ];
    return $table_data;
  }

  public function inventory_data_qty($products){
    $inventory_table_header = array();
    $inventory_table_header['index'] = t('Index');
    $warehouse_arr = WarehouseController::get_all_warehouses();
    foreach ($warehouse_arr as $warehouse_id=>$each_warehouse) {
      $inventory_table_header[$warehouse_id] = t($each_warehouse);
    }
    $inventory_table_header['total'] = t('Total');
    $inventory_table_header['change'] = t('Change');
    $inventory_table_rows=array();
    foreach ($products as $each_product_index=>$each_product){
      $inventory_table_row_data = array();
      $inventory_table_row_data['index'] = $each_product_index;
      $inventory_data = InventoryController::get_inventory_by_productid($each_product['id']);
      $total = 0;
      foreach ($warehouse_arr as $warehouse_id=>$each_inventory) {
        $inventory_table_row_data[$warehouse_id] = isset($inventory_data[$warehouse_id])?$inventory_data[$warehouse_id]:0;
        $total = $total + intval($inventory_data[$warehouse_id]);
      }
      $inventory_table_row_data['total'] = $total;
      $inventory_table_row_data['change_qty'] = isset($each_product['qty'])?$each_product['qty']:'';
      $inventory_table_rows[] = $inventory_table_row_data;
    }
    $table_data = [
      '#type' => 'table',
      '#header' => $inventory_table_header,
      '#rows' => $inventory_table_rows,
      '#empty' => t('No Data found'),
      '#attributes' => [   
        'class' => ['product_detail_table'],
      ],
    ];
    return $table_data;
  }
  public function inventory_data_price($products){
    $connection = Database::getConnection();
    $price_table_header = array(
      'index'=>'Index',
      'us_price'=>'US Price',
      'hk_price'=>'HK Price',
      'eu_price'=>'EU Price',
      'uk_price'=>'UK Price',
    );
    $price_table_rows=array();
    foreach ($products as $each_product_index=>$each_product){
      $query = $connection->select('miasuki_simple_product', 'msp')
        ->condition('id', $each_product['id'])
        ->fields('msp');
      $record = $query->execute()->fetchAssoc();
      
      $price_table_row_data = array();
      $us_special_price = !empty($record['us_special_price'])?'('.$record['us_special_price'].')':'';
      $hk_special_price = !empty($record['hk_special_price'])?'('.$record['hk_special_price'].')':'';
      $eu_special_price = !empty($record['eu_special_price'])?'('.$record['eu_special_price'].')':'';
      $hk_special_price = !empty($record['uk_special_price'])?'('.$record['uk_special_price'].')':'';
      $price_table_row_data['index'] = $each_product_index;
      $price_table_row_data['us_price'] = $record['us_price'].$us_special_price;
      $price_table_row_data['hk_price'] = $record['hk_price'].$hk_special_price;
      $price_table_row_data['eu_price'] = $record['eu_price'].$eu_special_price;
      $price_table_row_data['uk_price'] = $record['uk_price'].$uk_special_price;
      $price_table_rows[] = $price_table_row_data;
    }
    $table_data = [
      '#type' => 'table',
      '#header' => $price_table_header,
      '#rows' => $price_table_rows,
      '#empty' => t('No Data found'),
      '#attributes' => [   
        'class' => ['product_detail_table'],
      ],
    ];
    return $table_data;
  }
  public function inventory_data_detail($products){
    $connection = Database::getConnection();
    $detail_table_header = array(
      'index'=>'Index',
      'color'=>'Color',
      'nav_color'=>'Nav Color',
      'magento_color_id'=>'Magento Color ID',
      'size'=>'Size',
      'length'=>'Length',
    );
    $detail_table_rows=array();
    foreach ($products as $each_product_index=>$each_product) {
      $query = $connection->select('miasuki_simple_product', 'msp')
        ->condition('id', $each_product['id'])
        ->fields('msp');
      $record = $query->execute()->fetchAssoc();
      
      $color_data = AttributeController::get_color_by_id($record['color_id']);
      $size_data = AttributeController::get_size_by_id($record['size_id']);
      $length_data = AttributeController::get_length_by_id($record['length_id']);
      $detail_table_row_data = array();
      $detail_table_row_data['index'] = $each_product_index;
      $detail_table_row_data['color'] = $color_data['color'];
      $detail_table_row_data['nav_color'] = $color_data['nav_color'];
      $detail_table_row_data['magento_color_id'] = $color_data['magento_id'];
      $detail_table_row_data['size'] = $size_data['size'];
      $detail_table_row_data['length'] = $length_data['length'];
      $detail_table_rows[] = $detail_table_row_data;
    }
      
    $table_data = [
      '#type' => 'table',
      '#header' => $detail_table_header,
      '#rows' => $detail_table_rows,
      '#empty' => t('No Data found'),
      '#attributes' => [   
        'class' => ['product_detail_table'],
      ],
    ];
    return $table_data;
  }
  public function inventory_data_identification($products){
    $connection = Database::getConnection();
    $identification_table_header = array(
      'index'=>'Index',
      'name'=>'Name',
      'magento_sku'=>'Magento SKU',
      'nav_sku'=>'NAV SKU',
      'barcode'=>'Barcode',
      'id'=>'ERP ID',
    );
    $identification_table_rows=array();
    foreach ($products as $each_product_index=>$each_product) {
      $query = $connection->select('miasuki_simple_product', 'msp')
        ->condition('id', $each_product['id'])
        ->fields('msp');
      $record = $query->execute()->fetchAssoc();
      $identification_table_row_data = array();
      $identification_table_row_data['index'] = $each_product_index;
      $identification_table_row_data['name'] = $record['parent_sku'];
      $identification_table_row_data['magento_sku'] = $record['magento_sku'];
      $identification_table_row_data['nav_sku'] = implode(';', ProductController::get_nav_sku_by_product_id($each_product['id']));
      $identification_table_row_data['barcode'] = implode(';', ProductController::get_barcode_by_product_id($each_product['id']));
      $identification_table_row_data['id'] = $record['id'];
      $identification_table_rows[] = $identification_table_row_data;
    }
    $table_data = [
      '#type' => 'table',
      '#header' => $identification_table_header,
      '#rows' => $identification_table_rows,
      '#empty' => t('No Data found'),
      '#attributes' => [   
        'class' => ['product_detail_table'],
      ],
    ];
    return $table_data;
  }

  public function modified_log($product_id, $warehouse_id, $old_qty,$new_qty,$reason){
    $connection = Database::getConnection();
    $current_user = \Drupal::currentUser();
    $connection->insert('miasuki_inventory_log')
      ->fields([
        'product_id' => $product_id,
        'warehouse_id' => $warehouse_id,
        'old_qty' => $old_qty,
        'new_qty' => $new_qty,
        'reason' => $reason,
        'updated_by' => $current_user->id(),
        'updated_time' => time(),
      ])
      ->execute();
  }
  public function get_relocation_log_by_id($relocation_history_id){
    $connection = Database::getConnection();
    $query_check = $connection->select('miasuki_relocation_history', 'mrh')
        ->condition('id', $relocation_history_id)
        ->fields('mrh');
    $record = $query_check->execute()->fetchAssoc();
    return $record;
  }
  public function relocation_log($type,$from_warehouse,$to_warehouse, $remark,$details){
    $connection = Database::getConnection();
    $current_user = \Drupal::currentUser();
    $connection->insert('miasuki_relocation_history')
      ->fields([
        'type' => $type,
        'from_warehouse' => $from_warehouse,
        'to_warehouse' => $to_warehouse,
        'updated_by' => $current_user->id(),
        'updated_time' => time(),
        'remark' => $remark,
        'details' => $details,
      ])
      ->execute();
  }
  public function get_inventory_by_productid($product_id){
    $connection = Database::getConnection();
    $query_check = $connection->select('miasuki_inventory', 'mi')
      ->condition('product_id', $product_id)
      ->fields('mi');
    $record = $query_check->execute()->fetchAll();
    $inventory_arr = array();
    foreach ($record as $each) {
      // $inventory_arr[$each['warehouse_id']]=$each['qty'];
      $inventory_arr[$each->warehouse_id]=$each->qty;
    }
    return $inventory_arr;
  }
  public function get_inventory_by_productid_warehouseid($product_id, $warehouse_id){
    $connection = Database::getConnection();
    $query_check = $connection->select('miasuki_inventory', 'mi')
        ->condition('product_id', $product_id)
        ->condition('warehouse_id', $warehouse_id)
        ->fields('mi');
    $record = $query_check->execute()->fetchAssoc();
    if (isset($record['id'])) {
      return $record['qty'];
    }else{
      return 0;
    }
  }

}
