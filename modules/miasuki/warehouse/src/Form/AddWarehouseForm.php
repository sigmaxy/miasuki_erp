<?php

namespace Drupal\warehouse\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

/**
 * Class AddWarehouseForm.
 */
class AddWarehouseForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_warehouse_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['warehouse_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Warehouse Name'),
      // '#description' => $this->t('Warehouse Name'),
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $warehouse_name = $form_state->getValue('warehouse_name');
    $existed_warehouse_name = db_select('miasuki_warehouse', 'mw')
        ->fields('mw')
        ->condition('warehouse_name', $warehouse_name,'=')
        ->execute()
        ->fetchAssoc();
    if(!preg_match('/[a-zA-Z0-9_ ]/', $warehouse_name)) {
      $form_state->setErrorByName('warehouse_name', $this->t('warehouse name not valid'));
    }
    if ($existed_warehouse_name) {
      $form_state->setErrorByName('warehouse_name', $this->t('warehouse name already existed'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    $connection = Database::getConnection();
    $depth = $connection->transactionDepth();
    $field=$form_state->getValues();
    $connection->insert('miasuki_warehouse')
        ->fields([
          'warehouse_name' => $field['warehouse_name'],
        ])
        ->execute();
    drupal_set_message('Warehouse Created');
    // foreach ($form_state->getValues() as $key => $value) {
    //   drupal_set_message($key . ': ' . $value);
    // }

  }

}
