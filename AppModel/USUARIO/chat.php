<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit();
}
include '../db.php';
include '../PAG_INICIO/navbar.php';

$id_chat_inicial = isset($_GET['id_chat']) ? (int) $_GET['id_chat'] : 0;
?>
<!DOCTYPE html>
<html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Mis Chats - ShopMate</title>
        <link rel="stylesheet" href="/Proyecto-Modelamiento/AppModel/PAG_INICIO/styles.css">
        <style>
            body {
                background-color: #f0f4f8;
                height: 100vh;
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }

            .chat-container {
                flex: 1;
                display: flex;
                max-width: 1200px;
                margin: 20px auto;
                background: #fff;
                border-radius: 16px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
                overflow: hidden;
                width: 95%;
                height: calc(100vh - 100px);
            }

            .chat-sidebar {
                width: 320px;
                background: #f8fafc;
                border-right: 1px solid #e2e8f0;
                display: flex;
                flex-direction: column;
            }

            .chat-header {
                padding: 20px;
                background: #fff;
                border-bottom: 1px solid #e2e8f0;
                font-weight: 700;
                color: #176d7a;
                font-size: 1.1em;
            }

            .chat-list {
                flex: 1;
                overflow-y: auto;
            }

            .chat-item {
                padding: 15px 20px;
                border-bottom: 1px solid #f1f5f9;
                cursor: pointer;
                transition: background 0.2s;
            }

            .chat-item:hover {
                background: #eef6f8;
            }

            .chat-item.active {
                background: #e0f2f5;
                border-left: 4px solid #176d7a;
            }

            .chat-item-title {
                font-weight: 600;
                color: #334155;
                margin-bottom: 4px;
            }

            .chat-item-subtitle {
                font-size: 0.85em;
                color: #64748b;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .chat-main {
                flex: 1;
                display: flex;
                flex-direction: column;
                background: #fff;
            }

            .messages-area {
                flex: 1;
                padding: 20px;
                overflow-y: auto;
                display: flex;
                flex-direction: column;
                gap: 12px;
                background: #ffffff;
            }

            .message {
                max-width: 70%;
                padding: 10px 16px;
                border-radius: 12px;
                font-size: 0.95em;
                line-height: 1.5;
                position: relative;
                word-wrap: break-word;
            }

            .message.sent {
                align-self: flex-end;
                background: #176d7a;
                color: #fff;
                border-bottom-right-radius: 2px;
            }

            .message.received {
                align-self: flex-start;
                background: #f1f5f9;
                color: #334155;
                border-bottom-left-radius: 2px;
            }

            .message-time {
                font-size: 0.7em;
                margin-top: 4px;
                opacity: 0.8;
                text-align: right;
            }

            .input-area {
                padding: 20px;
                background: #fff;
                border-top: 1px solid #e2e8f0;
                display: flex;
                gap: 10px;
            }

            .input-area input {
                flex: 1;
                padding: 12px 16px;
                border: 1px solid #cbd5e1;
                border-radius: 24px;
                outline: none;
                transition: border-color 0.2s;
            }

            .input-area input:focus {
                border-color: #176d7a;
            }

            .input-area button {
                background: #176d7a;
                color: #fff;
                border: none;
                padding: 0 24px;
                border-radius: 24px;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.2s;
            }

            .input-area button:hover {
                background: #135a66;
            }

            .empty-state {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100%;
                color: #94a3b8;
            }

            .empty-state svg {
                width: 64px;
                height: 64px;
                margin-bottom: 16px;
                opacity: 0.5;
            }
        </style>
    </head>

    <body>
        <?php echo renderNavbar(); ?>

        <div class="chat-container">
            <div class="chat-sidebar">
                <div class="chat-header">Mis Conversaciones</div>
                <div class="chat-list" id="chatList">
                    <!-- Lista de chats cargada vía JS -->
                </div>
            </div>
            <div class="chat-main">
                <div id="chatHeader" class="chat-header" style="display:none; border-bottom: 1px solid #f0f0f0;">
                    <span id="chatTitle">Chat</span>
                </div>
                <div id="messagesArea" class="messages-area">
                    <div class="empty-state">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                            </path>
                        </svg>
                        <p>Selecciona una conversación para comenzar</p>
                    </div>
                </div>
                <div id="inputArea" class="input-area" style="display:none;">
                    <input type="text" id="messageInput" placeholder="Escribe un mensaje..." autocomplete="off">
                    <button id="sendBtn">Enviar</button>
                </div>
            </div>
        </div>

        <script>
            let currentChatId = <?php echo $id_chat_inicial; ?>;
            let pollingInterval;

            document.addEventListener('DOMContentLoaded', () => {
                loadChats();
                if (currentChatId > 0) {
                    loadMessages(currentChatId);
                }

                document.getElementById('sendBtn').addEventListener('click', sendMessage);
                document.getElementById('messageInput').addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') sendMessage();
                });
            });

            async function loadChats() {
                try {
                    const res = await fetch('chat_api.php?action=listar_chats');
                    const data = await res.json();
                    if (data.success) {
                        const list = document.getElementById('chatList');
                        list.innerHTML = '';
                        data.chats.forEach(chat => {
                            const item = document.createElement('div');
                            item.className = `chat-item ${chat.ID_CHAT == currentChatId ? 'active' : ''}`;
                            item.onclick = () => selectChat(chat.ID_CHAT, chat.NOM_PRO, chat.otro_usuario);
                            item.innerHTML = `
                            <div class="chat-item-title">${chat.otro_usuario}</div>
                            <div class="chat-item-subtitle">
                                <span style="font-weight:600;color:#176d7a;">${chat.NOM_PRO}</span> • 
                                ${chat.ultimo_mensaje || '<i>Sin mensajes</i>'}
                            </div>
                        `;
                            list.appendChild(item);
                        });
                    }
                } catch (e) {
                    console.error('Error cargando chats', e);
                }
            }

            function selectChat(id, product, user) {
                currentChatId = id;
                document.getElementById('chatTitle').textContent = `${user} - ${product}`;
                document.getElementById('chatHeader').style.display = 'block';
                document.getElementById('inputArea').style.display = 'flex';

                // Actualizar clase active
                document.querySelectorAll('.chat-item').forEach(el => el.classList.remove('active'));
                // Recargar lista para actualizar active (o hacerlo manual)
                loadChats();

                loadMessages(id);
            }

            async function loadMessages(id) {
                if (pollingInterval) clearInterval(pollingInterval);

                await fetchMessages(id);
                pollingInterval = setInterval(() => fetchMessages(id), 3000);
            }

            async function fetchMessages(id) {
                try {
                    const res = await fetch(`chat_api.php?action=obtener_mensajes&id_chat=${id}`);
                    const data = await res.json();
                    if (data.success) {
                        const area = document.getElementById('messagesArea');
                        // Simple render: limpiar y pintar todo (idealmente solo agregar nuevos)
                        // Para evitar parpadeo, podríamos comparar. Por simplicidad, repintamos.
                        area.innerHTML = '';

                        if (data.mensajes.length === 0) {
                            area.innerHTML = '<div class="empty-state"><p>No hay mensajes aún. ¡Saluda!</p></div>';
                        } else {
                            data.mensajes.forEach(msg => {
                                const div = document.createElement('div');
                                div.className = `message ${msg.es_mio ? 'sent' : 'received'}`;
                                div.textContent = msg.mensaje;
                                area.appendChild(div);
                            });
                            // Scroll al final
                            area.scrollTop = area.scrollHeight;
                        }
                    }
                } catch (e) {
                    console.error(e);
                }
            }

            async function sendMessage() {
                const input = document.getElementById('messageInput');
                const text = input.value.trim();
                if (!text || currentChatId <= 0) return;

                input.value = ''; // Limpiar inmediato

                try {
                    const formData = new FormData();
                    formData.append('id_chat', currentChatId);
                    formData.append('mensaje', text);

                    const res = await fetch('chat_api.php?action=enviar_mensaje', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        fetchMessages(currentChatId); // Recargar mensajes
                        loadChats(); // Actualizar último mensaje en sidebar
                    } else {
                        alert('Error al enviar: ' + data.error);
                    }
                } catch (e) {
                    console.error(e);
                    alert('Error de conexión');
                }
            }
        </script>
    </body>

</html>