<?php

namespace Drupal\api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\api\Controller\AuthenticateController;
use Drupal\attribute\Controller\AttributeController;

/**
 * Class ApiOrderController.
 */
class ApiOrderController extends ControllerBase {

  /**
   * Syncorder.
   *
   * @return string
   *   Return Hello string.
   */
  public function testsyncorder() {
    $sync_start_order_id = 328;
    $apiorder_data = AuthenticateController::api_call('orders?searchCriteria[filter_groups][0][filters][0][field]=entity_id&searchCriteria[filter_groups][0][filters][0][value]='.$sync_start_order_id.'&searchCriteria[filter_groups][0][filters][0][condition_type]=gt','GET');
    foreach ($apiorder_data['items'] as $each_order) {
      print_r($each_order);
    }
    exit;
  }
  public function orderstatusupdate($site_code,$order_id,$status='complete') {
    $api_url = AuthenticateController::get_api_server_url($site_code).'msales/order/updatestatus/order_id/'.$order_id.'/status/'.$status;
    $result = file_get_contents($api_url);
    error_log("sync order status uri: ".$api_url."| sync order status result: ".$result, 0);
    return json_decode($result,1);
  }
  public function synctrackingnumber($order_id, $tracking_number,$carrier) {
    $api_url = AuthenticateController::get_api_server_url('us').'msales/order/addtracking/order_id/'.$order_id.'/trackingnumber/'.$tracking_number.'/carrier/'.$carrier;
    $result = file_get_contents($api_url);
    error_log("sync tracking number uri: ".$api_url."|sync tracking number result: ".$result, 0);
    return json_decode($result,1);
  }
  public function syncorder() {
    $connection = Database::getConnection();
    $result = $connection->query("select * from miasuki_order where id = (SELECT MAX(id) FROM miasuki_order where order_type = 'magento')")->fetchAssoc();
    if (empty($result['created_at'])) {
      $sync_start_data = '2019-01-01 00:00:00';
    }else{
      $sync_start_data = date('Y-m-d H:i:s', $result['created_at']-60*60*24*7);
    }
    $apiorder_data = AuthenticateController::api_call('orders?searchCriteria[filter_groups][0][filters][0][field]=created_at&searchCriteria[filter_groups][0][filters][0][value]='.urlencode($sync_start_data).'&searchCriteria[filter_groups][0][filters][0][condition_type]=from','GET');
    $order_data = array();
    $order_status = AttributeController::get_order_status();
    foreach ($apiorder_data['items'] as $each_order) {
      // error_log("order sync ID ".$each_order['entity_id']."| order status: ".$result, 0);
      if ($each_order['status']=='canceled') {
        $each_order['status']='cancelled';
      }
      $existed_magento_order = $connection->select('miasuki_order', 'mo')
        ->fields('mo')
        ->condition('order_type', 'magento','=')
        ->condition('entity_id', $each_order['entity_id'],'=')
        ->execute()
        ->fetchAssoc();
      if (isset($existed_magento_order['id'])) {
        $magento_order_status = array_search($each_order['status'], $order_status);
        if ($existed_magento_order['status']!=$magento_order_status) {
          $db_fields = array(
            'status' => $magento_order_status,
          );
          $query_update = $connection->update('miasuki_order')
            ->fields($db_fields)
            ->condition('id', $existed_magento_order['id'])
            ->execute();
        }
        continue;
      }
      $order_data_each = array(
        'order_type' => 'magento',
        'entity_id' => $each_order['entity_id'],
        'increment_id' => $each_order['increment_id'],
        'created_at' => strtotime($each_order['created_at']),
        'customer_email' => $each_order['customer_email'],
        'customer_lastname' => $each_order['customer_lastname'],
        'customer_firstname' => $each_order['customer_firstname'],
        'order_currency_code' => $each_order['order_currency_code'],
        'grand_total' => $each_order['grand_total'],
        'subtotal' => $each_order['subtotal'],
        'discount_amount' => $each_order['discount_amount'],
        'shipping_amount' => $each_order['shipping_amount'],
        'store_id' => $each_order['store_id'],
        'total_qty_ordered' => $each_order['total_qty_ordered'],
        'weight' => $each_order['weight'],
      );
      $order_address_billing = array(
        'address_type' => $each_order['billing_address']['address_type'],
        'email' => $each_order['billing_address']['email'],
        'prefix' => isset($each_order['billing_address']['prefix'])?$each_order['billing_address']['prefix']:NULL,
        'firstname' => $each_order['billing_address']['firstname'],
        'lastname' => $each_order['billing_address']['lastname'],
        'telephone' => $each_order['billing_address']['telephone'],
        'country_id' => $each_order['billing_address']['country_id'],
        'postcode' => $each_order['billing_address']['postcode'],
        'city' => $each_order['billing_address']['city'],
        'street_1' => isset($each_order['billing_address']['street'][0])?$each_order['billing_address']['street'][0]:null,
        'street_2' => isset($each_order['billing_address']['street'][1])?$each_order['billing_address']['street'][1]:null,
        'region' => $each_order['billing_address']['region'],
      );
      $order_address_shipping = array(
        'address_type' => $each_order['extension_attributes']['shipping_assignments'][0]['shipping']['address']['address_type'],
        'email' => $each_order['extension_attributes']['shipping_assignments'][0]['shipping']['address']['email'],
        'prefix' => isset($each_order['extension_attributes']['shipping_assignments'][0]['shipping']['address']['prefix'])?$each_order['extension_attributes']['shipping_assignments'][0]['shipping']['address']['prefix']:NULL,
        'firstname' => $each_order['extension_attributes']['shipping_assignments'][0]['shipping']['address']['firstname'],
        'lastname' => $each_order['extension_attributes']['shipping_assignments'][0]['shipping']['address']['lastname'],
        'telephone' => $each_order['extension_attributes']['shipping_assignments'][0]['shipping']['address']['telephone'],
        'country_id' => $each_order['extension_attributes']['shipping_assignments'][0]['shipping']['address']['country_id'],
        'postcode' => $each_order['extension_attributes']['shipping_assignments'][0]['shipping']['address']['postcode'],
        'city' => $each_order['extension_attributes']['shipping_assignments'][0]['shipping']['address']['city'],
        'street_1' => isset($each_order['extension_attributes']['shipping_assignments'][0]['shipping']['address']['street'][0])?$each_order['extension_attributes']['shipping_assignments'][0]['shipping']['address']['street'][0]:null,
        'street_2' => isset($each_order['extension_attributes']['shipping_assignments'][0]['shipping']['address']['street'][1])?$each_order['extension_attributes']['shipping_assignments'][0]['shipping']['address']['street'][1]:null,
        'region' => $each_order['extension_attributes']['shipping_assignments'][0]['shipping']['address']['region'],
      );
      // $order_data_each['shipping_address'] = $order_address_billing;
      // $order_data_each['billing_address'] = $order_address_shipping;
      // $order_data_each['shipping_address_id'] = 0;
      // $order_data_each['billing_address_id'] = 0;
      $order_items = array();
      foreach ($each_order['items'] as $each_item) {
        if ($each_item['product_type']=='simple') {
          $order_items[] = array(
            'sku' => $each_item['sku'],
            'name' => $each_item['name'],
            'price' => $each_item['parent_item']['price'],
            'original_price' => $each_item['parent_item']['original_price'],
            'qty' => $each_item['qty_ordered'],
          );
          //helmet order add lining to product
          $product_id = $each_item['product_id'];
          $order_id = $each_item['order_id'];
          $store_id = $each_order['store_id'];
          $helmet_cpsku = self::helmet_mapping($product_id);
          if (isset($helmet_cpsku)) {
            $order_item_option = self::get_order_item_option($order_id,$product_id,$store_id);
            foreach ($order_item_option as $order_item_option_each) {
              if ($order_item_option_each['option_title']=='helmet_lining') {
                $order_items[] = array(
                  'sku' => $order_item_option_each['option_sku'],
                  'name' => 'lining with helmet',
                  'price' => 0,
                  'original_price' => 0,
                  'qty' => 1,
                );
              }
            }
          }
        }
      }
      $order_data_each['payment'] = $each_order['payment']['method']=='checkmo'?'paypal':$each_order['payment']['method'];
      $order_data_each['status'] = array_search($each_order['status'], $order_status);
      $order_data_each['tracking_number'] = null;
      $query_check = $connection->select('miasuki_order', 'mo')
          ->condition('order_type', $order_data_each['order_type'])
          ->condition('entity_id', $order_data_each['entity_id'])
          ->fields('mo');
      $record = $query_check->execute()->fetchAssoc();
      if (empty($record['id'])) {
        //insert
        $order_insert_id = $connection->insert('miasuki_order')
          ->fields($order_data_each)
          ->execute();
        //insert order address
        $order_address_shipping['order_id'] = $order_insert_id;
        $order_address_billing['order_id'] = $order_insert_id;
        $order_address_shipping_insert_id = $connection->insert('miasuki_order_address')->fields($order_address_shipping)->execute();
        $order_address_billing_insert_id = $connection->insert('miasuki_order_address')->fields($order_address_billing)->execute();
        //insert order items
        foreach ($order_items as $each_order_item) {
          $each_order_item['order_id'] = $order_insert_id;
          $connection->insert('miasuki_order_item')->fields($each_order_item)->execute();
        }
        // $order_address_id_update = array(
        //   'shipping_address_id' => $order_address_shipping_insert_id,
        //   'billing_address_id' => $order_address_billing_insert_id,
        // );
        // //update address id
        // $query_update = $connection->update('miasuki_order')
        //   ->fields($order_address_id_update)
        //   ->condition('id', $order_insert_id)
        //   ->execute();
      }
    }
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

}
