<?php
$servername = "localhost:3307";
$username = "root";
$password = "123456";
$dbname = "cms";

// 创建连接
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->query("SET NAMES utf8");
// 检测连接
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

$sql = "SELECT `id`, `shopname`, `shopid`, `building`, `px`, `py`, `lx`, `ly`, `phone`, `introduce`, `logo`, `img`, `time`, `author`, `typeid`, `comment_count` FROM `articles` WHERE 1";
$result = $conn->query($sql);




// echo $sql;

// var_dump($result->num_rows);
// var_dump($result);

if ($result->num_rows > 0) {
    // 输出每行数据
    while($row = $result->fetch_assoc()) {
    	// var_dump($row);
        echo "<br> id: ". $row["id"]. " - shopname: ". $row["shopname"]. "-shopid: " . $row["shopid"]. "-building: " . $row["building"]. "-phone: " . $row["phone"];
    }
} else {
    echo "0 results";
}
$conn->close();
?>