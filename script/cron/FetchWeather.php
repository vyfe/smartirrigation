<?php

/**
 * Created by PhpStorm.
 * User: lvxinwei
 * Date: 16/4/6
 * Time: 下午3:28
 */
require_once realpath(dirname(__FILE__)) . "/../Library/php-selector/selector.php";
require_once realpath(dirname(__FILE__)) . "/../Library/HttpClient.php";
require_once realpath(dirname(__FILE__)) . "/../Library/MysqliDb.php";

class FetchWeather {
    private $_httpClient;
    private $_db;

    public function __construct() {
        $this->_db = new MysqliDb(Array(
                'host' => '127.0.0.1',
                'username' => 'root',
                'password' => 'xinweiustc',
                'db' => 'smartirrigation',
                'port' => 3306,
                'prefix' => '',
                'charset' => 'utf8',
            )
        );
        $this->_httpClient = new HttpClient();
        $this->_httpClient->setUserAgent("Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML,
        like Gecko) Chrome/48.0.2564.109 Safari/537.36");
    }

    public function getNum($data) {
        if(strlen($data)<1){
            return 100;
        }
        $ret = 0;
        $flag = 1;
        if ($data[0] == '-') {
            $flag = -1;
        } else if (is_numeric($data[0])) {
            $ret = $data[0];
        } else {
            return '';
        }
        for ($i = 1; $i < strlen($data); $i++) {
            if (!is_numeric($data[$i])) {
                break;
            }
            $ret = $ret * 10 + $data[$i];
        }
        return $ret * $flag;
    }
    public function run(){
        $cityInfo=$this->_db->get('city');
        foreach($cityInfo as $key=>$value){
            $cityId=$value['city_id'];
            $cityName=$value['city_name'];
            $data=$this->getWeatherData($cityId);
            $data['city_id']=$cityId;
            $data['city_name']=$cityName;
            $data['time']=date("Y-m-d",time());
            $id=$this->_db->insert('weather',$data);
            usleep(10);
        }


    }

    function getWeatherData($cityid) {
        $ret = array();
        $low_temp_index=1;
        $high_temp_index=1;
        $weather_index=1;
        $win_index=1;
        $url = "http://www.weather.com.cn/weather/" . $cityid . ".shtml";
        $content = $this->_httpClient->fetchUrl($url);
        $dom = new SelectorDOM($content);
        $lowTemp = $dom->select("div#7d .tem i");
        foreach ($lowTemp as $key => $value) {
            $value = $this->getNum($value['text']);
            $ret['low_temp'.$low_temp_index++]=$value;
        }
        $highTemp = $dom->select("div#7d .tem span");
        foreach ($highTemp as $key => $value) {
            $value = $this->getNum($value['text']);
            $ret['high_temp'.$high_temp_index++]=$value;
        }
        $weather = $dom->select("div#7d .wea");
        foreach ($weather as $key => $value) {
             $ret['weather'.$weather_index++]=mb_substr($value['text'], 0, 20);
        }
        $win = $dom->select("div#7d .win i");
        foreach ($win as $key => $value) {
            $ret['win'.$win_index++]=mb_substr($value['text'], 0, 20);
        }
        /**
         * 开始抓取15天的
         */
        $url = "http://www.weather.com.cn/weather15d/" . $cityid . ".shtml";
        $content = $this->_httpClient->fetchUrl($url);
        $dom = new SelectorDOM($content);
        $lowTemp = $dom->select("div#15d .tem");
        foreach ($lowTemp as $key => $value) {
            $temp = explode("/", $value['text']);
            $value = $this->getNum($temp[1]);
            $ret['low_temp'.$low_temp_index++]=$value;
        }
        $highTemp = $dom->select("div#15d .tem em");
        foreach ($highTemp as $key => $value) {
            $value = $this->getNum($value['text']);
             $ret['high_temp'.$high_temp_index++]= $value;
        }
        $weather = $dom->select("div#15d .wea");
        foreach ($weather as $key => $value) {
            $ret['weather'.$weather_index++]= mb_substr($value['text'], 0, 20);
        }
        $win = $dom->select("div#15d .wind1");
        foreach ($win as $key => $value) {
            $ret['win'.$win_index++]= mb_substr($value['text'], 0, 20);
        }
        return $ret;
    }
}

$fw = new FetchWeather();
$fw->run();


//echo $fw->getNum('aa-23aaa');
