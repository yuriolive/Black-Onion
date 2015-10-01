<?php
	$my_connect = mysql_connect("localhost","root","");
	if (!$my_connect) {	die('Error connecting to the database: ' . mysql_error()); }

	mysql_select_db("black_onion", $my_connect);

	mysql_query("SET NAMES 'utf8'");
	mysql_query('SET character_set_connection=utf8');
	mysql_query('SET character_set_client=utf8');
	mysql_query('SET character_set_results=utf8');

	mb_internal_encoding("UTF-8");
	$context = stream_context_create(array('http' => array('header'=>'Connection: close\r\n')));

	$keyPosicao = 0;
	$keyCounter = 0;
	$key = array(
		"AIzaSyD5oEahVob9PzIfthabvAyN7VP9e7k-ByA",
		"AIzaSyCvcEzsAb3YujPLcPJyjCu778_sXkysATo",
		"AIzaSyAo2Y8Jp5kHcb8-rYk0ag9UilcvJLFilu0",
		"AIzaSyBhMyKiRWLikA9uPL-APsxGqi1U3CJCdFQ",
		"",
		"",
	);




	$lat = "-22.8893559";
	$lng = "-47.0799138";
	$haveToken = 0;
	while(1) {
		$keyCounter++;
		if($keyCounter == 900) {
			$keyPosicao++;
			$keyCounter = 0;
		}

		if($haveToken) {
			$json = file_get_contents(
				"https://maps.googleapis.com/maps/api/place/radarsearch/json?location=" . $lat . "," . $lng . 
				"&radius=5000&types=bar|night_club|stadium&key=" . $key[$keyPosicao] . "&pagetoken=" . $next_page_token,
				false,
				$context
			);
		} else {
			$json = file_get_contents(
				"https://maps.googleapis.com/maps/api/place/radarsearch/json?location=" . $lat . "," . $lng .
				"&radius=5000&types=bar|night_club|stadium&key=" . $key[$keyPosicao],
				false,
				$context
			);
		}

		$json_decode = json_decode($json, true);
		$locais = $json_decode['results'];

		foreach ($locais as $local) {
			mysql_query("INSERT INTO tbgoogle (place_id, name, lat, lng, rating, vicinity, type) VALUES (
					'" . $local['place_id'] . "', 
					'" . $local['name'] . "', 
					'" . $local['geometry']['location']['lat'] . "', 
					'" . $local['geometry']['location']['lng'] . "', 
					'" . $local['rating'] . "',  
					'" . $local['vicinity'] . "', 
					'" . $local['types'][0] . "' 
					);");
		}

		if(isset($json_decode['next_page_token'])) {
			$haveToken = 1;
			$next_page_token = $json_decode['next_page_token'];
		} else {
			break;
		}

		sleep(2);
	}


	mysql_close($my_connect);


// Barao Geraldo -22.8212859,-47.0757828&radius=5000
// "https://maps.googleapis.com/maps/api/place/radarsearch/json?location=-22.8893559,-47.0799138&radius=50000&types=bar&key=AIzaSyD5oEahVob9PzIfthabvAyN7VP9e7k-ByA",	

/*foreach ($bares as $bar) {
		$json_moreinfo = file_get_contents(
			"https://maps.googleapis.com/maps/api/place/details/json?placeid=" . $bar['place_id'] . "&key=AIzaSyD5oEahVob9PzIfthabvAyN7VP9e7k-ByA",
			false,
			$context
		);

		$result_moreinfo = json_decode($json_moreinfo, true);
		$bar_moreinfo = $result_moreinfo['result'];
		
		mysql_query("INSERT INTO tbgoogle (nome, place_id, lat, lng, address, rating, type) VALUES (
				'" . $bar_moreinfo['name'] . "', 
				'" . $bar['place_id'] . "', 
				'" . $bar['geometry']['location']['lat'] . "', 
				'" . $bar['geometry']['location']['lng'] . "', 
				'" . $bar_moreinfo['formatted_address'] . "', 
				'" . $bar_moreinfo['rating'] . "',
				'night_club')"
		);
	}*/
?>



