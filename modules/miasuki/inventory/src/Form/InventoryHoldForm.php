<?php

namespace Drupal\inventory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\warehouse\Controller\WarehouseController;
use Drupal\Core\Database\Database;
use Drupal\inventory\Controller\InventoryController;
use Drupal\Core\Url;
use Drupal\product\Controller\ProductController;

/**
 * Class InventoryHoldForm.
 */
class InventoryHoldForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'inventory_hold_form';
  }
  public $inventory_id;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $inventory_id = NULL) {
    $connection = Database::getConnection();
    $record = array();
    $inventory_hold_flag = false;
    if (isset($inventory_id)) {
      $this->inventory_id = $inventory_id;
      $query = $connection->select('miasuki_inventory', 'mi')
          ->condition('id', $inventory_id)
          ->fields('mi');
      $record = $query->execute()->fetchAssoc();
      if (!empty($record['id'])) {
        $inventory_hold_flag = true;
      }
    }
    $db_product = ProductController::get_simple_product_by_id($record['product_id']);
    $warehouse_arr = WarehouseController::get_all_warehouses();
    $form['product_id'] = [
      '#type' => 'textfield',
      '#title' => $db_product['magento_sku'],
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '1',
      '#default_value' => isset($record['product_id'])?$record['product_id']:'',
      '#attributes' => array(
        'disabled' => TRUE,
      ),
    ];
    $form['warehouse_id'] = [
      '#type' => 'textfield',
      '#title' => $warehouse_arr[$record['warehouse_id']],
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '2',
      '#default_value' => isset($record['warehouse_id'])?$record['warehouse_id']:'',
      '#attributes' => array(
        'disabled' => TRUE,
      ),
    ];
    $form['qty'] = [
      '#type' => 'textfield',
      '#title' => 'Current QTY',
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '3',
      '#default_value' => isset($record['qty'])?$record['qty']:'',
      '#attributes' => array(
        'disabled' => TRUE,
      ),
    ];
    $form['hold'] = [
      '#type' => 'number',
      '#title' => 'Hold',
      '#maxlength' => 11,
      '#size' => 64,
      '#weight' => '4',
      '#default_value' => isset($record['hold'])?$record['hold']:'',
    ];

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
    if ($form_state->getValue('hold') > $form_state->getValue('qty')) {
      $form_state->setErrorByName('hold', 'Hold should be smaller than Current QTY');
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $connection = Database::getConnection();
    $connection->update('miasuki_inventory')
      ->fields([
        'hold' => $form_state->getValue('hold'),
      ])
      ->condition('id', $this->inventory_id)
      ->execute();
    drupal_set_message('inventory_id: '.$this->inventory_id.' has been hold: '.$form_state->getValue('hold'));
  }

}
