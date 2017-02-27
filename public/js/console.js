/**
 * Created by lvxinwei on 2016/4/18.
 */
var Kit=new Kit();
function callback(data){
    if(data.errno==0){
        alert("命令发送成功");
    } else{
        alert(data.msg);
    }
}
$(".command").click(function(){
    queryParam=new Object();
    queryParam['device_id']=$(this).attr("device-id");
    queryParam['command_id']=$(this).attr("command-id");
    queryParam['sign']=$(this).attr("sign");
    console.log(queryParam);
    Kit.query("/api/send_command",queryParam,callback)
})

$(".relay").click(function (event) {
    var relayStatus=new Object();
    var length=0;
    var device_id=$(this).attr("device-id");
    $(".relay[device-id="+device_id+"]").each(function () {
        value=parseInt($(this).attr("value"))
        order=parseInt($(this).attr("order"))
        relayStatus[order]=value;
        length++;
    })
    value=parseInt($(this).attr("value"))
    value_desc=""
    if(value){
        value=0;
        value_desc="关"
    } else {
        value=1
        value_desc="开"
    }
    order=parseInt($(this).attr("order"))
    relayStatus[order]=value;
    var set_relay_status=new Array();
    for(var i=0;i<length;i++){
        set_relay_status.push(relayStatus[i])
    }
    queryParam=new Object();
    queryParam['device_id']=$(this).attr("device-id");
    queryParam['sign']=$(this).attr("sign");
    queryParam['set_relay_status']=JSON.stringify(set_relay_status)
    check=confirm("是否要"+value_desc+" "+queryParam['device_id']+ "设备 第"+(order+1)+"开关")
    if(!check){
        return
    }
    Kit.query("/api/send_command",queryParam,callback)
})
var lastChangeTime=1;
function callback_get_device_info(parameters) {
    var data = parameters.data;
    if(data.errno==0){
        lastChangeTime=data.time;
        var data=data.data;
        for(device_id in data){
            console.log(data[device_id]['net']);
             $(".net-status[device-id="+device_id+"]").text(data[device_id]['net']);
            $(".update-time[device-id="+device_id+"]").text(data[device_id]['update_time']);
            //传感器数据更新
            sensor_value=JSON.parse(data[device_id]['sensor_value']);
            for(i=0;i<sensor_value.length;i++){
                value=sensor_value[i];
                $(".sensor-value[device-id="+device_id+"]"+"[order="+i+"]").text(value)
            }
            //更新开关状态
            relay_value=JSON.parse(data[device_id]['relay_value']);//TODO:开关状态不同步时会连续弹出提示窗口
            for(i=0;i<relay_value.length;i++){
                value=relay_value[i];
                now_value= $(".relay[device-id="+device_id+"]"+"[order="+i+"]").val();
                $(".relay[device-id="+device_id+"]"+"[order="+i+"]").val(value);//对页面中的开关状态进行变换，不成功？
                //下面就是对按钮属性的操作
                if(value){
                    $(".relay[device-id="+device_id+"]"+"[order="+i+"]").removeClass("btn-warning btn-info").addClass("btn-warning");
                    $(".relay[device-id="+device_id+"]"+"[order="+i+"]").text("开");
                    if(value!=now_value){
                        alert(device_id+"设备第"+(i+1)+"开关已经开启成功")
                    }
                } else {
                    $(".relay[device-id="+device_id+"]"+"[order="+i+"]").removeClass("btn-warning btn-info").addClass("btn-info")
                    $(".relay[device-id="+device_id+"]"+"[order="+i+"]").text("关")
                    if(value!=now_value){
                        alert(device_id+"设备第"+(i+1)+"开关已经关闭成功")
                    }
                }
            }

        }
    }
}
function getDeviceValue() {
    var queryParam=new Object();
    queryParam['lastChangeTime']=lastChangeTime;
    console.log(queryParam)
    Kit.query("/api/get_devices_info",queryParam,callback_get_device_info)
}
getDeviceValue();
setInterval(getDeviceValue,1000);//每秒从数据库刷新一次，包括联网状态、传感器数据、开关状态，此处仅涉及数据库操作。若有变化，显示弹出窗口
