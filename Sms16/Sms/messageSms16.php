<?php

namespace Sms16\Sms;

class messageSms16 {

  public $type='sms', $sender, $abonent, $text, $url, $name, $cell, $work, $fax, $email, $position, $organization, $post_office_box, $street,
    $city, $region, $postal_code, $country, $additional;

  public function __construct($opt){
    foreach($opt as $k => $v) $this->$k = $v;
  }

}
?>