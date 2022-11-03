<?php

namespace Drupal\develop\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\attribute\Controller\AttributeController;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Drupal\product\Controller\ProductController;
use Drupal\api\Controller\ApiOrderController;
use Drupal\api\Controller\AuthenticateController;
use Drupal\inventory\Controller\InventoryController;
use Drupal\warehouse\Controller\WarehouseController;
/**
 * Class DevelopController.
 */
class DevelopController extends ControllerBase {

  /**
   * Action.
   *
   * @return string
   *   Return Hello string.
   */
  public function action($actionname = NULL) {
    switch ($actionname) {
      case 'test':
        $this->test();exit;
        break;
      case 'packing_formate':
        $this->packing_formate();exit;
        break;
      case 'update_ignore_list_mgb':
        $this->update_ignore_list_mgb();exit;
        break;
      case 'update_ignore_list_milan':
        $this->update_ignore_list_milan();exit;
        break;
      case 'sync_inventory_config_product_one':
        $this->sync_inventory_config_product_one();exit;
        break;
      case 'sync_inventory_simple_product_one':
        $this->sync_inventory_simple_product_one();exit;
        break;
      case 'sync_inventory_23':
        $this->sync_inventory_23();exit;
        break;
      case 'milan_inventory':
        $this->milan_inventory();exit;
        break;
      case 'milan_counting':
        $this->milan_counting();exit;
        break;
      case 'track_order':
        $this->track_order();exit;
        break;
      case 'check_duplicate_barcode':
        $this->check_duplicate_barcode();exit;
        break;
      case 'update_barcode':
        $this->update_barcode();exit;
        break;
      case 'update_nav_sku_config_product':
        $this->update_nav_sku_config_product();exit;
        break;
      case 'check_mgb_stock':
        $this->check_mgb_stock();exit;
        break;
      case 'check_mgb_stock_bac':
        $this->check_mgb_stock_bac();exit;
        break;
      case 'update_nav_sku':
        $this->update_nav_sku();exit;
        break;
      case 'readwords':
        $this->readwords();exit;
        break;
      case 'readxls':
        $this->readxls();exit;
        break;
      case 'missing_mapping_nav':
        $this->missing_mapping_nav();exit;
        break;
      case 'truncate_some_table':
        $this->truncate_some_table();exit;
        break;
      case 'truncate_order':
        $this->truncate_order();exit;
        break;
      case 'mapping_nav':
        $this->mapping_nav();exit;
        break;
      case 'readcsv':
        $this->readcsv();exit;
        break;
      default:
        echo "no action"; exit;
        break;
    }
    exit;
  }
  public function test(){
    echo 'test function';
    $result = ApiOrderController::synctrackingnumber(391,'testt','testc');
    print($result);
  }
  public function packing_formate(){
    // $packing_data = $this->readcsv('packing.csv');
    // echo 'barcode, sku, qty, remark<br>';
    // foreach ($packing_data as $each_row) {
    //   $barcode = $each_row['barcode'];
    //   if (empty($each_row['desc3'])) {
    //     $remark = $each_row['desc1'].'.'.$each_row['desc2'];
    //   }else{
    //     $remark = $each_row['desc1'].'.'.$each_row['desc2'].' '.$each_row['desc3'];
    //   }
      
    //   $desc2_arr = explode(' ', $each_row['desc1']);
    //   $qty = array_pop($desc2_arr);
    //   echo $barcode.', , '.$qty.', '.$remark.'<br>';
    // }
    // print_r($packing_data);
    $connection = Database::getConnection();
    $filepath = 'public://development/';
    $file_uri = $filepath.'relocation_1.xls';
    $inputFileType = IOFactory::identify($file_uri);
    $reader = IOFactory::createReader($inputFileType);
    $reader->setReadDataOnly(TRUE);
    $spreadsheet = $reader->load($file_uri);
    $sheetData = $spreadsheet->getSheet(0)->toArray();
    $relocation_data = $this->formate_sheet_data($spreadsheet->getSheet(0)->toArray());
    foreach ($relocation_data as $each_relo_row) {
      $barcode = $each_relo_row['barcode'];
      $simple_product_by_barcode = ProductController::get_simple_product_by_barcode(trim($barcode));
      if(isset($simple_product_by_barcode['magento_sku'])){
        echo $each_relo_row['barcode'].',,'.$each_relo_row['qty'].','.$each_relo_row['remark'];
        echo "\n";
      }else{
        // echo $each_relo_row['barcode'].',,'.$each_relo_row['qty'].','.$each_relo_row['remark'];
        // echo "\n";
        // print_r($each_relo_row);
      }
    }

    if (isset($simple_product_by_barcode['id'])) {
      $relocation_items[$each_item_index]['id'] = $simple_product_by_sku['id'];
    }else if(isset($simple_product_by_barcode['magento_sku'])){
      $relocation_items[$each_item_index]['id'] = $simple_product_by_barcode['id'];
      $relocation_items[$each_item_index]['sku'] = $simple_product_by_barcode['magento_sku'];
    }else{
      drupal_set_message('Row '.$each_item_index.' item not found','error');
      $item_valid_flag = false;
    }
  }
  public function update_ignore_list_mgb(){
    $ignore_mgb_data = $this->readcsv('ignore_list_mgb.csv');
    $connection = Database::getConnection();
    foreach ($ignore_mgb_data as $each_data) {
      $query = $connection->select('miasuki_ignore_list', 'mil');
      $query->fields('mil');
      $query->condition('barcode', $each_data['CODICE_ARTICOLO']);
      $results = $query->execute()->fetchAssoc();
      if (empty($results['id'])) {
        $db_fields = array(
          'type'=>'mgb',
          'barcode'=>$each_data['CODICE_ARTICOLO'],
          'nav_code'=>$each_data['DESCRIZIONE_PRODOTTO'],
          'remark'=>$each_data['DESCRIZIONE_1'],
        );
        $query_insert = $connection->insert('miasuki_ignore_list')
          ->fields($db_fields)
          ->execute();
      }
    }
  }
  public function update_ignore_list_milan(){
    $ignore_milan_data = $this->readcsv('ignore_list_milan.csv');
    $connection = Database::getConnection();
    foreach ($ignore_milan_data as $each_data) {
      $nav_code = trim($each_data['nav_sku']).' '.trim($each_data['color_code']);
      $query = $connection->select('miasuki_ignore_list', 'mil');
      $query->fields('mil');
      $query->condition('nav_code', $nav_code);
      $results = $query->execute()->fetchAssoc();
      if (empty($results['id'])) {
        $db_fields = array(
          'type'=>'milan_office',
          'barcode'=>'',
          'nav_code'=>$nav_code,
          'remark'=>$each_data['remark'],
        );
        $query_insert = $connection->insert('miasuki_ignore_list')
          ->fields($db_fields)
          ->execute();
      }
    }
  }
  public function assign_stock_source($inventory_data){
    $erp_stock = array();
    $erp_stock['default'] = intval($inventory_data[1]) + intval($inventory_data[2]) + intval($inventory_data[3]);
    $erp_stock['hk_source'] = intval($inventory_data[1]) + intval($inventory_data[2]) + intval($inventory_data[3]) + intval($inventory_data[4]) + intval($inventory_data[5]) + intval($inventory_data[6]);
    $erp_stock['cn_source'] = intval($inventory_data[4]) + intval($inventory_data[5]) + intval($inventory_data[6]);
    return $erp_stock;
  }
  public function get_child_product_inventory_status($parent_sku){
    $warehouse_arr = WarehouseController::get_all_warehouses();
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_simple_product', 'msp');
    $query->condition('parent_sku', $parent_sku);
    $query->fields('msp');
    $results = $query->execute()->fetchAll();
    $config_stock = array();
    foreach($results as $data){
      $magento_sku = $data->magento_sku;
      $parent_sku = $data->parent_sku;
      $erp_product_id = $data->id;
      $inventory_data = InventoryController::get_inventory_by_productid($erp_product_id);
      $erp_stock = self::assign_stock_source($inventory_data);
      $config_stock[$parent_sku]['us'] = $config_stock[$parent_sku]['us'] + $erp_stock['default'];
      $config_stock[$parent_sku]['eu'] = $config_stock[$parent_sku]['eu'] + $erp_stock['default'];
      $config_stock[$parent_sku]['uk'] = $config_stock[$parent_sku]['uk'] + $erp_stock['default'];
      $config_stock[$parent_sku]['hk'] = $config_stock[$parent_sku]['hk'] + $erp_stock['hk_source'];
      $config_stock[$parent_sku]['cn'] = $config_stock[$parent_sku]['cn'] + $erp_stock['cn_source'];
    }
    return $config_stock;
  }
  public function sync_inventory_config_product_one(){
    $mcp_id = isset($_POST['start_id'])?$_POST['start_id']:1;
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_config_product', 'mcp')
    ->condition('id', $mcp_id);
    $query->fields('mcp');
    $results = $query->execute()->fetchAssoc();
    $config_stock = self::get_child_product_inventory_status($results['magento_sku']);
    $result_product = array();
    foreach ($config_stock as $parent_sku => $each_stock) {
      foreach ($each_stock as $site_code => $stock) {
        if ($stock>0 && $results['status']==1) {
          $magento_status = 1;
        }else{
          $magento_status = 0;
        }
        $field = array(
          'product' => array(
            "sku" => $parent_sku,
            "status" => $magento_status,
          ),
          "saveOptions"=> true,
        );
        $api_product = AuthenticateController::api_call('products','POST',$field,$site_code);
        $result_product['sku']=$parent_sku;
        $result_product['mcp_id']=$mcp_id;
        $result_product[$site_code]=$api_product['status'];
      }
    }
    echo json_encode($result_product);
  }
  public function sync_inventory_simple_product_one(){
    $msp_id = isset($_POST['start_id'])?$_POST['start_id']:1;
    $warehouse_arr = WarehouseController::get_all_warehouses();
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_simple_product', 'msp')
    ->condition('id', $msp_id);
    $query->fields('msp');
    $results = $query->execute()->fetchAssoc();
    $magento_sku = $results['magento_sku'];
    $erp_product_id = $results['id'];
    $inventory_data = InventoryController::get_inventory_by_productid($erp_product_id);
    $erp_stock = self::assign_stock_source($inventory_data);
    $result_products = array();
    $field = array();
    foreach ($erp_stock as $source => $stock_total) {
      if ($stock_total==0) {
        $is_in_stock = 0;
      }else{
        $is_in_stock = 1;
      }
      $source_item = array(
        'sku' => $magento_sku,
        'source_code'=>$source,
        'quantity'=> $stock_total,
        'status'=> $is_in_stock,
      );
      $field['sourceItems'][]=$source_item;
      $api_product = AuthenticateController::api_call('products','POST',$field,$site_code);
      $result_products[$source]['stock']=$stock_total;
      $result_products[$source]['sku']=$magento_sku;
      $result_products[$source]['magento_id']=$api_product['id'];
      $result_products[$source]['is_in_stock']=$is_in_stock;
    }
    $api_product = AuthenticateController::api_call('inventory/source-items','POST',$field);
    $result_products['msp_id']=$erp_product_id;
    echo json_encode($result_products);
  }
  public function assign_sourcetostock(){
    $field = array(
      'sourceItems'=>array(
        1 => array(
          'sku'=>'narafs_white_46',
          'source_code'=>'cn_source',
          'quantity'=>70,
          'status'=>1,
        ),
        2 => array(
          'sku'=>'narafs_white_46',
          'source_code'=>'default',
          'quantity'=>80,
          'status'=>1,
        ),
        3 => array(
          'sku'=>'narafs_white_46',
          'source_code'=>'hk_source',
          'quantity'=>90,
          'status'=>1,
        ),
      )
    );
    $api_product = AuthenticateController::api_call('inventory/source-items','POST',$field);
    print_r($api_product);
    exit;
  }

