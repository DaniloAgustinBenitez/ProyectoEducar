<?php
session_start();

// Verificamos que sea administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recogemos los datos del formulario de dashboard.php
    $plato_principal = trim($_POST['plato_principal'] ?? '');
    $opcion_vegetariana = trim($_POST['opcion_vegetariana'] ?? '');
    $postre = trim($_POST['postre'] ?? '');

    if (!empty($plato_principal) && !empty($opcion_vegetariana) && !empty($postre)) {
        
        $menu_actualizado = [
            "plato_principal" => $plato_principal,
            "opcion_vegetariana" => $opcion_vegetariana,
            "postre" => $postre
        ];

        // MAGIA DE RUTAS: Salimos de la carpeta "procesos" y entramos a "data"
        $archivo_menu = __DIR__ . '/../data/menu.json';
        
        // Por las dudas, si la carpeta data no existe, la crea
        if (!file_exists(__DIR__ . '/../data')) {
            mkdir(__DIR__ . '/../data', 0777, true);
        }

        // Guardamos el JSON de forma ordenada
        file_put_contents($archivo_menu, json_encode($menu_actualizado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    // Lo devolvemos al dashboard en la pestaña del comedor
    header('Location: ../dashboard.php?vista=vista-comedor');
    exit;
}

header('Location: ../dashboard.php');
exit;
?>