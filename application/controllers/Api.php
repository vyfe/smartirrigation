<?php

/**
 * Created by PhpStorm.
 * User: lvxinwei
 * Date: 2016/4/17
 * Time: 22:57
 */
class Api extends CI_Controller {
    private $_request;

    public function __construct() {
        parent::__construct();
        require_once BASEPATH . 'third_party/predis/autoload.php';
        $this->_redisClient = new Predis\Client();

    }

    function test() {


    }

    public function send_command() {
        $ret = array('errno' => 0, 'msg' => "OK");
        $this->_request = array_merge($_GET, $_POST);
        $deviceId = (int)$this->_request['device_id'];
        $sign = $this->_request['sign'];
        $setRelayStatus = $this->_request['set_relay_status'];
        $commandId = $this->_request['command_id'];
        if (empty($deviceId) || empty($sign)) {
            $ret['errno'] = 1;
            $ret['msg'] = "参数不完整";
            goto render;
        }
        if (empty($commandId) && empty($setRelayStatus)) {
            $ret['errno'] = 1;
            $ret['msg'] = "参数不完整";
            goto render;
        }
        /**
         * 判断该设备是否有未完成的命令,10s内未完成不允许继续发送
         */
//        if($this->db->get_where("command_data", array('device_id' => $deviceId,'result'=>0,'add_time>'=>time()-10))
//            ->result_array()){
//            $ret['errno'] = 4;
//            $ret['msg'] = "上一条命令仍为执行完成,请稍后";
//            goto render;
//        }
        /**
         * 如果发送的是set_relay_status,获取ID
         */
        if ($deviceData =
            $this->db->get_where("device", array('device_id' => $deviceId, 'sign' => $sign))->result_array()
        )
            //从数据库中读取命令代码模板
        {
            if (empty($commandId)) {
                $commandTemplateData = $this->db->get_where("command_template", array('command_desc' => $setRelayStatus,
                    'device_template_id' => $deviceData[0]['device_template_id']))->result_array();
                $commandId = $commandTemplateData['command_id'];//根据开关状态查询代码

            } else {

                $commandTemplateData =
                    $this->db->get_where("command_template", array('command_id' => $commandId))->result_array();
            }

            if (!$commandTemplateData) {
                $ret['errno'] = 2;
                $ret['msg'] = '无效命令ID';
                goto render;
            }
            $commandTemplateData = $commandTemplateData[0];//只取第一行
            $this->db->insert("command_data", array(
                'device_id' => $deviceId,
                'command_id' => $commandTemplateData['command_id'],
                'add_time' => time(),
                'command_data' => $commandTemplateData['command_data'],
                'response_data' => $commandTemplateData['response_data'],
            ));//在数据库中插入命令信息
            $this->send_command_to_redis($deviceId, $commandTemplateData['command_data']);

        } else {
            $ret['errno'] = 3;
            $ret['msg'] = '无效SIGN';
            goto render;
        }
        render:
        render_json($ret);
    }

    private function send_command_to_redis($deviceId, $commandData) {
        $redisKey = System_Config::MESSAGE_OUT . "_" . $deviceId;
        $value = array(
            'type' => System_Config::MESSAGE_TYPE_COMMAND,
            'time' => time(),
            'data' => $commandData,
        );
        $this->_redisClient->rpush($redisKey, json_encode($value));
    }

    public function get_devices_info() {
        $ret = array('errno' => 0, 'msg' => "OK");
        if (empty($_SESSION['user_info'])) {
            $ret['errno']=1;
            $ret['msg']="未授权";
            render_json($ret);
        }
        $_POST['lastChangeTime']=time()-10;
        if(empty($_POST['lastChangeTime'])){
            $ret['errno']=2;
            $ret['msg']="参数缺失";
            render_json($ret);//传输到前端
        }
        $lastChangeTime=(int)$_POST['lastChangeTime'];
        $data=$this->db->get_where("device",array("user_id"=>$_SESSION['user_info']['user_id']))
            ->result_array();
        $deviceInfo=array();
        foreach ($data as $key=>$row){
            $deviceInfo[$row['device_id']]=array(
                'update_time'=>date("Y-m-d H:i:s",$row['update_time']),
                'net'=>System_Config::$deviceNetStatusDesc[$row['net_status']],
                'sensor_value'=>$row['editor_value'],
                'relay_value'=>$row['relay_value'],
            );
        }
        $ret['time']=time();
        $ret['data']=$deviceInfo;
        render_json($ret);
    }
}