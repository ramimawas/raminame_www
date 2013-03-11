
<?php

Class Movie extends ArrayObject{
  private $movie;
  
  private static $DEFAULTS = array (
    'int'=> -1,
    'string' => ''
  );
  
  public static $FIELDS = array(
    'ID' => '_id',
    'POSITION' => array('position', -1),
     
    // PERSONAL
    'RATING'     => array('rating', -1),
    'DATE_ADDED' => array('added', ''),
    
    // GENERIC
    'TITLE'        => array('title', ''),
    'YEAR'         => array('year', -1),
    'RELEASE_DATE' => array('released', ''),
    'DIRECTORS'    => array('directors', array()),
    'RUNTIME'      => array('runtime', -1),
    'GENRES'       => array('genres', array()),
    'MPAA_RATING'  => array('mpaa_rating', ''),
    'CAST'         => array('cast', array()),
    'TYPE'         => array('type', ''),
    
    // IMDB
    'IMDB_ID'       => array('imdb_id', ''),
    'IMDB_RATING'   => array('imdb_rating', -1),
      
    // ROTTEN
    'ROTTEN_ID'             => array('rotten_id', -1),
    'ROTTEN_CRITICS_SCORE'  => array('rotten_critics_score', -1),
    'ROTTEN_AUDIENCE_SCORE' => array('rotten_audience_score', -1)
  );
  
  function __construct($movie=null) {
    $this->movie = $movie;
    if (!isset($movie)) {
      foreach(Movie::$FIELDS as $key => $val) {
        if($key != 'ID')
          $this[$key] = $val[1];
      }
      $this->DATE_ADDED = strtotime('now');
    }
  }
  
  public static function toMovies($movies_db) {
    $movies = array();
    foreach($movies_db as $movie_db)
      $movies[] = new Movie($movie_db);
    return $movies;
  }
  
  public static function toMoviesDB($movies) {
    $movies_db = array();
    foreach($movies as $movie)
      $movies_db[] = $movie->get();
    return $movies_db;
  }
  
  public function set($movie) {
    $this->movie = $movie;
  }
  
  public function get() {
    return $this->movie;
  }
  
  public function __set($key, $value) {
    if (array_key_exists($key, Movie::$FIELDS)) {
      switch($key) {       
        case 'RATING': //int
        case 'POSITION':
        case 'YEAR':
        case 'RUNTIME':
        case "ROTTEN_ID":
        case "ROTTEN_CRITICS_SCORE":
        case "ROTTEN_AUDIENCE_SCORE":
          $value = intval($value);
          break;
        case "IMDb Rating": //float
          $value = floatval($value);
          break;
        case "DATE_ADDED":  // int datetime
          if (gettype($value) == "string") {
            if (($value = strtotime($value)) === false)
              $value = strtotime("now");
          } else if(gettype($value) != "integer")
            $value = strtotime("now");
          break;
        case "RELEASE_DATE":
        case "TITLE": //string
        case "TYPE":
        case 'IMDB_ID':
        case "MPAA_RATING":
          $value = (string)$value;
          break;
        case "CAST": //array
        case "DIRECTORS": 
        case "GENRES":
          if (is_string($value)) {
            if ($key == "GENRES")
              $value = strtolower($value);
            $value = explode(", ", $value);
          }
          if(!is_array($value))
            $value = array();
          break;
      }
      $this->movie[Movie::$FIELDS[$key][0]] = $value;
    }
  }
  
  public function __get($key) {
    if (array_key_exists($key, Movie::$FIELDS))
      return $this->movie[Movie::$FIELDS[$key][0]];
    return null;
  }
  
  public function offsetGet($name) { 
    return $this->__get($name);
  }
  
  public function offsetSet($name, $value) { 
    $this->__set($name, $value);
  }
  
  public function offsetExists($name) { 
    return isset($this->data[$name]); 
  }
  
  public function offsetUnset($name) { 
    unset($this->data[$name]); 
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