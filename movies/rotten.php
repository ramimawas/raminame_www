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
$ch = curl_init( $url );
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result =  curl_exec($ch); // Getting jSON result string
echo "<p>" . gettype($result) . "</p>";
$json = json_decode($result, true);
var_dump($json);


//$results = MongoHQ.find();

?>