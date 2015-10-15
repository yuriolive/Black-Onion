<?php 
function get_nearest_timezone($cur_lat, $cur_long, $country_code = '') {
    $timezone_ids = ($country_code) ? DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $country_code)
                                    : DateTimeZone::listIdentifiers();

    if($timezone_ids && is_array($timezone_ids) && isset($timezone_ids[0])) {

        $time_zone = '';
        $tz_distance = 0;

        //only one identifier?
        if (count($timezone_ids) == 1) {
            $time_zone = $timezone_ids[0];
        } else {

            foreach($timezone_ids as $timezone_id) {
                $timezone = new DateTimeZone($timezone_id);
                $location = $timezone->getLocation();
                $tz_lat   = $location['latitude'];
                $tz_long  = $location['longitude'];

                $theta    = $cur_long - $tz_long;
                $distance = (sin(deg2rad($cur_lat)) * sin(deg2rad($tz_lat))) 
                + (cos(deg2rad($cur_lat)) * cos(deg2rad($tz_lat)) * cos(deg2rad($theta)));
                $distance = acos($distance);
                $distance = abs(rad2deg($distance));
                // echo '<br />'.$timezone_id.' '.$distance; 

                if (!$time_zone || $tz_distance > $distance) {
                    $time_zone   = $timezone_id;
                    $tz_distance = $distance;
                } 

            }
        }
        return  $time_zone;
    }
    return 'unknown';
}

?>


<?php

    /********
     ** Main Program
     **
     **
     */
	session_start();

//var_dump($_SESSION['fb_access_token']);
//var_dump($_COOKIE['location']);

	/** Verificando se a sessao e a localizacao estao OK's **/




	/** Decodificando a posicao **/
	if(isset($_COOKIE['location']) && isset($_SESSION['fb_access_token'])) {
        $position = explode('|', $_COOKIE['location']);
        $lat = $position[0];
        $lng = $position[1];

        $fbtoken = $_SESSION['fb_access_token'];
    } else {
        #abaixo, criamos uma variavel que terá como conteúdo o endereço para onde haverá o redirecionamento:  
        $redirect = "../index.php";

        #abaixo, chamamos a função header() com o atributo location: apontando para a variavel $redirect, que por 
        #sua vez aponta para o endereço de onde ocorrerá o redirecionamento
        header("location:$redirect");
    }

    //$heading = $position[2];

    /***** DEBUG PURPOSE ****/
    if(isset($_GET['lat'])) {
        $lat = $_GET['lat'];
        $lng = $_GET['lng'];
    }
    /***** DEBUG PURPOSE ****/

    // Pinheiros
    //$lat=-23.562814;
    //$lng=-46.6876223;

    // Rio Barra
    //$lat = -22.9976109;
    //$lng = -43.3582234;  

    // Rio Copacabana
    //$lat = -22.9782643;
    //$lng = -43.1921606;

	/** Estabelecendo a conexao com o banco de dados **/
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

    /** Pegando os lugares **/
    $query_db = sprintf("SELECT id FROM tbpagefb WHERE (lat < %f AND lat >= %f AND lng < %f AND lng >= %f) ORDER BY `likes` DESC LIMIT 300", 
    			$lat+0.1, $lat-0.1, $lng+0.1, $lng-0.1);

    $pages = array();
    $pages_res = mysql_query($query_db, $my_connect);
    if($pages_res === FALSE) { die(mysql_error()); }
    while($pages_row = mysql_fetch_array($pages_res)) {
       array_push($pages, $pages_row['id']);
    }

    date_default_timezone_set(get_nearest_timezone($lat, $lng, 'BR'));
    $time = time();
    //var_dump(date(DATE_ATOM, $time));
    $idEvents = array();
    $events = array();
    $number_events = 0;
    while(count($pages) > 0) {
        $query_pages = "";
        $counter = 0;
        foreach ($pages as $key => $page) {
            if($counter++ > 40)
                break;
            $query_pages .= $page . ",";
            unset($pages[$key]);
        }

        $query_pages = substr($query_pages, 0, -1);
        /** TODO Pegar timezone automaticamente **/
        $fburl = "https://graph.facebook.com/events?ids=" . $query_pages . "&since=" . ($time - 60*60) . "&until=" . date("Y-m-d", $time) . "T24:00:00-0300" .
            "&fields=cover,name,place,start_time,owner&access_token=$fbtoken";

        /*
        Debug Porpouse 
        $fburl = "https://graph.facebook.com/events?ids=" . $query_pages . "&since=" . ($time - 60*60) .
        "&fields=cover,name,place,start_time,owner&access_token=$fbtoken";*/

        $json_events = file_get_contents($fburl, false, $context);
        $places_events = json_decode($json_events, true);

        foreach ($places_events as $place_events) {
            foreach ($place_events['data'] as $place_event) {
                if(!in_array($place_event['id'], $idEvents)) {
                    array_push($events, $place_event);
                    array_push($idEvents, $place_event['id']);
                    $number_events++;
                    break;
                }
            }
        }

        if($number_events >= 20) {
            break;
        }
    }
