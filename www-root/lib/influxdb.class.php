<?php

class InfluxDB{
  private $server = 'http://localhost:8086/';
  private $lines;
  private $db;

  function __construct($db) {
    $this->db = $db;
  }

  function insert($measurement,$tags,$values) {
    $this->add_line($measurement,$tags,$values);
    return $this->push();
  }

  function add_line($measurement,$tags,$values,$timestamp=null){
    $this->lines[] = $measurement . $this->tags_to_string($tags) . $this->values_to_string($values).($timestamp?' '.$timestamp:'');
  }

  function push(){

    $lines = implode("\n",$this->lines);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_URL, $this->server."write?db=".$this->db);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $lines);

    return curl_exec($ch);
  }

  function query($q){

    $url = $this->server."query?pretty=true&db=".$this->db.'&q='.urlencode($q);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_URL, $url);

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if($http_status != 200){
      throw new Exception($response);
    }
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $result['header'] = substr($response, 0, $header_size);
    $result['body'] = json_decode(substr( $response, $header_size ));

    return $result;
  }

  function tags_to_string($tags){
    if(!is_array($tags) || !sizeof($tags)) return '';
    $result = '';
    foreach($tags as $key=>$value){
      $value = str_replace(' ','\ ',$value);
      $result .= ",$key=$value";
    }
    return $result;
  }
  
  function values_to_string($values){
    if(!is_array($values) || !sizeof($values)) return '';
    $result = Array();
    foreach($values as $key=>$value){
      $result[] = "$key=$value";
    }
    return ' '.implode(',',$result);
  }



}
