<?php

class MongoHQ {
  private $config = array(
    'username' => 'rami',
    'password' => 'rami.name',
    'host' => 'alex.mongohq.com"',
    'port' => '10091',
    'dbName' => 'Movies',
    'collectionName' => 'watched'
   );

  private function buildUrl () {
    //"mongodb://rami:rami.name@alex.mongohq.com:10092/Movies";
    return "mongodb://${config['username']}:${config['password']}@${config['host']}:${config['port']}/${config['dbName']}";
  }

  private function getCollection($colelction=null) {
    $m = new Mongo(buildUrl());
    $db = $m->selectDB($mongoHQ[$dbName]);
    var_dump($db);
    if ($collection==null)
      $collection = $config['$collectionName'];
    return $db[$collection];
    //return $db->watched;
  }

  public static function find($query=null) {
    $results = array();
    try {
      $collection = $this->getCollection('watched');
      $cursor = $collection->find();
      foreach ($cursor as $obj)
        $results[] = $obj["title"];
    } catch (Exception $e) {
      var_dump($e);
    }
    return $results;
  }
}
?>