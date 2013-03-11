<?php

header('Content-Type: application/json');

include_once("mongohq.php");
include_once("rotten.php");
include_once("movie.php");
include_once("omdb.php");
include_once("response.php");

//$date = strtotime("Tue Jul  3 12:00:00 2007");
//$date = strtotime("2008-11-26");
//$date = strtotime("1 h 44 min");
//$date = strtotime("0");
//echo date('l dS \o\f F Y', $date);
  

class API {
  private $db;
  private $DEFAULT_RATING = 3;
  private $rotten;
  
  function __construct() {
    $this->db = new MongoHQ(array('collectionName'=>'watched'));
    $this->rotten = new RottenTomatoes();
  }
  
  public function addMovie($imdb_id, $rotten_id, $rating) {
    $response = new Response();
    if (!isset($imdb_id))
      throw new Exception("API", 300);
    if(!isset($rating))
      throw new Exception("API", 301);
    if ($rating < 1 || $rating > 5)
      throw new Exception("API", 302);
    $movie = OMDB::getMovieByImdbId($imdb_id);
    if (isset($rotten_id))
      $movie->ROTTEN_ID = $rotten_id;
    $movie->RATING = $rating;
    $this->rotten->augmentMovie($movie);
    if(empty($movie))
      throw new Exception("API", 400);
    $position = $this->db->count();
    $movie->POSITION = $position+1;
    $this->db->save($movie->get());
    $response->set(new Status(200), $movie->get());
    return $response;
  }
  
  public function findMovie($imdb_id, $rotten_id, $title) {
    $response = new Response();
    if (!isset($imdb_id) && !isset($rotten_id) && !isset($title))
      throw new Exception("API", 303);
    else if(isset($imdb_id))
      $movie = OMDB::getMovieByImdbId($imdb_id);
    else if(isset($title))
      $movie = OMDB::getMovieByTitle($title);
    else
      $movie = new Movie();
    if (isset($rotten_id))
      $movie->ROTTEN_ID = $rotten_id;
    $this->rotten->augmentMovie($movie);
    if(empty($movie))
      throw new Exception("API", 400);
    $response->set(new Status(200), $movie->get());
    return $response;
  }
  
  public function dispatch($api) {
    $response = new Response();
    try {
      $method = $_GET["m"];
      if(isset ($method) ) {
        switch($method) {
          case 'add':
            $response = $this->addMovie($_GET["i"], $_GET["rid"], $_GET["r"]);
            break;
          case 'find':
            $response = $this->findMovie($_GET["i"], $_GET["rid"], $_GET["t"]);
            break;
          default:
            throw new Exception("API", 304);
            break;
        }
      }
    } catch (Exception $e) {
      if ($e->getMessage() == "API")
         $response->status = new Status($e->getCode());
      else
        $response->getStatus.setError($e->getMessage());
    }
    echo json_encode($response);
  }
}

$api = new API();
$api->dispatch();

?>