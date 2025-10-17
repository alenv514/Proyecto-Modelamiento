<?php
include 'db.php';

$mensaje = "";

// Obtener el id del tipo de usuario 'COMPRADOR'
$sql_tipo = "SELECT ID_TIP FROM tipos_usuario WHERE nombre_tipo = 'COMPRADOR' LIMIT 1";
$res_tipo = $conn->query($sql_tipo);
$id_tipo_usuario = ($res_tipo && $res_tipo->num_rows > 0) ? $res_tipo->fetch_assoc()['ID_TIP'] : 3; // fallback a 3

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_usuario = $_POST['nombre_usuario'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $celular = $_POST['celular'];
    $contrasena = $_POST['contrasena'];
    // $id_tipo_usuario ya está definido arriba

    $sql = "INSERT INTO usuarios (nombre_usuario, nombre, apellido, correo, fecha_nacimiento, celular, contrasena, id_tipo_usuario)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssi", $nombre_usuario, $nombre, $apellido, $correo, $fecha_nacimiento, $celular, $contrasena, $id_tipo_usuario);
    if ($stmt->execute()) {
        $mensaje = "Registro exitoso. <a href='login.php'>Iniciar sesión</a>";
    } else {
        $mensaje = "Error al registrar: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Registro</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <div class="form-container">
        <h2>Registro</h2>
        <form method="POST">
            <input type="text" name="nombre_usuario" placeholder="Usuario" required>
            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="text" name="apellido" placeholder="Apellido" required>
            <input type="email" name="correo" placeholder="Correo" required>
            <input type="date" name="fecha_nacimiento" required>
            <input type="text" name="celular" placeholder="Celular" required maxlength="10">
            <input type="password" name="contrasena" placeholder="Contraseña" required>
            <!-- El tipo de usuario se asigna automáticamente como COMPRADOR -->
            <button type="submit">Registrarse</button>
        </form>
        <p style="color:green;"><?php echo $mensaje; ?></p>
        <a href="login.php"><button>Volver al login</button></a>
    </div>
</body>
</html>
