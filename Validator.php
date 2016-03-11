<?php namespace Anekdotes;
use Carbon\Carbon;

/**
 * Validator class, validate passed Input
 */
class Validator {

  private $items;
  private $rules;
  private $currentItemName;
  public $errors = array();

  public static function make($items, $rules) {
    $validator = new Validator();
    $validator->items = $items;
    $validator->rules = $rules;
    return $validator;
  }

  /**
   * fail return true if any value fail the validation
   *
   * @return bool
   */
  public function fail() {

    $mergedParams = array();

    foreach ($this->rules as $itemName => $ruleNames) {

      foreach ($ruleNames as $rule) {

        $ruleParams = explode(":", $rule);
        $rule = $ruleParams[0];
        array_splice($ruleParams, 0, 1);

        if (array_key_exists($itemName, $this->items)) {

          $mergedParams[] = $this->items[$itemName];

          if (count($ruleParams) > 0) {
            $ruleParams = explode(",", $ruleParams[0]);
            $mergedParams = array_merge($mergedParams, $ruleParams);
          }

          $this->currentItemName = $itemName;
          if (!call_user_func_array(array($this, $rule), $mergedParams)) {
            $this->errors[$itemName] = $itemName." is not ".$rule;
          }
          $this->currentItemName = null;

          array_splice($mergedParams, 0, count($mergedParams));

        }
        else {
          $this->errors[$itemName] = $itemName." is not found ";
        }

      }

    }

    return count($this->errors) > 0 ? true : false;
  }





  /**
   * Check if $value is empty
   *
   * @param   mixed  value to validate
   * @return  bool
   */
  public function required($value) {
    if (is_array($value)){
      return count($value) > 0 ? true : false;
    }
    else{
      return strlen($value) > 0 ? true : false;
    }
  }

  /**
   * Check if $value is empty if
   * $itemValue has $condition value
   *
   * @param   mixed  value to validate
   * @param   mixed  item value to check
   * @param   mixed  condition to respect
   * @return  bool
   */
  public function requiredIf($value, $item, $condition) {
    return $this->items[$item] == $condition ?
      $this->required($value) :
      true ;
  }

  /**
   * Check if $value is empty if
   * $itemValue is not empty
   *
   * @param   mixed  value to validate
   * @param   mixed  item value to check
   * @return  bool
   */
  public function requiredWith($value, $item) {
    return $this->required($this->items[$item]) ?
      $this->required($value) :
      true ;
  }

  /**
   * Check if $value is empty if
   * $itemValue is empty
   *
   * @param   mixed  value to validate
   * @param   mixed  item value to check
   * @return  bool
   */
  public function requiredWithout($value, $item) {
    return $this->required($this->items[$item]) ?
      true :
      $this->required($value);
  }

  /**
   * Check if the $value is an integer
   *
   * @param   mixed  value to validate
   * @return  bool
   */
  public function integer($value) {
    return $this->required($value) ? is_int($value) : true ;
  }

  /**
   * Check if the $value is numeric
   *
   * @param   mixed  value to validate
   * @return  bool
   */
  public function numeric($value) {
    return $this->required($value) ? is_numeric($value) : true ;
  }

  /**
   * Check if the $value is a date in supported format
   * {"d-m-Y", "d/m/Y"}
   *
   * @param   string value to validate
   *
   * @return  bool
   */
  public function date($value) {
    $formats = array("d-m-Y", "d/m/Y");                   // Must add format with time...
    if ($this->required($value)) {
      foreach ($formats as $format) {
        $date = \Carbon::createFromFormat($format, $value);
        if ($date && (date_format($date, $format) == $value)) {
          return true;
        }
      }
      return false;
    }
    return true;
  }

  /**
   * Check if the $value is different then
   * $params
   *
   * @param   array {value to vidate, n value to compare}
   * @return  bool
   */
  public function different() {
    $params = func_get_args();
    $value = $params[0];
    array_splice($params, 0, 1);
    if ($this->required($value)) {
      foreach ($params as $param) {
        if ($value == $param) {
          return false;
        }
      }
    }
    return true;
  }

  /**
   * Check if the $value match a email
   *
   * @param   string  value to validate
   * @return  bool
   */
  public function email($value) {
    return $this->required($value) ?
      filter_var($value, FILTER_VALIDATE_EMAIL) && preg_match('/@.+\./', $value) :
      true ;
  }

  /**
   * Check if the $value match a postal code
   *
   * @param   string  value to validate
   * @return  bool
   */
  public function postalCode($value) {
    return $this->required($value) ?
      (bool)preg_match(
        '/^[ABCEGHJKLMNPRSTVXY]{1}\d{1}[A-Z]{1}[\s-_]*\d{1}[A-Z]{1}\d{1}$/i',
        $value
      ) :
      true ;
  }

  /**
   * Check if the $value match a phone number
   *
   * @param   string  value to validate
   * @return  bool
   */
  public function phoneNumber($value) {
    $value = preg_replace("/[^\d]+/", "", $value);
    return $this->required($value) ?
      in_array(strlen($value), array(7, 10, 11)) :
      true ;
  }

