<?php
session_start();
require_once '../../config/database.php';

//verificar si es administrador
if (!isset($_SESSION['loggedin']) || $_SESSION['usuario_tipo'] !== 'administrador') {
    header("Location: ../clientes/login.php");
    exit;
}

$mensaje = '';
$error = '';

//cambio de estado del pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $pedido_id = intval($_POST['pedido_id']);
    $nuevo_estado = $_POST['nuevo_estado'];
    
    $sql = "UPDATE pedidos SET estado = '$nuevo_estado' WHERE id = $pedido_id";
    
    if (mysqli_query($conexion, $sql)) {
        $mensaje = "Estado del pedido #$pedido_id actualizado a: " . ucfirst($nuevo_estado);
        
        //redirigir para actualizar las estadisticas
        header("Location: gestionar_pedidos.php?mensaje=" . urlencode($mensaje));
        exit;
    } else {
        $error = "Error al actualizar estado: " . mysqli_error($conexion);
    }
}

//mostrar mensaje si viene por redireccion
if (isset($_GET['mensaje'])) {
    $mensaje = $_GET['mensaje'];
}

//obtener todos los pedidos con informacion del cliente
$pedidos = [];
$sql = "SELECT p.*, c.nombre as cliente_nombre, c.email as cliente_email,
            (SELECT COUNT(*) FROM detalle_pedidos dp WHERE dp.pedido_id = p.id) as total_items,
            (SELECT SUM(dp.cantidad) FROM detalle_pedidos dp WHERE dp.pedido_id = p.id) as total_productos
        FROM pedidos p 
        LEFT JOIN clientes c ON p.cliente_id = c.id 
        ORDER BY p.fecha_pedido DESC";
$result = mysqli_query($conexion, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $pedidos[] = $row;
    }
}

//obtener estadisticas actualizazadas - consulta directa a la BD
$estadisticas_sql = "SELECT 
    COUNT(*) as total_pedidos,
    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado = 'confirmado' THEN 1 ELSE 0 END) as confirmados,
    SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as enviados,
    SUM(CASE WHEN estado = 'entregado' THEN 1 ELSE 0 END) as entregados,
    SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados
    FROM pedidos";

$result_stats = mysqli_query($conexion, $estadisticas_sql);
$estadisticas = mysqli_fetch_assoc($result_stats);

