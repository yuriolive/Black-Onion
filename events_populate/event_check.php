<?php
/* set deprecated on evets that already happend */

$my_connect = mysql_connect("localhost","root","");
if (!$my_connect) { die('Error connecting to the database: ' . mysql_error()); }
mysql_select_db("blackonion", $my_connect);
mysql_query("SET NAMES 'utf8'");
mysql_query('SET character_set_connection=utf8');
mysql_query('SET character_set_client=utf8');
mysql_query('SET character_set_results=utf8');
mb_internal_encoding("UTF-8");

$query_db = "SELECT id,start_time FROM tbevents;";
$events_res = mysql_query($query_db, $my_connect);
if($events_res === FALSE) { die(mysql_error()); }

while($events_row = mysql_fetch_array($events_res)) {
  if($events_row['start_time'] < time()) {
    $query_db = "UPDATE tbevents SET deprecated = true WHERE id = " . $events_row['id'];
    $ok = mysql_query($query_db, $my_connect);
    if($ok === FALSE) { die(mysql_error()); }
  }
}

mysql_close($my_connect);
?>