  public function milan_inventory(){
    $connection = Database::getConnection();
    $filepath = 'public://development/';
    $file_uri = $filepath.'milan_inventory.xls';
    $inputFileType = IOFactory::identify($file_uri);
    $reader = IOFactory::createReader($inputFileType);
    $reader->setReadDataOnly(TRUE);
    $spreadsheet = $reader->load($file_uri);
    $sheetData = $spreadsheet->getSheet(0)->toArray();
    $mgb_stock_data = $this->formate_sheet_data($spreadsheet->getSheet(0)->toArray());
    foreach ($mgb_stock_data as $row=>$each_data) {
      $nav_code = trim($each_data['Nr. articolo']);
      $nav_desc = trim($each_data['DESCRIZIONE_PRODOTTO']);
      $nav_color_size = trim($each_data['Cod. variante']);
      $unit = trim($each_data['Cod. variante']);
      if (empty($each_data['magento_sku'])) {
        continue;
      }
      $count = $each_data['Count'];
      $magento_sku = $each_data['magento_sku'];
      $db_product = ProductController::get_simple_product_by_sku($magento_sku);
      if (isset($db_product['id'])) {
        //add qty to warehouse
        $check_to_query = $connection->select('miasuki_inventory', 'mi')
          ->condition('product_id', $db_product['id'])
          ->condition('warehouse_id', 3)
          ->fields('mi');
        $check_to_record = $check_to_query->execute()->fetchAssoc();
        if (empty($check_to_record['id'])) {
          //no record insert new
          $connection->insert('miasuki_inventory')
            ->fields([
              'product_id' => $db_product['id'],
              'warehouse_id' => 3,
              'qty' => $count,
            ])
            ->execute();
            InventoryController::modified_log($db_product['id'],3,0,$count,'Milan office count');
        }else{
          $new_inventory_to = intval($check_to_record['qty']) + intval($each_product['qty']);
          $connection->update('miasuki_inventory')
            ->fields([
              'qty' => $count,
            ])
            ->condition('product_id', $db_product['id'])
            ->condition('warehouse_id', 3)
            ->execute();
          InventoryController::modified_log($db_product['id'],3,$check_to_record['qty'],$count,'Milan office count');
        }
        //end of add qty to warehouse
      }else{
        echo "missing ".$each_data['magento_sku'].' '.$each_data['Count'].'<br>';

      }
    }
    // exit;
    // echo '<table><tr><td>Nr. articolo</td><td>DESCRIZIONE_PRODOTTO</td><td>Cod. variante</td><td>Cod. unità di misura</td><td>Qtà disponibile da prendere</td><td>magento_sku</td><td>erp_flag</td><td>comment</td></tr>';
    // foreach ($missing_stock as $each_stock) {
    //   echo '<tr>';
    //   echo '<td>'.$each_stock['Nr. articolo'].'</td>';
    //   echo '<td>'.$each_stock['DESCRIZIONE_PRODOTTO'].'</td>';
    //   echo '<td>'.$each_stock['Cod. variante'].'</td>';
    //   echo '<td>'.$each_stock['Cod. unità di misura'].'</td>';
    //   echo '<td>'.$each_stock['Qtà disponibile da prendere'].'</td>';
    //   echo '<td>'.$each_stock['magento_sku'].'</td>';
    //   echo '<td>'.$each_stock['erp_flag'].'</td>';
    //   echo '<td>'.$each_stock['comment'].'</td>';
    //   echo '</tr>';
    // }
    // echo '</table><br><br><br><br><br>';
    
  }
  public function milan_counting(){
    $connection = Database::getConnection();
    $filepath = 'public://development/';
    $file_uri = $filepath.'milan_counting.xls';
    $inputFileType = IOFactory::identify($file_uri);
    $reader = IOFactory::createReader($inputFileType);
    $reader->setReadDataOnly(TRUE);
    $spreadsheet = $reader->load($file_uri);
    $sheetData = $spreadsheet->getSheet(0)->toArray();
    $mgb_stock_data = $this->formate_sheet_data($spreadsheet->getSheet(0)->toArray());
    $new_product_arr = ['Kyla','Kamila','Luna','Molly','Rita','Gilda','Stella','Scarlett','Lexi','Marte'];
    foreach ($mgb_stock_data as $row=>$each_data) {
      // print_r($each_data);exit;
      $nav_code = trim($each_data['Nr. articolo']);
      $nav_desc = trim($each_data['DESCRIZIONE_PRODOTTO']);
      $nav_color_size = trim($each_data['Cod. variante']);
      $unit = trim($each_data['Cod. variante']);
      $count = $each_data['Cod. unità di misura'];
      $nav_sku = $nav_code.' '.$nav_color_size;
      $nav_desc_arr = explode(' ', $nav_desc);

      $query = $connection->select('miasuki_barcode', 'mb');
      $query->fields('mb');
      $query->condition('nav_sku', $nav_sku);
      $record = $query->execute()->fetchAssoc();
      $db_product = ProductController::get_simple_product_by_id($record['product_id']);
      if (isset($db_product['id'])) {
        $each_data['magento_sku'] = $db_product['magento_sku'];
        $each_data['erp_flag'] = '';
        $each_data['comment'] = '';
      }else{
        $each_data['magento_sku'] = '';
        $each_data['erp_flag'] = 'NOT Found in ERP';
        $each_data['comment'] = '';
        if (in_array($nav_desc_arr[1], $new_product_arr)) {
          $each_data['comment'] = 'SS19 New Product';
        }
        
        // echo 'index '.$row.': '.$nav_desc.' '.$nav_sku.' '.$nav_desc_arr[1].'<br>';
      }
      $missing_stock[] = $each_data;
    }
    // exit;
    echo '<table><tr><td>Nr. articolo</td><td>DESCRIZIONE_PRODOTTO</td><td>Cod. variante</td><td>Cod. unità di misura</td><td>Qtà disponibile da prendere</td><td>magento_sku</td><td>erp_flag</td><td>comment</td></tr>';
    foreach ($missing_stock as $each_stock) {
      echo '<tr>';
      echo '<td>'.$each_stock['Nr. articolo'].'</td>';
      echo '<td>'.$each_stock['DESCRIZIONE_PRODOTTO'].'</td>';
      echo '<td>'.$each_stock['Cod. variante'].'</td>';
      echo '<td>'.$each_stock['Cod. unità di misura'].'</td>';
      echo '<td>'.$each_stock['Qtà disponibile da prendere'].'</td>';
      echo '<td>'.$each_stock['magento_sku'].'</td>';
      echo '<td>'.$each_stock['erp_flag'].'</td>';
      echo '<td>'.$each_stock['comment'].'</td>';
      echo '</tr>';
    }
    echo '</table><br><br><br><br><br>';
    
  }
  public function helmet_mapping($product_id){
    $helmet_arr=array(
      1025 => 'Rider',
      1026 => 'Rider',
      1027 => 'Rider',
      1028 => 'Rider',
      1030 => 'Brocade',
      1031 => 'Brocade',
      1033 => 'Ribbon',
      1034 => 'Ribbon',
      1036 => 'Winner',
      1037 => 'Winner',
      1039 => 'Flame',
      1040 => 'Flame',
    );
    if (isset($helmet_arr[$product_id])) {
      return $helmet_arr[$product_id];
    }else{
      return null;
    }
  }
  public function get_order_item_option($order_id,$product_id,$store_id){
    $order_item_option = array();
    $apiorder_data = AuthenticateController::api_call('orders/items?searchCriteria[filter_groups][0][filters][0][field]=order_id&searchCriteria[filter_groups][0][filters][0][value]='.$order_id.'&searchCriteria[filter_groups][0][filters][0][condition_type]=eq','GET');
    foreach ($apiorder_data['items'] as $each_order_item) {
      if ($each_order_item['product_id']==$product_id) {
        $order_item_option = $each_order_item['parent_item']['product_option']['extension_attributes']['custom_options'];
      }
    }
    $apioption_data = AuthenticateController::api_call('products/'.self::helmet_mapping($product_id).'/options','GET');
    $order_item_option_data = array();
    foreach ($order_item_option as $each_order_item_option) {
      foreach ($apioption_data as $each_apioption_data) {
        if ($each_order_item_option['option_id']==$each_apioption_data['option_id']) {
          foreach ($each_apioption_data['values'] as $each_apioption_data_value) {
            if ($each_apioption_data_value['option_type_id']==$each_order_item_option['option_value']) {
              $order_item_option_value = $each_apioption_data_value['title'];
            }
          }
          if ($store_id==3||$store_id==4) {
            $option_sku_prefix = 'ava_amarone_';
          }else{
            $option_sku_prefix = 'boo_amarone_';
          }
          $order_item_option_data[] = array(
            'option_title'=>'helmet_lining',
            'option_id'=>$each_order_item_option['option_id'],
            'option_value'=>$each_order_item_option['option_value'],
            'option_text'=>$order_item_option_value,
            'option_sku'=>$option_sku_prefix.$order_item_option_value,
          );
        }
      }
    }
    return $order_item_option_data;
  }
  public function track_order(){
    $apiorder_data = AuthenticateController::api_call('orders?searchCriteria[filter_groups][0][filters][0][field]=entity_id&searchCriteria[filter_groups][0][filters][0][value]=230&searchCriteria[filter_groups][0][filters][0][condition_type]=gt','GET');
    print_r($apiorder_data);exit;
    foreach ($apiorder_data['items'] as $each_order) {
      foreach ($each_order['items'] as $key => $each_order_item) {
        $product_id = $each_order_item['product_id'];
        $order_id = $each_order_item['order_id'];
        $store_id = $each_order['store_id'];
        $helmet_cpsku = self::helmet_mapping($product_id);
        if (isset($helmet_cpsku)) {
          $order_item_option = self::get_order_item_option($order_id,$product_id,$store_id);
          print_r($order_item_option);
          exit;
        }
        # code...
      }
    }
    exit;
  }
  public function check_duplicate_barcode(){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_barcode', 'msp');
    $query->fields('msp');
    // $query->condition('barcode', '');
    $results = $query->execute()->fetchAll();
    foreach ($results as $each_data) {
      $query = $connection->select('miasuki_barcode', 'msp');
      $query->fields('msp');
      $query->condition('barcode', $each_data->barcode);
      $test = $query->execute()->fetchAll();
      if (count($test)>1) {
        echo $each_data->product_id.' '.$each_data->nav_sku.' '.$each_data->barcode.'test<br>';
      }
    }
  }
  public function update_barcode(){
    $new_product_barcode = $this->readcsv('new_product_barcode.csv');
    $connection = Database::getConnection();
    foreach ($new_product_barcode as $each_data) {
      $query = $connection->select('miasuki_simple_product', 'msp');
      $query->fields('msp');
      $query->condition('magento_sku', $each_data['Magenta SKU']);
      $results = $query->execute()->fetchAssoc();
      if (isset($results['id'])) {
        $check_query = $connection->select('miasuki_barcode', 'mb')
            ->condition('product_id', $results['id'])
            ->condition('barcode', $each_data['Bar Code'])
            ->fields('mb');
        $check_record = $check_query->execute()->fetchAssoc();
        if (empty($check_record['id'])) {
          $db_fields = array(
            'product_id'=>$results['id'],
            'barcode'=>$each_data['Bar Code'],
            'nav_sku'=>$each_data['Nav Code'],
          );
          $query_insert = $connection->insert('miasuki_barcode')
              ->fields($db_fields)
              ->execute();
        }
      }
    }
  }
  public function check_mgb_stock(){
    $connection = Database::getConnection();
    $filepath = 'public://development/';
    $file_uri = $filepath.'MGBstock.xls';
    $inputFileType = IOFactory::identify($file_uri);
    $reader = IOFactory::createReader($inputFileType);
    $reader->setReadDataOnly(TRUE);
    $spreadsheet = $reader->load($file_uri);
    $sheetData = $spreadsheet->getSheet(0)->toArray();
    $mgb_stock_data = $this->formate_sheet_data($spreadsheet->getSheet(0)->toArray());
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
      if (isset($db_product['id'])) {
        $each_data['magento_sku'] = $db_product['magento_sku'];
        $each_data['nav_sku'] = $record['nav_sku'];
        $found_stock[] = $each_data;
        if ($each_data['DESCRIZIONE_PRODOTTO']!=$record['nav_sku']) {
          $wrong_barcode_stock[] = $each_data;
        }
      }else{
        $missing_stock[] = $each_data;
      }
    }
    echo 'Missing Stock<br><br><table><tr><td>CODICE_ARTICOLO</td><td>DESCRIZIONE_PRODOTTO</td><td>DESCRIZIONE_1</td><td>DESCRIZIONE_2</td><td>DESC_UM_DI_RIFERIMENTO</td><td>QTA_FISICA</td><td>QTA_PRELEVATA</td><td>QTA_IMPEGNATA</td><td>QTA_BLOCCCATA</td></tr>';
    foreach ($missing_stock as $each_stock) {
      echo '<tr>';
      echo '<td>'.$each_stock['CODICE_ARTICOLO'].'</td>';
      echo '<td>'.$each_stock['DESCRIZIONE_PRODOTTO'].'</td>';
      echo '<td>'.$each_stock['DESCRIZIONE_1'].'</td>';
      echo '<td>'.$each_stock['magento_sku'].'</td>';
      echo '<td>'.$each_stock['DESC_UM_DI_RIFERIMENTO'].'</td>';
      echo '<td>'.$each_stock['QTA_FISICA'].'</td>';
      echo '<td>'.$each_stock['QTA_PRELEVATA'].'</td>';
      echo '<td>'.$each_stock['QTA_IMPEGNATA'].'</td>';
      echo '<td>'.$each_stock['QTA_BLOCCCATA'].'</td>';
      echo '</tr>';
    }
    echo '</table><br><br><br><br><br>';
    echo 'Wrong Barcode Stock<br><br><table><tr><td>CODICE_ARTICOLO</td><td>DESCRIZIONE_PRODOTTO</td><td>DESCRIZIONE_1</td><td>Magento SKU</td><td>ERP NAV Code</td><td>QTA_FISICA</td><td>QTA_PRELEVATA</td><td>QTA_IMPEGNATA</td><td>QTA_BLOCCCATA</td></tr>';
    foreach ($wrong_barcode_stock as $each_stock) {
      echo '<tr>';
      echo '<td>'.$each_stock['CODICE_ARTICOLO'].'</td>';
      echo '<td>'.$each_stock['DESCRIZIONE_PRODOTTO'].'</td>';
      echo '<td>'.$each_stock['DESCRIZIONE_1'].'</td>';
      echo '<td>'.$each_stock['magento_sku'].'</td>';
      echo '<td>'.$each_stock['nav_sku'].'</td>';
      echo '<td>'.$each_stock['QTA_FISICA'].'</td>';
      echo '<td>'.$each_stock['QTA_PRELEVATA'].'</td>';
      echo '<td>'.$each_stock['QTA_IMPEGNATA'].'</td>';
      echo '<td>'.$each_stock['QTA_BLOCCCATA'].'</td>';
      echo '</tr>';
    }
    echo '</table><br><br><br><br><br>';
    echo 'Found Stock<br><br><table><tr><td>CODICE_ARTICOLO</td><td>DESCRIZIONE_PRODOTTO</td><td>DESCRIZIONE_1</td><td>DESCRIZIONE_2</td><td>DESC_UM_DI_RIFERIMENTO</td><td>QTA_FISICA</td><td>QTA_PRELEVATA</td><td>QTA_IMPEGNATA</td><td>QTA_BLOCCCATA</td></tr>';
    foreach ($found_stock as $each_stock) {
      echo '<tr>';
      echo '<td>'.$each_stock['CODICE_ARTICOLO'].'</td>';
      echo '<td>'.$each_stock['DESCRIZIONE_PRODOTTO'].'</td>';
      echo '<td>'.$each_stock['DESCRIZIONE_1'].'</td>';
      echo '<td>'.$each_stock['magento_sku'].'</td>';
      echo '<td>'.$each_stock['DESC_UM_DI_RIFERIMENTO'].'</td>';
      echo '<td>'.$each_stock['QTA_FISICA'].'</td>';
      echo '<td>'.$each_stock['QTA_PRELEVATA'].'</td>';
      echo '<td>'.$each_stock['QTA_IMPEGNATA'].'</td>';
      echo '<td>'.$each_stock['QTA_BLOCCCATA'].'</td>';
      echo '</tr>';
    }
    echo '</table><br><br><br><br><br>';
    

  }
  public function check_mgb_stock_bac($filename){
    $filepath = 'public://development/';
    $file_uri = $filepath.'MGBstock.xls';
    $inputFileType = IOFactory::identify($file_uri);
    $reader = IOFactory::createReader($inputFileType);
    $reader->setReadDataOnly(TRUE);
    $spreadsheet = $reader->load($file_uri);
    $sheetData = $spreadsheet->getSheet(0)->toArray();
    $mgb_stock_data = $this->formate_sheet_data($spreadsheet->getSheet(0)->toArray());
    $found_stock = array();
    $wrong_barcode_stock = array();
    $missing_stock = array();
    $db_nav_color_code = array(' U099-',' U000-',' U110-',' U116-',' S588-',' U395-',' U382-');
    $mapping_nav_color_code = array(' F099-',' F000-',' S110-',' S116-',' U588-',' S395-',' S382-');
    foreach ($mgb_stock_data as $each_data) {
      $nav_sku = $each_data['DESCRIZIONE_PRODOTTO'];
      $replaced_nav_sku = str_replace($mapping_nav_color_code, $db_nav_color_code, $nav_sku);
      $db_product = ProductController::get_simple_product_by_nav($nav_sku);
      $replaced_db_product = ProductController::get_simple_product_by_nav($replaced_nav_sku);
      if (isset($db_product['id'])) {
        $found_stock[] = $each_data;
        if ($each_data['CODICE_ARTICOLO']!=$db_product['barcode']) {
          $wrong_barcode_stock[] = $each_data;
        }
      }else if(isset($replaced_db_product['id'])){
        $found_stock[] = $each_data;
        if ($each_data['CODICE_ARTICOLO']!=$replaced_db_product['barcode']) {
          $wrong_barcode_stock[] = $each_data;
        }
      }else{
        $missing_stock[] = $each_data;
      }
    }
    echo 'Wrong Barcode Stock<br><br><table><tr><td>CODICE_ARTICOLO</td><td>DESCRIZIONE_PRODOTTO</td><td>UM_DI_RIFERIMENTO</td><td>DESCRIZIONE_1</td><td>DESCRIZIONE_2</td><td>DESC_UM_DI_RIFERIMENTO</td><td>QTA_FISICA</td><td>QTA_PRELEVATA</td><td>QTA_IMPEGNATA</td><td>QTA_BLOCCCATA</td></tr>';
    foreach ($wrong_barcode_stock as $each_stock) {
      echo '<tr>';
      echo '<td>'.$each_stock['CODICE_ARTICOLO'].'</td>';
      echo '<td>'.$each_stock['DESCRIZIONE_PRODOTTO'].'</td>';
      echo '<td>'.$each_stock['UM_DI_RIFERIMENTO'].'</td>';
      echo '<td>'.$each_stock['DESCRIZIONE_1'].'</td>';
      echo '<td>'.$each_stock['DESCRIZIONE_2'].'</td>';
      echo '<td>'.$each_stock['DESC_UM_DI_RIFERIMENTO'].'</td>';
      echo '<td>'.$each_stock['QTA_FISICA'].'</td>';
      echo '<td>'.$each_stock['QTA_PRELEVATA'].'</td>';
      echo '<td>'.$each_stock['QTA_IMPEGNATA'].'</td>';
      echo '<td>'.$each_stock['QTA_BLOCCCATA'].'</td>';
      echo '</tr>';
    }
    echo '</table><br><br><br><br><br>';
    echo 'Found Stock<br><br><table><tr><td>CODICE_ARTICOLO</td><td>DESCRIZIONE_PRODOTTO</td><td>UM_DI_RIFERIMENTO</td><td>DESCRIZIONE_1</td><td>DESCRIZIONE_2</td><td>DESC_UM_DI_RIFERIMENTO</td><td>QTA_FISICA</td><td>QTA_PRELEVATA</td><td>QTA_IMPEGNATA</td><td>QTA_BLOCCCATA</td></tr>';
    foreach ($found_stock as $each_stock) {
      echo '<tr>';
      echo '<td>'.$each_stock['CODICE_ARTICOLO'].'</td>';
      echo '<td>'.$each_stock['DESCRIZIONE_PRODOTTO'].'</td>';
      echo '<td>'.$each_stock['UM_DI_RIFERIMENTO'].'</td>';
      echo '<td>'.$each_stock['DESCRIZIONE_1'].'</td>';
      echo '<td>'.$each_stock['DESCRIZIONE_2'].'</td>';
      echo '<td>'.$each_stock['DESC_UM_DI_RIFERIMENTO'].'</td>';
      echo '<td>'.$each_stock['QTA_FISICA'].'</td>';
      echo '<td>'.$each_stock['QTA_PRELEVATA'].'</td>';
      echo '<td>'.$each_stock['QTA_IMPEGNATA'].'</td>';
      echo '<td>'.$each_stock['QTA_BLOCCCATA'].'</td>';
      echo '</tr>';
    }
    echo '</table><br><br><br><br><br>';
    echo 'Missing Stock<br><br><table><tr><td>CODICE_ARTICOLO</td><td>DESCRIZIONE_PRODOTTO</td><td>UM_DI_RIFERIMENTO</td><td>DESCRIZIONE_1</td><td>DESCRIZIONE_2</td><td>DESC_UM_DI_RIFERIMENTO</td><td>QTA_FISICA</td><td>QTA_PRELEVATA</td><td>QTA_IMPEGNATA</td><td>QTA_BLOCCCATA</td></tr>';
    foreach ($missing_stock as $each_stock) {
      echo '<tr>';
      echo '<td>'.$each_stock['CODICE_ARTICOLO'].'</td>';
      echo '<td>'.$each_stock['DESCRIZIONE_PRODOTTO'].'</td>';
      echo '<td>'.$each_stock['UM_DI_RIFERIMENTO'].'</td>';
      echo '<td>'.$each_stock['DESCRIZIONE_1'].'</td>';
      echo '<td>'.$each_stock['DESCRIZIONE_2'].'</td>';
      echo '<td>'.$each_stock['DESC_UM_DI_RIFERIMENTO'].'</td>';
      echo '<td>'.$each_stock['QTA_FISICA'].'</td>';
      echo '<td>'.$each_stock['QTA_PRELEVATA'].'</td>';
      echo '<td>'.$each_stock['QTA_IMPEGNATA'].'</td>';
      echo '<td>'.$each_stock['QTA_BLOCCCATA'].'</td>';
      echo '</tr>';
    }
    echo '</table>';
    // print_r($mgb_stock_data);
    exit;
  }
  public function update_nav_sku_config_product(){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_simple_product', 'msp');
    $query->fields('msp');
    // $query->condition('barcode', '');
    $results = $query->execute()->fetchAll();
    foreach ($results as $each_data) {
      $db_fields = array();
      $color_data = AttributeController::get_color_by_id($each_data->color_id);
      $size_data = AttributeController::get_size_by_id($each_data->size_id);
      if (!empty($each_data->nav_sku)) {
        $config_product_nav_sku = explode(' ', $each_data->nav_sku)[0];

      }else{
        $config_product_nav_sku = '';
      }
      $db_fields = array(
        'nav_sku'=>$config_product_nav_sku,
      );
      $query_update = $connection->update('miasuki_config_product')
          ->fields($db_fields)
          ->condition('magento_sku', $each_data->parent_sku)
          ->execute();
    }
  }
  public function update_nav_sku(){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_simple_product', 'msp');
    $query->fields('msp');
    // $query->condition('barcode', '');
    $results = $query->execute()->fetchAll();
    foreach ($results as $each_data) {
      $db_fields = array();
      $color_data = AttributeController::get_color_by_id($each_data->color_id);
      $size_data = AttributeController::get_size_by_id($each_data->size_id);
      
      if (!empty($each_data->parent_sku)) {
        $config_product_nav_sku = explode(' ', $each_data->nav_sku)[0];
        $update_nav_sku = $config_product_nav_sku.' '.$color_data['nav_color'].'-'.$size_data['nav_size'];
        echo $update_nav_sku.'<br>';
      }else{
        $update_nav_sku = '';
      }
      $db_fields = array(
        'nav_sku'=>$update_nav_sku,
      );
      $query_update = $connection->update('miasuki_simple_product')
          ->fields($db_fields)
          ->condition('id', $each_data->id)
          ->execute();
    }
  }
  public function truncate_some_table(){
    $connection = Database::getConnection();
    $query = $connection->truncate('miasuki_inventory')->execute();
    $query = $connection->truncate('miasuki_inventory_log')->execute();
  }
  public function truncate_order(){
    $connection = Database::getConnection();
    $query = $connection->truncate('miasuki_order')->execute();
    $query = $connection->truncate('miasuki_order_address')->execute();
    $query = $connection->truncate('miasuki_order_item')->execute();
  }
  public function missing_mapping_nav(){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_simple_product', 'msp');
    // $query->fields('msp', ['id','magento_sku','parent_sku','color_id','size_id','length_id']);
    $query->fields('msp');
    // $db_or = db_or();
    // $db_or->condition('msp.barcode', '');
    // $db_or->condition('msp.nav_sku', '');
    // $query->condition($db_or);
    // $color_data = AttributeController::get_color_by_id($each_data->color_id);
    // $size_data = AttributeController::get_size_by_id($each_data->size_id)['size'];
    // $length_data = AttributeController::get_length_by_id($each_data->length_id)['length'];

    $query->condition('barcode', '');
    $results = $query->execute()->fetchAll();

    echo '<table><tr><th>Product</th><th>Magento SKU</th><th>Color</th><th>Size</th><th>Length</th><th>Barcode</th><th>NAV SKU</th></tr>';
    foreach ($results as $each_data) {
      // print_r($each_data);
      // print_r(AttributeController::get_color_by_id($each_data->color_id)); 
        echo '<tr><td>'.$each_data->parent_sku.'</td><td>'.$each_data->magento_sku.'</td><td>'.AttributeController::get_color_by_id($each_data->color_id)['color'].'</td><td>'.AttributeController::get_size_by_id($each_data->size_id)['size'].'</td><td>'.AttributeController::get_length_by_id($each_data->length_id)['length'].'</td><td></td><td></td></tr>';
    }
    echo '</table>';
  }
  public function mapping_nav(){
    $connection = Database::getConnection();
    $csv_data = $this->readcsv('mapping_nav_code.csv');
    $mapping_data = array();
    foreach ($csv_data as $key => $each_row) {
      $row_index = $key+1;
      $name_arr = explode(' ', $each_row['name']);
      $parent_sku = $name_arr[1];
      $attr_arr = explode(', ', $each_row['color_size']);
      $sku = strtolower($name_arr[1].'_'.str_replace(array(', ',' '), '_', $each_row['color_size']));
      // $sku = str_replace(' ', '_', trim($sku_temp));
      $mapping_data[] = array(
        'barcode' => $each_row['barcode'],
        'nav_sku' => $each_row['nav_sku'],
        'description' => $each_row['name'],
        'parent_sku' => $name_arr[1],
        'nav_color' => $each_row['nav_color'],
        'color_size' => $each_row['color_size'],
        'color' => $attr_arr[0],
        'size' => $attr_arr[1],
        'sku' => $sku,
      );

      $record = array();
      $query_check = $connection->select('miasuki_simple_product', 'msp')
          ->condition('magento_sku', $sku)
          ->fields('msp');

      // echo 'test'.$sku.'<br>';
      $record = $query_check->execute()->fetchAssoc();
      if (empty($record['id'])) {
        echo 'row '.$row_index.' not found '.$sku.' Parent SKU: '.$name_arr[1].' ';
        echo '<br>';
      }else{
        $db_fields = array(
          'nav_sku' => $each_row['nav_sku'],
          'barcode' => $each_row['barcode'],
        );
        $query_update = $connection->update('miasuki_simple_product')
          ->fields($db_fields)
          ->condition('id', $record['id'])
          ->execute();
      }
    }
    print_r($mapping_data);
  }
  public function readcsv($filename){
    $filepath = 'public://development/';
    $file_uri = $filepath.$filename;
    $row = 1;
    $data_index = array();
    $all_data = array();
    $row_data = array();
    if (($handle = fopen($file_uri, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, ",")) !== FALSE) {
            $num = count($data);
            if ($row==1) {
                $data_index = $data;
            }else{
                for ($c=0; $c < $num; $c++) {
                    $row_data[$data_index[$c]]= $data[$c];
                }
                $all_data[] = $row_data;
            }
            $row++;                    
        }
        fclose($handle);
    }
    return $all_data;
  }
  function write($phpWord, $filename)
  {
      $result = '';
      $writers = array('Word2007' => 'docx', 'ODText' => 'odt', 'RTF' => 'rtf', 'HTML' => 'html', 'PDF' => 'pdf');
      // Write documents
      foreach ($writers as $format => $extension) {
          $result .= date('H:i:s') . " Write to {$format} format";
          if (null !== $extension) {
              $targetFile = __DIR__ . "/results/{$filename}.{$extension}";
              $phpWord->save($targetFile, $format);
          } else {
              $result .= ' ... NOT DONE!';
          }
          $result .= EOL;
      }

      $result .= getEndingNotes($writers, $filename);

      return $result;
  }

  public function readwords($filename){
    $do_template_file = drupal_realpath('public://development/'.'DOtemplate.docx');
    $do_file = drupal_realpath('public://development/'.'Delivery Note_'.time().'.docx');

    // $phpWord = \PhpOffice\PhpWord\IOFactory::load($do_template_file);
    // $phpWord->save($do_file, 'Word2007');

    // $do_template_file = drupal_realpath('public://development/'.'s07.docx');
    // $do_file = drupal_realpath('public://development/'.'s07_'.time().'.docx');


    $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($do_template_file);
    $templateProcessor->setValue('do_address', 'Miasuki Asia LTD.'."\n".'5/F Sea Bird House'."\n".'22-28 Wyndham Street'."\n".'Central'."\n".'Hong Kong');
    $templateProcessor->setValue('do_date', date('Y-m-d'));

    // // Simple table
    $templateProcessor->cloneRow('item_index', 1);

    $templateProcessor->setValue('item_index#1', '1');
    $templateProcessor->setValue('item_name#1', '2');
    $templateProcessor->setValue('item_color#1', '3');
    $templateProcessor->setValue('item_size#1', 'test1');
    $templateProcessor->setValue('item_qty#1', 'test2');
    $templateProcessor->setValue('item_price#1', 'test3');
    $templateProcessor->setValue('item_amount#1', 'test4');



    // // Table with a spanned cell
    // $templateProcessor->cloneRow('userId', 3);

    // $templateProcessor->setValue('userId#1', '1');
    // $templateProcessor->setValue('userFirstName#1', 'James');
    // $templateProcessor->setValue('userName#1', 'Taylor');
    // $templateProcessor->setValue('userPhone#1', '+1 428 889 773');

    // $templateProcessor->setValue('userId#2', '2');
    // $templateProcessor->setValue('userFirstName#2', 'Robert');
    // $templateProcessor->setValue('userName#2', 'Bell');
    // $templateProcessor->setValue('userPhone#2', '+1 428 889 774');

    // $templateProcessor->setValue('userId#3', '3');
    // $templateProcessor->setValue('userFirstName#3', 'Michael');
    // $templateProcessor->setValue('userName#3', 'Ray');
    // $templateProcessor->setValue('userPhone#3', '+1 428 889 775');

    echo date('H:i:s'), ' Saving the result document...', EOL;
    $templateProcessor->saveAs($do_file);




  }
  public function readxls($filename){

    $filepath = 'public://development/';
    $file_uri = $filepath.'test.xls';
    $file_uri = $filepath.'B2BOrderForm.xls';
    $inputFileType = IOFactory::identify($file_uri);
    $reader = IOFactory::createReader($inputFileType);
    $reader->setReadDataOnly(TRUE);
    $spreadsheet = $reader->load($file_uri);
    $sheetData = $spreadsheet->getSheet(0)->toArray();

    print_r($sheetData);

    $order_detail_data = $this->formate_sheet_data($spreadsheet->getSheet(0)->toArray());
    print_r($order_detail_data);
    $order_item_data = $this->formate_sheet_data($spreadsheet->getSheet(1)->toArray());
    print_r($order_item_data);
    $order_address_data = $this->formate_sheet_data($spreadsheet->getSheet(2)->toArray());
    print_r($order_address_data);
    exit;
  }
  public function formate_sheet_data($sheetData){
    $formatesheetData = array();
    foreach ($sheetData as $key => $row_data) {
      if ($key==0) {
        $sheetData_header = $row_data;
      }else{
        foreach ($row_data as $cell_index => $cell_value) {
          $formatesheetData[$key][$sheetData_header[$cell_index]]=$cell_value;
        }
      }
    }
    return $formatesheetData;
  }
  public function writexls($filename){

    $filepath = 'public://development/';
    $file_uri = $filepath.'B2BOrderForm.xlsx';
    // $file_uri = $filepath.'testxlsx.xlsx';
    // $inputFileType = IOFactory::identify($file_uri);
    // $inputFileType = 'Xlsx';
    // $reader = IOFactory::createReader($inputFileType);
    $reader = IOFactory::createReader('Xlsx');
    $reader->setReadDataOnly(TRUE);
    $spreadsheet = $reader->load($file_uri);

    // $spreadsheet = IOFactory::load($file_uri);
    // $sheetData = $spreadsheet->getSheet(1)->toArray(null, true, true, true);
    $sheetData = $spreadsheet->getSheet(0)->toArray();

    print_r($sheetData);
    exit;
  }
  
}
