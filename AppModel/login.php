<?php
session_start();
include 'db.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $contrasena = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';

    // Seleccionar también el ID del tipo para fallback
    $sql = "SELECT u.ID_USU, u.NIC_USU, u.CON_USU, u.ID_TIP_USU, t.nombre_tipo, 
            u.EST_USU, u.FEC_BAN_USU
            FROM usuarios u
            LEFT JOIN tipos_usuario t ON u.ID_TIP_USU = t.ID_TIP
            WHERE u.NIC_USU = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("DB prepare failed in login.php: " . $conn->error);
        $error = "Error interno. Intenta más tarde.";
    } else {
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();

            // Verificar estado y baneo ANTES de validar la contraseña
            $estadoFila = isset($row['EST_USU']) ? $row['EST_USU'] : 'L';
            $fecBanFila = isset($row['FEC_BAN_USU']) ? $row['FEC_BAN_USU'] : null;
            $blocked = false;
            if ($estadoFila === 'B') {
                if ($fecBanFila) {
                    $now = new DateTime('now');
                    $until = DateTime::createFromFormat('Y-m-d H:i:s', $fecBanFila);
                    if ($until && $now < $until) {
                        $error = "Usuario Baneado (hasta " . $until->format('Y-m-d H:i:s') . ")";
                        $blocked = true;
                    } else {
                        // Baneo expirado: restaurar estado y continuar
                        $upd = $conn->prepare("UPDATE usuarios SET EST_USU = 'L', FEC_BAN_USU = NULL WHERE ID_USU = ?");
                        if ($upd) {
                            $upd->bind_param("i", $row['ID_USU']);
                            $upd->execute();
                            $upd->close();
                        }
                    }
                } else {
                    // Estado B sin fecha
                    $error = "Usuario Baneado";
                    $blocked = true;
                }
            }

            if (!$blocked) {
                $hash = $row['CON_USU'];
                $ok = false;

                // Verificar hash
                if (password_verify($contrasena, $hash)) {
                    $ok = true;
                } elseif ($contrasena === $hash) {
                    // Fallback: contraseña almacenada en texto plano. Permitir acceso y rehash seguro.
                    $ok = true;
                    $newHash = password_hash($contrasena, PASSWORD_DEFAULT);
                    $upd = $conn->prepare("UPDATE usuarios SET CON_USU = ? WHERE ID_USU = ?");
                    if ($upd) {
                        $upd->bind_param("si", $newHash, $row['ID_USU']);
                        $upd->execute();
                        $upd->close();
                    } else {
                        error_log("DB prepare failed for password rehash: " . $conn->error);
                    }
                }

                if ($ok) {
                    session_regenerate_id(true);
                    $_SESSION['usuario'] = $row['NIC_USU'];
                    $_SESSION['tipo_usuario'] = $row['nombre_tipo'] ?? '';
                    $_SESSION['tipo_id'] = $row['ID_TIP_USU'] ?? null;

                    // Normalizar para comparar (aceptar "ADMIN" / "ADMINISTRADOR" etc.)
                    $tipo_str = strtoupper(trim($_SESSION['tipo_usuario'] ?? ''));
                    $tipo_id = isset($_SESSION['tipo_id']) ? (int) $_SESSION['tipo_id'] : null;

                    if (strpos($tipo_str, 'ADMIN') !== false || $tipo_str === 'USUARIO' || strpos($tipo_str, 'MODERADOR') !== false) {
                        header("Location: /Proyecto-Modelamiento/AppModel/PAG_INICIO/dashboard.php");
                        exit();
                    }

                    // Fallback por ID (si en su sistema el admin tiene ID 1)
                    if ($tipo_id === 1) {
                        header("Location: /Proyecto-Modelamiento/AppModel/PAG_INICIO/dashboard.php");
                        exit();
                    }

                    // No reconocido: registrar para depuración
                    error_log("login.php: tipo de usuario desconocido para NIC_USU={$row['NIC_USU']} tipo_nombre='{$row['nombre_tipo']}' tipo_id='{$row['ID_TIP_USU']}'");
                    $error = "Tipo de usuario no válido.";
                } else {
                    $error = "Usuario o contraseña incorrectos";
                }
            } else {
                // Si está bloqueado, no intentar autenticar; el $error ya contiene el mensaje de baneo
            }
        } else {
            $error = "Usuario o contraseña incorrectos";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>

    <head>
        <title>Login</title>
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

            .login-container {
                background: #fff;
                padding: 40px 36px 32px 36px;
                border-radius: 18px;
                box-shadow: 0 6px 32px #176d7a18, 0 1.5px 8px #2ca8c610;
                width: 370px;
                display: flex;
                flex-direction: column;
                align-items: center;
                animation: fadeIn 0.8s;
                gap: 0.5em;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .login-logo {
                display: flex;
                align-items: center;
                gap: 12px;
                font-size: 2.1em;
                font-weight: bold;
                color: #176d7a;
                margin-bottom: 8px;
                letter-spacing: 1px;
                user-select: none;
            }

            .login-logo-icon {
                font-size: 1.3em;
                background: linear-gradient(135deg, #2ca8c6 60%, #176d7a 100%);
                color: #fff;
                border-radius: 50%;
                padding: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 8px #2ca8c633;
            }

            .login-title {
                font-size: 1.22em;
                font-weight: 500;
                margin-bottom: 18px;
                color: #176d7a;
                letter-spacing: 0.5px;
            }

            .login-form {
                width: 100%;
                display: flex;
                flex-direction: column;
                gap: 16px;
                margin-bottom: 8px;
                align-items: center;
            }

            .login-form input,
            .login-form button {
                width: 100%;
                max-width: 290px;
                /* Ajusta este valor para que coincida con el ancho del botón */
                box-sizing: border-box;
            }

            .login-form input {
                padding: 13px 15px;
                border: 1.5px solid #e3e8ee;
                border-radius: 7px;
                font-size: 1.04em;
                background: #f8fafc;
                transition: border 0.2s, box-shadow 0.2s;
                box-shadow: 0 1px 2px #2ca8c610;
                margin: 0 auto;
                display: block;
            }

            .login-form input:focus {
                border: 1.5px solid #2ca8c6;
                outline: none;
                background: #fff;
                box-shadow: 0 2px 8px #2ca8c622;
            }

            .login-form button {
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

            .login-form button:hover {
                background: linear-gradient(90deg, #2ca8c6 60%, #176d7a 100%);
                transform: translateY(-2px) scale(1.03);
                box-shadow: 0 4px 16px #2ca8c633;
            }

            .login-error {
                color: #e74c3c;
                background: #fdecea;
                border: 1px solid #f5c6cb;
                border-radius: 6px;
                padding: 10px 14px;
                margin: 10px 0 0 0;
                font-size: 1em;
                text-align: center;
                width: 100%;
                animation: shake 0.3s;
                box-sizing: border-box;
            }

            @keyframes shake {
                0% {
                    transform: translateX(0);
                }

                25% {
                    transform: translateX(-5px);
                }

                50% {
                    transform: translateX(5px);
                }

                75% {
                    transform: translateX(-5px);
                }

                100% {
                    transform: translateX(0);
                }
            }

            .register-link {
                margin-top: 18px;
                width: 100%;
                text-align: center;
            }

            .register-link a {
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

            .register-link a:hover {
                background: #2ca8c6;
                color: #fff;
                border: 1.5px solid #176d7a;
                text-decoration: none;
            }

            @media (max-width: 500px) {
                .login-container {
                    width: 95vw;
                    padding: 24px 4vw 18px 4vw;
                }

                .login-logo {
                    font-size: 1.2em;
                }

                .login-form input,
                .login-form button {
                    max-width: 100%;
                }
            }
        </style>
        <script>
            // Recargar la página cada vez que el usuario llega a la pestaña
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    window.location.reload();
                }
            });
        </script>

        <script>
            // Ocultar automáticamente mensajes de error después de 3 segundos
            document.addEventListener('DOMContentLoaded', function () {
                var err = document.querySelector('.login-error');
                if (!err) return;
                // aplicar transición y ocultar
                setTimeout(function () {
                    err.style.transition = 'opacity 0.35s ease';
                    err.style.opacity = '0';
                    // eliminar del DOM después de la transición
                    setTimeout(function () {
                        if (err && err.parentNode) err.parentNode.removeChild(err);
                    }, 400);
                }, 3000);
            });
        </script>
    </head>

    <body>
        <div class="login-container">
            <div class="login-logo">
                <span class="login-logo-icon">&#128722;</span>
                ShopMate
            </div>
            <div class="login-title">Iniciar Sesión</div>
            <form class="login-form" method="POST">
                <input type="text" name="usuario" placeholder="Usuario" required autocomplete="username">
                <input type="password" name="contrasena" placeholder="Contraseña" required
                    autocomplete="current-password">
                <button type="submit">Ingresar</button>
            </form>
            <?php if ($error): ?>
                <div class="login-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <div class="register-link">
                ¿No tienes cuenta?
                <a href="registro.php">Regístrate</a>
            </div>
        </div>
    </body>

</html>