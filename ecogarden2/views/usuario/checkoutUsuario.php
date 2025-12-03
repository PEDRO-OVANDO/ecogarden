<?php
session_start();
require_once '../../config/database.php';

// Verificar que el usuario esté logueado y tenga items en el carrito
if (!isset($_SESSION['loggedin']) || empty($_SESSION['carrito'])) {
    header("Location: ../clientes/login.php");
    exit;
}
$carrito = $_SESSION['carrito'];

$total = 0;
foreach ($carrito as $item) {
    $total += $item['precio'] * $item['cantidad'];
}

//items para el header
$total_items = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $total_items += $item['cantidad'];
    }
}

// En tu proceso de checkout
if (isset($_SESSION['cupon_aplicado'])) {
    $cupon_id = $_SESSION['cupon_aplicado']['cupon_id'];
    $descuento = $_SESSION['cupon_aplicado']['descuento'];
    
    $total -= $descuento;
    // Registrar uso del cupón
    
    
    // Guardar en el pedido
    $sql = "INSERT INTO pedidos (..., cupon_id, descuento_aplicado) 
            VALUES (..., $cupon_id, $descuento)";
    
    // Limpiar cupón de la sesión
    unset($_SESSION['cupon_aplicado']);
}

$error = '';
$success = '';

//obtener datos del cliente
$cliente_id = $_SESSION['usuario_id'];
$sql_cliente = "SELECT * FROM clientes WHERE id = $cliente_id";
$result_cliente = mysqli_query($conexion, $sql_cliente);
$cliente = mysqli_fetch_assoc($result_cliente);

