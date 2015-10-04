<?php
	mb_internal_encoding("UTF-8");
	$context = stream_context_create(array('http' => array('header'=>'Connection: close\r\n')));
	$json = file_get_contents(
		"https://maps.googleapis.com/maps/api/place/radarsearch/json?location=-22.8893559,-47.0799138&radius=50000&types=bar&key=AIzaSyD5oEahVob9PzIfthabvAyN7VP9e7k-ByA",
		false,
		$context
	);

	$result = json_decode($json, true);
	$bares = $result['results'];

	$my_connect = mysql_connect("localhost","root","");
	if (!$my_connect) {	die('Error connecting to the database: ' . mysql_error()); }

	mysql_select_db("black_onion", $my_connect);

	mysql_query("SET NAMES 'utf8'");
	mysql_query('SET character_set_connection=utf8');
	mysql_query('SET character_set_client=utf8');
	mysql_query('SET character_set_results=utf8');

	$result = mysql_query('SELECT * WHERE 1=1');
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}



	foreach ($bares as $bar) {
		mysql_query("INSERT INTO tbgoogle (nome, place_id, lat, lng, rua, type) VALUES (
				'" . $bar['name'] . "', 
				'" . $bar['place_id'] . "', 
				'" . $bar['geometry']['location']['lat'] . "', 
				'" . $bar['geometry']['location']['lng'] . "', 
				'" . $bar['vicinity'] . "', 
				'bar')");
	}

	/*foreach ($bares as $bar) {
		mysql_query("INSERT INTO tbgoogle (place_id, lat, lng, type) VALUES (
				'" . $bar['place_id'] . "', 
				'" . $bar['geometry']['location']['lat'] . "', 
				'" . $bar['geometry']['location']['lng'] . "',  
				'bar')");
	}*/
	
	mysql_close($my_connect);

	// NAME PLACE_ID LAT LNG VICINITY

?>