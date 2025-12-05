<?php
session_start();
include 'db.php';

// Registro normal: insertar usuario directamente tras validación
$mensaje = "";

$sql_tipo = "SELECT ID_TIP FROM tipos_usuario WHERE nombre_tipo = 'USUARIO' LIMIT 1";
$res_tipo = $conn->query($sql_tipo);
$id_tipo_usuario = ($res_tipo && $res_tipo->num_rows > 0) ? $res_tipo->fetch_assoc()['ID_TIP'] : 3;

$pending = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // obtener valores (mantener en caso de error para repoblar formulario)
    $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $fec_d = $_POST['fec_d'] ?? '';
    $fec_m = $_POST['fec_m'] ?? '';
    $fec_y = $_POST['fec_y'] ?? '';
    $fecha_nacimiento = '';
    if ($fec_y && $fec_m && $fec_d) {
        $fecha_nacimiento = sprintf('%04d-%02d-%02d', (int)$fec_y, (int)$fec_m, (int)$fec_d);
    } else {
        $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
    }
    $celular = trim($_POST['celular'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    // guardar en $pending para repoblar el formulario si hay errores
    $pending = [
        'NIC_USU' => $nombre_usuario,
        'NOM_USU' => $nombre,
        'APE_USU' => $apellido,
        'COR_USU' => $correo,
        'FEC_D' => $fec_d,
        'FEC_M' => $fec_m,
        'FEC_Y' => $fec_y,
        'FEC_NAC_USU' => $fecha_nacimiento,
        'TEL_USU' => $celular
    ];

    // validaciones
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "El correo no tiene un formato válido.";
    } else {
        $celular_clean = preg_replace('/\D+/', '', $celular);
        if (strlen($celular_clean) !== 10) {
            $mensaje = "El número de celular debe contener exactamente 10 dígitos.";
        } elseif (strlen($nombre_usuario) < 3) {
            $mensaje = "El usuario debe tener al menos 3 caracteres.";
        } elseif (strlen($contrasena) < 6) {
            $mensaje = "La contraseña debe tener al menos 6 caracteres.";
        } else {
            // comprobar disponibilidad del nombre de usuario (NIC_USU)
            $sql_check = "SELECT ID_USU FROM usuarios WHERE NIC_USU = ? LIMIT 1";
            if ($stmt_check = $conn->prepare($sql_check)) {
                $stmt_check->bind_param("s", $nombre_usuario);
                $stmt_check->execute();
                $stmt_check->store_result();
                if ($stmt_check->num_rows > 0) {
                    $mensaje = "El nombre de usuario ya está en uso. Elige otro.";
                    $stmt_check->close();
                } else {
                    $stmt_check->close();
                    // insertar usuario directamente
                    $pass_hash = password_hash($contrasena, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO usuarios (NIC_USU, NOM_USU, APE_USU, COR_USU, FEC_NAC_USU, TEL_USU, CON_USU, ID_TIP_USU)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    if ($stmt = $conn->prepare($sql)) {
                        $tel_final = $celular_clean;
                        $stmt->bind_param("sssssssi", $nombre_usuario, $nombre, $apellido, $correo, $fecha_nacimiento, $tel_final, $pass_hash, $id_tipo_usuario);
                        if ($stmt->execute()) {
                            $mensaje = "Registro completado. Ya puedes iniciar sesión.";
                            // limpiar $pending para que el formulario quede vacío
                            $pending = null;
                            // opcional: redirigir al login automáticamente
                            header("Refresh:2; url=login.php");
                        } else {
                            $mensaje = "Error al registrar: " . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        $mensaje = "Error interno en la preparación de la consulta: " . $conn->error;
                    }
                }
            } else {
                $mensaje = "Error interno al verificar nombre de usuario. Intenta de nuevo.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Registro</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
    body {
        margin: 0;
        font-family: 'Segoe UI', Arial, sans-serif;
        background: linear-gradient(120deg, #f4f8fb 60%, #e3f1f8 100%);
        color: #222;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .register-container {
        background: #fff;
        padding: 38px 36px 28px 36px;
        border-radius: 18px;
        box-shadow: 0 6px 32px #176d7a18, 0 1.5px 8px #2ca8c610;
        width: 390px;
        display: flex;
        flex-direction: column;
        align-items: center;
        animation: fadeIn 0.8s;
        gap: 0.5em;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(30px);}
        to { opacity: 1; transform: translateY(0);}
    }
    .register-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 2em;
        font-weight: bold;
        color: #176d7a;
        margin-bottom: 8px;
        letter-spacing: 1px;
        user-select: none;
    }
    .register-logo-icon {
        font-size: 1.2em;
        background: linear-gradient(135deg, #2ca8c6 60%, #176d7a 100%);
        color: #fff;
        border-radius: 50%;
        padding: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px #2ca8c633;
    }
    .register-title {
        font-size: 1.18em;
        font-weight: 500;
        margin-bottom: 18px;
        color: #176d7a;
        letter-spacing: 0.5px;
    }
    .register-form {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 14px;
        margin-bottom: 8px;
        align-items: center;
    }
    .register-form input,
    .register-form button {
        width: 100%;
        max-width: 290px;
        box-sizing: border-box;
    }
    .register-form input {
        padding: 12px 14px;
        border: 1.5px solid #e3e8ee;
        border-radius: 7px;
        font-size: 1.03em;
        background: #f8fafc;
        transition: border 0.2s, box-shadow 0.2s;
        box-shadow: 0 1px 2px #2ca8c610;
        margin: 0 auto;
        display: block;
    }
    .register-form input:focus {
        border: 1.5px solid #2ca8c6;
        outline: none;
        background: #fff;
        box-shadow: 0 2px 8px #2ca8c622;
    }
    .register-form button {
        padding: 13px;
        background: linear-gradient(90deg, #176d7a 60%, #2ca8c6 100%);
        color: #fff;
        border: none;
        border-radius: 7px;
        font-size: 1.09em;
        font-weight: 500;
        margin-top: 6px;
        cursor: pointer;
        transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
        box-shadow: 0 2px 8px #176d7a22;
        letter-spacing: 0.2px;
        margin: 0 auto;
        display: block;
    }
    .register-form button:hover {
        background: linear-gradient(90deg, #2ca8c6 60%, #176d7a 100%);
        transform: translateY(-2px) scale(1.03);
        box-shadow: 0 4px 16px #2ca8c633;
    }
    .register-message {
        color: #1ca67a;
        background: #e0f7e9;
        border: 1px solid #b2f2d7;
        border-radius: 6px;
        padding: 10px 14px;
        margin: 10px 0 0 0;
        font-size: 1em;
        text-align: center;
        width: 100%;
        box-sizing: border-box;
    }
    .register-error {
        color: #e74c3c;
        background: #fdecea;
        border: 1px solid #f5c6cb;
        border-radius: 6px;
        padding: 10px 14px;
        margin: 10px 0 0 0;
        font-size: 1em;
        text-align: center;
        width: 100%;
        box-sizing: border-box;
    }
    .login-link {
        margin-top: 18px;
        width: 100%;
        text-align: center;
    }
    .login-link a {
        text-decoration: none;
        color: #2ca8c6;
        font-weight: 500;
        font-size: 1.04em;
        padding: 8px 22px;
        border-radius: 6px;
        border: 1.5px solid #2ca8c6;
        background: #f4f8fb;
        transition: background 0.2s, color 0.2s, border 0.2s;
        margin-left: 4px;
        display: inline-block;
    }
    .login-link a:hover {
        background: #2ca8c6;
        color: #fff;
        border: 1.5px solid #176d7a;
        text-decoration: none;
    }
    /* minimal estilos para modal de verificación */
    .verification-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    .verification-modal {
        background:#fff;padding:22px;border-radius:10px;max-width:420px;width:90%;
        box-shadow:0 6px 30px rgba(0,0,0,0.25);text-align:left;
    }
    .verification-modal h3{margin:0 0 12px 0;color:#176d7a;}
    .verification-modal input{width:100%;padding:10px;border:1px solid #e3e8ee;border-radius:6px;margin-bottom:10px;}
    .verification-modal button{padding:10px 14px;background:#2ca8c6;color:#fff;border:none;border-radius:6px;cursor:pointer;}
    .verification-error{color:#e74c3c;margin-top:8px;}
    @media (max-width: 500px) {
        .register-container { width: 95vw; padding: 24px 4vw 18px 4vw;}
        .register-logo { font-size: 1.2em;}
        .register-form input,
        .register-form button { max-width: 100%; }
    }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-logo">
            <span class="register-logo-icon">&#128722;</span>
            ShopMate
        </div>
        <div class="register-title">Registro</div>
        <form class="register-form" method="POST" id="registerForm">
            <?php
            // Prefill values from $pending or empty
            $pref = $pending ?? [];
            function pv($k, $pref){ return htmlspecialchars($pref[$k] ?? ''); }
            ?>
            <input type="text" name="nombre_usuario" placeholder="Usuario" required value="<?php echo pv('NIC_USU',$pref); ?>">
            <input type="text" name="nombre" placeholder="Nombre" required value="<?php echo pv('NOM_USU',$pref); ?>">
            <input type="text" name="apellido" placeholder="Apellido" required value="<?php echo pv('APE_USU',$pref); ?>">
            <input type="email" name="correo" placeholder="Correo" required value="<?php echo pv('COR_USU',$pref); ?>">

            <!-- Fecha: selects -->
            <?php
            $sel_d = $pref['FEC_D'] ?? '';
            $sel_m = $pref['FEC_M'] ?? '';
            $sel_y = $pref['FEC_Y'] ?? '';
            ?>
            <div style="display:flex;gap:8px;">
                <select name="fec_d" required>
                    <option value="">Día</option>
                    <?php for($d=1;$d<=31;$d++): ?>
                        <option value="<?php echo $d; ?>" <?php echo $sel_d== $d ? 'selected':''; ?>><?php echo $d; ?></option>
                    <?php endfor; ?>
                </select>
                <select name="fec_m" required>
                    <option value="">Mes</option>
                    <?php for($m=1;$m<=12;$m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $sel_m== $m ? 'selected':''; ?>><?php echo $m; ?></option>
                    <?php endfor; ?>
                </select>
                <select name="fec_y" required>
                    <option value="">Año</option>
                    <?php for($y = date('Y')-100; $y <= date('Y')-12; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo $sel_y== $y ? 'selected':''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Teléfono: exactamente 10 dígitos -->
            <input type="text" name="celular" placeholder="Celular (10 dígitos)" required maxlength="10" pattern="\d{10}" inputmode="numeric" oninput="this.value = this.value.replace(/\D/g,'').slice(0,10);" title="Ingrese solo 10 números" value="<?php echo pv('TEL_USU',$pref); ?>">

            <input type="password" name="contrasena" placeholder="Contraseña" required>

            <button type="submit">Registrarse</button>
        </form>

        <?php if ($mensaje): ?>
            <div class="register-message"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <div class="login-link">
            ¿Ya tienes cuenta?
            <a href="login.php">Volver al login</a>
        </div>
    </div>

    <script>
    // Mantener comportamiento de formulario ligero (evitar pérdida en back/refresh)
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
    </script>
</body>
</html>
