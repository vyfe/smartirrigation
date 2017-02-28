<?php
/**
 * Created by PhpStorm.
 * User: lvxinwei
 * Date: 2016/4/16
 * Time: 21:50
 */
/*
 *  控制器父类，重定向功能做在此处，没有session存在则回到login页面
 * */
defined('BASEPATH') OR exit('No direct script access allowed');
class MY_Controller extends CI_Controller{
    public function __construct(){
        parent::__construct();
        if(!$_SESSION['user_info']){
           $this->session->set_userdata('redirect',"http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
            redirect("user/login");
        }
    }
}
