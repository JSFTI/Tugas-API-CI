<?php

namespace App\Traits;

use Carbon\Carbon;

trait CustomValidators{
  public function exists($value, $param){
    if($value !== 0 && !$value){
      return true;
    }

    $explode = explode('.', $param);
    $table = $explode[0];
    $column = $explode[1];

    $count = $this->db->where($column, $value)->from($table)->count_all_results();

    if($count > 0){
      return true;
    }

    $this->form_validation->set_message('exists', "Value '$value' does not exist in database");
    return false;
  }

  public function after_date_field($value, $param){
    if($value !== 0 && !$value){
      return true;
    }

    $fieldValue = $this->form_validation->validation_data[$param];

    if(!is_date_format_iso($fieldValue)){
      return true;
    }

    $fieldDate = new Carbon($fieldValue);
    $value = new Carbon($value);

    if($value->gte($fieldDate)){
      return true;
    }

    $this->form_validation->set_message('after_date_field', "This date must be set after $param");
    return false;
  }

  public function valid_date($value){
    if(!is_date_format_iso($value)){
      $this->form_validation->set_message('valid_date', "Invalid date format. Use ISO YYYY-MM-DD");
      return false;
    }

    return true;
  }
}

?>