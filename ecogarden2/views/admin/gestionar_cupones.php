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

//agregar/editar cupon
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo']);
    $tipo = $_POST['tipo'];
    $valor = floatval($_POST['valor']);
    $min_compra = floatval($_POST['min_compra'] ?? 0);
    $usos_maximos = !empty($_POST['usos_maximos']) ? intval($_POST['usos_maximos']) : NULL;
    $fecha_inicio = !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : NULL;
    $fecha_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : NULL;
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    //validaciones
    if (empty($codigo) || $valor <= 0) {
        $error = 'Código y valor son obligatorios. El valor debe ser mayor a 0.';
    } elseif ($tipo == 'porcentaje' && $valor > 100) {
        $error = 'El porcentaje no puede ser mayor a 100%.';
    } else {
        //verificar si el codigo ya existe (excepto en edicion)
        $cupon_id = $_POST['cupon_id'] ?? 0;
        $sql_verificar = "SELECT id FROM cupones_descuento WHERE codigo = '$codigo'";
        if ($cupon_id > 0) {
            $sql_verificar .= " AND id != $cupon_id";
        }
        
        $result_verificar = mysqli_query($conexion, $sql_verificar);
        if (mysqli_num_rows($result_verificar) > 0) {
            $error = 'El código del cupón ya existe.';
        } else {
            if (isset($_POST['cupon_id']) && !empty($_POST['cupon_id'])) {
                //editar cupon existente
                $cupon_id = intval($_POST['cupon_id']);
                
                $sql = "UPDATE cupones_descuento SET 
                        codigo = '$codigo', 
                        tipo = '$tipo', 
                        valor = $valor, 
                        min_compra = $min_compra, 
                        usos_maximos = " . ($usos_maximos ? "$usos_maximos" : "NULL") . ", 
                        fecha_inicio = " . ($fecha_inicio ? "'$fecha_inicio'" : "NULL") . ", 
                        fecha_fin = " . ($fecha_fin ? "'$fecha_fin'" : "NULL") . ", 
                        activo = $activo 
                        WHERE id = $cupon_id";
                
                if (mysqli_query($conexion, $sql)) {
                    $mensaje = 'Cupón actualizado correctamente.';
                } else {
                    $error = 'Error al actualizar cupón: ' . mysqli_error($conexion);
                }
            } else {
                //agregar nuevo cupón
                $sql = "INSERT INTO cupones_descuento (codigo, tipo, valor, min_compra, usos_maximos, fecha_inicio, fecha_fin, activo) 
                        VALUES ('$codigo', '$tipo', $valor, $min_compra, " . 
                        ($usos_maximos ? "$usos_maximos" : "NULL") . ", " . 
                        ($fecha_inicio ? "'$fecha_inicio'" : "NULL") . ", " . 
                        ($fecha_fin ? "'$fecha_fin'" : "NULL") . ", $activo)";
                
                if (mysqli_query($conexion, $sql)) {
                    $mensaje = 'Cupón agregado correctamente.';
                    //limpiar el formulario
                    $_POST = array();
                } else {
                    $error = 'Error al agregar cupón: ' . mysqli_error($conexion);
                }
            }
        }
    }
}

//eliminar cupon
if (isset($_GET['eliminar'])) {
    $cupon_id = intval($_GET['eliminar']);
    $sql = "DELETE FROM cupones_descuento WHERE id = $cupon_id";
    
    if (mysqli_query($conexion, $sql)) {
        $mensaje = 'Cupón eliminado correctamente.';
    } else {
        $error = 'Error al eliminar cupón: ' . mysqli_error($conexion);
    }
}

//lista de cupones
$cupones = [];
$result = mysqli_query($conexion, "SELECT * FROM cupones_descuento ORDER BY creado_en DESC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $cupones[] = $row;
    }
}

