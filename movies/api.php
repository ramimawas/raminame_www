<?php
header('Content-Type: application/json');

include_once("mongohq.php");
include_once("rotten.php");
include_once("movie.php");
include_once("omdb.php");
include_once("response.php");


class API {
  private $db;
  private $DEFAULT_RATING = 3;
  
  function __construct() {
    $db = new MongoHQ(array('collectionName'=>'watched'));
  }

  private function getMovie($imdb_id, $rating) {
    $movie = OMDB::getMovie($imdb_id);
    if ($movie != null) {
      $movie->RATING = $rating;
      $rotten = new RottenTomatoes();
      $rotten->augmentMovie($movie);
    }
    return $movie;
  }
  
  public function addMovie($imdb_id, $rating) {
    $response = new Response();
    if (!isset($imdb_id))
      throw new Exception("API", 300);
    if(!isset($rating))
      throw new Exception("API", 301);
    if ($rating < 1 || $rating > 5)
      throw new Exception("API", 302);
    $movie = $this->getMovie($imdb_id, $rating);
    if($movie == null)
      throw new Exception("API", 400);
    $response->set(new Status(200), $movie->get());
    return $response;
  }
  
  public function dispatch($api) {
    $response = new Response();
    try {
      switch($api) {
        case 'api/add':
          $response = $this->addMovie($_GET["imdb_id"], $_GET["rating"]);
          break;
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
$api->dispatch('api/add');

?>