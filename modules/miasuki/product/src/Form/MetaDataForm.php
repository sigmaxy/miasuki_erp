<?php

namespace Drupal\product\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
/**
 * Class MetaDataForm.
 */
class MetaDataForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'meta_data_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $connection = Database::getConnection();
    $header_table_page = array(
      'id'=>t('Detail'),
      'page_id'=>array(
        'data'=>t('Page ID'),
        'class'=>array('table_header_page_id'),
      ),
      'store'=>t('Store Front'),
      'page_title'=>t('Page Title'),
    );
    $query_page = $connection->select('miasuki_seo', 'ms');
    $query_page->fields('ms');
    $query_page->condition('page_type', 'static');
    $results_page = $query_page->execute()->fetchAll();
    $rows_page=array();
    foreach($results_page as $data){
      $row_data = array();
      $row_data['control'] = array(
        'data'=>null,
        'class'=>array('seo-control'),
        'title'=>$data->id,
        'data-link'=>$data->link,
        'data-meta_title'=>$data->meta_title,
        'data-meta_description'=>$data->meta_description,
        'data-meta_keywords'=>$data->meta_keywords,
        'data-img_alt'=>$data->img_alt,
      );
      $row_data['page_id'] = array(
        'data'=>$data->page_id,
      );
      $row_data['store'] = array(
        'data'=>$data->store,
      );
      $row_data['page_title'] = array(
        'data'=>$data->page_title,
      );
      $rows_page[] = $row_data;
    }
    $header_table_product = array(
      'id'=>t('Detail'),
      'sku'=>t('SKU'),
      'store'=>t('Store Front'),
    );
    $query_product = $connection->select('miasuki_seo', 'ms');
    $query_product->fields('ms');
    $query_product->condition('page_type', 'product');
    $results_product = $query_product->execute()->fetchAll();
    $rows_product=array();
    foreach($results_product as $data){
      $row_data = array();
      $row_data['control'] = array(
        'data'=>null,
        'class'=>array('seo-control'),
        'title'=>$data->id,
        'data-link'=>$data->link,
        'data-meta_title'=>$data->meta_title,
        'data-meta_description'=>$data->meta_description,
        'data-meta_keywords'=>$data->meta_keywords,
        'data-img_alt'=>$data->img_alt,
      );
      $row_data['sku'] = array(
        'data'=>$data->sku,
      );
      $row_data['store'] = array(
        'data'=>$data->store,
      );
      $rows_product[] = $row_data;
    }
    $form['metadatafileset'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Batch Meta Data'),
        '#open'  => true,
      ];
    $form['metadatafileset']['csv_file'] = [
      '#type' => 'managed_file',
      '#upload_location' => 'public://miasuki_file/',
      '#upload_validators' => array(
        'file_validate_extensions' => array('csv'),
      ),
      '#title' => $this->t('Meta Data File'),
      '#weight' => '2',
    ];
    $form['metadatafileset']['download_template'] = [
      '#markup' => 'Get CDN deployed Meta Datas File <a target="_blank" href="https://hk.miasuki.com/develop/developer/tools/action/export_meta_data">Meta Data</a>.<br><br>',
      '#weight' => '3',
    ];
    $form['metadatafileset']['upload'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#submit' => array('::batch_meta_data_upload'),
      '#weight' => '4',
    ];
    $form['pagefileset'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Static Page Meta Data'),
        '#open'  => true,
      ];
    $form['pagefileset']['table'] = [
      '#type' => 'table',
      '#header' => $header_table_page,
      '#rows' => $rows_page,
      '#empty' => t('No Inventory found'),
      '#attributes' => [   
        'class' => ['seo_table'],
      ],
    ];
    $form['productfileset'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Product Page Meta Data'),
        '#open'  => true,
      ];
    $form['productfileset']['table'] = [
      '#type' => 'table',
      '#header' => $header_table_product,
      '#rows' => $rows_product,
      '#empty' => t('No Inventory found'),
      '#attributes' => [   
        'class' => ['seo_table'],
      ],
    ];
    $form['#attached']['library'][] = 'inventory/inventory';
    $form['#attached']['library'][] = 'product/product';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message('Meta Data Updated');
  }
  /**
   * {@inheritdoc}
   */
  public function batch_meta_data_upload(array &$form, FormStateInterface $form_state) {
    $csv_file = file_load($form_state->getValue('csv_file')[0]);
    $file_uri = $csv_file->getFileUri();
    $row = 1;
    $data_index = array();
    $all_data = array();
    $row_data = array();
    if (($handle = fopen($file_uri, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, ",")) !== FALSE) {
            $num = count($data);
            if ($row==1) {
                $data_index = $data;
            }else{
                for ($c=0; $c < $num; $c++) {
                    $row_data[$data_index[$c]]= $data[$c];
                }
                $all_data[] = $row_data;
            }
            $row++;                    
        }
        fclose($handle);
    }
    $connection = Database::getConnection();
    // $query_truncate = $connection->truncate('miasuki_config_product')->execute();
    foreach ($all_data as $each_data) {
      $record = array();
      // echo $each_data['Status'];
      // echo ProductController::get_product_statusid_by_status($each_data['Status']);
      // echo '<br>';
      $db_fields = array(
        'page_type' => $each_data['page type'],
        'sku' => !empty($each_data['sku'])?$each_data['sku']:null,
        'page_id' => !empty($each_data['page id'])?$each_data['page id']:null,
        'link' => $each_data['link'],
        'store' => $each_data['store'],
        'page_title' => $each_data['page title'],
        'meta_title' => $each_data['meta title'],
        'meta_description' => $each_data['meta description'],
        'meta_keywords' => $each_data['meta keywords'],
        'img_alt' => $each_data['img alt text'],
      );
      if ($each_data['page type']=='static') {
        $query_check = $connection->select('miasuki_seo', 'ms')
          ->condition('page_id', $db_fields['page_id'])
          ->condition('store', $db_fields['store'])
          ->fields('ms');
      }else if ($each_data['page type']=='product') {
        $query_check = $connection->select('miasuki_seo', 'ms')
          ->condition('sku', $db_fields['sku'])
          ->condition('store', $db_fields['store'])
          ->fields('ms');
      }
      
      $record = $query_check->execute()->fetchAssoc();
      if (!empty($record['id'])) {
        $query_update = $connection->update('miasuki_seo')
          ->fields($db_fields)
          ->condition('id', $record['id'])
          ->execute();
      }else{
        $query_insert = $connection->insert('miasuki_seo')
          ->fields($db_fields)
          ->execute();
      }
    }
    drupal_set_message('Please contact admin to clear server cache and deploy to CDN');

  }

}
