<?php
session_start();
// ...aquí podrías validar que el usuario sea comprador...
?>
<!DOCTYPE html>
<html>
<head>
    <title>ShopMate - Comprador</title>
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
            <h3>Filter by</h3>
            <div class="filter-group">
                <label>Price</label>
                <input type="range" min="0" max="2000">
                <div class="price-labels">
                    <span>$ Min</span>
                    <span>$ Max</span>
                </div>
            </div>
            <div class="filter-group">
                <label>Screen Size</label>
                <div><input type="radio" name="screen" id="s1"><label for="s1">Up to 13 in</label></div>
                <div><input type="radio" name="screen" id="s2"><label for="s2">14 in – 15.9 in</label></div>
                <div><input type="radio" name="screen" id="s3"><label for="s3">16 in – 17.9 in</label></div>
            </div>
            <div class="filter-group">
                <label>Smartphone</label>
                <div><input type="radio" name="smartphone" id="sp1"><label for="sp1">$449</label></div>
            </div>
            <div class="filter-group">
                <label>Sponsored</label>
                <div><input type="radio" name="sponsored" id="sphost"><label for="sphost">Sunhost</label></div>
            </div>
        </aside>
        <section class="products">
            <!-- Aquí aparecerán los productos agregados por los usuarios vendedores -->
        </section>
    </div>
</body>
</html>
