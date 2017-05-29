var plotlayers = {};
var nearest = null;
var current_position = {lat:null,long:null}
var current_marker = null;
var last_update = null;
var zz = 0;
var mymap = null;
var debug_flag = window.location.search.indexOf('debug=1')!=-1?true:false
var debug_log = null;

window.onerror = function(msg, url, line, col, error) {
    var extra = !col ? '' : '\ncolumn: ' + col;
    extra += !error ? '' : '\nerror: ' + error;
    debug("Error: " + msg + "\nurl: " + url + "\nline: " + line + extra);
};

$( document ).ready(function() {
    $('#gstatus').html('get geolocation ...');
    debug_log = $('#debug_log');
    mymap = L.map('mapdiv',{
        fullscreenControl: {
            pseudoFullscreen: true // if true, fullscreen to page width and height
        },
        zoomControl: false});

    L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        subdomains: ['a','b','c'],
        maxZoom: 18,
    }).addTo( mymap );
    
    mymap.prevent_move_event = false;
    mymap.setZoom(14);
    mymap.on('moveend', function() { 
      if(mymap.prevent_move_event === true) return;
      debug('Map move');
      get_points();
    });
    
    $('#return_cp').click(function(){
      mymap.prevent_move_event = true;
      debug('Home');
      //mymap.setView([current_position.lat,current_position.long],14);
        set_map_center();
      mymap.prevent_move_event = false;
    })
    $('#infoclose').click(function () {
        $('#infodiv').hide();
        window.location.hash='';
    });
    if (navigator.geolocation) {
        //navigator.geolocation.getCurrentPosition(function(position){
        navigator.geolocation.watchPosition(function(position){
            debug('Position check');
            var new_position = {lat:position.coords.latitude,long:position.coords.longitude};
            watch_position(new_position);
         }, error);

        setInterval(function () {
            debug('Interval check');
            //get_points();
            watch_position({lat:Number(current_position.lat) + Number(0),long:Number(current_position.long) + Number(0)}); //debug
        },6000);

    } else {
        error('Geolocation not supported or is disabled');
    }
});

function watch_position(new_position){

    debug(new_position.lat);
    debug(current_position.lat);

    var diff = null;
    if(current_position.lat) {
        diff = getDistance(Number(new_position.lat), Number(new_position.long), Number(current_position.lat), Number(current_position.long));
    }

    if(diff !== null && Number(diff) < 50){
        debug(' Same place ('+diff+' m)');
        return
    }
    debug(' Diff ('+diff+' m)');
    current_position.lat = new_position.lat;
    current_position.long = new_position.long;

    set_map_center();
}

function set_map_center(){
  /* Set map to current coordinate */
    mymap.setView([current_position.lat,current_position.long]);
    var plotll = new L.LatLng(current_position.lat,current_position.long, true);
    if(!current_marker){
        var plotmark = new L.Marker(plotll).addTo(mymap);
        current_marker = plotmark;
    }else{
        current_marker.setLatLng(plotll);
    }
    if(current_marker.line){
        mymap.removeLayer(current_marker.line);
    }
}

function get_points(){
    var next_check = new Date();
    next_check.setSeconds(next_check.getSeconds() - 0);

    if(last_update && last_update > next_check){
        debug('Tooo quick');
        return;
    }

    bounds = mymap.getBounds();
    $.ajax({
        url: "location.php?lat="+current_position.lat+'&long='+current_position.long+"&bounds="+bounds.toBBoxString(),
        dataType: "json"
    })
        .done(function (points) {
            var np=0;
            last_update = new Date();
            for (i = 0; i < points.length; i++) {
                var point = points[i];
                if(!plotlayers[point.geohash]) {
                    var plotll = new L.LatLng(point.latitude, point.longitude, true);
                    var plotmark = new L.circle(plotll, {
                        radius: 300,
                        stroke: 0,
                        color: point.color,
                        fillOpacity: 0.8
                    }).on('mouseover', function (e) {
                        set_status(this);
                    });

                    mymap.addLayer(plotmark);
                    np++;
                    plotlayers[point.geohash] = plotmark;
                }else{
                    plotmark = plotlayers[point.geohash];
                }
                plotmark.data = point;
                if(!nearest || nearest.dist > point.dist) {
                    nearest = plotmark;
                }
            }
            debug('Load points: '+points.length+'/'+np);
            if(nearest) {
                set_status(nearest);
                if(current_marker.line){
                    mymap.removeLayer(current_marker.line);
                }
                var pathLine = L.polyline([current_marker.getLatLng(),nearest.getLatLng()],{
                    color: 'blue',
                    weight: 1,
                    opacity: 0.9,
                    dashArray: '5 5'
                }).addTo(mymap);
                current_marker.line = pathLine;
                nearest = null;
            }
        });
}

function error(msg) {
    var s = document.querySelector('#content');
    s.innerHTML = typeof msg == 'string' ? msg : "failed";
    s.className = 'fail';
    debug(arguments);
}

