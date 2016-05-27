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

/**
 * 聊天主逻辑
 * 主要是处理 onMessage onClose 
 */
use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\Store;
use \GatewayWorker\Lib\Db;

class Event
{
   
   /**
    * 有消息时
    * @param int $client_id
    * @param string $message
    */
   public static function onMessage($client_id, $message)
   {

        // debug
        echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:".json_encode($_SESSION)." onMessage:".$message."\n";
        
        // 客户端传递的是json数据
        $message_data = json_decode($message, true);

        // var_dump($message_data);
        if(!$message_data)
        {
            return ;
        }
        
        // 根据类型执行不同的业务
        switch($message_data['type'])
        {
            // 客户端回应服务端的心跳
            case 'pong':
                return;

            //注册
            case 'register':
                if(!isset($message_data['user_name']) || !isset($message_data['password'])){
                   
                    throw new \Exception("\$message_data['user_name'] or \$message_data['password'] not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                }

                $user_name = $message_data['user_name'];
                $userID = self::getUserID();
                $password = $message_data['password'];
                $email = $message_data['email'];
                $group = $message_data['group'];

                $insert_id = Db::instance('db1')->insert('users')->cols(array('username'=>$user_name, 'userID'=>$userID, 'email'=>$email, 'password'=>$password,'joined'=>date('Y-m-d H:i:s'), 'group' =>$group))->query();

                // var_dump($insert_id);

                $_SESSION['userID'] = $userID;
                $_SESSION['user_name'] =  $user_name;
             

                return;
               

            // 客户端登录 message格式: {type:login, name:xx, room_id:1} ，添加到客户端，广播给所有客户端xx进入聊天室
            case 'login':
                // 判断是否有房间号
                if(!isset($message_data['room_id']))
                {
                    throw new \Exception("\$message_data['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                }
                
                // 把房间号昵称放到session中
                $room_id = $message_data['room_id'];
                $client_name = htmlspecialchars($message_data['client_name']);
                $_SESSION['room_id'] = $room_id;
                $_SESSION['client_name'] = $client_name;
              
                // 获取房间内所有用户列表 
                $clients_list = Gateway::getClientInfoByGroup($room_id);
                foreach($clients_list as $tmp_client_id=>$item)
                {
                    $clients_list[$tmp_client_id] = $item['client_name'];
                }
                $clients_list[$client_id] = $client_name;
                
                // 转播给当前房间的所有客户端，xx进入聊天室 message {type:login, client_id:xx, name:xx} 
                $new_message = array('type'=>$message_data['type'], 'client_id'=>$client_id, 'client_name'=>htmlspecialchars($client_name), 'time'=>date('Y-m-d H:i:s'));
                Gateway::sendToGroup($room_id, json_encode($new_message));
                Gateway::joinGroup($client_id, $room_id);
               
                // 给当前用户发送用户列表 
                $new_message['client_list'] = $clients_list;
                Gateway::sendToCurrentClient(json_encode($new_message));
                return;
                
            // 客户端发言 message: {type:say, to_client_id:xx, content:xx}
            case 'say':
                // 非法请求
                if(!isset($_SESSION['room_id']))
                {
                    throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $room_id = $_SESSION['room_id'];
                $client_name = $_SESSION['client_name'];
                
                // 私聊
                if($message_data['to_client_id'] != 'all')
                {
                    $new_message = array(
                        'type'=>'say',
                        'from_client_id'=>$client_id, 
                        'from_client_name' =>$client_name,
                        'to_client_id'=>$message_data['to_client_id'],
                        'content'=>"<b>对你说: </b>".nl2br(htmlspecialchars($message_data['content'])),
                        'time'=>date('Y-m-d H:i:s'),
                    );
                    Gateway::sendToClient($message_data['to_client_id'], json_encode($new_message));
                    $new_message['content'] = "<b>你对".htmlspecialchars($message_data['to_client_name'])."说: </b>".nl2br(htmlspecialchars($message_data['content']));
                    return Gateway::sendToCurrentClient(json_encode($new_message));
                }
                
                $new_message = array(
                    'type'=>'say', 
                    'from_client_id'=>$client_id,
                    'from_client_name' =>$client_name,
                    'to_client_id'=>'all',
                    'content'=>nl2br(htmlspecialchars($message_data['content'])),
                    'time'=>date('Y-m-d H:i:s'),
                );
                return Gateway::sendToGroup($room_id ,json_encode($new_message));
        }
   }
   
   /**
    * 当客户端断开连接时
    * @param integer $client_id 客户端id
    */
   public static function onClose($client_id)
   {
       // debug
       echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id onClose:''\n";
       
       // 从房间的客户端列表中删除
       if(isset($_SESSION['room_id']))
       {
           $room_id = $_SESSION['room_id'];
           $new_message = array('type'=>'logout', 'from_client_id'=>$client_id, 'from_client_name'=>$_SESSION['client_name'], 'time'=>date('Y-m-d H:i:s'));
           Gateway::sendToGroup($room_id, json_encode($new_message));
       }
   }

   public static function generate_license($start = 0, $end = 0, $num = 10)
   {
        $license_list = array();

        $i = 1;
        $retry = 100;

        while ($i <= $num) {
            $tmp_num = self::is_better_id(mt_rand($start, $end));
            if ($tmp_num) {
                $license_list[$i] = $tmp_num;
                $i++;
            } else {
                $retry--;
            }
            if ($retry <= 0) {
                break;
            }
        }
        return $license_list;
    }

    public static function is_better_id($license_id = '')
    {
        if (!$license_id) {
            return 0;
        }
        if (strlen($license_id) < 6) {
            $license_id = str_pad($license_id, 6, '0', STR_PAD_LEFT);
        }
        $better_list = array(
            '111', '222', '333', '444', '555', '666', '777', '888', '999', '000', 
            '123', '234', '345', '456', '567', '678', '789',
            '321', '432', '543', '654', '765', '876', '987',
            '520', '521'
        );
        $number_list  = array();
        $start        = 0;
        $is_better_id = false;
        $end          = strlen($license_id);

        while ($start < $end) {
            $tmp_num = substr($license_id, $start, 3);
            if (strlen($tmp_num) == 3) {
                $number_list[] = $tmp_num;
            }
            $start += 3;
        }
        foreach($number_list as $k => $v) {
            if (in_array($v, $better_list)) {
                $is_better_id = true;
                break;
            }
        }
        if ($is_better_id) {
            return 0;
        } else {
            return $license_id;
        }
    }


    public static function getUserID(){

        $i = 100000;

        $ret = Db::instance('db1')->single("SELECT MAX(userID) FROM users");

        // var_dump($ret);

        if ($ret < $i) {
            // echo "string";
            return $i;
           
        }
            return ++$ret ;
             
        
    }
}
