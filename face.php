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

    $places_res = mysql_query("SELECT name city  FROM tbgoogle");
    $places = mysql_fetch_array($places_res);

    foreach ($places as $place) {
        if(isset($place["street"] && $place["street"] != NULL)) {
            $json = file_get_contents(
            "https://graph.facebook/search?q=". $palce["name"] . $place[street] ."&type=page&access_token=1629898650614051|mrLeYR0bO0ym2eIRnzLKp0NZrxU",
            false,
            $context
            );
        } else {
            $json = file_get_contents(
            "https://graph.facebook/search?q=". $palce["name"] . "Sao Paulo" ."&type=page&access_token=1629898650614051|mrLeYR0bO0ym2eIRnzLKp0NZrxU",
            false,
            $context
            );
        }
    }

    mysql_close($my_connect);
?>