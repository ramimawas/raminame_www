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

  private  function fetchJsonResults($url) {
    $ch = curl_init( $url );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result =  curl_exec($ch); // Getting jSON result string
    $json = json_decode($result, true);
    return $json;
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
  
  private function addMovie($imdb_id, $rating) {
    $response = new Response();
    try {  
      if (isset($_GET["imdb_id"])) {
        $rating = isset($_GET["rating"]) ? $_GET["rating"] : $this->DEFAULT_RATING;
        $movie = $this->getMovie($imdb_id, $rating);
        if($movie != null)
          $response.set(new Response(new Status(200), $movie->get()));
      } else
        $response.setStatus(new Response(new Status(300)));
    } catch (xcpetion $e) {
      $response.getStatus.setError($e);
    }
    echo json_encode($response);
  }
}

$api = new API();
$api->addMovie($_GET["imdb_id"], $_GET["rating"]);

?>