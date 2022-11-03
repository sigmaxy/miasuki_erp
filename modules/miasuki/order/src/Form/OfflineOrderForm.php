<?php

namespace Drupal\order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\product\Controller\ProductController;
use Drupal\inventory\Controller\InventoryController;
use Drupal\warehouse\Controller\WarehouseController;
use Drupal\attribute\Controller\AttributeController;
use Drupal\order\Controller\OrderController;
/**
 * Class OfflineOrderForm.
 */
class OfflineOrderForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'offline_order_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $param = \Drupal::request()->query->all();
    $connection = Database::getConnection();
    $form['order_detail'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Order Detail'),
      '#open'  => true,
      '#weight' => '1',
    ];
    $form['order_detail']['increment_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Invoice ID'),
      '#required' => true,
      '#weight' => '1',
    ];
    $currenc_opt = AttributeController::get_currency_opt();
    $form['order_detail']['order_currency_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Product Currency'),
      '#options' => $currenc_opt,
      '#default_value' => 'EUR',
      '#weight' => '2',
    ];
    $order_type_opt = OrderController::get_ordertype_options();
    $form['order_detail']['order_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Order Type'),
      '#options' => $order_type_opt,
      '#default_value' => 'offline',
      '#weight' => '3',
    ];
    $payment_opt = OrderController::get_payment_options();
    $form['order_detail']['payment'] = [
      '#type' => 'select',
      '#title' => $this->t('Payment'),
      '#options' => $payment_opt,
      '#default_value' => 'Cash',
      '#weight' => '3',
    ];
    $form['products'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Products'),
      '#open'  => true,
      '#weight' => '2',
    ];
    $form['products']['magento_sku'] = [
      '#title'         => 'Magento SKU',
      '#type'          => 'search',
      '#autocomplete_route_name' => 'product.autocomplete_simple_product_sku',
      '#autocomplete_route_parameters' => array('count' => 10),
      '#weight' => '2',
    ];
    $form['products']['barcode'] = [
      '#title'         => 'Barcode',
      '#type'          => 'search',
      '#weight' => '3',
    ];
    $form['products']['add'] = [
      '#type' => 'button',
      '#value' => $this->t('Add'),
      '#attributes' => [
        'onclick' => 'return false;',
        'id' => 'offline_order_product_add',
      ],
      '#weight' => '4',
    ];

    $form['products_detail'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Product Detail'),
      '#open'  => true,
      '#weight' => '2',
    ];
    $detail_table_header = array(
      'magento_sku'=>'Magento SKU',
      'original_price'=>'Original Price',
      'price'=>'Price',
      'qty'=>'Qty',
      'del'=>'Delete',
    );
    $form['products_detail']['products_table'] = [
      '#type' => 'table',
      '#header' => $detail_table_header,
      '#attributes' => [   
        'class' => ['product_detail_table'],
      ],
      '#weight' => '14',
    ];
    $form['products_detail']['product_count'] = array(
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'product_count',
      ],
      '#default_value' => 0,
    );
    $form['customer_detail'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Customer Detail'),
      '#open'  => true,
      '#weight' => '3',
    ];
    $form['customer_detail']['customer_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#maxlength' => 255,
      '#size' => 64,
      '#required' => true,
      '#weight' => '1',
    ];
    $form['customer_detail']['customer_firstname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '2',
    ];
    $form['customer_detail']['customer_lastname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '3',
    ];
    $form['address'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Address'),
      '#open'  => true,
      '#weight' => '4',
    ];
    $form['address']['firstname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#maxlength' => 255,
      '#size' => 64,
      '#required' => true,
      '#weight' => '1',
    ];
    $form['address']['lastname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '2',
    ];
    $form['address']['telephone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tel'),
      '#maxlength' => 255,
      '#size' => 64,
      '#required' => true,
      '#weight' => '3',
    ];
    $country_opt = AttributeController::get_country_list();
    $form['address']['country_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => $country_opt,
      '#weight' => '4',
    ];
    $form['address']['region'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Province'),
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '5',
    ];
    $form['address']['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '6',
    ];
    $form['address']['street_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Street Line 1'),
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '7',
    ];
    $form['address']['street_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Street Line 2'),
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '8',
    ];
    $form['address']['postcode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zip'),
      '#maxlength' => 255,
      '#size' => 64,
      '#weight' => '9',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => '5',
    ];
    $form['#attached']['library'][] = 'order/order';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $field=$form_state->getValues();
    if (empty($field['products_table'])) {
      $form_state->setErrorByName('products_table', t('No Product'));
    }
    
  }
  public function add_product(array &$form, FormStateInterface $form_state) {
    $query_parameter = array();
    $connection = Database::getConnection();
    // print_r($form_state->getValue('filters'));exit;
    if (!empty($form_state->getValue('magento_sku'))) {
      $query_parameter['magento_sku']=$form_state->getValue('magento_sku');
      $record = ProductController::get_simple_product_by_sku($form_state->getValue('magento_sku'));
      if (empty($record['id'])) {
        drupal_set_message('There is no such product!', $type = 'error');
      }else{
        $form['tmp']['data']['products'][]=$record;
      }
    }else if (!empty($form_state->getValue('barcode'))) {
      $query_parameter['barcode']=$form_state->getValue('barcode');
      $record = ProductController::get_simple_product_by_barcode($form_state->getValue('barcode'));
      if (empty($record['id'])) {
        drupal_set_message('There is no such product!', $type = 'error');
      }else{
        $form['tmp']['data']['products'][]=$record;
      }
    }
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $connection = Database::getConnection();
    $field=$form_state->getValues();
    $currency_to_storeid = array(
      'USD' => '1',
      'HKD' => '2',
      'EUR' => '3',
      'GBP' => '4',
      'CNY' => '5',
    );
    $grand_total = 0;
    $total_qty_ordered = 0;
    
    foreach ($field['products_table'] as $each_product) {
      $grand_total = $grand_total + $each_product['price']*$each_product['qty'];
      $total_qty_ordered = $total_qty_ordered + $each_product['qty'];
    }
    $order_data = array(
      'order_type' => $field['order_type'],
      'increment_id' => $field['increment_id'],
      'created_at' => time(),
      'customer_email' => $field['customer_email'],
      'customer_lastname' => $field['customer_lastname'],
      'customer_firstname' => $field['customer_firstname'],
      'order_currency_code' => $field['order_currency_code'],
      'grand_total' => $grand_total,
      'subtotal' => $grand_total,
      'discount_amount' => 0,
      'shipping_amount' => 0,
      'store_id' => $currency_to_storeid[$field['order_currency_code']],
      'total_qty_ordered' => $total_qty_ordered,
      'payment' => $field['payment'],
      'status' => 3,
    );
    $order_insert_id = $connection->insert('miasuki_order')
          ->fields($order_data)
          ->execute();
    foreach ($field['products_table'] as $each_order_item) {
      $each_order_item_data = array(
        'order_id' => $order_insert_id,
        'sku' => $each_order_item['magento_sku'],
        'name' => $each_order_item['magento_sku'],
        'price' => $each_order_item['price'],
        'original_price' => $each_order_item['original_price'],
        'qty' => $each_order_item['qty'],
      );
      $connection->insert('miasuki_order_item')->fields($each_order_item_data)->execute();
    }
    $each_order_address_data = array(
      'order_id' => $order_insert_id,
      'address_type' => 'shipping',
      'email' => $field['email'],
      'firstname' => $field['firstname'],
      'lastname' => $field['lastname'],
      'telephone' => $field['telephone'],
      'country_id' => $field['country_id'],
      'postcode' => $field['postcode'],
      'city' => $field['city'],
      'street_1' => $field['street_1'],
      'street_2' => $field['street_2'],
      'region' => $field['region'],
    );
    $connection->insert('miasuki_order_address')->fields($each_order_address_data)->execute();
    $new_order_url = Url::fromRoute('order.order_detail_form',['order_id'=>$order_insert_id]);
    drupal_set_message('One Order has been created, <a href="@new_order_url">View Detail</a>',array('@new_order_url'=>$new_order_url->toString()));
    // $url = Url::fromRoute('order.list_order_form');
    return $form_state->setRedirectUrl($new_order_url);
  }

}
