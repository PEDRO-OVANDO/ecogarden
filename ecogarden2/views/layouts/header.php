<?php
// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

//calcular total_items para el header
$total_items = 0;
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        if (isset($item['cantidad'])) {
            $total_items += $item['cantidad'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        .header {
            background: var(--white);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

        .cart-count {
            background: var(--accent-color);
            color: var(--white);
            border-radius: 50%;
            padding: 0.2rem 0.5rem;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-link {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-link:hover {
            color: var(--primary-color);
        }

        /* ===== MENU HAMBURGUESA SOLO PARA MOVIL ===== */
        .hamburger {
            display: none;
            font-size: 2rem;
            cursor: pointer;
            color: var(--primary-color);
        }

        /* sidebar */
        .mobile-menu {
            position: fixed;
            top: 0;
            right: -260px;
            width: 260px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0,0,0,0.2);
            padding: 2rem 1rem;
            transition: 0.3s;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            z-index: 2000;
        }

        .mobile-menu a {
            font-size: 1.1rem;
            text-decoration: none;
            color: var(--text-color);
            font-weight: 600;
        }

        .mobile-menu.open {
            right: 0;
        }

        /* fondo oscuro */
        .menu-overlay {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0; left: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            z-index: 1500;
        }

        .menu-overlay.show {
            display: block;
        }

        /* Mostrar hamburguesa y ocultar menú normal en móvil */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            .hamburger {
                display: block;
            }
        }
    </style>
    <link rel="stylesheet" href="../css/responsiveHeader.css">
</head>
<body>
    <header class="header">
        <nav class="nav container">
            <div class="logo">
                <i class="fas fa-leaf"></i>
                <span>EcoGarden</span>
            </div>

            <!-- Botón Hamburguesa (solo móvil) -->
            <div class="hamburger" id="hamburgerBtn">
                <i class="fas fa-bars"></i>
            </div>

            <div class="nav-links">
                <a href="index.php" class="nav-link">Inicio</a>
                <a href="../views/productos/catalogo.php" class="nav-link">Productos</a>
                <a href="../views/pedidos/carrito.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    Carrito <span class="cart-count"><?php echo $total_items; ?></span>
                </a>
                <a href="../views/clientes/login.php" class="nav-link">Ingresar</a>
            </div>

            <!-- Menú lateral móvil -->
            <div class="mobile-menu" id="mobileMenu">
                <a href="index.php" class="nav-link" class="nav-link">Inicio</a>
                <a href="../views/productos/catalogo.php" class="nav-link" style="color: var(--primary-color);">Productos</a>
                <a  href="../views/pedidos/carrito.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    Carrito <span class="cart-count"><?php echo $total_items; ?></span>
                </a>
                <a href="../views/clientes/login.php" class="nav-link">Ingresar</a>
            </div>
            <div class="menu-overlay" id="menuOverlay"></div>
        </nav>
    </header>
    <script>
    const hamburger = document.getElementById("hamburgerBtn");
    const mobileMenu = document.getElementById("mobileMenu");
    const overlay = document.getElementById("menuOverlay");

    hamburger.addEventListener("click", () => {
        mobileMenu.classList.add("open");
        overlay.classList.add("show");
    });

    overlay.addEventListener("click", () => {
        mobileMenu.classList.remove("open");
        overlay.classList.remove("show");
    });
    </script>

</body>
</html>


