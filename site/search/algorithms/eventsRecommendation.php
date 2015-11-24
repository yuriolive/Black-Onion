<?php

      include("/AhoCorasick.php");
      include("/TreeNodes.php");

class HistoricRecomendation implements IRecommendation
{ 


       protected $id_user, $eventos, $my_connect, $lat_user, $long_user, $artistas ;

       function __construct($id_user, $eventos, $bd_name){
                $this->id_user = $id_user;
                $this->my_connect = mysql_connect("localhost","root","");
                if (!$this->my_connect) {
                   die('Error connecting to the database: ' . mysql_error());
                }
                $this->eventos = $eventos ;
                mysql_select_db($bd_name, $this->my_connect);
                mysql_query("SET NAMES 'utf8'");
                mysql_query('SET character_set_connection=utf8');
                mysql_query('SET character_set_client=utf8');
                mysql_query('SET character_set_results=utf8');
                mb_internal_encoding("UTF-8");
                $this->artistas = mysql_query("select nome_artista, id_fb, id_spotify FROM artista WHERE id_fb is not null", $this->my_connect);
       }
       
       function getKeywords() {
                $keywords = array();
	            if( $this->artistas === FALSE) { die(mysql_error()); }
                $i = 0;
	            while( $artista = mysql_fetch_array($this->artistas) ) {
                      $keywords[$i]['keyword'] =  strtolower($artista['nome_artista']);
                      $keywords[$i]['id_fb'] = $artista['id_fb'];
                      $keywords[$i]['id_spotify'] = $artista['id_spotify'];
                      $i++;
	            }
                return $keywords;
       }
       
       function cmp($a, $b) {
	            return $a["attending_count"] < $b["attending_count"];
       }
       
       function findWords( $inputText ){

                $filePath = dirname(__FILE__) . '/serializedData'.$this->id_user.'.dat';
                $memoryWhole = memory_get_usage();

                if (!file_exists($filePath) || (isset($_GET['force']))) {
                       $ac = new AhoCorasick();
                       $ac->setCombineResults(false);
                       $keywords = $this->getKeywords();
                       $tree = $ac->buildTree($keywords);
                }
                else {
                       $ac = unserialize(file_get_contents($filePath));
                }
                $res = $ac->FindAll($inputText);
                unset($ac);
                $i = 0;
                $res_final = array();
                while ($i < count($res) ){
                      if ( !in_array($res[$i][1],$res_final) ) array_push($res_final, $res[$i][1]);
                      $i++;
                }
                return $res_final ;
       }
       
       public function recommendation( $num ){
              $artitas_eventos = array();
              if (is_array($this->eventos)){
                 foreach ( $this->eventos as $event ){
                      $res =  $this->findWords($event['description']);
                      $tam = count($res);
                      for ( $i = 0 ; $i < $tam ; $i++ ){
                          if ( !in_array($res[$i],$artitas_eventos))
                             array_push($artitas_eventos, $res[$i]);
                      }
                 }
              }
              $artistas_eventos_final = array() ;
              
              foreach ( $artitas_eventos as $a ){
                      array_push( $artistas_eventos_final, $a['id_fb']);
                      $artistas_semelhantes = mysql_query("select A.id_fb FROM artista A, assemelha_artista AA where AA.id_spotify_super = ".$a['id_spotify']." and A.id_spotify = AA.id_spotify_sub and A.id_fb is not null",$this->my_connect);
                      if ($artistas_semelhantes){
                             while ( $artis_row = mysql_fetch_assoc($artistas_semelhantes) ){
                                       if (!in_array($artis_row['id_fb'],$artitas_eventos_final) )
                                          array_push($artitas_eventos_final, $artis_row['id_fb']);
                             }
                      }
              }
              $eventos_res_final = array();
              foreach( $artistas_eventos_final as $af ){
                       $sql_b = "select E.id_event, E.name, E.start_time, E.end_time, E.attending_count, E.ticket_uri, E.description, E.id_page, E.preco_medio, E.cover, E.deprecated FROM tbevents E,tbevents_artista EA WHERE EA.id_fb_artista = ".$af." and E.id_event = EA.id_event" ;
                       $eventos_res = mysql_query($sql_b, $this->my_connect);
                       if ($eventos_res ){
                            while ( $event_row = mysql_fetch_assoc($eventos_res) ) {
                                   if (!in_array($event_row,$eventos_res_final) )
                                       array_push($eventos_res_final, $event_row);
                            }
                       }
              }
              $eventos_retorno = array();
              for ($i = 0 ; $i < $num ; $i++)
                  array_push($eventos_retorno,$eventos_res_final[$i]);

              usort($eventos_retorno, array("HistoricRecomendation", "cmp"));
              return $eventos_retorno ;
       }
}
?>