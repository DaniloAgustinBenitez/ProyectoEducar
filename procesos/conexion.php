<?php
<<<<<<< HEAD
// Detecta si estamos en local (XAMPP) o en producción (InfinityFree)
if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
    $host     = 'localhost';
    $dbname   = 'educar_db';
    $username = 'root';
    $password = '';
} else {
    // ========= COMPLETAR CON LOS DATOS DE INFINITYFREE =========
    $host     = 'sql311.infinityfree.com';
    $dbname   = 'if0_42238951_educar';
    $username = 'if0_42238951';
    $password = 'u77fQfc4bJ3I';
    // ============================================================
}
=======
// Configuración de la base de datos (Entorno Local - XAMPP)
$host = 'sql101.infinityfree.com';
$dbname = 'if0_42106332_gestion_colegio';
$username = 'if0_42106332'; // XAMPP usa 'root' por defecto
$password = 'k0o8QJSnCG8';     // XAMPP no tiene contraseña por defecto
>>>>>>> 06c46a04af358fcea21eaa10eeedf3cbe4504e52

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<div style='background: #fff5f2; color: #f15a24; padding: 20px; font-family: sans-serif;'>
            <h3>Error de Base de Datos</h3>
            <p>No se pudo conectar a MySQL.</p>
            <p><small>Host: $host | DB: $dbname | User: $username</small></p>
            <p><small>Detalle: " . htmlspecialchars($e->getMessage()) . "</small></p>
         </div>");
}
?>
