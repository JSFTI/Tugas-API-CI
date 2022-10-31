<?php

use App\Utils\Stream;

defined('BASEPATH') OR exit('No direct script access allowed');

if(!function_exists('view_json')){
  /**
   * Returns encoded string as JSON
   * 
   * @param stdClass|array $array
   * @param number $status
   * 
   * @return string
   */
  function view_json($array = [], $status = 200){
    http_response_code($status);
    header("Content-Type: application/json");
    return json_encode((array) $array);
  }
}

if(!function_exists('view_422_json')){
  /**
   * Wrapper for view_json for form validations errors
   * 
   * @param stdClass|array $errors
   * 
   * @return string
   */
  function view_body_invalid($errors){
    return view_json([
      'message' => 'Unprocessable Entity',
      'errors' => $errors
    ], 422);
  }
}

if(!function_exists('get_input_json')){
  /**
   * Get request body as JSON, if content type is form data, convert form data to stdObject  
   * NOTE: Native PHP does not support file parsing with PUT natively.  
   * If PUT is used as the HTTP verb, this function will parse file from request body
   * and overwrites $_FILES
   * 
   * @return stdClass
   */
  function get_input_json(){
    if($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH'){
      if($_SERVER['CONTENT_TYPE'] === 'application/x-www-form-urlencoded' || str_starts_with($_SERVER['CONTENT_TYPE'], 'multipart/form-data')){
        $stream = new Stream(file_get_contents("php://input"));
        $_FILES = $stream->get_files();
        return (object) $stream->get_post();
      }
      return json_decode(file_get_contents("php://input"));
    }

    if($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE'] === 'application/json'){
      return json_decode(file_get_contents("php://input"));
    }
    
    return (object) $_POST;
  }
}

if(!function_exists('is_date_format_iso')){
  function is_date_format_iso($date){
    $date = DateTime::createFromFormat("Y-m-d", $date);
    return $date !== false && $date::getLastErrors();
  }
}

if(!function_exists('has_string_keys')){
  function has_string_keys($array) {
    return count(array_filter(array_keys($array), 'is_string')) > 0;
  }
}

if(!function_exists('add_hash_filename')){
  function add_hash_filename($filename){
    $random_string = random_string('alnum', 10);
    return "$random_string-$filename";
  }
}