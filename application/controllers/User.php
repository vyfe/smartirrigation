<?php
/**
 * @Copyright (c) 2016 Rd.Lanjinger.com. All Rights Reserved.
 * @author          lvxinwei <lvxinwei@lvxinwei.com>
 * @version
 * @desc
 */
class User extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $ret = $this->load->model("user_model");
    }

    public function login() {
        if(!$_SESSION['user_info']){
            $this->load->view("templates/login.html");
        }
        /*闲得无聊，把登陆后还能进login的Bug改一下*/
        else
            redirect("/Console");
    }

    public function Syslogin() {
        $this->load->view("templates/Syslogin.html");
    }

    public function postLogin() {

        $config = array(
            array(
                'field' => 'user_name',
                'label' => 'Username',
                'rules' => 'required|trim',
                'errors' => array(
                    'required' => '请输入用户名',
                ),
            ),
            array(
                'field' => 'password',
                'label' => 'Password',
                'rules' => 'required|trim|callback_validate',
                'errors' => array(
                    'required' => '请输入密码',
                ),
            ),
        );
        $this->form_validation->set_rules($config);
        $this->form_validation->set_error_delimiters("<div>", "</div>");
        if ($this->form_validation->run() == false) {
            $this->load->view('templates/login.html');
        } else {
            $ret = $this->user_model->get_user_info($_POST['user_name'], $_POST['password']);
            $user_info = $ret[0];
            $this->session->set_userdata("user_info", $user_info);
            if ($this->session->userdata('redirect')) {
                $redirect = $this->session->userdata('redirect');
                $this->session->unset_userdata('redirect');
                redirect($redirect);
            } else {
                redirect("/console");
            }
        }
    }

    public function validate() {
        if ($this->user_model->get_user_info($_POST['user_name'], $_POST['password'])) {
            return true;
        } else {
            $this->form_validation->set_message('validate', '密码错误');

            return false;
        }
    }

    public function logout() {
        $_SESSION = array();
        redirect("/user/login");
    }

    public function sign() {
        $this->load->view("templates/sign.html");
    }

    public function post_sign() {
        $config = array(
            array('field' => 'user_name',
                'label' => 'Username',
                'rules' => 'required|trim|callback_validateUserName',
                'errors' => array('required' => '请输入用户名',),
            ),
            array('field' => 'password',
                'label' => 'Password',
                'rules' => 'required|trim',
                'errors' => array('required' => '请输入密码',),
            ),
            array('field' => 'email',
                'label' => 'email',
                'rules' => 'required|trim|valid_email',
                'errors' => array('required' => '请输入邮箱',),
            ),
            array('field' => 'phone',
                'label' => 'phone',
                'rules' => 'required|trim',
                'errors' => array('required' => '请输入电话',),
            ),
        );
        $this->form_validation->set_rules($config);
        $this->form_validation->set_error_delimiters("<div>", "</div>");
        if ($this->form_validation->run() == false) {
            $this->load->view('templates/sign.html');
        } else {
            $insertData = array();
            $insertData['user_name'] = $_POST['user_name'];
            $insertData['phone'] = $_POST['phone'];
            $insertData['email'] = $_POST['email'];
            $insertData['password'] = md5(trim($_POST['password']));
            $insertData['add_time'] = time();
            $insertData['access'] = $_POST['access'];//插入的是是否有权限
            $ret = $this->db->insert("user", $insertData);
            if ($ret) {
                redirect("/user/login");

            }

        }

    }

    public function validateUserName() {
        $userNmae = trim($_POST['user_name']);
        if ($this->db->get_where("user", array('user_name' => $userNmae))->result_array()) {
            $this->form_validation->set_message('validateUserName', '用户名重复');
            return false;
        } else {
            return true;
        }
    }
}