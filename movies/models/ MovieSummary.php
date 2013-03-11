
<?php

Class MongoModel extends ArrayObject {
  protected $model;
  protected $classname;
  
  function __construct($objects_db=null) {
    $this->model = $objects_db;
    if (!isset($objects_db)) {
      foreach(MongoObject::$FIELDS as $key => $val)
        if($key != 'ID')
          $this[$key] = $val[1];
    }
    $classname = gettype($this);
  }
  
  public static function toModel($objects_db) {
    $models = array();
    foreach($objects_db as $object_db)
      $models[] = new $this->classname($object_db);
    return $models;
  }
  
  public static function toDB($models) {
    $objects_db = array();
    foreach($models as $model)
      $objects_db[] = $model->get();
    return $objects_db;
  }
  
  public function set($model) {
    $this->model = $model;
  }
  
  public function get() {
    return $this->model;
  }
  
  public function __set($key, $value) {
    if (array_key_exists($key, $this->FIELDS))
      $this->model[$this->FIELDS[$key][0]] = $value;
  }
  
  public function __get($key) {
    if (array_key_exists($key, $this->$FIELDS))
      return $this->model[$this->FIELDS[$key][0]];
    return null;
  }
  
  public function offsetGet($name) { 
    return $this->__get($name);
  }
  
  public function offsetSet($name, $value) { 
    $this->__set($name, $value);
  }
  
  public function offsetExists($name) { 
    return isset($this->model[$name]); 
  }
  
  public function offsetUnset($name) { 
    unset($this->model[$name]); 
  }
}

Class MovieSummary extends ArrayObject {
  
  public $FIELDS = array(
    'ID' => '_id',
    'OWNER' => array('owner', -1),
    'MOVIE_ID' => array('mid', -1),
    'LISTS' => array('lists', array()),
    'RATING' => array('rating', array()),
    'REVIEW' => array('review', null),
    'TIMESTAMP'=> array('ts', null)
  );
  
  /*function __construct($objects_db=null) {
    parent::__construct();
  }*/
  
  public function __set($key, $value) {
    if (array_key_exists($key, Movie::$FIELDS)) {
      switch($key) {       
        case 'MOVIE_ID': //int
          $value = intval($value);
          break;
        case "OWNER": //string
          $value = (string)$value;
          break;
        case "LISTS":
          break;
        case "RATING":
          $date = $this->TIMESTAMP = new Date();
          $value = array('value' => intval($value), 'ts' => $date);
          break;
        case "REVIEW":
          $date = $this->TIMESTAMP = new Date();
          $value = array('text' => (string)$value, 'ts' => $date);
          break;
      }
      parent::__set($value, $key);
    }
  }
  
  public function addToList($list_id) {
    $date = $this->TIMESTAMP = new Date();
    $this->LISTS[] = array('id' => intval($list_id), 'ts' => $date);
  }
  
  public function removeList($list_id) {
    $date = $this->TIMESTAMP = new Date();
    $lists = $this->LISTS;
    foreach($lists as $i => $list) {
      if ($list['id'] == $list_id) {
        unset($lists[$i]);
        break;
      }
    }
  }
}

// not being used currently
class Cast {
  private $data = array();
  
  public static $FIELDS = array(
    'ROTTEN_ID' => 'rotten_id',
    'NAME' => 'name',
    'MAIN' => 'main'
  );
  
  function __construct($value=null) {
    //echo $actors;
    if(is_object($value) && get_class($value) == "Cast") {
      $this->set($value->get());
    } else {
      if (is_string($value))
        $value = explode(", ", $value);
      if (is_array($value)) {
        foreach($value as $index => $actor) {
          if (is_array($actor)) {
            $one = $actor;
          } else {
            $one = array();
            $one[Cast::$FIELDS['NAME']] = $actor;
            $one[Cast::$FIELDS['ROTTEN_ID']] = 0;
            $one[Cast::$FIELDS['MAIN']] = true;
          }
          $this->data[] = $one;
        }
      }
    }
  }
  
  public function set($data) {
    $this->data = $data;
  }
  
  public function get() {
    return $this->data;
  }
  public function add($cast) {
   $this->data[] = $cast;
  }
  
  public function addDetail($actor, $rotten_id=0, $main=true) {
    $one = array();
    $one[Cast::$FIELDS['NAME']] = $actor;
    $one[Cast::$FIELDS['ROTTEN_ID']] = $rotten_id;
    $one[Cast::$FIELDS['MAIN']] = $main;
    $this->data[] = $one;
  }
}

?>