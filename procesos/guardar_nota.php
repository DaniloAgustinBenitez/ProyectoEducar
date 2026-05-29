<?php
session_start();

// Control de seguridad
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Limpiamos todos los datos que vienen del formulario
    $alumno = trim($_POST['alumno'] ?? '');
    $materia = trim($_POST['materia'] ?? '');
    $nombre_examen = trim($_POST['nombre_examen'] ?? '');
    $nota = floatval($_POST['nota'] ?? 0);

    if ($alumno !== '' && $materia !== '' && $nombre_examen !== '') {
        $archivo_notas = __DIR__ . '/../data/calificaciones.json';
        $notas_actuales = [];

        // Leer el archivo existente
        if (file_exists($archivo_notas)) {
            $contenido = file_get_contents($archivo_notas);
            if (!empty($contenido)) {
                $notas_actuales = json_decode($contenido, true) ?: [];
            }
        }

        // Armar la estructura si no existe
        if (!isset($notas_actuales[$alumno])) {
            $notas_actuales[$alumno] = [];
        }
        if (!isset($notas_actuales[$alumno][$materia])) {
            $notas_actuales[$alumno][$materia] = [];
        }

        // Agregar la nueva calificación
        $notas_actuales[$alumno][$materia][] = [
            "examen" => $nombre_examen,
            "nota" => $nota
        ];

        // Escribir en el archivo
        file_put_contents($archivo_notas, json_encode($notas_actuales, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

// Redireccionar de vuelta al aula
header('Location: ../dashboard.php?vista=vista-gestion-aula');
exit;
?>