<?php

namespace Drupal\report\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class ReportController.
 */
class ReportController extends ControllerBase {

  /**
   * Report.
   *
   * @return string
   *   Return Hello string.
   */
  public function report() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: report')
    ];
  }
  public function split_month_toarray($start_date,$end_date) {
    $month_arr = array();
    $start_date_arr = explode('-', $start_date); 
    $end_date_arr = explode('-', $end_date);
    if ($start_date_arr[0]==$end_date_arr[0]) {
      //same year
      for ($i=intval($start_date_arr[1]); $i <=intval($end_date_arr[1]) ; $i++) { 
        $month_arr[]=substr($start_date_arr[0], -2).'/'.sprintf("%02d", $i);
      }
    }else if(intval($start_date_arr[0])==intval($end_date_arr[0])-1){
      //next year
      for ($i=$start_date_arr[1]; $i <=12 ; $i++) { 
        $month_arr[]=substr($start_date_arr[0], -2).'/'.sprintf("%02d", $i);
      }
      for ($i=1; $i <=$end_date_arr[1] ; $i++) { 
        $month_arr[]=substr($end_date_arr[0], -2).'/'.sprintf("%02d", $i);
      }
    }else{
      //more years
    }
    return $month_arr;
  }
  public function report_color_list() {
    $color_arr = ['red','orange','yellow','green','cyan','blue','purple','pink','gray','brown','salmon','darkorange','lightyellow','springgreen','darkcyan','skyblue','darkviolet','deeppink','lightgray','sandybrown','darkseagreen','gold','midnightblue','black'];
    return $color_arr;
  }
  public function report_category_list() {
    $category_arr = array(
      8 => 'Coats & Jackets',
      14 => 'Shirts',
      4 => 'Pants',
      9 => 'Bodysuits',
      7 => 'Knitwear',
      6 => 'T-Shirts & Polos',
      11 => 'Helmets',
      10 => 'Accessories',
    );
    return $category_arr;
  }

}