  /**
   * Check if the $value is between $min and $max
   * You can put
   * string : test string length
   * number : test number size
   * file : test file size in kilobytes
   *
   * @param   mixed    string or number or file
   * @param   numeric  minimum size to check
   * @param   numeric  maximum size to check
   * @return  bool
   */
  public function between($value, $min, $max) {
    if ($this->required($value)) {
      if (is_numeric($value)) {                               // is_numeric or is_integer ?
        if ($value > $min & $value < $max) {
          return true;
        }
      }
      elseif (is_string($value)) {
        if (strlen($value) > $min & strlen($value) < $max) {
          return true;
        }
      }
      elseif (is_file($value)) {
        $fileSize = ($value["size"] / 1024);
        if ($fileSize > $min & $fileSize < $max) {
          return true;
        }
      }
      return false;
    }
    return true;
  }

  /**
   * Check if the $value is under $min
   * You can put
   * string : test string length
   * number : test number size
   * file : test file size in kilobytes
   *
   * @param   mixed    string or number or file
   * @param   numeric  minimum size to check
   * @return  bool
   */
  public function minimum($value, $min) {
    if ($this->required($value)) {
      if (is_numeric($value)) {                               // is_numeric or is_integer ?
        if ($value > $min) {
          return true;
        }
      }
      elseif (is_string($value)) {
        if (strlen($value) > $min) {
          return true;
        }
      }
      elseif (is_file($value)) {
        $fileSize = ($value["size"] / 1024);
        if ($fileSize > $min) {
          return true;
        }
      }
      return false;
    }
    return true;
  }

  /**
   * Check if the $value is over $max
   * You can put
   * string : test string length
   * number : test number size
   * file : test file size in kilobytes
   *
   * @param   mixed    string or number or file
   * @param   numeric  maximum size to check
   * @return  bool
   */
  public function maximum($value, $max) {
    if ($this->required($value)) {
      if (is_numeric($value)) {                               // is_numeric or is_integer ?
        if ($value > $max) {
          return true;
        }
      }
      elseif (is_string($value)) {
        if (strlen($value) > $max) {
          return true;
        }
      }
      elseif (is_file($value)) {
        $fileSize = ($value["size"] / 1024);
        if ($fileSize > $max) {
          return true;
        }
      }
      return false;
    }
    return true;
  }

  /**
   * Check if the $value is exactly $size
   * You can put
   * string : test string length
   * number : test number size
   * file : test file size in kilobytes
   *
   * @param   mixed    string or number or file
   * @param   numeric  exact size to check
   * @return  bool
   */
  public function size($value, $size) {
    if ($this->required($value)) {
      if (is_numeric($value)) {                               // is_numeric or is_integer ?
        if ($value == $size) {
          return true;
        }
      }
      elseif (is_string($value)) {
        if (strlen($value) == $size) {
          return true;
        }
      }
      elseif (is_array($value)) {
        if (count($value) == $size) {
          return true;
        }
      }
      elseif (is_file($value)) {
        $fileSize = ($value["size"] / 1024);
        if ($fileSize == $size) {
          return true;
        }
      }
      return false;
    }
    return true;
  }

  /**
   * Check if the $value is exactly $length
   *
   * @param   mixed    string or number
   * @return  bool
   */
  public function length($value,$length) {
    return strlen($value) == $length ? true : false;
  }

  /**
   * Check if $value match an URL..
   *
   * @param  string  value to check
   * @return bool
   */
  public function url($value)  {
    return $this->required($value) ?
      (bool)preg_match(
        '/^(?:(?:https?|ftp):\/\/)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})'.
        '(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})'.
        '(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])'.
        '(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))'.
        '|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}'.
        '-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))'.
        '(?::\d{2,5})?(?:\/[^\s]*)?$/iuS',
        $value) :
      true ;
  }

  /**
   * Check if $value match an URL and is valid
   *
   * @param  string  value to check
   * @return bool
   */
  public function validUrl($value) {
    return $this->required($value) ?
      $this->url($value) && checkdnsrr($value) :
      true ;
  }

  /**
   * Check if _confirmed value is the same
   *
   * @param   string  value tu check
   * @return  bool
   */
  public function same($value, $item) {
    return $value === $this->items[$item];
  }

  /**
   * Check if $item_confirmation value is the same
   *
   * @param   string  value tu check
   * @return  bool
   */
  public function confirmed($value) {
    return $value === $this->items[$this->currentItemName.'_confirmation'];
  }

}


/*

  "accepted"         => "The :attribute must be accepted.",
  "after"            => "The :attribute must be a date after :date.",
  "alpha"            => "The :attribute may only contain letters.",
  "alpha_dash"       => "The :attribute may only contain letters, numbers, and dashes.",
  "alpha_num"        => "The :attribute may only contain letters and numbers.",
  "before"           => "The :attribute must be a date before :date.",
  "confirmed"        => "The :attribute confirmation does not match.",
  "date_format"      => "The :attribute does not match the format :format.",
  "digits"           => "The :attribute must be :digits digits.",
  "digits_between"   => "The :attribute must be between :min and :max digits.",
  "exists"           => "The selected :attribute is invalid.",
  "image"            => "The :attribute must be an image.",
  "in"               => "The selected :attribute is invalid.",
  "ip"               => "The :attribute must be a valid IP address.",
  "mimes"            => "The :attribute must be a file of type: :values.",
  "not_in"           => "The selected :attribute is invalid.",
  "regex"            => "The :attribute format is invalid.",
  "unique"           => "The :attribute has already been taken.",

*/