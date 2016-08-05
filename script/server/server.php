<?php

/**
 * @Copyright (c) 2016 Rd.Lanjinger.com. All Rights Reserved.
 * @author          lvxinwei <lvxinwei@lvxinwei.com>
 * @version
 * @desc
 */
class server {
    private $_server;
    private $_config = array(
        'worker_num' => 4,
    );
    public function __construct() {
        $this->_initSocektServer();
    }

    public function _initSocektServer() {
        $this->_server = new swoole_server("0.0.0.0", 5557
            
            
            , SWOOLE_BASE);
        $this->_server->on('workerstart', array($this, 'onWorkstart'));

        $this->_server->on('connect', array($this, 'onConnect'));

        $this->_server->on('receive', array($this, 'onReceive'));

        $this->_server->on('close', array($this, 'onClose'));
        $this->_server->set($this->_config);
        $this->_server->start();

    }

    public function onworkstart($server, $id) {
        print "work start";
        print_r($server);
        print_r($id);

    }

    public function onConnect(swoole_server $server, $fd, $from_id) {
        print "connect";
        print_r($server);
        print_r($fd);

        print_r($from_id);

    }

   public function onReceive(swoole_server $server, $fd, $from_id, $data) {
       print "receive";
       print_r($server);
       print_r($fd);
       print_r($from_id);

    }

    public function onClose(swoole_server $server, $fd, $from_id) {
        print "close";
        print_r($server);
        print_r($fd);
        print_r($from_id);

    }

}
$class=new server();
