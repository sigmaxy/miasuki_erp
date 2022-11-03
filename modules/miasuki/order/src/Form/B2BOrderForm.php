<?php

namespace Drupal\order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\product\Controller\ProductController;
use Drupal\inventory\Controller\InventoryController;
use Drupal\develop\Controller\ExcelController;

/**
 * Class B2BOrderForm.
 */
class B2BOrderForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'b2b_order_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $b2bform_template_uri = 'public://development/B2BOrderForm.xls';

    // $b2bform_template = Url::fromUri(file_create_url($b2bform_template));
    $b2bform_template = file_create_url($b2bform_template_uri);
    $form['excel_file'] = [
      '#type' => 'managed_file',
      '#upload_location' => 'public://miasuki_file/',
      '#upload_validators' => array(
        'file_validate_extensions' => array('xls'),
        // Pass the maximum file size in bytes
        // 'file_validate_size' => array(MAX_FILE_SIZE*1024*1024),
      ),
      '#title' => $this->t('B2B Order Import File'),
      '#markup' => 'Some arbitrary markup.',
      '#weight' => '1',
    ];
    $form['download_template'] = [
      '#markup' => 'B2B Order Form Template <a href="'.file_create_url($b2bform_template_uri).'">download</a>.<br><br><br>',
      '#weight' => '2',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => '3',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('excel_file') == NULL) {
      $form_state->setErrorByName('excel_file', $this->t('Empty File'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $connection = Database::getConnection();
    $csv_file = file_load($form_state->getValue('excel_file')[0]);
    $file_uri = $csv_file->getFileUri();
    $inputFileType = IOFactory::identify($file_uri);
    $reader = IOFactory::createReader($inputFileType);
    $reader->setReadDataOnly(TRUE);
    $spreadsheet = $reader->load($file_uri);
    $order_detail_data = ExcelController::formate_sheet_data($spreadsheet->getSheet(0)->toArray());
    $order_item_data = ExcelController::formate_sheet_data($spreadsheet->getSheet(1)->toArray());
    $order_address_data = ExcelController::formate_sheet_data($spreadsheet->getSheet(2)->toArray());
    $order_data = array();
    foreach ($order_detail_data as $each_order_detail) {
      $order_data[$each_order_detail['increment_id']]=$each_order_detail;
      foreach ($order_item_data as $each_order_item) {
        if ($each_order_item['increment_id']==$each_order_detail['increment_id']) {
          $order_data[$each_order_detail['increment_id']]['order_item'][]=$each_order_item;
        }
      }
      foreach ($order_address_data as $each_order_address) {
        if ($each_order_address['increment_id']==$each_order_detail['increment_id']) {
          $order_data[$each_order_detail['increment_id']]['order_address'][]=$each_order_address;
        }
      }
    }
    //validate order data
    $order_validation_flag = true;
    foreach ($order_data as $each_order_index=>$each_order) {
      if (empty($each_order['increment_id'])) {
        drupal_set_message('Field increment_id not valid','error');
        $order_validation_flag = false;
      }
      if (empty($each_order['customer_email'])) {
        drupal_set_message('Field customer_email not valid','error');
        $order_validation_flag = false;
      }
      if (empty($each_order['order_currency_code'])) {
        drupal_set_message('Field order_currency_code not valid','error');
        $order_validation_flag = false;
      }
      if (empty($each_order['grand_total'])||is_int($each_order['grand_total'])) {
        drupal_set_message('Field grand_total not valid','error');
        $order_validation_flag = false;
      }
      if (empty($each_order['store_id'])||is_int($each_order['store_id'])) {
        drupal_set_message('Field store_id not valid','error');
        $order_validation_flag = false;
      }
      if (empty($each_order['payment'])) {
        drupal_set_message('Field payment not valid','error');
        $order_validation_flag = false;
      }
      if (empty($each_order['total_qty_ordered'])||is_int($each_order['total_qty_ordered'])) {
        drupal_set_message('Field total_qty_ordered not valid','error');
        $order_validation_flag = false;
      }
      if (empty($each_order['status'])||is_int($each_order['status'])) {
        drupal_set_message('Field status not valid','error');
        $order_validation_flag = false;
      }
      if (count($each_order['order_item'])<1) {
        drupal_set_message('no order_item for '.$each_order['increment_id'],'error');
        $order_validation_flag = false;
      }
      if (count($each_order['order_address'])<1) {
        drupal_set_message('no order_address for '.$each_order['increment_id'],'error');
        $order_validation_flag = false;
      }
      if (empty($each_order['warehouse_id'])||is_int($each_order['warehouse_id'])) {
        drupal_set_message('Field warehouse_id not valid','error');
        $order_validation_flag = false;
      }
      foreach ($each_order['order_item'] as $each_order_item_index=>$each_order_item) {
        $item_valid_flag = true;
        $simple_product_by_sku = ProductController::get_simple_product_by_sku(trim($each_order_item['sku']));
        $simple_product_by_barcode = ProductController::get_simple_product_by_barcode(trim($each_order_item['barcode']));
        if (isset($simple_product_by_sku['id'])) {
          $order_data[$each_order_index]['order_item'][$each_order_item_index]['id'] = $simple_product_by_sku['id'];
        }else if(isset($simple_product_by_barcode['magento_sku'])){
          $order_data[$each_order_index]['order_item'][$each_order_item_index]['sku']= $simple_product_by_barcode['magento_sku'];
          $order_data[$each_order_index]['order_item'][$each_order_item_index]['id'] = $simple_product_by_barcode['id'];
        }else{
          drupal_set_message('Order '.$each_order['increment_id'].' item '.$each_order_item['sku'].' not found','error');
          $order_validation_flag = false;
          $item_valid_flag = false;
        }
        //check item inventory
        if ($item_valid_flag) {
          $check_query = $connection->select('miasuki_inventory', 'mi')
            ->condition('product_id', $order_data[$each_order_index]['order_item'][$each_order_item_index]['id'])
            ->condition('warehouse_id', $each_order['warehouse_id'])
            ->fields('mi');
          $check_record = $check_query->execute()->fetchAssoc();
          // print_r($check_record);
          if (empty($check_record['id'])||$check_record['qty']==0) {
            //no record insert new
            $order_validation_flag = false;
            drupal_set_message('There is no inventory of '.$order_data[$each_order_index]['order_item'][$each_order_item_index]['sku'].' in such warehouse','error');
          }else if ($check_record['qty']<$order_data[$each_order_index]['order_item'][$each_order_item_index]['qty']) {
            //not enough inventory
            $order_validation_flag = false;
            drupal_set_message('Not enough inventory of '.$order_data[$each_order_index]['order_item'][$each_order_item_index]['sku'].' in such warehouse','error');
          }else{
            $order_data[$each_order_index]['order_item'][$each_order_item_index]['new_inventory'] = $check_record['qty']-$order_data[$each_order_index]['order_item'][$each_order_item_index]['qty'];
            $order_data[$each_order_index]['order_item'][$each_order_item_index]['old_inventory'] = $check_record['qty'];
          }
        }
      }
    }
    if ($order_validation_flag) {
      foreach ($order_data as $each_order_index=>$each_order){
        $order_data_each = array(
          'order_type' => 'B2B',
          'increment_id' => $each_order['increment_id'],
          'created_at' => time(),
          'customer_email' => $each_order['customer_email'],
          'customer_lastname' => $each_order['customer_lastname'],
          'customer_firstname' => $each_order['customer_firstname'],
          'order_currency_code' => $each_order['order_currency_code'],
          'grand_total' => $each_order['grand_total'],
          'subtotal' => $each_order['subtotal'],
          'discount_amount' => $each_order['discount_amount'],
          'shipping_amount' => $each_order['shipping_amount'],
          'store_id' => $each_order['store_id'],
          'total_qty_ordered' => $each_order['total_qty_ordered'],
          'payment' => $each_order['payment'],
          'status' => $each_order['status'],
          'tracking_number' => empty($each_order['tracking_number'])?null:$each_order['tracking_number'],
        );
        
        $order_insert_id = $connection->insert('miasuki_order')
          ->fields($order_data_each)
          ->execute();
        foreach ($each_order['order_item'] as $each_order_item) {
          $each_order_item_data = array(
            'order_id' => $order_insert_id,
            'sku' => $each_order_item['sku'],
            'name' => $each_order_item['name'],
            'price' => $each_order_item['price'],
            'original_price' => $each_order_item['original_price'],
            'qty' => $each_order_item['qty'],
          );
          $connection->insert('miasuki_order_item')->fields($each_order_item_data)->execute();
          // completed order deduct inventory
          if ($each_order['status']==6) {
            //update inventory
            $connection->update('miasuki_inventory')
              ->fields([
                'qty' => $each_order_item['new_inventory'],
              ])
              ->condition('product_id', $each_order_item['id'])
              ->condition('warehouse_id', $each_order['warehouse_id'])
              ->execute();
            drupal_set_message('product '.$each_order_item['sku'].' warehouse_id: '.$each_order['warehouse_id'].' qty update to:'.$each_order_item['new_inventory']);
            // $url = Url::fromRoute('inventory.outbound_form');
            InventoryController::modified_log($each_order_item['id'],$each_order['warehouse_id'],$each_order_item['old_inventory'],$each_order_item['new_inventory'],'B2B Order Deduct, Order ID '.$order_insert_id);
          }
        }
        foreach ($each_order['order_address'] as $each_order_address) {
          $each_order_address_data = array(
            'order_id' => $order_insert_id,
            'address_type' => $each_order_address['address_type'],
            'email' => $each_order_address['email'],
            'prefix' => $each_order_address['prefix'],
            'firstname' => $each_order_address['firstname'],
            'lastname' => $each_order_address['lastname'],
            'telephone' => $each_order_address['telephone'],
            'country_id' => $each_order_address['country_id'],
            'postcode' => $each_order_address['postcode'],
            'city' => $each_order_address['city'],
            'street_1' => $each_order_address['street_1'],
            'street_2' => $each_order_address['street_2'],
            'region' => $each_order_address['region'],
          );
          $connection->insert('miasuki_order_address')->fields($each_order_address_data)->execute();
        }
      }
      drupal_set_message('B2B Order Imported');
      $url = Url::fromRoute('order.list_order_form');
      return $form_state->setRedirectUrl($url);
    }
  }

}
