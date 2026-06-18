<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alumno_nombre  = trim($_POST['alumno_nombre'] ?? '');
    $tutor_username = trim($_POST['tutor_username'] ?? '');

    if (!empty($alumno_nombre)) {
        try {
            // Siempre borramos la asignación previa de este alumno
            $stmt_del = $pdo->prepare("DELETE FROM tutores_alumnos WHERE alumno_nombre = :alumno");
            $stmt_del->execute([':alumno' => $alumno_nombre]);

            // Si se eligió un tutor (no "Sin tutor"), lo insertamos
            if (!empty($tutor_username)) {
                $stmt_ins = $pdo->prepare("INSERT INTO tutores_alumnos (tutor_username, alumno_nombre) VALUES (:tutor, :alumno)");
                $stmt_ins->execute([':tutor' => $tutor_username, ':alumno' => $alumno_nombre]);
            }

            header('Location: ../dashboard.php?vista=vista-asignar-alumnos&msj=usuario_modificado');
            exit;
        } catch (PDOException $e) {
            error_log("Error en procesar_asignacion_tutor: " . $e->getMessage());
        }
    }
}

header('Location: ../dashboard.php?vista=vista-asignar-alumnos');
exit;
