<?php
session_start();
require_once '../../config/database.php';

//login
if (!isset($_SESSION['loggedin'])) {
    header("Location: ../clientes/login.php");
    exit;
}

class CuponUsuarioController {
    private $conexion;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
    }
    
    public function obtenerCuponesDisponibles() {
        $sql = "SELECT * FROM cupones_descuento 
                WHERE activo = 1 
                AND (fecha_inicio IS NULL OR fecha_inicio <= CURDATE())
                AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
                AND (usos_maximos IS NULL OR usos_actuales < usos_maximos)
                ORDER BY valor DESC, fecha_fin ASC";
        
        $result = mysqli_query($this->conexion, $sql);
        
        $cupones = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $cupones[] = $row;
            }
        }
        
        return $cupones;
    }
    
    public function obtenerCuponesExpirados() {
        $sql = "SELECT * FROM cupones_descuento 
                WHERE activo = 1 
                AND fecha_fin IS NOT NULL 
                AND fecha_fin < CURDATE()
                ORDER BY fecha_fin DESC";
        
        $result = mysqli_query($this->conexion, $sql);
        
        $cupones = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $cupones[] = $row;
            }
        }
        
        return $cupones;
    }
}

$cuponController = new CuponUsuarioController($conexion);
$cupones_disponibles = $cuponController->obtenerCuponesDisponibles();
$cupones_expirados = $cuponController->obtenerCuponesExpirados();

