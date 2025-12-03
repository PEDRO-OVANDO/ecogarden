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

//configuracion para subida de imágenes
$directorio_imagenes = '../../uploads/productos/';
$tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$max_tamano = 5 * 1024 * 1024; // 5MB

//crear directorio si no existe
if (!file_exists($directorio_imagenes)) {
    mkdir($directorio_imagenes, 0777, true);
}

//agregar/editar producto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);
    $categoria_id = intval($_POST['categoria_id']);
    $tipo = $_POST['tipo'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    //validaciones 
    if (empty($nombre) || $precio <= 0) {
        $error = 'Nombre y precio son obligatorios. El precio debe ser mayor a 0.';
    } elseif (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] === UPLOAD_ERR_NO_FILE) {
    $error = 'La imagen es obligatoria.';
    } else {
        //procesar imagen
        $nombre_imagen = '';
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $archivo_temporal = $_FILES['imagen']['tmp_name'];
            $nombre_archivo = $_FILES['imagen']['name'];
            $tamano_archivo = $_FILES['imagen']['size'];
            $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
            
            //validar tipo de archivo
            if (!in_array($extension, $tipos_permitidos)) {
                $error = 'Tipo de archivo no permitido. Use: ' . implode(', ', $tipos_permitidos);
            } elseif ($tamano_archivo > $max_tamano) {
                $error = 'El archivo es demasiado grande. Máximo 5MB.';
            } else {
                //generar nombre unico
                $nombre_imagen = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $nombre) . '.' . $extension;
                $ruta_destino = $directorio_imagenes . $nombre_imagen;
                
                if (!move_uploaded_file($archivo_temporal, $ruta_destino)) {
                    $error = 'Error al subir la imagen.';
                }
            }
        }
        
        if (empty($error)) {
            if (isset($_POST['producto_id']) && !empty($_POST['producto_id'])) {
                //editar producto existente
                $producto_id = intval($_POST['producto_id']);
                
                //si hay nueva imagen, eliminar la anterior
                if (!empty($nombre_imagen) && !empty($_POST['imagen_actual'])) {
                    $imagen_anterior = $directorio_imagenes . $_POST['imagen_actual'];
                    if (file_exists($imagen_anterior)) {
                        unlink($imagen_anterior);
                    }
                }
                
                //actualizar
                $sql = "UPDATE productos SET 
                        nombre = '$nombre', 
                        descripcion = '$descripcion', 
                        precio = $precio, 
                        stock = $stock, 
                        categoria_id = $categoria_id, 
                        tipo = '$tipo', 
                        activo = $activo";
                
                if (!empty($nombre_imagen)) {
                    $sql .= ", imagen = '$nombre_imagen'";
                }
                
                $sql .= " WHERE id = $producto_id";
                
                if (mysqli_query($conexion, $sql)) {
                    $mensaje = 'Producto actualizado correctamente.';
                } else {
                    $error = 'Error al actualizar producto: ' . mysqli_error($conexion);
                }
            } else {
                //agregar nuevo producto
                $sql = "INSERT INTO productos (nombre, descripcion, precio, stock, categoria_id, tipo, activo, imagen) 
                        VALUES ('$nombre', '$descripcion', $precio, $stock, $categoria_id, '$tipo', $activo, " . 
                        (!empty($nombre_imagen) ? "'$nombre_imagen'" : "NULL") . ")";
                
                if (mysqli_query($conexion, $sql)) {
                    $mensaje = 'Producto agregado correctamente.';
                    //limpiar el formulario
                    $_POST = array();
                } else {
                    $error = 'Error al agregar producto: ' . mysqli_error($conexion);
                }
            }
        }
    }
}

//eliminar producto
if (isset($_GET['eliminar'])) {
    $producto_id = intval($_GET['eliminar']);
    
    //obtener informacion de la imagen antes de eliminar
    $sql_imagen = "SELECT imagen FROM productos WHERE id = $producto_id";
    $result_imagen = mysqli_query($conexion, $sql_imagen);
    if ($result_imagen && $imagen = mysqli_fetch_assoc($result_imagen)) {
        //eliminar imagen del servidor
        if (!empty($imagen['imagen'])) {
            $ruta_imagen = $directorio_imagenes . $imagen['imagen'];
            if (file_exists($ruta_imagen)) {
                unlink($ruta_imagen);
            }
        }
    }
    
    $sql = "DELETE FROM productos WHERE id = $producto_id";
    
    if (mysqli_query($conexion, $sql)) {
        $mensaje = 'Producto eliminado correctamente.';
    } else {
        $error = 'Error al eliminar producto: ' . mysqli_error($conexion);
    }
}

