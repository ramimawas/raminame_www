
<table width="600">
  <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post" enctype="multipart/form-data">

  <tr>
    <td width="20%">Select file</td>
    <td width="80%"><input type="file" name="file" id="file" /></td>
  </tr>

  <tr>
    <td>Submit</td>
    <td><input type="submit" name="submit" /></td>
  </tr>

  </form>
</table>

<?php

include("mongohq.php");

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

      //if file already exists
      if (file_exists("upload/" . $_FILES["file"]["name"])) {
        echo $_FILES["file"]["name"] . " already exists. ";
      } else {
        //Store file in directory "upload" with the name of "uploaded_file.txt"
        $storagename = "uploaded_file.txt";
        move_uploaded_file($_FILES["file"]["tmp_name"], "upload/" . $storagename);
        echo "Stored in: " . "upload/" . $_FILES["file"]["name"] . "<br />";
      }
      
      if ($file = fopen( "upload/" . $storagename , r ) ) {
        echo "File opened.<br />";

        $firstline = fgets ($file, 4096 );
        $fields = array();
        preg_match_all('/\"([^\"]*?)\"/', $firstline, $matches);
        $fields = $matches[1];
        $count = count($fields);
        echo "<p># fields: ${count}</p>";
        var_dump($fields);
        echo "<br/><br/>";
        $line = array();
        $rows = array();
        $i = 0;
        
        $name_map = array (
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
        
        while ( $line[$i] = fgets ($file, 4096) ) {
          $object = array();
          $matches = array();
          preg_match_all('/\"([^\"]*?)\"/', $line[$i], $matches);
          $values = $matches[1];
          foreach ($values as $index => $value) {
            $key = $fields[$index];
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
              $object[$key] = $value;
            }
          }
          $rows[$i] = $object;
          $i++;
        }
        $db = new MongoHQ(array('collectionName'=>'watched'));
        $db->saveMany($rows);
      }
    }
  } else {
    echo "No file selected <br />";
  }
}
?>