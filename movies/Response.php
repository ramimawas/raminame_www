<?php

Class Response {
  private $status;
  private $data;
  
  function __construct() {
    $this->status = new Status();
    $this->data = null;
  }
  
  function __construct($status, $data) {
    $this->status = $status;
    $this->data = $data;
  }
  
  function set($status, $data) {
    $this->status = $status;
    $this->data = $data;
  }
  
  function setStatus() {
    $this->status = $status;
  }
  
  function getStatus() {
    return $this->status;
  }
  
  function setData() {
    $this->data = $data;
  }
  
  function getData() {
    return $this->data;
  }
}

Class Response {
  private $text;
  private $code;
  
  function __construct($code=501) {
    $this->status = $status;
    if (!array_key_exists($code, $this->map))
      $code = 501;
    $this->text = $this->map[$code];
  }
  
  function setError($error) {
    $this->text = $this->text . ': ' $error;
  }
  
  private $map = array (
    200: 'ok',
    
    //NOT FOUND
    300=>'movie not found',
    
    //ERRORS
    500 => 'Exception',
    501 => 'Unknown Error Code'
  );
}

?>