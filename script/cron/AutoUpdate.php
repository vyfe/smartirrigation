<?php
/**
 * Created by PhpStorm.
 * User: Chenyifei
 * Date: 2016/8/26
 * Time: 12:17
 * 配合plant_info表中数据使用
 * 经11.30修改后运行成功。
 */
require_once realpath(dirname(__FILE__)) . "/../Library/php-selector/selector.php";
require_once realpath(dirname(__FILE__)) . "/../Library/HttpClient.php";
require_once realpath(dirname(__FILE__)) . "/../Library/MysqliDb.php";

class AutoUpdate
{
    private $_db;
    private $_redis;
    private $_httpClient;

    public function __construct()
    {
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

    public function run()
    {
        $data = $this->_db->get("device");
        foreach($data as $row) {
            $row['plant_id'] = json_decode($row['plant_id'], true);//TODO:计划在device表中添加一列指示作物种类，原有下限值不变，不显示出来。
            $row['irrigation_min'] = json_decode($row['irrigation_min'], true);
            $row['irrigation_on'] = json_decode($row['irrigation_on'], true);
            $deviceid = $row['device_id'];
            if (!is_array($row['irrigation_on'])) {
                continue;
            }
            foreach ($row['irrigation_on'] as $sensorNum => $isOn) {
                if ($isOn && ($row['plant_id'][$sensorNum] != 0)) {
                    $j=$sensorNum+1;
                    $stagedata = $this->_db->where("plant_id",$row['plant_id'][$sensorNum])->get("plant_info");
                    $date = time();
                    $stagedata = $stagedata[0];
                    for($i=1;$i<=$stagedata['stagecount'];$i++)
                    {
                        $stagedata['stage'.$i] = json_decode($stagedata['stage'.$i],true);
                        $stm =  $stagedata['stage'.$i]['0'];
                        $std =  $stagedata['stage'.$i]['1'];
                        $edm = $stagedata['stage'.$i]['2'];
                        $edd = $stagedata['stage'.$i]['3'];//表示字符串中4个时间
                        $value = $stagedata['stage'.$i]['5'];
                        $starttime = strtotime(date("Y")."-".$stm."-".$std." 00:00:00");
                        $endtime = strtotime(date("Y")."-".$edm."-".$edd." 00:00:00");
                        echo "当前时间：".$date."\r\n";
                        echo "生育期".$i."开始时间：".$starttime."结束时间".$endtime."\r\n";
                        if($date > $starttime && $date < $endtime){//执行写入操作并跳出循环
                            $data = $this->_db->where("device_id",$deviceid)->get("device");
                            if($data){
                                $data = $data[0];
                                $data['irrigation_min']=json_decode($data['irrigation_min'],true);
                                $data['irrigation_min'][$sensorNum] = $value;
                                $data['irrigation_min']=json_encode($data['irrigation_min'],true);
                                $r=$this->_db->where("device_id",$deviceid)->update("device",$data);
                                if($r){
                                    echo $deviceid."第".$j."传感器更新水位信息成功！\r\n";}
                                    break;
                            }
                        }
                        else{echo $deviceid."第".$j."传感器时间不在生育期".$i."内\r\n";}
                    }
                } 
                else{echo $deviceid."第".$sensorNum."传感器未开启功能或未选择作物 \r\n";}
            }
        }
    }
}
$class = new AutoUpdate();
$class->run();