?>


<html>
	<head>
		<title>Black Onion</title>
		<meta charset="UTF-8">
		
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
		
		<!-- Latest compiled and minified CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">

		<!-- Optional theme -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">

        <!-- Google Font -->
        <link href='https://fonts.googleapis.com/css?family=Open+Sans:400,600,700,800,300' rel='stylesheet' type='text/css'>

	</head>

    <style type="text/css">
        body {
            font-family: 'Open Sans', sans-serif;
            font-weight: 400;
            background-color: #333333;
        }

        .event-item {
            -webkit-box-shadow: 0px 0px 27px -4px rgba(0,0,0,0.75);
            -moz-box-shadow: 0px 0px 27px -4px rgba(0,0,0,0.75);
            box-shadow: 0px 0px 27px -4px rgba(0,0,0,0.75);
            width: 100%;
            margin: 20px;
            background-color: #252525;
            padding-bottom: 10px;
            text-align: center;
        }
        p {
            color: white;
        }
        .event-picture {
            width: 100%;
            margin-bottom: 10px;
        }
        .event-name {
            font-weight: 800;
            padding-top: 10px;
        }
        .text {
            color: black;
        }
        .navbar {
            background-color: #252525;
            min-height: 0px !important;
            background-image: none;
            border: transparent;
        }
        #start-logo-img {
            width: 30px;
            height: auto;
            vertical-align: text-top;
            padding-bottom: 2px;
        }
        #start-logo-text {
            margin-left: 8px;
            height: 100%;
            color: #1c1c1c;
            vertical-align: middle;
        }
        .navbar-header {
            margin-left: 100px !important;
            font-weight: 400;
            font-size: 20px;
            background-color: #12FFD4;
            padding-left: 15px;
            padding-right: 15px;
            padding-top: 5px;
            padding-bottom: 5px;
        }
        #list-events {
            padding: 20%;
            padding-top: 0px;
            padding-bottom: 0px;
            margin-top: 50px;
            max-width: 1200px;
        }
        .title-list-events {
            color: white;
            font-weight: 300;
            font-size: 30px;
            margin: 20px;
        }
        .image-list-container {
            overflow:hidden; 
            width: 100%; 
            max-height:300px
        }
    </style>

	<body>

        <nav class="navbar navbar-default navbar-fixed-top">
            <div class="container-fluid">
                <div class="navbar-header">
                    <img src="../logo.png" id="start-logo-img"><span id="start-logo-text">Black Onion</span>
                </div>
            </div>
        </nav>

        <div class="container-fluid" id="list-events">
            <p class="title-list-events">Eventos próximos</p>
            <?php 
            foreach ($events as $event) { ?>
                <div class="event-item">
                    <div class="image-list-container"><img src="<?php echo $event['cover']['source']; ?>" class="event-picture"></div>
                    <p class="event-name"><?php echo $event['name']; ?></p>
                    <p>Local: <?php echo (isset($event['place']['name']) ? $event['place']['name'] : $event['owner']['name']); ?></p>
                    <p>Horario: <?php echo date('l jS \of F h:i:s A', strtotime($event['start_time'])); ?></p>
                    <a href="https://www.facebook.com/<?php echo $event['id']; ?>" target="_blank">Mais informações</a>
                </div>
            <?php
            } ?>
        </div>


        <!-- DEBUG PURPOSE -->
        <form action="index.php" style="margin-top: 50px">
            <input type="number" name="lat" step="any" placeholder="Latitude"><br>
            <input type="number" name="lng" step="any" placeholder="Longitude"><br>
            <input type="submit" value="Mudar posicao">
        </form>
         <!-- DEBUG PURPOSE -->
	</body>
</html>