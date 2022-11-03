<?php

namespace Drupal\warehouse\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Render\Element;

/**
 * Class AddWarehouseForm.
 */
class DeleteWarehouseForm extends ConfirmFormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'delete_warehouse_form';
  }
  public $cid;
  public function getQuestion() { 
    return t('Do you want to delete %cid?', array('%cid' => $this->cid));
  }
  public function getCancelUrl() {
    return new Url('warehouse.warehouse_controller_list_warehouse');
  }
  public function getDescription() {
    return t('Only do this if you are sure!');
  }
  public function getConfirmText() {
    return t('Delete it!');
  }
  public function getCancelText() {
    return t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cid = NULL) {
     $this->id = $cid;
    return parent::buildForm($form, $form_state);
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
       $query = \Drupal::database();
       $query->delete('miasuki_warehouse')
                   ->condition('id',$this->id)
                  ->execute();
             drupal_set_message("succesfully deleted");
            $form_state->setRedirect('warehouse.warehouse_controller_list_warehouse');
  }

}
