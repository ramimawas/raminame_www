<?php
include_once("mongohq.php");
include_once("movie.php");

class RottenTomatoes {
  
  private $config = array (
    'apikey' => '6k3szrykkcyqjpvxkkzpt2az',
    'urls' => array(
      'api' => 'api.rottentomatoes.com/api/public/v1.0/',
      'cast_info_p1'=> 'movies/',
      'cast_info_p2'=> '/cast.json?',
      'movies_search'=> 'movies.json?'
    ),
    'qs' => array(
      'movies_search' => array(
        'q' => 'To Story 3',
        'page_limit'=> '40'
      ),
      'cast_info_p'=> array()
    )
  );
  
  private function transliterateString($txt) {
    return iconv("UTF-8", "ISO-8859-1//TRANSLIT", $txt);
  }
  
  private function buildMoviesSearchUrl($q, $limit=null) {
    $config = $this->config;
    $urls = $config['urls'];
    $query = $config['qs']['movies_search'];
    if($q != null)
      $query['q'] = $q;
    if($limit != null)
      $query['limit'] = $limit;
    $query['apikey'] = $config['apikey'];
    return $urls['api'] . $urls['movies_search']. http_build_query($query);
  }

  private function buildCastInfoUrl($id) {
    $config = $this->config;
    $urls = $config['urls'];
    $query = $config['qs']['cast_info_p'];
    $query['apikey'] = $config['apikey'];
    return $urls['api'] . $urls['cast_info_p1']. $id . $urls['cast_info_p2'] . http_build_query($query);
  }

  private function runCastInfo($id) {
    $url = $this->buildCastInfoUrl($id);
    //echo "<div>${url}</div>";
    return $this->fetchJsonResults($url);
  }

  private function runMoviesSearch($title) {
    $url = $this->buildMoviesSearchUrl($this->transliterateString($title));
    //echo "<div>${url}</div>";
    return $this->fetchJsonResults($url);
  }

  private function fetchJsonResults($url) {
    $ch = curl_init( $url );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result =  curl_exec($ch); // Getting jSON result string
    $json = json_decode($result, true);
    return $json;
  }

  private function findBestMatch($rotten_movies, $title,  $imdb_id, &$info) {
    $rotten_movie = $rotten_movies[0];
    $found = false;
    $percentages = array();
    $count = count($rotten_movies);
    $imdbfound = false;

    for($i=0; $i<$count; $i++) {
      $alternate_ids = $rotten_movies[$i]['alternate_ids'];
      if ($imdb_id != null && !$imdbfound && $alternate_ids != null) {
        $imddb = "tt" . $alternate_ids['imdb'];
        if($imdb_id == $imddb) {
          $rotten_movie_imdb = $rotten_movies[$i];
          $imdbfound = true;
        }
      }
      if(strtolower($rotten_movies[$i]['title']) == strtolower($title)) {
        $rotten_movie = $rotten_movies[$i];
        $found = true;
        break;
      } else {
        similar_text($title, $rotten_movies[$i]['title'], $percent);
        $percentages[] = $percent;
      }
    }

    if(!$found) {
      //echo "<div>Titles NOT EQUAL!!!</div>";
      $max = 0;
      $maxIndex = 0;
      foreach($percentages as $index => $percent) {
        if ($percent > $max) {
          $max = $percent;
          $maxIndex = $index;
        }
      }
      $rotten_movie = $rotten_movies[$maxIndex];
      /*
      echo "<div>Maximum percentage is ${max} at index ${maxIndex}</div>";
      if($count==1) echo "<div>Only title: [" . $rotten_movie['title'] . "]</div>";
      else echo "<div>Best title: [" . $rotten_movie['title'] . "]</div>";
      */
      if($imdbfound) {
        //echo "<div>IMDB ids matched ${imdb_id}: [" . $rotten_movie_imdb['title'] . "]</div>";
        $rotten_movie = $rotten_movie_imdb;
      } else {
        if (info != null)
          $info['mismatch'][] = array($title, $rotten_movie['title']);
      }
      if (info != null)
        $info['titleMismatch'][] = array($title, $rotten_movie['title'], $rotten_movies[$maxIndex]['title'], $max, $imdbfound);
    }
    return $rotten_movie;
  }

