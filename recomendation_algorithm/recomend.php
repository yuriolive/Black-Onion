<?php
/* Implementacao incompleta do algoritimo para recomendacoes */

/* recommend event in a short term
   parameters: user_id -> facebook identifier of the user
               lat -> current latitude of the user
               lng -> current longitude of the user
               events -> array of events to analyze (filds id,lat,lng,time)
               num -> required number of events
               time -> maximum time in seconds for accept the event
               distance -> maximum distance in unit of latitude or longitude for accept the event
   return: array of ids from recommended events
*/
function short_term($user_id, $lat, $lng, $events, $num, $time = NULL, $distance = 0.1) {
    if(!isset($time)) $time = time() + 3600 * 24 * 7;
    $cont = 0;
    $recommendation = array();
    foreach($events as $event) {
        if($cont == $num) break;
        $event_time = strtotime($event['time']);
        if($event_time < $time and abs($event['lat'] - $lat) < $distance and  abs($event['lng'] - $lng) < $distance) {
          $recommendation[$event_time] = $event['id'];
        }
    }
    ksort($recommendation);
    return $recommendation;
}

/* recommend event in a long term
   parameters: user_id -> facebook identifier of the user
               events -> array of events to analyze (filds id,lat,lng,time)
               my_connect -> connection to the database
               ... (incompleto)
   return: array of ids from recommended events
 */
function long_term($user_id, $events, $num, $my_connect) {
    $recommendation = NULL;
    return $recommendation;
}

/*
   recommend events
   parameters: user_id -> facebook identifier of the user
               lat -> current latitude of the user
               lng -> current longitude of the user
               type -> type of recommendation (1 = short term and 2 = long term)
               num -> required number of events
   return: array of ids from recommended events
 */
function get_recommendation($user_id, $lat, $lng, $type, $num = 20) {

  $my_connect = mysql_connect("localhost","root","");
  if (!$my_connect) { die('Error connecting to the database: ' . mysql_error()); }
  mysql_select_db("blackonion", $my_connect);
  mysql_query("SET NAMES 'utf8'");
  mysql_query('SET character_set_connection=utf8');
  mysql_query('SET character_set_client=utf8');
  mysql_query('SET character_set_results=utf8');
  mb_internal_encoding("UTF-8");

  $query_db = "SELECT id FROM tbevents ORDER BY attending_count DESC;";
  $events_res = mysql_query($query_db, $my_connect);
  if($events_res === FALSE) { die(mysql_error()); }

  $query_db = "SELECT id,lat,lng FROM tbpagefb;";
  $places_res = mysql_query($query_db, $my_connect);
  if($places_res === FALSE) { die(mysql_error()); }

  $places = array();
  while($places_row = mysql_fetch_array($places_res)) {
    $pages[$pages_row['id']]['lat'] = $pages_row['lat'];
    $pages[$pages_row['id']]['lng'] = $pages_row['lng'];
  }

  $events = array();
  $i = 0;
  while($events_row = mysql_fetch_array($events_res)) {
    $events[$i]['id'] = $events_row['id'];
    $events[$i]['lat'] = $pages[$events_row['place']]['lat'];
    $events[$i]['lng'] = $pages[$events_row['place']]['lng'];
    $events[$i]['time'] = $events_row['time'];
  }

if($type == 1) $recommendation = short_term($user_id, $lat, $lng, $events, $num);
else $recommendation = long_term($user_id, $events, $num, $my_connect);
  
  mysql_close($my_connect);
  return $recommendation;
}
?>