<?php
/**
 * @Copyright (c) 2016 lvxinwei All Rights Reserved.
 * @author          lvxinwei <lvxinwei@lvxinwei.com>
 * @version
 * @desc
 * @Edited by Chenyifei,2016 Aug 10th
 */
require_once realpath(dirname(__FILE__)) . "/../Library/php-selector/selector.php";
require_once realpath(dirname(__FILE__)) . "/../Library/HttpClient.php";
require_once realpath(dirname(__FILE__)) . "/../Library/MysqliDb.php";

class Irrigation 
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

public function Operate3002($data, $sensorNum) //对阀门开闭进行操作
	{
        echo "begin Operation \r\n";
        $irrigationMin = $data['irrigation_min'];
		$irrigationMax = $data['irrigation_max'];
		$sensorValue = $data['editor_value'];
        $irrigationUnit = $data['irrigation_unit'][$sensorNum];
		$optime = 120;
		$process = $data['process'];//新加入变量监控灌溉进程是否结束，起始为0，开始工作时为1,放水模式为2，结束工作为3.
        if ($process) 
        {
			if($process == 1)
			{
				$recentCommand = $this->_db->where("device_id" , '3002')->where('command_id < 17')->orderBy("add_time","desc")->getOne
	            ("command_data");
	            $recentSendTime = $recentCommand['add_time'];
            	echo "上次开启时间".$recentSendTime."\r\n";
            	echo "目前时间".time()."\r\n";				
				if ((time() - $recentSendTime) >= $optime) 
				{
	                $this->send_command($data['device_id'], $data['sign'], '[0,1,0,0]');
            	echo "开启时间已到，现发送命令只开水泵 \r\n";					
	                return false;
           		}
				else
				{
					return true;
				}	
            }
			else if($process == 2)
			{
				$realdata = $this->_db->where("device_id" , '3002')->orderBy("id","desc")->getOne("device_test");
				$realvalue = json_decode($realdata[editor_value]);
				$compare = 0;	
	            for($i=0;$i<3;$i++)
	            {
	            	if($realvalue[$i] < $irrigationMax[$i])
					{$compare++;
					echo "至少有一个传感器水位还未高出设定值 \r\n";
					break;}
	            }
				if(!$compare)
				{
					$command = array(0,0,0,1);				
	                $this->send_command($data['device_id'], $data['sign'], json_encode($command),$process);
					echo "经比较，传感器数据已高于最大水位，现发送命令关闭水闸 \r\n";
	                return false;
	            }	
				else
				{
					return true;
				}
			}

			elseif($process == 3)
			{
				$recentCommand = $this->_db->where("device_id" , '3002')->where('command_id < 17')->orderBy("id","desc")->getOne
	            ("command_data");
	            $recentSendTime = $recentCommand['add_time'];
            	echo "上次开启时间".$recentSendTime."\r\n";
            	echo "目前时间".time()."\r\n";				
				if ((time() - $recentSendTime) >= $optime) 
				{
	                $this->send_command($data['device_id'], $data['sign'], '[0,0,0,0]');
					echo "已发送关闭命令，开关已全部关闭\r\n";
	                return false;
           		}
				else
				{
					return true;
				}	
            }
        } 
        else //开启阀门，首先判断是否需要灌溉，若不需要，则返回false；
        {
            $compare = 0;	
            for($i=0;$i<3;$i++)
            {
            	if($irrigationMin[$i] > $sensorValue[$i])
				{$compare++;
				$j = $i + 1;
				echo "第".$j."传感器对应水位低于设定值\r\n";	
				break;}
            }	
            if ($compare)
			{
				$command = array(0,1,1,0);				
                $this->send_command($data['device_id'], $data['sign'], json_encode($command));
                return true;
				echo "需要灌溉，已发送开闸、开泵命令于".time()."\r\n";	
            }
			else
			{
				return false;
			}
        }
    }
public function get_sensor_data($deviceId, $sign) 
	{
        $ret = $this->_httpClient->sendPostData("http://www.smartirrigation.org/api/send_command", array(
            'device_id' => $deviceId,
            'sign' => $sign,
            'command_id' => 17,
        ));
        return json_decode($ret, true);
    }
public function send_command($deviceId, $sign, $relayStatus) 
	{
        $ret = $this->_httpClient->sendPostData("http://www.smartirrigation.org/api/send_command", array(
            'device_id' => $deviceId,
            'sign' => $sign,
            'set_relay_status' => $relayStatus,
        ));
        return json_decode($ret, true);
    }

public function run() 
	{
        $OpCon = array('3002','3003');	
        $data = $this->_db->where("net_status=7")->where("device_id = 3002 or device_id = 3003")->get("device_test");
        $end = 0;
        foreach ($data as $row)
		{
            $deviceId = $row['device_id'];
            $row['irrigation_max'] = json_decode($row['irrigation_max'], true);
            $row['irrigation_min'] = json_decode($row['irrigation_min'], true);
            $row['irrigation_unit'] = json_decode($row['irrigation_unit'], true);
            $row['irrigation_on'] = json_decode($row['irrigation_on'], true);
            $row['editor_value'] = json_decode($row['editor_value'], true);
            $row['relay_value'] = json_decode($row['relay_value'], true);
			$row['process'] = 0;
            if (!is_array($row['irrigation_on'])) //??检查啥？
            {
                continue;
            }
            foreach ($row['irrigation_on'] as $sensorNum => $isOn) 
            {
                if ($isOn) 
                {
                	echo "编号".$deviceId."设备第".($sensorNum+1)."开关判断开启.\r\n";
                        $row['process'] = 0;
						echo "进程编号：".$row['process']."\r\n";
                        $r = $this->{"Operate".$deviceId}($row, $sensorNum);
						echo "开始进行判断 是否需要灌溉 \r\n";
                        if($r)
                        {
	                        for($i = 0;$i < 3;$i++)
	                        {
		                        $row['process']++;
								echo "进程编号已更新为：".$row['process']."\r\n";
								$r = 1;
		                        while ($r) 
		                        {
		                            $s = $this->get_sensor_data($deviceId,$row['sign']);
									echo "于".time()."发送更新命令 \r\n";			                           
		                            $r = $this->{"Operate".$deviceId}($row, $sensorNum);		                            									
									sleep(20);
		                        }
							}
						}
						else
						{
							echo "并不需要，下一位 \r\n";
						}
                    }
                }
            }
        }
    }


$class = new Irrigation();
$class->run();