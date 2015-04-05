<?php

include_once("mongohq.php");
include_once("Movie.php");

class MOVIEDB {
  private static $URL = 'http://api.themoviedb.org/3/search/movie?api_key=a56c4c9722f90923979c4ed41b5c715f&';
  
  public static function getMovieById($imdb_id) {
    return MOVIEDB::getMovie(MOVIEDB::buildUrlByImdbId($imdb_id));
  }
  
  public static function getMoviesByTitle($title) {
    return MOVIEDB::getMovies(MOVIEDB::buildUrlByTitle($title));
  }
  
  private static function fetchJsonResults($url) {
    //echo $url;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result =  curl_exec($ch); // Getting jSON result string
    $json = json_decode($result, true);
    return $json;
  }
  
  private static function buildUrlById($id) {
    return MOVIEDB::$URL . 'i=' . $id;
  }
  
  private static function buildUrlByTitle($title) {
    return MOVIEDB::$URL . 'query=' . urlencode($title);
  }
  
  private static function getMovies($url) {
    return MOVIEDB::_getMovies(MOVIEDB::fetchJsonResults($url));
  }
  
  private static function toMinutes($runtime_str) {
    $runtime_int = 0;
    preg_match('/((\d+) h )?((\d+) min)/', $runtime_str, $matches);
    if (count($matches)==5) {
      $hours = intval($matches[2]);
      $minutes = intval($matches[4]);
      $runtime_int = 60 * $hours + $minutes;
    }
    return $runtime_int;
  }
  
  private static function _matchMovie($movies, $title, $year, &$info=null) {
   //echo '---MATCHING---';
   $movie_matched = null;
   foreach($movies as $movie) {
     //echo "<br/>{$movie->TITLE} == $title<br/>";
     //var_dump($movie->TITLE == $title);
     //echo "<br/>{$movie->YEAR} == $year<br/>";
     //var_dump($movie->YEAR == $year);
     if (strtolower($movie->TITLE) == strtolower($title) || ($movie->YEAR == $year)) {
       $movie_matched = $movie;
       //echo 'BREAK!!!!';
       break;
     }
   }
   if (!isset($movie_matched) && isset($info))
     $info[] = $title;
   //var_dump($movie_matched);
   return $movie_matched;
  }
  
  private static function _getMovies($moviesdb_json) {
    $moviesdb = $moviesdb_json['results'];
    $movies = array();
    foreach($moviesdb as $moviedb) {
      $movie = MOVIEDB::_getMovie($moviedb);
      if(isset($movie))
        $movies[] =  $movie;
    }
    return $movies;
  }
  
  private static function _getMovie($moviedb) {
    $movie = null;
    if(array_key_exists('id', $moviedb) && array_key_exists('title', $moviedb)) {
      $movie = new Movie();
      $movie->MOVIEDB_ID = $moviedb['id'];
      $movie->TITLE = $moviedb['original_title'];
      $release_date = $moviedb['release_date'];
      $release_array = explode("-", $release_date);
      if(!empty($release_array))
        $movie->YEAR = $release_array[0];
    }
    return $movie;
  }
  
  public function augmentMovie(&$movie, &$info=null) {
    //echo "<br/><br/>{$movie->TITLE}<br/>";
    $moviedb = MOVIEDB::_matchMovie(MOVIEDB::getMoviesByTitle($movie->TITLE), $movie->TITLE, $movie->YEAR, $info);
    $themoviedb_id = -1;
    if(isset($moviedb))
      $themoviedb_id = $moviedb->MOVIEDB_ID;
    $movie->MOVIEDB_ID = $themoviedb_id;
  }
  
  public function augmentMovies(&$movies, &$info=null) {
    foreach($movies as &$movie) {
      $this->augmentMovie($movie, $info);
    }
  }
}

/*$db = new MongoHQ(array('collectionName'=>'watched'));
$themoviedb = new MOVIEDB();


$movie_db = $db->findOne();
//$movies_db = $db->find();
//var_dump($movie_db); echo "<br/><br/>";
$movie = new Movie($movie_db);
//var_dump($movie->get()); echo "<br/><br/>";;
$info = array();
$themoviedb->augmentMovie($movie, $info);
var_dump($movie->get());
echo '<br/><br/>';
var_dump($info);


//$movies_db = $db->find(null, 10, 0);
$movies_db = $db->find();
//var_dump($movies_db); echo "<br/><br/>";
$movies = Movie::toMovies($movies_db);
//echo count($movies);
$info = array();
$themoviedb->augmentMovies($movies, $info);
//var_dump(Movie::toMoviesDB($movies));
echo "<br/><br/>";
print_r($info);
$db->saveMany(Movie::toMoviesDB($movies));
*/

?>