<?php
// Configuración de la base de datos (Entorno Local - XAMPP)
$host = 'localhost';
$dbname = 'educar_db';
$username = 'root'; // XAMPP usa 'root' por defecto
$password = '';     // XAMPP no tiene contraseña por defecto

try {
    // Creamos la conexión usando PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Configuramos PDO para que nos avise si hay errores críticos
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Le decimos que nos devuelva los datos como arrays asociativos (como hacíamos con el JSON)
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Si la conexión falla, detenemos todo y mostramos el error
    die("<div style='background: #fff5f2; color: #f15a24; padding: 20px; font-family: sans-serif;'>
            <h3>🚨 Error crítico de Base de Datos:</h3>
            <p>No se pudo conectar a MySQL. Revisá que XAMPP tenga el módulo MySQL encendido.</p>
            <p><b>Detalle técnico:</b> " . $e->getMessage() . "</p>
         </div>");
}
?>