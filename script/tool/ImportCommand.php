<?php
/**
 * @Copyright (c) 2016 Rd.Lanjinger.com. All Rights Reserved.
 * @author          lvxinwei <lvxinwei@lvxinwei.com>
 * @version
 * @desc
 */
require_once realpath(dirname(__FILE__)) . "/../Library/MysqliDb.php";
$importDb = new MysqliDb(Array(
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => 'xinweiustc',
        'db' => 'etforecast',
        'port' => 3306,
        'prefix' => '',
        'charset' => 'utf8',
    )
);
$handle=fopen("import.txt","r");
while ($row=fgets($handle)){
    $data=array();
    $row=trim($row);
    $row=explode("\t",$row);
    $data['command_data']=$row[1];
    $data['command_desc']=$row[0];
    $data['command_type']=1;
    $data['response_data']=$row[1];
    $data['user_id']=31;
    $data['device_template_id']=1;
    $data['add_time']=time();
    $data['command_name']='';
    $i=0;

    foreach (json_decode($row[0]) as $value){
        $i++;
        $data['command_name'].=$i;
        if($value){
            $data['command_name'].="开";
        } else {
            $data['command_name'].="关";
        }
    }
    $importDb->insert("command_template",$data);
}

