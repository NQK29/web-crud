<?php
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "doan_web"; 

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
// Set charset UTF-8 để hiển thị tiếng Việt
$conn->set_charset("utf8");
?>