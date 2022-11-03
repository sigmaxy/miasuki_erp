<?php

namespace Drupal\welcome\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class DefaultController.
 */
class DefaultController extends ControllerBase {

  /**
   * Warehouselist.
   *
   * @return string
   *   Return Hello string.
   */
  public function warehouselist() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: warehouselist')
    ];
  }

}
