<?php
session_start();
// Sincronizamos el reloj antes de guardar la falta
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Validamos que solo puedan entrar Profesores (primaria) o Preceptores
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['preceptor', 'profesor'])) {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $curso = $_POST['curso'] ?? '';
    $fecha = $_POST['fecha'] ?? date('d-m-Y');
    
    // Si no se marcó ninguno, no envía array, entonces asumimos asistencia perfecta
    $ausentes = $_POST['ausentes'] ?? [];

    if (!empty($curso)) {
        $archivo_asistencias = __DIR__ . '/../data/asistencias.json';
        $asistencias = file_exists($archivo_asistencias) ? json_decode(file_get_contents($archivo_asistencias), true) : [];

        if (!isset($asistencias[$curso])) {
            $asistencias[$curso] = [];
        }

        // Guardamos los ausentes del día
        $asistencias[$curso][$fecha] = $ausentes;

        file_put_contents($archivo_asistencias, json_encode($asistencias, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    header('Location: ../dashboard.php?vista=vista-asistencia-preceptor');
    exit;
}

header('Location: ../dashboard.php');
exit;
?>