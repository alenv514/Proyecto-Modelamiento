<?php
session_start();
include '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Obtener ID del usuario actual
$nic_usu = $_SESSION['usuario'];
$stmt = $conn->prepare("SELECT ID_USU FROM usuarios WHERE NIC_USU = ?");
$stmt->bind_param("s", $nic_usu);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $id_usuario_actual = $row['ID_USU'];
} else {
    echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
    exit;
}
$stmt->close();

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'iniciar_chat') {
    $id_producto = isset($_POST['id_producto']) ? (int) $_POST['id_producto'] : 0;
    $id_vendedor = isset($_POST['id_vendedor']) ? (int) $_POST['id_vendedor'] : 0;

    if ($id_producto <= 0 || $id_vendedor <= 0) {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
        exit;
    }

    if ($id_usuario_actual == $id_vendedor) {
        echo json_encode(['success' => false, 'error' => 'No puedes chatear contigo mismo']);
        exit;
    }

    // Verificar si ya existe un chat
    $stmt = $conn->prepare("SELECT ID_CHAT FROM CHATS WHERE ID_PRODUCTO = ? AND ID_COMPRADOR = ? AND ID_VENDEDOR = ?");
    $stmt->bind_param("iii", $id_producto, $id_usuario_actual, $id_vendedor);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        echo json_encode(['success' => true, 'id_chat' => $row['ID_CHAT']]);
    } else {
        // Crear nuevo chat
        $stmt = $conn->prepare("INSERT INTO CHATS (ID_PRODUCTO, ID_COMPRADOR, ID_VENDEDOR) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $id_producto, $id_usuario_actual, $id_vendedor);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id_chat' => $stmt->insert_id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al crear chat']);
        }
    }

} elseif ($action === 'enviar_mensaje') {
    $id_chat = isset($_POST['id_chat']) ? (int) $_POST['id_chat'] : 0;
    $mensaje = isset($_POST['mensaje']) ? trim($_POST['mensaje']) : '';

    if ($id_chat <= 0 || empty($mensaje)) {
        echo json_encode(['success' => false, 'error' => 'Mensaje vacío o chat inválido']);
        exit;
    }

    // Verificar pertenencia al chat
    $stmt = $conn->prepare("SELECT ID_COMPRADOR, ID_VENDEDOR FROM CHATS WHERE ID_CHAT = ?");
    $stmt->bind_param("i", $id_chat);
    $stmt->execute();
    $res = $stmt->get_result();
    $chat = $res->fetch_assoc();

    if (!$chat || ($chat['ID_COMPRADOR'] != $id_usuario_actual && $chat['ID_VENDEDOR'] != $id_usuario_actual)) {
        echo json_encode(['success' => false, 'error' => 'Acceso denegado al chat']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO MENSAJES (ID_CHAT, ID_REMITENTE, MENSAJE) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $id_chat, $id_usuario_actual, $mensaje);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al enviar']);
    }

} elseif ($action === 'obtener_mensajes') {
    $id_chat = isset($_GET['id_chat']) ? (int) $_GET['id_chat'] : 0;

    // Verificar pertenencia
    $stmt = $conn->prepare("SELECT ID_COMPRADOR, ID_VENDEDOR FROM CHATS WHERE ID_CHAT = ?");
    $stmt->bind_param("i", $id_chat);
    $stmt->execute();
    $res = $stmt->get_result();
    $chat = $res->fetch_assoc();

    if (!$chat || ($chat['ID_COMPRADOR'] != $id_usuario_actual && $chat['ID_VENDEDOR'] != $id_usuario_actual)) {
        echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
        exit;
    }

    // Marcar como leídos los mensajes del OTRO usuario
    $stmt = $conn->prepare("UPDATE MENSAJES SET LEIDO = TRUE WHERE ID_CHAT = ? AND ID_REMITENTE != ? AND LEIDO = FALSE");
    $stmt->bind_param("ii", $id_chat, $id_usuario_actual);
    $stmt->execute();

    // Obtener mensajes
    $stmt = $conn->prepare("
        SELECT m.*, u.NIC_USU as remitente_nombre 
        FROM MENSAJES m 
        JOIN usuarios u ON m.ID_REMITENTE = u.ID_USU 
        WHERE m.ID_CHAT = ? 
        ORDER BY m.FECHA_ENVIO ASC
    ");
    $stmt->bind_param("i", $id_chat);
    $stmt->execute();
    $res = $stmt->get_result();

    $mensajes = [];
    while ($row = $res->fetch_assoc()) {
        $mensajes[] = [
            'id' => $row['ID_MSJ'],
            'mensaje' => $row['MENSAJE'],
            'remitente' => $row['remitente_nombre'],
            'es_mio' => ($row['ID_REMITENTE'] == $id_usuario_actual),
            'fecha' => $row['FECHA_ENVIO']
        ];
    }
    echo json_encode(['success' => true, 'mensajes' => $mensajes]);

} elseif ($action === 'listar_chats') {
    $stmt = $conn->prepare("
        SELECT c.ID_CHAT, p.NOM_PRO, 
               CASE 
                   WHEN c.ID_COMPRADOR = ? THEN uv.NIC_USU 
                   ELSE uc.NIC_USU 
               END as otro_usuario,
               (SELECT MENSAJE FROM MENSAJES WHERE ID_CHAT = c.ID_CHAT ORDER BY FECHA_ENVIO DESC LIMIT 1) as ultimo_mensaje
        FROM CHATS c
        JOIN PRODUCTOS p ON c.ID_PRODUCTO = p.ID_PRO
        JOIN usuarios uc ON c.ID_COMPRADOR = uc.ID_USU
        JOIN usuarios uv ON c.ID_VENDEDOR = uv.ID_USU
        WHERE c.ID_COMPRADOR = ? OR c.ID_VENDEDOR = ?
        ORDER BY c.FECHA_INICIO DESC
    ");
    $stmt->bind_param("iii", $id_usuario_actual, $id_usuario_actual, $id_usuario_actual);
    $stmt->execute();
    $res = $stmt->get_result();

    $chats = [];
    while ($row = $res->fetch_assoc()) {
        $chats[] = $row;
    }
    echo json_encode(['success' => true, 'chats' => $chats]);

} else {
    echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}
?>