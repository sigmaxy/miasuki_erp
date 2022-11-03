<?php

namespace Drupal\warehouse\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

/**
 * Class AddWarehouseController.
 */
class WarehouseController extends ControllerBase {

  /**
   * Add_warehouse.
   *
   * @return string
   *   Return Hello string.
   */
  public function list_warehouse() {
    $header_table = array(
      'id'=>    t('ID'),
      'sort'=>    t('Sort Order'),
      'name' => t('Warehouse Name'),
      'opt' =>  t('operations'),
      'opt1' => t('operations'),
    );
    $query = \Drupal::database()->select('miasuki_warehouse', 'mw');
    $query->fields('mw');
    $query->orderBy('sort_order');
    $results = $query->execute()->fetchAll();
    $rows=array();
    foreach($results as $data){
        $delete = Url::fromUserInput('/warehouse/form/delete/'.$data->id);
        $edit   = Url::fromUserInput('/warehouse/form/edit?id='.$data->id);
      //print the data from table
        $rows[] = array(
          'id' =>$data->id,
          'sort' =>$data->sort_order,
          'warehouse_name' => $data->warehouse_name,
           \Drupal::l('Delete', $delete),
           \Drupal::l('Edit', $edit),
        );
    }
    //display data in site
    $form['table'] = [
      '#type' => 'table',
      '#header' => $header_table,
      '#rows' => $rows,
      '#empty' => t('No users found'),
    ];
    return $form;
  }
  public function get_all_warehouses(){
    $query = \Drupal::database()->select('miasuki_warehouse', 'mw');
    $query->fields('mw');
    $query->orderBy('sort_order');
    $results = $query->execute()->fetchAll();
    $warehouse_arr=array();
    foreach($results as $data){
        $warehouse_arr[$data->id] = $data->warehouse_name;
    }
    return $warehouse_arr;
  }
  public function get_address_by_warehouse_id($warehouse_id){
    $warehouse_address = array(
      4 => 'Miasuki Asia LTD.'."\n\n".'5/F Sea Bird House'."\n".'22-28 Wyndham Street'."\n".'Central'."\n".'Hong Kong',
      5 => 'Levade Shop'."\n\n".'Jockey Club Shatin Club House'."\n".'Ground Floor'."\n".'Shatin Racecourse'."\n".'Hong Kong'."\n\n".'2966 6534',
      6 => 'Levade Shop'."\n\n".'Jockey Club Beas River Old Club House'."\n".'Kam Tsin Road, Kwu Tung'."\n".'Hong Kong'."\n\n".'2966 1981',
      7 => 'Bits and Boots Saddlery (H.K.) Company'."\n\n".'Room 1721B'."\n".'17/F Star House'."\n".'Tsim Sha Tsui'."\n".'Kowloon'."\n\n".'2735 0123',
    );
    return $warehouse_address[$warehouse_id];
  }


}
