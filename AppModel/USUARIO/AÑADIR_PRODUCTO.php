<?php
session_start();
include __DIR__ . '/../db.php';
include __DIR__ . '/../PAG_INICIO/navbar.php';

// Usar la conexión de db.php, pero mantener compatibilidad con $mysqli
if (!isset($conn) || !$conn) {
    http_response_code(500);
    echo "Error de conexión a la base de datos.";
    exit;
}
// Alias para mantener compatibilidad con el código existente
$mysqli = $conn;

// Helper: extraer usuario normalizado desde $_SESSION (varias formas)
function extractUserFromSession()
{
    if (!isset($_SESSION) || empty($_SESSION))
        return null;
    // claves directas
    $directKeys = ['ID_USU', 'NIC_USU', 'id', 'ID', 'userid', 'user_id', 'nick', 'username', 'user'];
    $id = null;
    $nick = null;
    foreach ($directKeys as $k) {
        if ($k === 'NIC_USU' && !empty($_SESSION[$k])) {
            $nick = $_SESSION[$k];
        }
        if ($k === 'ID_USU' && !empty($_SESSION[$k])) {
            $id = $_SESSION[$k];
        }
        // otras variantes
        if (!$id && isset($_SESSION[$k]) && is_numeric($_SESSION[$k]))
            $id = $_SESSION[$k];
        if (!$nick && isset($_SESSION[$k]) && is_string($_SESSION[$k]) && strpos($_SESSION[$k], '@') === false)
            $nick = $_SESSION[$k];
    }
    // arrays dentro de session
    foreach ($_SESSION as $v) {
        if (is_array($v)) {
            if (isset($v['ID_USU']))
                $id = $id ?: $v['ID_USU'];
            if (isset($v['NIC_USU']))
                $nick = $nick ?: $v['NIC_USU'];
            if (isset($v['id']))
                $id = $id ?: $v['id'];
            if (isset($v['nick']))
                $nick = $nick ?: $v['nick'];
            if (isset($v['username']))
                $nick = $nick ?: $v['username'];
            if (isset($v['email']) && !$nick)
                $nick = strstr($v['email'], '@', true) ?: $v['email'];
        }
    }
    if ($id || $nick)
        return ['ID_USU' => $id, 'NIC_USU' => $nick];
    return null;
}

// Rutas relativas públicas (desde /AppModel/)
$PUBLIC_TEMP_BASE = 'uploads/temp/';
// Ruta pública correcta desde localhost
// Ajusta esta ruta según donde coloques el proyecto en htdocs
// Si está en: C:\xampp\htdocs\AppModel\ → usa '/AppModel'
// Si está en: C:\xampp\htdocs\Proyecto-Modelamiento\AppModel\ → usa '/Proyecto-Modelamiento/AppModel'
$APP_BASE_URL = '/Proyecto-Modelamiento/AppModel';

$PUBLIC_IMAGES_BASE = $APP_BASE_URL . '/uploads/imagenes/';




// Aceptar subida temporal sin sesión (endpoint AJAX)
// Retorna JSON: { success: true, file: 'basename.ext', url: 'uploads/temp/{session_id}/basename.ext' }
if (isset($_GET['action']) && $_GET['action'] === 'temp_upload') {
    if (!isset($_FILES['image'])) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'No file']);
        exit;
    }
    $file = $_FILES['image'];
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    if ($file['error'] !== UPLOAD_ERR_OK || !isset($allowedTypes[$file['type']]) || $file['size'] > 8 * 1024 * 1024) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Archivo inválido.']);
        exit;
    }
    $session_id = session_id() ?: bin2hex(random_bytes(8));
    $tempDir = __DIR__ . '/../uploads/temp/' . $session_id;
    if (!is_dir($tempDir) && !mkdir($tempDir, 0755, true)) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'No se pudo crear carpeta temporal.']);
        exit;
    }
    $ext = $allowedTypes[$file['type']];
    $basename = bin2hex(random_bytes(12)) . '.' . $ext;
    $target = $tempDir . '/' . $basename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'No se pudo guardar el archivo.']);
        exit;
    }
    $url = $PUBLIC_TEMP_BASE . $session_id . '/' . $basename;
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'file' => $basename, 'url' => $url]);
    exit;
}

