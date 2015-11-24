<?php
/*
 *  @author Martin Vseticka <vseticka.martin@gmail.com>
 *  
 *  GET parameter "force", force=1 if you don't want to use cache    
 */

ini_set('memory_limit', '-1');

function remover_caracter($string) {
    $string = preg_replace("/[áàâãä]/", "a", $string);
    $string = preg_replace("/[ÁÀÂÃÄ]/", "A", $string);
    $string = preg_replace("/[éèê]/", "e", $string);
    $string = preg_replace("/[ÉÈÊ]/", "E", $string);
    $string = preg_replace("/[íì]/", "i", $string);
    $string = preg_replace("/[ÍÌ]/", "I", $string);
    $string = preg_replace("/[óòôõö]/", "o", $string);
    $string = preg_replace("/[ÓÒÔÕÖ]/", "O", $string);
    $string = preg_replace("/[úùü]/", "u", $string);
    $string = preg_replace("/[ÚÙÜ]/", "U", $string);
    $string = preg_replace("/ç/", "c", $string);
    $string = preg_replace("/Ç/", "C", $string);
    $string = preg_replace("/[][><}{)(:;,!?*%~^`&#@]/", "", $string);
    $string = preg_replace("/ /", "_", $string);
    return $string;
}
 
require dirname(__FILE__) . "/TextProcessingAlgorithms/AhoCorasick.php";
require dirname(__FILE__) . "/TextProcessingAlgorithms/TreeNodes.php";

$filePath = dirname(__FILE__) . '/serializedData.dat';
$memoryWhole = memory_get_usage();


function getKeywords() { 
  $keywords = array();

	$my_connect = mysql_connect("localhost","root","");
	if (!$my_connect) { die('Error connecting to the database: ' . mysql_error()); }
	mysql_select_db("black_onion", $my_connect);
	mysql_query("SET NAMES 'utf8'");
	mysql_query('SET character_set_connection=utf8');
	mysql_query('SET character_set_client=utf8');
	mysql_query('SET character_set_results=utf8');
	mb_internal_encoding("UTF-8");

	/** Pegando os artistas **/
	$query_db = "SELECT id_fb, nome_artista FROM artista WHERE id_fb AND popularidade > 40 IS NOT NULL ORDER BY popularidade DESC";
	$artistas_res = mysql_query($query_db, $my_connect);
	if($artistas_res === FALSE) { die(mysql_error()); }
  $i = 0;
	while($artista = mysql_fetch_array($artistas_res)) {
	   $keywords[$i]['keyword'] =  remover_caracter(strtolower($artista['nome_artista']));
     $keywords[$i]['id_fb'] = $artista['id_fb'];
     $i++;
	}
  
  return $keywords;
}

function memUsage($startMemory, $caption = "") {
    $bytes = memory_get_usage() - $startMemory;
    $kBytes = $bytes / 1024;
    echo "<b>$caption</b> {$kBytes}kB<br />";
}

function saveToCache($tree, $filePath) {
    $fh = fopen($filePath, 'w') or die("can't open file");
    //fwrite($fh, json_encode($tree));
    fwrite($fh, serialize($tree));
    fclose($fh);
    echo 'cache size: ' . (filesize($filePath) / 1024) . " kB<br />";
}
  $my_connect = mysql_connect("localhost","root","");
  if (!$my_connect) { die('Error connecting to the database: ' . mysql_error()); }
  mysql_select_db("black_onion", $my_connect);
  mysql_query("SET NAMES 'utf8'");
  mysql_query('SET character_set_connection=utf8');
  mysql_query('SET character_set_client=utf8');
  mysql_query('SET character_set_results=utf8');
  mb_internal_encoding("UTF-8");
  
  if (!file_exists($filePath) || (isset($_GET['force']))) {
      $ac = new AhoCorasick();
      $ac->setCombineResults(false);
      memUsage($memoryWhole, "Memory (AC instantiated):");      
      $keywords = getKeywords();
      memUsage($memoryWhole, "Memory (keywords loaded):");
      $tree = $ac->buildTree($keywords);
      memUsage($memoryWhole, "Memory (tree built):");
      //unset($keywords);
      memUsage($memoryWhole, "Memory (keywords unset):");
      saveToCache($ac, $filePath);
      memUsage($memoryWhole, "Memory (result cached):");                                    
  } else {
      $ac = unserialize(file_get_contents($filePath));
  }

  $query_db_events = "SELECT id_event, name, description FROM tbevents ORDER BY `attending_count` DESC";
  $events_res = mysql_query($query_db_events, $my_connect);
  
  if($events_res === FALSE) { die(mysql_error()); }
  while($event = mysql_fetch_array($events_res)) {
    $res = $ac->FindAll(remover_caracter(strtolower($event['name'] . "             " . $event['description'])));
    $res_artistas = array();
    foreach ($res as $res_artista) {
      if(!in_array($res_artista[1]["id_fb"], $res_artistas))
        array_push($res_artistas, $res_artista[1]["id_fb"]);
    }

    foreach ($res_artistas as $artista) {
          mysql_query("INSERT INTO tbevents_artista VALUES ('" .
          $artista . "','" .
          $event['id_event'] . "'" .
          ");"
      );
    }
  }
  unset($ac);
?>