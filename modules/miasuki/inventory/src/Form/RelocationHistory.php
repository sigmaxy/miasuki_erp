<?php

namespace Drupal\inventory\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\warehouse\Controller\WarehouseController;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Drupal\Core\Url;

/**
 * Class RelocationHistory.
 */
class RelocationHistory extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'relocation_history';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['relocation'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Relocation History'),
      '#open'  => true,
    ];
    $header_table = array(
      'relocation_id'=>t('Relocation ID'),
      'type'=>t('Type'),
      'from_warehouse'=>t('From Warehouse'),
      'to_warehouse'=>t('To Warehouse'),
      'updated_by'=>t('Operator'),
      'updated_time'=>t('Update Time'),
      'opts'=>t('Opts'),
    );
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_relocation_history', 'mrh');
    $query->fields('mrh');
    $results = $query->execute()->fetchAll();
    $rows=array();
    $warehouse_arr = WarehouseController::get_all_warehouses();
    $js_data = array();
    foreach($results as $data){
        // $edit   = Url::fromUserInput('/inventory/form/relocation/'.$data->id);
      //print the data from table
        $row_data['relocation_id'] = array(
          'data'=>null,
          'class'=>array('relocation_detail_control','details-control '),
          'title'=>$data->id,
        );
        $row_data['type'] = $data->type;
        $row_data['from_warehouse'] = $warehouse_arr[$data->from_warehouse];
        $row_data['to_warehouse'] = $warehouse_arr[$data->to_warehouse];
        $account = \Drupal\user\Entity\User::load($data->updated_by);
        $row_data['updated_by'] = $account->getEmail();
        $row_data['updated_time'] = date("Y-m-d H:i:s",$data->updated_time);
        // $row_data['opts'] = 'Print';
        $print_url = Url::fromUserInput('/inventory/deliverynote/'.$data->id);
        $row_data['opt'] = \Drupal::l('Print', $print_url);
        $rows[] = $row_data;
        $details_arr = json_decode($data->details,1);
        $js_data[$data->id] = json_decode($data->details,1);
        $js_remark_data[$data->id] = $data->remark;
        
        // $details_data = '';
        // foreach ($details_arr as $each_sub_data) {
        //   $details_data = $details_data.'<tr style="display:none"><td>'.$each_sub_data['magento_sku'].'</td><td>'.$each_sub_data['id'].'</td><td>'.$each_sub_data['qty'].'</td></tr>';
        // }

        // $details_data_wrapper = '<table><thead><tr><th>SKU</th><th>ERP ID</th><th>QTY</th></tr></thead><tbody>'.$details_data.'</tbody></table>';
        // $sub_data = array();
        // $sub_data['details'] = array(
        //   'colspan' => 6,
        //   'data' => array(
        //     // '#type'=>'hidden',
        //     '#markup'=>$details_data_wrapper,
        //     '#attributes'=>array('hidden'=>true),
        //   ),
        //   'style' => 'display:none',
        // );
        // $rows[] = $sub_data;
    }
    $form['relocation']['table'] = [
      '#type' => 'table',
      '#header' => $header_table,
      '#rows' => $rows,
      '#empty' => t('No Inventory found'),
      '#attributes' => [   
        'class' => ['relocation_table'],
      ],
    ];
    $form['#attached']['library'][] = 'inventory/inventory';
    $form['#attached']['library'][] = 'product/product';
    $form['#attached']['drupalSettings']['relocation_history_data'] = $js_data;
    $form['#attached']['drupalSettings']['relocation_history_remark_data'] = $js_remark_data;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      \Drupal::messenger()->addMessage($key . ': ' . ($key === 'text_format'?$value['value']:$value));
    }
  }



}
