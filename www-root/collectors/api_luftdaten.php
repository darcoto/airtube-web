<?php
/*
 {"software_version": "NRZ-2017-078",
 "sensordatavalues":[
  {"value_type":"SDS_P1","value":"11.40"},
  {"value_type":"SDS_P2","value":"3.92"},
  {"value_type":"BME280_temperature","value":"24.54"},
  {"value_type":"BME280_humidity","value":"31.90"},
  {"value_type":"BME280_pressure","value":"94707.14"},
  {"value_type":"samples","value":"827160"},
  {"value_type":"min_micro","value":"164"},
  {"value_type":"max_micro","value":"1374942"},
  {"value_type":"signal","value":"-78 dBm"}
  ]
}
*/

include('mongo.class.php');
include('influxdb.class.php');
include('GeoHash.php');
include('geo2addr.class.php');

$url = 'http://api.luftdaten.info/v1/sensor/2035/';
$url = 'http://api.luftdaten.info/v1/now/';

//$url = '/tmp/luft.json';
$content = file_get_contents($url);
//file_put_contents('/tmp/luft.json',$content);
$content = json_decode($content);


$db_mongo = (new MongoDB\Client)->dusty->location;
$db = new InfluxDB('dusty');
$addr = new geo2addr();

foreach ($content as $row){
  $tags = Array();
  $tags['location_id'] = $row->location->id;
  $tags['longitude'] = $row->location->longitude;
  $tags['latitude'] = $row->location->latitude;
  $tags['geohash'] = \Lvht\GeoHash::encode($row->location->longitude,$row->location->latitude);
  if($row->location->country) {
    $tags['country'] = $row->location->country;
  }
  if($row->location->country == 'BG') {
    $addr_obj = $addr->get_addr($tags['geohash'], $row->location->longitude, $row->location->latitude);
    $tags['city'] = $addr_obj->city;
    $tags['street'] = $addr_obj->street;
    $tags['street_number'] = $addr_obj->street_number;
    //print_r($tags);
  }
  $tags['sensor_id'] = $row->sensor->id;
  $tags['sensor_type_id'] = $row->sensor->sensor_type->id;
  $tags['sensor_type_name'] = $row->sensor->sensor_type->name;
  $tags['sensor_type_manufacturer'] = $row->sensor->sensor_type->manufacturer;

  $values = Array();
  foreach ($row->sensordatavalues as $val){
    $values[$val->value_type] = $val->value;
  }
  $timestamp = strtotime($row->timestamp).'000000000';
  $db->add_line('feinstaub',$tags,$values,$timestamp);
  
  if($tags['geohash']) {
    $values_mongo = array_merge($tags,$values);
    $values_mongo['location'] = Array(floatval ($tags['latitude']),floatval ($tags['longitude']));
    $values_mongo['last_update'] = new MongoDB\BSON\UTCDateTime((new DateTime())->getTimestamp()*1000);

    $db_mongo->updateOne(
      [
        'geohash' => $tags['geohash']
      ],
      [
        '$set' => $values_mongo,
        '$inc' => ['count' => 1]
      ], 
      [
        'upsert' => true
      ]
    );
  }
}
$addr->finish();
echo $db->push();


