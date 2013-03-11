<?php

class OMDB {
  private static $URL = 'http://www.omdbapi.com/?';
  
  public static function getMovieByImdbId($imdb_id) {
    return OMDB::getMovie(OMDB::buildUrlByImdbId($imdb_id));
  }
  
  public static function getMovieByTitle($title) {
    return OMDB::getMovie(OMDB::buildUrlByTitle($title));
  }
  
  private static function fetchJsonResults($url) {
    //echo $url;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result =  curl_exec($ch); // Getting jSON result string
    $json = json_decode($result, true);
    return $json;
  }
  
  private static function buildUrlByImdbId($id) {
    return OMDB::$URL . 'i=' . $id;
  }
  
  private static function buildUrlByTitle($title) {
    return OMDB::$URL . 't=' . urlencode($title);
  }
  
  private static function getMovie($url) {
    return OMDB::_getMovie(OMDB::fetchJsonResults($url));
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
  
  private static function _getMovie($omdb_json) {
    $movie = null;
    if(array_key_exists('imdbID', $omdb_json) && array_key_exists('Title', $omdb_json)) {
      $movie = new Movie();
      $movie->IMDB_ID = $omdb_json['imdbID'];
      $movie->IMDB_RATING = $omdb_json['imdbRating'];
      $movie->TITLE = $omdb_json['Title'];
      $movie->TYPE = $omdb_json['Type'];
      $movie->YEAR = $omdb_json['Year'];
      $movie->MPAA_RATING = $omdb_json['Rated'];
      $movie->RELEASE_DATE = $omdb_json['Released'];
      $movie->RUNTIME = OMDB::toMinutes($omdb_json['Runtime']);
      $movie->GENRES = preg_replace('/-/', '_', $omdb_json['Genre']);
      if ($omdb_json['Director'] != 'N/A')
        $movie->DIRECTORS = $omdb_json['Director'];
      $movie->CAST = $omdb_json['Actors'];
    }
    return $movie;
  }
}