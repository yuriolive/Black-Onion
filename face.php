<?php
    $logFile = fopen("tbface.log", "w");

    function lng_f($fi) {
        $WGS84_a = 6378137;
        $num = pi()*$WGS84_a*abs(cos($fi));
        $den = 180 * sqrt(abs(1 - exp(2)*pow(sin($fi), 2)));
        return $num/$den;
    }
    
    function lat_f($fi) {
        return 111132.954 - 559.822*cos(2*$fi) + 1.175*cos(4*$fi);
    }

   // $token = "CAAXKYloWZBSMBAJTFxoCZAfRVKtnBKDYOSZCjip0RoPKdrZBbALDGSpYRqHSNSYS7p4yKbU7oTop9TpJ90CrgB0lfy6koKKgVZAXemiykpK6i2BFG09lS5Yfm9lCZAPibXR72M0XLZC52JZA8DeF0xdeLL5WZAq7XE0zr8gzMeWKnmZBk3BYX63cLM";
    
   // $token = "CAAXKYloWZBSMBAFVPMITJrZAUoIgsuVL6jw0lSStiNg56XDWeSCc7TG7Wyfudc5apdOvjPrGwsHlbCLgvIIZC07OBWwRZBNbl1TmYTjb5ZBaBbWkOtvYGln7TPZCBqrCkZA41ZA3KtnKFzBowWc3nUsCeheIAWZAu9cY6ZAEMWF2QaQuoTKcf8v28MJm7m1jZB2iOrlWNa0ZCaSSNlHI1INTuZCbEuuJWkQj0W1kZD";

    $token = "CAAXKYloWZBSMBADbnOQyglMcdsqXQpPgk2bDPeZChDjnTj8tVs3IoWaKc5wNJ5ZAqcmZBmKT5cslOL6YrDxxe46wUx24tH8dNXldBH8OH4Jt2EixKtdf70EFSkNijzguGVmGTsJoxZC6gDQomUxMFzm4xCs0MbW6rAZBQK7bqPz5GhTzHmLW60";

    $my_connect = mysql_connect("localhost","root","");
    if (!$my_connect) { die('Error connecting to the database: ' . mysql_error()); }
    mysql_select_db("black_onion", $my_connect);
    mysql_query("SET NAMES 'utf8'");
    mysql_query('SET character_set_connection=utf8');
    mysql_query('SET character_set_client=utf8');
    mysql_query('SET character_set_results=utf8');
    mb_internal_encoding("UTF-8");
    $context = stream_context_create(
        array(
            'http' => array(
                'method' => "GET",
                'header'=>'Connection:keep-alive\r\n'
            )
        )
    );
    
    $categorias = array();
    $subcategorias = array();

    /** Pegando as categorias **/
    $query_db = "SELECT name FROM tbcategoriafb WHERE important=1";
    $categorias_res = mysql_query($query_db, $my_connect);
    if($categorias_res === FALSE) { die(mysql_error()); }
    while($categoria_res = mysql_fetch_array($categorias_res)) {
       array_push($categorias, $categoria_res['name']);
    }
   
     /** Pegando as subcategorias **/
    $query_db = "SELECT id FROM tbsubcategoriafb WHERE important=1";
    $subcategorias_res = mysql_query($query_db, $my_connect);
    if($subcategorias_res === FALSE) { die(mysql_error()); }
    while($subcategoria_res = mysql_fetch_array($subcategorias_res)) {
       array_push($subcategorias, $subcategoria_res['id']);
    }

    $lat = -23.6994;
    $lng = -46.7146;
    $lat_delta = lat_f((float)$lat);
    $lng_delta = lng_f((float)$lng);
    $lat_beg = $lat - 50000/$lat_delta;
    $lng_beg = $lng - 50000/$lng_delta;
    $lat_end = $lat + 50000/$lat_delta;
    $lng_end = $lng + 50000/$lng_delta;
    $lat_center = $lat;
    $lng_center = $lng;
    $lat_radio = 500/$lat_delta;
    $lng_radio = 500/$lng_delta;

    $step = 0;
    $direction = 3;
    $max_step = 0;
    $first = false;

    for($lat = $lat_center, $lng = $lng_center; $lat <= $lat_end && $lat >= $lat_beg && $lng <= $lng_end && $lng >= $lng_beg;) {
        /** Pegando os lugares **/
        firstSearchType:
        $json = file_get_contents(
                "https://graph.facebook.com/search?center=" . $lat . "," . $lng . 
                "&distance=500&fields=id,name,category,category_list,about,bio,description,general_info,location,talking_about_count,were_here_count,likes,phone,cover,website&type=place&limit=5000&access_token=$token",
                false,
                $context
            );  
        loop:
        
        if($json === FALSE) {
            fwrite($logFile, "************************\n");
            fwrite($logFile, "ERROR\n");
            fwrite($logFile, "Current position: (lat: $lat; lng: $lng)\n");
            fwrite($logFile, "Step: $step\n");
            fwrite($logFile, "Direction: $direction\n");
            fwrite($logFile, "Max_step: $max_step\n");
            fwrite($logFile, "************************\n");
            sleep(10);
            goto firstSearchType;
        }

        $possible_places = json_decode($json, true);

        foreach ($possible_places['data'] as $possible_place) {
            if($possible_place['category'] == "Local business") {
                foreach ($possible_place['category_list']  as $place_category) {
                    if(in_array($place_category['id'], $subcategorias)) {
                        mysql_query("INSERT INTO tbpagefb VALUES ('" .
                            $possible_place['id'] . "','" .
                            $possible_place['name'] . "','" .
                            $possible_place['category'] . "','" .
                            $possible_place['were_here_count'] . "','" .
                            $possible_place['likes'] . "','" .
                            $possible_place['location']['latitude'] . "','" .
                            $possible_place['location']['longitude'] . "','" .
                            $possible_place['location']['city'] . "','" .
                            $possible_place['location']['state'] . "','" .
                            $possible_place['location']['country'] . "'" .
                            ");"
                        );
                        break;
                    }
                }
            } else if(in_array($possible_place['category'], $categorias)) {
                mysql_query("INSERT INTO tbpagefb VALUES ('" .
                    $possible_place['id'] . "','" .
                    $possible_place['name'] . "','" .
                    $possible_place['category'] . "','" .
                    $possible_place['were_here_count'] . "','" .
                    $possible_place['likes'] . "','" .
                    $possible_place['location']['latitude'] . "','" .
                    $possible_place['location']['longitude'] . "','" .
                    $possible_place['location']['city'] . "','" .
                    $possible_place['location']['state'] . "','" .
                    $possible_place['location']['country'] . "'" .
                    ");"
                );
            }
        }

        if(isset($possible_places['paging']['next'])) {
            $json = file_get_contents(
                $possible_places['paging']['next'] . "&access_token=$token",
                false,
                $context
            );
            goto loop;
        } 


        /** Movendo para o proximo lugar **/
        if($step == $max_step) {
            $direction++;
            $step = 0;
            if ($direction == 4) {
                $direction = 0;
                $lng += $lng_radio;
                $lat += $lat_radio;
                $max_step += 2;
                $first = true;
            }
        }
        if(!$first) {
            switch($direction) {
            case 0:
                $lat -= $lat_radio;
                break;
            case 1:
                $lng -= $lng_radio;
                break;
            case 2:
                $lat += $lat_radio;
                break;
            case 3:
                $lng += $lng_radio;
                break;
            }
            $step++;
        } else {
            $first = false;
        }
    }

    mysql_close($my_connect);
?>
