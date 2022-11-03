<?php

namespace Drupal\warehouse\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class AddWarehouseController.
 */
class AddWarehouseController extends ControllerBase {

  /**
   * Add_warehouse.
   *
   * @return string
   *   Return Hello string.
   */
  public function add_warehouse() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: add_warehouse')
    ];
  }

}
