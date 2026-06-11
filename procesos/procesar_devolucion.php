<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'profesor') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_actividad = trim($_POST['id_actividad'] ?? '');
    $alumno = trim($_POST['alumno'] ?? '');
    $materia = trim($_POST['materia'] ?? '');
    $comentario = trim($_POST['comentario'] ?? '');
    $nota = trim($_POST['nota'] ?? '');
    $guardar_boletin = $_POST['guardar_boletin'] ?? 'no';

    if (!empty($id_actividad) && !empty($alumno) && !empty($comentario)) {
        
        // 1. Guardamos la devolución en la actividad
        $archivo_actividades = __DIR__ . '/../data/actividades.json';
        $actividades = file_exists($archivo_actividades) ? json_decode(file_get_contents($archivo_actividades), true) : [];
        $titulo_actividad = "Actividad Evaluada";

        foreach ($actividades as &$act) {
            if ($act['id'] === $id_actividad && isset($act['entregas'][$alumno])) {
                $act['entregas'][$alumno]['devolucion'] = $comentario;
                $act['entregas'][$alumno]['nota'] = $nota;
                $titulo_actividad = $act['titulo'];
                break;
            }
        }
        file_put_contents($archivo_actividades, json_encode($actividades, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 2. Si el profe lo pidió, mandamos la nota directo al boletín
        if ($guardar_boletin === 'si' && !empty($nota) && !empty($materia)) {
            $archivo_notas = __DIR__ . '/../data/calificaciones.json';
            $calificaciones = file_exists($archivo_notas) ? json_decode(file_get_contents($archivo_notas), true) : [];

            if (!isset($calificaciones[$alumno])) $calificaciones[$alumno] = [];
            if (!isset($calificaciones[$alumno][$materia])) $calificaciones[$alumno][$materia] = [];

            $calificaciones[$alumno][$materia][] = [
                "examen" => $titulo_actividad . " (TP)",
                "nota" => $nota
            ];

            file_put_contents($archivo_notas, json_encode($calificaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}

header('Location: ../dashboard.php?vista=vista-actividades');
exit;
?>