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
    error_reporting(E_ERROR);
    /********
     ** Main Program
     **
     **
     */
	session_start();


    include("/algorithms/recommend.php");
    include("/algorithms/inputRecommendation.php");
    include("/algorithms/eventsRecommendation.php");

	/** Verificando se a sessao e a localizacao estao OK's **/


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

    $location = explode("|", $_COOKIE['location']);

    switch ($_GET['tipo']) {
        case 0:
            $recommend = new Recommendation_short_term($location[0], $location[1]);
            $events = $recommend->recommendation(50);
            break;

        case 1:
            $fburl = "https://graph.facebook.com/me?fields=id,name,music&access_token=".$_SESSION['fb_access_token'];
            $json_user = file_get_contents($fburl, false);
            $user = json_decode($json_user, true);
            $artists = array();

            foreach ($user['music']['data'] as $user_artist) {
                array_push($artists, $user_artist['id']);
            }

            $recommend = new Recommendation_long_term($location[0], $location[1], $artists);
            $events = $recommend->recommendation(50);
            $recommend->destruct();
            break;

        case 2:
            $fburl = "https://graph.facebook.com/me?fields=id,name,events&access_token=".$_SESSION['fb_access_token'];
            $json_user = file_get_contents($fburl, false);
            $user = json_decode($json_user, true);

            $r = new HistoricRecomendation($user['id'],$user['events']['data'],"black_onion");
            $events = $r->recommendation(50);

            break;

        case 3:
            $recommend = new Recommendation_long_term_by_input($_GET['busca']);
            $events = $recommend->recommendation(50);
            break;
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
            padding-left: 15px;
            padding-right: 15px;
            padding-top: 5px;
            padding-bottom: 5px;
            cursor: pointer;
            /* Permalink - use to edit and share this gradient: http://colorzilla.com/gradient-editor/#12ffd4+0,12ffd4+0,14bafc+100 */
            background: #12ffd4; /* Old browsers */
            background: -moz-linear-gradient(top,  #12ffd4 0%, #12ffd4 0%, #14bafc 100%); /* FF3.6+ */
            background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#12ffd4), color-stop(0%,#12ffd4), color-stop(100%,#14bafc)); /* Chrome,Safari4+ */
            background: -webkit-linear-gradient(top,  #12ffd4 0%,#12ffd4 0%,#14bafc 100%); /* Chrome10+,Safari5.1+ */
            background: -o-linear-gradient(top,  #12ffd4 0%,#12ffd4 0%,#14bafc 100%); /* Opera 11.10+ */
            background: -ms-linear-gradient(top,  #12ffd4 0%,#12ffd4 0%,#14bafc 100%); /* IE10+ */
            background: linear-gradient(to bottom,  #12ffd4 0%,#12ffd4 0%,#14bafc 100%); /* W3C */
            filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#12ffd4', endColorstr='#14bafc',GradientType=0 ); /* IE6-9 */
        }
        #list-events {
            padding: 20%;
            padding-top: 0px;
            padding-bottom: 0px;
            margin-top: 50px;
            max-width: 1200px;
        }
        .title-list-events {
            color: #949494;
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

    <script type="text/javascript">
        $(document).ready(function() {
            $('.navbar-header').click(function() {
                $(location).attr('href','/');
            });
        });

    </script>

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
                    <div class="image-list-container"><img src="<?php echo $event['cover']; ?>" class="event-picture"></div>
                    <p class="event-name"><?php echo $event['name']; ?></p>
                    <p>Confirmados: <?php echo $event['attending_count']; ?></p>
                    <?php $time = explode("T", substr($event['start_time'], 0, 19)); ?>
                    <p>Horario: <?php echo date('l jS \of F Y h:i:s A', strtotime($time[0]." ".$time[1])); ?></p>
                    <a href="https://www.facebook.com/<?php echo $event['id_event']; ?>" target="_blank">Mais informações</a>
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