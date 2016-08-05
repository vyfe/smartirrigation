<?php

/**
 * Created by PhpStorm.
 * User: lvxinwei
 * Date: 2016/5/5
 * Time: 17:39
 */
class Download extends MY_Controller {
	public function weather() {
		$this->load->library( 'pagination' );
		$where = array( "1" => 1 );
		if ( ! empty( $_GET['city_name'] ) ) {
			$where['city_name'] = $_GET['city_name'];
		}
		if ( ! empty( $_GET['begin_day'] ) ) {
			$where['time>='] = $_GET['begin_day'];
		}
		if ( ! empty( $_GET['end_day'] ) ) {
			$where['time<='] = $_GET['end_day'];
		}
		$this->db->where( $where );
		$config['base_url']           = site_url( 'download/weather' ); //url地址
		$config['total_rows']         = $this->db->count_all_results( 'weather' );
		$config['per_page']           = 100;
		$config['num_links'] = 5;
		$config['use_page_numbers']   = true;
		$config['reuse_query_string'] = true;
		$this->pagination->initialize( $config );
		$page   = (int) $this->uri->segment( 3 );
		$offset = $page == false ? 0 : ( $config['per_page'] * ( $page - 1 ) );
		$this->db->limit( $config['per_page'], $offset );
		$this->db->order_by( "id desc " );
		$data['list']      = $this->db->get_where( 'weather', $where )->result_array();
		$data['page_list'] = $this->pagination->create_links();
		$this->load->view( "templates/weather.html", $data );
	}

	public function weather_export() {

		$where = array( "1" => 1 );
		if ( ! empty( $_GET['city_name'] )&&$_GET['city_name'] ) {
			$where['city_name'] = $_GET['city_name'];
		}
		if ( ! empty( $_GET['begin_day'] ) &&$_GET['begin_day']) {
			$where['time>='] = $_GET['begin_day'];
		} else {
//			$where['time>=']=date("Y-m-d",time()-3600*24*7);
		}
		if ( ! empty( $_GET['end_day'] ) &&$_GET['end_day']) {
			$where['time<='] = $_GET['end_day'];
		} else {
//			$where['time<='] = date("Y-m-d",time());
		}
		if(empty($_GET['city_name'])){
			echo "请填写城市名";
			exit();
		}
		$data   = $this->db->get_where( 'weather', $where )->result_array();
		$header = array(
			'time'      => '日期',
			'city_name' => '城市名',
		);
		for ( $i = 1; $i < 16; $i ++ ) {
			$header = array_merge( $header, array(
				'low_temp' . $i  => '第' . $i . '天低温',
				'high_temp' . $i => '第' . $i . '天高温',
				'weather' . $i   => '第' . $i . '天天气',
				'win' . $i       => '第' . $i . '天风力',
			) );
		}
		exportCsv( $header, $data, time() );
	}


}