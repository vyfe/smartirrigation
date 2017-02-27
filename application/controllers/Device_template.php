<?php

/**
 * Created by PhpStorm.
 * User: lvxinwei
 * Date: 2016/4/17
 * Time: 0:10
 */
class Device_template extends MY_Controller {
	function add_form() {
		$this->load->view( "templates/device_template_add.html" );
	}

	function add() {

		$config = array(
			array(
				'field'  => 'device_name',
				'label'  => 'device_name',
				'rules'  => 'required|trim',
				'errors' => array( 'required' => '请输入设备模板名', ),
			),
			array(
				'field'  => 'sensor_num',
				'label'  => 'sensor_num',
				'rules'  => 'required|trim|integer',
				'errors' => array( 'required' => '请输入传感器数量', ),
			),
			array(
				'field'  => 'relay_num',
				'label'  => 'relay_num',
				'rules'  => 'required|trim|integer',
				'errors' => array( 'required' => '请输入开关设备数量', ),
			),
		);
		$this->form_validation->set_rules( $config );
		$this->form_validation->set_error_delimiters( "<div>", "</div>" );
		if ( $this->form_validation->run() == false ) {
			$this->load->view( 'templates/device_template_add.html' );
		} else {

		}

	}

}