<?php
/**
 * @Copyright (c) 2016 Rd.Lanjinger.com. All Rights Reserved.
 * @author          lvxinwei <lvxinwei@lvxinwei.com>
 * @version
 * @desc
 */
require_once realpath(dirname(__FILE__)) . "/../Library/MysqliDb.php";
$db = new mysqli(
    $host = '127.0.0.1',
    $username = 'root',
    $password = 'xinweiustc',
    $db = 'old_weather',
    $port = 3306
);

function show($array){
    foreach ($array as $row){
        echo "\t".$row;
    }
}
$sql = "select * from forecast where cityname='南昌'";
$db->query("SET character_set_connection=utf8;set names utf8");
$db->set_charset("utf8");
$ret = $db->query($sql,MYSQLI_USE_RESULT);
while ($row = $ret->fetch_assoc()) {
    echo $row['cityname']."\t".$row['time'];
    foreach (json_decode($row['info']) as $key=>$value){
            show($value);
    }
    echo "\n";
    
}
