<?php
/**
 * Created by PhpStorm.
 * User: lvxinwei
 * Date: 2016/4/16
 * Time: 23:35
 */
class Home extends CI_Controller{
    function index(){
        $this->load->view("templates/home.html");
    }
	function test(){
		print_r($_SESSION);
	}
}