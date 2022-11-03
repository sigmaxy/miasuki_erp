<?php

namespace Drupal\warehouse\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

/**
 * Class AddWarehouseForm.
 */
class EditWarehouseForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_warehouse_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $connection = Database::getConnection();
    $record = array();
    if (isset($_GET['id'])) {
      $query = $connection->select('miasuki_warehouse', 'mw')
          ->condition('id', $_GET['id'])
          ->fields('mw');
      $record = $query->execute()->fetchAssoc();
    }
    $form['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Warehouse ID'),
      '#attributes' => array('readonly' => 'readonly'),
      // '#description' => $this->t('Warehouse Name'),
      '#weight' => '0',
      '#default_value' => $record['id'],
    ];
    $form['warehouse_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Warehouse Name'),
      // '#description' => $this->t('Warehouse Name'),
      '#weight' => '1',
      '#default_value' => $record['warehouse_name'],
    ];
    $form['sort_order'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sort Order'),
      // '#description' => $this->t('Warehouse Name'),
      '#weight' => '2',
      '#default_value' => $record['sort_order'],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#weight' => '3',
      '#value' => $this->t('Edit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $warehouse_name = $form_state->getValue('warehouse_name');
    $warehouse_id = $form_state->getValue('id');
    $existed_warehouse_name = db_select('miasuki_warehouse', 'mw')
        ->fields('mw')
        ->condition('warehouse_name', $warehouse_name,'=')
        ->execute()
        ->fetchAssoc();
    if(!preg_match('/[a-zA-Z0-9_ ]/', $warehouse_name)) {
      $form_state->setErrorByName('warehouse_name', $this->t('warehouse name not valid'));
    }
    if ($existed_warehouse_name && $existed_warehouse_name['id']!=$warehouse_id) {
      $form_state->setErrorByName('warehouse_name', $this->t('warehouse name already existed'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $field=$form_state->getValues();
    $db_fields = array(
      'warehouse_name' => $field['warehouse_name'],
      'sort_order' => $field['sort_order'],
    );
    $query = \Drupal::database();
    $query->update('miasuki_warehouse')
        ->fields($db_fields)
        ->condition('id', $_GET['id'])
        ->execute();
    drupal_set_message('Warehouse Updated');
  }

}
