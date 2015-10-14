<?php
	session_start();

//var_dump($_SESSION['fb_access_token']);
//var_dump($_COOKIE['location']);

	/** Verificando se a sessao e a localizacao estao OK's **/





	$fbtoken = $_SESSION['fb_access_token'];

	/** Decodificando a posicao **/
	$position = explode('|', $_COOKIE['location']);
	$lat = $position[0];
	$lng = $position[1];
    $heading = $position[2];
    
    // Pinheiros
    //$lat=-23.562814;
    //$lng=-46.6876223;

    // Rio Barra
    //$lat = -22.9976109;
    //$lng = -43.3582234;  

    // Rio Copacabana
    $lat = -22.9782643;
    $lng = -43.1921606;

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

    $time = time();
    
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
            "&fields=cover,name,place,start_time&access_token=$fbtoken";
        //var_dump($fburl);

        $json_events = file_get_contents($fburl, false, $context);
        $places_events = json_decode($json_events, true);

        foreach ($places_events as $place_events) {
            foreach ($place_events['data'] as $place_event) {
                array_push($events, $place_event);
                $number_events++;
            }
        }

        if($number_events >= 10) {
            break;
        }
    }
?>


<html>
	<head>
		<title> | Black Onion | </title>
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
            color: white;
            font-weight: 600;
        }

        .event-item {
            -webkit-box-shadow: 0px 0px 27px -4px rgba(0,0,0,0.75);
            -moz-box-shadow: 0px 0px 27px -4px rgba(0,0,0,0.75);
            box-shadow: 0px 0px 27px -4px rgba(0,0,0,0.75);
            width: 600px;
            margin: 20px;
            background-color: #101010;
            padding: 10px;
            text-align: center;
        }

        .event-picture {
            width: 100%;
            margin-bottom: 10px;
        }
        .event-name {
            font-weight: 800;
        }
    </style>

	<body>

		<!--<nav class="navbar navbar-default navbar-fixed-top">
			<div class="container-fluid">
				<div class="navbar-header">
					<a class="navbar-brand" href="#">
						Brand
						<img alt="Brand" src="...">
					</a>
				</div>
			</div>
		</nav>-->
        <div class="container-fluid">
            <?php 
            foreach ($events as $event) { ?>
                <div class="event-item">
                    <img src="<?php echo $event['cover']['source']; ?>" class="event-picture">
                    <p class="event-name"><?php echo $event['name']; ?></p>
                    <p>Local: <?php echo $event['place']['name']; ?></p>
                    <p>Horario: <?php echo $event['start_time']; ?></p>
                </div>
            <?php
            } ?>
        </div>
	</body>
</html>