<?php
session_start();

// Solo el profesor puede cambiar el menú
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'Profesor') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_menu = [
        "plato_principal" => $_POST['plato_principal'],
        "opcion_vegetariana" => $_POST['opcion_vegetariana'],
        "postre" => $_POST['postre']
    ];

    // Guardamos en el archivo menu.json
    file_put_contents(__DIR__ . '/../data/menu.json', json_encode($nuevo_menu, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Volvemos al panel
    header('Location: ../dashboard.php?vista=vista-comedor&msj=menu_actualizado');
    exit;
}
?>