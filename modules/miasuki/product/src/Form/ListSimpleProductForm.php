<?php

namespace Drupal\product\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\attribute\Controller\AttributeController;
use Drupal\product\Controller\ProductController;

/**
 * Class ListSimpleProductForm.
 */
class ListSimpleProductForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'list_simple_product_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $header_table = array(
      'id'=>    t('ID'),
      'magento_sku' => t('Mangento SKU'),
      'parent_sku' =>  t('Parent SKU'),
      'color' =>  t('Color'),
      'size' =>  t('Size'),
      'length' =>  t('Length'),
      'us_price' =>  t('US Price'),
      'hk_price' =>  t('HK Price'),
      'eu_price' =>  t('EU Price'),
      'uk_price' =>  t('UK Price'),
      'cn_price' =>  t('CN Price'),
      'sync' =>  t('Sync'),
      'status' =>  t('Status'),
      'opt1' => t('View/Edit'),
    );
    $param = \Drupal::request()->query->all();
    $query = \Drupal::database()->select('miasuki_simple_product', 'msp');
    // $query->fields('msp', ['id','magento_sku','parent_sku','color_id','size_id','length_id']);
    $query->fields('msp');
    if (!empty($param['magento_sku'])) {
      $query->condition('magento_sku', "%" . $param['magento_sku'] . "%", 'LIKE');
    }
    if (!empty($param['parent_sku'])) {
      $query->condition('parent_sku', "%" . $param['parent_sku'] . "%", 'LIKE');
    }
    if (!empty($param['color_id'])) {
      $query->condition('color_id', $param['color_id']);
    }
    if (!empty($param['size_id'])) {
      $query->condition('size_id', $param['size_id']);
    }
    if (!empty($param['length_id'])) {
      $query->condition('length_id', $param['length_id']);
    }
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(100);
    $results = $pager->execute()->fetchAll();
    $rows=array();
    $options_color = AttributeController::get_color_options_mapping();
    $options_size = AttributeController::get_size_options_mapping();
    $options_length = AttributeController::get_length_options_mapping();
    foreach($results as $data){
        $edit   = Url::fromUserInput('/product/form/simple_product/'.$data->id);
      //print the data from table
        if ($data->sync_flag==1) {
          $sync_flag = 'Unsync';
        }else if($data->sync_flag==2){
          $sync_flag = 'Sync';
        }else{
          $sync_flag = 'Offline';
        }
        $rows[] = array(
          'id' =>$data->id,
          'magento_sku' => array(
            'data'=>$data->magento_sku,
            'title'=>array($data->magento_sku),
          ),
          'parent_sku' => $data->parent_sku,
          'color' => $options_color[$data->color_id],
          'size' => $options_size[$data->size_id],
          'length' => $options_length[$data->length_id],
          'us_price' => $data->us_price.'('.$data->us_special_price.')',
          'hk_price' => $data->hk_price.'('.$data->hk_special_price.')',
          'eu_price' => $data->eu_price.'('.$data->eu_special_price.')',
          'uk_price' => $data->uk_price.'('.$data->uk_special_price.')',
          'cn_price' => $data->cn_price.'('.$data->cn_special_price.')',
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
      '#type'          => 'textfield',
      '#default_value' => !empty($param['magento_sku'])?$param['magento_sku']:'',
      '#weight' => '1',
    ];
    $form['filters']['parent_sku'] = [
      '#title'         => 'Parent SKU',
      '#type'          => 'textfield',
      '#default_value' => !empty($param['parent_sku'])?$param['parent_sku']:'',
      '#weight' => '2',
    ];
    $form['filters']['color_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Color'),
      '#options' => $options_color,
      '#default_value' => !empty($param['color_id'])?$param['color_id']:'',
      '#weight' => '3',
    ];
    $form['filters']['size_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Size'),
      '#options' => $options_size,
      '#default_value' => !empty($param['size_id'])?$param['size_id']:'',
      '#weight' => '4',
    ];
    $form['filters']['length_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Length'),
      '#options' => $options_length,
      '#default_value' => !empty($param['length_id'])?$param['length_id']:'',
      '#weight' => '4',
    ];
    $form['filters']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#weight' => '10',
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
    if (!empty($form_state->getValue('parent_sku'))) {
      $query_parameter['parent_sku']=$form_state->getValue('parent_sku');
    }
    if (!empty($form_state->getValue('color_id'))) {
      $query_parameter['color_id']=$form_state->getValue('color_id');
    }
    if (!empty($form_state->getValue('size_id'))) {
      $query_parameter['size_id']=$form_state->getValue('size_id');
    }
    if (!empty($form_state->getValue('length_id'))) {
      $query_parameter['length_id']=$form_state->getValue('length_id');
    }
    $url = Url::fromRoute('product.list_simple_product_form', $query_parameter);
    return $form_state->setRedirectUrl($url);


  }

}
