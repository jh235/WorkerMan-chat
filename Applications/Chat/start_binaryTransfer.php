<?php

use Workerman\Worker;
use \Protocols\JsonNL;
use \GatewayWorker\Gateway;
use \GatewayWorker\BusinessWorker;
use \Workerman\Autoloader;


$worker = new Worker('BinaryTransfer://0.0.0.0:8333');
$worker ->name = "data";


// 保存文件到tmp下
$worker->onMessage = function($connection, $data)
{
    $save_path = '/tmp/'.$data['file_name'];
    file_put_contents($save_path, $data['file_data']);
    $connection->send("upload success. save path $save_path");
};

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

