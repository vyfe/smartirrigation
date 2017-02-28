<?php

/**
 * Created by PhpStorm.
 * User: lvxinwei
 * Date: 2016/4/16
 * Time: 20:22
 */
class User_model extends CI_Model {
    public function get_user_info ($user_name, $password) {
        $this->db->select(array('user_id','user_name'));
        $this->db->where(array('user_name' => $user_name, 'password' =>md5($password)));
        $this->db->limit(1);
        return $this->db->get("user")->result_array();
    }
    public function get_access($user_name){
        $this->db->select(array('user_id','access'));
        $this->db->where(array('user_name' => $user_name,));
        $this->db->limit(1);
        return $this->db->get("user")->result_array();
    } 
}