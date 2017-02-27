<?php
/**
 * @Copyright (c) 2016 Rd.Lanjinger.com. All Rights Reserved.
 * @author          lvxinwei <lvxinwei@lvxinwei.com>
 * @version
 * @desc
 */
class Device extends MY_Controller{
    function __construct() {
        parent::__construct();
    }
    function add_device_form(){
        $deviceTemplateData=$this->db->get("device_template")->result_array();
        $data=array();
        $data['deviceTemplateData']=$deviceTemplateData;
        $this->load->view("templates/add_device_form.html",$data);

    }
    function add_device(){
        $config = array(
            array( 'field'  => 'device_name',
                'label'  => 'device_name',
                'rules'  => 'required|trim',
                'errors' => array( 'required' => '请输入设备名', ),
            ),
            array( 'field'  => 'device_description',
                'label'  => 'device_description',
                'rules'  => 'required|trim',
                'errors' => array( 'required' => '请输入设备描述', ),
            ),
            array( 'field'  => 'device_id',
                'label'  => 'device_id',
                'rules'  => 'required|trim|callback_validate',
                'errors' => array( 'required' => '请输入设备号', ),
            ),
            array( 'field'  => 'city_name',
                'label'  => 'city_name',
                'rules'  => 'required|trim|callback_validate',
                'errors' => array( 'required' => '请输入设备城市', ),
            ),

            array( 'field'  => 'device_type',
                'label'  => 'device_name',
                'rules'  => 'required|trim',
                'errors' => array( 'required' => '请选择设备类型', ),
            ),

            array( 'field'  => 'device_template_id',
                'label'  => 'device_template_id',
                'rules'  => 'required|trim',
                'errors' => array( 'required' => '请选择设备模板', ),
            ),


        );
        $this->form_validation->set_rules( $config );
        $this->form_validation->set_error_delimiters( "<div>", "</div>" );
        if ( $this->form_validation->run() == false ) {
	        $deviceTemplateData=$this->db->get("device_template")->result_array();
	        $data=array();
	        $data['deviceTemplateData']=$deviceTemplateData;
	        $this->load->view("templates/add_device_form.html",$data);
        } else {
	        $insertData=array();
	        $insertData['device_id']=$_POST['device_id'];
            $insertData['city_name']=$_POST['city_name'];
	        $insertData['device_type']=$_POST['device_type'];
	        $insertData['device_name']=$_POST['device_name'];
	        $insertData['description']=$_POST['device_description'];
	        $insertData['device_template_id']=$_POST['device_template_id'];
	        $insertData['add_time']=time();
	        $insertData['user_id']=$_SESSION['user_info']['user_id'];
	        $insertData['sign']=md5(time());
	        $insertData['net_status']=System_Config::DEVICE_NET_STATUS_OFFLINE;
	        $deviceTemplateData=$this->db->get_where("device_template",array("device_template_id"=>$_POST['device_template_id']))->result_array();
	        $deviceTemplateData=$deviceTemplateData[0];
	        $insertData['sensor_num']=$deviceTemplateData['sensor_num'];
	        $insertData['relay_num']=$deviceTemplateData['relay_num'];
	        $insertData['sensor_value']=relay_sensor_value($insertData['sensor_num']);
	        $insertData['relay_value']=relay_sensor_value($insertData['relay_num']);
            $ret= $this->db->insert("device",$insertData);
	        if($ret){
		        redirect("/console");
	        }

        }

    }
	public function validate(){
		if ( $this->db->get_where("device",array("device_id"=>$_POST['device_id']))->result_array() ) {
			$this->form_validation->set_message( 'validate', '设备号重复' );
			return false;
		}
		return true;

	}
    function delete(){
	   

    }
    function sensor_editor(){
        $ret=array();
        $where = array('user_id' => $_SESSION['user_info']['user_id']);
        $this->db->where($where);
        $deviceData = $this->db->get("device")->result_array();
        foreach ($deviceData as &$item){
            $item['sensor_value']=json_decode($item['sensor_value'],true);
            $item['sensor_editor']=json_decode($item['sensor_editor'],true);
        }
        $ret['data']=$deviceData;
	    $this->load->view("templates/sensor_editor.html",$ret);
    }
    function do_sensor_editor(){
        $ret=array("error_code"=>"0","msg"=>"修改成功");
        $deviceId=(int)$_POST['device_id'];
        $sensorEditor=@json_encode($_POST['sensor_editor']);
        if($deviceId>0){
            $this->db->where(array('user_id' => $_SESSION['user_info']['user_id'],"device_id"=>$deviceId));
            $r=$this->db->update("device",array("sensor_editor"=>$sensorEditor));
            if($r){
                render_json($ret);
            } else {
                $ret['error_code']=1;
                $ret['msg']="修改失败";
                render_json($ret);
            }
        } else {
            $ret['error_code']=2;
            $ret['msg']="参数错误";
            render_json($ret);
        }
    }
    function sensor_data(){
        $where = array('user_id' => $_SESSION['user_info']['user_id']);
        $this->db->where($where);
        $deviceData = $this->db->get("device")->result_array();
        $ret['data']=$deviceData;
        $this->load->view("templates/sensor_data.html",$ret);

    }
    function get_sensor_data(){
        $beginTime=strtotime($_POST['begin_time']);
        $endTime=strtotime($_POST['end_time'])+24*3600;
        $deviceId= $_POST['device_id'];
        $this->db->where(array("device_id"=>$deviceId));
        $deviceData=$this->db->get("device")->result_array();
        $deviceData=$deviceData[0];
        $this->db->where(array("device_id"=>$deviceId,"add_time>"=>$beginTime,"add_time<="=>$endTime));
        $sensorData=$this->db->get("sensor_data")->result_array();
        $time=array();
        foreach ($sensorData as $item) {
            $time[]=date("Y-m-d H:i",$item['add_time']);
        }
        $plot['title']=array('text'=>$deviceId."设备统计数据");
        $plot['series'] = array();
        $plot['legend']['data'] = array();
        $plot['xAxis'] = array('type' => 'category', 'data' => $time);
        $plot['yAxis'] = array(array('type' => 'value', 'name' => '数值', 'min' => 0,),);
        $plot['tooltip'] = array('trigger' => 'axis');
        $plot['toolbox'] = array(
            'feature' => array(
                'dataView' => array('show' => true, 'readOnly' => false),
                'magicType' => array('show' => true, 'type' => array('line', 'bar')),
                'restore' => array('show' => true),
                'saveAsImage' => array('show' => true)
            )
        );
        /**
         * 该目录下的统计项
         */
        $sensorEditor=json_decode($deviceData['sensor_editor'],true);
        $dataType=array('传感器1','传感器2','传感器3','传感器4');
        foreach ($dataType as $typeId=>$dataTypeName) {
            array_push($plot['legend']['data'], $dataTypeName);
            $dataTemp = array();
            foreach ($sensorData as $v) {

                $temp=str_replace("%d",$v['value'.($typeId+1)],$sensorEditor[$typeId]);
                eval('$temp='.$temp.';');
                if($temp<0){
                    $temp=0;
                }
                array_push($dataTemp,(int)$temp);
            }
            array_push($plot['series'],
                array('type' => 'line', 'name' => $dataTypeName, 'data' => $dataTemp));
        }
        $ret['data']['plot']=$plot;
        $ret['msg']="成功";
        $ret['errno']=0;
        render_json($ret);
    }
    public function irrigation(){
        $ret=array();
        $where = array('user_id' => $_SESSION['user_info']['user_id']);
        if(isset($_GET['device_id'])){
            $where['device_id']=$_GET['device_id'];
        }
        $this->db->where($where);
        $deviceData = $this->db->get("device")->result_array();
        foreach ($deviceData as &$item){
            $item['irrigation_max']=json_decode($item['irrigation_max'],true);
            $item['irrigation_min']=json_decode($item['irrigation_min'],true);
            $item['irrigation_type']=json_decode($item['irrigation_type'],true);
            $item['irrigation_on']=json_decode($item['irrigation_on'],true);
            $item['irrigation_unit']=json_decode($item['irrigation_unit'],true);
        }
        $ret['data']=$deviceData[0];
        $where = array('user_id' => $_SESSION['user_info']['user_id']);
        $this->db->where($where);
        $deviceInfo = $this->db->get("device")->result_array();
        $ret['deviceInfo']=$deviceInfo;
        $ret['irrigationType']=System_Config::$irrigationTypeDesc;
        $this->load->view("templates/irrigation.html",$ret);
    }
    function irrigation_editor(){
        $ret=array("error_code"=>"0","msg"=>"修改成功");
        $deviceId=(int)$_POST['device_id'];
        $sensorId=(int)$_POST['sensor_id'];
        $irrigationOn=(int)$_POST['irrigation_on'];
        $irrigationType=(int)$_POST['irrigation_type'];
        $irrigationMin=(int)$_POST['irrigation_min'];
        $irrigationMax=(int)$_POST['irrigation_max'];
        $irrigationUnit=(int)$_POST['irrigation_unit'];
        if($deviceId<=0||$sensorId<=0){
            $ret['error_code']=1;
            $ret['msg']='修改失败';
            render_json($ret);
        }
        $where = array('user_id' => $_SESSION['user_info']['user_id']);
        $where['device_id']=$deviceId;
        $this->db->where($where);
        $deviceData = $this->db->get("device")->result_array();
        if($deviceData){
            $deviceData=$deviceData[0];
            $deviceData['irrigation_max']=json_decode($deviceData['irrigation_max'],true);
            $deviceData['irrigation_min']=json_decode($deviceData['irrigation_min'],true);
            $deviceData['irrigation_type']=json_decode($deviceData['irrigation_type'],true);
            $deviceData['irrigation_on']=json_decode($deviceData['irrigation_on'],true);
            $deviceData['irrigation_unit']=json_decode($deviceData['irrigation_unit'],true);
            $deviceData['irrigation_max'][$sensorId-1]=$irrigationMax;
            $deviceData['irrigation_min'][$sensorId-1]=$irrigationMin;
            $deviceData['irrigation_unit'][$sensorId-1]=$irrigationUnit;
            $deviceData['irrigation_type'][$sensorId-1]=$irrigationType;
            $deviceData['irrigation_on'][$sensorId-1]=$irrigationOn;
            $deviceData['irrigation_max']=json_encode($deviceData['irrigation_max'],true);
            $deviceData['irrigation_min']=json_encode($deviceData['irrigation_min'],true);
            $deviceData['irrigation_unit']=json_encode($deviceData['irrigation_unit'],true);
            $deviceData['irrigation_type']=json_encode($deviceData['irrigation_type'],true);
            $deviceData['irrigation_on']=json_encode($deviceData['irrigation_on'],true);
            $where = array('user_id' => $_SESSION['user_info']['user_id']);
            $where['device_id']=$deviceId;
            $r=$this->db->where($where)->update("device",$deviceData);
            if($r){
                render_json($ret);
            } else {
                $ret['error_code']=3;
                $ret['msg']='修改失败';
                render_json($ret);
            }



        } else {
            $ret['error_code']=2;
            $ret['msg']='修改失败';
            render_json($ret);
        }

    }
    public function editor(){
        $ret=array();
        $where = array('user_id' => $_SESSION['user_info']['user_id']);
        if(isset($_GET['device_id'])){
            $where['device_id']=$_GET['device_id'];
        }
        $this->db->where($where);
        $deviceData = $this->db->get("device")->result_array();
        $ret['data']=$deviceData[0];
        $where = array('user_id' => $_SESSION['user_info']['user_id']);
        $this->db->where($where);
        $deviceInfo = $this->db->get("device")->result_array();
        $ret['deviceInfo']=$deviceInfo;
        $deviceTemplateData=$this->db->get("device_template")->result_array();
        $ret['deviceTemplateData']=$deviceTemplateData;
        $this->load->view("templates/editor.html",$ret);
    }
    public function do_editor(){
        $where=array();
        $where['device_id']=$_POST['device_id'];
        $where['user_id']=$_SESSION['user_info']['user_id'];
        $r=$this->db->where($where)->update("device",$_POST);
        if($r){
            redirect("/device/editor?device_id=".$_POST['device_id']);
        }
    }
}
