<?php

namespace Drupal\report\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\attribute\Controller\AttributeController;
use Drupal\report\Controller\ReportController;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

/**
 * Class OrderReportForm.
 */
class OrderReportForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'order_report_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $param = \Drupal::request()->query->all();
    $connection = Database::getConnection();
    if (empty($param['date_from'])) {
      $default_date_from = '2018-01-01';
    }else{
      $default_date_from = $param['date_from'];
    }
    if (empty($param['date_to'])) {
      $default_date_to = date("Y-m-d");
    }else{
      $default_date_to = $param['date_to'];
    }
    if (empty($param['currency'])) {
      $default_currency = 'USD';
    }else{
      $default_currency = $param['currency'];
    }
    if (empty($param['status'])) {
      $default_status = 9;
    }else{
      $default_status = $param['status'];
    }
    $month_arr = ReportController::split_month_toarray($default_date_from,$default_date_to);
    $query = $connection->select('miasuki_order', 'mo');
    $query->fields('mo');
    $query->condition('created_at', strtotime($default_date_from.' 00:00:00'), '>');
    $query->condition('created_at', strtotime($default_date_to.' 23:59:59'), '<');
    $query->condition('order_currency_code', $default_currency);
    if ($default_status==9) {
      $or = db_or();
      $or->condition('status', 3);
      $or->condition('status', 4);
      $or->condition('status', 6);
      $query->condition($or);
    }else{
      $query->condition('status', $default_status);
    }
    if (!empty($param['order_type'])) {
      $query->condition('order_type', $param['order_type']);
    }
    $results = $query->execute()->fetchAll();
    $order_sum_data = array();
    foreach ($results as $each_order) {
      $order_sum_data_index = date('y/m',$each_order->created_at);
      $order_sum_data[$each_order->order_type][$order_sum_data_index] = $order_sum_data[$each_order->order_type][$order_sum_data_index]+$each_order->grand_total;
    }
    $data_arr = array();
    
    foreach ($order_sum_data as $order_type_key => $each_month_data) {
      foreach ($month_arr as $each_month_key) {
        if (array_key_exists($each_month_key, $each_month_data)) { 
            $data_arr[$order_type_key][] = $each_month_data[$each_month_key];
        }else{ 
            $data_arr[$order_type_key][] = 0;
        } 
      }
    }
    $order_type_options = array_merge(array(0=>'All'),AttributeController::get_order_type());
    $currency_options = AttributeController::get_report_currency_options();
    $status_options = AttributeController::get_order_status();
    $form['filters'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Filter'),
      '#open'  => true,
    ];
    $form['filters']['date_from'] = array(
      '#type' => 'date',
      '#title' => 'Enter From Date',
      '#required' => TRUE,
      '#default_value' => $default_date_from,
    );
    $form['filters']['date_to'] = array(
      '#type' => 'date',
      '#title' => 'Enter To Date',
      '#required' => TRUE,
      '#default_value' =>$default_date_to,
    );
    $form['filters']['currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Currency'),
      '#options' => $currency_options,
      '#default_value' => $default_currency,
      '#weight' => '2',
    ];
    $form['filters']['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Order Status'),
      '#options' => $status_options,
      '#default_value' => $default_status,
      '#weight' => '2',
    ];
    $form['filters']['order_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Order Type'),
      '#options' => $order_type_options,
      '#default_value' => !empty($param['order_type'])?$param['order_type']:'0',
      '#weight' => '2',
    ];
    $form['filters']['filter'] = [
      '#type' => 'submit',
      '#value' => $this->t('filter'),
      '#attributes' => [   
        'class' => ['next_button'],
      ],
      // '#submit' => array('::Filter'),
      '#weight' => '11',
    ];
    $form['chart'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Chart'),
      '#open'  => true,
    ];
    // $form['chart']['#markup'] = '<canvas id="myChart" width="400" height="400"></canvas>';
    $form['chart']['#markup'] = new FormattableMarkup('<canvas id="order_report_chart" width="400" height="400"></canvas>', []);
    $order_type_color_mapping = array(
      'magento'=>'red',
      'B2B'=>'blue',
      'offline'=>'grey',
    );
    foreach ($data_arr as $order_type_index => $each_data_set) {
      $data_set = array();
      $data_set['label'] = $order_type_index;
      $data_set['data'] = $each_data_set;
      $data_set['borderWidth'] = 1;
      $data_set['backgroundColor'] = $order_type_color_mapping[$order_type_index];
      $data_set['stack'] = 'Stack 0';
      $chart_config['datasets'][] = $data_set;
    }
    // print_r($chart_config['datasets']);exit;
    // $chart_config['datasets'][0]['label'] = 'Order Report Currency('.$default_currency.')';
    // $chart_config['datasets'][0]['data'] = $data_arr;
    // $chart_config['datasets'][0]['borderWidth'] = 1;
    // $chart_config['datasets'][0]['backgroundColor'] = 'red';
    $chart_config['labels'] = $month_arr;
    // $chart_config['data'] = $data_arr;
    $form['#attached']['drupalSettings']['chart_config'] = $chart_config;
    $form['#attached']['library'][] = 'report/report';
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $field=$form_state->getValues();
    $date_from_arr = explode('-', $field['date_from']); 
    $date_to_arr = explode('-', $field['date_to']);
    if ((intval($date_to_arr[0])-intval($date_from_arr[0]))>1) {
        $form_state->setErrorByName('date_from', 'Max 2 years Range');
        $form_state->setErrorByName('date_to', 'Max 2 years Range');
    }
    if (intval($date_to_arr[0])<intval($date_from_arr[0])) {
        $form_state->setErrorByName('date_from', 'Wrong Date Range');
        $form_state->setErrorByName('date_to', 'Wrong Date Range');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query_parameter = array();
    // print_r($form_state->getValue('filters'));exit;
    if (!empty($form_state->getValue('date_from'))) {
      $query_parameter['date_from']=$form_state->getValue('date_from');
    }
    if (!empty($form_state->getValue('date_to'))) {
      $query_parameter['date_to']=$form_state->getValue('date_to');
    }
    if (!empty($form_state->getValue('currency'))) {
      $query_parameter['currency']=$form_state->getValue('currency');
    }
    if (!empty($form_state->getValue('status'))) {
      $query_parameter['status']=$form_state->getValue('status');
    }
    if (!empty($form_state->getValue('order_type'))) {
      $query_parameter['order_type']=$form_state->getValue('order_type');
    }
    $url = Url::fromRoute('report.order_report_form', $query_parameter);
    return $form_state->setRedirectUrl($url);
  }

}
