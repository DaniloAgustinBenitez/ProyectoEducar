<?php
session_start();
require_once 'conexion.php';

// Verificamos permisos
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['preceptor', 'admin'])) {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $curso = $_POST['curso'] ?? '';
    $fecha = date('Y-m-d', strtotime($_POST['fecha'])); // Convertimos formato d-m-Y a Y-m-d para SQL
    $ausentes = $_POST['ausentes'] ?? []; // Array con nombres de alumnos

    if (!empty($curso) && !empty($fecha)) {
        try {
            // 1. Primero borramos por si acaso ya se cargó algo hoy y el preceptor quiere corregir
            $stmt_del = $pdo->prepare("DELETE FROM asistencias WHERE curso_clave = :curso AND fecha = :fecha");
            $stmt_del->execute([':curso' => $curso, ':fecha' => $fecha]);

            // 2. Insertamos los nuevos ausentes
            if (!empty($ausentes)) {
                $stmt_ins = $pdo->prepare("INSERT INTO asistencias (alumno_nombre, curso_clave, fecha, estado) VALUES (:alumno, :curso, :fecha, 'ausente')");
                
                foreach ($ausentes as $alumno) {
                    $stmt_ins->execute([
                        ':alumno' => $alumno,
                        ':curso'  => $curso,
                        ':fecha'  => $fecha
                    ]);
                }
            }
            
            header('Location: ../dashboard.php?vista=vista-asistencia-preceptor&msj=asistencia_guardada');
            exit;
            
        } catch (PDOException $e) {
            error_log("Error guardando asistencia: " . $e->getMessage());
        }
    }
}

header('Location: ../dashboard.php?vista=vista-asistencia-preceptor');
exit;
?>