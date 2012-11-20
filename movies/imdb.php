<?php

class IMDB {
  
  private $name_map = array (
    "position" => "position",
    "const" => "imdb_id",
    "Runtime (mins)" => "runtime",
    "Year" => "year",
    "Title" => "title",
    "IMDb Rating" => "rating",
    "Title type" => "type",
    "created" => "added",
    "Directors" => "directors",
    "Genres" => "genres"
  );
  
  public static function clean($array) {
    $cleanArray = array();
    foreach ($values as $key => $value) {
      $skip = IMDB::clean($key, $value);
      if (!$skip)
        $cleanArray[$key] = $value;
    }
  }
  
  public static function clean($key, $value) {
    global $name_map;
    $unknown = false;
    switch ($key) {
      case "position": // int
      case "Year":
      case "Runtime (mins)":
      case "IMDb Rating":
      case "Year":
        $value = intval($value);
        break;
      case "const": //string
      case "Title":
      case "Title type":
        // do nothing
        break;
      case "created": //date
        $value = $value;
        break;
      case "Directors": //array
      case "Genres":
        $value = explode(", ", $value);
        break;
      default:
        $unknown = true;
        break;
    }
    if (!$unknown) {
      $key = $name_map[$key];
      $value = $value;
    }
    return $unknown;
  }
}

?>