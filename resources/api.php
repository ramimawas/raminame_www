<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

include_once("mongohq.php");
include_once("rotten.php");
include_once("Movie.php");
include_once("omdb.php");
include_once("themoviedb.php");
include_once("Response.php");

class API {
  private $db;
  private $DEFAULT_RATING = 3;
  private $rotten;
  
  function __construct() {
    $this->db = new MongoHQ(array('collectionName'=>'watched'));
    $this->rotten = new RottenTomatoes();
    $this->themoviedb = new MOVIEDB();
  }
  
  public function mean() {
    
    $response = new Response();
    
    $project = array(
          '$project' => array(
              "year" => 1,
              "rating" => 1,
              "imdb_rating" => 1,
              "rotten_critics_score" => 1,
              "rotten_audience_score" => 1
          )
      );
    $group = array(
          '$group' => array(
              '_id' => 'averages',
              'year'=> array('$avg' => '$year'),
              'rating'=> array('$avg' => '$rating'),
              'imdb_rating'=> array('$avg' => '$imdb_rating'),
              'rotten_critics_score'=> array('$avg' => '$rotten_critics_score'),
              'rotten_audience_score'=> array('$avg' => '$rotten_audience_score')
          )
      );
    
    $match = array('$match' => array('rotten_critics_score' => array('$ne' => -1)));
    
    $pipeline = array(
      $match,
      $project,
      $group
    );
    
    $results = $this->db->aggregate($pipeline);
    if (!empty($results))
      $results = $results[0];
    else
      throw new Exception("API", 501);
    $rating = $results[0]['rating'];
    $rating *= 20;
    $results[0]['rating'] = round($rating, 2);
    
    $imdb_rating = $results[0]['imdb_rating'];
    $imdb_rating *= 10;
    $results[0]['imdb_rating'] = round($imdb_rating, 2);
    
    $results[0]['year'] = round($results[0]['year']);
    
    $rotten_critics_score = $results[0]['rotten_critics_score'];
    $results[0]['rotten_critics_score'] = round($rotten_critics_score, 2);
    
    $rotten_audience_score = $results[0]['rotten_audience_score'];
    $results[0]['rotten_audience_score'] = round($rotten_audience_score, 2);
    
    $response->set(new Status(200), $results);
    return $response;
  }
  
  public function top($field, $minimumCount=0, $sort='count', $direction=-1) {
    $response = new Response();
    if(empty($minimumCount))
      $minimumCount = 0;
    if(empty($direction))
      $direction = -1;
    $direction = intval($direction);
    $minimumCount = intval($minimumCount);
    
    $project = array(
          '$project' => array(
              $field => 1,
              'rating' => 1
          )
      );
    $unwind = array('$unwind' => '$' . $field);
    $group = array(
          '$group' => array(
              '_id' => '$' . $field,
              'count'=> array('$sum' => 1),
              'rating'=> array('$avg' => '$rating')
          )
      );
    $match = array(
          '$match'=> array(
              'count' => array('$gte' => $minimumCount),
              '_id' => array('$ne' => null))
      );
    if ($field == 'year') {
      if ($sort == 'default')
        $hierarchy = array('_id' => $direction);
      else if ($sort == 'rating')
        $hierarchy = array('rating' => $direction, "_id" => -1);
      else
        $hierarchy = array('count' => $direction, "_id" => -1);
    } else {
      if ($sort == 'default')
        $hierarchy = array('_id' => $direction, 'count' => -1, "rating" => -1);
      else if ($sort == 'rating')
        $hierarchy = array("rating" => $direction, 'count' => -1, "_id" => 1);
      else
        $hierarchy = array('count' => $direction, "rating" => -1, "_id" => 1);
    }
    
    $sort = array('$sort'=> $hierarchy);
    
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
    
    foreach ($results[0] as &$row) 
      $row['rating'] = round($row['rating'], 2);
    
    if (!empty($results))
      $results = $results[0];
    else
      throw new Exception("API", 501);
    $response->set(new Status(200), $results);
    return $response;
  }
  
