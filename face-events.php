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
session_start();
include "site/fb-config.php";
require_once __DIR__ . '/facebook-php-sdk-v4-5.0-dev/src/Facebook/autoload.php';

$fb = new Facebook\Facebook([  
  'app_id' => $config['app_id'],  
  'app_secret' => $config['app_secret'],  
  'default_graph_version' => 'v2.4',  
  ]);  

$helper = $fb->getRedirectLoginHelper();  
  
try {  
  $token = $helper->getAccessToken(); 
} catch(Facebook\Exceptions\FacebookResponseException $e) {  
  // When Graph returns an error  
  echo 'Graph returned an error: ' . $e->getMessage();  
  exit;  
} catch(Facebook\Exceptions\FacebookSDKException $e) {  
  // When validation fails or other local issues  
  echo 'Facebook SDK returned an error: ' . $e->getMessage();  
  exit;  
}  

if (!isset($token)) {  
  if ($helper->getError()) {  
    header('HTTP/1.0 401 Unauthorized');  
    echo "Error: " . $helper->getError() . "\n";
    echo "Error Code: " . $helper->getErrorCode() . "\n";
    echo "Error Reason: " . $helper->getErrorReason() . "\n";
    echo "Error Description: " . $helper->getErrorDescription() . "\n";
  } else {  
    header('HTTP/1.0 400 Bad Request');  
    echo 'Bad request';  
  }  
  exit;  
}  

$logFile = fopen("tbface_event.log", "w");

$my_connect = mysql_connect("localhost","root","");
if (!$my_connect) { die('Error connecting to the database: ' . mysql_error()); }
mysql_select_db("blackonion", $my_connect);
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



$query_db = "SELECT id FROM tbpagefb ORDER BY checkins DESC, likes DESC;";
$pages = array();
$pages_res = mysql_query($query_db, $my_connect);
if($pages_res === FALSE) { die(mysql_error()); }
while($pages_row = mysql_fetch_array($pages_res)) {
   array_push($pages, $pages_row['id']);
}

$lat = -22.9045159;
$lng = -47.0634906;

date_default_timezone_set(get_nearest_timezone($lat, $lng, 'BR'));
$time = time();
$idEvents = array();

$second_try = false;
while(count($pages) > 0) {


    $query_pages = "";
    $counter = 0;

    if(!$second_try) {
      foreach ($pages as $key => $page) {
          if($counter++ > 40)
                break;
          $query_pages .= $page . ",";
          unset($pages[$key]);
      }
      $query_pages = substr($query_pages, 0, -1);
    }

    /** TODO Pegar timezone automaticamente **/
    $fburl = "https://graph.facebook.com/events?ids=" . $query_pages . "&since=" . ($time - 60*60) . "&until=" . date("Y-m-d", $time) . "T24:00:00-0300" .
        "&fields=id,likes,attending_count,ticket_uri,timezone,description,cover,name,id_host,start_time,end_time&access_token=$token";

    $json_events = file_get_contents($fburl, false, $context);

    if($json_events === FALSE) {
        fwrite($logFile, "************************\n");
        fwrite($logFile, "ERROR\n");
        fwrite($logFile, "Current place:\n");
        fwrite($logFile, "************************\n");
        sleep(10);
        /* tenta de novo pelo menos uma vez */
        $second_try = !$second_try;
        continue;
    }
    if($second_try) $second_try = false;

    do {
        $places_events = json_decode($json_events, true);

        foreach ($places_events as $place_events => $place_id) {
            foreach ($place_events['data'] as $place_event) {
                if(!in_array($place_event['id'], $idEvents)) {
                    mysql_query("INSERT INTO tbevents VALUES ('" .
                                $place_event['id'] . "','" .
                                $place_event['name'] . "','" .
                                $place_event['start_time'] . "','" .
                                $place_event['end_time'] . "','" .
                                $place_event['likes'] . "','" .
                                $place_event['attending_count'] . "','" .
                                $place_event['ticket_uri'] . "','" .
                                $place_event['timezone'] . "','" .
                                $place_event['description'] . "','" .
                                $place_id . "','" .
                                NULL . "','" .
                                $place_event['cover']['source'] . "''" .
                                ");"
                    );
                    array_push($idEvents, $place_event['id']);
                }
            }
        }

        if(isset($possible_places['paging']['next'])) {
            do {
                $json_events = file_get_contents(
                    $possible_places['paging']['next'] . "&access_token=$token",
                    false,
                    $context
                );
                if($json_events === FALSE) {
                  fwrite($logFile, "************************\n");
                  fwrite($logFile, "ERROR\n");
                  fwrite($logFile, "Current place: $pages[0]\n");
                  fwrite($logFile, "************************\n");
                  sleep(10);
                  /* tenta de novo pelo menos uma vez */
                  $second_try = !$second_try;
                }
            } while($second_try);
        } 
    } while(isset($possible_places['paging']['next']));
}

mysql_close($my_connect);
?>