//procesar el pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $direccion_envio = trim($_POST['direccion_envio']);
    $telefono = trim($_POST['telefono']);
    $notas = trim($_POST['notas']);
    
    if (empty($direccion_envio)) {
        $error = 'La dirección de envío es obligatoria.';
    } else {
        //iniciar transaccion
        mysqli_begin_transaction($conexion);
        
        try {
            //calcular total
            $total = 0;
            foreach ($_SESSION['carrito'] as $item) {
                $total += $item['precio'] * $item['cantidad'];
            }
            
            //1.crear pedido
            $sql_pedido = "INSERT INTO pedidos (cliente_id, total, direccion_envio, estado) 
                VALUES ($cliente_id, $total, '$direccion_envio', 'pendiente')";
            mysqli_query($conexion, $sql_pedido);
            $pedido_id = mysqli_insert_id($conexion);
            
            //2.crear detalles del pedido y actualizar stock
            foreach ($_SESSION['carrito'] as $item) {
                $producto_id = $item['id'];
                $cantidad = $item['cantidad'];
                $precio_unitario = $item['precio'];
                $subtotal = $precio_unitario * $cantidad;
                
                //insertar detalle
                $sql_detalle = "INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario, subtotal) 
                    VALUES ($pedido_id, $producto_id, $cantidad, $precio_unitario, $subtotal)";
                mysqli_query($conexion, $sql_detalle);
                
                //actualizar stock
                $sql_stock = "UPDATE productos SET stock = stock - $cantidad WHERE id = $producto_id";
                mysqli_query($conexion, $sql_stock);
                
                //registrar movimiento en inventario
                $sql_inventario = "INSERT INTO inventario (producto_id, movimiento, cantidad, motivo) 
                    VALUES ($producto_id, 'salida', $cantidad, 'venta_pedido_$pedido_id')";
                mysqli_query($conexion, $sql_inventario);
            }
            
            //3.confirmar transacción
            mysqli_commit($conexion);
            
            //4.limpiar carrito y mostrar éxito
            $_SESSION['carrito'] = [];
            $success = "¡Pedido #$pedido_id realizado con éxito!";
            
        } catch (Exception $e) {
            //revertir transaccion en caso de error
            mysqli_rollback($conexion);
            $error = "Error al procesar el pedido: " . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - EcoGarden</title>
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
        .checkout-header {
            margin-top: 80px;
            padding: 2rem 0;
        }
        
        .page-title {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        /*layout*/
        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        /*formulario*/
        .form-section {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .section-title {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        /*detalles*/
        .summary-section {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .summary-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
            padding: 1rem 0;
            border-top: 2px solid #eee;
            margin-top: 1rem;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            width: 100%;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background: #23421f;
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
        
        /*alerts*/
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /*cart items*/
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .item-info h4 {
            margin-bottom: 0.25rem;
        }
        
        .item-details {
            color: #666;
            font-size: 0.9rem;
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
    <link rel="stylesheet" href="../css/responsiveCheckoutU.css">
</head>
<body>
    <!--header-->
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
                    <a href="catalogoUsuario.php" class="nav-link">Productos</a>
                    <a href="carritoUsuario.php" class="nav-link" style="color: var(--primary-color);">
                        <i class="fas fa-shopping-cart"></i>
                        Carrito <span class="cart-count"><?php echo $total_items; ?></span>
                    </a>
                    <a href="../clientes/perfil.php" class="nav-link">Mi Cuenta</a>
                    <a href="cuponesUsuario.php" class="nav-link">Cupones</a>
                    <a href="logout.php" class="nav-link">Cerrar Sesión</a>
                </div>

                <!-- Menú lateral móvil -->
                <div class="mobile-menu" id="mobileMenu">
                    <a href="catalogoUsuario.php">Productos</a>
                    <a href="carritoUsuario.php">Carrito (<?php echo $total_items; ?>)</a>
                    <a href="../clientes/perfil.php">Mi Cuenta</a>
                    <a href="cuponesUsuario.php">Cupones</a>
                    <a href="logout.php">Cerrar Sesión</a>
                </div>
                <div class="menu-overlay" id="menuOverlay"></div>
            </nav>
        </div>
    </header>

    <!--contenido principal-->
    <main class="container">
        <div class="checkout-header">
            <h1 class="page-title">Finalizar Compra</h1>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <div style="margin-top: 1rem;">
                    <a href="../clientes/perfil.php" class="btn btn-primary">Ver Mis Pedidos</a>
                    <a href="catalogoUsuario.php" class="btn btn-outline">Seguir Comprando</a>
                </div>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="checkout-layout">
                <!--formulario-->
                <section class="form-section">
                    <h2 class="section-title">Información de Envío</h2>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label class="form-label" for="nombre">Nombre</label>
                            <input type="text" class="form-control" id="nombre" 
                                value="<?php echo htmlspecialchars($cliente['nombre']); ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" class="form-control" id="email" 
                                value="<?php echo htmlspecialchars($cliente['email']); ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="telefono">Teléfono *</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                value="<?php echo htmlspecialchars($cliente['telefono'] ?? ''); ?>" 
                                required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="direccion_envio">Dirección de Envío *</label>
                            <textarea class="form-control" id="direccion_envio" name="direccion_envio" 
                                placeholder="Calle, número, colonia, ciudad, código postal..." 
                                required><?php echo isset($_POST['direccion_envio']) ? htmlspecialchars($_POST['direccion_envio']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="notas">Notas adicionales (opcional)</label>
                            <textarea class="form-control" id="notas" name="notas" 
                                placeholder="Instrucciones especiales para la entrega..."><?php echo isset($_POST['notas']) ? htmlspecialchars($_POST['notas']) : ''; ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Confirmar Pedido</button>
                    </form>
                </section>

                <!--resumen del pedido-->
                <section class="summary-section">
                    <h2 class="section-title">Resumen del Pedido</h2>
                    
                    <div class="cart-items">
                        <?php foreach ($carrito as $item): ?>
                            <div class="cart-item">
                                <div class="item-info">
                                    <h4><?php echo htmlspecialchars($item['nombre']); ?></h4>
                                    <div class="item-details">
                                        $<?php echo number_format($item['precio'], 2); ?> x <?php echo $item['cantidad']; ?>
                                    </div>
                                </div>
                                <div class="item-subtotal">
                                    $<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <div style="margin-top: 1rem;">
                        <a href="carritoUsuario.php" class="btn btn-outline">Modificar Carrito</a>
                    </div>
                </section>
            </div>
        <?php endif; ?>
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