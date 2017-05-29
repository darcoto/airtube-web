<?php

  require_once __DIR__ . "/../vendor/autoload.php";

  class MongoLib{
    
    function __construct(){
      $this->db = (new MongoDB\Client)->dusty;  
    }
    
    static function getInstance(){
      static $db;
      if(!$db){
        $db = new MongoLib();
      }
      return $db;
    }
    
    function get_geohashes($bounds){
      $bounds = explode(',',$bounds);
      $cursor = $this->db->location->find(
        [
          'location' => [
            '$geoWithin' => [
              '$box' => [
                  [floatval($bounds[1]),floatval($bounds[0])],
                  [floatval($bounds[3]),floatval($bounds[2])]
               ]
            ]
          ]
        ],
        [
          'projection' => ['geohash'=>1]
        ]
      );
      $geohashes = Array();
      foreach($cursor as $row){
        $geohashes[] = $row->geohash;
      }
      return $geohashes;
    }
  }
  
