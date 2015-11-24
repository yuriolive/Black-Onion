<?php

class Recommendation_long_term_by_input {
   private $input;
   private $artistas = array();      // artists that the user may like
   private $connect;
  
  function __construct($input) {    
      $this->input = $input;
      $this->connect = mysql_connect("localhost","root","");
      if (!$this->connect) { die('Error connecting to the database: ' . mysql_error()); }
      mysql_select_db("black_onion", $this->connect);
      mysql_query("SET NAMES 'utf8'");
      mysql_query('SET character_set_connection=utf8');
      mysql_query('SET character_set_client=utf8');
      mysql_query('SET character_set_results=utf8');
      mb_internal_encoding("UTF-8");

      $this->find_artist();
  }
    
    /*public function reset_recommendation() {
      parent::reset_recommendation();        
      $this->find_artist();
      }*/

  /* find artists indirectly releted with the user */
  public function find_artist () {
      $query_db = "SELECT DISTINCT id_fb FROM artista WHERE artista.id_fb IS NOT NULL AND LOWER(nome_artista) LIKE LOWER('%".$this->input."%')" ;
      $artistas_input_res = mysql_query($query_db, $this->connect);
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

    if(is_array($this->artistas)) {
          $str_artista = "(FALSE";
          foreach($this->artistas as $get_artist) {
            $str_artista .= " OR id_fb_artista = '" . $get_artist . "'";
          }
          $str_artista .= " )";

          $query_db = "SELECT DISTINCT e.id_event, e.name, e.start_time, e.end_time, e.attending_count, e.ticket_uri, e.id_page, e.cover FROM tbevents_artista ea, tbevents e WHERE e.deprecated = FALSE AND $str_artista AND ea.id_event = e.id_event ORDER BY e.attending_count DESC";     
          $events_res = mysql_query($query_db, $this->connect);
          if($events_res === FALSE) { die(mysql_error()); }
          while($events_row = mysql_fetch_array($events_res)) {
              array_push($recommendation, $events_row);
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
    return array_unique($recommendation, SORT_REGULAR);
  }
}
?>