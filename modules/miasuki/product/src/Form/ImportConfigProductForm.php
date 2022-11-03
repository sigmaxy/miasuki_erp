<?php

namespace Drupal\product\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\product\Controller\ProductController;


/**
 * Class ImportConfigProductForm.
 */
class ImportConfigProductForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'import_config_product_form';
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
      '#title' => $this->t('Config Product CSV File'),
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
    $connection = Database::getConnection();
    // $query_truncate = $connection->truncate('miasuki_config_product')->execute();
    foreach ($all_data as $each_data) {
      $record = array();
      // echo $each_data['Status'];
      // echo ProductController::get_product_statusid_by_status($each_data['Status']);
      // echo '<br>';
      $db_fields = array(
        'magento_sku' => $each_data['Magento SKU'],
        'label' => $each_data['Label'],
        'name' => $each_data['Name'],
        'name_cn' => $each_data['cn Name'],
        'category' => $each_data['Category IDs'],
        'short_description' => $each_data['Short Description'],
        'short_description_cn' => $each_data['cn Short Description'],
        'cloth_and_cut' => $each_data['Cloth and Cut'],
        'cloth_and_cut_cn' => $each_data['cn Cloth and Cut'],
        'fit' => $each_data['Fit'],
        'fit_cn' => $each_data['cn Fit'],
        'fabric_care' => $each_data['Fabric Care'],
        'fabric_care_cn' => $each_data['cn Fabric Care'],
        'match_it_with' => $each_data['Match It With'],
        'size_guide' => $each_data['Size Guide'],
        'you_might_like' => $each_data['You Might Like'],
        'status' => ProductController::get_product_statusid_by_status($each_data['Status']),
        'sync_flag' => 2,
      );
      $query_check = $connection->select('miasuki_config_product', 'mcp')
          ->condition('magento_sku', $db_fields['magento_sku'])
          ->fields('mcp');
      $record = $query_check->execute()->fetchAssoc();
      if (!empty($record['id'])) {
        $query_update = $connection->update('miasuki_config_product')
          ->fields($db_fields)
          ->condition('id', $record['id'])
          ->execute();
      }else{
        $query_insert = $connection->insert('miasuki_config_product')
          ->fields($db_fields)
          ->execute();
      }
    }
    drupal_set_message('Config Product Updated');

  }

}
