<?php
session_start();
require_once '../../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    //validaciones
    if (empty($email) || empty($password)) {
        $error = 'Por favor ingresa email y contraseña.';
    } else {
        //buscar usuario en la base de datos
        $sql = "SELECT id, nombre, email, password, tipo_usuario FROM clientes WHERE email = '$email'";
        $result = mysqli_query($conexion, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $usuario = mysqli_fetch_assoc($result);
            
            //verificar contraseña
            if (password_verify($password, $usuario['password'])) {
                // Login exitoso
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_tipo'] = $usuario['tipo_usuario'];
                $_SESSION['loggedin'] = true;
                
                //redirigir según tipo de usuario y si venia del carrito
                if (isset($_GET['redirect']) && $_GET['redirect'] == 'checkout') {
                    //si venia del carrito, ir al checkout
                    header("Location: ../usuario/checkoutUsuario.php");
                }else if ($usuario['tipo_usuario'] === 'administrador') {
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: ../clientes/perfil.php");
                }
                exit;
            } else {
                $error = 'Contraseña incorrecta.';
            }
        } else {
            $error = 'No existe una cuenta con este email.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - EcoGarden</title>
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
        
        /*formulario de login*/
        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        
        .login-card {
            background: var(--white);
            padding: 3rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-title {
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
        
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .admin-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1rem;
            font-size: 0.9rem;
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

    <!--formulario de login-->
    <div class="login-container">
        <div class="container">
            <div class="login-card">
                <h1 class="login-title">Iniciar Sesión</h1>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                            required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Ingresar</button>
                </form>
                
                <div class="register-link">
                    ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
                </div>

                <div class="forgot-password-link" style="text-align: center; margin-top: 1rem;">
                    <a href="olvide_password.php" style="color: var(--accent-color); text-decoration: none;">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>