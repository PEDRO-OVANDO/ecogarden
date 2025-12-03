<?php
session_start();
require_once '../../config/database.php';

//verificar login
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

$total_items = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $total_items += $item['cantidad'];
    }
}

$cliente_id = $_SESSION['usuario_id'];

//datos del cliente
$sql_cliente = "SELECT * FROM clientes WHERE id = $cliente_id";
$result_cliente = mysqli_query($conexion, $sql_cliente);
$cliente = mysqli_fetch_assoc($result_cliente);

//pedidos del cliente
$pedidos = [];
$sql = "SELECT p.*, 
            (SELECT COUNT(*) FROM detalle_pedidos dp WHERE dp.pedido_id = p.id) as total_items,
            (SELECT SUM(dp.subtotal) FROM detalle_pedidos dp WHERE dp.pedido_id = p.id) as total_pedido
        FROM pedidos p 
        WHERE p.cliente_id = $cliente_id 
        ORDER BY p.fecha_pedido DESC";
$result = mysqli_query($conexion, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $pedidos[] = $row;
    }
}

//estadisticas del cliente
$estadisticas_sql = "SELECT 
    COUNT(*) as total_pedidos,
    SUM(total) as total_gastado,
    MAX(fecha_pedido) as ultimo_pedido
    FROM pedidos WHERE cliente_id = $cliente_id";
