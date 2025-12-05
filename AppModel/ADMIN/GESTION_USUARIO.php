<?php
session_start();
include __DIR__ . '/../db.php';
include __DIR__ . '/../PAG_INICIO/navbar.php';

// Verificar conexión y permisos
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if (
    !isset($_SESSION['tipo_usuario']) ||
    ($_SESSION['tipo_usuario'] !== 'ADMIN' && $_SESSION['tipo_usuario'] !== 'MODERADOR')
) {
    die("Acceso no autorizado");
}

// Para peticiones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'list') {
        try {
            $sql = "SELECT u.ID_USU, u.NIC_USU, u.NOM_USU, u.APE_USU, 
                    u.COR_USU, t.nombre_tipo, u.EST_USU, u.FEC_BAN_USU 
                    FROM usuarios u 
                    LEFT JOIN tipos_usuario t ON u.ID_TIP_USU = t.ID_TIP 
                    ORDER BY u.ID_USU ASC";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception($conn->error);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }

            echo json_encode(['ok' => true, 'data' => $users]);
            exit;

        } catch (Exception $e) {
            error_log("Error en consulta de usuarios: " . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Error al cargar usuarios']);
            exit;
        }
    }

    if ($_POST['action'] === 'ban') {
        if (!isset($_POST['user_id'], $_POST['amount'], $_POST['unit'])) {
            echo json_encode(['ok' => false, 'msg' => 'Faltan parámetros']);
            exit;
        }

        $user_id = (int) $_POST['user_id'];
        $amount = (int) $_POST['amount'];
        $unit = $_POST['unit'];

        if ($amount <= 0) {
            echo json_encode(['ok' => false, 'msg' => 'Tiempo inválido']);
            exit;
        }

        $now = new DateTime('now');
        switch ($unit) {
            case 'hours':
                $now->modify("+{$amount} hours");
                break;
            case 'days':
                $now->modify("+{$amount} days");
                break;
            case 'months':
                $now->modify("+{$amount} months");
                break;
            case 'years':
                $now->modify("+{$amount} years");
                break;
            default:
                echo json_encode(['ok' => false, 'msg' => 'Unidad inválida']);
                exit;
        }
        $until = $now->format('Y-m-d H:i:s');

        // Actualizar estado y fecha de baneo
        $stmt = $conn->prepare("UPDATE usuarios SET EST_USU = 'B', FEC_BAN_USU = ? WHERE ID_USU = ?");
        if (!$stmt) {
            echo json_encode(['ok' => false, 'msg' => 'Error BD: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param("si", $until, $user_id);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            echo json_encode(['ok' => false, 'msg' => 'No se pudo banear: ' . $conn->error]);
            exit;
        }

        echo json_encode(['ok' => true, 'until' => $until, 'msg' => 'Usuario baneado hasta ' . $until]);
        exit;
    }

    if ($_POST['action'] === 'change_role') {
        if (!isset($_POST['user_id'], $_POST['role_id'])) {
            echo json_encode(['ok' => false, 'msg' => 'Faltan parámetros']);
            exit;
        }

        $user_id = (int) $_POST['user_id'];
        $role_id = (int) $_POST['role_id'];

        $stmt = $conn->prepare("UPDATE usuarios SET ID_TIP_USU = ? WHERE ID_USU = ?");
        if (!$stmt) {
            echo json_encode(['ok' => false, 'msg' => 'Error BD: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param("ii", $role_id, $user_id);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            echo json_encode(['ok' => false, 'msg' => 'No se pudo cambiar rol: ' . $conn->error]);
            exit;
        }

        echo json_encode(['ok' => true, 'msg' => 'Rol actualizado']);
        exit;
    }

    if ($_POST['action'] === 'delete') {
        if (!isset($_POST['user_id'])) {
            echo json_encode(['ok' => false, 'msg' => 'Faltan parámetros']);
            exit;
        }

        $user_id = (int) $_POST['user_id'];

        $stmt = $conn->prepare("DELETE FROM usuarios WHERE ID_USU = ?");
        if (!$stmt) {
            echo json_encode(['ok' => false, 'msg' => 'Error BD: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param("i", $user_id);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            echo json_encode(['ok' => false, 'msg' => 'No se pudo eliminar: ' . $conn->error]);
            exit;
        }

        echo json_encode(['ok' => true, 'msg' => 'Usuario eliminado']);
        exit;
    }

    // NUEVO: devolver lista de roles (tipos_usuario)
    if ($_POST['action'] === 'roles') {
        $rows = [];
        $q = $conn->query("SELECT ID_TIP, nombre_tipo FROM tipos_usuario ORDER BY nombre_tipo ASC");
        if ($q) {
            while ($r = $q->fetch_assoc())
                $rows[] = $r;
            echo json_encode(['ok' => true, 'data' => $rows]);
            exit;
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Error al cargar roles']);
            exit;
        }
    }

    // NUEVO: desbanear usuario (est -> 'L', limpiar fecha)
    if ($_POST['action'] === 'unban') {
        if (!isset($_POST['user_id'])) {
            echo json_encode(['ok' => false, 'msg' => 'Faltan parámetros']);
            exit;
        }
        $user_id = (int) $_POST['user_id'];
        $stmt = $conn->prepare("UPDATE usuarios SET EST_USU = 'L', FEC_BAN_USU = NULL WHERE ID_USU = ?");
        if (!$stmt) {
            echo json_encode(['ok' => false, 'msg' => 'Error BD: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("i", $user_id);
        $ok = $stmt->execute();
        $stmt->close();
        if (!$ok) {
            echo json_encode(['ok' => false, 'msg' => 'No se pudo desbanear: ' . $conn->error]);
            exit;
        }
        echo json_encode(['ok' => true, 'msg' => 'Usuario desbaneado']);
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Acción no reconocida']);
    exit;
}

// ----- Nuevo: consulta servidor para renderizar la tabla al cargar la página -----
// incluir EST_USU y FEC_BAN_USU para usar en la interfaz
$admins = $mods = $resto = [];
$stmt = $conn->prepare(
    "SELECT u.ID_USU, u.NIC_USU, u.NOM_USU, u.APE_USU, u.COR_USU,
            COALESCE(t.nombre_tipo,'Sin rol') AS nombre_tipo,
            COALESCE(u.EST_USU,'L') AS EST_USU, u.FEC_BAN_USU
     FROM usuarios u
     LEFT JOIN tipos_usuario t ON u.ID_TIP_USU = t.ID_TIP
     ORDER BY u.ID_USU ASC"
);
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $tipo = strtoupper($r['nombre_tipo'] ?? '');
        if (strpos($tipo, 'ADMIN') !== false) {
            $admins[] = $r;
        } elseif (strpos($tipo, 'MODERADOR') !== false) {
            $mods[] = $r;
        } else {
            $resto[] = $r;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">

    <head>
        <meta charset="UTF-8">
        <title>Gestión de Usuarios</title>
        <style>
            .container {
                padding: 20px;
            }

            .tables-row {
                display: flex;
                gap: 18px;
                flex-wrap: wrap;
                align-items: flex-start;
            }

            .role-table {
                flex: 1 1 320px;
                background: #fff;
                padding: 12px;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            }

            .role-table h3 {
                margin: 0 0 8px 0;
                color: #176d7a;
            }

            .table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 8px;
            }

            .table th,
            .table td {
                padding: 8px 10px;
                border-bottom: 1px solid #eee;
                text-align: left;
                font-size: 0.95em;
            }

            .btn-gestionar {
                padding: 6px 10px;
                background: #176d7a;
                color: #fff;
                border: none;
                border-radius: 6px;
                cursor: pointer;
            }

            .btn-gestionar:hover {
                background: #2ca8c6;
            }

            /* modal styles (existing) */
            .modal-back {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.35);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            }

            .modal {
                background: #fff;
                padding: 18px;
                border-radius: 8px;
                min-width: 320px;
                max-width: 520px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2)
            }

            .modal h3 {
                margin: 0 0 8px 0
            }

            .modal .actions {
                display: flex;
                gap: 8px;
                margin-top: 12px;
                flex-wrap: wrap
            }

            .modal .actions button {
                padding: 8px 12px;
                border-radius: 6px;
                border: 1px solid #ddd;
                cursor: pointer;
                background: #f4f8fb;
                color: #176d7a
            }

            .modal .danger {
                background: #ffecec;
                border-color: #f5c6cb;
                color: #a33
            }

            .modal .primary {
                background: #176d7a;
                color: #fff;
                border-color: #176d7a
            }

            .field {
                margin-top: 10px
            }
        </style>
    </head>

    <body>
        <?php echo renderNavbar(); ?>
        <div class="container">
            <h2>Gestión de Usuarios</h2>

            <div class="tables-row" id="users-container">
                <!-- Tabla Admin -->
                <div class="role-table" id="admins-table">
                    <h3>Administradores</h3>
                    <table class="table" id="admins-tbody-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (empty($admins)) {
                                echo '<tr><td colspan="4" style="text-align:center;padding:8px;">Sin administradores</td></tr>';
                            } else {
                                foreach ($admins as $u) {
                                    $id = (int) $u['ID_USU'];
                                    $nick = htmlspecialchars($u['NIC_USU'] ?? '', ENT_QUOTES);
                                    $est = ($u['EST_USU'] === 'B') ? 'Baneado' : 'Libre';
                                    $nic = addslashes($u['NIC_USU'] ?? '');
                                    echo "<tr>
                                        <td>{$id}</td>
                                        <td>{$nick}</td>
                                        <td>{$est}</td>
                                        <td><button class='btn-gestionar' onclick=\"gestionarUsuario({$id}, '{$nic}', '{$u['EST_USU']}', '" . ($u['FEC_BAN_USU'] ?? '') . "')\">⚙️</button></td>
                                      </tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Tabla Moderadores -->
                <div class="role-table" id="mods-table">
                    <h3>Moderadores</h3>
                    <table class="table" id="mods-tbody-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (empty($mods)) {
                                echo '<tr><td colspan="4" style="text-align:center;padding:8px;">Sin moderadores</td></tr>';
                            } else {
                                foreach ($mods as $u) {
                                    $id = (int) $u['ID_USU'];
                                    $nick = htmlspecialchars($u['NIC_USU'] ?? '', ENT_QUOTES);
                                    $est = ($u['EST_USU'] === 'B') ? 'Baneado' : 'Libre';
                                    $nic = addslashes($u['NIC_USU'] ?? '');
                                    echo "<tr>
                                        <td>{$id}</td>
                                        <td>{$nick}</td>
                                        <td>{$est}</td>
                                        <td><button class='btn-gestionar' onclick=\"gestionarUsuario({$id}, '{$nic}', '{$u['EST_USU']}', '" . ($u['FEC_BAN_USU'] ?? '') . "')\">⚙️</button></td>
                                      </tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Tabla Usuarios -->
                <div class="role-table" id="users-plain-table">
                    <h3>Usuarios</h3>
                    <table class="table" id="users-tbody-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (empty($resto)) {
                                echo '<tr><td colspan="4" style="text-align:center;padding:8px;">Sin usuarios</td></tr>';
                            } else {
                                foreach ($resto as $u) {
                                    $id = (int) $u['ID_USU'];
                                    $nick = htmlspecialchars($u['NIC_USU'] ?? '', ENT_QUOTES);
                                    $est = ($u['EST_USU'] === 'B') ? 'Baneado' : 'Libre';
                                    $nic = addslashes($u['NIC_USU'] ?? '');
                                    echo "<tr>
                                        <td>{$id}</td>
                                        <td>{$nick}</td>
                                        <td>{$est}</td>
                                        <td><button class='btn-gestionar' onclick=\"gestionarUsuario({$id}, '{$nic}', '{$u['EST_USU']}', '" . ($u['FEC_BAN_USU'] ?? '') . "')\">⚙️</button></td>
                                      </tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Nuevo: modal de gestión -->
        <div class="modal-back" id="userModalBack" aria-hidden="true">
            <div class="modal" role="dialog" aria-modal="true" id="userModal" tabindex="0">
                <div id="userModalBody"></div>
                <!-- boton cerrar reaparecido -->
                <div style="text-align:right;margin-top:12px;">
                    <button id="userModalClose" type="button" onclick="closeUserModal()">Cerrar</button>
                </div>
            </div>
        </div>

        <script>
            const API_URL = '/Proyecto-Modelamiento/AppModel/ADMIN/GESTION_USUARIO.php';
            const SESSION_ROLE = "<?php echo isset($_SESSION['tipo_usuario']) ? addslashes($_SESSION['tipo_usuario']) : ''; ?>".toUpperCase();

            // loadUsers devuelve lista completa; renderUsers la agrupa y rellena las tres tablas dinámicamente
            function loadUsers() {
                return fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=list'
                }).then(r => r.json());
            }

            function renderUsers(users) {
                // agrupar
                const admins = [], mods = [], resto = [];
                users.forEach(u => {
                    const tipo = (u.nombre_tipo || '').toUpperCase();
                    if (tipo.indexOf('ADMIN') !== -1) admins.push(u);
                    else if (tipo.indexOf('MODERADOR') !== -1) mods.push(u);
                    else resto.push(u);
                });

                function fillTable(tbodySelector, arr) {
                    const tbody = document.querySelector(tbodySelector);
                    if (!tbody) return;
                    tbody.innerHTML = '';
                    if (!arr.length) {
                        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:8px;">Sin registros</td></tr>';
                        return;
                    }
                    arr.forEach(u => {
                        const tr = document.createElement('tr');
                        const nicEsc = escapeHtml(u.NIC_USU || '');
                        const estLabel = (u.EST_USU === 'B') ? 'Baneado' : 'Libre';
                        tr.innerHTML = `<td>${u.ID_USU}</td>
                                    <td>${nicEsc}</td>
                                    <td>${estLabel}</td>
                                    <td><button class="btn-gestionar" onclick="gestionarUsuario(${u.ID_USU}, '${nicEsc}', '${u.EST_USU}', '${u.FEC_BAN_USU || ''}')">⚙️</button></td>`;
                        tbody.appendChild(tr);
                    });
                }

                fillTable('#admins-tbody-table tbody', admins);
                fillTable('#mods-tbody-table tbody', mods);
                fillTable('#users-tbody-table tbody', resto);
            }

            // abre modal con contenido inicial (nombre + acciones según rol)
            function gestionarUsuario(userId, nick, estado, fecBan) {
                const back = document.getElementById('userModalBack');
                const body = document.getElementById('userModalBody');
                body.innerHTML = '';
                const title = document.createElement('h3');
                title.textContent = 'Usuario: ' + nick + ' (ID ' + userId + ')';
                body.appendChild(title);

                const info = document.createElement('div');
                info.innerHTML = `<div style="margin-top:8px;color:#555">Escoge una acción:</div>`;
                body.appendChild(info);

                const actions = document.createElement('div');
                actions.className = 'actions';

                // Si el usuario está baneado mostrar "Desbanear" en lugar de "Banear usuario"
                if (estado === 'B') {
                    const unbanBtn = document.createElement('button');
                    unbanBtn.textContent = 'Desbanear';
                    unbanBtn.className = 'primary';
                    unbanBtn.addEventListener('click', function () {
                        if (!confirm('Confirmar desbanear usuario ID ' + userId + ' ?')) return;
                        fetch(API_URL, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'action=unban&user_id=' + encodeURIComponent(userId)
                        }).then(r => r.json()).then(js => {
                            if (!js.ok) return alert(js.msg || 'Error al desbanear');
                            alert(js.msg || 'Usuario desbaneado');
                            closeUserModal();
                            if (typeof loadUsers === 'function') loadUsers().then(d => { if (d.ok) renderUsers(d.data); });
                        }).catch(e => { console.error(e); alert('Error al desbanear'); });
                    });
                    actions.appendChild(unbanBtn);
                } else {
                    // Banear (disponible para ADMIN y MODERADOR)
                    const banBtn = document.createElement('button');
                    banBtn.textContent = 'Banear usuario';
                    banBtn.addEventListener('click', function () { showBanForm(userId, nick); });
                    actions.appendChild(banBtn);
                }

                // Cambiar rol (solo ADMIN)
                if (SESSION_ROLE.indexOf('MODERADOR') === -1) {
                    const roleBtn = document.createElement('button');
                    roleBtn.textContent = 'Cambiar rol';
                    roleBtn.addEventListener('click', function () { showChangeRoleForm(userId); });
                    actions.appendChild(roleBtn);
                }

                // Eliminar (ADMIN y MODERADOR)
                const delBtn = document.createElement('button');
                delBtn.textContent = 'Eliminar usuario';
                delBtn.className = 'danger';
                delBtn.addEventListener('click', function () {
                    if (!confirm('¿Eliminar usuario ID ' + userId + '? Esta acción es irreversible.')) return;
                    fetch(API_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=delete&user_id=' + encodeURIComponent(userId)
                    }).then(r => r.json()).then(js => {
                        if (!js.ok) return alert(js.msg || 'Error al eliminar');
                        alert(js.msg || 'Usuario eliminado');
                        closeUserModal();
                        if (typeof loadUsers === 'function') loadUsers().then(d => { if (d.ok) renderUsers(d.data); });
                    }).catch(e => { console.error(e); alert('Error al eliminar'); });
                });
                actions.appendChild(delBtn);

                body.appendChild(actions);
                openUserModal();
            }

            // Mostrar formulario de baneo
            function showBanForm(userId, nick) {
                const body = document.getElementById('userModalBody');
                body.innerHTML = `<h3>Banear: ${escapeHtml(nick)}</h3>`;
                const f = document.createElement('div');
                f.className = 'field';
                f.innerHTML = `
                <label>Cantidad: <input type="number" id="banAmount" value="1" min="1" style="width:80px"></label>
                <label style="margin-left:8px">Unidad:
                    <select id="banUnit">
                        <option value="hours">Horas</option>
                        <option value="days" selected>Días</option>
                        <option value="months">Meses</option>
                        <option value="years">Años</option>
                    </select>
                </label>
                <div style="margin-top:12px">
                    <button id="confirmBan" class="primary">Confirmar ban</button>
                    <button id="cancelBan" style="margin-left:8px">Cancelar</button>
                </div>
            `;
                body.appendChild(f);

                document.getElementById('cancelBan').addEventListener('click', function () { gestionarUsuario(userId, nick); });
                document.getElementById('confirmBan').addEventListener('click', function () {
                    const amount = parseInt(document.getElementById('banAmount').value, 10);
                    const unit = document.getElementById('banUnit').value;
                    if (!amount || amount <= 0) return alert('Cantidad inválida');
                    fetch(API_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=ban&user_id=' + encodeURIComponent(userId) + '&amount=' + encodeURIComponent(amount) + '&unit=' + encodeURIComponent(unit)
                    }).then(r => r.json()).then(js => {
                        if (!js.ok) return alert(js.msg || 'Error al banear');
                        alert(js.msg || 'Usuario baneado');
                        closeUserModal();
                        if (typeof loadUsers === 'function') loadUsers().then(d => { if (d.ok) renderUsers(d.data); });
                    }).catch(e => { console.error(e); alert('Error al banear'); });
                });
            }

            // Mostrar formulario para cambiar rol
            function showChangeRoleForm(userId) {
                const body = document.getElementById('userModalBody');
                body.innerHTML = `<h3>Cambiar rol (ID ${userId})</h3>`;
                const wrapper = document.createElement('div');
                wrapper.className = 'field';
                wrapper.innerHTML = `<div>Cargando roles...</div>`;
                body.appendChild(wrapper);

                // pedir roles al servidor
                fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=roles'
                }).then(r => r.json()).then(js => {
                    if (!js.ok) return alert(js.msg || 'No se cargaron roles');
                    wrapper.innerHTML = '';
                    const sel = document.createElement('select');
                    sel.id = 'roleSelect';
                    js.data.forEach(r => {
                        const opt = document.createElement('option');
                        opt.value = r.ID_TIP;
                        opt.textContent = r.nombre_tipo;
                        sel.appendChild(opt);
                    });
                    wrapper.appendChild(sel);
                    const btn = document.createElement('button');
                    btn.textContent = 'Actualizar rol';
                    btn.style.marginLeft = '8px';
                    btn.className = 'primary';
                    btn.addEventListener('click', function () {
                        const role_id = sel.value;
                        fetch(API_URL, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'action=change_role&user_id=' + encodeURIComponent(userId) + '&role_id=' + encodeURIComponent(role_id)
                        }).then(r => r.json()).then(js2 => {
                            if (!js2.ok) return alert(js2.msg || 'Error al actualizar rol');
                            alert(js2.msg || 'Rol actualizado');
                            closeUserModal();
                            if (typeof loadUsers === 'function') loadUsers().then(d => { if (d.ok) renderUsers(d.data); });
                        }).catch(e => { console.error(e); alert('Error al actualizar rol'); });
                    });
                    wrapper.appendChild(btn);
                }).catch(e => { console.error(e); alert('Error al cargar roles'); });
            }

            // helpers de modal
            function openUserModal() {
                const b = document.getElementById('userModalBack'); b.style.display = 'flex'; b.setAttribute('aria-hidden', 'false');
                const modal = document.getElementById('userModal'); if (modal) modal.focus();
            }
            function closeUserModal() { const b = document.getElementById('userModalBack'); b.style.display = 'none'; b.setAttribute('aria-hidden', 'true'); }

            // ya no es necesario el IIFE para el botón; se cierra por clic fuera y por Escape también
            // clic en el fondo cierra; evitamos cerrar al clicar dentro del cuadro modal
            document.getElementById('userModalBack').addEventListener('click', function (e) { if (e.target === this) closeUserModal(); });
            document.getElementById('userModal').addEventListener('click', function (e) { e.stopPropagation(); });

            // Cerrar modal con tecla Escape
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    const back = document.getElementById('userModalBack');
                    if (back && back.style.display === 'flex') {
                        closeUserModal();
                    }
                }
            });

            // util escapado
            function escapeHtml(s) { return String(s || '').replace(/[&<>"']/g, function (m) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": "&#39;" }[m]; }); }

            // Si se realizan acciones via modal, recargar dinámicamente:
            // ejemplo: loadUsers().then(d=>{ if (d.ok) renderUsers(d.data); })
        </script>
    </body>

</html>