  public function fixMovie($imdb_ids, $rotten_ids, $rating) {
    $response = new Response();
    if (!isset($imdb_ids))
      throw new Exception("API", 300);
    if(isset($rotten_ids))
      $rottenIds = explode(',', $rotten_ids);
    $imdbIds = explode(',', $imdb_ids);
    
    if($imdb_ids == "_fixall") {
      $query = array();
    } else {
      if (isset($rottenIds)) {
        if(count($rottenIds) != count($imdbIds))
          throw new Exception("API", 305);
        else {
          $rottenIdsMap = array();
          for($i = 0; $i<count($imdbIds) ; $i++)
            $rottenIdsMap[$imdbIds[$i]] = $rottenIds[$i];
        }
      }
      $query = array("imdb_id"=>array('$in' => $imdbIds));
    }
    if (!isset($query))
      throw new Exception("API", 501);
    $movies_db = $this->db->find($query);
    $i = 0;
    foreach ($movies_db as $movie_db) {
      $movieRami = new Movie($movie_db);
      $imdb_id = $movieRami->IMDB_ID;
      //echo "processing {$imdb_id}";echo "========= Original movie in DB: ";var_dump($movieRami);

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
      if (isset($rottenIds))
        $movieRami->ROTTEN_ID = $rottenIdsMap[$imdb_id];
      $this->rotten->augmentMovie($movieRami);
      //echo "========= After rotten merge: ";var_dump($movieRami);
      if(isset($rating))
        $movieRami->RATING = intval($rating);
      $this->db->save($movieRami->get());
      $i++;
    }
    $response->set(new Status(200), $movieRami->get());
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
    $movie_db = $this->db->findOne(array("imdb_id"=>$imdb_id));
    if (empty($movie_db)) {
      $movie = OMDB::getMovieByImdbId($imdb_id);
      if (isset($rotten_id))
        $movie->ROTTEN_ID = $rotten_id;
      $movie->RATING = $rating;
      $this->rotten->augmentMovie($movie);
      if(empty($movie))
        throw new Exception("API", 400);
      $this->themoviedb->augmentMovie($movie);
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
  
  public function listMovie($imdb_id, $rotten_id, $limit, $skip) {
    $response = new Response();
    
    if(isset($imdb_id))
      $imdbIds = explode(',', $imdb_id);
    if(isset($rotten_id))
      $rottenIds = explode(',', $rotten_id);
    
    $query = array();
    if (isset($imdbIds))
      $query = array('imdb_id' => array( '$in' => $imdbIds));
    else if (isset($rottenIds))
      $query = array('rotten_id' => array( '$in' => $rottenIds));
    $movies_db = $this->db->find($query, $limit, $skip);
    $response->set(new Status(200), $movies_db);
    return $response;
  }

  public function listAllMovies() {
    $response = new Response();
    $movies_db = $this->db->all();
    $response->set(new Status(200), $movies_db);
    return $response;
  }
  
  public function dispatch() {
    $response = new Response();
    try {
      $method = $_GET["method"];
      if(isset ($method) ) {
        switch($method) {
          case 'add':
            $response = $this->addMovie($_GET["imdbid"], $_GET["rid"], $_GET["rating"]);
            break;
          case 'find':
            $response = $this->findMovie($_GET["imdbid"], $_GET["rid"], $_GET["title"]);
            break;
          case 'list':
            $response = $this->listMovie($_GET["imdbid"], $_GET["rid"], $_GET["limit"], $_GET["skip"]);
            break;
          case 'all':
            $response = $this->listAllMovies();
            break;
          case 'fix':
            $response = $this->fixMovie($_GET["imdbid"], $_GET["rid"], $_GET["rating"]);
            break;
          case 'top':
            $response = $this->top($_GET["field"], $_GET["count"], $_GET["sort"], $_GET["direction"]);
            break;
          case 'mean':
            $response = $this->mean();
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