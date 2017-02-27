<?php
/**
 * Created by PhpStorm.
 * User: Worker
 * Date: 2016/9/23
 * Time: 10:21
 */
require_once realpath(dirname(__FILE__)) . "/../Library/php-selector/selector.php";
require_once realpath(dirname(__FILE__)) . "/../Library/HttpClient.php";
require_once realpath(dirname(__FILE__)) . "/../Library/MysqliDb.php";

class Irrigation {
    private $_db;
    private $_redis;
    private $_httpClient;

    public function __construct() {
        $this->_db = new MysqliDb(Array(
            'host' => '127.0.0.1',
            'username' => 'root',
            'password' => 'xinweiustc',
            'db' => 'smartirrigation',
            'port' => 3306,
            'prefix' => '',
            'charset' => 'utf8',
        ));
        $this->_httpClient = new HttpClient();

    }
    public function run() {
        $data = $this->_db->where("net_status=7")->where("device_id like '200%'")->get("device");
        foreach ($data as $row) {
            $deviceId = $row['device_id'];
            $row['irrigation_max'] = json_decode($row['irrigation_max'], true);
            $row['irrigation_min'] = json_decode($row['irrigation_min'], true);
            $row['irrigation_unit'] = json_decode($row['irrigation_unit'], true);
            $row['irrigation_type'] = json_decode($row['irrigation_type'], true);
            $row['irrigation_on'] = json_decode($row['irrigation_on'], true);
            $row['editor_value'] = json_decode($row['editor_value'], true);
            $row['relay_value'] = json_decode($row['relay_value'], true);
            print_r($row);
        }
    }


}

$class = new Irrigation();
$class->run();