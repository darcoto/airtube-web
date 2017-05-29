<?php

include('lib/exceptions.php');
include('lib/influxdb.class.php');
include('lib/functions.php');

$geohash  = isset($_GET['geohash'])?$_GET['geohash']:null;
$client_long = isset($_GET['geohash'])?$_GET['geohash']:null;

$db = new InfluxDB('dusty');

$sensor_data = $db->query('select longitude as long,latitude, country,city,street,P1 from feinstaub where geohash = \''.$geohash.'\' order by time desc limit 1');

if(!isset($sensor_data['body']->results)){
  throw new Exception('No sensor');
}

$sensor = new stdClass();
foreach ($sensor_data['body']->results[0]->series[0]->columns as $col_index=>$column){
  $value = $sensor_data['body']->results[0]->series[0]->values[0][$col_index];
  $sensor->$column = $value;
}

$result = $db->query('select mean(P1) as P1, mean(P2) as P2, mean(temperature) as temperature, mean(humidity) as humidity from feinstaub where geohash = \''.$geohash.'\' and time > now() - 1d group by time(10m) order by time ');

$columns = $result['body']->results[0]->series[0]->columns;
$values = Array();

foreach ($result['body']->results[0]->series[0]->values as $row_ind=>$row) {
  foreach ($columns as $col_index=>$column){
    $value = $row[$col_index];
    $values[$column][] = round($value);
  }
}

$sensor->time = date('d.m.Y H:i:s',strtotime($sensor->time));
$start_time = sizeof($result['body']->results[0]->series[0]->values)?$result['body']->results[0]->series[0]->values[0][0]:null;
unset($values['time']);

$data = new stdClass();
$data->start_time = strtotime($start_time).'000';
$data->values = $values;
$data->sensor = $sensor;

echo json_encode($data);