<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Prueba de Subida de Archivos y Creación de Directorios</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "--- PROCESANDO PETICIÓN POST ---<br>";

    // 1. Verificar si se subió un archivo
    if (!isset($_FILES['test_image']) || $_FILES['test_image']['error'] !== UPLOAD_ERR_OK) {
        die("<strong style='color:red;'>FALLO: No se subió ningún archivo o hubo un error en la subida. Código de error: " . ($_FILES['test_image']['error'] ?? 'N/A') . "</strong>");
    }
    $file = $_FILES['test_image'];
    echo "Archivo temporal recibido: " . htmlspecialchars($file['tmp_name']) . "<br>";

    // 2. Definir directorios
    $baseDir = __DIR__ . '/uploads/products';
    $userDir = $baseDir . '/test_user';
    $destination = $userDir . '/' . $file['name'];

    echo "Directorio base a crear: " . htmlspecialchars($baseDir) . "<br>";
    echo "Directorio de usuario a crear: " . htmlspecialchars($userDir) . "<br>";
    echo "Destino final del archivo: " . htmlspecialchars($destination) . "<br>";

    // 3. Intentar crear el directorio base
    if (!is_dir($baseDir)) {
        echo "Intentando crear directorio base...<br>";
        if (mkdir($baseDir, 0755, true)) {
            echo "<span style='color:green;'>Éxito al crear el directorio base.</span><br>";
        } else {
            die("<strong style='color:red;'>FALLO: No se pudo crear el directorio base '$baseDir'.</strong>");
        }
    } else {
        echo "Directorio base ya existe.<br>";
    }

    // 4. Intentar crear el directorio de usuario
    if (!is_dir($userDir)) {
        echo "Intentando crear directorio de usuario...<br>";
        if (mkdir($userDir, 0755, true)) {
            echo "<span style='color:green;'>Éxito al crear el directorio de usuario.</span><br>";
        } else {
            die("<strong style='color:red;'>FALLO: No se pudo crear el directorio de usuario '$userDir'.</strong>");
        }
    } else {
        echo "Directorio de usuario ya existe.<br>";
    }

    // 5. Intentar mover el archivo
    echo "Intentando mover el archivo subido...<br>";
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        echo "<h3 style='color:green;'>¡ÉXITO TOTAL!</h3>";
        echo "<p>El archivo se subió y movió correctamente a su destino.</p>";
        echo "<p>Esto significa que la lógica de subida de archivos y creación de directorios funciona perfectamente. El problema debe estar en cómo se integran los datos en el script principal (AÑADIR_PRODUCTO.php).</p>";
    } else {
        die("<strong style='color:red;'>FALLO CRÍTICO: No se pudo mover el archivo de '" . htmlspecialchars($file['tmp_name']) . "' a '" . htmlspecialchars($destination) . "'.</strong>");
    }

    echo "<hr><a href='test_upload.php'>Volver a intentar</a>";

} else {
    ?>
    <p>Este script probará si el servidor tiene permisos para crear las carpetas necesarias y mover un archivo subido.</p>
    <form method="POST" enctype="multipart/form-data">
        <label for="test_image">Selecciona una imagen de prueba:</label><br><br>
        <input type="file" name="test_image" id="test_image" required>
        <br><br>
        <button type="submit">Subir y Probar</button>
    </form>
    <?php
}
?>
