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

    //策略1
    public function smart1($data, $sensorNum) {
        echo "begin smart1";
        $irrigationMax = $data['irrigation_max'][$sensorNum];
        $irrigationMin = $data['irrigation_min'][$sensorNum];
        $irrigationType = $data['irrigation_type'][$sensorNum];
        $sensorValue = $data['editor_value'][$sensorNum];
        $relayValue = $data['relay_value'][$sensorNum];
        $weatherData =
            $this->_db->where('city_name', $data['city_name'])->where('time', date("Y-m-d", time() - 24 * 3600))
                ->getOne
                ("weather");
        $need = 0;
        if ($weatherData) {
            //三天内有雨
            if (strpos($weatherData['weather1'], '雨') !== false || strpos($weatherData['weather2'], '雨') !== false ||
                strpos
                ($weatherData['weather3'], '雨') !== false
            ) {
                $need=0;

            }else if (strpos($weatherData['weather4'], '雨') !== false || strpos($weatherData['weather5'], '雨') !== false
            ) {
                //4-5天有雨
                $need = $irrigationMin + ($irrigationMax - $irrigationMin) / 4;
            } else if (strpos($weatherData['weather6'], '雨') !== false || strpos($weatherData['weather7'], '雨') !==
                false
            ) {
                $need = $irrigationMin + ($irrigationMax - $irrigationMin) / 2;
            } else {
                $need=$irrigationMax;
            }
            echo $data['device_id']."设备 ".($sensorNum+1)."传感器 需要  ".$need."\r\n";
            //继电器开
            if($relayValue){
                if($sensorValue>=$need){
                    $this->send_command($data['device_id'],$data['sign'],'[0,0,0,0]');
                    echo $data['device_id']."设备 ".$sensorNum."传感器  关闭开关 \r\n";
                    //移交控制权
                    return false;
                } else {
                    //发送获取传感器指令
                    $this->get_sensor_data($data['device_id'],$data['sign']);

                    //移交控制权
                    return false;
                }
            } else {
                $command=array(0,0,0,0);
                $command[$sensorNum]=1;
                if($need>$sensorValue){
                    $this->send_command($data['device_id'],$data['sign'],json_encode($command));
                    //锁定控制权
                    return true;
                }
            }


        } else {
            return false;
        }


    }

    //策略2
    public function smart2($data,$sensorNum) {
        echo "begin smart2";
        $irrigationMax = $data['irrigation_max'][$sensorNum];
        $irrigationMin = $data['irrigation_min'][$sensorNum];
        $irrigationType = $data['irrigation_type'][$sensorNum];
        $sensorValue = $data['editor_value'][$sensorNum];
        $relayValue = $data['relay_value'][$sensorNum];
        $weatherData =
            $this->_db->where('city_name', $data['city_name'])->where('time', date("Y-m-d", time() - 24 * 3600))
                ->getOne
                ("weather");
        $need = 0;
        if ($weatherData) {
            //1天内有雨
            if (strpos($weatherData['weather1'], '雨') !== false) {
                $need=0;

            }else if (strpos($weatherData['weather2'], '雨') !== false || strpos($weatherData['weather3'], '雨') !== false
            ) {
                //4-5天有雨
                $need = $irrigationMin + ($irrigationMax - $irrigationMin) / 4;
            } else if (strpos($weatherData['weather4'], '雨') !== false || strpos($weatherData['weather5'], '雨') !==
                false
            ) {
                $need = $irrigationMin + ($irrigationMax - $irrigationMin) / 2;
            } else {
                $need=$irrigationMax;
            }
            echo $data['device_id']."设备 ".($sensorNum+1)."传感器 需要  "."\r\n";
            //继电器开
            if($relayValue){
                if($sensorValue>=$need){
                    $this->send_command($data['device_id'],$data['sign'],'[0,0,0,0]');
                    //移交控制权
                    return false;
                } else {
                    //发送获取传感器指令
                    $this->get_sensor_data($data['device_id'],$data['sign']);

                    //移交控制权
                    return false;
                }
            } else {
                $command=array(0,0,0,0);
                $command[$sensorNum]=1;
                if($need>$sensorValue){
                    $this->send_command($data['device_id'],$data['sign'],json_encode($command));
                    //锁定控制权
                    return true;
                }
            }


        } else {
            return false;
        }


    }

    //策略3
    public function smart3($data,$sensorNum) {
        echo "begin smart3";
        $irrigationMax = $data['irrigation_max'][$sensorNum];
        $irrigationMin = $data['irrigation_min'][$sensorNum];
        $irrigationType = $data['irrigation_type'][$sensorNum];
        $sensorValue = $data['editor_value'][$sensorNum];
        $relayValue = $data['relay_value'][$sensorNum];
        $weatherData =
            $this->_db->where('city_name', $data['city_name'])->where('time', date("Y-m-d", time() - 24 * 3600))
                ->getOne
                ("weather");
        $need = 0;
        if ($weatherData) {
            //三天内有雨
            if (strpos($weatherData['weather1'], '雨') !== false || strpos($weatherData['weather2'], '雨') !== false
            ) {
                $need=0;

            }else if (strpos($weatherData['weather3'], '雨') !== false || strpos($weatherData['weather4'], '雨') !== false
            ) {
                //4-5天有雨
                $need = $irrigationMin + ($irrigationMax - $irrigationMin) / 4;
            } else if (strpos($weatherData['weather5'], '雨') !== false || strpos($weatherData['weather6'], '雨') !==
                false ||strpos($weatherData['weather7'], '雨') !== false
            ) {
                $need = $irrigationMin + ($irrigationMax - $irrigationMin) / 2;
            } else {
                $need=$irrigationMax;
            }
            echo $data['device_id']."设备 ".($sensorNum+1)."传感器 需要  ".$need."\r\n";
            //继电器开
            if($relayValue){
                if($sensorValue>=$need){
                    $this->send_command($data['device_id'],$data['sign'],'[0,0,0,0]');
                    //移交控制权
                    return false;
                } else {
                    //发送获取传感器指令
                    $this->get_sensor_data($data['device_id'],$data['sign']);

                    return true;
                }
            } else {
                $command=array(0,0,0,0);
                $command[$sensorNum]=1;
                if($need>$sensorValue){
                    $this->send_command($data['device_id'],$data['sign'],json_encode($command));
                    //锁定控制权
                    return true;
                }
            }

        } else {
            return false;
        }
    }
    //策略4
    public function smart4($data,$sensorNum) {
        echo "begin smart4";
        $irrigationMax = $data['irrigation_max'][$sensorNum];
        $irrigationMin = $data['irrigation_min'][$sensorNum];
        $irrigationType = $data['irrigation_type'][$sensorNum];
        $sensorValue = $data['editor_value'][$sensorNum];
        $relayValue = $data['relay_value'][$sensorNum];
        $weatherData =
            $this->_db->where('city_name', $data['city_name'])->where('time', date("Y-m-d", time() - 24 * 3600))
                ->getOne
                ("weather");
        if ($sensorValue < $irrigationMin) {
            $need = $irrigationMax;
        } else {
            return false;
        }
        echo $data['device_id']."设备 ".($sensorNum+1)."传感器 需要  ".$need."\r\n";
        //继电器开
        if ($relayValue) {
            if ($sensorValue >= $need) {
                $this->send_command($data['device_id'], $data['sign'], '[0,0,0,0]');
                //移交控制权
                return false;
            } else {
                //发送获取传感器指令
                $this->get_sensor_data($data['device_id'], $data['sign']);

                //移交控制权
                return false;
            }
        } else {
            $command = array(0, 0, 0, 0);
            $command[$sensorNum] = 1;
            if ($need > $sensorValue) {
                $this->send_command($data['device_id'], $data['sign'], json_encode($command));
                //锁定控制权
                return true;
            }
        }

    }

    public function send_command($deviceId, $sign, $relayStatus) {

            if($relayStatus!='[0,0,0,0]'){
                //开2007 水泵
                $ret=$this->_httpClient->sendPostData("http://www.smartirrigation.org/api/send_command",array(
                    'device_id'=>2007,
                    'sign'=>'8d9ee8324adbdd79e0e665eb6b6b5e87',
                    'set_relay_status'=>'[1,0,0,0]',
                ));
            } else {
                //开2007 水泵
                $ret=$this->_httpClient->sendPostData("http://www.smartirrigation.org/api/send_command",array(
                    'device_id'=>2007,
                    'sign'=>'8d9ee8324adbdd79e0e665eb6b6b5e87',
                    'set_relay_status'=>'[0,0,0,0]',
                ));
            }

//
        $ret=$this->_httpClient->sendPostData("http://www.smartirrigation.org/api/send_command",array(
            'device_id'=>$deviceId,
            'sign'=>$sign,
            'set_relay_status'=>$relayStatus,
        ));
        return json_decode($ret,true);
    }
    public function get_sensor_data($deviceId,$sign){
        $ret=$this->_httpClient->sendPostData("http://www.smartirrigation.org/api/send_command",array(
            'device_id'=>$deviceId,
            'sign'=>$sign,
            'command_id'=>17,
        ));
        return json_decode($ret,true);
    }


    public function run() {
        $data = $this->_db->where("net_status=7")->get("device");
        $end=0;
        foreach ($data as $row) {
            if($end){
                break;
            }
            $deviceId = $row['device_id'];
            $row['irrigation_max'] = json_decode($row['irrigation_max'], true);
            $row['irrigation_min'] = json_decode($row['irrigation_min'], true);
            $row['irrigation_type'] = json_decode($row['irrigation_type'], true);
            $row['irrigation_on'] = json_decode($row['irrigation_on'], true);
            $row['editor_value'] = json_decode($row['editor_value'], true);
            $row['relay_value'] = json_decode($row['relay_value'], true);
            if(!is_array($row['irrigation_on'])){
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
                        $r=$this->{"smart" . $irrigationType}($row, $sensorNum);
                        if($r){
                            //占用该设备,跳过该设备控制
                            $end=1;
                            break;

                        }
                    }

                }

            }


        }
    }


}

$class = new Irrigation();
while (True){
    $class->run();
    sleep(10);
}
