<html>
<head><title></title></head>
<body>
<?php
    $my_connect = mysql_connect("localhost","root","");
    if (!$my_connect) { die('Error connecting to the database: ' . mysql_error()); }
    mysql_select_db("black_onion", $my_connect);
    mysql_query("SET NAMES 'utf8'");
    mysql_query('SET character_set_connection=utf8');
    mysql_query('SET character_set_client=utf8');
    mysql_query('SET character_set_results=utf8');
    mb_internal_encoding("UTF-8");
    $context = stream_context_create(array('http' => array('header'=>'Connection: close\r\n')));
    
    $token = "CAAXKYloWZBSMBAJTFxoCZAfRVKtnBKDYOSZCjip0RoPKdrZBbALDGSpYRqHSNSYS7p4yKbU7oTop9TpJ90CrgB0lfy6koKKgVZAXemiykpK6i2BFG09lS5Yfm9lCZAPibXR72M0XLZC52JZA8DeF0xdeLL5WZAq7XE0zr8gzMeWKnmZBk3BYX63cLM";
    $json = file_get_contents(
        "https://graph.facebook.com/search?type=placetopic&topic_filter=all&fields=id,name,parent_ids&limit=2000" .
        "&access_token=$token",
        false,
        $context
    );

    $subcategories = json_decode($json, true);

    foreach ($subcategories['data'] as $subcategory) {
        mysql_query("INSERT INTO tbsubcategoriafb (id, name) VALUES (" .
            $subcategory['id'] . ",'" .
            $subcategory['name'] . "'" .
            ");"
        );
    }

    mysql_close();
?>
</body>