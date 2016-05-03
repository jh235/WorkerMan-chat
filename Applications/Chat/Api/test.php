<?php

require_once('./response.php');

$arr = array(
  'id' => 1,
  'name' => 'jiang',
  'age' => 18,
  'test' =>array(1,4,7,9)
);

Response::json(200,'success',$arr);

// Response::xmlEncode(200,'success',$arr);
