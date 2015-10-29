<?php
/*
 *  @author Martin Vseticka <vseticka.martin@gmail.com>
 *  
 *  GET parameter "force", force=1 if you don't want to use cache    
 */

require dirname(__FILE__) . "/TextProcessingAlgorithms/AhoCorasick.php";
require dirname(__FILE__) . "/TextProcessingAlgorithms/TreeNodes.php";

$filePath = dirname(__FILE__) . '/serializedData.dat';
$memoryWhole = memory_get_usage();
$inputText = strtolower('Sexta 23 de Outubro a partir das 22h 
ARIANA GRANDE, NICK JONAS, LEA MICHELE, RYAN MURPHY & CHANEL OBERLIN orgulhosamente apresentam
"SCREAM QUEENS & AMERICAN HORROR STORY! POP FICTION ED.ESPECIAL ESQUENTA HALLOWEEN PRÉ ÓRFÃOS! DOUBLE CARAVELA ROXA, FREE BLOODY SHOTS, FREE MARSHMALLOWS & GOSTOSURAS, HORROR MOVIES NO TELÃO! PLUS TAYLOR SWIFT.ARIANA.NICK JONAS.LADY GAGA & MORE!"
Jay
Pink Floyd doidao fodão com Avicii vai tocar hj caraiu e The Doors 50 Cent

RYAN MURPHY (CRIADOR DE GLEE E AMERICAN HORROR STORY), ARIANA GRANDE, NICK JONAS, LEA MICHELE, & "CHANEL OBERLIN" te convidam em primeira mão pra curtir a festa de esquenta HALLOWEEN PRÉ ÓRFÃOS!
SCREAM QUEENS, COM DOUBLE CARAVELA ROXA, FREE BLOODY SHOTS, FREE MARSHMALLOWS & GOSTOSURAS, HORROR MOVIES NO TELÃO! 
E NA PISTA HITS DA POP FICTION E COISAS QUE VOCÊS ADORAM DAS FESTAS DA CASINHA: 
DE TAYLOR SWIFT A MARILYN MANSON, DE ARIANA GRANDE A MICHAEL JACKSON, DE NICK JONAS A EVANSCENCE, DE TEMAS INCIDENTAIS A LADY GAGA!"

:: NO TELÃO, SCREAM QUEENS, AMERICAN HORROR STORY, CLASSICAL HORRO MOVIES!
:: DOUBLE CARAVELA ROXA!
:: FREE BLOODY SHOTS, FREE MARSHMALLOWS & GOSTOSURAS!

POP FICTION = FESTA COM FILMES, SERIADOS, ENTRETENIMENTO, TEMÁTICAS E CULTURA RETRÔ! TUDO JUNTO NUMA FESTA SÓ!

:: NA PISTA!
Trilhas de filmes e seriados, e também o melhor da cultura retrô! de 70s a 00s! de David Bowie a Oasis, de Donna Summer a Aguilera, de Pet Shop Boys a Gigi Dagostino, de ABBA a Spice Girls, de Blondie a Bon Jovi!
e também o melhor do que toca nas festas da casinha! Lady Gaga, Ariana Grande, Pharrel Williams, Lana Del Rey, Jessie J, Imagine Dragons, Katy Perry, Avicii, Kiesza, Clean Bandit, Neon Jungle, Lorde, Ellie Goulding, Calvin Harris, David Guetta, Iggy Azalea, Little Mix, Ke$ha, Disclosure, Florence + The Machine, Daft Punk, Bella Thorne, Nicki Minaj, Demi Lovato, Zedd, One Direction, Bonnie Mckee, Lykke Li, The Wanted, Justin Bieber, Taylor Swift, Marina & The Diamonds, Maroon 5, Bruno Mars, Avril Lavigne, Rita Ora, Rihanna, Britney Spears, Selena Gomez, Shakira, Kylie Minogue, J.Lo, Foster The People, Two Door Cinema Club, Mika, Phoenix, 3OH!3, Cobra Starship, Hyper Crush, Millionaires, The Ready Set, Neon Trees, Train, Kelly Clarkson, The Killers, Gossip, Paramore, Neon Hitch, Cher Lloyd, Coldplay, Yeah Yeah Yeahs & Cia!');


function getKeywords() { 
  $keywords = array();

	$my_connect = mysql_connect("localhost","root","");
	if (!$my_connect) { die('Error connecting to the database: ' . mysql_error()); }
	mysql_select_db("modulo_musias", $my_connect);
	mysql_query("SET NAMES 'utf8'");
	mysql_query('SET character_set_connection=utf8');
	mysql_query('SET character_set_client=utf8');
	mysql_query('SET character_set_results=utf8');
	mb_internal_encoding("UTF-8");

	/** Pegando os artistas **/
	$query_db = "SELECT id_fb, nome_artista FROM artista WHERE id_fb IS NOT NULL ORDER BY popularidade DESC";
	$artistas_res = mysql_query($query_db, $my_connect);
	if($artistas_res === FALSE) { die(mysql_error()); }
  $i = 0;
	while($artista = mysql_fetch_array($artistas_res)) {
	   $keywords[$i]['keyword'] =  strtolower($artista['nome_artista']);
     $keywords[$i]['id_fb'] = $artista['id_fb'];
     $i++;
	}
  
  return $keywords;
}

function memUsage($startMemory, $caption = "") {
    $bytes = memory_get_usage() - $startMemory;
    $kBytes = $bytes / 1024;
    echo "<b>$caption</b> {$kBytes}kB<br />";
}

function saveToCache($tree, $filePath) {
    $fh = fopen($filePath, 'w') or die("can't open file");
    //fwrite($fh, json_encode($tree));
    fwrite($fh, serialize($tree));
    fclose($fh);
    echo 'cache size: ' . (filesize($filePath) / 1024) . " kB<br />";
}

?>

<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
</head>
<body>
  <?php
  
  if (!file_exists($filePath) || (isset($_GET['force']))) {
      $ac = new AhoCorasick();
      $ac->setCombineResults(false);
      memUsage($memoryWhole, "Memory (AC instantiated):");      
      $keywords = getKeywords();
      memUsage($memoryWhole, "Memory (keywords loaded):");
      $tree = $ac->buildTree($keywords);
      memUsage($memoryWhole, "Memory (tree built):");
      //unset($keywords);
      memUsage($memoryWhole, "Memory (keywords unset):");
      saveToCache($ac, $filePath);
      memUsage($memoryWhole, "Memory (result cached):");                                    
  } else {
      $ac = unserialize(file_get_contents($filePath));
  }

  $res = $ac->FindAll($inputText);
  memUsage($memoryWhole, "Memory (after find all):");
  memUsage($memoryWhole, "Memory whole:");
  unset($ac);
  echo "<b>Results: </b><pre>";var_dump($res);echo "</pre>";
  $res_artistas = array();
  foreach ($res as $res_artista) {
    if(!in_array($res_artista[1]["id_fb"], $res_artistas))
      array_push($res_artistas, $res_artista[1]["id_fb"]);
  }

  var_dump($res_artistas);
?>
</body>
</html>