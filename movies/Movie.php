
<?php

Class Movie extends ArrayObject{
  private $movie;
  
  public static $FIELDS = array(
    'ID' => '_id',
     
    // PERSONAL
    'RATING'     => 'rating',
    'DATE_ADDED' => 'added',
    
    // GENERIC
    'TITLE'        => 'title',
    'YEAR'         => 'year',
    'RELEASE_DATE' => 'released',
    'DIRECTORS'    => 'directors',
    'RUNTIME'      => 'runtime',
    'GENRES'       => 'genres',
    'MPAA_RATING'  => 'mpaa_rating',
    'CAST'         => 'cast',
    'TYPE'         => 'type',
    
    // IMDB
    'IMDB_ID'       => 'imdb_id',
    'IMDB_RATING'   => 'imdb_rating',
    'IMDB_POSITION' => 'position',
      
    // ROTTEN
    'ROTTEN_ID'             => 'rotten_id',
    'ROTTEN_CRITICS_SCORE'  => 'rotten_critics_score',
    'ROTTEN_AUDIENCE_SCORE' => 'rotten_audience_score'
  );
  
  function __construct($movie=array()) {
   $this->movie = $movie;
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
        case 'IMDB_POSITION':
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
        case "TITLE": //string
        case "TYPE":
        case 'IMDB_ID':
        case "DATE_ADDED":
        case "RELEASE_DATE":
        case "MPAA_RATING":
        case "MPAA_RATING":
          $value = (string)$value;
          break;
        case "DIRECTORS": //array
        case "GENRES":
          $value = explode(", ", $value);
          break;
        case "CAST":
          $value = new Cast($value);
          $value = $value->get();
          break;
      }
      $this->movie[Movie::$FIELDS[$key]] = $value;
    }
  }
  
  public function __get($key) {
    if (array_key_exists($key, Movie::$FIELDS)) {
      return $this->movie[Movie::$FIELDS[$key]];
    }
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

class Cast {
  
  private $data = array();
  
  public static $FIELDS = array(
    'ROTTEN_ID' => 'rotten_id',
    'NAME' => 'name',
    'MAIN' => 'main'
  );
  
  function __construct($value=null) {
    //echo $actors;
    if(is_object($value ) && get_class($value) == "Cast") {
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