// Guardado final (requiere usuario en session)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $isXHR = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    // Solo verificar que existe una sesión activa
    if (!isset($_SESSION)) {
        http_response_code(401);
        echo json_encode(['error' => 'Debe iniciar sesión para guardar el producto.']);
        exit;
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = trim($_POST['precio'] ?? '');
    $temp_file = trim($_POST['temp_file'] ?? '');

    // Asignar un ID de usuario por defecto si no existe
    $userId = isset($_SESSION['ID_USU']) ? $_SESSION['ID_USU'] : 1;
    $userNick = isset($_SESSION['NIC_USU']) ? $_SESSION['NIC_USU'] : 'user_' . time();

    // Resto de validaciones de campos
    if ($nombre === '' || $descripcion === '' || $precio === '' || $temp_file === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Todos los campos son obligatorios.']);
        exit;
    }
    if (!is_numeric($precio)) {
        if ($isXHR) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Precio inválido.']);
            exit;
        }
        $_SESSION['msg'] = 'Precio inválido.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $session_id = session_id();
    $tempDir = __DIR__ . '/../uploads/temp/' . $session_id;
    $tempPath = $tempDir . '/' . $temp_file;
    if (!file_exists($tempPath)) {
        // intentar si temp_file viene con ruta parcial (por seguridad sólo basename)
        if ($isXHR) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Archivo temporal no encontrado.']);
            exit;
        }
        $_SESSION['msg'] = 'Archivo temporal no encontrado.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Verificar si la carpeta ya existe antes de intentar crearla
    $finalDir = __DIR__ . '/../uploads/imagenes/' . $userNick;
    if (!file_exists($finalDir)) {
        if (!mkdir($finalDir, 0755, true)) {
            if ($isXHR) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'No se pudo crear carpeta de usuario.']);
                exit;
            }
            $_SESSION['msg'] = 'Error al crear carpeta de usuario.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }

    $finalName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', $temp_file);
    $finalPath = $finalDir . '/' . $finalName;
    if (!rename($tempPath, $finalPath)) {
        if ($isXHR) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'No se pudo mover la imagen a la carpeta final.']);
            exit;
        }
        $_SESSION['msg'] = 'No se pudo mover la imagen a la carpeta final.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $relativeUrl = $PUBLIC_IMAGES_BASE . $userNick . '/' . $finalName;


    $mysqli->begin_transaction();
    try {
        $stmt = $mysqli->prepare("INSERT INTO PRODUCTOS (NOM_PRO, DES_PRO, PRECIO, ID_USUARIO) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssdi', $nombre, $descripcion, $precio, $userId);
        $stmt->execute();
        $productId = $stmt->insert_id;
        $stmt->close();

        $stmt2 = $mysqli->prepare("INSERT INTO IMAGENES_PRODUCTOS (URL_IMG, ID_PRO_PER) VALUES (?, ?)");
        $stmt2->bind_param('si', $relativeUrl, $productId);
        $stmt2->execute();
        $stmt2->close();

        $mysqli->commit();

        // limpiar carpeta temp si está vacía
        @unlink($tempPath);
        @rmdir($tempDir);

        if ($isXHR) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'product_id' => $productId, 'image' => $relativeUrl]);
            exit;
        }
        $_SESSION['msg'] = 'Producto agregado correctamente.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        $mysqli->rollback();
        // intentar restaurar temp (no crítico)
        @rename($finalPath, $tempPath);

        if ($isXHR) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Error al guardar el producto.']);
            exit;
        }
        $_SESSION['msg'] = 'Error al guardar el producto.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Página: formulario y JS (permite subir temporal sin sesión)
