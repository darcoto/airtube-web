<?php

function time_diff($time){
  $datetime1 = new DateTime();
  $datetime2 = new DateTime($time);
  return $datetime1->diff($datetime2);
}

//--------------------------------
function interval_format($time){
  $interval = time_diff($time);
  if($interval->d){
    $format = '%a days';
    $class='danger';
  }elseif($interval->d){
    $format = '%h hours';
    $class='warning';
  }else{
    $format = '%i min';
    $class='default';
  }
  return '<span class="label label-'.$class.'">'.$interval->format($format).'</span>';
}


//--------------------------------
function format_p($value){
  switch ($value){
    //case $value < 10:$class='default';break;
    case $value < 20:$class='success';break;
    case $value < 30:$class='info';break;
    case $value < 50:$class='warning';break;
    default:$class='danger';break;
  }

  return '<span class="label label-'.$class.'">'.$value.'</span>';
}


function get_color($p1,$p2){
  if(sizeof($p1) && sizeof($p2)){
    return value2color(max($p1->current,$p2->current));
  }
  return '#ffffff';
}

function value2color($value){

  $value = round($value,0);
  $value = round(($value/125)*100,0);
  $value = min($value,100);
  $colors = Array('#00796b','#00796b','#00796b','#00796b','#00796b','#00796b','#00796b','#00796b','#00796b','#00796b','#00796b','#00796b','#00796b','#00796b','#00796b','#00796b','#00796b','#00796b','#107c66','#217f61','#31825d','#428558','#538853','#638b4f','#748e4a','#849245','#959541','#a6983c','#b69b37','#c79e33','#d7a12e','#e8a429','#f9a825','#f9a825','#f7a222','#f69c20','#f5961d','#f3901b','#f28b18','#f18516','#f07f13','#ee7911','#ed730e','#ec6e0c','#eb6809','#e96207','#e85c04','#e75602','#e65100','#e65100','#e54f00','#e54d00','#e44c00','#e44a00','#e44800','#e34700','#e34500','#e24400','#e24200','#e24000','#e13f00','#e13d00','#e03c00','#e03a00','#e03800','#df3700','#df3500','#de3400','#de3200','#de3000','#dd2f00','#dd2d00','#dd2c00','#dc2a00','#dd2c00','#dd2c00','#dd2c00','#dd2c00','#dd2c00','#dd2c00','#dd2c00','#dd2c00','#dd2c00','#dd2c00','#d82906','#d4270d','#d02514','#cb221b','#c72022','#c31e29','#bf1b30','#ba1937','#b6173e','#b21445','#ae124c','#a91053','#a50d5a','#a10b61','#9d0968','#98066f','#940476','#90027d','#8c0084');
  return $colors[$value];
}

function loc_diff($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
{
  // convert from degrees to radians
  $latFrom = deg2rad($latitudeFrom);
  $lonFrom = deg2rad($longitudeFrom);
  $latTo = deg2rad($latitudeTo);
  $lonTo = deg2rad($longitudeTo);

  $lonDelta = $lonTo - $lonFrom;
  $a = pow(cos($latTo) * sin($lonDelta), 2) +
    pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
  $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

  $angle = atan2(sqrt($a), $b);
  return $angle * $earthRadius;
}

function get_param_data($values){
  $avg = null;
  $current = null;
  if(sizeof($values)){
    $current = $values[0];
    $avg = round(array_sum($values) / count($values));
  }
  return (object)Array('current'=>$current,'average'=> $avg,'history'=>$values);
}