<?php
include 'db.php';

echo "<h2>Reparando Tablas de Chat</h2>";

// Crear tabla CHATS
$sql_chats = "CREATE TABLE IF NOT EXISTS CHATS (
    ID_CHAT INT AUTO_INCREMENT PRIMARY KEY,
    ID_PRODUCTO INT NOT NULL,
    ID_COMPRADOR INT NOT NULL,
    ID_VENDEDOR INT NOT NULL,
    FECHA_INICIO DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_PRODUCTO) REFERENCES PRODUCTOS(ID_PRO),
    FOREIGN KEY (ID_COMPRADOR) REFERENCES usuarios(ID_USU),
    FOREIGN KEY (ID_VENDEDOR) REFERENCES usuarios(ID_USU)
)";

if ($conn->query($sql_chats) === TRUE) {
    echo "Tabla CHATS verificada/creada correctamente.<br>";
} else {
    echo "Error creando tabla CHATS: " . $conn->error . "<br>";
}

// Crear tabla MENSAJES
$sql_mensajes = "CREATE TABLE IF NOT EXISTS MENSAJES (
    ID_MSJ INT AUTO_INCREMENT PRIMARY KEY,
    ID_CHAT INT NOT NULL,
    ID_REMITENTE INT NOT NULL,
    MENSAJE TEXT NOT NULL,
    FECHA_ENVIO DATETIME DEFAULT CURRENT_TIMESTAMP,
    LEIDO BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (ID_CHAT) REFERENCES CHATS(ID_CHAT),
    FOREIGN KEY (ID_REMITENTE) REFERENCES usuarios(ID_USU)
)";

if ($conn->query($sql_mensajes) === TRUE) {
    echo "Tabla MENSAJES verificada/creada correctamente.<br>";
} else {
    echo "Error creando tabla MENSAJES: " . $conn->error . "<br>";
}

echo "<br>Proceso finalizado.";
?>