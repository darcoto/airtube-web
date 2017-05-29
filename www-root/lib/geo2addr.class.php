<?php

class geo2addr {
  private $addr_cache;
  private $cache_file = __DIR__.'/../data/addr_cache.txt';

  function __construct() {
    $addr_cache = file_get_contents($this->cache_file);
    if ($addr_cache) {
      $this->addr_cache = json_decode($addr_cache);
    }else{
      $this->addr_cache = new stdClass();
    }
  }

  function finish() {
    file_put_contents($this->cache_file,json_encode($this->addr_cache));
  }

  public function get_addr($geohash,$long, $lat) {
    if(isset($this->addr_cache->$geohash)){
      /* Имаме кеш - използваме го */
      $addr = $this->addr_cache->$geohash;
      $this->debug('use cache for '.$geohash);
    }else {
      /* Няма кеш - теглиме наново */
      $addr = $this->get_addr_from_google($long, $lat);
      /* Добавяме в кеша */
      $this->addr_cache->$geohash = $addr;
      $this->debug('add cache for '.$geohash);
    }

    return $this->parse_addr($addr);
  }

  private function get_addr_from_google($long, $lat) {

    $long = trim($long);
    $lat = trim($lat);

    $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=$lat,$long&key=AIzaSyBj97hu927Xtgd2IOzX26SCJsvEwb0T_As";
    $responce = file_get_contents($url);
    $responce = json_decode($responce);

 if(!is_object($responce)){
   var_dump($responce);
   exit;
 }
    if($responce->status == 'OK') {
      if (sizeof($responce->results)) {
        return $responce->results;
      }
    }
    return Array();
  }

  private function parse_addr($addresses){

    $result = new stdClass();
    $result->city = null;
    $result->street = null;
    $result->street_number = null;

    foreach ($addresses[0]->address_components as $addr) {

      if ($addr->types[0] == 'locality') {
        $result->city = $addr->long_name;
      } elseif ($addr->types[0] == 'route') {
        $result->street = $addr->long_name;
      } elseif ($addr->types[0] == 'street_number') {
        $result->street_number = $addr->long_name;
      }
    }
    return $result;
  }

  function debug($txt){
    error_log($txt);
  }
}