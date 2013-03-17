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
  
  public function top($field, $minimumCount=0) {
    $response = new Response();
    if(empty($minimumCount))
      $minimumCount = 0;
    $minimumCount = intval($minimumCount);
    //db.watched.aggregate({ $project : {cast: 1, title: 1} }, {$unwind: "$cast"}, {$group: {_id: "$cast", titles: { $addToSet: "$title"}, count: {$sum: 1} }}, {$sort: {count: -1}}, {$limit: 20})
    
    $project = array(
          '$project' => array(
              $field => 1
          )
      );
    $unwind = array('$unwind' => '$' . $field);
    $group = array(
          '$group' => array(
              '_id' => '$' . $field,
              'count'=> array('$sum' => 1)
          )
      );
    $match = array(
          '$match'=> array(
              'count' => array('$gte' => $minimumCount))
      );
    $sort = array('$sort'=> array('count' => -1));
    
    if (in_array($field, array('cast', 'directors', 'genres'))) {
      $pipeline = array(
        $project,
        $unwind,
        $group,
        $match,
        $sort
      );
    } else {
      $pipeline = array(
        $project,
        $group,
        $match,
        $sort
      );
    }
    
    $results = $this->db->aggregate($pipeline);
    if (!empty($results))
      $results = $results[0];
    else
      throw new Exception("API", 501);
    $response->set(new Status(200), $results);
    return $response;
  }
  
  public function fixMovie($imdb_ids) {
    $response = new Response();
    if (!isset($imdb_ids))
      throw new Exception("API", 300);
    
    if($imdb_ids == "_fixall") {
      $query = array();
    } else {
      $query = array("imdb_id"=>array('$in' => explode(',', $imdb_ids)));
    }
    if (!isset($query))
      throw new Exception("API", 501);
    $movies_db = $this->db->find($query);
    foreach ($movies_db as $movie_db) {
      $movieRami = new Movie($movie_db);
      $imdb_id = $movieRami->IMDB_ID;
      //echo "processing {$imdb_id}";
      //echo "========= Original movie in DB: ";var_dump($movieRami);

      $movieOMDB = OMDB::getMovieByImdbId($imdb_id);
      //echo "========= omdb movie: ";var_dump($movieOMDB);
      $fieldsStrInt = array("IMDB_RATING", "TITLE", "TYPE", "YEAR", "MPAA_RATING", "RELEASE_DATE", "RUNTIME");
      foreach($fieldsStrInt as $field) {
        if ($movieRami[$field] != $movieOMDB[$field])
          $movieRami[$field] = $movieOMDB[$field];
      }

      $fieldsArray = array("CAST", "DIRECTORS", "GENRES");
      foreach($fieldsArray as $field) {
        $diff1 = array_diff($movieRami[$field], $movieOMDB[$field]);
        $diff2 = array_diff($movieOMDB[$field], $movieRami[$field]);
        if (!($diff1 != null && $diff2 != null && count($diff1)==0 && count($diff2)==0))
          $movieRami[$field] = $movieOMDB[$field];
      }
      //echo "========= After omdb merge: ";var_dump($movieRami);
      $this->rotten->augmentMovie($movieRami);
      //echo "========= After rotten merge: ";var_dump($movieRami);
      $this->db->save($movieRami->get());
    }
    $response->set(new Status(200), $movie->get());
    return $response;
  }
  
  public function addMovie($imdb_id, $rotten_id, $rating) {
    $response = new Response();
    if (!isset($imdb_id))
      throw new Exception("API", 300);
    if(!isset($rating))
      throw new Exception("API", 301);
    if ($rating < 1 || $rating > 5)
      throw new Exception("API", 302);
    $movie_db = $this->db->findOne(array("imdb_id"=>$imdb_id  ));
    if (empty($movie_db)) {
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
    } else {
      throw new Exception("API", 401);
    }
    $response->set(new Status(200), $movie->get());
    return $response;
  }
  
  public function findMovie($imdb_id, $rotten_id, $title) {
    $response = new Response();
    if ((!isset($imdb_id) || empty($imdb_id)) && (!isset($rotten_id) || empty($rotten_id)) && (!isset($title) || empty($title)))
      throw new Exception("API", 303);
    else if(isset($imdb_id) && !empty($imdb_id))
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
  
  public function dispatch() {
    $response = new Response();
    header('Access-Control-Allow-Origin: *');
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
          case 'fix':
            $response = $this->fixMovie($_GET["i"]);
            break;
          case 'top':
            $response = $this->top($_GET["f"], $_GET["c"]);
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
    if ($_GET['callback']) {
        print $_GET['callback']."(";
    }
    echo json_encode($response);
    if ($_GET['callback']) {
        print ")";
    }
  }
}

$api = new API();
$api->dispatch();

?>