//si se quiere editar, obtener datos del cupon
$cupon_editar = null;
if (isset($_GET['editar'])) {
    $cupon_id = intval($_GET['editar']);
    $result = mysqli_query($conexion, "SELECT * FROM cupones_descuento WHERE id = $cupon_id");
    if ($result && mysqli_num_rows($result) > 0) {
        $cupon_editar = mysqli_fetch_assoc($result);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Cupones - EcoGarden</title>
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
        
        .form-section, .list-section {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
        }
        
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
            background: #155724;
            color: var(--white);
        }

        .btn-edit {
            background-color: #ffffff;       
            color: var(--text-color);        
            border: 2px solid #ffc107;       
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s ease; 
        }
        
        .btn-edit:hover {
            background-color: #ffc107;       
            color: #000;                     
        }

        .btn-delete {
            background-color: #ffffff;       
            color: var(--text-color);        
            border: 2px solid #dc3545;       
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s ease;
        }

        .btn-delete:hover {
            background-color: #dc3545;       
            color: #000;                     
        }
        
        .btn-cancel {
            background: none;
            color: var(--white);
        }

        .btn-cancel:hover {
            background-color: #ffffff;       
            color: #000;                     
        }

        .Dbtn-cancel {
            background: none;
            color: var(--text-color);
            border: 2px solid #6c757d;
            padding: 10px 25px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .Dbtn-cancel:hover {
            background: #5a6268;
            color: var(--white);
        }
        
        .Dbtn-cancel:active {
            background: #545b62;
            color: var(--white);
        }

        /* ---normalizar tamaño y fuente entre los dos botones ---*/
        .btn-primary,
        .Dbtn-cancel {
            font-family: 'Century Gothic', Arial, sans-serif;
            font-size: 16px;
            padding: 10px 25px;       
            display: inline-block;
            text-align: center;
            border-radius: 6px;      
            cursor: pointer;
        }

        .btn-primary,
        .Dbtn-cancel {
            min-width: 180px;         
        }

        .form-group .btn-primary,
        .form-group .Dbtn-cancel {
            margin-right: 10px;
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
        
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-porcentaje {
            background: #e7f3ff;
            color: #0c63e4;
        }
        
        .badge-monto {
            background: #fff3cd;
            color: #856404;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
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

        .info-text {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.25rem;
        }
    </style>
    <link rel="stylesheet" href="../css/responsiveGestionCuA.css">
</head>
<body>
    <header class="admin-header">
        <nav class="admin-nav">
            <h1>Gestionar cupones de descuento</h1>
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

        <!--formulario para agregar/editar cupon-->
        <section class="form-section">
            <h2 style="text-align: center;"><?php echo $cupon_editar ? 'Editar Cupón' : 'Agregar nuevo cupón'; ?></h2>
            
            <form method="POST" action="">
                <?php if ($cupon_editar): ?>
                    <input type="hidden" name="cupon_id" value="<?php echo $cupon_editar['id']; ?>">
                <?php endif; ?>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="codigo">Código del cupón</label>
                        <input type="text" class="form-control" id="codigo" name="codigo" 
                            value="<?php echo $cupon_editar ? $cupon_editar['codigo'] : (isset($_POST['codigo']) ? $_POST['codigo'] : ''); ?>" 
                            required placeholder="EJ: VERANO20" style="font-family: 'Century Gothic', Arial, sans-serif;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="tipo">Tipo de descuento</label>
                        <select class="form-control" id="tipo" name="tipo" style="font-family: 'Century Gothic', Arial, sans-serif;" required>
                            <option value="porcentaje" <?php echo ($cupon_editar && $cupon_editar['tipo'] == 'porcentaje') || (isset($_POST['tipo']) && $_POST['tipo'] == 'porcentaje') ? 'selected' : ''; ?>>Porcentaje (%)</option>
                            <option value="monto_fijo" <?php echo ($cupon_editar && $cupon_editar['tipo'] == 'monto_fijo') || (isset($_POST['tipo']) && $_POST['tipo'] == 'monto_fijo') ? 'selected' : ''; ?>>Monto Fijo ($)</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="valor">Valor del descuento</label>
                        <input type="number" class="form-control" id="valor" name="valor" step="0.01" min="0.01"
                            value="<?php echo $cupon_editar ? $cupon_editar['valor'] : (isset($_POST['valor']) ? $_POST['valor'] : ''); ?>" 
                            required>
                        <small class="info-text" id="valor-help">Ingrese el valor del descuento</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="min_compra">Mínimo de Compra</label>
                        <input type="number" class="form-control" id="min_compra" name="min_compra" step="0.01" min="0"
                            value="<?php echo $cupon_editar ? $cupon_editar['min_compra'] : (isset($_POST['min_compra']) ? $_POST['min_compra'] : '0'); ?>">
                    </div>
                </div>
                
                <div class="grid-3">
                    <div class="form-group">
                        <label class="form-label" for="usos_maximos">Límite de Usos</label>
                        <input type="number" class="form-control" id="usos_maximos" name="usos_maximos" min="1"
                            value="<?php echo $cupon_editar ? $cupon_editar['usos_maximos'] : (isset($_POST['usos_maximos']) ? $_POST['usos_maximos'] : ''); ?>">
                        <small class="info-text">Dejar vacío para uso ilimitado</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="fecha_inicio">Fecha de inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" style="font-family: 'Century Gothic', Arial, sans-serif;"
                            value="<?php echo $cupon_editar ? $cupon_editar['fecha_inicio'] : (isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : ''); ?>">
                        <small class="info-text">Dejar vacío para empezar inmediatamente</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="fecha_fin">Fecha de fin</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" style="font-family: 'Century Gothic', Arial, sans-serif;"
                            value="<?php echo $cupon_editar ? $cupon_editar['fecha_fin'] : (isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : ''); ?>">
                        <small class="info-text">Dejar vacío para cupón permanente</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="activo" name="activo" value="1" 
                            <?php echo ($cupon_editar && $cupon_editar['activo']) || (isset($_POST['activo']) && $_POST['activo']) ? 'checked' : ''; ?>>
                        <label for="activo">Cupón activo (disponible para uso)</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $cupon_editar ? 'Actualizar' : 'Agregar'; ?>
                    </button>
                    
                    <?php if ($cupon_editar): ?>
                        <a href="gestionar_cupones.php" class="btn Dbtn-cancel">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!--lista de cupones-->
        <section class="list-section">
            <h2>Cupones existentes (<?php echo count($cupones); ?>)</h2>
            
            <?php if (empty($cupones)): ?>
                <p>No hay cupones registrados.</p>
            <?php else: ?>
                <div class="tabla-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Tipo</th>
                                <th>Valor</th>
                                <th>Mín. Compra</th>
                                <th>Usos</th>
                                <th>Válido Hasta</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cupones as $cupon): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($cupon['codigo']); ?></strong></td>
                                    <td>
                                        <span class="badge <?php echo $cupon['tipo'] == 'porcentaje' ? 'badge-porcentaje' : 'badge-monto'; ?>">
                                            <?php echo $cupon['tipo'] == 'porcentaje' ? 'Porcentaje' : 'Monto Fijo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($cupon['tipo'] == 'porcentaje'): ?>
                                            <?php echo $cupon['valor']; ?>%
                                        <?php else: ?>
                                            $<?php echo number_format($cupon['valor'], 2); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $cupon['min_compra'] > 0 ? '$' . number_format($cupon['min_compra'], 2) : 'Sin mínimo'; ?>
                                    </td>
                                    <td>
                                        <?php if ($cupon['usos_maximos']): ?>
                                            <?php echo $cupon['usos_actuales']; ?>/<?php echo $cupon['usos_maximos']; ?>
                                        <?php else: ?>
                                            Ilimitado
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $cupon['fecha_fin'] ? date('d/m/Y', strtotime($cupon['fecha_fin'])) : 'Permanente'; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $cupon['activo'] ? 'badge-active' : 'badge-inactive'; ?>">
                                            <?php echo $cupon['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="?editar=<?php echo $cupon['id']; ?>" class="btn btn-edit">Editar</a>
                                        <a href="?eliminar=<?php echo $cupon['id']; ?>" class="btn btn-delete" 
                                        onclick="return confirm('¿Estás seguro de eliminar este cupón?')">Eliminar</a>
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
        // Actualizar texto de ayuda según el tipo de descuento
        document.getElementById('tipo').addEventListener('change', function() {
            const valorHelp = document.getElementById('valor-help');
            if (this.value === 'porcentaje') {
                valorHelp.textContent = 'Ingrese el porcentaje de descuento (ej: 20 para 20%)';
            } else {
                valorHelp.textContent = 'Ingrese el monto fijo de descuento (ej: 50 para $50)';
            }
        });

        // Confirmación antes de eliminar
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('¿Estás seguro de que quieres eliminar este cupón?')) {
                    e.preventDefault();
                }
            });
        });

        // Inicializar texto de ayuda
        document.addEventListener('DOMContentLoaded', function() {
            const tipoSelect = document.getElementById('tipo');
            if (tipoSelect) {
                tipoSelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>