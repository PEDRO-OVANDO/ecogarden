<?php
// DATOS DE DIGITALOCEAN (Búscalos en tu panel de la base de datos)
// ----------------------------------------------------------------
$hostname = "ecogarden2025-bd-do-user-30009942-0.f.db.ondigitalocean.com"; // Tu HOST largo
$username = "doadmin";                                    // Usuario
$password = "AVNS__0jgvDF2cdAeMpN710a";                         // La contraseña que te dio DO
$database = "defaultdb";                                  // OJO: En DBeaver usaste "defaultdb"
$port     = 25060;                                        // ¡IMPORTANTE! No es 3306

// INTENTO DE CONEXIÓN
// Nota: Agregamos el $port al final, es obligatorio en DigitalOcean.
$conexion = mysqli_connect($hostname, $username, $password, $database, $port);

// VERIFICAR ERRORES
if (!$conexion) {
    die("Fallo la conexion a la base de datos: " . mysqli_connect_error());
}

// (Opcional) Forzar caracteres latinos para que no salgan símbolos raros
mysqli_set_charset($conexion, "utf8");

?>
