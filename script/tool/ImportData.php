<?php
/**
 * @Copyright (c) 2016 lvxinwei.com. All Rights Reserved.
 * @author          lvxinwei <lvxinwei@lvxinwei.com>
 * @version
 * @desc
 */
require_once realpath(dirname(__FILE__)) . "/../Library/MysqliDb.php";
$sourceDb = new mysqli(
    $host = '127.0.0.1',
    $username = 'root',
    $password = 'xinweiustc',
    $db = 'old_weather',
    $port = 3306
);
$importDb = new MysqliDb(Array(
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => 'xinweiustc',
        'db' => 'smartirrigation',
        'port' => 3306,
        'prefix' => '',
        'charset' => 'utf8',
    )
);
function alias($data) {
    $ret = array();
    for ($i = 1; $i < 16; $i++) {
        $ret['low_temp' . $i] = empty($data['tl' . $i])?100:$data['tl' . $i];
        unset($data['tl' . $i]);
        $ret['high_temp' . $i] = empty($data['th' . $i])?100:$data['th' . $i];
        unset($data['th' . $i]);
        $ret['win' . $i] = empty($data['fl' . $i])?'无':empty($data['fl' . $i]);
        unset($data['fl' . $i]);
        $ret['weather' . $i] = empty($data['weather' . $i])?'无':empty($data['weather' . $i]);
        unset($data['weather' . $i]);
    }
    return $ret;
}
$sql = "select * from weather_15  where time >'2014-01-01'";
$sourceDb->query("SET character_set_connection=utf8;set names utf8");
$sourceDb->set_charset("utf8");
$ret = $sourceDb->query($sql,MYSQLI_USE_RESULT);
while ($row = $ret->fetch_assoc()) {
    $data = alias($row);
    $data['city_id'] = $row['cityid'];
    $data['city_name'] = $row['cityname'];
    $data['time']=$row['time'];
    $r=$importDb->where("city_name",$row['cityname'])->where("time",$row['time'])->get("weather",1);
     if(!$r){
         print_r($data);
         $r=$importDb->insert('weather',$data);
         if(!$r){
             echo $importDb->getLastQuery()."\n";
             exit();
         }
     }
    echo $data['time']."\n";
    echo $i++."\n";
//


}




