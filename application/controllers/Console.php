<?php
/**
 * Created by PhpStorm.
 * User: lvxinwei
 * Date: 2016/4/16
 * Time: 21:51
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Console extends MY_Controller {
    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $ret = array();
        $where = array('user_id' => $_SESSION['user_info']['user_id']);
        $this->db->where($where);
        $deviceData = $this->db->get("device")->result_array();
        $ret['deviceData'] = $deviceData;
        $commandData = array();
        foreach ($deviceData as $device) {
            $this->db->where(
                array(
                    'device_template_id' => $device['device_template_id'],
                )
            );
            $this->db->order_by("sort_num", "desc");
            $command = $this->db->get("command_template")->result_array();
            $commandData[$device['device_id']] = $command;
        }
        $ret['commandData'] = $commandData;

        $this->load->view("templates/console.html", $ret);

    }
}
