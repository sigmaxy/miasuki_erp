<?php

namespace Drupal\api\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class AuthenticateController.
 */
class AuthenticateController extends ControllerBase {

  /**
   * Login.
   *
   * @return string
   *   Return Hello string.
   */
  public $api_url = 'http://hkmiasuki/index.php/rest/V1/';
  public function get_api_url($site_code){
    $api_url = self::get_api_server_url($site_code).'rest/V1/';
    return $api_url;
  }
  public function get_api_server_url($site_code){
    if ($_SERVER['ENVIRONMENT']=='production') {
      switch ($site_code) {
        case 'hk':
          $server_url = 'https://hk.miasuki.com/';
          break;
        case 'eu':
          $server_url = 'https://eu.miasuki.com/';
          break;
        case 'uk':
          $server_url = 'https://uk.miasuki.com/';
          break;
        case 'cn':
          $server_url = 'https://cn.miasuki.com/';
          break;
        default:
          $server_url = 'https://www.miasuki.com/';
          break;
      }
    }else if ($_SERVER['ENVIRONMENT']=='staging') {
      switch ($site_code) {
        case 'hk':
          $server_url = 'http://shk.miasuki.com/';
          break;
        case 'eu':
          $server_url = 'http://seu.miasuki.com/';
          break;
        case 'uk':
          $server_url = 'http://suk.miasuki.com/';
          break;
        case 'cn':
          $server_url = 'http://scn.miasuki.com/';
          break;
        default:
          $server_url = 'http://sus.miasuki.com/';
          break;
      }
    }else{
      switch ($site_code) {
        case 'hk':
          $server_url = 'http://hkmiasuki/';
          break;
        case 'eu':
          $server_url = 'http://eumiasuki/';
          break;
        case 'uk':
          $server_url = 'http://ukmiasuki/';
          break;
        case 'cn':
          $server_url = 'http://cnmiasuki/';
          break;
        default:
          $server_url = 'http://usmiasuki/';
          break;
      }
    }
    return $server_url;
  }
  public function test_api() {
    // $result = self::api_call('customers/3');
    $result = self::api_call('orders?searchCriteria[filter_groups][0][filters][0][field]=entity_id&searchCriteria[filter_groups][0][filters][0][value]=175&searchCriteria[filter_groups][0][filters][0][condition_type]=gt');
    // $result = self::api_call('orders?searchCriteria=');
    print_r($result);
    foreach ($result as $key => $value) {
      echo $key;
    }
    exit;

  }
  public function api_call($relative_path,$postmethod='GET',$postfield=NULL,$site_code='us'){
    $userData = '{"username": "admin","password": "P@$$w0rd"}';
    $ch = curl_init(self::get_api_url($site_code)."integration/admin/token");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $userData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Content-Lenght: " . strlen($userData)));
    $token = curl_exec($ch);
    $url = self::get_api_url($site_code).$relative_path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $postmethod);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($postfield!=NULL) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postfield));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . json_decode($token)));
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result,1);
  }

}
