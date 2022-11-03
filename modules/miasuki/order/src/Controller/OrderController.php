<?php

namespace Drupal\order\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\attribute\Controller\AttributeController;
use Drupal\product\Controller\ProductController;
use Drupal\warehouse\Controller\WarehouseController;
use Drupal\inventory\Controller\InventoryController;

/**
 * Class OrderController.
 */
class OrderController extends ControllerBase {

  /**
   * Get_order_by_id.
   *
   * @return string
   *   Return Hello string.
   */
  public function get_payment_options(){
    $order_type_opt = array(
      'Cash' => 'Cash',
      'Transfer' => 'Transfer',
      'Paypal' => 'Paypal',
      'Alipay' => 'Alipay',
      'Wechatpay' => 'Wechatpay',
      'Credit Card' => 'Credit Card',
      'Cheque' => 'Cheque',
      'Transfer' => 'Transfer',
    );
    return $order_type_opt;
  }
  public function get_ordertype_options(){
    $order_type_opt = array(
      'B2B' => 'B2B',
      'offline' => 'Offline',
      'samplesale' => 'Sample Sale',
      'ps' => 'Photo Shooting',
    );
    return $order_type_opt;
  }
  public function order_data_detail($order_record){
    // print_r($order_id);exit;
    $connection = Database::getConnection();
    $detail_table_header = array(
      'label'=>'Label',
      'value'=>'Value',
    );

    // $order_status_arr = AttributeController::get_order_status();
    // print_r($order_record);exit;
    $order_detail_data_arr = array(
      'Order Status' => AttributeController::get_order_status()[$order_record['status']],
      'Tracking Number' => $order_record['tracking_number'],
      'Order Type' => $order_record['order_type'],
      'Magento Order ID' => $order_record['increment_id'],
      'ERP Order ID' => $order_record['id'],
      'Created At' => date("Y-m-d h:i:s", $order_record['created_at']),
      'Customer Email' => $order_record['customer_email'],
      'Order Currency' => $order_record['order_currency_code'],
      'Subtotal' => $order_record['subtotal'],
      'Total' => $order_record['grand_total'],
      'Discount Amount' => $order_record['discount_amount'],
      'Shipping Amount' => $order_record['shipping_amount'],
      'Payment' => $order_record['payment'],
      'Total Items' => $order_record['total_qty_ordered'],
      'Weight' => $order_record['weight'],
    );
    $detail_table_rows=array();
    foreach ($order_detail_data_arr as $label => $value) {
      $detail_table_rows[] = array(
        'label' => $label,
        'value' => $value,
      );
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
  public function order_data_inventory($order_id){
    // print_r($order_id);exit;
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_order_item', 'moi')
      ->condition('order_id', $order_id)
      ->fields('moi');
    $record = $query->execute()->fetchAll();
    $inventory_table_header = array(
      'magento_sku'=>    t('Magento SKU'),
    );
    $warehouse_arr = WarehouseController::get_all_warehouses();
    foreach ($warehouse_arr as $warehouse_id=>$each_warehouse) {
      $inventory_table_header[$warehouse_id] = t($each_warehouse);
    }
    $inventory_table_header['total'] = t('Total');
    $inventory_table_rows=array();
    foreach ($record as $each_db_order_item) {
      $simple_product = ProductController::get_simple_product_by_sku($each_db_order_item->sku);
      $inventory_table_row_data = array();
      $inventory_table_row_data['magento_sku'] = $each_db_order_item->sku;
      $inventory_data = InventoryController::get_inventory_by_productid($simple_product['id']);
      $total = 0;
      foreach ($warehouse_arr as $warehouse_id=>$each_inventory) {
        $inventory_table_row_data[$warehouse_id] = isset($inventory_data[$warehouse_id])?$inventory_data[$warehouse_id]:0;
        $total = $total + intval($inventory_data[$warehouse_id]);
      }
      $inventory_table_row_data['total'] = $total;
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

  public function order_data_item($order_id){
    // print_r($order_id);exit;
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_order_item', 'moi')
      ->condition('order_id', $order_id)
      ->fields('moi');
    $record = $query->execute()->fetchAll();
    $item_table_header = array(
      'qty' => 'QTY',
      'name'=>'Name',
      'sku'=>'Magento SKU',
      'color'=>'Color',
      'size'=>'Size',
      'length'=>'Length',
      'price'=>'Price',
      'barcode'=>'Barcode',
      'nav_sku'=>'NAV SKU',
    );
    $item_table_rows=array();
    foreach ($record as $each_db_order_item) {
      $simple_product = ProductController::get_simple_product_by_sku($each_db_order_item->sku);
      $color_data = AttributeController::get_color_by_id($simple_product['color_id']);
      $size_data = AttributeController::get_size_by_id($simple_product['size_id']);
      $length_data = AttributeController::get_length_by_id($simple_product['length_id']);
      $item_table_row_data = array();
      $item_table_row_data['qty'] = $each_db_order_item->qty;
      $item_table_row_data['name'] = $each_db_order_item->name;
      // $item_table_row_data['sku'] = $each_db_order_item->sku;
      $item_table_row_data['sku'] = array(
        'data'=>$each_db_order_item->sku,
        'title'=>array($each_db_order_item->sku),
      );
      $item_table_row_data['color'] = $color_data['color'];
      $item_table_row_data['size'] = $size_data['size'];
      $item_table_row_data['length'] = $length_data['length'];
      $item_table_row_data['price'] = $each_db_order_item->price;
      // $item_table_row_data['barcode'] = implode(';', ProductController::get_barcode_by_product_id($simple_product['id']));
      $item_table_row_data['barcode'] = array(
        'data'=>implode(';', ProductController::get_barcode_by_product_id($simple_product['id'])),
        'title'=>array(implode(';', ProductController::get_barcode_by_product_id($simple_product['id']))),
      );
      // $item_table_row_data['nav_sku'] = implode(';', ProductController::get_nav_sku_by_product_id($simple_product['id']));
      $item_table_row_data['nav_sku'] = array(
        'data'=>implode(';', ProductController::get_nav_sku_by_product_id($simple_product['id'])),
        'title'=>array(implode(';', ProductController::get_nav_sku_by_product_id($simple_product['id']))),
      );
      $item_table_rows[] = $item_table_row_data;
    }
    $table_data = [
      '#type' => 'table',
      '#header' => $item_table_header,
      '#rows' => $item_table_rows,
      '#empty' => t('No Data found'),
      '#attributes' => [   
        'class' => ['product_detail_table'],
      ],
    ];
    return $table_data;
  }

  public function order_data_address($order_id){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_order_address', 'moa')
      ->condition('order_id', $order_id)
      ->fields('moa');
    $record = $query->execute()->fetchAll();
    $address_table_header = array(
      'address_type'=>'Type',
      'name'=>'Customer',
      'telephone'=>'Tel',
      'country_id'=>'Country',
      'postcode'=>'Zip',
      'region_city'=>'Region City',
      'street'=>'Street',
    );
    $country_arr = AttributeController::get_country_list();
    $address_table_rows=array();
    foreach ($record as $each_db_order_address) {
      $address_table_row_data = array();
      $address_table_row_data['address_type'] = $each_db_order_address->address_type;
      $address_table_row_data['name'] = $each_db_order_address->prefix.' '.$each_db_order_address->firstname.' '.$each_db_order_address->lastname;
      $address_table_row_data['telephone'] = $each_db_order_address->telephone;
      $address_table_row_data['country_id'] = $country_arr[$each_db_order_address->country_id];
      $address_table_row_data['postcode'] = $each_db_order_address->postcode;
      $address_table_row_data['region_city'] = $each_db_order_address->region.', '.$each_db_order_address->city;
      if (!empty($each_db_order_address->street_2)) {
        $street = $each_db_order_address->street_1.", ".$each_db_order_address->street_2;
      }else{
        $street = $each_db_order_address->street_1;
      }
      $address_table_row_data['street'] = $street;
      $address_table_rows[] = $address_table_row_data;
    }      
    $table_data = [
      '#type' => 'table',
      '#header' => $address_table_header,
      '#rows' => $address_table_rows,
      '#empty' => t('No Data found'),
      '#attributes' => [   
        'class' => ['order_address_table'],
      ],
    ];
    return $table_data;
  }

  public function get_order_by_id($order_id) {
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_order', 'mo');
    $query->fields('mo');
    $query->condition('id', $order_id);
    $record = $query->execute()->fetchAssoc();
    return $record;
  }
  public function get_order_shipping_address_by_id($order_id) {
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_order_address', 'moa');
    $query->fields('moa');
    $query->condition('order_id', $order_id);
    $query->condition('address_type', 'shipping');
    $record = $query->execute()->fetchAssoc();
    return $record;
  }
  public function get_order_billing_address_by_id($order_id) {
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_order_address', 'moa');
    $query->fields('moa');
    $query->condition('order_id', $order_id);
    $query->condition('address_type', 'billing');
    $record = $query->execute()->fetchAssoc();
    return $record;
  }
  public function get_order_items_by_id($order_id) {
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_order_item', 'moi');
    $query->fields('moi');
    $query->condition('order_id', $order_id);
    $record = $query->execute()->fetchAll();
    return $record;
  }
  public function get_all_orders(){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_order', 'mo');
    $query->fields('mo');
    $record = $query->execute()->fetchAll();
    return $record;
  }
}
