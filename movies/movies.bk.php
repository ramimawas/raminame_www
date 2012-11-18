<html>
  <head>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js"></script>
    <script type="text/javascript" src="http://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.0/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="http://www.appelsiini.net/download/jquery.jeditable.js"></script>
    <script type="text/javascript" src="http://jquery-datatables-editable.googlecode.com/svn/trunk/media/js/jquery.validate.js"></script>
    <script type="text/javascript" src="http://jquery-datatables-editable.googlecode.com/svn/trunk/media/js/jquery.dataTables.editable.js"></script>
    <script type="text/javascript" src="http://datatables.net/release-datatables/extras/KeyTable/js/KeyTable.js"></script>
    <script type="text/javascript" src="dataTableInit.js"></script>
  </head>
  <body>
    <?php 
      echo '<p>Hello World</p>';
      
      $username = "rami";
      $password = "rami.name";
      $host = "alex.mongohq.com";
      $port = "10091";
      $dbName = "Movies";
      $collectionName = "watched";
      //$proposed = "mongodb://rami:rami.name@alex.mongohq.com:10092/Movies";
      $options = "mongodb://${username}:${password}@${host}:${port}/${dbName}";
      
      echo "<p>mongoHQ mongodb: [${options}]</p>";
      try {
        $m = new Mongo($options);
        $db = $m->selectDB("Movies");
        var_dump($db);
        $collection = $db->watched;

        $query = array();
        $cursor = $collection->find();
        $count = 0;
        foreach ($cursor as $obj) {
          echo $obj["title"] . "\n";
        }
      } catch (Exception $e) {
        var_dump($e);
      }
      
      $token = "cqebxoajr6eueff6j7ax";
      $domain = "https://api.mongohq.com/";
      $url = "https://api.mongohq.com/databases/${dbName}/collections/${collectionName}/documents?_apikey=${token}";
      echo "<br><p>mongoHQ get url: ${url}</p>";
      /*$response = http_get($url, array("timeout"=>1), $info);
      print_r($info);
      echo "<p>${response}<p>";*/
      
      $ch = curl_init( $url );
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $result =  curl_exec($ch); // Getting jSON result string
      echo "<p>" . gettype($result) . "</p>";
      $json = json_decode($result, true);
      var_dump($json);
      
      echo "<p>" . gettype($json) . "</p>";
      
      $count = 0;
      foreach ($json as $movie) {
        $count++;
        echo "<p>Movie #${count}</p>";
        foreach ($movie as $key=>$value) {
          echo "<p>${key}: ";
          if (gettype($value) == "array")
            print_r($value);
          else
            print($value);
          echo "</p>";
        }
      }
      
    ?>
    <div id="insert" class="whitelist">
      <table cellpadding="0" cellspacing="0" border="0" class="display" id="dataTable"></table>
    </div>
  </body>
</html>
