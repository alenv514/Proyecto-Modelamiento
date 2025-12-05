<?php
function renderNavbar()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $roleId = isset($_SESSION['tipo_id']) ? (int) $_SESSION['tipo_id'] : null;
    $roleName = strtoupper(trim($_SESSION['tipo_usuario'] ?? ''));
    $isAdmin = ($roleId === 1) || (strpos($roleName, 'ADMIN') !== false);
    $isMod = ($roleId === 2) || (strpos($roleName, 'MODERADOR') !== false);
    $isUser = ($roleId === 3) || ($roleName === 'USUARIO');

    // Definir la ruta base del proyecto
    $base_url = '/Proyecto-Modelamiento/AppModel';

    // Construir lista de enlaces de forma más estructurada
    $items = [
        ['href' => $base_url . '/PAG_INICIO/DASHBOARD.php', 'icon' => '&#128200;', 'label' => 'Inicio'],
        ['href' => $base_url . '/USUARIO/AÑADIR_PRODUCTO.php', 'icon' => '&#128228;', 'label' => 'Añadir producto'],
        ['href' => $base_url . '/USUARIO/VER_PRODUCTOS.php', 'icon' => '&#128722;', 'label' => 'Ver productos'],
    ];
    if ($isUser || $isMod || $isAdmin) {
        $items[] = ['href' => $base_url . '/USUARIO/REVISAR_ESTADISTICAS.php', 'icon' => '&#128202;', 'label' => 'Estadísticas'];
    }
    if ($isUser || $isAdmin) {
        $items[] = ['href' => $base_url . '/USUARIO/REVISAR_LISTA_INTERESES.php', 'icon' => '&#128278;', 'label' => 'Lista de intereses'];
    }
    if ($isUser) {
        $items[] = ['href' => $base_url . '/USUARIO/REVISAR_REPORTES.php', 'icon' => '&#9888;', 'label' => 'Reportes'];
    }
    if ($isMod || $isAdmin) {
        $items[] = ['href' => $base_url . '/ADMIN/GESTION_APELACIONES.php', 'icon' => '&#9881;', 'label' => 'Apelaciones'];
        $items[] = ['href' => $base_url . '/ADMIN/GESTION_ESTADISTICAS.php', 'icon' => '&#128200;', 'label' => 'Estadísticas empresa'];
        $items[] = ['href' => $base_url . '/ADMIN/REVISAR_REPORTES.php', 'icon' => '&#128221;', 'label' => 'Revisar reportes'];
    }
    if ($isAdmin) {
        $items[] = ['href' => $base_url . '/ADMIN/GESTION_USUARIO.php', 'icon' => '&#128101;', 'label' => 'Gestionar usuarios'];
    }

    $menuHtml = '';
    foreach ($items as $it) {
        $menuHtml .= '<a class="nav-grid-item" role="menuitem" tabindex="-1" href="' . htmlspecialchars($it['href']) . '"><span class="item-icon">' . $it['icon'] . '</span><span class="item-label">' . htmlspecialchars($it['label']) . '</span></a>';
    }

    // Badge de ejemplo (puedes reemplazar con contador real guardado en sesión o BD)
    $notifCount = isset($_SESSION['notificaciones']) ? intval($_SESSION['notificaciones']) : 0;
    $cartCount = isset($_SESSION['carrito_count']) ? intval($_SESSION['carrito_count']) : 0;

    return '
    <style>
        :root{
            --accent:#176d7a;
            --accent-2:#2ca8c6;
            --muted:#6f8e90;
            --soft:#f4fbfb;
            --card-bg:#ffffff;
            --glass: rgba(255,255,255,0.75);
        }
        .top-nav{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:16px;
            padding:12px 20px;
            background:linear-gradient(180deg, var(--glass), #ffffff);
            border-bottom:1px solid #eef7f7;
            box-shadow:0 6px 16px rgba(15,40,45,0.06);
            position:sticky;
            top:0;
            z-index:999;
            backdrop-filter: blur(6px);
        }
        .nav-left{ display:flex; align-items:center; gap:14px; }
        .nav-brand{ font-weight:700; color:var(--accent); font-size:1.05rem; display:flex; align-items:center; gap:10px; }
        .nav-brand .logo-icon{ font-size:1.4rem; display:inline-block; transform:translateY(1px); }
        .nav-actions{ display:flex; gap:12px; align-items:center; }
        .nav-icon-btn{
            position:relative;
            background:transparent;
            border:none;
            cursor:pointer;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:40px;height:40px;border-radius:10px;
            color:var(--accent);
            transition:all .12s ease;
            outline:none;
        }
        .nav-icon-btn:hover{ transform:translateY(-3px); background:var(--soft); box-shadow:0 6px 18px rgba(15,40,45,0.04); }
        .nav-badge{ position:absolute; top:6px; right:6px; background:var(--accent-2); color:white; font-size:0.7rem; min-width:18px; height:18px; display:inline-flex; align-items:center; justify-content:center; padding:0 5px; border-radius:9px; box-shadow:0 2px 6px rgba(44,168,198,0.12); }
        /* Dropdown container */
        .user-dropdown { position:relative; display:inline-block; }
        .dropdown-panel{
            position:absolute;
            right:0;
            top:calc(100% + 12px);
            min-width:320px;
            background:var(--card-bg);
            border-radius:12px;
            box-shadow:0 12px 36px rgba(14,34,36,0.12);
            overflow:hidden;
            transform-origin:top right;
            opacity:0; transform: translateY(-6px) scale(.98);
            transition:transform .16s cubic-bezier(.2,.9,.2,1), opacity .14s ease;
            border:1px solid #eef6f6;
            display:none;
            padding:10px;
            z-index:9999;
        }
        .dropdown-panel.open{ display:block; opacity:1; transform:translateY(0) scale(1); }
        /* Grid layout for header menu */
        .nav-grid{
            display:grid;
            grid-template-columns: repeat(2, 1fr);
            gap:8px;
            padding:8px;
        }
        .nav-grid-item{
            display:flex;
            align-items:center;
            gap:10px;
            padding:10px;
            border-radius:10px;
            text-decoration:none;
            color:var(--accent);
            transition:background .12s ease, transform .08s ease;
            outline:none;
        }
        .nav-grid-item:focus, .nav-grid-item:hover{
            background:linear-gradient(90deg,#f7fffe,#f2fbfc);
            transform:translateY(-3px);
            box-shadow:0 8px 18px rgba(20,80,82,0.06);
        }
        .item-icon{
            width:40px;height:40px;border-radius:9px;background:linear-gradient(180deg,#e9faf9,#f3ffff);display:inline-flex;align-items:center;justify-content:center;font-size:1.1rem;color:var(--accent);
            box-shadow:inset 0 -6px 14px rgba(40,140,128,0.03);
        }
        .item-label{ font-size:0.95rem; font-weight:600; color:var(--accent); }
        .dropdown-footer{
            border-top:1px dashed #eef8f8;
            margin-top:10px;
            padding-top:10px;
            display:flex;
            gap:8px;
            align-items:center;
            justify-content:space-between;
        }
        .profile-box{ display:flex; gap:10px; align-items:center; }
        .profile-avatar{ width:40px;height:40px;border-radius:999px;background:linear-gradient(90deg,#2ca8c6,#176d7a);display:inline-flex;align-items:center;justify-content:center;color:#fff;font-weight:700; }
        .profile-actions a, .profile-actions button{ border:none;background:none;color:var(--accent); padding:8px 10px;border-radius:8px; cursor:pointer; text-decoration:none; }
        .profile-actions a:hover, .profile-actions button:hover{ background:#fbfffe; transform:translateY(-2px); }
        /* Responsive adjustments */
        @media (max-width:520px){
            .dropdown-panel{ right:10px; left:10px; min-width:unset; }
            .nav-grid{ grid-template-columns: 1fr; }
        }
    </style>

    <div class="top-nav" role="navigation" aria-label="Top Navigation">
        <div class="nav-left">
            <div class="nav-brand" title="ShopMate">
                <span class="logo-icon">&#128722;</span>
                <span>ShopMate</span>
            </div>
        </div>

        <div class="nav-actions" aria-hidden="false">
            <button class="nav-icon-btn" id="notifBtn" aria-haspopup="true" aria-expanded="false" title="Notificaciones" type="button">
                &#128276;
                ' . ($notifCount > 0 ? '<span class="nav-badge" aria-hidden="false">' . $notifCount . '</span>' : '') . '
            </button>

            <button class="nav-icon-btn" id="cartBtn" aria-haspopup="true" aria-expanded="false" title="Carrito" type="button">
                &#128722;
                ' . ($cartCount > 0 ? '<span class="nav-badge" aria-hidden="false">' . $cartCount . '</span>' : '') . '
            </button>

            <div class="user-dropdown" id="mainMenuDropdownWrap">
                <button class="nav-icon-btn" id="navMenuToggle" aria-haspopup="true" aria-expanded="false" aria-controls="navMenuDropdown" title="Menú" type="button">&#9776;</button>
                <div class="dropdown-panel" id="navMenuDropdown" role="menu" aria-label="Menú principal">
                    <div class="nav-grid">
                        ' . $menuHtml . '
                    </div>
                    <div class="dropdown-footer">
                        <div class="profile-box">
                            <div class="profile-avatar">' . (isset($_SESSION['usuario']) && strlen($_SESSION['usuario']) ? strtoupper(substr($_SESSION['usuario'], 0, 1)) : 'U') . '</div>
                            <div style="display:flex;flex-direction:column;">
                                <strong style="color:var(--accent);font-size:.95rem;">' . (htmlspecialchars($_SESSION['usuario'] ?? 'Invitado')) . '</strong>
                                <small style="color:var(--muted);font-size:.8rem;">' . ($isAdmin ? 'Administrador' : ($isMod ? 'Moderador' : 'Usuario')) . '</small>
                            </div>
                        </div>
                        <div class="profile-actions">
                            <a href="' . $base_url . '/USUARIO/MI_PERFIL.php" title="Mi perfil">Mi perfil</a>
                            <form method="post" action="' . $base_url . '/PAG_INICIO/logout.php" style="display:inline-block;margin:0;">
                                <button type="submit">Cerrar sesión</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- adminChatButton removido -->
        </div>
    </div>

    <script>
    (function(){
        // ARIA + UX utilities
        function togglePanel(btn, panel) {
            const isOpen = panel.classList.contains("open");
            if(isOpen) {
                panel.classList.remove("open");
                btn.setAttribute("aria-expanded","false");
            } else {
                panel.classList.add("open");
                btn.setAttribute("aria-expanded","true");
                // focus the first focusable item inside
                const first = panel.querySelector("[role=menuitem], a, button");
                if(first) { first.focus(); }
            }
        }
        function closePanel(btn, panel) {
            panel.classList.remove("open");
            btn.setAttribute("aria-expanded","false");
        }

        const navBtn = document.getElementById("navMenuToggle");
        const navPanel = document.getElementById("navMenuDropdown");
        const userBtn = document.getElementById("userMenuBtn");
        const userPanel = document.getElementById("userDropdown");
        const notifBtn = document.getElementById("notifBtn");
        const cartBtn = document.getElementById("cartBtn");

        // Click handlers
        if(navBtn && navPanel){
            navBtn.addEventListener("click", function(e){
                e.stopPropagation();
                togglePanel(navBtn, navPanel);
            });
        }
        if(userBtn && userPanel){
            userBtn.addEventListener("click", function(e){
                e.stopPropagation();
                togglePanel(userBtn, userPanel);
            });
        }
        if(notifBtn){
            notifBtn.addEventListener("click", function(e){
                e.stopPropagation();
                // comportamiento simple: abrir panel lateral o redirigir
                window.location.href = "' . $base_url . '/USUARIO/NOTIFICACIONES.php";
            });
        }
        if(cartBtn){
            cartBtn.addEventListener("click", function(e){
                e.stopPropagation();
                window.location.href = "' . $base_url . '/USUARIO/CARRITO.php";
            });
        }

        // Close on outside click
        document.addEventListener("click", function() {
            if(navPanel) closePanel(navBtn, navPanel);
            if(userPanel) closePanel(userBtn, userPanel);
        });

        // Keyboard support: Esc closes, arrow navigation inside navPanel
        document.addEventListener("keydown", function(e){
            if(e.key === "Escape") {
                if(navPanel) closePanel(navBtn, navPanel);
                if(userPanel) closePanel(userBtn, userPanel);
            }
        });

        // arrow navigation inside grid
        if(navPanel){
            navPanel.addEventListener("keydown", function(e){
                const focusables = Array.from(navPanel.querySelectorAll("[role=menuitem], a, button")).filter(Boolean);
                if(!focusables.length) return;
                const idx = focusables.indexOf(document.activeElement);
                if(e.key === "ArrowRight" || e.key === "ArrowDown") {
                    e.preventDefault();
                    const next = focusables[(idx+1) % focusables.length];
                    next && next.focus();
                } else if(e.key === "ArrowLeft" || e.key === "ArrowUp") {
                    e.preventDefault();
                    const prev = focusables[(idx-1 + focusables.length) % focusables.length];
                    prev && prev.focus();
                } else if(e.key === "Home") { e.preventDefault(); focusables[0].focus(); }
                else if(e.key === "End") { e.preventDefault(); focusables[focusables.length-1].focus(); }
            });
        }

        // Make panel items tabbable when open
        function syncTabIndices(panel, open) {
            const items = panel ? panel.querySelectorAll("[role=menuitem], a, button") : [];
            items.forEach(function(it){
                it.tabIndex = open ? 0 : -1;
            });
        }
        // Observe open/close to set tab indices
        const observer = new MutationObserver(function(mutations){
            mutations.forEach(function(m){
                if(m.target === navPanel) syncTabIndices(navPanel, navPanel.classList.contains("open"));
                if(m.target === userPanel) syncTabIndices(userPanel, userPanel.classList.contains("open"));
            });
        });
        if(navPanel) observer.observe(navPanel, { attributes: true, attributeFilter: ["class"] });
        if(userPanel) observer.observe(userPanel, { attributes: true, attributeFilter: ["class"] });
    })();
    </script>';
}
?>