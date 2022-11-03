<?php

namespace Drupal\order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\attribute\Controller\AttributeController;
use Drupal\order\Controller\OrderController;
use Drupal\warehouse\Controller\WarehouseController;
use Drupal\product\Controller\ProductController;
use Drupal\inventory\Controller\InventoryController;
use Drupal\api\Controller\ApiOrderController;
use Drupal\api\Controller\AuthenticateController;

/**
 * Class OrderDetailForm.
 */
class OrderDetailForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public $order_id;
  public function getFormId() {
    return 'order_detail_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state,$order_id = NULL) {
    $param = \Drupal::request()->query->all();
    $connection = Database::getConnection();
    $record = array();
    if (isset($order_id)) {
      $this->order_id = $order_id;
      $query = $connection->select('miasuki_order', 'mo')
        ->condition('id', $order_id)
        ->fields('mo');
      $record = $query->execute()->fetchAssoc();
      $form['order_recode'] = $record;
    }
    if (!isset($record['id'])) {
      
    }else{
      $this->order_id = $record['id'];
      //Tracking Number start
      if (empty($record['tracking_number'])&&AttributeController::get_order_status()[$record['status']]=='processing') {
      
        $form['logistic'] = [
          '#type'  => 'fieldset',
          '#title' => $this->t('Order Modification'),
          '#open'  => true,
          '#weight' => '1',
        ];
        $warehouse_opt = WarehouseController::get_all_warehouses();
        $form['logistic']['shipping_from'] = [
          '#type' => 'select',
          '#title' => $this->t('Shipping From'),
          '#options' => $warehouse_opt,
          '#weight' => '2',
          '#default_value' => 4,
        ];
        $form['logistic']['carrier'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Carrier'),
          '#weight' => '3',
        ];
        $form['logistic']['tracking_number'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Tracking Number'),
          '#weight' => '4',
          '#default_value' => isset($record['tracking_number'])?$record['tracking_number']:'',
        ];
        $form['logistic']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Add Tracking Number'),
          // '#submit' => array('::TestAddTrackingSubmit'),
          '#weight' => '5',
        ];
      }
      //Tracking Number end

      //order_detail data start
      $form['order_detail'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Order Detail'),
        '#open'  => true,
        'table' => OrderController::order_data_detail($record),
        '#weight' => '2',
      ];
      //order_detail data end

      //Address data start
      $form['address'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('address'),
        '#open'  => true,
        'table' => OrderController::order_data_address($record['id']),
        '#weight' => '3',
      ];
      //Address data end

      // Item data start
      $form['item'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Item'),
        '#open'  => true,
        'table' => OrderController::order_data_item($record['id']),
        '#weight' => '3',
      ];
      // Item data end

      // Inventory data start
      $form['inventory'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Inventory'),
        '#open'  => true,
        'table' => OrderController::order_data_inventory($record['id']),
        '#weight' => '4',
      ];
      // Inventory data end
      $form['order'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Order Status Modification'),
        '#open'  => true,
        '#weight' => '5',
      ];
      $form['order']['status'] = [
        '#type' => 'select',
        '#title' => $this->t('Status'),
        '#options' => AttributeController::get_order_status(),
        '#weight' => '1',
        '#default_value' => $record['status'],
      ];
      $form['order']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Change'),
        '#validate' => array('::OrderModifiedValidate'),
        '#submit' => array('::OrderModifiedSubmit'),
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
    $field=$form_state->getValues();
    $connection = Database::getConnection();
    if ( $field['tracking_number']  == '' ) {
        $form_state->setErrorByName('tracking_number', t('Required'));
    }
    //add total inventory to temp_variable
    foreach ($form['inventory']['table']['#rows'] as $each_inventory_row) {
      $form['temp_variable']['order_item'][$each_inventory_row['magento_sku']]['old_total_inventory']=$each_inventory_row['total'];
    }

    foreach (OrderController::get_order_items_by_id($this->order_id) as $each_item) {
      $sproduct = ProductController::get_simple_product_by_sku($each_item->sku);
      if (empty($sproduct['id'])) {
        $form_state->setErrorByName('item', 'item '.$each_item->sku.' not found');
      }else{
        $check_query = $connection->select('miasuki_inventory', 'mi')
          ->condition('product_id', $sproduct['id'])
          ->condition('warehouse_id', $field['shipping_from'])
          ->fields('mi');
        $check_record = $check_query->execute()->fetchAssoc();
        if (empty($check_record['id'])||$check_record['qty']==0) {
          //no record insert new
          $form_state->setErrorByName('item','There is no inventory of '.$each_item->sku.' in such warehouse');
        }else if ($check_record['qty']<$each_item->qty) {
          //not enough inventory
          $form_state->setErrorByName('item','Not enough inventory of '.$each_item->sku.' in such warehouse');
        }else{
          //add 
          $form['temp_variable']['order_item'][$each_item->sku]['product_id'] = $sproduct['id'];
          $form['temp_variable']['order_item'][$each_item->sku]['new_inventory'] = $check_record['qty'] - $each_item->qty;
          $form['temp_variable']['order_item'][$each_item->sku]['old_inventory'] = $check_record['qty'];
          $form['temp_variable']['order_item'][$each_item->sku]['new_total_inventory'] = $form['temp_variable']['order_item'][$each_item->sku]['old_total_inventory']-$each_item->qty;
        }
      }
    }
    

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //add tracking number
    $connection = Database::getConnection();
    $current_user = \Drupal::currentUser();
    $field=$form_state->getValues();
    //update order info
    $connection->update('miasuki_order')
      ->fields([
        'tracking_number' => $field['tracking_number'],
        'sync_flag' => 1,
        'status' => array_search('shipping', AttributeController::get_order_status()),
        'updated_by' => $current_user->id(),
        'updated_time' => time(),
      ])->condition('id', $this->order_id)->execute();
    // $connection->execute();
    //deduct order item inventory
    foreach ($form['temp_variable']['order_item'] as $each_item_sku => $each_item) {
      //update inventory
      $connection->update('miasuki_inventory')
        ->fields([
          'qty' => $each_item['new_inventory'],
        ])
        ->condition('product_id', $each_item['product_id'])
        ->condition('warehouse_id', $field['shipping_from'])
        ->execute();
      drupal_set_message('product_id: '.$each_item['product_id'].$each_item_sku.' warehouse_id: '.$field['shipping_from'].' qty update to:'.$each_item['new_inventory']);
      // $url = Url::fromRoute('inventory.outbound_form');
      InventoryController::modified_log($each_item['product_id'],$field['shipping_from'],$each_item['old_inventory'],$each_item['new_inventory'],'Order Deduct, Order ID '.$this->order_id);
    }
    //sync magento order info
    $result = ApiOrderController::synctrackingnumber($form['order_recode']['entity_id'],$field['tracking_number'],$field['carrier']);
    if ($result['errors']) {
      drupal_set_message($result['messages'],'error');
    }else{
      drupal_set_message($result['messages']);
      //update synce flag
      $connection->update('miasuki_order')
        ->fields([
          'sync_flag' => 2,
          'updated_by' => $current_user->id(),
          'updated_time' => time(),
        ])->condition('id', $this->order_id)->execute();
      }
    //sync magento inventory info
//         print_r($form['temp_variable']['order_item']);exit;


  }

  public function OrderModifiedValidate(array &$form, FormStateInterface $form_state) {
    // echo 'test';exit;
  }
  public function OrderModifiedSubmit(array &$form, FormStateInterface $form_state) {
    $field=$form_state->getValues();
    $connection = Database::getConnection();
    $current_user = \Drupal::currentUser();
    $field=$form_state->getValues();
    $connection->update('miasuki_order')
      ->fields([
        'status' => $field['status'],
        'sync_flag' => 1,
        'updated_by' => $current_user->id(),
        'updated_time' => time(),
      ])
      ->condition('id', $this->order_id)
      ->execute();
    $order_status_option = AttributeController::get_order_status();
    if($order_status_option[$field['status']]=='complete'){
      //sync magento order info
      $result = ApiOrderController::orderstatusupdate('us',$form['order_recode']['entity_id'],'complete');
      if ($result['errors']) {
        drupal_set_message($result['messages'],'error');
      }else{
        drupal_set_message($result['messages']);
        //update synce flag
        $connection->update('miasuki_order')
          ->fields([
            'sync_flag' => 2,
            'updated_by' => $current_user->id(),
            'updated_time' => time(),
          ])->condition('id', $this->order_id)->execute();
      }
    }
  }

}
