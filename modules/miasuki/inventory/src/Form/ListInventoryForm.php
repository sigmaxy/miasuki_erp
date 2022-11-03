<?php

namespace Drupal\inventory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\warehouse\Controller\WarehouseController;
use Drupal\Core\Database\Database;
use Drupal\inventory\Controller\InventoryController;
use Drupal\Core\Url;
use Drupal\attribute\Controller\AttributeController;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
/**
 * Class ListInventoryForm.
 */
class ListInventoryForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'list_inventory_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $header_table = array(
      'control'=>array(
        'data'=>t('Control'),
        'class'=>array('sorting_disabled'),
        'data-orderable'=>array('false'),
      ),
      'magento_sku'=>array(
        'data'=>t('Magento SKU'),
        'class'=>array('table_header_magento_sku'),
      ),
    );
    
    // $header_table = array(
    //     'data'=>t('Magento SKU'),
    //     'title'=>array('table_header_magento_sku'),
    //   );
    $col_count = 3;
    $warehouse_arr = WarehouseController::get_all_warehouses();
    foreach ($warehouse_arr as $warehouse_id=>$each_warehouse) {
      $header_table[$warehouse_id] = array(
        'data'=>t($each_warehouse),
        'class'=>array('table_header_warehouse'),
        'title'=>t($each_warehouse),
      );
      $col_count++;
    }
    $header_table['total'] = t('Total');
    $header_table['opt'] = t('Operation');
    $param = \Drupal::request()->query->all();
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_simple_product', 'msp');
    $query->fields('msp');
    if (!empty($param['magento_sku'])) {
      $query->condition('magento_sku', "%" . $param['magento_sku'] . "%", 'LIKE');
    }
    // $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(100);
    $results = $query->execute()->fetchAll();
    $rows=array();
    foreach($results as $data){
        $edit   = Url::fromUserInput('/inventory/form/inventory/'.$data->id);
      //print the data from table
        $row_data['control'] = array(
          'data'=>null,
          'class'=>array('details-control','inventory_details_control'),
          'title'=>$data->magento_sku,
        );
        $row_data['magento_sku'] = array(
          'data'=>$data->magento_sku,
          'title'=>array($data->magento_sku),
        );
        $inventory_data = InventoryController::get_inventory_by_productid($data->id);
        $total = 0;
        foreach ($warehouse_arr as $warehouse_id=>$each_inventory) {
          $row_data[$warehouse_id] = isset($inventory_data[$warehouse_id])?$inventory_data[$warehouse_id]:0;
          $total = $total + intval($inventory_data[$warehouse_id]);
        }
        $row_data['total'] = $total;
        $row_data['opt'] = \Drupal::l('Edit', $edit);
        $rows[] = $row_data;
    }
    $form['filters'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Function'),
      '#open'  => true,
    ];

    // $form['filters']['magento_sku'] = [
    //   '#title'         => 'SKU',
    //   '#type'          => 'textfield',
    //   '#default_value' => !empty($param['magento_sku'])?$param['magento_sku']:'',
    //   '#weight' => '1',
    // ];
    // $form['filters']['submit'] = [
    //   '#type' => 'submit',
    //   '#value' => $this->t('Filter'),
    //   '#weight' => '10',
    // ];
    $form['filters']['export'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export'),
      '#attributes' => [   
        'class' => ['next_button'],
      ],
      '#submit' => array('::ExportInventory'),
      '#weight' => '11',
    ];
    //display data in site
    $form['table'] = [
      '#type' => 'table',
      '#header' => $header_table,
      '#rows' => $rows,
      '#empty' => t('No Inventory found'),
      '#attributes' => [   
        'class' => ['inventory_table'],
        'data-col' => [$col_count],
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

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query_parameter = array();
    // print_r($form_state->getValue('filters'));exit;
    if (!empty($form_state->getValue('magento_sku'))) {
      $query_parameter['magento_sku']=$form_state->getValue('magento_sku');
    }
    $url = Url::fromRoute('inventory.list_inventory_form', $query_parameter);
    return $form_state->setRedirectUrl($url);

  }
  public function ExportInventory(array &$form, FormStateInterface $form_state) {
    $filename = 'miasuki_erp_'.time().'.csv';
    $filepath = 'public://miasuki_file/';
    $fp = fopen($filepath.$filename, 'w');
    $warehouse_opt = WarehouseController::get_all_warehouses();
    $csv_header = array('Parent SKU','Magento SKU','Color','Size','Length');
    foreach ($warehouse_opt as $warehouse_name) {
      $csv_header[] = $warehouse_name;
    }
    fputcsv($fp, $csv_header);
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_simple_product', 'msp');
    $query->fields('msp');
    if (!empty($form_state->getValue('magento_sku'))) {
      $query->condition('magento_sku', "%" . $form_state->getValue('magento_sku') . "%", 'LIKE');
    }
    $results = $query->execute()->fetchAll();
    $rows=array();
    
    foreach($results as $data){
      $row_data['Parent SKU'] = $data->parent_sku;
      $row_data['Magento SKU'] = $data->magento_sku;
      $row_data['Color'] = AttributeController::get_color_by_id($data->color_id)['color'];
      $row_data['Size'] = AttributeController::get_size_by_id($data->size_id)['size'];
      $row_data['Length'] = AttributeController::get_length_by_id($data->length_id)['length'];
      foreach ($warehouse_opt as $warehouse_id => $warehouse_name) {
        $row_data[$warehouse_name] = InventoryController::get_inventory_by_productid_warehouseid($data->id,$warehouse_id);
      }
      $rows[] = $row_data;
      fputcsv($fp, $row_data);
      //   $edit   = Url::fromUserInput('/inventory/form/inventory/'.$data->id);
      // //print the data from table
      //   $row_data = array(
      //     'magento_sku' =>$data->magento_sku,
      //   );
      //   $inventory_data = InventoryController::get_inventory_by_productid($data->id);
      //   $total = 0;
      //   foreach ($warehouse_arr as $warehouse_id=>$each_inventory) {
      //     $row_data[$warehouse_id] = isset($inventory_data[$warehouse_id])?$inventory_data[$warehouse_id]:0;
      //     $total = $total + intval($inventory_data[$warehouse_id]);
      //   }
      //   $row_data['total'] = $total;
      //   $row_data['opt'] = \Drupal::l('Edit', $edit);
      //   $rows[] = $row_data;

    }
    // file_unmanaged_save_data($rows , 'public://miasuki_file/display.csv', FILE_EXISTS_REPLACE);
    fclose($fp);
    $download_url = '<a target="_black" href="'.file_create_url($filepath.$filename).'">ERP Inventory</a>';
    drupal_set_message(t("You can download $download_url"));
    $query_parameter = array();
    if (!empty($form_state->getValue('magento_sku'))) {
      $query_parameter['magento_sku']=$form_state->getValue('magento_sku');
    }
    $url = Url::fromRoute('inventory.list_inventory_form', $query_parameter);
    return $form_state->setRedirectUrl($url);
  }

  private function insert_sample_inventory(){
    $warehouse_arr = WarehouseController::get_all_warehouses();
    //insert sample inventory data()
    $query = \Drupal::database()->select('miasuki_simple_product', 'msp');
    $query->fields('msp');
    $all_simple_products = $query->execute()->fetchAll();
    $connection = Database::getConnection();
    foreach ($all_simple_products as $each_simple_product) {
      foreach ($warehouse_arr as $warehouse_id=>$each_warehouse) {
        $record = array();
        $db_fields = array(
          'product_id' => $each_simple_product->id,
          'warehouse_id' => $warehouse_id,
          'qty' => rand(0,50),
        );
        $query_insert = $connection->insert('miasuki_inventory')
          ->fields($db_fields)
          ->execute();
      }
    }
    exit;
  }

}
