<?php
include 'db.php';

echo "<h2>Corrigiendo rutas de imágenes...</h2>";

// Buscar imágenes con la ruta incorrecta
$sql = "SELECT ID_IMG, URL_IMG FROM IMAGENES_PRODUCTOS WHERE URL_IMG LIKE '/AppModel/uploads/%'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Encontradas " . $result->num_rows . " imágenes con ruta incorrecta.<br>";

    while ($row = $result->fetch_assoc()) {
        $id = $row['ID_IMG'];
        $old_url = $row['URL_IMG'];
        $new_url = str_replace('/AppModel/uploads/', '/Proyecto-Modelamiento/AppModel/uploads/', $old_url);

        $update = $conn->prepare("UPDATE IMAGENES_PRODUCTOS SET URL_IMG = ? WHERE ID_IMG = ?");
        $update->bind_param("si", $new_url, $id);

        if ($update->execute()) {
            echo "Corregido ID $id: $old_url -> $new_url<br>";
        } else {
            echo "Error al corregir ID $id: " . $conn->error . "<br>";
        }
    }
} else {
    echo "No se encontraron imágenes con rutas incorrectas (que empiecen por /AppModel/uploads/).<br>";
}

// Verificación extra: buscar si hay alguna que ya esté bien
$sql_ok = "SELECT COUNT(*) as c FROM IMAGENES_PRODUCTOS WHERE URL_IMG LIKE '/Proyecto-Modelamiento/AppModel/uploads/%'";
$res_ok = $conn->query($sql_ok);
$row_ok = $res_ok->fetch_assoc();
echo "Imágenes con ruta correcta: " . $row_ok['c'] . "<br>";

echo "<br>Proceso finalizado.";
?>