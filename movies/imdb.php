<?php

class IMDB {
  
  private static $name_map = array (
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
    foreach ($array as $index => $row) {
      $cleanObject = array();
      $unknown = true;
      foreach ($row as $key => $value) {
        $skip = IMDB::cleanOne($key, $value);
        if (!$skip) {
          $cleanObject[$key] = $value;
          $unknown = false;
        }
      }
      if (!$unknown)
        $cleanArray[] = $cleanObject;
    }
  }
  
  public static function cleanOne($key, $value) {
    echo "${key}: ${value}";
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
      $key = IMDB::$name_map[$key];
    }
    return $unknown;
  }
}

?>