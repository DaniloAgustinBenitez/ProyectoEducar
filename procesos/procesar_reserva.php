<?php
// Modo detective activo por seguridad
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Solo el profesor gestiona reservas
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'Profesor') {
    die("<h1>Acceso Denegado</h1>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturamos la acción (por defecto será 'reservar')
    $accion = $_POST['accion'] ?? 'reservar';
    $espacio = $_POST['espacio']; 
    $fecha_seleccionada = $_POST['fecha_reserva'];

    $archivo_reservas = __DIR__ . '/../data/reservas.json';
    $reservas = [];

    if (file_exists($archivo_reservas)) {
        $reservas = json_decode(file_get_contents($archivo_reservas), true) ?: [];
    }

    if ($accion === 'reservar') {
        $profesor = $_SESSION['usuario'];
        
        // Validar si el horario ya está ocupado
        foreach ($reservas as $reserva) {
            if ($reserva['espacio'] === $espacio && $reserva['fecha'] === $fecha_seleccionada) {
                die("<h1>Error</h1><p>Este horario ya está reservado. Elegí otro.</p><a href='../dashboard.php'>Volver</a>");
            }
        }

        // Agregamos la reserva
        $reservas[] = [
            "profesor" => $profesor,
            "espacio" => $espacio,
            "fecha" => $fecha_seleccionada
        ];
        
    } elseif ($accion === 'eliminar') {
        // Filtramos el array dejando afuera la reserva que queremos borrar
        $reservas = array_filter($reservas, function($r) use ($espacio, $fecha_seleccionada) {
            // Devuelve true para todas las que NO coincidan con el espacio y fecha a borrar
            return !($r['espacio'] === $espacio && $r['fecha'] === $fecha_seleccionada);
        });
        
        // Reindexamos el array para que el JSON quede limpio
        $reservas = array_values($reservas);
    }

    // Guardamos los cambios
    file_put_contents($archivo_reservas, json_encode($reservas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Volvemos al panel
    header('Location: ../dashboard.php?vista=vista-reservas');
    exit;
}
?>