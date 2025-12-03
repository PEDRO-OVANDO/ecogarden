<?php
session_start();
require_once '../../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $experiencia = $_POST['experiencia_jardineria'];
    
    //validaciones
    if (empty($nombre) || empty($email) || empty($password)) {
        $error = 'Todos los campos obligatorios deben ser llenados.';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        //el email existe?
        $check_email = "SELECT id FROM clientes WHERE email = '$email'";
        $result = mysqli_query($conexion, $check_email);
        
        if (mysqli_num_rows($result) > 0) {
            $error = 'Este email ya está registrado.';
        } else {
            //encriptar contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            //insertar en la base de datos
            $sql = "INSERT INTO clientes (nombre, email, telefono, password, experiencia_jardineria, tipo_usuario) 
                    VALUES ('$nombre', '$email', '$telefono', '$password_hash', '$experiencia', 'cliente')";
            
            if (mysqli_query($conexion, $sql)) {
                $success = 'Cuenta creada exitosamente. Ahora puedes iniciar sesión.';
                //limpiar el formulario
                $_POST = array();
            } else {
                $error = 'Error al crear la cuenta: ' . mysqli_error($conexion);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - EcoGarden</title>
    <style>
        :root {
            --primary-color: #2d5a27;
            --secondary-color: #4CAF50;
            --accent-color: #ff6b35;
            --text-color: #333;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --gray: #6c757d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Century Gothic', Arial, sans-serif; background: var(--light-bg);
            background: var(--light-bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /*header*/
        .header {
            background: var(--white);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        
        /*formulario de registro*/
        .register-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        
        .register-card {
            background: var(--white);
            padding: 3rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
        }
        
        .register-title {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 2rem;
            font-size: 2rem;
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
        
        .select-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 1rem;
            background: var(--white);
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: 2px solid #23421f;
            border-radius: 6px;
            text-decoration: none;
            /*font-weight: 600;*/
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            width: 100%;
            font-size: 1rem;
            font-family: 'Century Gothic', Arial, sans-serif;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background: #23421f;
        }

        .btn-cancel {
            background: none;
            color: var(--black);
        }
        
        .btn-cancel:hover {
        background-color: #23421f;       
        color: #ffffffff;                     
        }

        /*cuando se presiona */
        .btn-cancel:active {
            background-color:#23421f;
            color: #ffffffff;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }

        .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        }

        .form-full {
        grid-column: span 2;
        }

    </style>
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
                <div class="nav-links">
                    <a href="../../public/index.php" class="btn btn-cancel">Volver al Inicio</a>
                </div>
            </nav>
        </div>
    </header>

    <!--formulario de registro-->
    <div class="register-container">
        <div class="container">
            <div class="register-card">
                <h1 class="register-title">Crear Cuenta</h1>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="nombre">Nombre Completo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" 
                                required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="telefono">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="experiencia_jardineria">Experiencia en Jardinería</label>
                            <select class="select-control" id="experiencia_jardineria" name="experiencia_jardineria">
                                <option value="principiante" <?php echo (isset($_POST['experiencia_jardineria']) && $_POST['experiencia_jardineria'] == 'principiante') ? 'selected' : ''; ?>>Principiante</option>
                                <option value="intermedio" <?php echo (isset($_POST['experiencia_jardineria']) && $_POST['experiencia_jardineria'] == 'intermedio') ? 'selected' : ''; ?>>Intermedio</option>
                                <option value="avanzado" <?php echo (isset($_POST['experiencia_jardineria']) && $_POST['experiencia_jardineria'] == 'avanzado') ? 'selected' : ''; ?>>Avanzado</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="password">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    
                        <div class="form-full">
                            <button type="submit" class="btn btn-primary">Crear Cuenta</button>
                        </div>
                    </div>
                </form>
                
                <div class="login-link">
                    ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>