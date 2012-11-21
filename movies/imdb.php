<?php

class IMDB {
  
  private static $name_map = array (
    "position" => 'IMDB_POSITION',
    "const" => "IMDB_ID",
    "Runtime (mins)" => "RUNTIME",
    "Year" => "YEAR",
    "Title" => "TITLE",
    "IMDb Rating" => "IMDB_RATING",
    "Title type" => "TYPE",
    "created" => "DATE_ADDED",
    "Directors" => "DIRECTORS",
    "Genres" => "GENRES"
  );
  
  public static function buildMovies($array) {
    $movies = array();
    foreach ($array as $index => $row) {
      $movie = new Movie();
      foreach ($row as $key => $value) 
        IMDB::saveKeyValue($movie, $key, $value);
      $movies[] = $movie;
    }
    return $movies;
  }
  
  public static function saveKeyValue($movie, $key, $value) {
    if (array_key_exists($key, IMDB::$name_map)) {
      $movie[IMDB::$name_map[$key]] = $value;
      $skip = false;
    }
  }
}

?>