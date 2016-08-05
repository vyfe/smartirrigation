<?php
/**
 * Created by PhpStorm.
 * User: lvxinwei
 * Date: 2016/4/20
 * Time: 22:54
 */
require_once realpath( dirname( __FILE__ ) ) . "/../Library/HttpClient.php";
require_once realpath( dirname( __FILE__ ) ) . "/../Library/MysqliDb.php";
require_once realpath( dirname( __FILE__ ) ) . "/../Library/predis/autoload.php";;
class GetSensorData {
	private $_db;
	private $_redis;
	private $_httpClient;

	public function __construct() {
		$this->_db    = new MysqliDb( Array(
			'host'     => '127.0.0.1',
			'username' => 'root',
			'password' => 'xinweiustc',
			'db'       => 'smartirrigation',
			'port'     => 3306,
			'prefix'   => '',
			'charset'  => 'utf8',
		) );
		$this->_redis = new Predis\Client();

	}


	public function run() {
		$data=$this->_db->where("net_status=7")->get("device");
		foreach($data as $key=>$value){
			$this->send_command($value['device_id'],17);
		}


	}

	public function send_command( $deviceId, $commandId ) {
		$commandTemplateData = $this->_db->where( "command_id", $commandId )->getOne( "command_template" );
		$this->_db->insert( "command_data", array(
			'device_id'     => $deviceId,
			'command_id'    => $commandTemplateData['command_id'],
			'add_time'      => time(),
			'command_data'  => $commandTemplateData['command_data'],
			'response_data' => $commandTemplateData['response_data'],
		) );
		$redisKey = "message_out_box_" . $deviceId;
		$value    = array(
			'type' => 4,
			'time' => time(),
			'data' => $commandTemplateData['command_data'],
		);
		$this->_redis->rpush( $redisKey, json_encode( $value ) );
	}
}

$class = new GetSensorData();
$class->run();

