<?php

namespace Drupal\inventory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\warehouse\Controller\WarehouseController;
use Drupal\Core\Database\Database;
use Drupal\inventory\Controller\InventoryController;
use Drupal\Core\Url;
use Drupal\attribute\Controller\AttributeController;

/**
 * Class InventoryLogForm.
 */
class InventoryLogForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'inventory_log_form';
  }
  public $product_id;
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $param = \Drupal::request()->query->all();
    $connection = Database::getConnection();
    $record = array();
    if (isset($param['magento_sku'])) {
      $query = $connection->select('miasuki_simple_product', 'msp')
        ->condition('magento_sku', $param['magento_sku'])
        ->fields('msp');
      $record = $query->execute()->fetchAssoc();
    }
    $form['filters'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Inventory Filter'),
      '#open'  => true,
    ];
    $form['filters']['magento_sku'] = [
      '#title'         => 'Magento SKU',
      '#type'          => 'textfield',
      '#default_value' => isset($param['magento_sku'])?$param['magento_sku']:'',
      '#weight' => '1',
    ];
    $warehouse_opt = WarehouseController::get_all_warehouses();
    $warehouse_opt[0] = t('All');
    $form['filters']['warehouse_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Warehouse'),
        '#options' => $warehouse_opt,
        '#default_value' => isset($param['warehouse_id'])?$param['warehouse_id']:'0',
        // '#size' => 1,
        '#weight' => '2',
      ];
    $form['filters']['check'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#weight' => '3',
    ];
    if (isset($record['id'])) {
    //Identification data start
      $form['inventorylog'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Identification'),
        '#open'  => true,
      ];
      $inventorylog_table_header = array(
        'id'=>'Inventory Log',
        'warehouse_id'=>'Warehouse',
        'old_qty'=>'Pre Qty',
        'new_qty'=>'New Qty',
        'user_id'=>'User',
        'time'=>'Time',
        'reason'=>'Remark',
      );
      $inventorylog_table_rows=array();
      $query = $connection->select('miasuki_inventory_log', 'mil');
      $query->fields('mil');
      if (!empty($param['magento_sku'])) {
        $query->condition('product_id', $record['id']);
      }
      if (!empty($param['warehouse_id'])&&$param['warehouse_id']!=0) {
        $query->condition('warehouse_id', $param['warehouse_id']);
      }
      $query->orderBy('updated_time', 'DESC');
      $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
      $results = $pager->execute()->fetchAll();
      foreach($results as $data){
        $account = \Drupal\user\Entity\User::load($data->updated_by);
        $inventorylog_table_rows[] = array(
          'id' =>$data->id,
          'warehouse_id' => $warehouse_opt[$data->warehouse_id],
          'old_qty' => $data->old_qty,
          'new_qty' => $data->new_qty,
          'user_id' => $account->getEmail(),
          'time' => date("Y-m-d H:i:s",$data->updated_time),
          'reason' => $data->reason,
        );
      }
      $form['inventorylog']['table'] = [
        '#type' => 'table',
        '#header' => $inventorylog_table_header,
        '#rows' => $inventorylog_table_rows,
        '#empty' => t('No Data found'),
        '#attributes' => [   
          'class' => ['product_detail_table'],
        ],
      ];
      $form['inventorylog']['pager'] = array(
        '#type' => 'pager'
      );
      //Identification data end
    }

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
    $connection = Database::getConnection();
    // print_r($form_state->getValue('filters'));exit;
    if (!empty($form_state->getValue('magento_sku'))) {
      $query_parameter['magento_sku']=$form_state->getValue('magento_sku');
      $query = $connection->select('miasuki_simple_product', 'msp')
          ->condition('magento_sku', $form_state->getValue('magento_sku'))
          ->fields('msp');
      $record = $query->execute()->fetchAssoc();
      if (empty($record['id'])) {
        drupal_set_message('There is no such product!', $type = 'error');
      }
    }
    if (!empty($form_state->getValue('warehouse_id'))) {
      $query_parameter['warehouse_id']=$form_state->getValue('warehouse_id');
    }
    $url = Url::fromRoute('inventory.inventory_log_form', $query_parameter);
    return $form_state->setRedirectUrl($url);

  }

}
