<?php
session_start();
require_once '../../config/database.php';

//verificar si es administrador
if (!isset($_SESSION['loggedin']) || $_SESSION['usuario_tipo'] !== 'administrador') {
    header("Location: ../clientes/login.php");
    exit;
}

//cards de estadisticas 
$total_productos = mysqli_query($conexion, "SELECT COUNT(*) as total FROM productos")->fetch_assoc()['total'];
$total_clientes = mysqli_query($conexion, "SELECT COUNT(*) as total FROM clientes WHERE tipo_usuario = 'cliente'")->fetch_assoc()['total'];
$total_pedidos = mysqli_query($conexion, "SELECT COUNT(*) as total FROM pedidos")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - EcoGarden</title>
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
        
        .admin-header {
            background: var(--primary-color);
            color: var(--white);
            padding: 1rem 0;
        }
        
        .admin-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .admin-main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }
        
        .welcome-section {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .action-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: var(--text-color);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: var(--primary-color);
            color: var(--white);
            text-decoration: none;
            border-radius: 6px;
            margin: 0.5rem;
        }
        
        .btn-logout {
            background: none;
            border: white 2px solid;
        }
    </style>
    <link rel="stylesheet" href="../css/responsiveDashboardA.css">
</head>
<body>
    <header class="admin-header">
        <nav class="admin-nav">
            <h1>Panel de administración</h1>
            <div>
                <a href="../../logout.php" class="btn btn-logout">Cerrar Sesión</a>
            </div>
        </nav>
    </header>

    <main class="admin-main">
        <section class="welcome-section">
            <h2>Bienvenido <span><?php echo $_SESSION['usuario_nombre']; ?></span> </h2>
            <p>Gestiona tu tienda EcoGarden desde aquí</p>
        </section>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_productos; ?></div>
                <div>Productos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_clientes; ?></div>
                <div>Clientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_pedidos; ?></div>
                <div>Pedidos</div>
            </div>
        </div>
        
        <!--le agrego iconos o imagenes a las tarjeta? preguntarle a peter-->
        <div class="actions-grid">
            <a href="gestionar_productos.php" class="action-card">
                <h3>Gestionar productos</h3>
                <p>Agregar, editar o eliminar productos</p>
            </a>
            <a href="gestionar_pedidos.php" class="action-card">
                <h3>Gestionar pedidos</h3>
                <p>Ver y actualizar estados de pedidos</p>
            </a>
            <a href="gestionar_clientes.php" class="action-card">
                <h3>Gestionar clientes</h3>
                <p>Administrar cuentas de clientes</p>
            </a>
            <a href="gestionar_cupones.php" class="action-card">
                <h3>Gestionar cupones</h3>
                <p>Administra los cupones de descuento</p>
            </a>
        </div>
    </main>
</body>
</html>