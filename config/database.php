<?php
define('DB_HOST', 'sql102.infinityfree.com');
define('DB_USER', 'if0_37872689');
define('DB_PASS', '8nr3IwUrvM');
define('DB_NAME', 'if0_37872689_petcaredb'); 

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// After establishing the connection
 $conn->query("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));"); 

$conn->set_charset("utf8");

return $conn;
?>