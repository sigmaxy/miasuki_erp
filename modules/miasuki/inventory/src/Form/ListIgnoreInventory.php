<?php

namespace Drupal\inventory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\inventory\Controller\InventoryController;
/**
 * Class ListIgnoreInventory.
 */
class ListIgnoreInventory extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'list_ignore_inventory';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $header_table = array(
      'id'=>    t('ID'),
      'type' => t('Type'),
      'barcode' =>  t('Barcode'),
      'nav_code' =>  t('Nav Code'),
      'remark' =>  t('Remark'),
      'opt1' => t('View/Edit'),
    );
    $param = \Drupal::request()->query->all();
    $query = \Drupal::database()->select('miasuki_ignore_list', 'mil');
    $query->fields('mil');
    if (!empty($param['barcode'])) {
      $query->condition('barcode', "%" . $param['barcode'] . "%", 'LIKE');
    }
    if (!empty($param['nav_code'])) {
      $query->condition('nav_code', "%" . $param['nav_code'] . "%", 'LIKE');
    }
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
    $results = $pager->execute()->fetchAll();
    $ignore_type = InventoryController::get_ignore_type_options();
    $rows=array();
    foreach($results as $data){
      //print the data from table
      $edit   = Url::fromUserInput('/inventory/form/ignore_inventory/'.$data->id);
      $rows[] = array(
        'id' =>$data->id,
        'type' => $ignore_type[$data->type],
        'barcode' => $data->barcode,
        'nav_code' => $data->nav_code,
        'remark' => $data->remark,
         \Drupal::l('View/Edit', $edit),
      );
    }
    $form['filters'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Filter'),
        '#open'  => true,
    ];

    $form['filters']['barcode'] = [
        '#title'         => 'Barcode',
        '#type'          => 'search',
        '#default_value' => !empty($param['barcode'])?$param['barcode']:'',
    ];
    $form['filters']['nav_code'] = [
        '#title'         => 'Nav Code',
        '#type'          => 'search',
        '#default_value' => !empty($param['nav_code'])?$param['nav_code']:'',
    ];
    $form['filters']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];
    $form['filters']['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#attributes' => array(
        'class' => array('add_ignore_inventory_button'),
      ),
      '#submit' => array('::add_ignore_inventory'),
    ];

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
    // $form_state->setRebuild();
    $query_parameter = array();
    // print_r($form_state->getValue('filters'));exit;
    if (!empty($form_state->getValue('barcode'))) {
      $query_parameter['barcode']=$form_state->getValue('barcode');
    }
    if (!empty($form_state->getValue('nav_code'))) {
      $query_parameter['nav_code']=$form_state->getValue('nav_code');
    }
    $url = Url::fromRoute('inventory.list_ignore_inventory', $query_parameter);
    return $form_state->setRedirectUrl($url);
  }
  public function add_ignore_inventory(array &$form, FormStateInterface $form_state) {
    $url = Url::fromRoute('inventory.ignore_inventory_form', array('ignore_id'=>'add'));
    return $form_state->setRedirectUrl($url);
  }

}
