<?php
include "../timezone.php";

interface IRecommendation {
  function recommendation($num);
}

class Recommendation_short_term implements IRecommendation {
    protected
      $user_id,// facebook identifier of the user
      $lat,    // current latitude of the user
      $lng,    // current longitude of the user
      $events, // array of events to analyze (filds id,lat,lng,time)
      $my_connect; // conection with mysql

  function __construct($user_id, $lat, $lng) {
    $this->user_id = $user_id;
    $this->$lat = $lat;
    $this->lng = $lng;

    $my_connect = mysql_connect("localhost","root","");
    if (!$my_connect) { die('Error connecting to the database: ' . mysql_error()); }
    mysql_select_db("blackonion", $my_connect);
    mysql_query("SET NAMES 'utf8'");
    mysql_query('SET character_set_connection=utf8');
    mysql_query('SET character_set_client=utf8');
    mysql_query('SET character_set_results=utf8');
    mb_internal_encoding("UTF-8");
    date_default_timezone_set(get_nearest_timezone($lat, $lng, 'BR'));
    reset();
  }

  public function __destruct() {
    mysql_close($my_connect);
  }

  public function reset() {
    $query_db = "SELECT id_event,start_time,id_page FROM tbevents ORDER BY attending_count DESC;";
    $events_res = mysql_query($query_db, $my_connect);
    if($events_res === FALSE) { die(mysql_error()); }

    $query_db = "SELECT id,lat,lng FROM tbpagefb;";
    $places_res = mysql_query($query_db, $my_connect);
    if($places_res === FALSE) { die(mysql_error()); }

    $places = array();
    while($places_row = mysql_fetch_array($places_res)) {
      $places[$places_row['id']]['lat'] = $places_row['lat'];
      $places[$places_row['id']]['lng'] = $places_row['lng'];
    }

    while($events_row = mysql_fetch_array($events_res)) {
      $id = $events_row['id_event'];
      $events[$id]['lat'] = $places[$events_row['id_page']]['lat'];
      $events[$id]['lng'] = $places[$events_row['id_page']]['lng'];
      $events[$id]['time'] = $events_row['start_time'];
    }

  }

  /* recommend event in a short term
     parameters: num -> required number of events
                 time -> maximum time in seconds for accept the event
                 distance -> maximum distance in unit of latitude or longitude for accept the event
     return: array of ids from recommended events
  */
  protected function short_term($num, $time = NULL, $distance = 0.1) {
    if(!isset($time)) $time = time() + 3600 * 24 * 7;
    $cont = 0;
    $recommendation = array();
    foreach($events as $key => $event) {
        if($cont == $num) break;
        $event['time'] = substr($event['time'],0,-1);
        $event_time = strtotime($event['time']);
        if($event_time < $time) {
            if(isset($lat) and isset($lng)) { 
              if(abs($event['lat'] - $lat) < $distance and  abs($event['lng'] - $lng) < $distance) {
                $recommendation[$event_time] = $key;
              }
            } else {
              $recommendation[$event_time] = $key;
            }
        }
        unset($event[$key]);
    }
    ksort($recommendation);
    return $recommendation;
  }


  /*
     recommend events
     parameters: type -> type of recommendation (1 = short term and 2 = long term)
                 num -> required number of events
     return: array of next ids from recommended events
   */
  public function recommendation($num = 20) {

    if($type == 1) $recommendation = short_term($num);
    else $recommendation = long_term($num);

    return $recommendation;
  }
}

class Recommendation_long_term extends Recommendation_short_term {
   private
      $artistas;
  
  function __construct() {
      parent::__construct();
      reset2();
  }
    
  public function reset() {
    parent::reset();
    reset2();
  }

  public function reset2() {

    /* find artists directly releted with the user */
    $artistas = array();
    $query_db = "SELECT id_artista FROM tbusuario_artista WHERE id_usuario = $user_id;";
    $artistas_res = mysql_query($query_db, $my_connect);
    if($artistas_res === FALSE) { die(mysql_error()); }
    while($artistas_row = mysql_fetch_array($artistas_res)) {
      array_push($artistas, $artistas_row['id_artista']);
    }

    /* find artists indirectly releted with the user */
    $query_db = "SELECT a2.id_fb FROM tbusuario_artista ua, artista a1, artista a2, assemelha_artista aa
    WHERE $user_id = ua.id_usuario and ua.id_artista = a1.id_fb and a1.id_spotify = aa.id_spotify_super and aa.id_spotify_sub = a2.id_spotify";
    $artistas_res = mysql_query($query_db, $my_connect);
    if($artistas_res === FALSE) { die(mysql_error()); }
    while($artistas_row = mysql_fetch_array($artistas_res)) {
      array_push($artistas, $artistas_row['id_fb']);
    }
    
    $artistas = array_unique($artistas);
  }

  /* recommend event in a long term
     parameter: num -> required number of events
     return: array of ids from recommended events
   */
  private function long_term($num) {
    $recommendation = array();

    foreach($artistas as $artista) {
      $query_db = "SELECT id_event FROM tbevents_artista WHERE $artista = id_fb_artista";     
      $events_res = mysql_query($query_db, $my_connect);
      if($events_res === FALSE) { die(mysql_error()); }
      while($events_row = mysql_fetch_array($events_res)) {
        if(isset($events[$events_row['id_event']])) {
          array_push($recommendation, $events_row['id_event']);
          unset($events[$events_row['id_event']]);
        }
      }
    }

    if (count($recommendation) < $num) {
      $time = time() * 3600 * 24 * 30;
      $recommendation2 = short_term($num - count($recommendation), $time, 0.5);
      $recommendation = array_merge($recommendation, $recommendation2);
    }

    return $recommendation;
  }
}
?>