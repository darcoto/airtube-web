<?php

include('lib/influxdb.class.php');
include('lib/mongolib.class.php');
include('lib/functions.php');

$client_lat  = isset($_GET['lat'])?$_GET['lat']:null;
$client_long = isset($_GET['long'])?$_GET['long']:null;
$bounds      = isset($_GET['bounds'])?$_GET['bounds']:null;

$db = new InfluxDB('dusty');

$mongo_db = MongoLib::getInstance();

$geohashes = $mongo_db->get_geohashes($bounds);

error_log('Found hashes:'.sizeof($geohashes));
if(!sizeof($geohashes)){
  header('Content-Type: application/json');
  echo '[]';
  exit;
}
$query = 'select longitude,latitude,geohash,sensor_id,country,city,street,P1,P2,temperature,humidity from feinstaub where geohash  =~ /'.implode('|',$geohashes).'/ and time > now() - 1d group by geohash order by time desc limit 12';

$result = $db->query($query);

//print_r($result['body']);

foreach ($result['body']->results[0]->series as $v) {
  $columns = $v->columns;

  $c = new stdClass();
  foreach ($columns as $column) {
    if(in_array($column,Array('P1','P2','humidity','temperature'))){
      $c->{$column} = Array();
    }else{
      $c->{$column} = null;
    }
  }

  foreach ($v->values as $row) {
    //var_dump($row);
    $dist = null;
    if($client_lat && $client_long){
      $dist = round(loc_diff($client_lat,$client_long,$c->latitude,$c->longitude),0);
    }
    foreach ($columns as $col_index=>$column){
      $value = $row[$col_index];
      if($value !== null) {
        if(in_array($column,Array('P1','P2','humidity','temperature'))){
          $c->{$column}[] = round($value,0);
        }else if(!isset($c->$column)){
          $c->{$column} = $value;
        }
      }
    }
  }
  $c->P1 = get_param_data($c->P1);
  $c->P2 = get_param_data($c->P2);
  $c->temperature = get_param_data($c->temperature);
  $c->humidity = get_param_data($c->humidity);
  $c->time = date('d.m.Y H:i:s',strtotime($c->time));
  $c->diff = time_diff($c->time)->format('%h:%i:%s');

  $c->dist = $dist;
  $c->color = get_color($c->P1,$c->P2);
  $points[] = $c;

}

usort($points,function($a,$b){
  if ($a->dist == $b->dist) {
    return 0;
  }
  return ($a->dist < $b->dist) ? -1 : 1;
});

error_log('Found sensors:'.sizeof($points));

header('Content-Type: application/json');
echo json_encode($points);