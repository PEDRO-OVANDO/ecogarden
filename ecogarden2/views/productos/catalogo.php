<?php
session_start();
//inicializar carrito si no existe para la variable $total_items
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

//calcular total_items para el header
$total_items = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total_items += $item['cantidad'];
}
require_once '../../config/database.php';

//crear controlador de productos
class ProductoController {
    private $conexion;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
    }
    
    public function listarProductos($categoria = null) {
        $sql = "SELECT p.*, c.nombre as categoria_nombre 
                FROM productos p 
                LEFT JOIN categorias c ON p.categoria_id = c.id 
                WHERE p.activo = 1";
        
        if ($categoria) {
            $categoria = mysqli_real_escape_string($this->conexion, $categoria);
            $sql .= " AND c.nombre = '$categoria'";
        }
        
        $sql .= " ORDER BY p.nombre";
        
        $result = mysqli_query($this->conexion, $sql);
        
        if (!$result) {
            return [];
        }
        
        $productos = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $productos[] = $row;
        }
        
        return $productos;
    }
    
    public function obtenerCategorias() {
        $sql = "SELECT * FROM categorias ORDER BY nombre";
        $result = mysqli_query($this->conexion, $sql);
        
        $categorias = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $categorias[] = $row;
        }
        
        return $categorias;
    }
}

$productoController = new ProductoController($conexion);

//obetener parametros de filtro y busqueda
$categoria_filtro = $_GET['categoria'] ?? null;
$busqueda = $_GET['busqueda'] ?? null;

//obtener productos
if ($busqueda) {
    //busqueda (simplificada por ahora)
    $productos = array_filter($productoController->listarProductos(), function($producto) use ($busqueda) {
        return stripos($producto['nombre'], $busqueda) !== false || 
            stripos($producto['descripcion'], $busqueda) !== false;
    });
} else {
    $productos = $productoController->listarProductos($categoria_filtro);
}

