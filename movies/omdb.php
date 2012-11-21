<?php

class OMDB {
  private static $URL = 'http://www.omdbapi.com/?i=';
  
  private static function fetchJsonResults($id) {
    $ch = curl_init(OMDB::$URL . $id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result =  curl_exec($ch); // Getting jSON result string
    $json = json_decode($result, true);
    return $json;
  }

  public static function getMovie($imdb_id) {
    $omdb_json = OMDB::fetchJsonResults($imdb_id);
    $movie = null;
    if(array_key_exists('imdbID', $omdb_json) && array_key_exists('Title', $omdb_json)) {
      $movie = new Movie();
      $movie->IMDB_ID = $imdb_id;
      $movie->IMDB_RATING = $omdb_json['imdbRating'];
      $movie->DATE_ADDED = date('D M j g:i:s Y'); //Tue Jul  3 12:00:00 2007
      $movie->TITLE = $omdb_json['Title'];
      $movie->YEAR = $omdb_json['Year'];
      $movie->MPAA_RATING = $omdb_json['Rated'];
      $movie->RELEASE_DATE = $omdb_json['Released'];
      // extract runtime
      $movie->GENRES = $omdb_json['Genre'];
      $movie->DIRECTORS = $omdb_json['Director'];
      $movie->CAST = $omdb_json['Actors'];
    }
    //var_dump($movie->get());
    return $movie;
  }
}