//items para el header
$total_items = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $total_items += $item['cantidad'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Cupones - EcoGarden</title>
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
        
        /*contenido prinicpal*/
        .cupones-header {
            margin-top: 80px;
            padding: 2rem 0;
        }
        
        .page-title {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .page-subtitle {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        
        /*grid de cupones*/
        .cupones-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}
        
        .cupon-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .cupon-card:hover {
            transform: translateY(-2px);
        }
        
        .cupon-card.expirado {
            border-left-color: #6c757d;
            opacity: 0.7;
        }
        
        .cupon-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .cupon-codigo {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--primary-color);
            background: #e8f5e8;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            border: 2px dashed var(--primary-color);
        }
        
        .cupon-tipo {
            background: var(--accent-color);
            color: var(--white);
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .cupon-valor {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 1rem 0;
        }
        
        .cupon-valor.porcentaje::after {
            content: '%';
            font-size: 1rem;
        }
        
        .cupon-valor.monto::before {
            content: '$';
            font-size: 1rem;
        }
        
        .cupon-descripcion {
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.4;
        }
        
        .cupon-detalles {
            background: var(--light-bg);
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        .cupones-scroll {
            max-height: 300px; 
            overflow-y: auto;
            padding-right: 10px;
        }

        
        .detalle-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .detalle-label {
            color: #666;
        }
        
        .detalle-value {
            font-weight: 600;
        }
        
        .badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-secondary {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .seccion-titulo {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin: 2rem 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light-bg);
        }
        
        .no-cupones {
            text-align: center;
            padding: 3rem;
            color: #666;
            grid-column: 1 / -1;
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
        
        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        
        .btn-outline:hover {
            background: var(--primary-color);
            color: var(--white);
        }
        
        .copiar-cupon {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 0.9rem;
            margin-left: 0.5rem;
        }
        
        .copiar-cupon:hover {
            text-decoration: underline;
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
    <link rel="stylesheet" href="../css/responsiveCuponesU.css">
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
                    <a href="carritoUsuario.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        Carrito <span class="cart-count"><?php echo $total_items; ?></span>
                    </a>
                    <a href="../clientes/perfil.php" class="nav-link">Mi Cuenta</a>
                    <a href="cuponesUsuario.php" class="nav-link" style="color: var(--primary-color);">Cupones</a>
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
        <div class="cupones-header">
            <h1 class="page-title">Mis cupones de descuento</h1>
            <p class="page-subtitle">Aprovecha estos descuentos especiales en tu próxima compra</p>
        </div>

        <!--cupones disponibles-->
        <h2 class="seccion-titulo">Cupones disponibles</h2>
        
        <?php if (empty($cupones_disponibles)): ?>
            <div class="no-cupones">
                <h3>No hay cupones disponibles en este momento</h3>
                <p>¡Vuelve pronto para descubrir nuevas promociones!</p>
                <a href="catalogoUsuario.php" class="btn btn-primary">Ir de Compras</a>
            </div>
        <?php else: ?>
            <div class="cupones-scroll">
                <div class="cupones-grid">
                    <?php foreach ($cupones_disponibles as $cupon): ?>
                        <div class="cupon-card">
                            <div class="cupon-header">
                                <div class="cupon-codigo">
                                    <?php echo $cupon['codigo']; ?>
                                    <button class="copiar-cupon" onclick="copiarCodigo('<?php echo $cupon['codigo']; ?>', this)">
                                        Copiar
                                    </button>
                                </div>
                                <span class="cupon-tipo">
                                    <?php echo $cupon['tipo'] == 'porcentaje' ? 'DESCUENTO %' : '$ MONTO FIJO'; ?>
                                </span>
                            </div>
                            
                            <div class="cupon-valor <?php echo $cupon['tipo']; ?>">
                                <?php echo $cupon['valor']; ?>
                            </div>
                            
                            <div class="cupon-detalles">
                                <?php if ($cupon['min_compra'] > 0): ?>
                                    <div class="detalle-item">
                                        <span class="detalle-label">Mínimo de compra:</span>
                                        <span class="detalle-value">$<?php echo number_format($cupon['min_compra'], 2); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($cupon['usos_maximos']): ?>
                                    <div class="detalle-item">
                                        <span class="detalle-label">Usos restantes:</span>
                                        <span class="detalle-value">
                                            <?php echo ($cupon['usos_maximos'] - $cupon['usos_actuales']); ?> de <?php echo $cupon['usos_maximos']; ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="detalle-item">
                                    <span class="detalle-label">Válido hasta:</span>
                                    <span class="detalle-value">
                                        <?php 
                                        if ($cupon['fecha_fin']) {
                                            echo date('d/m/Y', strtotime($cupon['fecha_fin']));
                                            $dias_restantes = floor((strtotime($cupon['fecha_fin']) - time()) / (60 * 60 * 24));
                                            if ($dias_restantes <= 7) {
                                                echo " <span class='badge badge-warning'>Vence pronto</span>";
                                            }
                                        } else {
                                            echo " <span class='badge badge-success'>Permanente</span>";
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!--cupones expirados-->
        <?php if (!empty($cupones_expirados)): ?>
            <h2 class="seccion-titulo">Cupones expirados</h2>
            <div class="cupones-scroll">
                <div class="cupones-grid">
                    <?php foreach ($cupones_expirados as $cupon): ?>
                        <div class="cupon-card expirado">
                            <div class="cupon-header">
                                <div class="cupon-codigo" style="background: #f8f9fa; color: #6c757d; border-color: #6c757d;">
                                    <?php echo $cupon['codigo']; ?>
                                </div>
                                <span class="cupon-tipo" style="background: #6c757d;">
                                    EXPIRADO
                                </span>
                            </div>
                            
                            <div class="cupon-valor <?php echo $cupon['tipo']; ?>" style="color: #6c757d;">
                                <?php echo $cupon['valor']; ?>
                            </div>
                            
                            <div class="cupon-descripcion" style="color: #999;">
                                Este cupón expiró el <?php echo date('d/m/Y', strtotime($cupon['fecha_fin'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        function copiarCodigo(codigo, boton) {
            navigator.clipboard.writeText(codigo).then(function() {
                const originalText = boton.textContent;
                boton.textContent = 'Copiado';
                boton.style.color = 'var(--secondary-color)';
                
                setTimeout(() => {
                    boton.textContent = originalText;
                    boton.style.color = 'var(--primary-color)';
                }, 2000);
            }).catch(function(err) {
                console.error('Error al copiar: ', err);
                alert('Error al copiar el código');
            });
        }
        
        //resaltar cupones que vencen pronto
        document.addEventListener('DOMContentLoaded', function() {
            const cupones = document.querySelectorAll('.cupon-card:not(.expirado)');
            cupones.forEach(cupon => {
                const fechaFin = cupon.querySelector('.detalle-value');
                if (fechaFin && fechaFin.textContent.includes('Vence pronto')) {
                    cupon.style.borderLeftColor = '#ffc107';
                    cupon.style.background = '#fffdf6';
                }
            });
        });
    </script>

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