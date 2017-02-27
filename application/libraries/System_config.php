<?php

/**
 * Created by PhpStorm.
 * User: lvxinwei
 * Date: 2016/4/17
 * Time: 20:22
 */
class System_Config {
	const SECRET_KEY = "iot_system!!";
	const DEVICE_TYPE_SWITCH = 1;
	const DEVICE_TYPE_SENSOR = 2;
	const DEVICE_TYPE_MIX = 3;
	static $deviceTypeDesc = array(
		self::DEVICE_TYPE_SWITCH => '单控制器',
		self::DEVICE_TYPE_SENSOR => '传感器',
		self::DEVICE_TYPE_MIX    => '控制传感器',
	);
	const DEVICE_RELAY_STATUS_CLOSE = 0;
	const DEVICE_RELAY_STATUS_OPEN = 1;
	static $deviceRelayStatusDesc = array(
		self::DEVICE_RELAY_STATUS_CLOSE => '关闭中',
		self::DEVICE_RELAY_STATUS_OPEN  => '开启中',
	);
	static $deviceRelayStatusButtonClass = array( 0 => 'btn-info', 1 => 'btn-warning', );
	static $deviceNetStatusButtonClass = array(
		0 => 'btn-danger',
		1 => 'btn-danger',
		2 => 'btn-danger',
		3 => 'btn-danger',
		4 => 'btn-danger',
		5 => 'btn-danger',
		6 => 'btn-danger',
		7 => 'btn-primary',
		);
	static $chineseNum = array( '零', '一', '二', '三', '四', '五', '六', '七', '八', '九', '十', '十一', '十二', '十三', '十四', '十五' );
	const MESSAGE_IN = "message_in_box";
	const MESSAGE_OUT = "message_out_box";
	const MESSAGE_WEB = 'message_web';
	//socket程序消息
	const MESSAGE_TYPE_NOTICE = 1;
	#芯片消息
	const MESSAGE_TYPE_DEVICE = 2;
	#服务器发送的消息
	#重启网络
	const MESSAGE_TYPE_REBOOT = 3;
	#命令消息
	const MESSAGE_TYPE_COMMAND = 4;
	const DEVICE_NET_STATUS_OFFLINE = 0;
	const DEVICE_NET_STATUS_ONLINE = 1;
	#客户端关闭连接
	const DEVICE_CONNECTION_CLOSE = 2;
	#客户端消息超时，服务器关闭连接
	const DEVICE_CONNECTION_TIMEOUT = 3;
	#通信出错，服务器关闭连接
	const DEVICE_CONNECTION_ERROR = 4;
	#服务端主动关闭连接，重启网路
	const DEVICE_CONNECTION_REBOOT = 5;
	#认证失败
	const DEVICE_CONNECTION_UNAUTHORIZED = 6;
	#连接成功
	const DEVICE_CONNECTION_CONNECTED = 7;
	static $deviceNetStatusDesc = array(
		self::DEVICE_NET_STATUS_OFFLINE => '离线中',
		self::DEVICE_NET_STATUS_ONLINE  => '在线中',
		self::DEVICE_CONNECTION_CLOSE=>'设备断线',
		self::DEVICE_CONNECTION_TIMEOUT=>'超时断线',
		self::DEVICE_CONNECTION_ERROR=>'通信故障',
		self::DEVICE_CONNECTION_REBOOT=>'设备重启',
		self::DEVICE_CONNECTION_UNAUTHORIZED=>'未授权',
		self::DEVICE_CONNECTION_CONNECTED=>'正常',
	);
	//风险趋向型
	const IRRIGATION_TYPE1=1;
	//风险回避型：
	const IRRIGATION_TYPE2=2;
	//风险中庸型
	const IRRIGATION_TYPE3=3;
    const IRRIGATION_TYPE4=4;
	static $irrigationTypeDesc = array(
		self::IRRIGATION_TYPE1=>"风险趋向型",
		self::IRRIGATION_TYPE2=>"风险回避型",
		self::IRRIGATION_TYPE3=>"风险中庸型",
        self::IRRIGATION_TYPE4=>"对照组",
	);
    const IRRIGATION_OFF=0;
    const IRRIGATION_ON=1;



}


