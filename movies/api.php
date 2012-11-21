<?php
header('Content-Type: application/json');

include_once("mongohq.php");
include_once("rotten.php");
include_once("movie.php");

function fetchJsonResults($url) {
  $ch = curl_init( $url );
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $result =  curl_exec($ch); // Getting jSON result string
  $json = json_decode($result, true);
  return $json;
}

$db = new MongoHQ(array('collectionName'=>'watched'));

if(isset($_GET["imdb_id"])) {
  $imdb_id = $_GET["imdb_id"];
  $omdb_url = "http://www.omdbapi.com/?i=" . $imdb_id;
  $omdb_json = fetchJsonResults($omdb_url);
  $movie = new Movie();
  $movie->IMDB_ID = $imdb_id;
  $movie->IMDB_RATING = $omdb_json['imdbRating'];
  $movie->TITLE = $omdb_json['Title'];
  $movie->YEAR = $omdb_json['Year'];
  $movie->MPAA_RATING = $omdb_json['Rated'];
  $movie->RELEASE_DATE = $omdb_json['Released'];
  // extract runtime
  $movie->GENRES = explode(", ", $omdb_json['Genre']);
  $movie->DIRECTOR = explode(", ", $omdb_json['Director']);
  $movie->CAST = $omdb_json['Actors'];
  
  //var_dump($movie->get());
  echo json_encode($movie->get());
}


//$movie_db = $db->findOne();
//echo json_encode($movie_db);

/*$rotten = new RottenTomatoes();
$info = array();
$movie_db_augmented = $rotten->dispatchDbMovie($movie_db, $info);
var_dump($movie_db_augmented);*/

?>