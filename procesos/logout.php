<?php
// Arrancamos la sesión solo para poder destruirla
session_start();

// Vaciamos todas las variables de sesión
$_SESSION = array();

// Destruimos la sesión (cortamos la pulsera VIP)
session_destroy();

// Lo mandamos de vuelta a la página de login
header('Location: ../login.php');
exit;
?>