<?php

class IMDB {
  
  private static $name_map = array (
    "position" => "position",
    "const" => "imdb_id",
    "Runtime (mins)" => "runtime",
    "Year" => "year",
    "Title" => "title",
    "IMDb Rating" => "imdb_rating",
    "Title type" => "type",
    "created" => "added",
    "Directors" => "directors",
    "Genres" => "genres"
  );
  
  public static function clean($array) {
    $cleanArray = array();
    foreach ($array as $index => $row) {
      $cleanObject = array();
      $empty = true;
      foreach ($row as $key => $value) {
        if (!IMDB::cleanOne($key, $value)) {
          //echo "<div>${key} =====> ${value}</div>";
          $cleanObject[$key] = $value;
          $empty = false;
        }
      }
      if (!$empty)
        $cleanArray[] = $cleanObject;
    }
    return $cleanArray;
  }
  
  public static function cleanOne(&$key, &$value) {
    $skip = false;
    switch ($key) {
      case "position": // int
      case "Year":
      case "Runtime (mins)":
      case "Year":
        $value = floatval($value);
        $value = intval($value);
        break;
      case "IMDb Rating":
        $value = floatval($value);
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
        $skip = true;
        break;
    }
    if (!$skip) {
      $key = IMDB::$name_map[$key];
    }
    return $skip;
  }
}

?>