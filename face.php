<?php
    $logFile = fopen("tbface.log", "w");

    function number_trim($x) {
        if ($x < 0)
            return ceil($x);
        else
            return floor($x);
    }


	$my_connect = mysql_connect("localhost","root","");
	if (!$my_connect) {	die('Error connecting to the database: ' . mysql_error()); }
    mysql_select_db("black_onion", $my_connect);
    mysql_query("SET NAMES 'utf8'");
	mysql_query('SET character_set_connection=utf8');
	mysql_query('SET character_set_client=utf8');
	mysql_query('SET character_set_results=utf8');
	mb_internal_encoding("UTF-8");
	$context = stream_context_create(array('http' => array('header'=>'Connection: close\r\n')));
    
    $query_db = "SELECT place_id, name, city, lat, lng FROM tbgoogle ORDER BY rating";
    $places_res = mysql_query($query_db, $my_connect);

    if($places_res === FALSE) { die(mysql_error()); }

    while($place = mysql_fetch_array($places_res)) {
        if(isset($place["city"]) && $place["city"] != NULL) {
            $json = file_get_contents(
                "https://graph.facebook.com/search?q=". urlencode($place["name"] . " " . $place["city"]) ."&type=page&access_token=1629898650614051|mrLeYR0bO0ym2eIRnzLKp0NZrxU",
                false,
                $context
            );
        } else {
            $json = file_get_contents(
                "https://graph.facebook.com/search?q=". urlencode($place["name"] . " Sao Paulo") ."&type=page&access_token=1629898650614051|mrLeYR0bO0ym2eIRnzLKp0NZrxU",
                false,
                $context
            );
        }

        $fb_places = json_decode($json, true);

        if(isset($fb_places["error"])) {
            fwrite($logFile, $place["place_id"] . "\n");
            die();
        }
        
        sleep(1);

        foreach ($fb_places["data"] as $fb_place) {
            $json_place = file_get_contents(
                "https://graph.facebook.com/" . $fb_place["id"] . "?access_token=1629898650614051|mrLeYR0bO0ym2eIRnzLKp0NZrxU&fields=location,about,category,name,likes,checkins",
                false,
                $context
            );

            if(isset($json_place["error"])) {
                fwrite($logFile, $place["place_id"] . "\n");
                die();
            }

            $candidate_place = json_decode($json_place, true);
            
            if(number_trim($place["lat"] * 1000) == number_trim($candidate_place["location"]["latitude"] * 1000) && number_trim($place["lng"] * 1000) == number_trim($candidate_place["location"]["longitude"] * 1000)) {
                if(isset($place["city"]) && $place["city"] != NULL) {
                    mysql_query(
                        "UPDATE tbgoogle SET
                        facebook_ID='" . $candidate_place['id'] . "', 
                        likes='" . $candidate_place['likes'] . "', 
                        checkins='" . $candidate_place['checkins'] . "'
                        WHERE place_id='" . $place["place_id"] . "'
                        ;"
                    ); 
                } else {
                    mysql_query(
                        "UPDATE tbgoogle SET
                        city='" . $candidate_place['location']['city'] . "', 
                        facebook_ID='" . $candidate_place['id'] . "', 
                        likes='" . $candidate_place['likes'] . "', 
                        checkins='" . $candidate_place['checkins'] . "'
                        WHERE place_id='" . $place["place_id"] . "'
                        ;"
                    );
                }
                break;
            }

            sleep(1);
        }
    }    
   
    mysql_close($my_connect);
?>