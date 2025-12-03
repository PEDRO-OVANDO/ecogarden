<?php
session_start();

//inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

require_once '../../config/database.php';

class CarritoController {
    private $conexion;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
    }
    
    public function agregarProducto($producto_id, $cantidad = 1) {
        //verificar que el producto existe y tiene stock
        $sql = "SELECT id, nombre, precio, stock FROM productos WHERE id = $producto_id AND activo = 1";
        $result = mysqli_query($this->conexion, $sql);
        
        if (!$result || mysqli_num_rows($result) == 0) {
            return ["error" => "Producto no encontrado"];
        }
        
        $producto = mysqli_fetch_assoc($result);
        
        if ($producto['stock'] < $cantidad) {
            return ["error" => "Stock insuficiente. Solo hay {$producto['stock']} disponibles"];
        }
        
        //agregar al carrito
        if (isset($_SESSION['carrito'][$producto_id])) {
            $_SESSION['carrito'][$producto_id]['cantidad'] += $cantidad;
        } else {
            $_SESSION['carrito'][$producto_id] = [
                'id' => $producto['id'],
                'nombre' => $producto['nombre'],
                'precio' => $producto['precio'],
                'cantidad' => $cantidad
            ];
        }
        
        return ["success" => "Producto agregado al carrito"];
    }
    
    public function eliminarProducto($producto_id) {
        if (isset($_SESSION['carrito'][$producto_id])) {
            unset($_SESSION['carrito'][$producto_id]);
            return ["success" => "Producto eliminado del carrito"];
        }
        return ["error" => "Producto no encontrado en el carrito"];
    }
    
    public function actualizarCantidad($producto_id, $cantidad) {
        if ($cantidad <= 0) {
            return $this->eliminarProducto($producto_id);
        }
        
        if (isset($_SESSION['carrito'][$producto_id])) {
            $_SESSION['carrito'][$producto_id]['cantidad'] = $cantidad;
            return ["success" => "Cantidad actualizada"];
        }
        return ["error" => "Producto no encontrado en el carrito"];
    }
    
    public function obtenerCarrito() {
        return $_SESSION['carrito'];
    }
    
    public function obtenerTotal() {
        $total = 0;
        foreach ($_SESSION['carrito'] as $item) {
            $total += $item['precio'] * $item['cantidad'];
        }
        return $total;
    }
    
    public function vaciarCarrito() {
        $_SESSION['carrito'] = [];
        return ["success" => "Carrito vaciado"];
    }
    
    public function getCantidadTotal() {
        $total = 0;
        foreach ($_SESSION['carrito'] as $item) {
            $total += $item['cantidad'];
        }
        return $total;
    }
}

class CuponController {
    private $conexion;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
    }
    
    public function validarCupon($codigo, $subtotal) {
        $codigo = mysqli_real_escape_string($this->conexion, $codigo);
        
        $sql = "SELECT * FROM cupones_descuento 
                WHERE codigo = '$codigo' 
                AND activo = 1 
                AND (fecha_inicio IS NULL OR fecha_inicio <= CURDATE())
                AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
                AND (usos_maximos IS NULL OR usos_actuales < usos_maximos)";
        
        $result = mysqli_query($this->conexion, $sql);
        
        if (!$result || mysqli_num_rows($result) == 0) {
            return ['error' => 'Cupón no válido o expirado'];
        }
        
        $cupon = mysqli_fetch_assoc($result);
        
        //verificar minimo de compra
        if ($subtotal < $cupon['min_compra']) {
            $minimo = number_format($cupon['min_compra'], 2);
            return ['error' => "Mínimo de compra: $$minimo"];
        }
        
        return ['success' => true, 'cupon' => $cupon];
    }
    
    public function calcularDescuento($cupon, $subtotal) {
        if ($cupon['tipo'] == 'porcentaje') {
            return $subtotal * ($cupon['valor'] / 100);
        } else { //monto_fijo
            return min($cupon['valor'], $subtotal);
        }
    }
    
    public function registrarUso($cupon_id) {
        $sql = "UPDATE cupones_descuento 
                SET usos_actuales = usos_actuales + 1 
                WHERE id = $cupon_id";
        return mysqli_query($this->conexion, $sql);
    }
}

