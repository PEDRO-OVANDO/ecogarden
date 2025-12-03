<?php
session_start();
require_once '../../config/database.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = 'Por favor ingresa tu email.';
    } else {
        //verificar si el email existe
        $sql = "SELECT id, nombre FROM clientes WHERE email = '$email'";
        $result = mysqli_query($conexion, $sql);
        
    if ($result && mysqli_num_rows($result) > 0) {
        $usuario = mysqli_fetch_assoc($result);
        
        //generar token unico
        $token = bin2hex(random_bytes(32));
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour')); //valido por una hora
        
        //guardar token en la base de datos
        $sql_token = "UPDATE clientes SET 
                    reset_token = '$token',
                    reset_expiracion = '$expiracion'
                    WHERE id = {$usuario['id']}";
        
        if (mysqli_query($conexion, $sql_token)) {
            //incluir PHPMailer
            require_once '../../config/email.php';
            $emailController = new EmailController();
            
            //enviar email de recuperación
            if ($emailController->enviarEmailRecuperacion($email, $usuario['nombre'], $token)) {
                $mensaje = "Se ha enviado un enlace de recuperación a tu email. Revisa tu bandeja de entrada.";
                error_log("Intentando enviar email a: $email");
                error_log("Token generado: $token");
            } else {
                $error = "Error al enviar el email. Por favor, intenta nuevamente.";
                // Opcional: Mostrar enlace para pruebas
                $enlace_reset = "http://" . $_SERVER['HTTP_HOST'] . "/ecogarden2/views/clientes/reset_password.php?token=$token";
                $mensaje = "Enlace de recuperación (para pruebas): <a href='$enlace_reset'>Haz clic aquí</a>";
            }
            
        } else {
            $error = 'Error al generar el token de recuperación.';
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
    <title>Recuperar Contraseña - EcoGarden</title>
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
            font-family: 'Century Gothic', Arial, sans-serif;
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
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="nav">
                <a href="../../public/index.php" class="logo">
                    <i class="fas fa-leaf"></i>
                    <span>EcoGarden</span>
                </a>
                <div class="nav-links">
                    <a href="login.php" class="btn btn-cancel">Volver al Login</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="login-container">
        <div class="container">
            <div class="login-card">
                <h1 class="login-title">Recuperar Contraseña</h1>
                
                <?php if ($mensaje): ?>
                    <div class="alert alert-success">
                        <?php echo $mensaje; ?>
                    </div>
                <?php endif; ?>
                
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
                    
                    <button type="submit" class="btn btn-primary">Enviar Enlace de Recuperación</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>