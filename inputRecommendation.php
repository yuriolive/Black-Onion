<?php

class Recommendation_short_term_by_input {
    protected
      $lat,        // current latitude of the user
      $lng,        // current longitude of the user
      $events,     // array of events to analyze (filds id,lat,lng,time)
      $my_connect, // connection with mysql
      $prev_atten, // represent last element returned from mysql query
      $places;     // array of places

    function __construct($lat, $lng) {
        $this->lat = $lat;
        $this->lng = $lng;

        $this->my_connect = mysql_connect("localhost","root","");
        if (!$this->my_connect) { die('Error connecting to the database: ' . mysql_error()); }
        mysql_select_db("black_onion", $this->my_connect);
        mysql_query("SET NAMES 'utf8'");
        mysql_query('SET character_set_connection=utf8');
        mysql_query('SET character_set_client=utf8');
        mysql_query('SET character_set_results=utf8');
        mb_internal_encoding("UTF-8");

        $this->reset_recommendation();
    }

    public function reset_recommendation() {
        $this->prev_atten = PHP_INT_MAX;
        $this->events = array();

        $query_db = "SELECT id,lat,lng FROM tbpagefb;";
        $places_res = mysql_query($query_db, $this->my_connect);
        if($places_res === FALSE) { die(mysql_error()); }

        $this->places = array();
        while($places_row = mysql_fetch_array($places_res)) {
          $this->places[$places_row['id']]['lat'] = $places_row['lat'];
          $this->places[$places_row['id']]['lng'] = $places_row['lng'];
        }
    }

  public function __destruct() {
    mysql_close($this->my_connect);
  }
    
  protected static function cmp($a, $b) {
    return $a['attending_count'] > $b['attending_count'];
  }

  public function find_events($num) {
    $query_db = "SELECT id_event,start_time,id_page,attending_count FROM id_event  tbevents WHERE  attending_count < $this->prev_atten ORDER BY attending_count DESC  LIMIT $num;";
    $events_res = mysql_query($query_db, $this->my_connect);
    if($events_res === FALSE) { die(mysql_error()); }

    while($events_row = mysql_fetch_array($events_res)) {
      $id = $events_row['id_event'];
      $this->events[$id]['id'] = $id;
      $this->events[$id]['attending_count'] = $events_row['attending_count'];
      $this->events[$id]['lat'] = $this->places[$events_row['id_page']]['lat'];
      $this->events[$id]['lng'] = $this->places[$events_row['id_page']]['lng'];
      $this->events[$id]['time'] = $events_row['start_time'];
    }
    usort($this->events, array('Recommendation_short_term','cmp'));
    $this->prev_atten = end($this->events)['attending_count'];
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
    while ($cont < $num) {
        if(empty($this->events)) $this->find_events($num);
        if(empty($this->events)) break;
        foreach($this->events as $key => $event) {
            $event['time'] = substr($event['time'],0,-1);
            $event_time = strtotime($event['time']);
            if($event_time < $time) {
                if(isset($lat) and isset($lng)) { 
                  if(abs($event['lat'] - $lat) < $distance and  abs($event['lng'] - $lng) < $distance) {
                    $recommendation[$event_time] = $event['id'];
                    $cont++;
                  }
                } else {
                  $recommendation[$event_time] = $event['id'];
                  $cont++;
                }
            }
            unset($this->events[$key]);
        }
    }
    ksort($recommendation);
    return $recommendation;
  }


  /*
     recommend events
     parameters: num -> required number of events
     return: array of next ids from recommended events
   */
  public function recommendation($num = 20) {
    $recommendation = $this->short_term($num);
    return $recommendation;
  }

}

class Recommendation_long_term_by_input extends Recommendation_short_term_by_input {
   private
       $input,
       $artistas = array();        // artists that the user may like
  
  function __construct($lat, $lng, $input) {
      parent::__construct($lat, $lng);      
      $this->input = $input;
      $this->find_artist();
  }
    
    /*public function reset_recommendation() {
      parent::reset_recommendation();        
      $this->find_artist();
      }*/

  /* find artists indirectly releted with the user */
    public function find_artist () {
    $query_db = "SELECT id_fb FROM artista WHERE artista.id_fb IS NOT NULL AND LOWER(nome_artista) LIKE LOWER('%".$this->input."%')" ;
    $artistas_input_res = mysql_query($query_db, $this->my_connect);
    if($artistas_input_res === FALSE) { die(mysql_error()); }
    while($artistas_row = mysql_fetch_array($artistas_input_res)) {
        array_push($this->artistas, $artistas_row['id_fb']);
    }
  }

  /* recommend event in a long term
     parameter: num -> required number of events
     return: array of ids from recommended events
   */
  private function long_term($num) {
    $recommendation = array();

    $my_connect = mysql_connect("localhost","root","");
    if (!$this->my_connect) { die('Error connecting to the database: ' . mysql_error()); }
    mysql_select_db("black_onion", $this->my_connect);
    mysql_query("SET NAMES 'utf8'");
    mysql_query('SET character_set_connection=utf8');
    mysql_query('SET character_set_client=utf8');
    mysql_query('SET character_set_results=utf8');
    mb_internal_encoding("UTF-8");

    if(is_array($this->artistas)) {
        foreach($this->artistas as $artista) {
          $query_db = "SELECT e.id_event, e.name, e.start_time, e.end_time, e.attending_count, e.ticket_uri, e.id_page, e.cover FROM tbevents_artista ea, tbevents e WHERE e.deprecated = FALSE AND $artista = id_fb_artista AND ea.id_event = e.id_event ORDER BY e.attending_count DESC";     
          $events_res = mysql_query($query_db, $my_connect);
          if($events_res === FALSE) { die(mysql_error()); }
          while($events_row = mysql_fetch_array($events_res)) {
              array_push($recommendation, $events_row);
            }
          }
        }

    return $recommendation;
  }

  /*
     recommend events
     parameters: num -> required number of events
     return: array of next ids from recommended events
   */
  public function recommendation($num = 20) {
    // use long term recommendation
    $recommendation = $this->long_term($num);
    // complemented with short term recommendation
    /*if (count($recommendation) < $num) {
        $time = time() * 3600 * 24 * 30;
        $recommendation2 = $this->short_term($num - count($recommendation), $time, 0.5);
        $recommendation = array_merge($recommendation, $recommendation2);
        }*/
    return $recommendation;
  }
}
?>