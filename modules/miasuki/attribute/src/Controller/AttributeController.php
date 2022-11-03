<?php

namespace Drupal\attribute\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

/**
 * Class AttributeController.
 */
class AttributeController extends ControllerBase {

  /**
   * Get_color_by_id.
   *
   * @return string
   *   Return Hello string.
   */
  public function get_order_type(){
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_order', 'mo');
    $query->fields('mo', ['order_type']);
    $results = $query->distinct()->execute()->fetchAll();
    $order_types = array();
    foreach($results as $data){
      $order_types[$data->order_type] = $data->order_type;
    }
    return $order_types;
  }
  public function get_order_status(){
    $order_status = array(
      1 => 'holded',
      2 => 'pending_payment',
      3 => 'processing',
      4 => 'shipping',
      5 => 'cancelled',
      6 => 'complete',
      7 => 'refund',
      8 => 'closed',
      9 => 'processing+shipping+completed(for report only)',
    );
    return $order_status;
  }
  public function get_currency_options(){
    $currency_opt = array(
      'us' => 'USD',
      'hk' => 'HKD',
      'eu' => 'EUR',
      'uk' => 'GBP',
      'cn' => 'CNY',
    );
    return $currency_opt;
  }
  public function get_currency_opt(){
    $currency_opt = array(
      'USD' => 'USD',
      'HKD' => 'HKD',
      'EUR' => 'EUR',
      'GBP' => 'GBP',
      'CNY' => 'CNY',
    );
    return $currency_opt;
  }
  public function get_sitecode_bystoreid($storeid){
    $data_arr = array(
      1 => 'us',
      2 => 'hk',
      3 => 'eu',
      4 => 'uk',
      5 => 'cn',
    );
    return $data_arr[$storeid];
  }
  public function get_report_currency_options(){
    $currency_opt = self::get_currency_options();
    $report_currency_options = array();
    foreach ($currency_opt as $value) {
      $report_currency_options[$value]=$value;
    }
    return $report_currency_options;
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
  
  public function get_color_by_id($color_id) {
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_attribute_color', 'mac');
    $query->fields('mac');
    $query->condition('id', $color_id);
    $record = $query->execute()->fetchAssoc();
    return $record;
  }

  public function get_size_by_id($size_id) {
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_attribute_size', 'mas');
    $query->fields('mas');
    $query->condition('id', $size_id);
    $record = $query->execute()->fetchAssoc();
    return $record;
  }

  public function get_length_by_id($length_id) {
    $connection = Database::getConnection();
    $query = $connection->select('miasuki_attribute_length', 'mal');
    $query->fields('mal');
    $query->condition('id', $length_id);
    $record = $query->execute()->fetchAssoc();
    return $record;
  }

  public function get_country_list(){
    $country_list = array(
      'AD'=>'Andorra',
      'AE'=>'United Arab Emirates',
      'AF'=>'Afghanistan',
      'AG'=>'Antigua & Barbuda',
      'AI'=>'Anguilla',
      'AL'=>'Albania',
      'AM'=>'Armenia',
      'AO'=>'Angola',
      'AQ'=>'Antarctica',
      'AR'=>'Argentina',
      'AS'=>'American Samoa',
      'AT'=>'Austria',
      'AU'=>'Australia',
      'AW'=>'Aruba',
      'AX'=>'Åland Islands',
      'AZ'=>'Azerbaijan',
      'BA'=>'Bosnia & Herzegovina',
      'BB'=>'Barbados',
      'BD'=>'Bangladesh',
      'BE'=>'Belgium',
      'BF'=>'Burkina Faso',
      'BG'=>'Bulgaria',
      'BH'=>'Bahrain',
      'BI'=>'Burundi',
      'BJ'=>'Benin',
      'BL'=>'St. Barthélemy',
      'BM'=>'Bermuda',
      'BN'=>'Brunei',
      'BO'=>'Bolivia',
      'BR'=>'Brazil',
      'BS'=>'Bahamas',
      'BT'=>'Bhutan',
      'BV'=>'Bouvet Island',
      'BW'=>'Botswana',
      'BY'=>'Belarus',
      'BZ'=>'Belize',
      'CA'=>'Canada',
      'CC'=>'Cocos (Keeling) Islands',
      'CD'=>'Congo - Kinshasa',
      'CF'=>'Central African Republic',
      'CG'=>'Congo - Brazzaville',
      'CH'=>'Switzerland',
      'CI'=>'Côte d’Ivoire',
      'CK'=>'Cook Islands',
      'CL'=>'Chile',
      'CM'=>'Cameroon',
      'CN'=>'China',
      'CO'=>'Colombia',
      'CR'=>'Costa Rica',
      'CU'=>'Cuba',
      'CV'=>'Cape Verde',
      'CX'=>'Christmas Island',
      'CY'=>'Cyprus',
      'CZ'=>'Czechia',
      'DE'=>'Germany',
      'DJ'=>'Djibouti',
      'DK'=>'Denmark',
      'DM'=>'Dominica',
      'DO'=>'Dominican Republic',
      'DZ'=>'Algeria',
      'EC'=>'Ecuador',
      'EE'=>'Estonia',
      'EG'=>'Egypt',
      'EH'=>'Western Sahara',
      'ER'=>'Eritrea',
      'ES'=>'Spain',
      'ET'=>'Ethiopia',
      'FI'=>'Finland',
      'FJ'=>'Fiji',
      'FK'=>'Falkland Islands',
      'FM'=>'Micronesia',
      'FO'=>'Faroe Islands',
      'FR'=>'France',
      'GA'=>'Gabon',
      'GB'=>'United Kingdom',
      'GD'=>'Grenada',
      'GE'=>'Georgia',
      'GF'=>'French Guiana',
      'GG'=>'Guernsey',
      'GH'=>'Ghana',
      'GI'=>'Gibraltar',
      'GL'=>'Greenland',
      'GM'=>'Gambia',
      'GN'=>'Guinea',
      'GP'=>'Guadeloupe',
      'GQ'=>'Equatorial Guinea',
      'GR'=>'Greece',
      'GS'=>'South Georgia & South Sandwich Islands',
      'GT'=>'Guatemala',
      'GU'=>'Guam',
      'GW'=>'Guinea-Bissau',
      'GY'=>'Guyana',
      'HK'=>'Hong Kong SAR China',
      'HM'=>'Heard & McDonald Islands',
      'HN'=>'Honduras',
      'HR'=>'Croatia',
      'HT'=>'Haiti',
      'HU'=>'Hungary',
      'ID'=>'Indonesia',
      'IE'=>'Ireland',
      'IL'=>'Israel',
      'IM'=>'Isle of Man',
      'IN'=>'India',
      'IO'=>'British Indian Ocean Territory',
      'IQ'=>'Iraq',
      'IR'=>'Iran',
      'IS'=>'Iceland',
      'IT'=>'Italy',
      'JE'=>'Jersey',
      'JM'=>'Jamaica',
      'JO'=>'Jordan',
      'JP'=>'Japan',
      'KE'=>'Kenya',
      'KG'=>'Kyrgyzstan',
      'KH'=>'Cambodia',
      'KI'=>'Kiribati',
      'KM'=>'Comoros',
      'KN'=>'St. Kitts & Nevis',
      'KP'=>'North Korea',
      'KR'=>'South Korea',
      'KW'=>'Kuwait',
      'KY'=>'Cayman Islands',
      'KZ'=>'Kazakhstan',
      'LA'=>'Laos',
      'LB'=>'Lebanon',
      'LC'=>'St. Lucia',
      'LI'=>'Liechtenstein',
      'LK'=>'Sri Lanka',
      'LR'=>'Liberia',
      'LS'=>'Lesotho',
      'LT'=>'Lithuania',
      'LU'=>'Luxembourg',
      'LV'=>'Latvia',
      'LY'=>'Libya',
      'MA'=>'Morocco',
      'MC'=>'Monaco',
      'MD'=>'Moldova',
      'ME'=>'Montenegro',
      'MF'=>'St. Martin',
      'MG'=>'Madagascar',
      'MH'=>'Marshall Islands',
      'MK'=>'North Macedonia',
      'ML'=>'Mali',
      'MM'=>'Myanmar (Burma)',
      'MN'=>'Mongolia',
      'MO'=>'Macao SAR China',
      'MP'=>'Northern Mariana Islands',
      'MQ'=>'Martinique',
      'MR'=>'Mauritania',
      'MS'=>'Montserrat',
      'MT'=>'Malta',
      'MU'=>'Mauritius',
      'MV'=>'Maldives',
      'MW'=>'Malawi',
      'MX'=>'Mexico',
      'MY'=>'Malaysia',
      'MZ'=>'Mozambique',
      'NA'=>'Namibia',
      'NC'=>'New Caledonia',
      'NE'=>'Niger',
      'NF'=>'Norfolk Island',
      'NG'=>'Nigeria',
      'NI'=>'Nicaragua',
      'NL'=>'Netherlands',
      'NO'=>'Norway',
      'NP'=>'Nepal',
      'NR'=>'Nauru',
      'NU'=>'Niue',
      'NZ'=>'New Zealand',
      'OM'=>'Oman',
      'PA'=>'Panama',
      'PE'=>'Peru',
      'PF'=>'French Polynesia',
      'PG'=>'Papua New Guinea',
      'PH'=>'Philippines',
      'PK'=>'Pakistan',
      'PL'=>'Poland',
      'PM'=>'St. Pierre & Miquelon',
      'PN'=>'Pitcairn Islands',
      'PS'=>'Palestinian Territories',
      'PT'=>'Portugal',
      'PW'=>'Palau',
      'PY'=>'Paraguay',
      'QA'=>'Qatar',
      'RE'=>'Réunion',
      'RO'=>'Romania',
      'RS'=>'Serbia',
      'RU'=>'Russia',
      'RW'=>'Rwanda',
      'SA'=>'Saudi Arabia',
      'SB'=>'Solomon Islands',
      'SC'=>'Seychelles',
      'SD'=>'Sudan',
      'SE'=>'Sweden',
      'SG'=>'Singapore',
      'SH'=>'St. Helena',
      'SI'=>'Slovenia',
      'SJ'=>'Svalbard & Jan Mayen',
      'SK'=>'Slovakia',
      'SL'=>'Sierra Leone',
      'SM'=>'San Marino',
      'SN'=>'Senegal',
      'SO'=>'Somalia',
      'SR'=>'Suriname',
      'ST'=>'São Tomé & Príncipe',
      'SV'=>'El Salvador',
      'SY'=>'Syria',
      'SZ'=>'Eswatini',
      'TC'=>'Turks & Caicos Islands',
      'TD'=>'Chad',
      'TF'=>'French Southern Territories',
      'TG'=>'Togo',
      'TH'=>'Thailand',
      'TJ'=>'Tajikistan',
      'TK'=>'Tokelau',
      'TL'=>'Timor-Leste',
      'TM'=>'Turkmenistan',
      'TN'=>'Tunisia',
      'TO'=>'Tonga',
      'TR'=>'Turkey',
      'TT'=>'Trinidad & Tobago',
      'TV'=>'Tuvalu',
      'TW'=>'Taiwan',
      'TZ'=>'Tanzania',
      'UA'=>'Ukraine',
      'UG'=>'Uganda',
      'UM'=>'U.S. Outlying Islands',
      'US'=>'United States',
      'UY'=>'Uruguay',
      'UZ'=>'Uzbekistan',
      'VA'=>'Vatican City',
      'VC'=>'St. Vincent & Grenadines',
      'VE'=>'Venezuela',
      'VG'=>'British Virgin Islands',
      'VI'=>'U.S. Virgin Islands',
      'VN'=>'Vietnam',
      'VU'=>'Vanuatu',
      'WF'=>'Wallis & Futuna',
      'WS'=>'Samoa',
      'YE'=>'Yemen',
      'YT'=>'Mayotte',
      'ZA'=>'South Africa',
      'ZM'=>'Zambia',
      'ZW'=>'Zimbabwe',
    );
    return $country_list;
  }

}
