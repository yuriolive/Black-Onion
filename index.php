<?php
	
	$logFile = fopen("tbgoogle.log", "w");

	function lng_f($fi) {
        $WGS84_a = 6378137;
        $num = pi()*$WGS84_a*abs(cos($fi));
        $den = 180 * sqrt(abs(1 - exp(2)*pow(sin($fi), 2)));
        return $num/$den;
    }
	
	function lat_f($fi) {
        return 111132.954 - 559.822*cos(2*$fi) + 1.175*cos(4*$fi);
	}

	function parseCidadeBairro($str) {
		$ctemp = explode(",", $str);
	    if(isset($ctemp[1])) {
	        $ctemp[1] = substr($ctemp[1], 1);
	        if(isset($ctemp[2])){
	            $ctemp[2] = substr($ctemp[2], 1);
	            if(is_numeric(substr($ctemp[1], 0, 1))) {
	                $c = $ctemp[2];
	                $btemp = explode("-", $ctemp[1]);
	                if(isset($btemp[1])) {
	                    $b = substr($btemp[1], 1);
	                } else {
	                    $b = NULL;
	                    $c = NULL; 
	                }
	            } else {
	                $b = NULL;
	                $c = NULL;
	            }
	        } else {
	            $b = NULL;
	            $c = NULL;
	        }
	    } else {
	        $b = NULL;
	        $c = NULL;
	    }
	    return array($b, $c);
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

	// Keys do Google
	$keyPosicao = 0;
	$keyCounter = 0;
	$key = array(
		"AIzaSyBhMyKiRWLikA9uPL-APsxGqi1U3CJCdFQ",
		"AIzaSyAuGL8iRZ0QCdkwLs-OO-ZCCBn7MzhCku4",
		"AIzaSyD5oEahVob9PzIfthabvAyN7VP9e7k-ByA",
		"AIzaSyCt_pNFzZZoL_Am_uArzYy8v_PwqV31mHY",
		"AIzaSyC6vPqsahgj_xIj3BoQdeBZ-9hVcE1-TP4",
		"AIzaSyClBBPrvtQFDej-DdPjnnVxv-W9vDMf1-o",
		"AIzaSyBYiCewBK7qL2TR6ck_vREPRYFqjUwUsGM",
		"AIzaSyCvcEzsAb3YujPLcPJyjCu778_sXkysATo",
		"AIzaSyAo2Y8Jp5kHcb8-rYk0ag9UilcvJLFilu0"
	);


	/**********************************/
	/**********************************/
	/**********************************/
	/**********************************/
	/**********************************/

	$lat = -23.5620061;
	$lng = -46.6884428;
    $lat_delta = lat_f((float)$lat);
    $lng_delta = lng_f((float)$lng);
    $lat_beg = $lat - 50000/$lat_delta;
    $lng_beg = $lng - 50000/$lng_delta;
    $lat_end = $lat + 50000/$lat_delta;
    $lng_end = $lng + 50000/$lng_delta;
    $lat_center = $lat;
    $lng_center = $lng;
    $lat_radio = 300/$lat_delta;
    $lng_radio = 300/$lng_delta;
   
	fwrite($logFile, "************************\n");
	fwrite($logFile, "STARTED VALUES\n");
	fwrite($logFile, "lat: $lat;\nlat_delta: $lat_delta;\nlat_beg: $lat_beg;\nlat_end: $lat_end;\nlat_center: $lat_center;\nlat_radio: $lat_radio");
	fwrite($logFile, "lng: $lng;\nlng_delta: $lng_delta;\nlng_beg: $lat_beg;\nlng_end: $lat_end;\nlng_center: $lng_center;\nlng_radio: $lng_radio");
	fwrite($logFile, "************************\n");
	fwrite($logFile, "EXECUTED\n");

    $step = 0;
    $direction = 3;
    $max_step = 0;
    $first = false;
    for($lat = $lat_center, $lng = $lng_center; $lat <= $lat_end && $lat >= $lat_beg && $lng <= $lng_end && $lng >= $lng_beg;) {
    	fwrite($logFile, "Current position: (lat: $lat; lng: $lng)\n");
		$haveToken = 0;
		while(1) {

			$keyCounter++;
			if($keyCounter > 950) {
				$keyPosicao++;
				$keyCounter = 0;
			}

			if($keyPosicao > 8) {
				fwrite($logFile, "************************\n");
				fwrite($logFile, "ERROR\n");
				fwrite($logFile, "Current position: (lat: $lat; lng: $lng)\n");
				fwrite($logFile, "Google error: ". $json_decode['status']  ."\n");
				fwrite($logFile, "Key: ". $key[$keyPosicao]  ."\n");
				fwrite($logFile, "KeyPosicao: $keyPosicao\n");
				fwrite($logFile, "Step: $step\n");
				fwrite($logFile, "Direction: $direction\n");
				fwrite($logFile, "Max_step: $max_step\n");
				fwrite($logFile, "--------------------------------------");
				die();
			}

			if($haveToken) {
				$json = file_get_contents(
					"https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=" . $lat . "," . $lng . 
					"&radius=5000&types=bar|night_club|stadium&key=" . $key[$keyPosicao] . "&pagetoken=" . $next_page_token,
					false,
					$context
				);
			} else {
				$json = file_get_contents(
					"https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=" . $lat . "," . $lng .
					"&radius=5000&types=bar|night_club|stadium&key=" . $key[$keyPosicao],
					false,
					$context
				);
			}

			// Iniciando a contagem do tempo
			$starttime = microtime(true);
			
			$json_decode = json_decode($json, true);

			if($json_decode['status'] == "REQUEST_DENIED") {
				fwrite($logFile, "************************\n");
				fwrite($logFile, "ERROR\n");
				fwrite($logFile, "Current position: (lat: $lat; lng: $lng)\n");
				fwrite($logFile, "Google error: ". $json_decode['status']  ."\n");
				fwrite($logFile, "Key: ". $key[$keyPosicao]  ."\n");
				fwrite($logFile, "KeyPosicao: $keyPosicao\n");
				fwrite($logFile, "Step: $step\n");
				fwrite($logFile, "Direction: $direction\n");
				fwrite($logFile, "Max_step: $max_step\n");
				fwrite($logFile, "--------------------------------------");
				$keyPosicao++;
				$keyCounter = 0;
			}

			$locais = $json_decode['results'];

			foreach ($locais as $local) {
				$localizacao = array();
				$localizacao = parseCidadeBairro($local['vicinity']); 
				
				mysql_query(
						"INSERT INTO tbgoogle (place_id, name, lat, lng, rating, country, state, city, bairro, street, type, facebook_ID, likes, checkins, insercao) VALUES (
						'" . $local['place_id'] . "', 
						'" . $local['name'] . "', 
						'" . $local['geometry']['location']['lat'] . "', 
						'" . $local['geometry']['location']['lng'] . "', 
						'" . (isset($local['rating']) ? $local['rating'] : NULL) . "',  
						'Brazil',
						'',
						'" . $localizacao[1] . "',
						'" . $localizacao[0] . "',
						'" . $local['vicinity'] . "',
						'" . $local['types'][0] . "',
						'',
						'',
						'',
						'2'
						);"
				);
			}

			if(isset($json_decode['next_page_token'])) {
				$haveToken = 1;
				$next_page_token = $json_decode['next_page_token'];
			} else {
				break;
			}

			// Calculando o tempo despendido
			/*$endtime = microtime(true);
			$timediff = ($endtime - $starttime) * 1000000;

			if($timediff < 2) {
				sleep(2 - $timediff);
			}*/
			sleep(2);
		}

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
	fclose($logFile);


					/* USANDO API DO OPENSTREETMAP
					sleep(1);
					// Pegando o estado, cidade e pais
					$json_localizacao = file_get_contents(
						"http://nominatim.openstreetmap.org/reverse?format=json&lat=" . 
						$local['geometry']['location']['lat'] . "&lon=" . 
						$local['geometry']['location']['lng'] . "&zoom=18&addressdetails=1",
						false,
						$context
					);
					$localizacao = json_decode($json_localizacao, true);

					mysql_query(
							"INSERT INTO tbgoogle (place_id, name, lat, lng, rating, country, state, city, street, type) VALUES (
							'" . $local['place_id'] . "', 
							'" . $local['name'] . "', 
							'" . $local['geometry']['location']['lat'] . "', 
							'" . $local['geometry']['location']['lng'] . "', 
							'" . $local['rating'] . "',  
							'" . $localizacao['address']['country'] . "',
							'" . $localizacao['address']['state'] . "',
							'" . $localizacao['address']['city'] . "',
							'" . $$local['vicinity'] . "',
							'" . $local['types'][0] . "' 
							);"
					);*/
					
					// Pegando o estado, cidade e pais
					/*$json_localizacao = file_get_contents(
						"https://api.opencagedata.com/geocode/v1/json?q=" .
						$local['geometry']['location']['lat'] . "+" .
						$local['geometry']['location']['lng'] . "&key=ba16f4dc1535dc1ec73462a70593ccd4",
						false,
						$context
					);
					$localizacao = json_decode($json_localizacao, true);

					
					foreach ($locais as $local) {
					mysql_query(
							"INSERT INTO tbgoogle (place_id, name, lat, lng, rating, country, state, city, bairro, street, type) VALUES (
							'" . $local['place_id'] . "', 
							'" . $local['name'] . "', 
							'" . $local['geometry']['location']['lat'] . "', 
							'" . $local['geometry']['location']['lng'] . "', 
							'" . (isset($local['rating']) ? $local['rating'] : null) . "',  
							'Brazil',
							'" . $localizacao['results'][0]['components']['state'] . "',
							'" . $localizacao['results'][0]['components']['city'] . "',
							'" . (isset($localizacao['results'][0]['components']['suburb']) ? $localizacao['results'][0]['components']['suburb'] : null) . "',
							'" . $local['vicinity'] . "',
							'" . $local['types'][0] . "' 
							);"
					);
				}


					*/
					
?>