  public function dispatchMovie($rotten_movies, &$movie, &$info) {
    if (count($rotten_movies) >= 1) {
      $rotten_movie = $this->findBestMatch($rotten_movies, $movie->TITLE, $movie->IMDB_ID, $info);

      $rotten_id = $rotten_movie['id'];
      $movie->ROTTEN_ID = $rotten_id;

      $movie->MPAA_RATING = $rotten_movie['mpaa_rating'];

      $ratings = $rotten_movie['ratings'];
      $movie->ROTTEN_CRITICS_SCORE = $ratings['critics_score'];
      $movie->ROTTEN_AUDIENCE_SCORE = $ratings['audience_score'];

      $abridged_cast = $rotten_movie['abridged_cast'];
      $abridged_cast_names = array();
      foreach($abridged_cast as $cast)
        $abridged_cast_names[] = $cast['name'];

      $cast_json = $this->runCastInfo($rotten_id);
      $cast = new Cast();
      $full_cast = $cast_json['cast'];
      $cast_list = array();
      foreach($full_cast as $one)
        $cast->addDetail($one['name'], $one['id'], in_array($one['name'], $abridged_cast_names));
      $movie->CAST = $cast->get();
      //print_r($db_movie);
      $db->save($db_movie);
      echo "----> movies updated.";
    } else {
      //echo "<div>ERROR while searching rotten tomatoes!!!!!!!!</div><br/>";
      $info['missing'][] = $movie['title'];
    }
  }
  
  public function augmentMovie(&$movie, &$info=null) {
    if($info != null)
      $info = array('titleMismatch'=> array(), 'mismatch' => array(), 'missing' => array());
    $title = $movie->TITLE;
    echo "<div>processing movie title: " . $title . "</div>";
    $search_json = $this->runMoviesSearch($title);
    $rotten_movies = $search_json['movies'];
    $this->dispatchMovie($rotten_movies, $movie, $info);
  }

  public function augmentMovies(&$movies, &$info=null) {
    $count = 1;
    foreach ($movies as &$movie) {
      echo "<div>[${count}] "; $count++;
      $this->augmentMovie($movie, $info);
    }
  }
}


$db = new MongoHQ(array('collectionName'=>'watched'));

$rotten = new RottenTomatoes();

/*
$movie_db = $db->findOne();
var_dump($movie_db); echo "<br/><br/>";
$movie = new Movie($movie_db);
var_dump($movie->get()); echo "<br/><br/>";;
$rotten->augmentMovie($movie, $info);
var_dump($movie->get());
*/

$movies_db = $db->find(null, null, 5);
var_dump($movies_db); echo "<br/><br/>";
$movies = Movie::toMovies($movies_db);
$rotten->augmentMovies($movies, $info);
var_dump(Movie::toMoviesDB($movies));


if (isset($info)) {
  if (isset($info['missing'])) {
    echo "<div>Total number of missing titles: " . count($info['missing']) . "</div>";
    foreach($info['missing'] as $val) {
      echo "<div>[${val}]</div>";
    }
  }

  if (isset($info['titleMismatch'])) {
    echo "<br/><br/><div>Total number of mismatched titles: " . count($info['titleMismatch']) . " [db/rotten]</div>";
    foreach($info['titleMismatch'] as $val) {
      echo "<div>[" . $val[0] . "]</div>";
      echo "<div>[" . $val[1] . "]</div>";
      echo "<div>[" . $val[2] . "]</div>";
      echo "<div>[" . $val[3] . "]</div>";
      echo "<div>[" . $val[4] . "]</div>";
      echo "<br/>";
    }
  }

  if (isset($info['mismatch'])) {
    echo "<br/><br/><div>Total number of overall mismatched movies: " . count($info['mismatch']) . " [db/rotten]</div>";
    foreach($info['mismatch'] as $val) {
      echo "<div>[" . $val[0] . "]</div>";
      echo "<div>[" . $val[1] . "]</div>";
      echo "<br/>";
    }
  }
}

?>