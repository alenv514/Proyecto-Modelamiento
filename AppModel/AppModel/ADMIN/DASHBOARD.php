<?php
session_start();
// ...validación de usuario admin si lo deseas...
?>
<!DOCTYPE html>
<html>
<head>
    <title>ShopMate - Admin</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="topbar">
        <div class="logo">ShopMate</div>
        <input class="search" type="text" placeholder="Search for products...">
        <div class="top-icons">
            <span class="icon">&#128100;</span>
            <span class="icon">&#128722;</span>
            <span class="icon">&#9776;</span>
        </div>
    </header>
    <div class="main-content">
        <aside class="sidebar">
            <ul>
                <li class="active"><span>&#128200;</span> Inicio</li>
                <li><span>&#128722;</span> Gestionar Usuarios</li>
                <li><span>&#128722;</span> Revisar Reportes</li>
                <li><span>&#128179;</span> Revisar Pedidos</li>
                <li><span>&#128202;</span> Revisar Apelaciones</li>
                <li><span>&#128202;</span> Revisar Estadisticas</li>
            </ul>
        </aside>
        <section class="panel">
            <div class="form-section">
                <h2>Publicar Nuevo Producto</h2>
                <form>
                    <label>Product Name</label>
                    <input type="text" placeholder="Nombre del producto">
                    <label>Description</label>
                    <textarea placeholder="Descripción"></textarea>
                    <label>Price $</label>
                    <input type="number" placeholder="Precio">
                    <label>Quantity in Stock</label>
                    <input type="number" placeholder="Cantidad">
                    <label>Category</label>
                    <select>
                        <option>Electronics</option>
                        <option>Home</option>
                        <option>Fashion</option>
                    </select>
                    <div class="form-buttons">
                        <button type="submit" class="publish">Publish Product</button>
                        <button type="button" class="draft">Save Draft</button>
                    </div>
                </form>
            </div>
            <div class="image-section">
                <div class="upload-box">
                    <span class="cloud">&#9729;</span>
                    <p>Drag & drop image or click to upload</p>
                </div>
            </div>
            <div class="variants-section">
                <h3>Product Variants</h3>
                <table>
                    <tr>
                        <th>Option Name</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                    <tr>
                        <td>Color</td>
                        <td>2</td>
                        <td>Shipped</td>
                    </tr>
                    <tr>
                        <td>Size</td>
                        <td>5</td>
                        <td>Shipped</td>
                    </tr>
                    <tr>
                        <td>Additional Images</td>
                        <td>8</td>
                        <td>Shipped</td>
                    </tr>
                </table>
            </div>
        </section>
    </div>
</body>
</html>
