<?php
session_start();
include __DIR__ . '/../db.php';
include __DIR__ . '/../PAG_INICIO/navbar.php';

// Verificar que $conn esté disponible
if (!isset($conn) || !$conn) {
    die("No hay conexión a la base de datos.");
}

// Consulta para obtener productos con su imagen principal
$sql = "SELECT 
            p.ID_PRO,
            p.NOM_PRO,
            p.PRECIO,
            (SELECT i.URL_IMG FROM IMAGENES_PRODUCTOS i WHERE i.ID_PRO_PER = p.ID_PRO ORDER BY i.ID_IMG ASC LIMIT 1) as imagen
        FROM PRODUCTOS p
        ORDER BY p.ID_PRO DESC";

$result = $conn->query($sql);
$productos = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['imagen'] = $row['imagen'] ? htmlspecialchars($row['imagen']) : 'https://via.placeholder.com/220x160.png?text=Sin+Imagen';
        $productos[] = $row;
    }
}

// Mantener endpoint get_product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_product') {
    if (!isset($conn) || !$conn) {
        http_response_code(500);
        echo json_encode(['error' => 'No hay conexión a la base de datos.']);
        exit;
    }
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }

    // Prepared: obtener producto + primera imagen + owner (columnas reales: DES_PRO y ID_USUARIO)
    $stmt = $conn->prepare("
        SELECT 
            p.ID_PRO,
            p.NOM_PRO,
            p.PRECIO,
            p.DES_PRO AS DESCRIPCION,
            COALESCE((SELECT i.URL_IMG FROM IMAGENES_PRODUCTOS i WHERE i.ID_PRO_PER = p.ID_PRO ORDER BY i.ID_IMG ASC LIMIT 1), '') AS imagen,
            p.ID_USUARIO AS owner_id
        FROM PRODUCTOS p
        WHERE p.ID_PRO = ?
        LIMIT 1
    ");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Error en la consulta.']);
        exit;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $product = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if ($product) {
        $product['imagen'] = $product['imagen'] ?: 'https://via.placeholder.com/420x300.png?text=Sin+Imagen';
        echo json_encode(['ok' => true, 'product' => $product]);
    } else {
        echo json_encode(['error' => 'Producto no encontrado']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

    <head>
        <meta charset="utf-8">
        <title>Ver Productos - ShopMate</title>
        <link rel="stylesheet" href="/Proyecto-Modelamiento/AppModel/PAG_INICIO/styles.css">
        <style>
            .page-container {
                padding: 20px;
                max-width: 1180px;
                margin: 0 auto;
            }
        </style>
    </head>

    <body>
        <?php echo renderNavbar(); ?>

        <div class="page-container">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h2 style="margin:0;color:#176d7a;">Productos</h2>
            </div>

            <!-- Barra de búsqueda -->
            <div style="display:flex;gap:12px;align-items:center;margin-bottom:16px;">
                <div style="position:relative;flex:1;max-width:720px;">
                    <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18"
                        height="18"
                        style="position:absolute;left:12px;top:50%;transform:translateY(-50%);fill:#9fbfc5;pointer-events:none;"></svg>
                    <input id="productSearch" type="search" placeholder="Buscar productos, p.ej. 'Smartphone' o '799'"
                        style="width:100%;padding:12px 16px 12px 44px;border-radius:12px;border:1px solid #e0eef0;box-shadow:0 4px 18px rgba(10,35,46,0.06);font-size:15px;outline:none;transition:box-shadow .18s ease;">
                </div>
                <div id="searchCount"
                    style="background:#eef6f8;color:#176d7a;padding:8px 12px;border-radius:12px;font-weight:700;font-size:0.95em;min-width:72px;text-align:center;">
                    Todos</div>
            </div>

            <style>
                .highlight {
                    background: linear-gradient(90deg, #ffd54d33, #ffd54d11);
                    padding: 0 3px;
                    border-radius: 3px;
                }

                .product-card {
                    transition: transform .12s ease, opacity .12s ease;
                }

                .no-results {
                    text-align: center;
                    color: #557c82;
                    padding: 28px;
                    border-radius: 10px;
                    background: #f6fbfc;
                    margin-top: 12px;
                }

                input#productSearch:focus {
                    box-shadow: 0 8px 30px rgba(36, 125, 132, 0.12);
                    border-color: #bfe6ea;
                }
            </style>

            <div id="productsGrid" style="
            display:grid;
            grid-template-columns: repeat(auto-fill,minmax(220px,1fr));
            gap:18px;
        ">
                <?php if (!empty($productos)): ?>
                    <?php foreach ($productos as $p): ?>
                        <div class="product-card" data-id="<?php echo htmlspecialchars($p['ID_PRO']); ?>"
                            data-name="<?php echo htmlspecialchars(strtolower($p['NOM_PRO'])); ?>"
                            data-price="<?php echo htmlspecialchars($p['PRECIO']); ?>"
                            style="background:#fff;border-radius:10px;padding:12px;box-shadow:0 2px 8px #0001;display:flex;flex-direction:column;align-items:flex-start; position:relative;">
                            <div style="width:100%;border-radius:8px;overflow:hidden;background:#f6f9fb;">
                                <img src="<?php echo $p['imagen']; ?>" alt="<?php echo htmlspecialchars($p['NOM_PRO']); ?>"
                                    style="width:100%;height:160px;object-fit:cover;display:block;">
                            </div>
                            <div
                                style="margin-top:10px;width:100%;display:flex;justify-content:space-between;align-items:flex-start;">
                                <div>
                                    <div style="color:#176d7a;font-weight:700;font-size:1.05em;">
                                        $<?php echo htmlspecialchars($p['PRECIO']); ?></div>
                                    <div style="color:#1f7c83;margin-top:6px;font-weight:600;">
                                        <span class="name-text"><?php echo htmlspecialchars($p['NOM_PRO']); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Añadido: botón flotante "VER" en la esquina inferior derecha -->
                            <div class="view-btn" data-product-id="<?php echo htmlspecialchars($p['ID_PRO']); ?>"
                                style="position:absolute;right:10px;bottom:10px;">
                                <button class="view-cta" title="Ver" aria-label="Ver">
                                    <span class="label">VER</span>
                                    <!-- ícono simple (lupa) -->
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M21 21l-4.35-4.35" stroke="#fff" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <circle cx="11" cy="11" r="6" stroke="#fff" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div id="noProducts" class="no-results" style="grid-column: 1 / -1;">No hay productos para mostrar.
                    </div>
                <?php endif; ?>
            </div>

            <div id="noResults" class="no-results" style="display:none;">No se encontraron productos.</div>
        </div>

        <!-- Añadidos: estilos para el botón y el modal -->
        <style>
            /* botón "VER" con etiqueta que se desliza hacia la izquierda */
            .view-btn {
                pointer-events: auto;
            }

            .view-cta {
                display: flex;
                align-items: center;
                gap: 8px;
                height: 40px;
                padding: 8px;
                border-radius: 999px;
                background: #176d7a;
                border: 0;
                color: #fff;
                cursor: pointer;
                box-shadow: 0 6px 18px rgba(23, 109, 122, 0.14);
                transition: transform .12s ease, background .12s, padding .12s;
                overflow: hidden;
                white-space: nowrap;
            }

            .view-cta .label {
                display: inline-block;
                font-weight: 700;
                margin-right: 6px;
                transform-origin: right center;
                transition: transform .18s ease, opacity .18s ease;
                opacity: 0;
                padding-right: 2px;
            }

            .view-btn:hover .view-cta .label {
                opacity: 1;
                transform: translateX(0);
            }

            .view-cta svg {
                flex: 0 0 18px;
            }

            /* Modal/emergente */
            .vp-overlay {
                position: fixed;
                inset: 0;
                background: rgba(2, 9, 11, 0.45);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 2200;
                padding: 18px;
            }

            .vp-modal {
                width: 100%;
                max-width: 880px;
                border-radius: 12px;
                background: #fff;
                overflow: hidden;
                display: flex;
                gap: 12px;
                box-shadow: 0 20px 60px rgba(10, 35, 46, 0.25);
                transform: translateY(14px);
                opacity: 0;
                transition: opacity .14s ease, transform .18s ease;
            }

            .vp-modal.show {
                transform: translateY(0);
                opacity: 1;
            }

            .vp-left {
                width: 48%;
                min-width: 260px;
                background: #f9fbfc;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 18px;
            }

            .vp-left img {
                width: 100%;
                height: 100%;
                max-height: 420px;
                object-fit: contain;
                border-radius: 8px;
            }

            .vp-right {
                padding: 20px;
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .vp-title {
                font-size: 1.35rem;
                font-weight: 800;
                color: #0f6a6f;
            }

            .vp-desc {
                color: #345c5f;
                line-height: 1.45;
                flex: 1;
            }

            .vp-price {
                font-size: 1.15rem;
                font-weight: 900;
                color: #176d7a;
            }

            .vp-actions {
                display: flex;
                gap: 10px;
                align-items: center;
            }

            .vp-btn {
                padding: 10px 14px;
                border-radius: 8px;
                border: 0;
                cursor: pointer;
                font-weight: 700;
            }

            .vp-btn.close {
                background: #f1f6f7;
                color: #123;
            }

            .vp-btn.chat {
                background: #176d7a;
                color: #fff;
                box-shadow: 0 8px 22px rgba(23, 109, 122, 0.12);
            }

            @media(max-width:720px) {
                .vp-modal {
                    flex-direction: column;
                }

                .vp-left {
                    width: 100%;
                }
            }
        </style>

        <!-- Modal HTML -->
        <div id="vpOverlay" class="vp-overlay" aria-hidden="true" role="dialog" aria-label="Ver producto">
            <div id="vpModal" class="vp-modal" role="document" aria-modal="true">
                <div class="vp-left">
                    <img id="vpImage" src="https://via.placeholder.com/420x300.png?text=Producto" alt="Imagen producto">
                </div>
                <div class="vp-right">
                    <div>
                        <div id="vpName" class="vp-title">Nombre producto</div>
                        <div id="vpPrice" class="vp-price">$0.00</div>
                    </div>
                    <div id="vpDesc" class="vp-desc">Detalles del producto...</div>

                    <div class="vp-actions">
                        <button id="vpChatBtn" class="vp-btn chat">Contactar Vendedor</button>
                        <button id="vpCloseBtn" class="vp-btn close">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat eliminado: todo el código JS/CSS relacionado con chat fue quitado -->

        <script>
            // El script de búsqueda se mantiene igual, ya que opera sobre el DOM existente.
            function debounce(fn, delay) { let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); }; }

            (function () {
                const input = document.getElementById('productSearch');
                const grid = document.getElementById('productsGrid');
                const cards = () => Array.from(grid.querySelectorAll('.product-card'));
                const countEl = document.getElementById('searchCount');
                const noRes = document.getElementById('noResults');
                const noProd = document.getElementById('noProducts');

                function escapeHtml(s) {
                    return s.replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
                }

                function highlight(text, q) {
                    if (!q) return escapeHtml(text);
                    const idx = text.toLowerCase().indexOf(q.toLowerCase());
                    if (idx === -1) return escapeHtml(text);
                    const before = escapeHtml(text.slice(0, idx));
                    const match = escapeHtml(text.slice(idx, idx + q.length));
                    const after = escapeHtml(text.slice(idx + q.length));
                    return `${before}<span class="highlight">${match}</span>${after}`;
                }

                function filterOnce() {
                    const q = (input.value || '').trim();
                    let visible = 0;

                    if (noProd) return; // No hacer nada si no hay productos desde el inicio

                    cards().forEach(card => {
                        const name = card.getAttribute('data-name') || '';
                        const price = card.getAttribute('data-price') || '';
                        const matches = q === '' || name.indexOf(q.toLowerCase()) !== -1 || price.indexOf(q) !== -1;
                        if (matches) {
                            card.style.display = '';
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0px)';
                            const nameSpan = card.querySelector('.name-text');
                            if (nameSpan) {
                                nameSpan.innerHTML = highlight(nameSpan.textContent, q);
                            }
                            visible++;
                        } else {
                            card.style.opacity = '0';
                            card.style.transform = 'translateY(6px)';
                            setTimeout(() => { if (!card.style.opacity || card.style.opacity === '0') card.style.display = 'none'; }, 120);
                        }
                    });

                    if (q === '') {
                        countEl.textContent = 'Todos';
                    } else {
                        countEl.textContent = visible + (visible === 1 ? ' encontrado' : ' encontrados');
                    }
                    noRes.style.display = visible === 0 ? 'block' : 'none';
                }

                input.addEventListener('input', debounce(filterOnce, 160));
                filterOnce();

                input.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); filterOnce(); });
            })();

            // Funcionalidad para ver producto y abrir chat
            (function () {
                // helpers
                function $(sel, el = document) { return el.querySelector(sel); }
                function $all(sel, el = document) { return Array.from(el.querySelectorAll(sel)); }

                const overlay = document.getElementById('vpOverlay');
                const modal = document.getElementById('vpModal');
                const img = document.getElementById('vpImage');
                const nameEl = document.getElementById('vpName');
                const priceEl = document.getElementById('vpPrice');
                const descEl = document.getElementById('vpDesc');
                const closeBtn = document.getElementById('vpCloseBtn');

                // abrir modal con información básica desde la tarjeta o con fetch de detalles
                async function openProductModal(productId, cardEl) {
                    // mostrar carga mínima
                    nameEl.textContent = 'Cargando...';
                    priceEl.textContent = '';
                    descEl.textContent = '';
                    img.src = 'https://via.placeholder.com/420x300.png?text=Cargando';

                    overlay.style.display = 'flex';
                    setTimeout(() => modal.classList.add('show'), 8);

                    // pedir detalles al servidor
                    try {
                        const form = new URLSearchParams();
                        form.set('action', 'get_product');
                        form.set('id', String(productId));
                        const res = await fetch(window.location.href, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: form.toString()
                        });
                        const data = await res.json();
                        if (data && data.ok && data.product) {
                            const p = data.product;
                            img.src = p.imagen || img.src;
                            img.alt = p.NOM_PRO || 'Producto';
                            nameEl.textContent = p.NOM_PRO || 'Sin nombre';
                            priceEl.textContent = (p.PRECIO !== null && p.PRECIO !== undefined) ? ('$' + p.PRECIO) : '';
                            descEl.textContent = p.DESCRIPCION ? p.DESCRIPCION : 'Sin más detalles.';

                            const chatBtn = document.getElementById('vpChatBtn');
                            if (chatBtn) {
                                chatBtn.dataset.productId = p.ID_PRO;
                                chatBtn.dataset.ownerId = p.owner_id;
                                // Ocultar si es mi propio producto (opcional)
                                if (p.owner_id == <?php echo isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 0; ?>) {
                                    chatBtn.style.display = 'none';
                                } else {
                                    chatBtn.style.display = 'flex';
                                }
                            }
                        } else {
                            nameEl.textContent = 'Producto no encontrado';
                            descEl.textContent = data && data.error ? data.error : 'No se pudieron obtener los detalles.';
                            img.src = 'https://via.placeholder.com/420x300.png?text=Sin+Imagen';
                        }
                    } catch (err) {
                        nameEl.textContent = 'Error';
                        descEl.textContent = 'No se pudo conectar al servidor.';
                    }
                }

                function closeModal() {
                    modal.classList.remove('show');
                    setTimeout(() => overlay.style.display = 'none', 160);
                }

                // Delegated clicks en botones "VER"
                document.body.addEventListener('click', function (e) {
                    const btn = e.target.closest && e.target.closest('.view-cta');
                    if (!btn) return;
                    const container = btn.closest('.view-btn');
                    const card = btn.closest('.product-card');
                    const productId = container ? container.dataset.productId || card?.dataset?.id : card?.dataset?.id;
                    if (!productId) return;
                    openProductModal(productId, card);
                });

                // Cerrar
                closeBtn.addEventListener('click', closeModal);
                overlay.addEventListener('click', function (e) { if (e.target === overlay) closeModal(); });
                document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });

                // Inicial: dejar el input de búsqueda en su estado anterior
            })();

            // Lógica del botón de chat
            (function () {
                const chatBtn = document.getElementById('vpChatBtn');
                if (chatBtn) {
                    chatBtn.addEventListener('click', async function () {
                        const productId = chatBtn.dataset.productId;
                        const ownerId = chatBtn.dataset.ownerId;

                        if (!productId || !ownerId) {
                            alert('Error: Información de producto incompleta.');
                            return;
                        }

                        // Llamar al backend para iniciar chat
                        try {
                            const formData = new FormData();
                            formData.append('id_producto', productId);
                            formData.append('id_vendedor', ownerId);

                            const res = await fetch('/Proyecto-Modelamiento/AppModel/USUARIO/chat_api.php?action=iniciar_chat', {
                                method: 'POST',
                                body: formData
                            });
                            const data = await res.json();

                            if (data.success) {
                                // Redirigir al chat
                                window.location.href = `chat.php?id_chat=${data.id_chat}`;
                            } else {
                                alert(data.error || 'No se pudo iniciar el chat.');
                            }
                        } catch (e) {
                            console.error(e);
                            alert('Error de conexión: ' + e.message);
                        }
                    });
                }
            })();
        </script>

        <!-- ...existing rest of file ... -->
    </body>

</html>