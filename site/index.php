<?php
session_start();
require_once __DIR__ . '/facebook-php-sdk-v4-5.0-dev/src/Facebook/autoload.php';

$fb = new Facebook\Facebook([
'app_id' => '1629898650614051',
'app_secret' => '6968e432fd1e8753708d37b33f65e101',
'default_graph_version' => 'v2.4',
]);

$helper = $fb->getRedirectLoginHelper();

$permissions = ['email', 'user_events', 'user_likes', 'user_actions.music', 'rsvp_event']; // Optional permissions

$loginUrl = $helper->getLoginUrl('http://52.11.55.27/fb-callback.php', $permissions);

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

	<style>
		body {
			font-family: 'Open Sans', sans-serif;
		}
		#start-logo {
			margin-top: 30px;
			margin-left: 50px;
			font-weight: 100;
			font-size: 30px;
			color: white;
		}
		#start-logo-img {
			width: 40px;
			height: auto;
			vertical-align: text-top;
		}
		#start-logo-text {
			margin-left: 10px;
			height: 100%;
			color: #1c1c1c;
		}
		#start-head {
			background:url('capa.jpg') no-repeat center center;
			height: 100%;
		}
		#start-head-text {
			margin-top: 100px;
			margin-bottom: 70px;
		}
		.text-center {
			color: white;
		}
		#head-first-text {
			font-weight: 600;
			font-size: 50px;
			letter-spacing: -3px;
		}
		#head-second-text {
			font-weight: 800;
			font-size: 70px;
			letter-spacing: -6px;
		}
		#login-facebook {
			padding: 10px;
			background: #3b5998;
			-webkit-border-radius: 10px; */
			border-radius: 10px;
			color: white;
			font-weight: 600;
		}
	</style>


	<script type="text/javascript">	
		function showLocation(position) {
			writeCookie('location', position.coords.latitude + "|" + position.coords.longitude + "|" + position.coords.heading, 1);
		}

		function errorHandler(err) {
			$.getJSON("http://ip-api.com/json", function(result) {
				writeCookie('location', result.lat + "|" + result.lon, 1);
			});
		}

		if(navigator.geolocation){
			// timeout at 60000 milliseconds (60 seconds)
			var options = {timeout:60000};
			navigator.geolocation.getCurrentPosition(showLocation, errorHandler, options);
		} else {
			$.getJSON("http://ip-api.com/json", function(result) {
				writeCookie('location', result.lat + "|" + result.lon, 1);
			});
		}

		function writeCookie(name,value,days) {
			var date, expires;
			if (days) {
				date = new Date();
				date.setTime(date.getTime()+(days*24*60*60*1000));
				expires = "; expires=" + date.toGMTString();
			} else {
				expires = "";
			}
			document.cookie = name + "=" + value + expires + "; path=/";
		}
	</script>
	
	</head>
	<body>
		<div class="container-fluid" id="start-head">
			<div class="row">
				<div class="col-md-6" id="start-logo"><img src="logo.png" id="start-logo-img"><span id="start-logo-text">Black Onion</span></div>
				<div class="col-md-6"></div>
			</div>
			<div class="container-fluid" id="start-head-text">
				<p class="text-center" id="head-first-text">FIQUE POR DENTRO DOS</p>
				<p class="text-center" id="head-second-text">MELHORES ROLES</p>
			</div>
			<div class="container-fluid" id="start-head-login">
				<p class="text-center"><?php echo '<a href="' . htmlspecialchars($loginUrl) . '" id="login-facebook">Log in with <span style="font-weight: 800">Facebook</span>!</a>'; ?></p>
			</div>
		</div>
	</body>
</html>