<?php

	$my_connect = mysql_connect("localhost","root","");
	if (!$my_connect) {	die('Error connecting to the database: ' . mysql_error()); }

	mysql_select_db("black_onion", $my_connect);

	mysql_query("SET NAMES 'utf8'");
	mysql_query('SET character_set_connection=utf8');
	mysql_query('SET character_set_client=utf8');
	mysql_query('SET character_set_results=utf8');

	mb_internal_encoding("UTF-8");
	$context = stream_context_create(array('http' => array('header'=>'Connection: close\r\n')));

	// https://graph.facebook.com/search?q=watermelon&type=page&access_token=1629898650614051|mrLeYR0bO0ym2eIRnzLKp0NZrxU

	$json = file_get_contents(
					"https://graph.facebook.com/search?q=" .  . "&type=page&access_token=1629898650614051|mrLeYR0bO0ym2eIRnzLKp0NZrxU",
					false,
					$context
				);

	$json_decode = json_decode($json, true);



	// Getting the chart datas
	$query_db = 'SELECT tbfinanceiroglobal.Valor, tbfinanceiroglobal.CodOrigem, tbfinanceiroglobal.CodObra, tbfinanceiroglobal.Status, tbfinanceiroglobal.DataXFim, tbfinanceiroglobal.DataXIn, tborigem.Data, tborigem.Rating FROM tborigem
				INNER JOIN tbfinanceiroglobal 
				ON tborigem.CodOrigem = tbfinanceiroglobal.CodOrigem
				AND tbfinanceiroglobal.CodObra = "' . $query['obra'] . '" 
				ORDER BY `Data`  ASC, tbfinanceiroglobal.DataXFim ASC';

	$result = mysql_query($query_db, $connect);

	if($result === FALSE) { die(mysql_error()); } 

	// Getting all the "Obras"
	while($row_chart = mysql_fetch_array($result)) {
		array_push($chart['Valor'], $row_chart['Valor']);
		array_push($chart['CodOrigem'], $row_chart['CodOrigem']);
		array_push($chart['CodObra'], $row_chart['CodObra']);
		array_push($chart['Data'], $row_chart['Data']);
		array_push($chart['Status'], $row_chart['Status']);
		
		if(strtotime($row_chart['DataXFim']) > strtotime($maior_X))
			$maior_X = $row_chart['DataXFim'];

		array_push($chart['DataXFim'], $row_chart['DataXFim']);
		array_push($chart['DataXIn'], $row_chart['DataXIn']);
		array_push($chart['Rating'], $row_chart['Rating']);
	};

	mysql_close($my_connect);
?>