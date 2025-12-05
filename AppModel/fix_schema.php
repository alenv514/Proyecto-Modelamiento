<?php
include 'db.php';

function checkAndAddColumn($conn, $table, $column, $definition)
{
    $check = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
    if ($check->num_rows == 0) {
        echo "Agregando columna $column a $table...<br>";
        if ($conn->query("ALTER TABLE $table ADD COLUMN $column $definition")) {
            echo "Columna $column agregada exitosamente.<br>";
        } else {
            echo "Error agregando columna $column: " . $conn->error . "<br>";
        }
    } else {
        echo "La columna $column ya existe en $table.<br>";
    }
}

// Verificar y agregar columnas faltantes en usuarios
checkAndAddColumn($conn, 'usuarios', 'EST_USU', "ENUM('L','B') DEFAULT 'L'");
checkAndAddColumn($conn, 'usuarios', 'FEC_BAN_USU', "DATETIME DEFAULT NULL");

echo "Verificación de esquema completada.";
?>