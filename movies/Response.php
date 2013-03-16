<?php

Class Response {
  public $status;
  public $data;
  
  function __construct($status=null, $data=null) {
    if ($status)
      $status = new Status();
    $this->status = $status;
    $this->data = $data;
  }
  
  public function set($status, $data=null) {
    $this->status = $status;
    $this->data = $data;
  }
}

Class Status {
  public $code;
  public $text;
  
  function __construct($code=501, $text=null) {
    if (!array_key_exists($code, $this->map))
      $code = 501;
    $this->code = $code;
    if (empty($text))
      $text = $this->map[$code];
    $this->text = $text;
  }
  
  public function setError($error) {
    $this->text = $this->text . ': ' . $error;
  }
  
  private $map = array (
    200=> 'ok',
    
    //NOT FOUND
    300=>'imdb_id is missing',
    301=>'rating is missing',
    302=>'rating must be between 1 and 5',
    303=>'imdb_id or title must be specified',
    304=>'method is missing',
    400=>'movie not found',
    401=>'movie is already in DB',
    402=>'movies not found',
    
    
    //ERRORS
    500 => 'Exception',
    501 => 'Unknown Error Code'
  );
}

?>