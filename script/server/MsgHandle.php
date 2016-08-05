<?php
/**
 * Created by PhpStorm.
 * User: lvxinwei
 * Date: 2016/4/20
 * Time: 22:54
 */
require_once realpath(dirname(__FILE__)) . "/../Library/HttpClient.php";
require_once realpath(dirname(__FILE__)) . "/../Library/MysqliDb.php";
require_once realpath(dirname(__FILE__)) . "/../Library/predis/autoload.php";

class MsgHandle {
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
        $this->_redis = new Predis\Client();

    }

    private function get_bin($num, $length = 4) {
        $bin = decbin($num);
        $ret = str_repeat("0", $length - strlen($bin)) . $bin;
        $ret = str_split($ret);
        foreach ($ret as $key => $value) {
            $ret[$key] = (int)$value;
        }

        return $ret;
    }

    public function run() {
        while (true) {
            $ret = $this->_redis->lpop("message_in_box");
            if (!$ret) {
                usleep(50000);
                continue;
            }
            $ret = json_decode($ret, true);
            $deviceId = $ret['device_id'];
            $msgData = $ret['data'];
            if ($ret['type'] == 1) {
                //notice
                $this->_db->where("device_id", $deviceId)->update("device", array(
                    'net_status' => $msgData,
                    'update_time' => $ret['time']
                ));
                if ($msgData == 7) {
                    $this->send_command($deviceId, 18);
                }

            } else {
                //返回的是传感器数据或者继电器数据
                $commandData = $this->_db->where("device_id", $deviceId)->where('result', 0)->get("command_data");
                foreach ($commandData as $row) {
                    if (preg_match("/" . $row['response_data'] . "/", $msgData, $matches)) {
                        //result 0 未执行 1 执行成功 2 无效
                        $this->_db->where("id", $row['id'])->update("command_data", array(
                            'success_time' => $ret['time'],
                            'result' => 1
                        ));
                        //在这做以下处理，传感器数据上传或继电器状态置位
                        $commandTemplateData =
                            $this->_db->where("command_id", $row['command_id'])->getOne("command_template");
                        //command type 1  控制开发结果返回 2 传感器数据上传
                        if ($commandTemplateData['command_type'] == 1) {
                            $this->_db->where('device_id', $deviceId)->update("device", array(
                                'relay_value' => $commandTemplateData['command_desc'],
                                'update_time' => $ret['time']
                            ));
                        } else if ($commandTemplateData['command_type'] == 2) {
                            //获取传感器状态
                            $responseData = $commandTemplateData['response_data'];
                            preg_match("/" . $responseData . "/", $msgData, $matches);
                            $sensorData = array();
                            if ($matches) {
                                for ($i = 1; $i < count($matches); $i++) {
                                    array_push($sensorData, hexdec($matches[$i]));
                                }
                            }
                            $deviceData = $this->_db->where('device_id', $deviceId)->getOne("device");
                            $sensorEditor = json_decode($deviceData['sensor_editor'], true);
                            $sensorEditorData = array();
                            for ($i = 0; $i < 4; $i++) {
                                if(!isset($sensorData[$i])){
                                    $sensorData[$i]=0;
                                }
                                if(strlen($sensorEditor[$i])<2){
                                    $sensorEditor[$i]="%d";
                                }
                                $temp = str_replace("%d", $sensorData[$i], $sensorEditor[$i]);
                                eval('$temp=' . $temp . ';');
                                if ($temp < 0) {
                                    $temp = 0;
                                }
                                array_push($sensorEditorData, round($temp,2));
                            }
                            $this->_db->where('device_id', $deviceId)->update("device", array
                            (
                                'sensor_value' => json_encode($sensorData),
                                'editor_value'=>json_encode($sensorEditorData),
                                'update_time' => $ret['time']
                            ));

                            $this->_db->insert("sensor_data", array(
                                'device_id' => $deviceId,
                                "add_time" => $ret['time'],
                                'value1' => $sensorData[0],
                                'value2' => $sensorData[1],
                                'value3' => $sensorData[2],
                                'value4' => $sensorData[3],
                                'editor_value1' => $sensorEditorData[0],
                                'editor_value2' => $sensorEditorData[1],
                                'editor_value3' => $sensorEditorData[2],
                                'editor_value4' => $sensorEditorData[3],
                            ));
                        } else if ($commandTemplateData['command_type'] == 3) {
                            //获取继电器状态数据
                            //因为高位在前表示继电器大的数,所以数组要反转
                            $responseData = $commandTemplateData['response_data'];
                            preg_match("/" . $responseData . "/", $msgData, $matches);
                            $relayValue = $this->get_bin(hexdec($matches[1]), 4);
                            $this->_db->where('device_id', $deviceId)->update("device", array
                            (
                                'relay_value' => json_encode(array_reverse($relayValue)),
                                'update_time' => $ret['time']
                            ));

                        }
                        break;
                    }
                }
                $this->_db->where("id<" . $row['id'] . " and result=0 and device_id=" . $deviceId)
                    ->update("command_data", array('result' => 2));
            }

        }
    }

    public function send_command($deviceId, $commandId) {
        $commandTemplateData = $this->_db->where("command_id", $commandId)->getOne("command_template");
        $this->_db->insert("command_data", array(
            'device_id' => $deviceId,
            'command_id' => $commandTemplateData['command_id'],
            'add_time' => time(),
            'command_data' => $commandTemplateData['command_data'],
            'response_data' => $commandTemplateData['response_data'],
        ));
        $redisKey = "message_out_box_" . $deviceId;
        $value = array(
            'type' => 4,
            'time' => time(),
            'data' => $commandTemplateData['command_data'],
        );
        $this->_redis->rpush($redisKey, json_encode($value));
    }
}

$class = new MsgHandle();
$class->run();

