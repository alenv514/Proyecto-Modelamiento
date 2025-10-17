<?php
$host = "localhost";
$user = "root";
$pass = "110523";
$db = "appmodel";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>