$result_stats = mysqli_query($conexion, $estadisticas_sql);
$estadisticas = mysqli_fetch_assoc($result_stats);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - EcoGarden</title>
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
        .profile-header {
            margin-top: 80px;
            padding: 2rem 0;
        }
        
        .page-title {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .welcome-message {
            color: #666;
            font-size: 1.1rem;
        }
        
        /*estadisticas*/
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }
        
        /*perfil*/
        .info-section {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .section-title {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--text-color);
        }
        
        .info-value {
            color: #666;
            padding: 0.5rem;
            background: var(--light-bg);
            border-radius: 6px;
        }
        
        /*pedidos*/
        .pedidos-section {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .pedidos-list {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .pedido-card {
            background: var(--light-bg);
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s;
        }
        
        .pedido-card:hover {
            transform: translateX(5px);
        }
        
        .pedido-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .pedido-id {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        .estado {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .estado.pendiente { background: #fff3cd; color: #856404; }
        .estado.confirmado { background: #d1ecf1; color: #0c5460; }
        .estado.enviado { background: #d4edda; color: #155724; }
        .estado.entregado { background: #c3e6cb; color: #155724; }
        .estado.cancelado { background: #f8d7da; color: #721c24; }
        
        .pedido-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .info-line {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .info-line strong {
            color: var(--text-color);
        }
        
        .info-line span {
            color: #666;
            font-size: 0.9rem;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Century Gothic', Arial, sans-serif;
        }
        
        .btn-primary {
            background-color: #ffffff;       
            color: var(--text-color);        
            border: 2px solid  #155724;     
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s ease;
        }

        .btn-primary:hover {
            background: #155724;
            color: var(--white);
        }

        .btn-primary:active {
            background: #155724;
            color: var(--white);
        }
        
        .btn-edit {
            background-color: #ffffff;       
            color: var(--text-color);        
            border: 2px solid  #155724;     
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s ease;
        }

        .btn-edit:hover {
            background: #155724;
            color: var(--white);
        }

        .btn-edit:active {
            background: #155724;
            color: var(--white);
        }

        .btn-cancel {
            background: none;
            color: var(--text-color);
            border: 2px solid #6c757d;
            padding: 10px 25px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-cancel:hover {
            background: #5a6268;
            color: var(--white);
        }

        .btn-cancel:active {
            background: #545b62;
            color: var(--white);
        }

        .btn-primary,
        .btn-cancel,
        .btn-edit {
            font-family: 'Century Gothic', Arial, sans-serif;
            font-size: 16px;
            padding: 10px 20px;       
            display: inline-block;
            text-align: center;
            border-radius: 6px;      
            cursor: pointer;
        }

        .btn-primary,
        .btn-cancel,
        .btn-edit {
            min-width: 180px;         
        }

        .form-group .btn-primary,
        .form-group .btn-cancel,
        .form-group .btn-edit {
            margin-right: 10px;
        }

        
        .no-pedidos {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .pedidos-scroll {
            max-height: 300px;
            overflow-y: auto;
            padding-right: 10px;
        }

        /* Estilos para los campos editables */
        .edit-input, .edit-textarea, .edit-select {
            padding: 0.5rem;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-family: 'Century Gothic', Arial, sans-serif;
            font-size: 1rem;
            background: var(--white);
            transition: border-color 0.3s;
            width: 100%;
        }

        .edit-input:focus, .edit-textarea:focus, .edit-select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .edit-textarea {
            resize: vertical;
            min-height: 80px;
        }

        .info-value {
            transition: all 0.3s;
        }

        /* Para los campos que no son editables (solo lectura) */
        .info-value:not(.edit-input):not(.edit-textarea):not(.edit-select) {
            padding: 0.5rem;
            background: var(--light-bg);
            border-radius: 6px;
            border: 2px solid transparent;
        }

        /* Estado de edición */
        .editing .info-value {
            background: var(--white);
            border-color: #e9ecef;
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
    <link rel="stylesheet" href="../css/responsivePerfil.css">
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
                    <a href="../usuario/catalogoUsuario.php" class="nav-link">Productos</a>
                    <a href="../usuario/carritoUsuario.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        Carrito <span class="cart-count"><?php echo $total_items; ?></span>
                    </a>
                    <a href="perfil.php" class="nav-link" style="color: var(--primary-color);">Mi Cuenta</a>
                    <a href="../usuario/cuponesUsuario.php" class="nav-link">Cupones</a>
                    <a href="../usuario/logout.php" class="nav-link">Cerrar Sesión</a>
                </div>

                <!-- Menú lateral móvil -->
                <div class="mobile-menu" id="mobileMenu">
                    <a href="../usuario/catalogoUsuario.php">Productos</a>
                    <a href="../usuario/carritoUsuario.php">Carrito (<?php echo $total_items; ?>)</a>
                    <a href="perfil.php">Mi Cuenta</a>
                    <a href="../usuario/cuponesUsuario.php">Cupones</a>
                    <a href="../usuario/logout.php">Cerrar Sesión</a>
                </div>
                <div class="menu-overlay" id="menuOverlay"></div>
            </nav>
        </div>
    </header>

    <!--contenido principal-->
    <main class="container">
        <div class="profile-header">
            <h1 class="page-title">Mi cuenta</h1>
            <p class="welcome-message">Hola, <strong><?php echo $_SESSION['usuario_nombre']; ?></strong>. Bienvenido a tu panel personal.</p>
        </div>

        <!--estadisticas-->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['total_pedidos'] ?? 0; ?></div>
                <div class="stat-label">Total pedidos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($estadisticas['total_gastado'] ?? 0, 2); ?></div>
                <div class="stat-label">Total gastado</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php if ($estadisticas['ultimo_pedido']): ?>
                        <?php echo date('d/m/Y', strtotime($estadisticas['ultimo_pedido'])); ?>
                    <?php else: ?>
                        --
                    <?php endif; ?>
                </div>
                <div class="stat-label">Último pedido</div>
            </div>
        </div>

        <!--perfil-->
        <section class="info-section">
            <h2 class="section-title">Información personal</h2>
            
            <!-- vista de solo lectura -->
            <div id="readonly-view">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Nombre Completo</span>
                        <div class="info-value"><?php echo htmlspecialchars($cliente['nombre']); ?></div>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <div class="info-value"><?php echo htmlspecialchars($cliente['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Teléfono</span>
                        <div class="info-value"><?php echo $cliente['telefono'] ? htmlspecialchars($cliente['telefono']) : 'No registrado'; ?></div>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Dirección</span>
                        <div class="info-value"><?php echo $cliente['direccion'] ? htmlspecialchars($cliente['direccion']) : 'No registrada'; ?></div>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Experiencia en Jardinería</span>
                        <div class="info-value"><?php echo ucfirst($cliente['experiencia_jardineria']); ?></div>
                    </div>
                </div>
                
                <div class="actions">
                    <button id="edit-button" class="btn btn-edit">Editar</button>
                </div>
            </div>
            
            <!--formulario de edicion-->
            <form method="POST" action="actualizar_perfil.php" class="info-grid" id="edit-view" style="display: none;">
                <div class="info-item">
                    <span class="info-label">Nombre Completo</span>
                    <input type="text" name="nombre" class="info-value edit-input" value="<?php echo htmlspecialchars($cliente['nombre']); ?>" required>
                </div>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <input type="email" name="email" class="info-value edit-input" value="<?php echo htmlspecialchars($cliente['email']); ?>" required>
                </div>
                <div class="info-item">
                    <span class="info-label">Teléfono</span>
                    <input type="tel" name="telefono" class="info-value edit-input" 
                        value="<?php echo htmlspecialchars($cliente['telefono'] ?? ''); ?>" 
                        placeholder="Ingresa tu teléfono">
                </div>
                <div class="info-item">
                    <span class="info-label">Dirección</span>
                    <textarea name="direccion" class="info-value edit-textarea" 
                            placeholder="Ingresa tu dirección completa"><?php echo htmlspecialchars($cliente['direccion'] ?? ''); ?></textarea>
                </div>
                <div class="info-item">
                    <span class="info-label">Experiencia en Jardinería</span>
                    <select name="experiencia_jardineria" class="info-value edit-select">
                        <option value="principiante" <?php echo ($cliente['experiencia_jardineria'] ?? '') == 'principiante' ? 'selected' : ''; ?>>Principiante</option>
                        <option value="intermedio" <?php echo ($cliente['experiencia_jardineria'] ?? '') == 'intermedio' ? 'selected' : ''; ?>>Intermedio</option>
                        <option value="avanzado" <?php echo ($cliente['experiencia_jardineria'] ?? '') == 'avanzado' ? 'selected' : ''; ?>>Avanzado</option>
                    </select>
                </div>
                <div class="info-item">
                    <span class="info-label">Cambiar Contraseña</span>
                    <input type="password" name="nueva_password" class="info-value edit-input" 
                        placeholder="Nueva contraseña (dejar vacío para no cambiar)">
                </div>
                <div class="info-item">
                    <span class="info-label">Confirmar Contraseña</span>
                    <input type="password" name="confirmar_password" class="info-value edit-input" 
                        placeholder="Confirmar nueva contraseña">
                </div>
                
                <div class="actions" style="grid-column: 1 / -1;">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <button type="button" id="cancel-button" class="btn btn-cancel">Cancelar</button>
                </div>
            </form>
        </section>                

        <!--historial de pedidos-->
        <section class="pedidos-section">
            <h2 class="section-title">Mis pedidos</h2>
            
            <?php if (empty($pedidos)): ?>
                <div class="no-pedidos">
                    <h3>No tienes pedidos aún</h3>
                    <p>¡Descubre nuestros productos y comienza a cultivar tu jardín!</p>
                    <a href="../productos/catalogo.php" class="btn btn-primary">Explorar Productos</a>
                </div>
            <?php else: ?>
                <div class="pedidos-scroll">
                    <div class="pedidos-list">
                        <?php foreach ($pedidos as $pedido): ?>
                            <div class="pedido-card">
                                <div class="pedido-header">
                                    <span class="pedido-id">Pedido #<?php echo $pedido['id']; ?></span>
                                    <span class="estado <?php echo $pedido['estado']; ?>">
                                        <?php echo ucfirst($pedido['estado']); ?>
                                    </span>
                                </div>
                                <div class="pedido-info">
                                    <div class="info-line">
                                        <strong>Fecha</strong>
                                        <span><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></span>
                                    </div>
                                    <div class="info-line">
                                        <strong>Total</strong>
                                        <span>$<?php echo number_format($pedido['total_pedido'], 2); ?></span>
                                    </div>
                                    <div class="info-line">
                                        <strong>Items</strong>
                                        <span><?php echo $pedido['total_items']; ?> productos</span>
                                    </div>
                                    <div class="info-line">
                                        <strong>Dirección</strong>
                                        <span><?php echo htmlspecialchars($pedido['direccion_envio']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const editButton = document.getElementById('edit-button');
        const cancelButton = document.getElementById('cancel-button');
        const readonlyView = document.getElementById('readonly-view');
        const editView = document.getElementById('edit-view');
        
        editButton.addEventListener('click', function() {
            readonlyView.style.display = 'none';
            editView.style.display = 'grid';
        });
        
        cancelButton.addEventListener('click', function() {
            editView.style.display = 'none';
            readonlyView.style.display = 'block';
            editView.reset();
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