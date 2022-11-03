<?php

namespace Drupal\product\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\product\Ajax\ProductAjaxCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use Drupal\inventory\Controller\InventoryController;
use Drupal\warehouse\Controller\WarehouseController;

/**
 * Class ProductController.
 */
class ProductController extends ControllerBase {

  /**
   * List.
   *
   * @return string
   *   Return Hello string.
   */
  public function barcode_nav_detail($form,$product_id){
    
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_barcode', 'mb')
      ->condition('product_id', $product_id)
      ->fields('mb');
    $record = $query->execute()->fetchAll();
    $detail_table_header = array(
      'mapping_id'=>'Mapping ID',
      'barcode'=>'Barcode',
      'nav_sku'=>'Nav SKU',
    );
    $detail_table_rows=array();
    $elements = array();
    foreach ($record as $each_barcode_mapping) {
      $detail_table_row_data = array();
      $form['barcode-'.$each_barcode_mapping->id] = [
        '#type' => 'textfield',
        // '#title' => 'Barcode',
        '#default_value' => $each_barcode_mapping->barcode,
      ];
      $detail_table_row_data['mapping_id'] = $each_barcode_mapping->id;
      // $detail_table_row_data['barcode'] = $each_barcode_mapping->barcode;
      $detail_table_row_data['barcode'] = drupal_render($form['barcode-'.$each_barcode_mapping->id]);
      $detail_table_row_data['nav_sku'] = $each_barcode_mapping->nav_sku;
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
  public function get_config_product_by_sku($sku){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_config_product', 'mcp');
    $query->fields('mcp');
    $query->condition('magento_sku', $sku);
    $record = $query->execute()->fetchAssoc();
    return $record;
  }
  public function get_simple_product_by_sku($sku){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_simple_product', 'msp');
    $query->fields('msp');
    $query->condition('magento_sku', $sku);
    $record = $query->execute()->fetchAssoc();
    return $record;
  }
  public function get_simple_product_by_id($product_id){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_simple_product', 'msp');
    $query->fields('msp');
    $query->condition('id', $product_id);
    $record = $query->execute()->fetchAssoc();
    return $record;
  }
  public function get_simple_product_by_nav($navsku){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_barcode', 'mb');
    $query->fields('mb');
    $query->condition('nav_sku', $navsku);
    $record = $query->execute()->fetchAssoc();
    if (isset($record['product_id'])) {
      $product = self::get_simple_product_by_id($record['product_id']);
    }else{
      $product = null;
    }
    return $product;
  }
  public function get_barcode_by_product_id($product_id){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_barcode', 'mb');
    $query->fields('mb');
    $query->condition('product_id', $product_id);
    $results = $query->execute()->fetchAll();
    $barcode_arr = array();
    foreach ($results as $each_barcode_nav) {
      $barcode_arr[] = $each_barcode_nav->barcode;
    }
    return $barcode_arr;
  }
  public function get_nav_sku_by_product_id($product_id){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_barcode', 'mb');
    $query->fields('mb');
    $query->condition('product_id', $product_id);
    $results = $query->execute()->fetchAll();
    $nav_arr = array();
    foreach ($results as $each_barcode_nav) {
      $nav_arr[] = $each_barcode_nav->nav_sku;
    }
    return $nav_arr;
  }
  public function get_simple_product_by_barcode($barcode){
    $connection = Database::getConnection();
    $barcode_query = $connection->select('miasuki_barcode', 'mb');
    $barcode_query->fields('mb');
    $barcode_query->condition('barcode', $barcode);
    $barcode_record = $barcode_query->execute()->fetchAssoc();
    $product_query = $connection->select('miasuki_simple_product', 'msp');
    $product_query->fields('msp');
    $product_query->condition('id', $barcode_record['product_id']);
    $barcode_record = $product_query->execute()->fetchAssoc();
    return $barcode_record;
  }
  public function list_config_products() {
    $header_table = array(
      'id'=>    t('ID'),
      'magento_sku' => t('Mangento SKU'),
      'name' =>  t('Name'),
      'opt1' => t('View/Edit'),
    );
    $query = \Drupal::database()->select('miasuki_config_product', 'mcp');
    $query->fields('mcp', ['id','magento_sku','name']);
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
    $results = $pager->execute()->fetchAll();
    $rows=array();
    foreach($results as $data){
        $edit   = Url::fromUserInput('/product/form/config_product/'.$data->id);
      //print the data from table
        $rows[] = array(
          'id' =>$data->id,
          'magento_sku' => $data->magento_sku,
          'name' => $data->name,
           \Drupal::l('View/Edit', $edit),
        );
    }

    $form['filters'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Filter'),
        '#open'  => true,
    ];

    $form['filters']['magento_sku'] = [
        '#title'         => 'SKU',
        '#type'          => 'search',
        // '#default_value' =>  \Drupal::request()->request->get('magento_sku'),
        // '#default_value' => isset($form_state['filters']['magento_sku'])?$form_state['filters']['magento_sku']:'',
    ];
    $form['filters']['name'] = [
        '#title'         => 'Name',
        '#type'          => 'search'
    ];

    // $form['form']['filters']['actions'] = [
    //     '#type'       => 'actions'
    // ];

    $form['filters']['action'] = [
        '#type'  => 'button',
        '#value' => $this->t('Filter'),
        // '#submit' => array('filter_table'),
    ];
    $form['filters']['actions'] = array(
      '#type' => 'submit',
      '#value' => t('test'),
      '#submit' => array('::$this->filter_table()'),
      // '#submit' => $this->filter_table(),
    );

    //display data in site
    $form['table'] = [
      '#type' => 'table',
      '#header' => $header_table,
      '#rows' => $rows,
      '#empty' => t('No users found'),
    ];
    $form['pager'] = array(
      '#type' => 'pager'
    );
    return $form;
  }
  public function filter_table() {
    print_r('test');
    // $url = Url::fromRoute('product.product_controller_list', [], ['query' => ['word' => 'test']]);
    // $form_state->setRedirectUrl($url);
  }
  public function product_status(){
    $product_status = array(
      1 => 'Enabled',
      2 => 'Disabled',
    );
    return $product_status;
  }
  public function get_product_statusid_by_status($status) {
    return array_search($status, self::product_status());
  }
  public function get_product_status_by_statusid($statusid) {
    return self::product_status()[$statusid];
  }
  public function ajax_simple_product_info($magento_sku) {
    $product = $this->get_simple_product_by_sku($magento_sku);
    if (isset($product['id'])) {
      $query = Database::getConnection()->select('miasuki_barcode', 'mb')
        ->condition('product_id', $product['id'])
        ->fields('mb');
      $record = $query->execute()->fetchAll();
      if (count($record)==0) {
        $product['barcode_mapping'][0] = array(
          'barcode'=>null,
          'nav_sku'=>null,
        );
      }else{
        foreach ($record as $each_barcode_mapping) {
          $product['barcode_mapping'][] = array(
            'barcode'=>$each_barcode_mapping->barcode,
            'nav_sku'=>$each_barcode_mapping->nav_sku,
          );
        }
      }
      $warehouse_arr = WarehouseController::get_all_warehouses();
      $inventory_data = InventoryController::get_inventory_by_productid($product['id']);
      $product['inventory_total'] = 0;
      foreach ($warehouse_arr as $warehouse_id=>$each_inventory) {
        $product['inventory'][$warehouse_id] = isset($inventory_data[$warehouse_id])?$inventory_data[$warehouse_id]:0;
        $product['inventory_total'] = $product['inventory_total'] + intval($inventory_data[$warehouse_id]);
      }
      $status = true;
      $result = $product;
      $message = 'Product Found';
    }else{
      $status = false;
      $result = null;
      $message = 'Product Not Found';
    }
    // Create AJAX Response object.
    $response = new AjaxResponse();
    // Call the readMessage javascript function.
    $response->addCommand( new ProductAjaxCommand('ajax_simple_product_info',$status,$result,$message));
   // Return ajax response.
   return $response;
  }
  public function ajax_simple_product_info_bybarcode($barcode) {
    $product = $this->get_simple_product_by_barcode($barcode);
    if (isset($product['id'])) {
      $query = Database::getConnection()->select('miasuki_barcode', 'mb')
        ->condition('product_id', $product['id'])
        ->fields('mb');
      $record = $query->execute()->fetchAll();
      if (count($record)==0) {
        $product['barcode_mapping'][0] = array(
          'barcode'=>null,
          'nav_sku'=>null,
        );
      }else{
        foreach ($record as $each_barcode_mapping) {
          $product['barcode_mapping'][] = array(
            'barcode'=>$each_barcode_mapping->barcode,
            'nav_sku'=>$each_barcode_mapping->nav_sku,
          );
        }
      }
      $warehouse_arr = WarehouseController::get_all_warehouses();
      $inventory_data = InventoryController::get_inventory_by_productid($product['id']);
      $product['inventory_total'] = 0;
      foreach ($warehouse_arr as $warehouse_id=>$each_inventory) {
        $product['inventory'][$warehouse_id] = isset($inventory_data[$warehouse_id])?$inventory_data[$warehouse_id]:0;
        $product['inventory_total'] = $product['inventory_total'] + intval($inventory_data[$warehouse_id]);
      }
      $status = true;
      $result = $product;
      $message = 'Product Found';
    }else{
      $status = false;
      $result = null;
      $message = 'Product Not Found';
    }
    // Create AJAX Response object.
    $response = new AjaxResponse();
    // Call the readMessage javascript function.
    $response->addCommand( new ProductAjaxCommand('ajax_simple_product_info_bybarcode',$status,$result,$message));
   // Return ajax response.
   return $response;
  }
  public function autocomplete_simple_product_sku(Request $request, $count) {
    $results = [];

    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = Unicode::strtolower(array_pop($typed_string));
      $connection = Database::getConnection();
      $query = $connection->select('miasuki_simple_product', 'msp');
      $query->fields('msp');
      $query->condition('magento_sku', '%'.$typed_string.'%','LIKE');
      $records = $query->execute()->fetchAll();
      $i = 0;
      foreach ($records as $each_data) {
        if ($i< $count) {
          $results[] = [
            'value' => $each_data->magento_sku,
            'label' => $each_data->magento_sku,
          ];
          $i++;
        }
        
      }
    }
    return new JsonResponse($results);
  }
}
