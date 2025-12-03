<?php
session_start();
require_once '../../config/database.php';

//verificar login
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

$cliente_id = $_SESSION['usuario_id'];
$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $experiencia_jardineria = $_POST['experiencia_jardineria'] ?? 'principiante';
    $nueva_password = $_POST['nueva_password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';
    
    //validaciones
    //nomnre y email obligatorios
    if (empty($nombre)) {
        $error = 'El nombre es obligatorio.';
    } elseif (empty($email)) {
        $error = 'El email es obligatorio.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del email no es válido.';
    }
    
    //verificar si el email ya existe (excluyendo al usuario actual)
    if (empty($error)) {
        $sql_check_email = "SELECT id FROM clientes WHERE email = '$email' AND id != $cliente_id";
        $result_check = mysqli_query($conexion, $sql_check_email);
        if ($result_check && mysqli_num_rows($result_check) > 0) {
            $error = 'Este email ya está registrado por otro usuario.';
        }
    }

    if (!empty($nueva_password)) {
        if (strlen($nueva_password) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif ($nueva_password !== $confirmar_password) {
            $error = 'Las contraseñas no coinciden.';
        }
    }
    
    if (empty($error)) {
        //actualizar
        $updates = [
            "nombre = '" . mysqli_real_escape_string($conexion, $nombre) . "'",
            "email = '" . mysqli_real_escape_string($conexion, $email) . "'",
            "telefono = '" . mysqli_real_escape_string($conexion, $telefono) . "'",
            "direccion = '" . mysqli_real_escape_string($conexion, $direccion) . "'",
            "experiencia_jardineria = '" . mysqli_real_escape_string($conexion, $experiencia_jardineria) . "'"
        ];
        
        //si hay nueva contraseña, agregarla al update
        if (!empty($nueva_password)) {
            $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
            $updates[] = "password = '" . mysqli_real_escape_string($conexion, $password_hash) . "'";
        }
        
        $sql = "UPDATE clientes SET " . implode(', ', $updates) . " WHERE id = $cliente_id";
        
        if (mysqli_query($conexion, $sql)) {
            //actualizar en la sesion 
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['usuario_email'] = $email;

            $_SESSION['mensaje_exito'] = 'Perfil actualizado correctamente.';
            header("Location: perfil.php");
            exit;
        } else {
            $error = 'Error al actualizar el perfil: ' . mysqli_error($conexion);
        }
    }
    
    //si hay error, guardarlo en sesion para mostrar en perfil.php
    if (!empty($error)) {
        $_SESSION['error_perfil'] = $error;
        header("Location: perfil.php");
        exit;
    }
} else {
    header("Location: perfil.php");
    exit;
}
?>