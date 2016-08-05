# 物联网控制系统
## 系统架构
## 数据格式
Redis中的接收到的数据和发出的数据均为16进制明文,非ANSCII码

发送控制命令到redis,格式为json

{'type':命令类型,'time:时间戳,'data':命令内容}

## MySQL数据库格式
### 表device_template
设备模版表
### 表command_template
与设备模版表关联的命令模版表

且字段含义如下:

command_name:控制名称

command_type:命令类型,1为控制开关,2为读传感器数据

command_desc:控制描述,用列表表示,例如:

[1,1,1,1]表示这个设备模版有四个开关设备,该命令为四个开关全开

command_data:发送命令内容,16进制明文

response_data:期待发挥的内容,支持正则表达式

## 系统使用
1.建立设备模版

2.为设备模版添加控制命令

3.建立新设备并把模版应用于该设备



