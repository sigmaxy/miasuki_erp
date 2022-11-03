<?php

namespace Drupal\product\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\product\Controller\ProductController;

/**
 * Class ConfigProductForm.
 */
class ConfigProductForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_product_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $product_id = NULL) {
    $connection = Database::getConnection();
    $record = array();
    if (isset($product_id)) {
      $this->product_id = $product_id;
      $query = $connection->select('miasuki_config_product', 'mcp')
          ->condition('id', $product_id)
          ->fields('mcp');
      $record = $query->execute()->fetchAssoc();
    }
    $options_status = ProductController::product_status();
    $form['magento_sku'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Magento SKU'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '1',
      '#default_value' => isset($record['magento_sku'])?$record['magento_sku']:'',
    ];
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#attributes' => array(
        'placeholder' => t('Product Name'),
      ),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '2',
      '#default_value' => isset($record['label'])?$record['label']:'',
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#attributes' => array(
        'placeholder' => t('Product Tagline Description'),
      ),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '3',
      '#default_value' => isset($record['name'])?$record['name']:'',
    ];
    $form['name_cn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name CN'),
      '#attributes' => array(
        'placeholder' => t('Product Tagline Description CN'),
      ),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '4',
      '#default_value' => isset($record['name_cn'])?$record['name_cn']:'',
    ];
    $form['category'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Category'),
      '#attributes' => array(
        'placeholder' => t('Shop Page Category'),
      ),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '5',
      '#default_value' => isset($record['category'])?$record['category']:'',
    ];
    $form['short_description'] = [
      '#type' => 'textarea',
      '#wysiwyg' => false,
      '#title' => $this->t('Short Description'),
      '#weight' => '6',
      '#default_value' => isset($record['short_description'])?$record['short_description']:'',
    ];
    $form['short_description_cn'] = [
      '#type' => 'textarea',
      '#wysiwyg' => false,
      '#title' => $this->t('Short Description CN'),
      '#weight' => '7',
      '#default_value' => isset($record['short_description_cn'])?$record['short_description_cn']:'',
    ];
    $form['cloth_and_cut'] = [
      '#type' => 'textarea',
      '#wysiwyg' => false,
      '#title' => $this->t('Cloth and Cut'),
      '#weight' => '8',
      '#default_value' => isset($record['cloth_and_cut'])?$record['cloth_and_cut']:'',
    ];
    $form['cloth_and_cut_cn'] = [
      '#type' => 'textarea',
      '#wysiwyg' => false,
      '#title' => $this->t('Cloth and Cut CN'),
      '#weight' => '9',
      '#default_value' => isset($record['cloth_and_cut_cn'])?$record['cloth_and_cut_cn']:'',
    ];
    $form['fit'] = [
      '#type' => 'textarea',
      '#wysiwyg' => false,
      '#title' => $this->t('Fit'),
      '#weight' => '10',
      '#default_value' => isset($record['fit'])?$record['fit']:'',
    ];
    $form['fit_cn'] = [
      '#type' => 'textarea',
      '#wysiwyg' => false,
      '#title' => $this->t('Fit CN'),
      '#weight' => '11',
      '#default_value' => isset($record['fit_cn'])?$record['fit_cn']:'',
    ];
    $form['fabric_care'] = [
      '#type' => 'textarea',
      '#wysiwyg' => false,
      '#title' => $this->t('Fabric Care'),
      '#weight' => '12',
      '#default_value' => isset($record['fabric_care'])?$record['fabric_care']:'',
    ];
    $form['fabric_care_cn'] = [
      '#type' => 'textarea',
      '#wysiwyg' => false,
      '#title' => $this->t('Fabric Care CN'),
      '#weight' => '13',
      '#default_value' => isset($record['fabric_care_cn'])?$record['fabric_care_cn']:'',
    ];
    $form['match_it_with'] = [
      '#type' => 'textarea',
      '#wysiwyg' => false,
      '#title' => $this->t('Match It With'),
      '#weight' => '14',
      '#default_value' => isset($record['match_it_with'])?$record['match_it_with']:'',
    ];
    $form['size_guide'] = [
      '#type' => 'textarea',
      '#wysiwyg' => false,
      '#title' => $this->t('Size Guide'),
      '#weight' => '15',
      '#default_value' => isset($record['size_guide'])?$record['size_guide']:'',
    ];
    $form['you_might_like'] = [
      '#type' => 'textarea',
      '#wysiwyg' => false,
      '#title' => $this->t('You Might Like'),
      '#weight' => '16',
      '#default_value' => isset($record['you_might_like'])?$record['you_might_like']:'',
    ];
    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#options' => $options_status,
      '#size' => 1,
      '#weight' => '17',
      '#default_value' => isset($record['status'])?$record['status']:'',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#weight' => '18',
      '#value' => $this->t('Submit'),
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
    $field=$form_state->getValues();
    if ($this->product_id == 'add') {
      $connection->insert('miasuki_config_product')
        ->fields([
          'magento_sku' => $field['magento_sku'],
          'label' => $field['label'],
          'name' => $field['name'],
          'name_cn' => $field['name_cn'],
          'category' => $field['category'],
          'short_description' => $field['short_description'],
          'short_description_cn' => $field['short_description_cn'],
          'cloth_and_cut' => $field['cloth_and_cut'],
          'cloth_and_cut_cn' => $field['cloth_and_cut_cn'],
          'fit' => $field['fit'],
          'fit_cn' => $field['fit_cn'],
          'fabric_care' => $field['fabric_care'],
          'fabric_care_cn' => $field['fabric_care_cn'],
          'match_it_with' => $field['match_it_with'],
          'size_guide' => $field['size_guide'],
          'you_might_like' => $field['you_might_like'],
          'status' => $field['status'],
        ])
        ->execute();
      drupal_set_message('Config Product Created');
    }else{
      $db_fields = array(
        'magento_sku' => $field['magento_sku'],
        'label' => $field['label'],
        'name' => $field['name'],
        'name_cn' => $field['name_cn'],
        'category' => $field['category'],
        'short_description' => $field['short_description'],
        'short_description_cn' => $field['short_description_cn'],
        'cloth_and_cut' => $field['cloth_and_cut'],
        'cloth_and_cut_cn' => $field['cloth_and_cut_cn'],
        'fit' => $field['fit'],
        'fit_cn' => $field['fit_cn'],
        'fabric_care' => $field['fabric_care'],
        'fabric_care_cn' => $field['fabric_care_cn'],
        'match_it_with' => $field['match_it_with'],
        'size_guide' => $field['size_guide'],
        'you_might_like' => $field['you_might_like'],
        'status' => $field['status'],
      );
      $query = \Drupal::database();
      $query->update('miasuki_config_product')
          ->fields($db_fields)
          ->condition('id', $this->product_id)
          ->execute();
      drupal_set_message('Config Product Updated');
    }

  }

}