$carritoController = new CarritoController($conexion);
$cuponController = new CuponController($conexion);
require_once '../../config/envio.php';
$envioController = new EnvioController($conexion);
$mensaje = '';

//procesar acciones del carrito desde POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $producto_id = $_POST['producto_id'] ?? '';
    
    switch ($action) {
        case 'actualizar':
            $cantidad = intval($_POST['cantidad']);
            $resultado = $carritoController->actualizarCantidad($producto_id, $cantidad);
            break;
        case 'eliminar':
            $resultado = $carritoController->eliminarProducto($producto_id);
            break;
        case 'vaciar':
            $resultado = $carritoController->vaciarCarrito();
            //si se vacia el carrito, quitar el cupon 
            unset($_SESSION['cupon_aplicado']);
            break;
        case 'aplicar_cupon':
            $codigo_cupon = trim($_POST['codigo_cupon'] ?? '');
            if (!empty($codigo_cupon)) {
                $subtotal = $carritoController->obtenerTotal();
                $resultado = $cuponController->validarCupon($codigo_cupon, $subtotal);
                if (isset($resultado['success'])) {
                    $cupon = $resultado['cupon'];
                    $descuento = $cuponController->calcularDescuento($cupon, $subtotal);
                    
                    $_SESSION['cupon_aplicado'] = [
                        'cupon_id' => $cupon['id'],
                        'codigo' => $cupon['codigo'],
                        'descuento' => $descuento,
                        'tipo' => $cupon['tipo'],
                        'valor' => $cupon['valor'],
                        'mensaje' => generarMensajeDescuento($cupon, $descuento)
                    ];
                    $mensaje = "¡Cupón aplicado correctamente!";
                } else {
                    $mensaje = $resultado['error'];
                }
            } else {
                $mensaje = "Por favor ingresa un código de cupón";
            }
            break;
        case 'remover_cupon':
            unset($_SESSION['cupon_aplicado']);
            $mensaje = "Cupón removido";
            break;
    }
    
    if (isset($resultado['success'])) {
        $mensaje = $resultado['success'];
    }
}

//procesar agregar producto desde GET
if (isset($_GET['agregar'])) {
    $producto_id = intval($_GET['agregar']);
    $cantidad = isset($_GET['cantidad']) ? intval($_GET['cantidad']) : 1;
    $resultado = $carritoController->agregarProducto($producto_id, $cantidad);
    
    if (isset($resultado['success'])) {
        $mensaje = $resultado['success'];
        //redirigir para evitar reenvio del formulario
        header("Location: carritoUsuario.php");
        exit;
    } else {
        $mensaje = $resultado['error'];
    }
}

//generar mensaje de descuento
function generarMensajeDescuento($cupon, $descuento) {
    if ($cupon['tipo'] == 'porcentaje') {
        return "{$cupon['valor']}% de descuento - Ahorras: $" . number_format($descuento, 2);
    } else {
        return "Descuento de $" . number_format($cupon['valor'], 2) . " - Ahorras: $" . number_format($descuento, 2);
    }
}

$carrito = $carritoController->obtenerCarrito();
$subtotal = $carritoController->obtenerTotal();
$total_items = $carritoController->getCantidadTotal();
$tarifa_envio = $envioController->calcularEnvio($subtotal);
$costo_envio = $tarifa_envio['costo']; 

//calcular total con descuento y envio 
$descuento = 0;
if (isset($_SESSION['cupon_aplicado'])) {
    $descuento = $_SESSION['cupon_aplicado']['descuento'];
}

