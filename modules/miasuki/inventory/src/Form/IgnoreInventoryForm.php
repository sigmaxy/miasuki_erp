<?php

namespace Drupal\inventory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\inventory\Controller\InventoryController;

/**
 * Class IgnoreInventoryForm.
 */
class IgnoreInventoryForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ignore_inventory_form';
  }
  public $ignore_id;
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ignore_id = NULL) {
    $connection = Database::getConnection();
    if (isset($ignore_id)) {
      $this->ignore_id = $ignore_id;
      $query = $connection->select('miasuki_ignore_list', 'mil')
          ->condition('id', $ignore_id)
          ->fields('mil');
      $record = $query->execute()->fetchAssoc();
    }
    $ignore_type = InventoryController::get_ignore_type_options();
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => $ignore_type,
      '#weight' => '1',
      '#default_value' => isset($record['type'])?$record['type']:'',
    ];
    $form['barcode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Barcode'),
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '2',
      '#default_value' => isset($record['barcode'])?$record['barcode']:'',
    ];
    $form['nav_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nav Code'),
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '3',
      '#default_value' => isset($record['nav_code'])?$record['nav_code']:'',
    ];
    $form['remark'] = [
      '#type' => 'textarea',
      '#wysiwyg' => false,
      '#title' => $this->t('Remark'),
      '#weight' => '4',
      '#default_value' => isset($record['remark'])?$record['remark']:'',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#weight' => '5',
      '#value' => $this->t('Add'),
    ];
    if (isset($record['id'])) {
      $form['submit'] = [
        '#type' => 'submit',
        '#weight' => '5',
        '#value' => $this->t('Edit'),
      ];
      $form['delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#attributes' => array(
          'class' => array('add_ignore_inventory_button'),
        ),
        '#submit' => array('::delete_ignore_inventory'),
        '#weight' => '6',
      ];
    }
      
    $form['#attached']['library'][] = 'inventory/inventory';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $connection = Database::getConnection();
    $field=$form_state->getValues();
    if ($this->ignore_id == 'add') {
      $connection->insert('miasuki_ignore_list')
        ->fields([
          'type' => $field['type'],
          'barcode' => $field['barcode'],
          'nav_code' => $field['nav_code'],
          'remark' => $field['remark'],
        ])
        ->execute();
      drupal_set_message('Ignore Inventory Created');
    }else{
      $db_fields = array(
        'type' => $field['type'],
        'barcode' => $field['barcode'],
        'nav_code' => $field['nav_code'],
        'remark' => $field['remark'],
      );
      $query = \Drupal::database();
      $query->update('miasuki_ignore_list')
          ->fields($db_fields)
          ->condition('id', $this->ignore_id)
          ->execute();
      drupal_set_message('Ignore Inventory Updated');
    }
    $url = Url::fromRoute('inventory.list_ignore_inventory');
    return $form_state->setRedirectUrl($url);
  }

  public function delete_ignore_inventory(array &$form, FormStateInterface $form_state) {
    $connection = Database::getConnection();
    $field=$form_state->getValues();
    $num_deleted = $connection->delete('miasuki_ignore_list')
      ->condition('id', $this->ignore_id)
      ->execute();
    drupal_set_message('Ignore Inventory Deleted');
    $url = Url::fromRoute('inventory.list_ignore_inventory');
    return $form_state->setRedirectUrl($url);
  }

}
