<?php

if (!class_exists('BRNGBOKGPTDY_Utils')):


class BRNGBOKGPTDY_Utils {

  function __construct(){}

  public static function is_integer($num) {

    if(!isset($num) || !is_numeric($num)) {
      return false;
    }

    if (is_string($num) && ctype_digit($num)) {
      return true;
    } 

    if(!is_int($num)) {
      return false;
    }
     
    return true;
  }

  public static function restrict_to_admin() {
    return current_user_can('manage_options');
  }

  public static function check_arr_property($arr, $name) {
    return (
      array_key_exists($name, $arr) &&
      $arr[$name]
    );
  }

}

endif;