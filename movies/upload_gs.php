
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
include("imdb.php");
require_once('parsecsv.lib.php');

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
        $storagename = "google_spreadsheet.txt";
        move_uploaded_file($_FILES["file"]["tmp_name"], "upload/" . $storagename);
        echo "Stored in: " . "upload/" . $_FILES["file"]["name"] . "<br />";
      }
      
      $csv = new parseCSV();
      $csv->auto('upload/' . $storagename);
      $rows = IMDB::clean($csv->data);
      print_r($rows);
    }
  } else {
    echo "No file selected <br />";
  }
}
?>