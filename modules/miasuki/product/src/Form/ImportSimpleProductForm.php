<?php

namespace Drupal\product\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\attribute\Controller\AttributeController;
use Drupal\product\Controller\ProductController;

/**
 * Class ImportSimpleProductForm.
 */
class ImportSimpleProductForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'import_simple_product_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['csv_file'] = [
      '#type' => 'managed_file',
      '#upload_location' => 'public://miasuki_file/',
      '#upload_validators' => array(
        'file_validate_extensions' => array('gif png jpg jpeg csv'),
        // Pass the maximum file size in bytes
        // 'file_validate_size' => array(MAX_FILE_SIZE*1024*1024),
      ),
      '#title' => $this->t('Simple Product CSV File'),
      '#weight' => '1',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => '2',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if ($form_state->getValue('csv_file') == NULL) {
      $form_state->setErrorByName('csv_file', $this->t('Empty File'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
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
    $color_data = AttributeController::get_colorname_to_id();
    $size_data = AttributeController::get_sizename_to_id();
    $length_data = AttributeController::get_lengthname_to_id();
    $connection = Database::getConnection();
    // $query_truncate = $connection->truncate('miasuki_simple_product')->execute();
    foreach ($all_data as $each_data) {
      $record = array();
      $db_fields = array(
        'magento_sku' => $each_data['Magento SKU'],
        'color_id' => $color_data[$each_data['Color']],
        'size_id' => $size_data[$each_data['Size']],
        'length_id' => $length_data[$each_data['Length']],
        'us_price' => $each_data['US Price'],
        'hk_price' => $each_data['HK Price'],
        'eu_price' => $each_data['EU Price'],
        'uk_price' => $each_data['UK Price'],
        'cn_price' => $each_data['CN Price'],
        'us_special_price' => !empty($each_data['US Special Price'])?$each_data['US Special Price']:null,
        'hk_special_price' => !empty($each_data['HK Special Price'])?$each_data['HK Special Price']:null,
        'eu_special_price' => !empty($each_data['EU Special Price'])?$each_data['EU Special Price']:null,
        'uk_special_price' => !empty($each_data['UK Special Price'])?$each_data['UK Special Price']:null,
        'cn_special_price' => !empty($each_data['CN Special Price'])?$each_data['CN Special Price']:null,
        'parent_sku' => $each_data['Config SKU'],
        'status' => ProductController::get_product_statusid_by_status($each_data['Status']),
        'sync_flag' => 2,
      );
      if (!empty($each_data['Nav SKU'])) {
        $db_fields['nav_sku'] = $each_data['Nav SKU'];
      }
      if (!empty($each_data['Barcode'])) {
        $db_fields['barcode'] = $each_data['Nav SKU'];
      }
      $query_check = $connection->select('miasuki_simple_product', 'msp')
          ->condition('magento_sku', $db_fields['magento_sku'])
          ->fields('msp');
      $record = $query_check->execute()->fetchAssoc();
      if (!empty($record['id'])) {
        $query_update = $connection->update('miasuki_simple_product')
          ->fields($db_fields)
          ->condition('id', $record['id'])
          ->execute();
      }else{
        $query_insert = $connection->insert('miasuki_simple_product')
          ->fields($db_fields)
          ->execute();
      }
    }
    drupal_set_message('Simple Product Updated');

  }

}
