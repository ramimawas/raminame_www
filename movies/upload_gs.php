
<html>
  <body>
    <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post" enctype="multipart/form-data">
      <div>
        <span>Select file:</span>
        <input type="file" name="file" id="file" />
        <span>CSV File Source:</span>
        <select name="source" type="checkbox">
          <option value="imdb" selected="selected">imdb</option>
          <option value="google" >google</option>
        </select>
      </div>

      <div>
        <span>Submit</span>
        <input type="submit" name="submit" />
      </div>
    </form>
  </body>
</html>

<?php

include("mongohq.php");
include("imdb.php");
require_once('parsecsv.lib.php');

$source = null;

function upload($source) {
  $fileName = null;
  if ( isset($_POST["submit"]) ) {
    if ( isset($_FILES["file"])) {
      //if there was an error uploading the file
      if ($_FILES["file"]["error"] > 0) {
        echo "Return Code: " . $_FILES["file"]["error"] . "<br />";
      } else {
        //Print file details
        echo "Upload: " . $_FILES["file"]["name"] . "<br />";
        echo "Type: " . $_FILES["file"]["type"] . "<br />";
        echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
        echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br />";
        
        $fileName = "upload/" . $source . "_" . str_replace(' ', '', $_FILES["file"]["name"]);
        move_uploaded_file($_FILES["file"]["tmp_name"], $fileName);
        echo "Stored in: " . $fileName . "<br />";
      }
    } else {
      echo "No file selected <br />";
    }
  }
  return $fileName;
}

function buildHash($rows) {
  $hashMap = array();
  foreach($rows as $row)
    $hashMap[hkey($row['Movie Title'])] = $row;
  return $hashMap;
}

function hkey($title) {
  return strtolower($title);
}

if(isset($_POST["source"]))
  $source = $_POST["source"];

$fileName = upload($source);

echo $fileName  . "<br/>";
echo $source  . "<br/>";

if($fileName != null) {
  $db = new MongoHQ(array('collectionName'=>'watched'));
  $csv = new parseCSV($fileName);
  $csv->auto();
  $data = $csv->data;
  //print_r($data);
  if ($source == "imdb") {
    $rows = IMDB::clean($data);
    //echo "[" . $rows  . "]<br/>";
    //print_r($rows);
    $db->saveMany($rows);
  } else if ($source == "google") {
    $hashMap = buildHash($data);
    $db_movies = $db->find();
    $mismatched = array();
    foreach ($db_movies as $db_movie) {
      $title = $db_movie['title'];
      //echo "<br/><div>[${db_movie['position']}] processing movie title: " . $title . " [db/rotten]</div>";
      if (isset($hashMap[hkey($title)])) {
        $rating = intval($hashMap[hkey($title)]['Rate']);
        //echo "<div>${rating}</div>";
        $db_movie['rating'] = $rating;
        $db->save($db_movie);
      } else {
        $mismatched[] = $title;
      }
    }
    foreach ($mismatched as $title)
      echo "<div>${title}</div>";
  }
}
?>