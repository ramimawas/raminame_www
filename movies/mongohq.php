<?php

class MongoHQ {
  private $m;
  private $db;
  private $config = array(
    'username' => 'rami',
    'password' => 'rami.name',
    'host' => 'alex.mongohq.com',
    'port' => '10091',
    'dbName' => 'Movies',
    'collectionName' => 'watched'
   );

  public function buildUrl () {
    return "mongodb://" . $this->config['username'] . ":" . $this->config['password'] . "@" . $this->config['host'] . ":" . $this->config['port'] . "/" . $this->config['dbName'];
  }
  
  function __construct($config) {
    if($config == null)
      $config = $this->config;
    foreach($config as $key => $val)
      if ($val != null)
        $this->config[$key] = $val;
    $this->m = new Mongo($this->buildUrl());
    $this->db = $this->m->selectDB($this->config['dbName']);
  }
  
  public function setCollectionName($collectionName) {
    $this->config['collectionName'] = $collectionName;
  }

  private function getCollection($collection=null) {
    if ($collection==null)
      $collection = $this->config['collectionName'];
    return $this->db->selectCollection($collection);
  }
  
  public function findOne($query=null, $collection=null) {
    $results = $this->find($query, $collection, 1);
    $one = null;
    if ($results != null && count($results) > 0)
      $one = $results[0];
    return $one;
  }
  
  public function find($query=null, $collection=null, $limit=-1) {
    $results = array();
    if($query == null)
      $query = array();
    var_dump($query);
    try {
      $collection = $this->getCollection($collection);
      if ($limit != null && $limit != -1)
        $cursor = $collection->find($query)->limit($limit);
      else
        $cursor = $collection->find($query);
      foreach ($cursor as $obj)
        $results[] = $obj;
    } catch (Exception $e) {
      var_dump($e);
    }
    echo "<p>fetched " . count($results) . " items</p></br>";
    return $results;
  }
  
  public function save($row, $collection=null) {
    $this->saveMany(array($row), $collection);
  }
  
  public function saveMany($rows, $collection=null) {
    $collection = $this->getCollection($collection);
    foreach ($rows as $row) {
      try {
        $collection->save($row);
      } catch (Exception $e) {
        var_dump($e);
      }
    }
  }
}
/*
$mongoHq = new MongoHQ();
$results = $mongoHq->find();
foreach ($results as $movie) {
  echo "<p>movie title: " . $movie['title'] . "</p></br>";
}
*/    
?>