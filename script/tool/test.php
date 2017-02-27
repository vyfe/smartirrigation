<?php
/**
 * Created by PhpStorm.
 * User: lvxinwei
 * Date: 2016/4/21
 * Time: 0:54
 */
$s="0101010C518D";
var_dump(preg_match("/0101\w{3}(\w{1})/", $s, $matches));
print_r($matches);
function get_bin($num,$length=4){
    $bin=decbin($num);
    $ret=str_repeat("0",$length-strlen($bin)).$bin;
    $ret=str_split($ret);
    foreach ($ret as $key=>$value){
        $ret[$key]=(int)$value;
    }
    return $ret;
}
print_r(get_bin(hexdec($matches[1])));