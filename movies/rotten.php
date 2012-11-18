<?php

include("mongohq.php");

//api.rottentomatoes.com/api/public/v1.0/?q=Toy%20Story%203&apikey=6k3szrykkcyqjpvxkkzpt2az

$config = array(
  'host' => 'api.rottentomatoes.com/api/public/v1.0/movies.json?',
  'apikey' => '6k3szrykkcyqjpvxkkzpt2az',
  'query' => 'To Story 3'
);


function builUrl() {
  return $config['host'] . 'apikey=' . $config['apikey'] .'&q=' . urlencode($config['apikey']);
}

$url = builUrl();
echo "<p>${url}</p><br/>";
$response = http_get($url, array("timeout"=>1), $info);
var_dump($response);

//$results = MongoHQ.find();

?>