<?php

namespace Drupal\product\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\product\Controller\ProductController;

/**
 * Class ListConfigProductForm.
 */
class ListConfigProductForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'list_config_product_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $header_table = array(
      'id'=>    t('ID'),
      'magento_sku' => t('Mangento SKU'),
      'name' =>  t('Name'),
      'sync' =>  t('Sync'),
      'status' =>  t('Status'),
      'opt1' => t('View/Edit'),
    );
    $param = \Drupal::request()->query->all();
    $query = \Drupal::database()->select('miasuki_config_product', 'mcp');
    $query->fields('mcp');
    if (!empty($param['magento_sku'])) {
      $query->condition('magento_sku', "%" . $param['magento_sku'] . "%", 'LIKE');
    }
    if (!empty($param['name'])) {
      $query->condition('name', "%" . $param['name'] . "%", 'LIKE');
    }
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
    $results = $pager->execute()->fetchAll();
    $rows=array();
    foreach($results as $data){
        $edit   = Url::fromUserInput('/product/form/config_product/'.$data->id);
        if ($data->sync_flag==1) {
          $sync_flag = 'Unsync';
        }else if($data->sync_flag==2){
          $sync_flag = 'Sync';
        }else{
          $sync_flag = 'Offline';
        }
      //print the data from table
        $rows[] = array(
          'id' =>$data->id,
          'magento_sku' => $data->magento_sku,
          'name' => $data->name,
          'sync' => $sync_flag,
          'status' => ProductController::get_product_status_by_statusid($data->status),
           \Drupal::l('View/Edit', $edit),
        );
    }
    $form['filters'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Filter'),
        '#open'  => true,
    ];

    $form['filters']['magento_sku'] = [
        '#title'         => 'SKU',
        '#type'          => 'search',
        '#default_value' => !empty($param['magento_sku'])?$param['magento_sku']:'',
        // '#default_value' => !empty($form_state->getValue('magento_sku')) ? $form_state->getValue('magento_sku') : '',
        // '#default_value' => $form_state->get('sigma'),
        // '#default_value' =>  \Drupal::request()->request->get('magento_sku'),
        // '#default_value' => isset($form_state['filters']['magento_sku'])?$form_state['filters']['magento_sku']:'',
    ];
    $form['filters']['name'] = [
        '#title'         => 'Name',
        '#type'          => 'search',
        '#default_value' => !empty($param['name'])?$param['name']:'',
    ];
    $form['filters']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
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
    // $form_state->setRebuild();
    $query_parameter = array();
    // print_r($form_state->getValue('filters'));exit;
    if (!empty($form_state->getValue('magento_sku'))) {
      $query_parameter['magento_sku']=$form_state->getValue('magento_sku');
    }
    if (!empty($form_state->getValue('name'))) {
      $query_parameter['name']=$form_state->getValue('name');
    }
    $url = Url::fromRoute('product.list_config_product_form', $query_parameter);
    return $form_state->setRedirectUrl($url);

  }

}