//asegurar que todos los valores tengan un número
$estadisticas = array_map(function($value) {
    return $value ? $value : 0;
}, $estadisticas);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Pedidos - EcoGarden</title>
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
        
        /*tabal de pedidos*/
        .pedidos-section {
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
        
        .badge-pendiente { background: #fff3cd; color: #856404; }
        .badge-confirmado { background: #d1ecf1; color: #0c5460; }
        .badge-enviado { background: #d4edda; color: #155724; }
        .badge-entregado { background: #c3e6cb; color: #155724; }
        .badge-cancelado { background: #f8d7da; color: #721c24; }
        
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
            background-color: #ffffff;       
            color: var(--text-color);        
            border: 2px solid  #155724;     
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s ease;
        }

        .btn-primary:hover {
            background: #2d5a27;
            color: var(--white);
        }
        
        .btn-primary:active {
            background: #2d5a27;
            color: var(--white); 
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
        
        .estado-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .select-estado {
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.8rem;
            background: var(--white);
        }
        
        .pedido-detalle {
            background: var(--light-bg);
            padding: 1rem;
            border-radius: 6px;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        
        .detalle-toggle {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: underline;
            margin-top: 0.5rem;
        }
        
        .cliente-info {
            font-size: 0.8rem;
            color: #666;
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
    <link rel="stylesheet" href="../css/responsiveGestionPA.css">
</head>
<body>
    <header class="admin-header">
        <nav class="admin-nav">
            <h1>Gestionar pedidos</h1>
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
                <div class="stat-number"><?php echo $estadisticas['pendientes']; ?></div>
                <div class="stat-label">Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['confirmados']; ?></div>
                <div class="stat-label">Confirmados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['enviados']; ?></div>
                <div class="stat-label">Enviados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['entregados']; ?></div>
                <div class="stat-label">Entregados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $estadisticas['cancelados']; ?></div>
                <div class="stat-label">Cancelados</div>
            </div>
        </div>

        <!--lista de pedidos-->
        <section class="pedidos-section">
            <h2>Todos los pedidos (<?php echo count($pedidos); ?>)</h2>
            
            <?php if (empty($pedidos)): ?>
                <p>No hay pedidos registrados.</p>
            <?php else: ?>
                <div class="tabla-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Items</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $pedido): ?>
                                <tr>
                                    <td>
                                        <div><strong><?php echo htmlspecialchars($pedido['cliente_nombre']); ?></strong></div>
                                        <div class="cliente-info"><?php echo $pedido['cliente_email']; ?></div>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></td>
                                    <td><strong>$<?php echo number_format($pedido['total'], 2); ?></strong></td>
                                    <td><?php echo $pedido['total_items']; ?> productos</td>
                                    <td>
                                        <span class="badge badge-<?php echo $pedido['estado']; ?>">
                                            <?php echo ucfirst($pedido['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="estado-form">
                                            <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                            <select name="nuevo_estado" class="select-estado" style="font-family: 'Century Gothic', Arial, sans-serif;">
                                                <option value="pendiente" <?php echo $pedido['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                                <option value="confirmado" <?php echo $pedido['estado'] == 'confirmado' ? 'selected' : ''; ?>>Confirmado</option>
                                                <option value="enviado" <?php echo $pedido['estado'] == 'enviado' ? 'selected' : ''; ?>>Enviado</option>
                                                <option value="entregado" <?php echo $pedido['estado'] == 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                                                <option value="cancelado" <?php echo $pedido['estado'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                            </select>
                                            <button type="submit" name="cambiar_estado" value="1" class="btn btn-primary">
                                                Actualizar
                                            </button>
                                        </form>
                                        
                                        <button class="detalle-toggle" style="font-family: 'Century Gothic', Arial, sans-serif;" onclick="toggleDetalle(<?php echo $pedido['id']; ?>)">
                                            Ver Detalles
                                        </button>
                                    </td>
                                </tr>
                                <tr id="detalle-<?php echo $pedido['id']; ?>" style="display: none;">
                                    <td colspan="7">
                                        <div class="pedido-detalle">
                                            <h4>Detalles del Pedido #<?php echo $pedido['id']; ?></h4>
                                            <p><strong>Dirección de envío:</strong> <?php echo htmlspecialchars($pedido['direccion_envio']); ?></p>
                                            
                                            <?php
                                            //detalles del pedido
                                            $sql_detalles = "SELECT dp.*, p.nombre as producto_nombre 
                                                            FROM detalle_pedidos dp 
                                                            LEFT JOIN productos p ON dp.producto_id = p.id 
                                                            WHERE dp.pedido_id = {$pedido['id']}";
                                            $result_detalles = mysqli_query($conexion, $sql_detalles);
                                            ?>
                                            
                                            <p><b>Productos:</b></p>
                                            <ul>
                                                <?php while ($detalle = mysqli_fetch_assoc($result_detalles)): ?>
                                                    <li>
                                                        <strong><?php echo $detalle['producto_nombre']; ?></strong> - 
                                                        <?php echo $detalle['cantidad']; ?> x 
                                                        $<?php echo number_format($detalle['precio_unitario'], 2); ?> = 
                                                        <strong>$<?php echo number_format($detalle['subtotal'], 2); ?></strong>
                                                    </li>
                                                <?php endwhile; ?>
                                            </ul>
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
        function toggleDetalle(pedidoId) {
            const detalle = document.getElementById('detalle-' + pedidoId);
            const button = event.target;
            
            if (detalle.style.display === 'none') {
                detalle.style.display = 'table-row';
                button.textContent = 'Ocultar Detalles';
            } else {
                detalle.style.display = 'none';
                button.textContent = 'Ver Detalles';
            }
        }
        
        //auto-ocultar mensajes después de 5 segundos
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