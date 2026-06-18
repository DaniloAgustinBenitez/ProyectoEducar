<?php
session_start();
require_once 'conexion.php';

// Verificamos que solo un docente pueda cargar notas
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'profesor') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alumno  = trim($_POST['alumno'] ?? '');
    $materia = trim($_POST['materia'] ?? '');
    $examen  = trim($_POST['nombre_examen'] ?? '');
    $nota    = trim($_POST['nota'] ?? '');

    if (!empty($alumno) && !empty($materia) && !empty($examen) && !empty($nota)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO calificaciones (alumno_nombre, materia, nombre_examen, nota) VALUES (:alumno, :mat, :examen, :nota)");
            $stmt->execute([
                ':alumno' => $alumno,
                ':mat'    => $materia,
                ':examen' => $examen,
                ':nota'   => $nota
            ]);
        } catch (PDOException $e) {
            error_log("Error guardando nota en SQL: " . $e->getMessage());
        }
    }
}
header('Location: ../dashboard.php?vista=vista-gestion-aula');
exit;
?>