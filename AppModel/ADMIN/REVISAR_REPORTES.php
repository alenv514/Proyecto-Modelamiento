<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../PAG_INICIO/navbar.php';
?>
<!DOCTYPE html>
<html lang="es">

    <head>
        <meta charset="utf-8">
        <title>Revisar reportes</title>
        <link rel="stylesheet" href="/Proyecto-Modelamiento/AppModel/PAG_INICIO/styles.css">
    </head>

    <body>
        <?php echo renderNavbar(); ?>
    </body>

</html>