function set_status(point) {
    if(mymap.active){
        mymap.active.setStyle({stroke:0});
    }
    mymap.active = point;
    point.setStyle({stroke:1});

    var cont = '<table id="shortinfo" geohash="' + point.data.geohash + '"><caption>' + point.data.street + '</caption>';
    cont += '<tr><td><i class="fa fa-industry" aria-hidden="true"></i> P10</td><td>' + point.data.P1.current + ' µg/m³ <i class="fa fa-long-arrow-'+get_dir(point.data.P1)+'"  title="'+point.data.P1.average+'"></i></td><td rowspan="5" class="big"><span class="big" style="background: '+point.data.color+'">'+Math.max(Number(point.data.P1.current),Number(point.data.P2.current)).toString()+'</span> µg/m³</td></tr>';
    cont += '<tr><td><i class="fa fa-industry" aria-hidden="true"></i> P2.5</td><td>' + point.data.P2.current + ' µg/m³ <i class="fa fa-long-arrow-'+get_dir(point.data.P2)+'" title="'+point.data.P2.average+'"/></td></tr>';
    cont += '<tr><td><i class="fa fa-thermometer-half" aria-hidden="true"></i> Temp</td><td>' + point.data.temperature.current + ' &deg;C <i class="fa fa-long-arrow-'+get_dir(point.data.temperature)+'" title="'+point.data.temperature.average+'"/></td></tr>';
    cont += '<tr><td><i class="fa fa-bath" aria-hidden="true"></i> Hum</td><td>' + point.data.humidity.current + '% <i class="fa fa-long-arrow-'+get_dir(point.data.humidity)+'" title="'+point.data.humidity.average+'"/></td></tr>';
    cont += '<tr><td><i class="fa fa-clock-o" aria-hidden="true"></i> Time</td><td>' + point.data.diff + '</td></tr>';
    cont += '<tr><td><i class="fa fa-arrows-h" aria-hidden="true"></i> Distance</td><td>' + point.data.dist + 'm</td></tr>';
    cont += '</table>';
    $('#content').html(cont);
    $( "#shortinfo").click(show_info);

}

function get_dir(mes){
    if (mes.current == null){
        dir = 'n/a';
    }else if (mes.current > mes.average){
        dir = 'down';
    }else if (mes.current == mes.average){
        dir = 'eq';
    }else{
        dir = 'up';
    }

    return dir;
}

function show_info(e){
    var geohash = $(this).attr('geohash');
    window.location.hash='#chart-'+geohash;


    $.ajax({
        url: "charts_values.php?geohash="+geohash,
        dataType: "json"
    })
    .done(function (points) {

        var chartData_p = {
            title:{text:'Dust'},
            scaleX: {
                minValue: points.start_time
            },

            series: [
                { values: points.values.P1,text:"P10"},
                { values: points.values.P2,text:"P2.5"}
            ]
        };

        var chartData_t = {
            title:{
                text:'Temperature,Humidity'
            },
            scaleX: {
                minValue: points.start_time
            },
            series: [
                { values: points.values.temperature,"text":"Temeperature"},
                { values: points.values.humidity,"text":"Humidity"}
            ]
        };
        console.log(points.sensor);
        var info = $('#infodiv');
        //info.find('#info_sensor_id').html(points.sensor.sensor_id);
        info.find('#info_street').html(points.sensor.country + ' / ' + points.sensor.city + ' / ' + points.sensor.street);

        show_chart('chart_p',chartData_p)
        show_chart('chart_t',chartData_t)

        $( "#infodiv" ).slideToggle("slow",function () {

        });

    });

}

function show_chart(id,config){

    var default_config = {
        type: "line",
        title:{
            "background-color":"#555",
            "font-color":"#ccc"
        },
        border:"1",
        "plotarea":{
            "background-color":"#fff",
            "margin-left":"25px",
            "margin-right":"15px"

        },
        scaleX:{
            lineColor: '#E3E3E5',
            zooming: false,
            step: '10minute',
            item:{
                fontColor:'#a1a1a1'
            },
            transform:{
                type: 'date',
                all: '%d.%M<br>%H:%i'
            }
        },
        plot:{
            marker:{
                visible:false
            }
        },
        "crosshair-x":{
            "plot-label":{
                "text":"%t: %v"
            }
        }

    };

    zingchart.render({
        id: id,
        data: jQuery.extend(true, default_config,config),
        height: 400,
        width: '100%'
    });
}

function getDistance(lat1,lon1,lat2,lon2) {
    var R = 6371; // Radius of the earth in km
    var dLat = deg2rad(lat2-lat1);  // deg2rad below
    var dLon = deg2rad(lon2-lon1);
    var a =
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
        Math.sin(dLon/2) * Math.sin(dLon/2)
    ;
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    var d = Math.round(R * c*1000,0);
    return d;
}

function deg2rad(deg) {
    return deg * (Math.PI/180)
}

function debug(line) {
    if (debug_flag) {
        if ($.browser.mobile) {
            debug_log.show();
            if(debug_log[0].num == undefined) debug_log[0].num = 1;
            debug_log.append(debug_log[0].num + '.' + line + '<br>');
            debug_log[0].scrollTop = debug_log[0].scrollHeight;
            debug_log[0].num = Number(debug_log[0].num) + 1;
        } else {
            console.log(line)
        }
    }
}