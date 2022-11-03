<?php

namespace Drupal\report\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\attribute\Controller\AttributeController;
use Drupal\report\Controller\ReportController;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\order\Controller\OrderController;
use Drupal\product\Controller\ProductController;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

/**
 * Class CategoryReportForm.
 */
class CategoryReportForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'category_report_form';
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
      $default_status = 6;
    }else{
      $default_status = $param['status'];
    }
    // $month_arr = ReportController::split_month_toarray($default_date_from,$default_date_to);
    $query = $connection->select('miasuki_order', 'mo');
    $query->fields('mo');
    $query->condition('created_at', strtotime($default_date_from.' 00:00:00'), '>');
    $query->condition('created_at', strtotime($default_date_to.' 23:59:59'), '<');
    $query->condition('order_currency_code', $default_currency);
    $query->condition('status', $default_status);
    if (!empty($param['order_type'])) {
      $query->condition('order_type', $param['order_type']);
    }
    $results = $query->execute()->fetchAll();
    $category_data = array();
    $category_list = ReportController::report_category_list();
    foreach ($results as $each_order) {
      $order_items = OrderController::get_order_items_by_id($each_order->id);
      foreach ($order_items as $each_item) {
        
        $simple_product = ProductController::get_simple_product_by_sku($each_item->sku);
        $config_product = ProductController::get_config_product_by_sku($simple_product['parent_sku']);
        $db_cat_arr = explode(',', $config_product['category']);
        foreach ($db_cat_arr as $each_db_cat_index) {
          if (array_key_exists($each_db_cat_index, $category_list)) { 
              $category_data[$category_list[$each_db_cat_index]] = $category_data[$category_list[$each_db_cat_index]] + $each_item->price;
          }
        }
      }
    }
    $label_arr = array();
    $data_arr = array();
    $color_arr = array();
    $color_list = ReportController::report_color_list();
    foreach ($category_data as $each_cat=>$each_sum) {
      $label_arr[] = $each_cat;
      $data_arr[] = $each_sum;
      $color_arr[] = array_shift($color_list);
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
    $form['chart']['#markup'] = new FormattableMarkup('<canvas id="category_report_chart" width="400" height="400"></canvas>', []);
    $chart_config['title'] = 'Order Report';
    $chart_config['labels'] = $label_arr;
    $chart_config['data'] = $data_arr;
    $chart_config['color'] = $color_arr;
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
    $url = Url::fromRoute('report.category_report_form', $query_parameter);
    return $form_state->setRedirectUrl($url);

  }

}
