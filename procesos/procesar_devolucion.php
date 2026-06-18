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
        require_once 'conexion.php';
        try {
            // 1. Guardamos la devolución en la entrega
            $stmt_upd = $pdo->prepare("UPDATE actividad_entregas SET devolucion = :dev, nota = :nota WHERE actividad_id = :act_id AND alumno_nombre = :alumno");
            $stmt_upd->execute([':dev' => $comentario, ':nota' => $nota, ':act_id' => $id_actividad, ':alumno' => $alumno]);

            // 2. Si el profe lo pidió, mandamos la nota directo al boletín
            if ($guardar_boletin === 'si' && !empty($nota) && !empty($materia)) {
                $stmt_titulo = $pdo->prepare("SELECT titulo FROM actividades WHERE id = :id LIMIT 1");
                $stmt_titulo->execute([':id' => $id_actividad]);
                $act = $stmt_titulo->fetch();
                $titulo_actividad = $act ? $act['titulo'] . " (TP)" : "Actividad Evaluada (TP)";

                $stmt_cal = $pdo->prepare("INSERT INTO calificaciones (alumno_nombre, materia, nombre_examen, nota) VALUES (:alumno, :mat, :examen, :nota)");
                $stmt_cal->execute([':alumno' => $alumno, ':mat' => $materia, ':examen' => $titulo_actividad, ':nota' => $nota]);
            }
        } catch (PDOException $e) {
            error_log("Error guardando devolución: " . $e->getMessage());
        }
    }
}

header('Location: ../dashboard.php?vista=vista-actividades');
exit;
?>