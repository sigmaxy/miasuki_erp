<?php

namespace Drupal\product\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\attribute\Controller\AttributeController;
use Drupal\product\Controller\ProductController;

/**
 * Class SimpleProductForm.
 */
class SimpleProductForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_product_form';
  }
  public $product_id;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $product_id = NULL) {
    $connection = Database::getConnection();
    $record = array();
    if (isset($product_id)) {
      $this->product_id = $product_id;
      $query = $connection->select('miasuki_simple_product', 'msp')
          ->condition('id', $product_id)
          ->fields('msp');
      $record = $query->execute()->fetchAssoc();
    }
    $options_color = AttributeController::get_color_options();
    $options_size = AttributeController::get_size_options();
    $options_length = AttributeController::get_length_options();
    $options_status = ProductController::product_status();
    $form['magento_sku'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Magento SKU'),
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '1',
      '#default_value' => isset($record['magento_sku'])?$record['magento_sku']:'',
    ];
    $form['parent_sku'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Parent SKU'),
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '1',
      '#default_value' => isset($record['parent_sku'])?$record['parent_sku']:'',
    ];
    $form['color_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Color'),
      '#options' => $options_color,
      '#size' => 1,
      '#weight' => '2',
      '#default_value' => isset($record['color_id'])?$record['color_id']:'',
    ];
    $form['size_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Size'),
      '#options' => $options_size,
      '#size' => 1,
      '#weight' => '3',
      '#default_value' => isset($record['size_id'])?$record['size_id']:'',
    ];
    $form['length_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Length'),
      '#options' => $options_length,
      '#size' => 1,
      '#weight' => '4',
      '#default_value' => isset($record['length_id'])?$record['length_id']:'',
    ];
    $form['us_price'] = [
      '#type' => 'textfield',
      '#title' => $this->t('US Price'),
      '#element_validate' => array('element_validate_number'), 
      '#weight' => '5',
      '#default_value' => isset($record['us_price'])?$record['us_price']:'',
    ];
    $form['hk_price'] = [
      '#type' => 'textfield',
      '#title' => $this->t('HK Price'),
      '#weight' => '6',
      '#default_value' => isset($record['hk_price'])?$record['hk_price']:'',
    ];
    $form['eu_price'] = [
      '#type' => 'textfield',
      '#title' => $this->t('EU Price'),
      '#weight' => '7',
      '#default_value' => isset($record['eu_price'])?$record['eu_price']:'',
    ];
    $form['uk_price'] = [
      '#type' => 'textfield',
      '#title' => $this->t('UK Price'),
      '#weight' => '8',
      '#default_value' => isset($record['uk_price'])?$record['uk_price']:'',
    ];
    $form['cn_price'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CN Price'),
      '#weight' => '8',
      '#default_value' => isset($record['cn_price'])?$record['cn_price']:'',
    ];
    $form['us_special_price'] = [
      '#type' => 'textfield',
      '#title' => $this->t('US Special Price'),
      '#weight' => '9',
      '#default_value' => isset($record['us_special_price'])?$record['us_special_price']:'',
    ];
    $form['hk_special_price'] = [
      '#type' => 'textfield',
      '#title' => $this->t('HK Special Price'),
      '#weight' => '10',
      '#default_value' => isset($record['hk_special_price'])?$record['hk_special_price']:'',
    ];
    $form['eu_special_price'] = [
      '#type' => 'textfield',
      '#title' => $this->t('EU Special Price'),
      '#weight' => '11',
      '#default_value' => isset($record['eu_special_price'])?$record['eu_special_price']:'',
    ];
    $form['uk_special_price'] = [
      '#type' => 'textfield',
      '#title' => $this->t('UK Special Price'),
      '#weight' => '12',
      '#default_value' => isset($record['uk_special_price'])?$record['uk_special_price']:'',
    ];
    $form['cn_special_price'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CN Special Price'),
      '#weight' => '12',
      '#default_value' => isset($record['cn_special_price'])?$record['cn_special_price']:'',
    ];
    //barcode_nav data start
    $query = $connection->select('miasuki_barcode', 'mb')
      ->condition('product_id', $product_id)
      ->fields('mb');
    $record = $query->execute()->fetchAll();
    $detail_table_header = array(
      'mapping_id'=>'MappingID',
      'barcode'=>'Barcode',
      'nav_sku'=>'Nav SKU',
      'del'=>'Delete',
      'add'=>'Add',
    );
    $form['barcode_nav'] = [
      '#type' => 'table',
      '#title' => 'Barcode Nav Code',
      '#header' => $detail_table_header,
      '#attributes' => [   
        'class' => ['product_detail_table'],
      ],
      '#weight' => '14',
    ];
    if (count($record)==0) {
      $form['barcode_nav']['new_1']['mapping_id'] = array(
        '#type' => 'markup',
        '#markup' => '',
      );
      $form['barcode_nav']['new_1']['barcode'] = array(
        '#type' => 'textfield',
        '#title' => t('Barcode'),
        '#title_display' => 'invisible',
      );
      $form['barcode_nav']['new_1']['nav_sku'] = array(
        '#type' => 'textfield',
        '#title' => t('Nav SKU'),
        '#title_display' => 'invisible',
      );
      $form['barcode_nav']['new_1']['del'] = array(
        '#type' => 'button',
        '#value' => t('Delete'), 
        '#attributes' => [
          'onclick' => 'return false;',
          'class' => ['barcode_nav_del'],
        ],
      );
      $form['barcode_nav']['new_1']['add'] = array(
        '#type' => 'button',
        '#value' => t('Add'), 
        '#attributes' => [
          'onclick' => 'return false;',
          'class' => ['barcode_nav_add'],
        ],
      );
    }else{
      foreach ($record as $each_barcode_mapping) {
        $form['barcode_nav'][$each_barcode_mapping->id]['mapping_id'] = array(
          '#type' => 'textfield',
          '#title' => t('ID'),
          '#title_display' => 'invisible',
          '#default_value' => $each_barcode_mapping->id,
          '#attributes' => array('readonly' => 'readonly'),
        );
        $form['barcode_nav'][$each_barcode_mapping->id]['barcode'] = array(
          '#type' => 'textfield',
          '#title' => t('Barcode'),
          '#title_display' => 'invisible',
          '#default_value' => $each_barcode_mapping->barcode,
        );
        $form['barcode_nav'][$each_barcode_mapping->id]['nav_sku'] = array(
          '#type' => 'textfield',
          '#title' => t('Nav SKU'),
          '#title_display' => 'invisible',
          '#default_value' => $each_barcode_mapping->nav_sku,
        );
        $form['barcode_nav'][$each_barcode_mapping->id]['del'] = array(
          '#type' => 'button',
          '#value' => t('Delete'), 
          '#attributes' => [
            'onclick' => 'return false;',
            'class' => ['barcode_nav_del'],
            'mapping_id' => [$each_barcode_mapping->id],
          ],
        );
        $form['barcode_nav'][$each_barcode_mapping->id]['add'] = array(
          '#type' => 'button',
          '#value' => t('Add'), 
          '#attributes' => [
            'onclick' => 'return false;',
            'class' => ['barcode_nav_add'],
          ],
        );
      }
    }
    $form['#attached']['library'][] = 'product/product';
    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#options' => $options_status,
      '#size' => 1,
      '#weight' => '16',
      '#default_value' => isset($record['status'])?$record['status']:'',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => '17',
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
        // Display result.
    $connection = Database::getConnection();
    $depth = $connection->transactionDepth();
    $field=$form_state->getValues();
    // print_r($field);exit;
    if ($this->product_id == 'add') {
      $product_insert_id = $connection->insert('miasuki_simple_product')
        ->fields([
          'magento_sku' => $field['magento_sku'],
          'color_id' => $field['color_id'],
          'size_id' => $field['size_id'],
          'length_id' => $field['length_id'],
          'us_price' => empty($field['us_price'])?NULL:$field['us_price'],
          'hk_price' => empty($field['hk_price'])?NULL:$field['hk_price'],
          'eu_price' => empty($field['eu_price'])?NULL:$field['eu_price'],
          'uk_price' => empty($field['uk_price'])?NULL:$field['uk_price'],
          'cn_price' => empty($field['cn_price'])?NULL:$field['cn_price'],
          'us_special_price' => empty($field['us_special_price'])?NULL:$field['us_special_price'],
          'hk_special_price' => empty($field['hk_special_price'])?NULL:$field['hk_special_price'],
          'eu_special_price' => empty($field['eu_special_price'])?NULL:$field['eu_special_price'],
          'uk_special_price' => empty($field['uk_special_price'])?NULL:$field['uk_special_price'],
          'cn_special_price' => empty($field['cn_special_price'])?NULL:$field['cn_special_price'],
          'parent_sku' => $field['parent_sku'],
          'status' => $field['status'],
        ])
        ->execute();
      $barcode_nav_mapping_product_id = $product_insert_id;
      drupal_set_message('Simple Product Created');
    }else{
      $db_fields = array(
        'magento_sku' => $field['magento_sku'],
        'color_id' => $field['color_id'],
        'size_id' => $field['size_id'],
        'length_id' => $field['length_id'],
        'us_price' => empty($field['us_price'])?NULL:$field['us_price'],
        'hk_price' => empty($field['hk_price'])?NULL:$field['hk_price'],
        'eu_price' => empty($field['eu_price'])?NULL:$field['eu_price'],
        'uk_price' => empty($field['uk_price'])?NULL:$field['uk_price'],
        'cn_price' => empty($field['cn_price'])?NULL:$field['cn_price'],
        'us_special_price' => empty($field['us_special_price'])?NULL:$field['us_special_price'],
        'hk_special_price' => empty($field['hk_special_price'])?NULL:$field['hk_special_price'],
        'eu_special_price' => empty($field['eu_special_price'])?NULL:$field['eu_special_price'],
        'uk_special_price' => empty($field['uk_special_price'])?NULL:$field['uk_special_price'],
        'cn_special_price' => empty($field['cn_special_price'])?NULL:$field['cn_special_price'],
        'parent_sku' => $field['parent_sku'],
        'status' => $field['status'],
      );
      $query = \Drupal::database();
      $query->update('miasuki_simple_product')
          ->fields($db_fields)
          ->condition('id', $this->product_id)
          ->execute();
      $barcode_nav_mapping_product_id = $this->product_id;
      drupal_set_message('Simple Product Updated');
    }
    //update barcode nav sku mapping
    foreach ($field['barcode_nav'] as $key => $each_barcode_nav) {
      if (isset($each_barcode_nav['mapping_id'])) {
        if ($each_barcode_nav['ops']=='del') {
          $num_deleted = $connection->delete('miasuki_barcode')
            ->condition('id', $each_barcode_nav['mapping_id'])
            ->execute();
        }else{
          $connection->update('miasuki_barcode')
            ->fields([
              'product_id' => $barcode_nav_mapping_product_id,
              'barcode' => $each_barcode_nav['barcode'],
              'nav_sku' => $each_barcode_nav['nav_sku'],
            ])
            ->condition('id', $each_barcode_nav['mapping_id'])
            ->execute();
        }
      }else{
        $connection->insert('miasuki_barcode')
          ->fields([
            'product_id' => $barcode_nav_mapping_product_id,
            'barcode' => $each_barcode_nav['barcode'],
            'nav_sku' => $each_barcode_nav['nav_sku'],
          ])
          ->execute();
      }
    }
  }

}
