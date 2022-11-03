<?php

namespace Drupal\attribute\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ImportSizeForm.
 */
class ImportSizeForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'import_size_form';
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
      '#title' => $this->t('Attribute Size Csv File'),
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
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
    foreach ($all_data as $each_data) {
      $connection = Database::getConnection();
      $record = array();
      $db_fields = array(
        'magento_id' => $each_data['magento_id'],
        'sort_order' => $each_data['sort_order'],
        'size' => $each_data['size'],
        'nav_size' => $each_data['nav_size'],
      );
      $query_check = $connection->select('miasuki_attribute_size', 'mac')
          ->condition('magento_id', $db_fields['magento_id'])
          ->fields('mac');
      $record = $query_check->execute()->fetchAssoc();
      if (!empty($record['id'])) {
        $query_update = $connection->update('miasuki_attribute_size')
          ->fields($db_fields)
          ->condition('id', $record['id'])
          ->execute();
      }else{
        $query_insert = $connection->insert('miasuki_attribute_size')
          ->fields($db_fields)
          ->execute();
      }
    }
    drupal_set_message('Attribute Updated');
  }

}
