<?php

    /** Estabelecendo a conexao com o banco de dados **/
    $my_connect = mysql_connect("localhost","root","");
    if (!$my_connect) { die('Error connecting to the database: ' . mysql_error()); }
    mysql_select_db("modulo_musias", $my_connect);
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

    /** Pegando os artistas **/
    $query_db = sprintf("SELECT id_spotify, nome_artista FROM artista WHERE id_fb IS NULL ORDER BY popularidade DESC");
    //$query_db = sprintf("SELECT id_spotify, nome_artista FROM artista ORDER BY popularidade DESC");

    $artist_res = mysql_query($query_db, $my_connect);
    if($artist_res === FALSE) { die(mysql_error()); }
    while($artist_row = mysql_fetch_array($artist_res)) {
        firstSearchType:
        $json = file_get_contents(
                    "https://graph.facebook.com/search?q=" . urlencode($artist_row['nome_artista'])  . "&type=page&fields=category,likes,best_page,is_verified&access_token=934099479983695|8iLGg90v3hpXztVtLQBxC9eEaSU",
                    false,
                    $context
                );

        if($json === FALSE) {
            sleep(10);
            goto firstSearchType;
        }

        $possible_artists = json_decode($json, true);
        $save_artist = array();

        foreach ($possible_artists['data'] as $possible_artist) {
            if($possible_artist['category'] == "Musician/Band" || $possible_artist['category'] == "Artist" || $possible_artist['category'] == "Public Figure") {
                if($possible_artist['is_verified'] == "true") {
                    $save_artist = $possible_artist;
                    break;
                } else if($save_artist == NULL || $save_artist['likes'] < $possible_artist['likes']) {
                    $save_artist = $possible_artist;
                }
            }
        }

        if(isset($save_artist)) {
            if(isset($save_artist['best_page'])) {
                best_page:
                $json_best = file_get_contents(
                    "https://graph.facebook.com/" . $save_artist['best_page']['id']. "?fields=likes,best_page,is_verified&access_token=934099479983695|8iLGg90v3hpXztVtLQBxC9eEaSU",
                    false,
                    $context
                );

                if($json_best === FALSE) {
                    sleep(10);
                    goto best_page;
                }

                $best_page = json_decode($json_best, true);

                if(isset($best_page['best_page'])) {
                    $save_artist['best_page']['id'] = $best_page['id'];
                    goto best_page;
                }

                mysql_query("UPDATE artista SET id_fb=". $best_page['id'] .", likes=". $best_page['likes'] ." WHERE id_spotify='". $artist_row['id_spotify'] ."'");
            } else {
                mysql_query("UPDATE artista SET id_fb=". $save_artist['id'] .", likes=". $save_artist['likes'] ." WHERE id_spotify='". $artist_row['id_spotify'] ."'");
            }
        }
    }


?>