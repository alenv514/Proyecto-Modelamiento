<?php
// Conexión centralizada a la base de datos usada por los scripts
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '2805'; 
$DB_NAME = 'appmodel';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
// Verificar conexión
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Establecer charset
$conn->set_charset("utf8mb4");
?>
