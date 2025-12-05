<?php
session_start();

$tipo_usuario = isset($_SESSION['tipo_usuario']) ? $_SESSION['tipo_usuario'] : 'USUARIO';
$nombre_usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ShopMate Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php require_once __DIR__ . '/navbar.php'; echo renderNavbar(); ?>

    <div class="main-content" style="display:flex;align-items:center;justify-content:center;min-height:calc(100vh - 80px);padding-top:20px;">
        <div class="welcome-box" style="background:#fff;border-radius:10px;box-shadow:0 2px 8px #0001;padding:40px 48px;min-height:120px;display:flex;align-items:center;justify-content:center;text-align:center;">
            <div style="font-size:1.5em;font-weight:600;color:#176d7a;">
                Bienvenido<?php echo $nombre_usuario ? ", <span style='color:#2ca8c6;'>$nombre_usuario</span>" : ""; ?>
            </div>
        </div>
    </div>
</body>
</html>
