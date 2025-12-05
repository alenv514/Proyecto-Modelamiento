<?php
include 'db.php';

echo "<h2>Diagnóstico de Base de Datos</h2>";

// 1. Listar columnas actuales
echo "<h3>Columnas actuales en 'usuarios':</h3>";
$result = $conn->query("SHOW COLUMNS FROM usuarios");
$existing_columns = [];
if ($result) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
        $existing_columns[] = $row['Field'];
    }
    echo "</ul>";
} else {
    echo "Error al listar columnas: " . $conn->error;
}

// 2. Intentar agregar columnas si faltan
$missing_cols = [];
if (!in_array('EST_USU', $existing_columns)) {
    $missing_cols['EST_USU'] = "ENUM('L','B') DEFAULT 'L'";
}
if (!in_array('FEC_BAN_USU', $existing_columns)) {
    $missing_cols['FEC_BAN_USU'] = "DATETIME DEFAULT NULL";
}

if (!empty($missing_cols)) {
    echo "<h3>Agregando columnas faltantes...</h3>";
    foreach ($missing_cols as $col => $def) {
        $sql = "ALTER TABLE usuarios ADD COLUMN $col $def";
        echo "Ejecutando: $sql <br>";
        if ($conn->query($sql)) {
            echo "EXITO: Columna $col agregada.<br>";
        } else {
            echo "ERROR al agregar $col: " . $conn->error . "<br>";
        }
    }
} else {
    echo "<h3>Todas las columnas requeridas parecen existir.</h3>";
}

echo "<br>Diagnóstico finalizado.";
?>