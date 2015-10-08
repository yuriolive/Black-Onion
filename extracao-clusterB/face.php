<?php
    $logFile = fopen("tbface.log", "w");

    $token = "CAAXKYloWZBSMBAJTFxoCZAfRVKtnBKDYOSZCjip0RoPKdrZBbALDGSpYRqHSNSYS7p4yKbU7oTop9TpJ90CrgB0lfy6koKKgVZAXemiykpK6i2BFG09lS5Yfm9lCZAPibXR72M0XLZC52JZA8DeF0xdeLL5WZAq7XE0zr8gzMeWKnmZBk3BYX63cLM";
    
    $my_connect = mysql_connect("localhost","root","");
    if (!$my_connect) { die('Error connecting to the database: ' . mysql_error()); }
    mysql_select_db("black_onion", $my_connect);
    mysql_query("SET NAMES 'utf8'");
    mysql_query('SET character_set_connection=utf8');
    mysql_query('SET character_set_client=utf8');
    mysql_query('SET character_set_results=utf8');
    mb_internal_encoding("UTF-8");
    $context = stream_context_create(array('http' => array('header'=>'Connection: close\r\n')));
    
    $query_db = "SELECT place_id, name, city, lat, lng FROM tbgoogle WHERE facebook_ID IS NULL OR facebook_ID='0' ORDER BY rating";
    $places_res = mysql_query($query_db, $my_connect);

    if($places_res === FALSE) { die(mysql_error()); }

    while($place = mysql_fetch_array($places_res)) {
        firstSearchType:
        $json = file_get_contents(
                "https://graph.facebook.com/search?center=" . $place['lat'] . "," . $place['lng'] . 
                "&distance=50&fields=id,name,category,category_list,about,bio,description,general_info,location,talking_about_count,were_here_count,likes,phone,cover,website&type=place&access_token=$token",
                false,
                $context
            );  
            
        $possible_places = json_decode($json, true);
        
        if(isset($possible_places['error'])) {
            sleep(1);
            goto firstSearchType;
        }
            
        $count = count($possible_places['data']);
        if($count <= 0) {
            secondSearchType:
            $json = file_get_contents(
                "https://graph.facebook.com/search?center=" . $place['lat'] . "," . $place['lng'] . 
                "&distance=100&fields=id,name,category,category_list,about,bio,description,general_info,location,talking_about_count,were_here_count,likes,phone,cover,website&q=" .
                urlencode($place['name']) .
                "&type=place&access_token=$token",
                false,
                $context
            );  
      
            $possible_places = json_decode($json, true);
            
            if(isset($possible_places['error'])) {
                sleep(1);
                goto secondSearchType;
            }
            
            $count = count($possible_places['data']);
            if ($count > 0) {
                $place_FB = $possible_places['data'][0];
                similar_text($place['name'], $place_FB['name'], $perc);
                for($i = 1; $i < $count; $i++) {
                     similar_text($place['name'], $possible_places['data'][$i]['name'], $auxPerc);
                     if($auxPerc > $perc) {
                        $perc = $auxPerc;
                        $place_FB = $possible_places['data'][$i]['name'];
                     }
                }
                if($perc > 50 && isset($place_FB['likes'])) {
                    mysql_query(
                        "UPDATE tbgoogle SET
                        facebook_ID='" . $place_FB['id'] . "', 
                        likes='" . $place_FB['likes'] . "', 
                        checkins='" . $place_FB['were_here_count'] . "',
                        name_FB='" . $place_FB['name'] . "'
                        WHERE place_id='" . $place["place_id"] . "'
                        ;"
                    );
                } 
            }
        } else {
            $place_FB = $possible_places['data'][0];
            similar_text($place['name'], $place_FB['name'], $perc);
            for($i = 1; $i < $count; $i++) {
                 similar_text($place['name'], $possible_places['data'][$i]['name'], $auxPerc);
                 if($auxPerc > $perc) {
                    $perc = $auxPerc;
                    $place_FB = $possible_places['data'][$i]['name'];
                 }
            }
            if($perc > 50 && isset($place_FB['likes'])) {
                mysql_query(
                    "UPDATE tbgoogle SET
                    facebook_ID='" . $place_FB['id'] . "', 
                    likes='" . $place_FB['likes'] . "', 
                    checkins='" . $place_FB['were_here_count'] . "',
                    name_FB='" . $place_FB['name'] . "'
                    WHERE place_id='" . $place["place_id"] . "'
                    ;"
                );
            } 
        }
    }    
   
    mysql_close($my_connect);
?>