?>
<!DOCTYPE html>
<html lang="es">

    <head>
        <meta charset="utf-8">
        <title>Añadir Producto - ShopMate</title>
        <style>
            :root {
                --primary-color: #176d7a;
                --primary-hover: #1a8296;
                --secondary-color: #6c757d;
                --success-color: #28a745;
                --error-color: #dc3545;
                --background-color: #f4f6f9;
                --border-color: #dde1e5;
                --shadow-color: rgba(0, 0, 0, 0.1);
            }

            body {
                background: var(--background-color);
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                margin: 0;
                padding: 0;
                color: #333;
                line-height: 1.5;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: stretch;
            }

            .container {
                width: 100%;
                max-width: 800px;
                margin: 20px auto;
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 4px 25px var(--shadow-color);
                padding: 30px;
                position: relative;
                animation: fadeInUp 0.5s ease;
            }

            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid var(--background-color);
            }

            .header h2 {
                margin: 0;
                color: var(--primary-color);
                font-size: 24px;
                font-weight: 600;
            }

            .form-group {
                margin-bottom: 25px;
            }

            .form-group label {
                display: block;
                margin-bottom: 10px;
                font-weight: 600;
                color: #2c3e50;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .form-group input[type="text"],
            .form-group input[type="number"],
            .form-group textarea {
                width: 100%;
                padding: 12px 15px;
                border: 2px solid var(--border-color);
                border-radius: 8px;
                font-size: 15px;
                transition: all 0.3s ease;
                box-sizing: border-box;
            }

            .form-group input:focus,
            .form-group textarea:focus {
                border-color: var(--primary-color);
                outline: none;
                box-shadow: 0 0 0 3px rgba(23, 109, 122, 0.1);
            }

            .preview-container {
                margin: 25px 0;
                padding: 30px;
                border: 2px dashed var(--border-color);
                border-radius: 12px;
                text-align: center;
                background: var(--background-color);
                transition: all 0.3s ease;
            }

            .preview-container:hover {
                border-color: var(--primary-color);
            }

            .preview {
                max-width: 100%;
                max-height: 400px;
                margin: 15px auto;
                border-radius: 8px;
                box-shadow: 0 4px 15px var(--shadow-color);
                display: none;
                object-fit: contain;
            }

            .preview.show {
                display: block;
                animation: fadeIn 0.3s ease;
            }

            .form-actions {
                display: flex;
                gap: 15px;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 2px solid var(--background-color);
            }

            button {
                padding: 12px 25px;
                border: none;
                border-radius: 8px;
                font-size: 15px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                flex: 1;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            button[type="submit"] {
                background: var(--primary-color);
                color: white;
            }

            button[type="submit"]:hover {
                background: var(--primary-hover);
                transform: translateY(-1px);
            }

            button[type="submit"]:disabled {
                background: var(--secondary-color);
                cursor: not-allowed;
                transform: none;
            }

            .btn-cancel {
                background: var(--secondary-color);
                color: white;
            }

            .btn-cancel:hover {
                background: #5a6268;
                transform: translateY(-1px);
            }

            input[type="file"] {
                padding: 12px;
                background: var(--background-color);
                border: 2px solid var(--border-color);
                border-radius: 8px;
                width: 100%;
                cursor: pointer;
                box-sizing: border-box;
            }

            input[type="file"]:hover {
                border-color: var(--primary-color);
            }

            .preview-text {
                color: var(--secondary-color);
                font-size: 15px;
                font-weight: 500;
            }

            #message-container {
                margin-bottom: 20px;
            }

            .notice {
                padding: 15px;
                border-radius: 8px;
                font-weight: 500;
                animation: slideIn 0.3s ease;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes slideIn {
                from {
                    transform: translateX(-10px);
                    opacity: 0;
                }

                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        </style>
    </head>

    <body>
        <?php echo renderNavbar(); ?>
        <div class="container">
            <div class="header">
                <h2>Añadir Producto</h2>
            </div>

            <div id="message-container"></div>

            <form id="productForm" method="post">
                <div class="form-group">
                    <label>Nombre del producto:</label>
                    <input type="text" name="nombre" id="nombre" maxlength="50" required>
                </div>
                <div class="form-group">
                    <label>Descripción:</label>
                    <textarea name="descripcion" id="descripcion" maxlength="255" required></textarea>
                </div>
                <div class="form-group">
                    <label>Precio:</label>
                    <input type="number" step="0.01" name="precio" id="precio" required>
                </div>
                <div class="form-group">
                    <label>Imagen del producto:</label>
                    <input type="file" id="imageInput" accept="image/*" required>
                    <input type="hidden" name="temp_file" id="temp_file" value="">
                </div>
                <div class="preview-container">
                    <div class="preview-text">Vista previa de la imagen</div>
                    <img id="preview" class="preview" src="#" alt="Previsualización" style="display:none;">
                </div>
                <input type="hidden" name="action" value="save">
                <div class="form-actions">
                    <button type="button" id="btn-cancel" class="btn-cancel">Cancelar</button>
                    <button type="submit" id="btn-submit">Guardar Producto</button>
                </div>
            </form>
        </div>
        <script>
            const imageInput = document.getElementById('imageInput');
            const preview = document.getElementById('preview');
            const tempInput = document.getElementById('temp_file');
            const previewContainer = document.querySelector('.preview-container');
            const previewText = document.querySelector('.preview-text');

            function showLocalPreview(file) {
                if (!file || !file.type.startsWith('image/')) return;

                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    preview.classList.add('show');
                    previewText.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }

            imageInput.addEventListener('change', function (e) {
                const file = this.files[0];
                if (!file) return;

                // Mostrar vista previa inmediata
                showLocalPreview(file);

                // Subir al servidor
                const fd = new FormData();
                fd.append('image', file);

                fetch('<?php echo basename(__FILE__); ?>?action=temp_upload', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.success) {
                            tempInput.value = data.file;
                        } else {
                            throw new Error(data.error || 'Error al subir la imagen');
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        alert('Error al procesar la imagen. Por favor, intenta de nuevo.');
                        preview.style.display = 'none';
                        previewText.style.display = 'block';
                        tempInput.value = '';
                    });
            });

            // Función para mostrar mensajes
            function showMessage(message, type) {
                const messageContainer = document.getElementById('message-container');
                const messageDiv = document.createElement('div');
                messageDiv.className = 'notice';
                messageDiv.style.cssText = type === 'success'
                    ? 'padding:12px; background:#d1f4d7; border-left:5px solid #2e7d32; border-radius:6px; margin-bottom:18px; color:#2e7d32; font-weight:600;'
                    : 'padding:12px; background:#f8d7da; border-left:5px solid #dc3545; border-radius:6px; margin-bottom:18px; color:#dc3545; font-weight:600;';
                messageDiv.textContent = message;
                messageContainer.innerHTML = '';
                messageContainer.appendChild(messageDiv);

                // Auto-ocultar después de 3 segundos solo si es error
                if (type === 'error') {
                    setTimeout(() => {
                        messageDiv.style.transition = "opacity 0.6s";
                        messageDiv.style.opacity = 0;
                        setTimeout(() => messageDiv.remove(), 600);
                    }, 3000);
                }
            }

            // Función para cerrar el modal (comunicación con el dashboard)
            function closeModal() {
                // Intentar usar la función global del dashboard
                if (typeof window.closeAddProductModal === 'function') {
                    window.closeAddProductModal();
                    return;
                }

                // Fallback: disparar evento personalizado
                try {
                    window.dispatchEvent(new CustomEvent('closeAddProductModal'));
                } catch (e) {
                    console.log('No se pudo disparar el evento');
                }

                // Fallback alternativo: intentar cerrar directamente
                try {
                    const modal = document.getElementById('add-product-modal');
                    const dashboardContent = document.getElementById('dashboard-content');
                    if (modal && dashboardContent) {
                        modal.innerHTML = '';
                        modal.style.display = 'none';
                        dashboardContent.style.display = 'block';
                    }
                } catch (e) {
                    console.log('No se pudo cerrar el modal directamente');
                }
            }

            // Añadir event listeners para los botones de cancelar
            (function () {
                // Manejar clic en botón cancelar
                const cancelBtn = document.getElementById('btn-cancel');
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        // Redirigir al dashboard
                        window.location.href = '/Proyecto-Modelamiento/AppModel/PAG_INICIO/DASHBOARD.php';
                    });
                }

                // Eliminar manejo de botón cerrar (X) ya que no es necesario en vista completa
                // const closeBtn = document.getElementById('close-add-product');
                // if (closeBtn) {
                //     closeBtn.addEventListener('click', function(e) {
                //         e.preventDefault();
                //         closeModal();
                //     });
                // }
            })();

            // Modificar submitForm para enviar por AJAX
            document.getElementById('productForm').addEventListener('submit', function (e) {
                e.preventDefault();

                if (tempInput.value === '') {
                    showMessage('Debes subir y previsualizar una imagen antes de guardar.', 'error');
                    return false;
                }

                // Deshabilitar botón de enviar
                const submitBtn = document.getElementById('btn-submit');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Guardando...';

                // Preparar datos del formulario
                const formData = new FormData(this);
                formData.append('action', 'save');

                // Enviar por AJAX
                fetch('<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(data => {
                                throw new Error(data.error || 'Error al guardar el producto');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            showMessage('¡Producto guardado correctamente!', 'success');
                            // Limpiar formulario
                            document.getElementById('productForm').reset();
                            preview.style.display = 'none';
                            previewText.style.display = 'block';
                            tempInput.value = '';
                            imageInput.value = '';

                            // Cerrar modal después de 1.5 segundos
                            setTimeout(() => {
                                closeModal();
                            }, 1500);
                        } else {
                            throw new Error(data.error || 'Error desconocido');
                        }
                    })
                    .catch(error => {
                        showMessage(error.message || 'Error al guardar el producto. Por favor, intenta de nuevo.', 'error');
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Guardar Producto';
                    });

                return false;
            });
        </script>

    </body>

</html>