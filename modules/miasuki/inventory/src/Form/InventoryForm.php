<?php

namespace Drupal\inventory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\warehouse\Controller\WarehouseController;
use Drupal\Core\Database\Database;
use Drupal\inventory\Controller\InventoryController;
use Drupal\Core\Url;

/**
 * Class InventoryForm.
 */
class InventoryForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'inventory_form';
  }
  public $product_id;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $product_id = NULL) {
    $connection = Database::getConnection();
    $record = array();
    $new_inventory_flag = false;
    if (isset($product_id)) {
      $this->product_id = $product_id;
      $query = $connection->select('miasuki_simple_product', 'msp')
          ->condition('id', $product_id)
          ->fields('msp');
      $record = $query->execute()->fetchAssoc();
      if (!empty($record['id'])) {
        $new_inventory_flag = true;
      }
      // exit;
    }
    $form['magento_sku'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Magento SKU'),
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '1',
      '#default_value' => isset($record['magento_sku'])?$record['magento_sku']:'',
      '#attributes' => array(
        'disabled' => $new_inventory_flag,
      ),
    ];
    $warehouse_arr = WarehouseController::get_all_warehouses();
    foreach ($warehouse_arr as $warehouse_id=>$each_warehouse) {
      $inventory_qty = InventoryController::get_inventory_by_productid_warehouseid($product_id, $warehouse_id);
      $form['warehouse_'.$warehouse_id] = [
        '#type' => 'textfield',
        '#title' => $this->t($each_warehouse),
        '#maxlength' => 255,
        '#size' => 64,
        '#weight' => '2',
        '#default_value' => $inventory_qty,
      ];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => '16',
    ];
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
    $connection = Database::getConnection();
    $depth = $connection->transactionDepth();
    foreach ($form_state->getValues() as $key => $qty) {
      $warehouse_data_arr = explode('warehouse_', $key);
      if (!empty($warehouse_data_arr[1])) {
        $check_query = $connection->select('miasuki_inventory', 'mi')
          ->condition('product_id', $this->product_id)
          ->condition('warehouse_id', $warehouse_data_arr[1])
          ->fields('mi');
        $check_record = $check_query->execute()->fetchAssoc();
        if (empty($check_record['id'])) {
          //no record insert new
          $connection->insert('miasuki_inventory')
            ->fields([
              'product_id' => $this->product_id,
              'warehouse_id' => $warehouse_data_arr[1],
              'qty' => $qty,
            ])
            ->execute();
        }else{
          if ($check_record['qty']==$qty) {
            //no change of current inventory
            drupal_set_message('product_id: '.$this->product_id.' warehouse_id: '.$warehouse_data_arr[1].' current qty no change');
          }else{
            //update inventory
            $connection->update('miasuki_inventory')
              ->fields([
                'qty' => $qty,
              ])
              ->condition('product_id', $this->product_id)
              ->condition('warehouse_id', $warehouse_data_arr[1])
              ->execute();
            drupal_set_message('product_id: '.$this->product_id.' warehouse_id: '.$warehouse_data_arr[1].' qty update:'.$qty);
          }
        }
      }
    }

  }

}
