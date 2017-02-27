<?php
/**
 * @Copyright (c) 2016 lvxinwei All Rights Reserved.
 * @author          lvxinwei <lvxinwei@lvxinwei.com>
 * @version
 * @desc
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
	public function judgeweather($data,$startNum,$endNum)
	//判断指定天数之内是否出现中雨及以上,出现返回1，不出现返回0
		{
		$WData = $data;
		$count = 0;
		for($i = $startNum;$i < ($endNum + 1);$i++)//针对范围内每一天判断
			{
			if(strpos($WData['weather'.$i], '中雨') !== false)
			{$count++;break;}
			else if(strpos($WData['weather'.$i], '大雨') !== false)
			{$count++;break;}
			else if(strpos($WData['weather'.$i], '暴雨') !== false)
			{$count++;break;}
			}
		if($count == 0)
		{return 0;}
		else
		{return 1;}
		}
    //策略1
    public function smart1($data, $sensorNum) {
        echo "begin smart1 \r\n";
        $irrigationMax = $data['irrigation_max'][$sensorNum];
        $irrigationMin = $data['irrigation_min'][$sensorNum];
        $irrigationType = $data['irrigation_type'][$sensorNum];
        $sensorValue = $data['editor_value'][$sensorNum];
        $relayValue = $data['relay_value'][$sensorNum];
        $irrigationUnit = $data['irrigation_unit'][$sensorNum];
        $weatherData =
            $this->_db->where('city_name', $data['city_name'])->where('time', date("Y-m-d", time() - 24 * 3600))
                ->getOne
                ("weather");
        $need = 0;
        
        if ($weatherData) {
            //三天内有中雨
            if ($this->judgeweather($weatherData,1,3))
            {
                $need = 0;

            } else if ($this->judgeweather($weatherData,4,5)
            ) {
                //4-5天有中雨
                $need = $irrigationUnit / 4;
            } else if ($this->judgeweather($weatherData,6,7)
            ) {
                $need = $irrigationUnit / 2;
            } else {
                $need = $irrigationUnit;
            }
            echo $data['device_id'] . "设备 " . ($sensorNum + 1) . "传感器 需要  " . $need . "\r\n";
            //继电器开
            if ($relayValue) {
                //继电器开,算下上次开时间,到时间没
				$shiqi = 17;
			$recentCommand = $this->_db->where("device_id" , $data['device_id'])->where('command_id < 17')->orderBy("id","desc")->getOne
                ("command_data");
                $recentSendTime = $recentCommand['add_time'];
            	echo "上次开启时间".$recentSendTime."\r\n";
            	echo "目前时间".time()."\r\n";
                //时间到了
                if ((time() - $recentSendTime) >= $need) {
                    $this->send_command($data['device_id'], $data['sign'], '[0,0,0,0]');
                    echo $data['device_id'] . "设备 传感器".($sensorNum+1)." 关闭开关 \r\n";
                    //移交控制权
                    return false;
                } else {

                    return true;
                }
            } else {
                $command = array(0, 0, 0, 0);
                $command[$sensorNum] = 1;
                if ($need > 0) {
                    echo time().":".$data['device_id'] . "设备  传感器".($sensorNum+1)." 开启开关 \r\n";
                    $this->send_command($data['device_id'], $data['sign'], json_encode($command));
                    //锁定控制权
                    return true;
                }
            }
        } else {
            return false;
        }


    }

    //策略2
    public function smart2($data, $sensorNum) {
        echo "begin smart2 \r\n";
        $irrigationMax = $data['irrigation_max'][$sensorNum];
        $irrigationMin = $data['irrigation_min'][$sensorNum];
        $irrigationType = $data['irrigation_type'][$sensorNum];
        $sensorValue = $data['editor_value'][$sensorNum];
        $relayValue = $data['relay_value'][$sensorNum];
        $irrigationUnit = $data['irrigation_unit'][$sensorNum];
        $weatherData =
            $this->_db->where('city_name', $data['city_name'])->where('time', date("Y-m-d", time() - 24 * 3600))
                ->getOne
                ("weather");
        $need = 0;
        if ($weatherData) {
            //1天内有中雨
            if ($this->judgeweather($weatherData,1,1)) {
                $need = 0;

            } else if ($this->judgeweather($weatherData,2,3)
            ) {
                //4-5天有中雨
                $need = $irrigationUnit / 4;
            } else if ($this->judgeweather($weatherData,4,5)
            ) {
                $need = $irrigationUnit / 2;
            } else {
                $need = $irrigationUnit;
            }
            echo $data['device_id'] . "设备 " . ($sensorNum + 1) . "传感器 需要  " .$need. "\r\n";
            //继电器开
            if ($relayValue) {
                //继电器开,算下上次开时间,到时间没
                $shiqi = 17;
				$recentCommand = $this->_db->where("device_id" , $data['device_id'])->where('command_id < 17')->orderBy("id","desc")->getOne
                ("command_data");
                $recentSendTime = $recentCommand['add_time'];
				echo "上次开启时间".$recentSendTime."\r\n";
				echo "目前时间".time()."\r\n";
                //时间到了
                if ((time() - $recentSendTime) >= $need) {
                    $this->send_command($data['device_id'], $data['sign'], '[0,0,0,0]');
                    echo $data['device_id'] . "设备  传感器".($sensorNum+1)." 关闭开关 \r\n";
                    //移交控制权
                    return false;
                } else {

                    return true;
                }
            } else {
                $command = array(0, 0, 0, 0);
                $command[$sensorNum] = 1;
                if ($need > 0) {
                    echo time().":".$data['device_id'] . "设备  传感器".($sensorNum+1)." 开启开关 \r\n";
                    $this->send_command($data['device_id'], $data['sign'], json_encode($command));
                    //锁定控制权
                    return true;
                }
            }


        } else {
            return false;
        }


    }

    //策略3
    public function smart3($data, $sensorNum) {
        echo "begin smart3\r\n";
        $irrigationMax = $data['irrigation_max'][$sensorNum];
        $irrigationMin = $data['irrigation_min'][$sensorNum];
        $irrigationType = $data['irrigation_type'][$sensorNum];
        $sensorValue = $data['editor_value'][$sensorNum];
        $relayValue = $data['relay_value'][$sensorNum];
        $irrigationUnit = $data['irrigation_unit'][$sensorNum];
        $weatherData =
            $this->_db->where('city_name', $data['city_name'])->where('time', date("Y-m-d", time() - 24 * 3600))
                ->getOne
                ("weather");
        $need = 0;
        if ($weatherData) {
            //两天内有中雨
            if ($this->judgeweather($weatherData,1,2)
            ) {
                $need = 0;

            } else if ($this->judgeweather($weatherData,3,4)
            ) {
                //3-4天有中雨
                $need = $irrigationUnit / 4;
            } else if ($this->judgeweather($weatherData,5,7)
            ) {
                $need = $irrigationUnit / 2;
            } else {
                $need = $irrigationUnit;
            }
            echo $data['device_id'] . "设备 传感器". ($sensorNum + 1)." 需要  " . $need . "\r\n";
            //继电器开
            if ($relayValue) {
            //继电器开,算下上次开时间,到时间没
            $shiqi = 17;
			$recentCommand = $this->_db->where("device_id" , $data['device_id'])->where('command_id < 17')->orderBy("id","desc")->getOne
                ("command_data");
            $recentSendTime = $recentCommand['add_time'];
            echo "上次开启时间".$recentSendTime."\r\n";
            echo "目前时间".time()."\r\n";
            //时间到了
            if ((time() - $recentSendTime) >= $need) {
                $this->send_command($data['device_id'], $data['sign'], '[0,0,0,0]');
                echo $data['device_id'] . "设备  传感器".($sensorNum+1)." 关闭开关 \r\n";
                //移交控制权
                return false;
            } else {

                return true;
            }
        } else {
            $command = array(0, 0, 0, 0);
            $command[$sensorNum] = 1;
            if ($need > 0) {
                echo time().":".$data['device_id'] . "设备  传感器".($sensorNum+1)." 开启开关 \r\n";
                $this->send_command($data['device_id'], $data['sign'], json_encode($command));
                //锁定控制权
                return true;
            }
        }

        } else {
            return false;
        }
    }

    //策略4
    public function smart4($data, $sensorNum) {
        echo "begin smart4 \r\n";
        $irrigationMax = $data['irrigation_max'][$sensorNum];
        $irrigationMin = $data['irrigation_min'][$sensorNum];
        $irrigationType = $data['irrigation_type'][$sensorNum];
        $sensorValue = $data['editor_value'][$sensorNum];
        $relayValue = $data['relay_value'][$sensorNum];
        $irrigationUnit = $data['irrigation_unit'][$sensorNum];
        $weatherData =
            $this->_db->where('city_name', $data['city_name'])->where('time', date("Y-m-d", time() - 24 * 3600))
                ->getOne
                ("weather");
        if ($sensorValue < $irrigationMin) {
            $need = $irrigationUnit;
        } else {
            return false;
        }
        echo $data['device_id'] . "设备  传感器".($sensorNum+1)." 需要  " . $need . "\r\n";
        //继电器开

        if ($relayValue) {
            //继电器开,算下上次开时间,到时间没
            $shiqi = 17;
			$recentCommand = $this->_db->where("device_id" , $data['device_id'])->where('command_id < 17')->orderBy("id","desc")->getOne
                ("command_data");
            $recentSendTime = $recentCommand['add_time'];
            echo "上次开启时间".$recentSendTime."\r\n";
            echo "目前时间".time()."\r\n";
            //时间到了
            if ((time() - $recentSendTime) >= $need) {
                $this->send_command($data['device_id'], $data['sign'], '[0,0,0,0]');
                echo $data['device_id'] . "设备  传感器".($sensorNum+1)." 关闭开关 \r\n";
                //移交控制权
                return false;
            } else {

                return true;
            }
        } else {
            $command = array(0, 0, 0, 0);
            $command[$sensorNum] = 1;
            if ($need > 0) {
                echo time().":".$data['device_id'] . "设备  传感器".($sensorNum+1)." 开启开关 \r\n";
                $this->send_command($data['device_id'], $data['sign'], json_encode($command));
                //锁定控制权
                return true;
            }
        }

    }

    public function send_command($deviceId, $sign, $relayStatus) {

        if ($relayStatus != '[0,0,0,0]') {
            //开2007 水泵
            $ret = $this->_httpClient->sendPostData("http://www.smartirrigation.org/api/send_command", array(
                'device_id' => 2007,
                'sign' => '8d9ee8324adbdd79e0e665eb6b6b5e87',
                'set_relay_status' => '[1,0,0,0]',
            ));
        } else {
            //关2007 水泵
            $ret = $this->_httpClient->sendPostData("http://www.smartirrigation.org/api/send_command", array(
                'device_id' => 2007,
                'sign' => '8d9ee8324adbdd79e0e665eb6b6b5e87',
                'set_relay_status' => '[0,0,0,0]',
            ));
        }

        //
        $ret = $this->_httpClient->sendPostData("http://www.smartirrigation.org/api/send_command", array(
            'device_id' => $deviceId,
            'sign' => $sign,
            'set_relay_status' => $relayStatus,
        ));
        return json_decode($ret, true);
    }

    public function get_sensor_data($deviceId, $sign) {
        $ret = $this->_httpClient->sendPostData("http://www.smartirrigation.org/api/send_command", array(
            'device_id' => $deviceId,
            'sign' => $sign,
            'command_id' => 17,
        ));
        return json_decode($ret, true);
    }


    public function run() {
        $data = $this->_db->where("net_status=7")->where("device_id like '200%'")->orderBy('device_id','DESC')->get("device");
        $end = 0;
        foreach ($data as $row) {
            $deviceId = $row['device_id'];
            $row['irrigation_max'] = json_decode($row['irrigation_max'], true);
            $row['irrigation_min'] = json_decode($row['irrigation_min'], true);
            $row['irrigation_unit'] = json_decode($row['irrigation_unit'], true);
            $row['irrigation_type'] = json_decode($row['irrigation_type'], true);
            $row['irrigation_on'] = json_decode($row['irrigation_on'], true);
            $row['editor_value'] = json_decode($row['editor_value'], true);
            $row['relay_value'] = json_decode($row['relay_value'], true);
            if (!is_array($row['irrigation_on'])) {
                continue;
            }
            foreach ($row['irrigation_on'] as $sensorNum => $isOn) {
                if ($isOn) {
                    $irrigationType = $row['irrigation_type'][$sensorNum];
                    $irrigationMin = $row['irrigation_min'][$sensorNum];
                    $irrigationType = $row['irrigation_type'][$sensorNum];
                    $sensorValue = $row['editor_value'][$sensorNum];
                    //如果未进行灌溉达到灌溉下限进行灌溉,否则在灌溉进程中
                    if ($row['relay_value'][$sensorNum] || $sensorValue < $irrigationMin) {
                        //在此插入开始信息
						$r = $this->{"smart" . $irrigationType}($row, $sensorNum);
                        while ($r) {
                            $row['relay_value'][$sensorNum]=1;
                            $r = $this->{"smart" . $irrigationType}($row, $sensorNum);
                            if(!$r){
                                $row['relay_value'][$sensorNum]=0;
                            }
                            //占用该设备,等待结束
                            sleep(10);
						//在此插入结束信息，作为一组；
                        }
                    }

                }

            }


        }
    }


}

$class = new Irrigation();
$class->run();



