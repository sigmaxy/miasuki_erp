<?php

namespace Drupal\inventory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

/**
 * Class SyncForm.
 */
class SyncForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sync_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_simple_product', 'msp');
    $query->fields('msp');
    $simple_product = $query->execute()->fetchAll();

    $query = $connection->select('miasuki_simple_product', 'msp');
    $query->addExpression('MAX(id)');
    $max_sp_id = $query->execute()->fetchField();

    $query = $connection->select('miasuki_config_product', 'mcp');
    $query->fields('mcp');
    $config_product = $query->execute()->fetchAll();

    $query = $connection->select('miasuki_config_product', 'mcp');
    $query->addExpression('MAX(id)');
    $max_cp_id = $query->execute()->fetchField();
    $form['sync_box'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Sync Box'),
      '#open'  => true,
    ];
    $form['sync_box']['total_simple_product'] = [
      '#type' => 'hidden',
      '#weight' => '1',
      '#default_value' => $max_sp_id,
      '#attributes' => array(
        'id' => array('total_simple_product'),
      ),
    ];
    $form['sync_box']['total_config_product'] = [
      '#type' => 'hidden',
      '#weight' => '1',
      '#default_value' => $max_cp_id,
      '#attributes' => array(
        'id' => array('total_config_product'),
      ),
    ];
    $form['sync_box']['start_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Start ID'),
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '1',
      '#default_value' => isset($record['start_id'])?$record['start_id']:'1',
    ];
    $form['sync_box']['sync'] = [
      '#type' => 'button',
      '#weight' => '2',
      '#value' => $this->t('Sync Simple'),
      '#attributes' => array(
        'onclick' => 'return false;',
        'class' => array('sync_button'),
        'id' => array('sync_simple_button'),
      ),
    ];
    $form['sync_box']['sync_config'] = [
      '#type' => 'button',
      '#weight' => '2',
      '#value' => $this->t('Sync Config'),
      '#attributes' => array(
        'onclick' => 'return false;',
        'class' => array('sync_button'),
        'id' => array('sync_config_button'),
      ),
    ];
    $form['sync_box']['progress_bar'] = [
      '#type' => 'progress_bar',
      '#weight' => '3',
      '#title' => $this->t('Progress Bar'),
      '#markup' => t('<div class="sync_progress_bar"><div class="sync_progress_bar_inner"></div><div class="sync_progress_bar_text">Progress Bar <span id="sync_progress_bar_percent">0</span> %</div></div>'),
    ];
    $header_table = array(
      'erp_product_id'=>array(
        'data'=>t('ID'),
        'class'=>array('header_erp_product_id'),
      ),
      'magento_sku'=>array(
        'data'=>t('Magento SKU'),
        'class'=>array('header_magento_sku'),
      ),
      'default_source'=>array(
        'data'=>t('Default Source'),
        'class'=>array('header_default_source'),
      ),
      'hk_source'=>array(
        'data'=>t('HK Source'),
        'class'=>array('header_hk_source'),
      ),
      'cn_source'=>array(
        'data'=>t('CN Source'),
        'class'=>array('header_cn_source'),
      ),
    );
    $header_c_table = array(
      'erp_product_id'=>array(
        'data'=>t('ID'),
        'class'=>array('header_erp_product_id'),
      ),
      'magento_sku'=>array(
        'data'=>t('Magento SKU'),
        'class'=>array('header_magento_sku'),
      ),
      'us_store'=>array(
        'data'=>t('US Store'),
      ),
      'hk_store'=>array(
        'data'=>t('HK Store'),
      ),
      'eu_store'=>array(
        'data'=>t('EU Store'),
      ),
      'uk_store'=>array(
        'data'=>t('UK Store'),
      ),
      'cn_store'=>array(
        'data'=>t('CN Store'),
      ),
    );
    $simple_rows=array();
    foreach($simple_product as $data){
      //print the data from table
      $row_data = array();
      $row_data['erp_product_id'] = array(
        'data'=>$data->id,
        'class'=>array('erp_product_id'),
        'id'=>array('erp_product_id_'.$data->id),
      );
      $row_data['magento_sku'] = array(
        'data'=>$data->magento_sku,
        'class'=>array('magento_sku'),
        'id'=>array('magento_sku_'.$data->magento_sku),
      );
      $row_data['default_source'] = array(
        'data'=>null,
        'class'=>array('default_source'),
        'id'=>array('us_'.$data->magento_sku),
      );
      $row_data['hk_source'] = array(
        'data'=>null,
        'class'=>array('hk_source'),
        'id'=>array('hk_'.$data->magento_sku),
      );
      $row_data['cn_source'] = array(
        'data'=>null,
        'class'=>array('cn_source'),
        'id'=>array('cn_'.$data->magento_sku),
      );
      $simple_rows[] = $row_data;
    }
    $form['simple_product'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Simple Product'),
      '#open'  => true,
    ];
    $form['simple_product']['table'] = [
      '#type' => 'table',
      '#header' => $header_table,
      '#rows' => $simple_rows,
      '#empty' => t('No Inventory found'),
      '#weight' => '3',
      '#attributes' => [   
        'class' => ['inventory_table'],
        'id' => ['simple_product_list'],
      ],
    ];
    $config_rows=array();
    foreach($config_product as $data){
      //print the data from table
      $row_data = array();
      $row_data['erp_product_id'] = array(
        'data'=>$data->id,
        'class'=>array('erp_product_id'),
        'id'=>array('erp_product_id_'.$data->id),
      );
      $row_data['magento_sku'] = array(
        'data'=>$data->magento_sku,
        'class'=>array('magento_sku'),
        'id'=>array('magento_sku_'.$data->magento_sku),
      );
      $row_data['us_store'] = array(
        'data'=>null,
      );
      $row_data['hk_store'] = array(
        'data'=>null,
      );
      $row_data['eu_store'] = array(
        'data'=>null,
      );
      $row_data['uk_store'] = array(
        'data'=>null,
      );
      $row_data['cn_store'] = array(
        'data'=>null,
      );
      $config_rows[] = $row_data;
    }
    $form['config_product'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Config Product'),
      '#open'  => true,
    ];
    $form['config_product']['table'] = [
      '#type' => 'table',
      '#header' => $header_c_table,
      '#rows' => $config_rows,
      '#empty' => t('No Inventory found'),
      '#weight' => '3',
      '#attributes' => [   
        'class' => ['inventory_table'],
        'id' => ['config_product_list'],
      ],
    ];
    $form['#attached']['library'][] = 'inventory/inventory';
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
    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }

  }

}
