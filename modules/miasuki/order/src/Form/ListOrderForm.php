<?php

namespace Drupal\order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\attribute\Controller\AttributeController;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\order\Controller\OrderController;
use Drupal\api\Controller\ApiOrderController;
use Drupal\develop\Controller\ExcelController;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Class ListOrderForm.
 */
class ListOrderForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'list_order_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $header_table = array(
      'increment_id' => t('Order ID'),
      'customer_name' => t('Customer Name'),
      'customer_email' => t('Email'),
      'country_id' => t('Shipping Country'),
      'created_at' => t('Created At'),
      'tracking_number' => t('Tracking'),
      'status' => t('Status'),
      'opt' => t('Operation'),
    );
    $param = \Drupal::request()->query->all();
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_order', 'mo');
    $query->fields('mo');
    if (!empty($param['increment_id'])) {
      $query->condition('increment_id', "%" . $param['increment_id'] . "%", 'LIKE');
    }
    if (!empty($param['status'])&&$param['status']!=0) {
      $query->condition('status', $param['status']);
    }
    $query->orderBy('created_at', 'DESC');
    // $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
    $results = $query->execute()->fetchAll();
    $rows=array();
    $order_status = AttributeController::get_order_status();
    $country_arr = AttributeController::get_country_list();
    foreach($results as $data){
        $view   = Url::fromUserInput('/order/form/order_detail/'.$data->id);
      //print the data from table
        $shipping_address = OrderController::get_order_shipping_address_by_id($data->id);
        $row_data = array(
          'increment_id' =>$data->increment_id,
          'customer_name' =>$data->customer_firstname.', '.$data->customer_lastname,
          'customer_email' =>$data->customer_email,
          'country_id' =>$country_arr[$shipping_address['country_id']],
          'created_at' =>date("Y-m-d H:i:s",$data->created_at),
          'tracking_number' =>$data->tracking_number,
          'status' =>$order_status[$data->status],
          'opt' =>\Drupal::l('View', $view),
        );
        $rows[] = $row_data;
    }

    $form['filters'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Filter'),
      '#open'  => true,
    ];

    $form['filters']['increment_id'] = [
      '#title'         => 'Order ID',
      '#type'          => 'textfield',
      '#default_value' => !empty($param['increment_id'])?$param['increment_id']:'',
      '#weight' => '1',
    ];
    // $order_status_options = AttributeController::get_order_status();
    $order_status_options = array_merge(array(0=>'None'),AttributeController::get_order_status());
    // print_r($order_status_options);exit;
    $form['filters']['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#options' => $order_status_options,
      '#default_value' => !empty($param['status'])?$param['status']:'',
      '#weight' => '2',
    ];
    $form['filters']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#weight' => '10',
    ];
    $form['filters']['sync'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sync'),
      '#attributes' => [   
        'class' => ['next_button'],
      ],
      '#submit' => array('::SyncOrder'),
      '#weight' => '11',
    ];
    $form['filters']['export'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export'),
      '#attributes' => [   
        'class' => ['next_button'],
      ],
      '#submit' => array('::ExportOrders'),
      '#weight' => '11',
    ];
    //display data in site
    $form['table'] = [
      '#type' => 'table',
      '#header' => $header_table,
      '#rows' => $rows,
      '#empty' => t('No Order found'),
      '#attributes' => [   
        'class' => ['order_list_table'],
      ],
    ];
    $form['pager'] = array(
      '#type' => 'pager'
    );
    $form['#attached']['library'][] = 'inventory/inventory';
    return $form;
  }
  public function ExportOrders(array &$form, FormStateInterface $form_state) {
    $all_order_data = OrderController::get_all_orders();
    $order_detail_data = array();
    $order_item_detail_data = array();
    $order_address_detail_data = array();
    $order_status = AttributeController::get_order_status();
    $store_front = array(
      1=>'US',
      2=>'HK',
      3=>'EU',
      4=>'UK',
      5=>'CN',
    );
    foreach ($all_order_data as $each_data) {
      $order_detail_data[]=array(
        $each_data->id,
        $each_data->order_type,
        $each_data->increment_id,
        date('m/d/Y H:i:s', $each_data->created_at),
        $each_data->customer_email,
        $each_data->customer_lastname,
        $each_data->customer_firstname,
        $each_data->order_currency_code,
        $each_data->grand_total,
        $each_data->subtotal,
        $each_data->discount_amount,
        $each_data->shipping_amount,
        $store_front[$each_data->store_id],
        $each_data->payment,
        $each_data->total_qty_ordered,
        $order_status[$each_data->status],
        $each_data->tracking_number,
      );
      $order_item_data = OrderController::get_order_items_by_id($each_data->id);
      foreach ($order_item_data as $each_order_item) {
        $order_item_detail_data[]=array(
          $each_order_item->order_id,
          $each_order_item->name,
          $each_order_item->sku,
          $each_order_item->price,
          $each_order_item->original_price,
          $each_order_item->qty,
        );
      }
      $order_address_data = OrderController::get_order_shipping_address_by_id($each_data->id);
      $order_address_detail_data[]=array(
        $order_address_data['order_id'],
        $order_address_data['address_type'],
        $order_address_data['email'],
        $order_address_data['prefix'],
        $order_address_data['firstname'],
        $order_address_data['lastname'],
        $order_address_data['telephone'],
        $order_address_data['country_id'],
        $order_address_data['postcode'],
        $order_address_data['city'],
        $order_address_data['street_1'],
        $order_address_data['street_2'],
        $order_address_data['region'],
      );

    }

    $spreadsheet = new Spreadsheet();
    // Set document properties
    // $spreadsheet->getProperties()->setCreator('PhpOffice')
    //         ->setLastModifiedBy('PhpOffice')
    //         ->setTitle('Office 2007 XLSX Test Document')
    //         ->setSubject('Office 2007 XLSX Test Document')
    //         ->setDescription('PhpOffice')
    //         ->setKeywords('PhpOffice')
    //         ->setCategory('PhpOffice');
    // Add some data
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('A1', 'ID');
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('B1', 'order_type');
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('C1', 'increment_id');
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('D1', 'created_at');
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('E1', 'customer_email');
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('F1', 'customer_lastname');
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('G1', 'customer_firstname');
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('H1', 'order_currency_code');
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('I1', 'grand_total');
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('J1', 'subtotal');
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('K1', 'discount_amount');
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('L1', 'shipping_amount');
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('M1', 'store_front');
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('N1', 'payment');
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('O1', 'total_qty_ordered');
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('P1', 'status');
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('Q1', 'tracking_number');
    $spreadsheet->getActiveSheet()->setTitle('Oroder Detail');
    $spreadsheet->getActiveSheet()->fromArray($order_detail_data,NULL,'A2');
    $spreadsheet->createSheet();
    $spreadsheet->setActiveSheetIndex(1)->setCellValue('A1', 'ORDER ID');
    $spreadsheet->setActiveSheetIndex(1)->setCellValue('B1', 'name');
    $spreadsheet->setActiveSheetIndex(1)->setCellValue('C1', 'sku');
    $spreadsheet->setActiveSheetIndex(1)->setCellValue('D1', 'price');
    $spreadsheet->setActiveSheetIndex(1)->setCellValue('E1', 'original_price');
    $spreadsheet->setActiveSheetIndex(1)->setCellValue('F1', 'qty');
    $spreadsheet->getActiveSheet()->setTitle('Oroder Item Detail');
    $spreadsheet->getActiveSheet()->fromArray($order_item_detail_data,NULL,'A2');
    $spreadsheet->createSheet();
    $spreadsheet->setActiveSheetIndex(2)->setCellValue('A1', 'ORDER ID');
    $spreadsheet->setActiveSheetIndex(2)->setCellValue('B1', 'address_type');
    $spreadsheet->setActiveSheetIndex(2)->setCellValue('C1', 'email');
    $spreadsheet->setActiveSheetIndex(2)->setCellValue('D1', 'prefix');
    $spreadsheet->setActiveSheetIndex(2)->setCellValue('E1', 'firstname');
    $spreadsheet->setActiveSheetIndex(2)->setCellValue('F1', 'lastname');
    $spreadsheet->setActiveSheetIndex(2)->setCellValue('G1', 'telephone');
    $spreadsheet->setActiveSheetIndex(2)->setCellValue('H1', 'country_id');
    $spreadsheet->setActiveSheetIndex(2)->setCellValue('I1', 'postcode');
    $spreadsheet->setActiveSheetIndex(2)->setCellValue('J1', 'city');
    $spreadsheet->setActiveSheetIndex(2)->setCellValue('K1', 'street_1');
    $spreadsheet->setActiveSheetIndex(2)->setCellValue('L1', 'street_2');
    $spreadsheet->setActiveSheetIndex(2)->setCellValue('M1', 'region');
    $spreadsheet->getActiveSheet()->setTitle('Oroder Address Detail');
    $spreadsheet->getActiveSheet()->fromArray($order_address_detail_data,NULL,'A2');
    // Set active sheet index to the first sheet, so Excel opens this as the first sheet
    $spreadsheet->setActiveSheetIndex(0);
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $relavent_path = 'public://miasuki_file/miasuki_erp_orders_'.time().'.xlsx';
    $writer->save(\Drupal::service('file_system')->realpath($relavent_path));
    $download_url = '<a target="_black" href="'.file_create_url($relavent_path).'">Order Data</a>';
    drupal_set_message(t("You can download $download_url"));
    $query_parameter = array();
    $url = Url::fromRoute('order.list_order_form', $query_parameter);
    return $form_state->setRedirectUrl($url);
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
    $query_parameter = array();
    // print_r($form_state->getValue('filters'));exit;
    if (!empty($form_state->getValue('increment_id'))) {
      $query_parameter['increment_id']=$form_state->getValue('increment_id');
    }
    if (!empty($form_state->getValue('status'))&&$form_state->getValue('status')!=0) {
      $query_parameter['status']=$form_state->getValue('status');
    }
    $url = Url::fromRoute('order.list_order_form', $query_parameter);
    return $form_state->setRedirectUrl($url);
  }
  public function SyncOrder(array &$form, FormStateInterface $form_state) {
    // ApiOrderController::testsyncorder();
    ApiOrderController::syncorder();
  }

}
