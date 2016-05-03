<?php

/** 
 *struct
{
  unsigned int total_len;  // 整个包的长度，大端网络字节序
  char         name_len;   // 文件名的长度
  char         name[name_len]; // 文件名
  char         file[total_len - BinaryTransfer::PACKAGE_HEAD_LEN - name_len]; // 文件数据
}
 */


namespace Protocols;
class BinaryTransfer
{
// 协议头长度
const PACKAGE_HEAD_LEN = 5;

public static function input($recv_buffer)
{
    // 如果不够一个协议头的长度，则继续等待
    if(strlen($recv_buffer) < self::PACKAGE_HEAD_LEN)
    {
    return 0;
    }
    // 解包
    $package_data = unpack('Ntotal_len/Cname_len', $recv_buffer);
    // 返回包长
    return $package_data['total_len'];
    }


    public static function decode($recv_buffer)
    {
    // 解包
    $package_data = unpack('Ntotal_len/Cname_len', $recv_buffer);
    // 文件名长度
    $name_len = $package_data['name_len'];
    // 从数据流中截取出文件名
    $file_name = substr($recv_buffer, self::PACKAGE_HEAD_LEN, $name_len);
    // 从数据流中截取出文件二进制数据
    $file_data = substr($recv_buffer, self::PACKAGE_HEAD_LEN + $name_len);
    return array(
    'file_name' => $file_name,
    'file_data' => $file_data,
    );
    }

    public static function encode($data)
    {
    // 可以根据自己的需要编码发送给客户端的数据，这里只是当做文本原样返回
    return $data;
    }
}