$categorias = $productoController->obtenerCategorias();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Productos - EcoGarden</title>
    <style>
        :root {
            --primary-color: #2d5a27;
            --secondary-color: #4CAF50;
            --accent-color: #ff6b35;
            --text-color: #333;
            --light-bg: #f8f9fa;
            --white: #ffffff;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Century Gothic', Arial, sans-serif; background: var(--light-bg);}
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /*header */
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
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            text-decoration: none;
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
        }

        .cart-count {
            background: var(--accent-color);
            color: var(--white);
            border-radius: 50%;
            padding: 0.2rem 0.5rem;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }
        
        /*contenido principal*/
        .catalogo-header {
            margin-top: 80px;
            padding: 2rem 0;
            text-align: center;
        }
        
        .page-title {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        /*filtros*/
        .filtros-section {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .filtros-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1rem;
            align-items: end;
        }
        
        .search-box {
            display: flex;
            gap: 0.5rem;
        }
        
        .search-input {
            flex: 1;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .categorias-filtro {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .categoria-btn {
            padding: 8px 16px;
            background: var(--light-bg);
            border: 2px solid #e9ecef;
            border-radius: 20px;
            text-decoration: none;
            color: var(--text-color);
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .categoria-btn.active,
        .categoria-btn:hover {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background: #23421f;
        }
        
        /*grid y scroll*/
        .productos-scroll {
            max-height: 400px;   
            overflow-y: auto;
            padding-right: 10px; 
        }

        .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            grid-auto-rows: 1fr; /* Esto hace que todas las filas tengan la misma altura */
            gap: 2rem;
            margin-bottom: 3rem;
            align-items: stretch; /* Asegura que todas las cards se estiren igual */
        }

        .producto-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            display: flex; /* Agregar flex */
            flex-direction: column; /* Columna para organizar contenido */
            height: 100%; /* Ocupa toda la altura disponible */
        }

        .producto-card:hover {
            transform: translateY(-5px);
        }

        .producto-imagen {
            width: 100%;
            height: 200px;
            background: var(--light-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 15px; 
            flex-shrink: 0; /* Evita que la imagen se encoja */
        }

        .producto-imagen img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .producto-info {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1; /* Hace que este contenedor crezca para ocupar el espacio */
        }

        .producto-categoria {
            color: var(--primary-color);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .producto-nombre {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .producto-descripcion {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.4;
            flex-grow: 1; /* Hace que la descripción ocupe el espacio disponible */
            min-height: 40px; /* Altura mínima para mantener consistencia */
        }

        .producto-precio {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .producto-stock {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .producto-acciones {
            display: flex;
            gap: 0.5rem;
            margin-top: auto; /* Empuja los botones hacia abajo */
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
            flex: 1;
            text-align: center;
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: var(--white);
        }

        .no-products {
            text-align: center;
            padding: 3rem;
            grid-column: 1 / -1;
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
    <link rel="stylesheet" href="../css/responsiveCatalogoIndex.css">
</head>
<body>
    <!--header -->
    <header class="header">
        <div class="container">
            <nav class="nav">
                <a href="../../public/index.php" class="logo">
                    <i class="fas fa-leaf"></i>
                    <span>EcoGarden</span>
                </a>

                <!-- Botón Hamburguesa (solo móvil) -->
                <div class="hamburger" id="hamburgerBtn">
                    <i class="fas fa-bars"></i>
                </div>

                <div class="nav-links">
                    <a href="../../public/index.php" class="nav-link">Inicio</a>
                    <a href="catalogo.php" class="nav-link" style="color: var(--primary-color);">Productos</a>
                    <a href="../pedidos/carrito.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        Carrito <span class="cart-count"><?php echo $total_items; ?></span>
                    </a>
                    <a href="../clientes/login.php" class="nav-link">Ingresar</a>
                </div>

                <!-- Menú lateral móvil -->
                <div class="mobile-menu" id="mobileMenu">
                    <a href="../../public/index.php" class="nav-link">Inicio</a>
                    <a href="catalogo.php" class="nav-link" style="color: var(--primary-color);">Productos</a>
                    <a href="../pedidos/carrito.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        Carrito <span class="cart-count"><?php echo $total_items; ?></span>
                    </a>
                    <a href="../clientes/login.php" class="nav-link">Ingresar</a>
                </div>
                <div class="menu-overlay" id="menuOverlay"></div>               
            </nav>
        </div>
    </header>

    <!--contenido principal-->
    <main class="container">
        <div class="catalogo-header">
            <h1 class="page-title">Nuestros Productos</h1>
            <p>Descubre todo lo que necesitas para tu jardín urbano</p>
        </div>

        <!--filtros-->
        <section class="filtros-section">
            <div class="filtros-grid">
                <div>
                    <h3 style="margin-bottom: 1rem;">Filtrar por Categoría</h3>
                    <div class="categorias-filtro">
                        <a href="?categoria=" class="categoria-btn <?php echo !$categoria_filtro ? 'active' : ''; ?>">
                            Todas
                        </a>
                        <?php foreach ($categorias as $categoria): ?>
                            <a href="?categoria=<?php echo $categoria['nombre']; ?>" 
                            class="categoria-btn <?php echo $categoria_filtro == $categoria['nombre'] ? 'active' : ''; ?>">
                                <?php echo ucfirst($categoria['nombre']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="search-box">
                    <form method="GET" style="display: flex; gap: 0.5rem; width: 100%;">
                        <input type="text" name="busqueda" class="search-input" 
                            placeholder="Buscar productos..." value="<?php echo htmlspecialchars($busqueda ?? ''); ?>">
                        <button type="submit" class="btn btn-primary">Buscar</button>
                        <?php if ($busqueda): ?>
                            <a href="catalogo.php" class="btn" style="background: #6c757d; color: white;">Limpiar</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </section>

        <!--grid-->
        <section class="productos-scroll">
            <div class="productos-grid">
            <?php if (empty($productos)): ?>
                <div class="no-products">
                    <h3>No se encontraron productos</h3>
                    <p><?php echo $busqueda ? 'Prueba con otros términos de búsqueda.' : 'No hay productos disponibles en esta categoría.'; ?></p>
                    <a href="catalogo.php" class="btn btn-primary">Ver Todos los Productos</a>
                </div>
            <?php else: ?>
                <?php foreach ($productos as $producto): ?>
                    <div class="producto-card">
                        <div class="producto-imagen">
                            <img src="../../uploads/productos/<?php echo $producto['imagen']; ?>" 
                                alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                loading="lazy">
                        </div>

                        <div class="producto-info">
                            <div class="producto-categoria">
                                <?php echo $producto['categoria_nombre'] ?? 'General'; ?>
                            </div>
                            <h3 class="producto-nombre"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                            <p class="producto-descripcion"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                            <div class="producto-precio">$<?php echo number_format($producto['precio'], 2); ?></div>
                            <div class="producto-stock">
                                <?php echo $producto['stock'] > 0 ? "{$producto['stock']} disponibles" : 'Agotado'; ?>
                            </div>
                            <div class="producto-acciones">
                                <?php if ($producto['stock'] > 0): ?>
                                    <a href="../pedidos/carrito.php?agregar=<?php echo $producto['id']; ?>" class="btn btn-primary btn-sm">
                                        Agregar al Carrito
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-sm" style="background: #ccc; color: #666;" disabled>Agotado</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
        </section>
    </main>

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

    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>