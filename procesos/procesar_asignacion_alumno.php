<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion        = $_POST['accion'] ?? '';
    $curso_clave   = $_POST['curso_clave'] ?? '';
    $alumno_nombre = $_POST['alumno_nombre'] ?? '';

    if (!empty($accion) && !empty($curso_clave) && !empty($alumno_nombre)) {
        try {
            if ($accion === 'asignar') {
                // Forzamos que se borre de cualquier asignación anterior para evitar duplicados
                $stmt_del = $pdo->prepare("DELETE FROM matricula WHERE alumno_nombre = :alumno");
                $stmt_del->execute([':alumno' => $alumno_nombre]);

                // Lo registramos en su curso actual
                $stmt = $pdo->prepare("INSERT INTO matricula (alumno_nombre, curso_clave) VALUES (:alumno, :curso)");
                $stmt->execute([
                    ':alumno' => $alumno_nombre,
                    ':curso'  => $curso_clave
                ]);
                
                header('Location: ../dashboard.php?vista=vista-asignar-alumnos&msj=alumno_asignado');
                exit;
            } elseif ($accion === 'quitar') {
                $stmt = $pdo->prepare("DELETE FROM matricula WHERE alumno_nombre = :alumno AND curso_clave = :curso");
                $stmt->execute([
                    ':alumno' => $alumno_nombre,
                    ':curso'  => $curso_clave
                ]);
                
                header('Location: ../dashboard.php?vista=vista-asignar-alumnos&msj=alumno_removido');
                exit;
            }
        } catch (PDOException $e) {
            error_log("Error en procesar_asignacion_alumno: " . $e->getMessage());
        }
    }
}

header('Location: ../dashboard.php?vista=vista-asignar-alumnos');
exit;