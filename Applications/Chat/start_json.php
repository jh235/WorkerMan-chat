<?php 
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
use \Workerman\Worker;
use \Protocols\JsonNL;
use \GatewayWorker\Gateway;
use \GatewayWorker\BusinessWorker;
use \Workerman\Autoloader;

// 自动加载类
require_once __DIR__ . '/../../Workerman/Autoloader.php';
Autoloader::setRootPath(__DIR__);


$json_worker = new Gateway("JsonNL://0.0.0.0:1234");
$json_worker->name = "jiang";
//$json_worker->count = 4;
//$json_worker->lanIp = '127.0.0.1';
//$json_worker->registerAddress = '127.0.0.1:1236';

$json_worker->onMessage = function($connection, $data)
{
    var_dump($data);
    $connection->send('receive success');
};



// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    // Worker::runAll();
}

