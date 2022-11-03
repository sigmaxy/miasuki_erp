<?php

namespace Drupal\attribute\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

/**
 * Class ColorForm.
 */
class ColorForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'color_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['magento_id'] = [
      '#type' => 'number',
      '#title' => $this->t('Magento Attribute ID'),
      '#weight' => '1',
    ];
    $form['color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Color Name'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '2',
    ];
    $form['nav_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nav Color Name'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '3',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => '4',
    ];

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
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }

  }
  public function get_colorname_to_id(){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_attribute_color', 'mac');
    $query->fields('mac');
    $results = $query->execute()->fetchAll();
    $data_arr = array();
    foreach($results as $data){
      $data_arr[$data->color] = $data->id;
    }
    return $data_arr;
  }
  public function get_sizename_to_id(){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_attribute_size', 'mas');
    $query->fields('mas');
    $results = $query->execute()->fetchAll();
    $data_arr = array();
    foreach($results as $data){
      $data_arr[$data->size] = $data->id;
    }
    return $data_arr;
  }
  public function get_lengthname_to_id(){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_attribute_length', 'mal');
    $query->fields('mal');
    $results = $query->execute()->fetchAll();
    $data_arr = array();
    foreach($results as $data){
      $data_arr[$data->length] = $data->id;
    }
    return $data_arr;
  }
  public function get_color_options(){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_attribute_color', 'mac');
    $query->fields('mac');
    $results = $query->execute()->fetchAll();
    $options = array();
    $options[0] = 'None';
    foreach($results as $data){
      $options[$data->id] = $data->magento_id.' | '.$data->color.' | '.$data->nav_color;
    }
    return $options;
  }
  public function get_size_options(){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_attribute_size', 'mas');
    $query->fields('mas');
    $results = $query->execute()->fetchAll();
    $options = array();
    $options[0] = 'None';
    foreach($results as $data){
      $options[$data->id] = $data->magento_id.' | '.$data->size.' | '.$data->nav_size;
    }
    return $options;
  }
  public function get_length_options(){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_attribute_length', 'mal');
    $query->fields('mal');
    $results = $query->execute()->fetchAll();
    $options = array();
    $options[0] = 'None';
    foreach($results as $data){
      $options[$data->id] = $data->magento_id.' | '.$data->length.' | '.$data->nav_length;
    }
    return $options;
  }
  public function get_color_options_mapping(){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_attribute_color', 'mac');
    $query->fields('mac');
    $results = $query->execute()->fetchAll();
    $options = array();
    $options[0] = 'None';
    foreach($results as $data){
      $options[$data->id] = $data->color;
    }
    return $options;
  }
  public function get_size_options_mapping(){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_attribute_size', 'mas');
    $query->fields('mas');
    $results = $query->execute()->fetchAll();
    $options = array();
    $options[0] = 'None';
    foreach($results as $data){
      $options[$data->id] = $data->size;
    }
    return $options;
  }
  public function get_length_options_mapping(){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_attribute_length', 'mal');
    $query->fields('mal');
    $results = $query->execute()->fetchAll();
    $options = array();
    $options[0] = 'None';
    foreach($results as $data){
      $options[$data->id] = $data->length;
    }
    return $options;
  }

}
