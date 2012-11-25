<?php
$cb = new Couchbase("ec2-50-112-3-209.us-west-2.compute.amazonaws.com:8091"); // uses the default bucket
// or specify a specific bucket like so:
//$cb = new Couchbase("127.0.0.1:8091", "bucket", "pass", "bucket");
$cb->set("rami", 1);
var_dump($cb->get("rami"));
?>