$total_final = max(0, $subtotal - $descuento + $costo_envio);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - EcoGarden</title>
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
        
        /*header*/
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
        .carrito-header {
            margin-top: 80px;
            padding: 2rem 0;
        }
        
        .page-title {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        /*alertas*/
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
        
        /*carrito*/
        .carrito-section {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .carrito-vacio {
            text-align: center;
            padding: 3rem;
        }
        
        .carrito-items {
            margin-bottom: 2rem;
        }
        
        .carrito-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            gap: 1rem;
        }
        
        .item-info h4 {
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }
        
        .item-precio {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .item-cantidad {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .cantidad-input {
            width: 60px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .item-subtotal {
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .carrito-total {
            text-align: right;
            padding: 1.5rem;
            border-top: 2px solid #eee;
        }
        
        .total-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .total-monto {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .descuento-text {
            color: var(--accent-color);
            font-weight: 600;
        }
        
        .carrito-acciones {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
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
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background: #23421f;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: var(--white);
        }
        
        .btn-danger {
            background: #dc3545;
            color: var(--white);
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
        
        .btn-cupon {
            background: var(--accent-color);
            color: var(--white);
        }
        
        .btn-cupon:hover {
            background: #e55a2b;
        }
        
        .login-required {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1rem;
            text-align: center;
        }
        
        /*cupones */
        .cupon-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
        }
        
        .cupon-aplicado {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .cupon-form {
            display: flex;
            gap: 0.5rem;
        }
        
        .cupon-input {
            flex: 1;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 1rem;
        }

        /*envio*/
        .envio-section {
            margin: 1.5rem 0; 
            padding: 1.5rem; 
            background: #f8f9fa; 
            border-radius: 8px; 
            border-left: 4px solid var(--secondary-color);"
        }

        /* ===== MENU HAMBURGUESA SOLO PARA MOVIL ===== */
        .hamburger {
            display: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: var(--primary-color);
            background: none;
            border: none;
            padding: 0.5rem;
            z-index: 2000;
            position: relative;
        }

        /* Reestructuración del nav para móvil */
        @media (max-width: 768px) {
            .nav {
                position: relative; /* Necesario para posicionar el hamburguesa */
            }
            
            .nav-links {
                display: none;
            }
            
            .hamburger {
                display: block;
                position: absolute;
                right: 0;
                top: 50%;
                transform: translateY(-50%);
            }
            
            /* Ajustar el logo para que no se superponga */
            .logo {
                margin-right: 3rem; /* Espacio para el botón hamburguesa */
            }
        }

        /* sidebar - menú móvil */
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
            padding: 0.75rem 1rem;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .mobile-menu a:hover {
            background: var(--light-bg);
        }

        .mobile-menu.open {
            right: 0;
        }

        /* fondo oscuro */
        .menu-overlay {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            z-index: 1500;
        }

        .menu-overlay.show {
            display: block;
        }
    </style>
    <link rel="stylesheet" href="../css/responsiveCarritoU.css">
</head>
<body>
    <!-- header -->
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
        <div class="carrito-header">
            <h1 class="page-title">Carrito de Compras</h1>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert <?php echo strpos($mensaje, 'error') !== false ? 'alert-error' : 'alert-success'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <section class="carrito-section">
            <?php if (empty($carrito)): ?>
                <div class="carrito-vacio">
                    <h3>Tu carrito está vacío</h3>
                    <p>¡Descubre nuestros productos para tu jardín!</p>
                    <a href="catalogoUsuario.php" class="btn btn-primary">Explorar Catálogo</a>
                </div>
            <?php else: ?>
                <div class="carrito-items">
                    <?php foreach ($carrito as $item): ?>
                        <div class="carrito-item">
                            <div class="item-info">
                                <h4><?php echo htmlspecialchars($item['nombre']); ?></h4>
                                <p class="item-precio">$<?php echo number_format($item['precio'], 2); ?> c/u</p>
                            </div>
                            
                            <div class="item-cantidad">
                                <form method="POST" style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="hidden" name="action" value="actualizar">
                                    <input type="hidden" name="producto_id" value="<?php echo $item['id']; ?>">
                                    <label>Cantidad:</label>
                                    <input type="number" name="cantidad" value="<?php echo $item['cantidad']; ?>" 
                                        min="1" class="cantidad-input">
                                    <button type="submit" class="btn btn-outline">Actualizar</button>
                                </form>
                            </div>
                            
                            <div class="item-subtotal">
                                $<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="eliminar">
                                <input type="hidden" name="producto_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn btn-danger">Eliminar</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!--cupones-->
                <div class="cupon-section">
                    <h4 style="margin-bottom: 1rem; color: var(--primary-color);">¿Tienes un cupón de descuento?</h4>
                    
                    <?php if (isset($_SESSION['cupon_aplicado'])): ?>
                        <div class="cupon-aplicado">
                            <strong>¡Cupón aplicado!</strong> 
                            <?php echo $_SESSION['cupon_aplicado']['mensaje']; ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="remover_cupon">
                                <button type="submit" style="background: none; border: none; color: #721c24; text-decoration: underline; cursor: pointer; margin-left: 10px;">
                                    No quiero usar el descuento
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="cupon-form">
                        <input type="hidden" name="action" value="aplicar_cupon">
                        <input type="text" name="codigo_cupon" placeholder="Ingresa tu código" 
                            class="cupon-input"
                            value="<?php echo $_POST['codigo_cupon'] ?? ''; ?>">
                        <button type="submit" class="btn btn-cupon">Aplicar Cupón</button>
                    </form>
                </div>

                <!--envio-->
                <div class="envio-section">
                    <h4 style="margin-bottom: 1rem; color: var(--primary-color);">
                        <i class="fas fa-truck"></i> Información de Envío
                    </h4>
                    
                    <div class="info-envio" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div class="info-item">
                            <strong>Método de envío:</strong><br>
                            <span style="color: var(--primary-color);"><?php echo $tarifa_envio['nombre']; ?></span>
                        </div>
                        
                        <div class="info-item">
                            <strong>Costo de envío:</strong><br>
                            <span style="font-size: 1.2rem; font-weight: bold; color: <?php echo $costo_envio == 0 ? 'var(--secondary-color)' : 'var(--text-color)'; ?>;">
                                <?php echo $costo_envio == 0 ? 'GRATIS' : '$' . number_format($costo_envio, 2); ?>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <strong>Tiempo de entrega:</strong><br>
                            <span><?php echo $tarifa_envio['dias_entrega']; ?> días hábiles</span>
                        </div>
                    </div>
                    
                    <?php if ($costo_envio > 0): ?>
                        <div style="margin-top: 1rem; padding: 0.75rem; background: #e7f3ff; border-radius: 6px;">
                            <small>
                                <strong>Sugerencia: </strong> 
                                Agrega productos por $<?php echo number_format(200 - $subtotal, 2); ?> más para envío GRATIS
                            </small>
                        </div>
                    <?php endif; ?>
                </div>

                <!--total con descuento-->
                <div class="carrito-total">
                    <div class="total-line">
                        <strong>Subtotal:</strong> 
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    
                    <?php if (isset($_SESSION['cupon_aplicado'])): ?>
                    <div class="total-line descuento-text">
                        <strong>Descuento:</strong> 
                        <span>-$<?php echo number_format($descuento, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="total-line">
                        <strong>Envío (<?php echo $tarifa_envio['nombre']; ?>):</strong> 
                        <span style="color: <?php echo $costo_envio == 0 ? 'var(--secondary-color)' : 'var(--text-color)'; ?>;">
                            <?php echo $costo_envio == 0 ? 'GRATIS' : '$' . number_format($costo_envio, 2); ?>
                        </span>
                    </div>
                    
                    <div class="total-line" style="border-top: 2px solid #eee; padding-top: 1rem; margin-top: 0.5rem;">
                        <strong>Total:</strong> 
                        <span class="total-monto">$<?php echo number_format($total_final, 2); ?></span>
                    </div>
                </div>
                
                <div class="carrito-acciones">
                    <div>
                        <form method="POST">
                            <input type="hidden" name="action" value="vaciar">
                            <button type="submit" class="btn btn-secondary">Vaciar Carrito</button>
                        </form>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <a href="catalogoUsuario.php" class="btn btn-outline">Seguir Comprando</a>
                        
                        <?php if (isset($_SESSION['loggedin'])): ?>
                            <a href="checkoutUsuario.php" class="btn btn-primary">Proceder al Pago</a>
                        <?php else: ?>
                            <a href="../clientes/login.php?redirect=checkout" class="btn btn-primary">
                                Iniciar Sesión para Comprar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!isset($_SESSION['loggedin'])): ?>
                    <div class="login-required">
                        <strong>¿Primera vez comprando?</strong><br>
                        Necesitas iniciar sesión para completar tu compra. 
                        <a href="../clientes/registro.php">Regístrate aquí</a> si no tienes cuenta.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
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