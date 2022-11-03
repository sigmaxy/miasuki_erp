<?php

namespace Drupal\product\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class ProductAjaxCommand.
 */
class ProductAjaxCommand implements CommandInterface {
  protected $command;
  protected $status;
  protected $result;
  protected $message;
  // Constructs a ReadMessageCommand object.
  public function __construct($command,$status,$result,$message) {
    $this->command = $command;
    $this->status = $status;
    $this->result = $result;
    $this->message = $message;
  }
  /**
   * Render custom ajax command.
   *
   * @return ajax
   *   Command function.
   */
  public function render() {
    return [
      'command' => $this->command,
      'status' => $this->status,
      'result' => $this->result,
      'message' => $this->message,
    ];
  }

}
