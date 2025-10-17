<?php
session_start();
include 'db.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];
    $sql = "SELECT u.*, t.nombre_tipo FROM usuarios u INNER JOIN tipos_usuario t ON u.id_tipo_usuario = t.ID_TIP WHERE u.nombre_usuario=? AND u.contrasena=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $usuario, $contrasena);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $_SESSION['usuario'] = $usuario;
        $_SESSION['tipo_usuario'] = $row['nombre_tipo'];
        // Redirección según tipo de usuario
        switch (strtoupper($row['nombre_tipo'])) {
            case 'ADMIN':
                header("Location: admin/dashboard.php");
                break;
            case 'COMPRADOR':
                header("Location: comprador/dashboard.php");
                break;
            case 'VENDEDOR':
                header("Location: vendedor/dashboard.php");
                break;
            case 'MODERADOR':
                header("Location: moderador/dashboard.php");
                break;
            default:
                $error = "Tipo de usuario no válido.";
                break;
        }
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <div class="form-container">
        <h2>Login</h2>
        <form method="POST">
            <input type="text" name="usuario" placeholder="Usuario" required>
            <input type="password" name="contrasena" placeholder="Contraseña" required>
            <button type="submit">Ingresar</button>
        </form>
        <p style="color:red;"><?php echo $error; ?></p>
        <a href="registro.php"><button>Registrarse</button></a>
    </div>
</body>
</html>
