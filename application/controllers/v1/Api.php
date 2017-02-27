<?php
class Api extends CI_Controller {
    public function __construct(){
        parent::__construct();
    }
    public function get_weather(){
        $cityName=$_GET['city_name'];
        $day=$_GET['day'];
        if(empty($cityName)||empty($day)){
            echo "empty params";
            exit;
        }
        $ret=$this->db->get_where("weather",array("time"=>$day,"city_name"=>$cityName))->result_array();
        if(!$ret){
            echo "no result";
            exit;
        }
        echo json_encode($ret[0]);
        
        
    }
}