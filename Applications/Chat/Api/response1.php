<?php
use \Workerman\Protocols\Http;
// $arr = array(
// 	'id' => 1,
// 	'name' => 'jiang'
// 	);


// $data = "输出json数据";
// //$newdata = iconv('UTF8', 'GBK', $data);
// echo json_encode($arr);
// echo json_encode($data);

/**
* 
*/
class Response
{
	/**
	 * 按json格式输出通讯信息
	 * @param  integer 状态码
	 * @param  string  提示信息
	 * @param  array   数据
	 * @return string
	 */
	public static  function json($code,$message ='',$data = array())
	{
		if (!is_numeric($code)) {
			return '';
		}
		$result =array(
			'code' => $code,
			'message' =>$message,
			'data' => $data,
			);
		echo json_encode($result);
		Http::end('');
		// exit;
	}

	/**
	 * 按xml格式输出通讯信息
	 * @param  integer 状态码
	 * @param  string  提示信息
	 * @param  array   数据
	 * @return string
	 */
	public static function xmlEncode($code,$message='',$data)
	{
		if (!is_numeric($code)) {
			return '';
		}
		$result = array(
			'code' =>$code,
			'message' => $message,
			'data' => $data,
			 );
		// header("Content-Type:text/xml");
		header("Content-Type:text/xml");

		$xml = "<?xml version = 1.0 encoding = 'UTF-8'?>\n";
		$xml.= "<root>\n";
		$xml.= self::xmlToEncode($result);
		$xml.= "</root>\n";

		echo $xml;

	}

	public static function xmlToEncode($data)
	{
		$xml = $attr = "";

		foreach ($data as $key => $value) {
			if (is_numeric($key)) {
				$attr = "id = '{$key}'";
				$key = 'item';
			}

			$xml .= "<{$key}{$attr}>";
			$xml .= is_array($value)?self::xmlToEncode($value):$value;
			$xml .= "</{$key}>\n";

		}
		return $xml;
	}
}


