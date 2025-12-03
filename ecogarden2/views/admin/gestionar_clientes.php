<?php
session_start();
require_once '../../config/database.php';

// Verificar si es administrador
if (!isset($_SESSION['loggedin']) || $_SESSION['usuario_tipo'] !== 'administrador') {
    header("Location: ../clientes/login.php");
    exit;
}

$mensaje = '';
$error = '';

// Procesar eliminación de cliente
if (isset($_GET['eliminar'])) {
    $cliente_id = intval($_GET['eliminar']);
    
    // No permitir eliminarse a sí mismo
    if ($cliente_id == $_SESSION['usuario_id']) {
        $error = "No puedes eliminar tu propia cuenta.";
    } else {
        //verificar si el cliente tiene pedidos
        $sql_pedidos = "SELECT COUNT(*) as total_pedidos FROM pedidos WHERE cliente_id = $cliente_id";
        $result_pedidos = mysqli_query($conexion, $sql_pedidos);
        $pedidos = mysqli_fetch_assoc($result_pedidos);
        
        if ($pedidos['total_pedidos'] > 0) {
            $error = "No se puede eliminar el cliente porque tiene pedidos asociados.";
        } else {
            $sql = "DELETE FROM clientes WHERE id = $cliente_id";
            
            if (mysqli_query($conexion, $sql)) {
                $mensaje = "Cliente eliminado correctamente.";
                header("Location: gestionar_clientes.php?mensaje=" . urlencode($mensaje));
                exit;
            } else {
                $error = "Error al eliminar cliente: " . mysqli_error($conexion);
            }
        }
    }
}

// Mostrar mensaje si viene por redirección
if (isset($_GET['mensaje'])) {
    $mensaje = $_GET['mensaje'];
}

//obtener todos los clientes (solo tipo 'cliente')
$clientes = [];
$sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM pedidos p WHERE p.cliente_id = c.id) as total_pedidos,
            (SELECT SUM(p.total) FROM pedidos p WHERE p.cliente_id = c.id) as total_gastado
        FROM clientes c 
        WHERE c.tipo_usuario = 'cliente'
        ORDER BY c.fecha_registro DESC";
$result = mysqli_query($conexion, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $clientes[] = $row;
    }
}

//obtener estadisticas
$estadisticas_sql = "SELECT 
    COUNT(*) as total_clientes,
    SUM(CASE WHEN tipo_usuario = 'cliente' THEN 1 ELSE 0 END) as total_usuarios,
    SUM(CASE WHEN tipo_usuario = 'administrador' THEN 1 ELSE 0 END) as total_administradores
    FROM clientes";

$result_stats = mysqli_query($conexion, $estadisticas_sql);
$estadisticas = mysqli_fetch_assoc($result_stats);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Clientes - EcoGarden</title>
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
        
        /*tabla de clientes*/
        .clientes-section {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table th {
            background: var(--light-bg);
            font-weight: 600;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-cliente { background: #d1ecf1; color: #0c5460; }
        .badge-administrador { background: #d4edda; color: #155724; }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;           
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
            background: none;
            border: white 2px solid;
            font-family: 'Century Gothic', Arial, sans-serif;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
            style="background: #6c757d; color: white;"
        }
        
        .btn-danger {
            background-color: #ffffff;       
            color: var(--text-color);        
            border: 2px solid #dc3545;       
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s ease;
            text-decoration: none;
        }

        .btn-danger:hover {
        background-color: #dc3545;       
        color: #000;                     
        }

        .btn-danger:active {
            background-color: #dc3545;
            color: #000;
        }

        .btn-submit {
            background-color: #cac7c7ff;   
            color: var(--text-color);        
            border: 2px solid #666;      
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s ease;
            text-decoration: none;
        }

        .btn-warning {
            background: #ffc107;
            color: var(--text-color);
        }

        .btn-cancel {
            background: none;
            color: var(--white);
        }

        .btn-cancel:hover {
        background-color: #ffffff;       
        color: #000;                     
        }

        .btn-cancel:active {
            background-color: #ffffff; 
            color: #000;
        }
        
        .tipo-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .select-tipo {
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.8rem;
            background: var(--white);
        }
        
        .cliente-info {
            font-size: 0.8rem;
            color: #666;
        }
        
        .acciones {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .usuario-actual {
            background: #fff3cd !important;
            border-left: 4px solid #ffc107;
        }
        
        .sin-pedidos {
            color: #6c757d;
            font-style: italic;
        }

        .tabla-scroll {
            max-height: 300px;    
            overflow-y: auto;     
            overflow-x: auto;     
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .tabla-scroll table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .tabla-scroll thead th {
            position: sticky;
            top: 0;
            background: white; 
            z-index: 3;        
        }
    </style>
    <link rel="stylesheet" href="../css/responsiveGestionCA.css">
</head>
<body>
    <header class="admin-header">
        <nav class="admin-nav">
            <h1>Gestionar clientes</h1>
            <div>
                <a href="dashboard.php" class="btn btn-cancel">Volver al Dashboard</a>
            </div>
        </nav>
    </header>

    <main class="admin-main">
        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!--estadisticas-->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['total_clientes']; ?></div>
                <div class="stat-label">Total Usuarios</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['total_usuarios']; ?></div>
                <div class="stat-label">Clientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['total_administradores']; ?></div>
                <div class="stat-label">Administradores</div>
            </div>
        </div>

        <!--lista de clientes-->
        <section class="clientes-section">
            <h2>Clientes registrados (<?php echo count($clientes); ?>)</h2>
            
            <?php if (empty($clientes)): ?>
                <p>No hay clientes registrados.</p>
            <?php else: ?>
                <div class="tabla-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Información</th>
                                <th>Registro</th>
                                <th>Pedidos</th>
                                <th>Experiencia</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente): ?>
                                <tr>
                                    <td>
                                        <div><strong><?php echo htmlspecialchars($cliente['nombre']); ?></strong></div>
                                        <div class="cliente-info"><?php echo $cliente['email']; ?></div>
                                        <div class="cliente-info"><?php echo $cliente['telefono'] ?: 'Sin teléfono'; ?></div>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($cliente['fecha_registro'])); ?>
                                    </td>
                                    <td>
                                        <?php if ($cliente['total_pedidos'] > 0): ?>
                                            <div><strong><?php echo $cliente['total_pedidos']; ?> pedidos</strong></div>
                                            <div class="cliente-info">
                                                Total: $<?php echo number_format($cliente['total_gastado'] ?? 0, 2); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="sin-pedidos">Sin pedidos</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo ucfirst($cliente['experiencia_jardineria']); ?>
                                    </td>
                                    <td>
                                        <div class="acciones">
                                            <?php if ($cliente['total_pedidos'] == 0): ?>
                                                <a href="?eliminar=<?php echo $cliente['id']; ?>" class="btn-danger" 
                                                onclick="return confirm('¿Estás seguro de eliminar a <?php echo htmlspecialchars($cliente['nombre']); ?>?')">
                                                    Eliminar
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-submit" disabled 
                                                        title="No se puede eliminar - Tiene pedidos">
                                                    No Eliminable
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>    
            <?php endif; ?>
        </section>
    </main>

    <script>
        // Auto-ocultar mensajes después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>