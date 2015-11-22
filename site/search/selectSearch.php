<!DOCTYPE html>
<html lang="pt-br">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Black Onion</title>

		<!-- Bootstrap CSS -->
		<link href="//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet">

		<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
		<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
			<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
		<![endif]-->
	</head>
	<body>
		<form action="./index.php" method="GET" style="padding-left: 30px;">
			<h2>Selecione o tipo de recomendação</h2>
			
			<input type="radio" name="tipo" value="0" checked> Curto prazo</input>
			<br>
			<input type="radio" name="tipo" value="1"> Longo prazo</input>
			<br>
			<input type="radio" name="tipo" value="2"> Eventos passados</input>
			<br>
			<input type="radio" name="tipo" value="3"> Busca: </input>
			<input type="text" name="busca" value="">
			<br><br>
			<input type="submit" value="Submit">
		</form>

		<!-- jQuery -->
		<script src="//code.jquery.com/jquery.js"></script>
		<!-- Bootstrap JavaScript -->
		<script src="//netdna.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
	</body>
</html>