//lista de productos
$productos = [];
$result = mysqli_query($conexion, 
    "SELECT p.*, c.nombre as categoria_nombre 
    FROM productos p 
    LEFT JOIN categorias c ON p.categoria_id = c.id 
    ORDER BY p.id DESC"
);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $productos[] = $row;
    }
}

//categorías para el formulario
$categorias = [];
$result_cat = mysqli_query($conexion, "SELECT * FROM categorias ORDER BY nombre");
if ($result_cat) {
    while ($row = mysqli_fetch_assoc($result_cat)) {
        $categorias[] = $row;
    }
}

//si se quiere editar, obtener datos del producto
$producto_editar = null;
if (isset($_GET['editar'])) {
    $producto_id = intval($_GET['editar']);
    $result = mysqli_query($conexion, "SELECT * FROM productos WHERE id = $producto_id");
    if ($result && mysqli_num_rows($result) > 0) {
        $producto_editar = mysqli_fetch_assoc($result);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Productos - EcoGarden</title>
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

        .btn-primary:active {
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
        
        /*cuando el cursor pasa por encima */
        .btn-edit:hover {
        background-color: #ffc107;       
        color: #000;                     
        }

        /*cuando se presiona */
        .btn-edit:active {
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

        .btn-delete:active {
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

        .btn-cancel:active {
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

        /* Estilos para la imagen */
        .imagen-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            margin-top: 0.5rem;
        }
        
        .imagen-actual {
            display: block;
            max-width: 100px;
            max-height: 100px;
            border-radius: 4px;
            margin-top: 0.5rem;
        }
        
        .info-imagen {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.5rem;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
        }
        /* En la sección <style> de gestionar_productos.php, agrega: */
@media (prefers-color-scheme: dark) {
    body {
        background: #f8f9fa !important;
    }
    
    .form-section, 
    .list-section {
        background: white !important;
        color: #333 !important;
    }
    
    .form-control {
        background: white !important;
        color: #333 !important;
        border-color: #e9ecef !important;
    }
}
    </style>
    <link rel="stylesheet" href="../css/responsiveGestionPr.css">
</head>
<body>
    <header class="admin-header">
        <nav class="admin-nav">
            <h1>Gestionar productos</h1>
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

        <!--formulario para agregar/editar producto-->
        <section class="form-section">
            <h2 style="text-align: center;"><?php echo $producto_editar ? 'Editar Producto' : 'Agregar nuevo producto'; ?></h2>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <?php if ($producto_editar): ?>
                    <input type="hidden" name="producto_id" value="<?php echo $producto_editar['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label" for="nombre">Nombre del producto</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" 
                        value="<?php echo $producto_editar ? $producto_editar['nombre'] : (isset($_POST['nombre']) ? $_POST['nombre'] : ''); ?>" 
                        required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="descripcion">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo $producto_editar ? $producto_editar['descripcion'] : (isset($_POST['descripcion']) ? $_POST['descripcion'] : ''); ?></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label" for="precio">Precio</label>
                        <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0"
                            value="<?php echo $producto_editar ? $producto_editar['precio'] : (isset($_POST['precio']) ? $_POST['precio'] : ''); ?>" 
                            required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="stock">Stock</label>
                        <input type="number" class="form-control" id="stock" name="stock" min="0"
                            value="<?php echo $producto_editar ? $producto_editar['stock'] : (isset($_POST['stock']) ? $_POST['stock'] : '0'); ?>">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label" for="categoria_id">Categoría</label>
                        <select class="form-control" id="categoria_id" name="categoria_id" style="font-family:'Century Gothic', Arial;" required>
                            <option value="">Seleccionar categoría</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>" 
                                    <?php echo ($producto_editar && $producto_editar['categoria_id'] == $categoria['id']) || (isset($_POST['categoria_id']) && $_POST['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                                    <?php echo $categoria['nombre']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="tipo">Tipo</label>
                        <select class="form-control" id="tipo" name="tipo" style="font-family:'Century Gothic', Arial;" required>
                            <option value="kit" <?php echo ($producto_editar && $producto_editar['tipo'] == 'kit') || (isset($_POST['tipo']) && $_POST['tipo'] == 'kit') ? 'selected' : ''; ?>>Kit</option>
                            <option value="herramienta" <?php echo ($producto_editar && $producto_editar['tipo'] == 'herramienta') || (isset($_POST['tipo']) && $_POST['tipo'] == 'herramienta') ? 'selected' : ''; ?>>Herramienta</option>
                            <option value="insumo" <?php echo ($producto_editar && $producto_editar['tipo'] == 'insumo') || (isset($_POST['tipo']) && $_POST['tipo'] == 'insumo') ? 'selected' : ''; ?>>Insumo</option>
                            <option value="maceta" <?php echo ($producto_editar && $producto_editar['tipo'] == 'maceta') || (isset($_POST['tipo']) && $_POST['tipo'] == 'maceta') ? 'selected' : ''; ?>>Maceta</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="imagen">Imagen del Producto</label>
                    <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*" style="font-family:'Century Gothic', Arial;" required>
                    <small class="info-imagen">Formatos: JPG, PNG, GIF, WEBP. Máximo 5MB.</small>
                    
                    <?php if ($producto_editar && !empty($producto_editar['imagen'])): ?>
                        <div>
                            <strong>Imagen actual:</strong>
                            <img src="../../uploads/productos/<?php echo $producto_editar['imagen']; ?>" 
                                alt="<?php echo $producto_editar['nombre']; ?>" 
                                class="imagen-actual">
                            <br>
                            <small class="info-imagen"><?php echo $producto_editar['imagen']; ?></small>
                        </div>
                    <?php endif; ?>
                    
                    <div id="preview-container" style="display: none;">
                        <strong>Vista previa:</strong>
                        <img id="preview-imagen" class="imagen-preview">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="activo" name="activo" value="1" 
                            <?php echo ($producto_editar && $producto_editar['activo']) || (isset($_POST['activo']) && $_POST['activo']) ? 'checked' : ''; ?>>
                        <label for="activo">Producto activo (disponible para venta)</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $producto_editar ? 'Actualizar' : 'Agregar'; ?>
                    </button>
                    
                    <?php if ($producto_editar): ?>
                        <a href="gestionar_productos.php" class="btn Dbtn-cancel">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!--lista de productos-->
        <section class="list-section">
            <h2>Productos existentes (<?php echo count($productos); ?>)</h2>
            
            <?php if (empty($productos)): ?>
                <p>No hay productos registrados.</p>
            <?php else: ?>
                <div class="tabla-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Categoría</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($producto['imagen'])): ?>
                                            <img src="../../uploads/productos/<?php echo $producto['imagen']; ?>" 
                                                alt="<?php echo $producto['nombre']; ?>" 
                                                style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                        <?php else: ?>
                                            <span style="color: #999;">Sin imagen</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                    <td>$<?php echo number_format($producto['precio'], 2); ?></td>
                                    <td><?php echo $producto['stock']; ?></td>
                                    <td><?php echo $producto['categoria_nombre'] ?? 'Sin categoría'; ?></td>
                                    <td><?php echo ucfirst($producto['tipo']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $producto['activo'] ? 'badge-active' : 'badge-inactive'; ?>">
                                            <?php echo $producto['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="?editar=<?php echo $producto['id']; ?>" class="btn btn-edit">Editar</a>
                                        <a href="?eliminar=<?php echo $producto['id']; ?>" class="btn btn-delete" 
                                        onclick="return confirm('¿Estás seguro de eliminar este producto?')">Eliminar</a>
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
         //vista previa de imagen
        document.getElementById('imagen').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewContainer = document.getElementById('preview-container');
            const previewImagen = document.getElementById('preview-imagen');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImagen.src = e.target.result;
                    previewContainer.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                previewContainer.style.display = 'none';
            }
        });

        //confirmacion antes de eliminar
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('¿Estás seguro de que quieres eliminar este producto?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>