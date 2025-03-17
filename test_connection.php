<?php
require 'config.php';
$conn = getDBConnection();
if ($conn instanceof PDO) {
    echo 'Connection successful!';
} else {
    echo $conn;
}
?>