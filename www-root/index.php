<!DOCTYPE html>
<html lang="en">
<head>

  <title>Dusty Map</title>

  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.0.3/dist/leaflet.css" integrity="sha512-07I2e+7D8p6he1SIM+1twR5TIrhUQn9+I6yjqD53JQjFiMf8EtC93ty0/5vJTZGF8aAocvHYNEDJajGdNx1IsQ==" crossorigin=""/>
  <link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="css/main_index.css?<?php echo time();?>"/>
  <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
  <script src="https://unpkg.com/leaflet@1.0.3/dist/leaflet.js" integrity="sha512-A7vV8IFfih/D732iSSKi20u/ooOfj/AGehOKq0f4vLT1Zr2Y+RX7C+w8A1gaSasGtRUZpF/NZgzSAu4/Gc41Lg==" crossorigin=""></script>
  <script src= "js/zingchart.min.js"></script>
  <script src="js/main_index.js?<?php echo time();?>"></script>
  <script src="js/mobile_detect.js?<?php echo time();?>"></script>
</head>
<body>
<div id="content">
  <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
  <span id="gstatus">Loading...</span>
</div>
<div id="mapdiv"></div>
<div id="return_cp"><i class="fa fa-map-marker" aria-hidden="true"></i></div>
<div id="infodiv" style="display: none">
  <div class="inner">
    <div id="infoclose">
      <i class="fa fa-window-close-o fa-3x" aria-hidden="true"></i>
    </div>
    <div class="head">
      <h3>Location: <span id="info_street"></span></h3>
    </div>
    <div class="chart" id="chart_p"></div>
    <div class="chart" id="chart_t"></div>
  </div>
</div>
<div id="debug_log" style="display:none"></div>

<script type="text/javascript">
var clicky_site_ids = clicky_site_ids || [];
clicky_site_ids.push(101048078);
(function() {
  var s = document.createElement('script');
  s.type = 'text/javascript';
  s.async = true;
  s.src = '//static.getclicky.com/js';
  ( document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0] ).appendChild( s );
})();
</script>
<noscript><p><img alt="Clicky" width="1" height="1" src="//in.getclicky.com/101048078ns.gif" /></p></noscript>
</body>
</html>
