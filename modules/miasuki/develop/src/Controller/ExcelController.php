<?php

namespace Drupal\develop\Controller;

use Drupal\Core\Controller\ControllerBase;

class ExcelController extends ControllerBase {
  public function formate_sheet_data($sheetData) {
    $formatesheetData = array();
    foreach ($sheetData as $key => $row_data) {
      if ($key==0) {
        $sheetData_header = $row_data;
      }else{
        foreach ($row_data as $cell_index => $cell_value) {
          $formatesheetData[$key][$sheetData_header[$cell_index]]=$cell_value;
        }
      }
    }
    return $formatesheetData;
  }

}
